<?php

namespace App\Services\Depot;

use App\Enums\Ateliers\TypeVetement;
use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\StatutVetement;
use App\Enums\Upcycling\Difficulte;
use App\Enums\Upcycling\StatutProjet;
use App\Models\Association;
use App\Models\Atelier;
use App\Models\DemandeReparation;
use App\Models\Don;
use App\Models\ProjetUpcycling;
use App\Models\Vetement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Le cycle TexTileCycle : un vêtement déposé (gestion Dépôt) poursuit sa vie dans les autres gestions.
 *
 *   Dépôt ─┬─► Réparation  : demande envoyée à un atelier, diagnostic calculé  (Ateliers & réparations)
 *          ├─► Upcycling   : projet brouillon créé à partir du vêtement, qui devient « Recyclé »  (Upcycling)
 *          └─► Don         : don à une association, le vêtement devient « Donné »  (Associations & dons)
 *
 * Chaque action est une transaction : l'enregistrement est créé dans l'autre gestion, relié au vêtement
 * par vetement_id, et le statut du vêtement est mis à jour en même temps.
 */
class CycleVetement
{
    public function envoyerEnReparation(Vetement $vetement, Atelier $atelier): DemandeReparation
    {
        $this->exigerSansReservationActive($vetement);
        if (! $atelier->actif) {
            throw ValidationException::withMessages(['atelier_id' => 'Cet atelier n\'accepte plus de demandes.']);
        }

        return DB::transaction(function () use ($vetement, $atelier) {
            $demande = new DemandeReparation([
                'vetement_id' => $vetement->id,
                'atelier_id' => $atelier->id,
                'user_id' => $vetement->user_id,
                'titre' => Str::limit('Réparation : '.$vetement->titre, 150, ''),
                'type_vetement' => $this->typePourAtelier($vetement),
                'description' => $vetement->description,
            ]);
            $demande->diagnostiquer()->save();

            return $demande;
        });
    }

    public function transformerEnUpcycling(Vetement $vetement): ProjetUpcycling
    {
        $this->exigerSansReservationActive($vetement);

        return DB::transaction(function () use ($vetement) {
            $projet = ProjetUpcycling::query()->create([
                'vetement_id' => $vetement->id,
                'user_id' => $vetement->user_id,
                'titre' => Str::limit('Upcycling : '.$vetement->titre, 150, ''),
                'description' => "Projet créé à partir du vêtement « {$vetement->titre} » du catalogue. ".$vetement->description,
                'vetement_origine' => Str::limit($vetement->titre.' ('.$vetement->matiere.')', 255, ''),
                'resultat' => 'À définir',
                'difficulte' => Difficulte::Moyen,
                'duree_minutes' => 60,
                'materiel_necessaire' => "Ciseaux\nFil et aiguille",
                // Copie de la photo : le projet gère ses propres fichiers (il les supprime avec lui).
                'photo_avant' => $this->copierPhoto($vetement, 'upcycling/projets'),
                'statut' => StatutProjet::Brouillon,
            ]);
            $vetement->update(['statut' => StatutVetement::Recycle]);

            return $projet;
        });
    }

    public function donnerAAssociation(Vetement $vetement, Association $association): Don
    {
        $this->exigerSansReservationActive($vetement);
        if (! $association->active) {
            throw ValidationException::withMessages(['association_id' => 'Cette association ne reçoit plus de dons.']);
        }

        return DB::transaction(function () use ($vetement, $association) {
            $don = Don::query()->create([
                'vetement_id' => $vetement->id,
                'association_id' => $association->id,
                'user_id' => $vetement->user_id,
                'type_article' => match ($vetement->genre) {
                    Genre::Femme => 'vetements_femme',
                    Genre::Enfant => 'vetements_enfant',
                    default => 'vetements_homme',
                },
                'quantite' => 1,
                'etat_general' => match ($vetement->etat) {
                    Etat::Use => 'moyen',
                    Etat::AReparer => 'a_recycler',
                    default => 'bon',
                },
                'mode_remise' => 'depot_sur_place',
                'date_remise' => today(),
                'statut' => 'accepte',
                'message' => "Vêtement du catalogue TexTileCycle : « {$vetement->titre} ».",
            ]);
            $vetement->update(['statut' => StatutVetement::Donne]);

            return $don;
        });
    }

    /**
     * Un vêtement réservé par un client ne peut pas partir ailleurs : il faut d'abord annuler la réservation.
     */
    private function exigerSansReservationActive(Vetement $vetement): void
    {
        if ($vetement->reservationActive()->exists()) {
            throw ValidationException::withMessages(['vetement' => 'Ce vêtement est réservé par un client : annulez d\'abord la réservation.']);
        }
    }

    private function typePourAtelier(Vetement $vetement): TypeVetement
    {
        $texte = Str::lower(Str::ascii($vetement->categorie?->nom.' '.$vetement->titre));

        return match (true) {
            str_contains($texte, 'jean') || str_contains($texte, 'pantalon') || str_contains($texte, 'short') => TypeVetement::Pantalon,
            str_contains($texte, 'robe') || str_contains($texte, 'jupe') => TypeVetement::RobeJupe,
            str_contains($texte, 'pull') || str_contains($texte, 'gilet') || str_contains($texte, 'maille') => TypeVetement::Maille,
            str_contains($texte, 'veste') || str_contains($texte, 'manteau') || str_contains($texte, 'blouson') => TypeVetement::VesteManteau,
            str_contains($texte, 'chemise') || str_contains($texte, 't-shirt') || str_contains($texte, 'haut') => TypeVetement::Haut,
            default => TypeVetement::Autre,
        };
    }

    private function copierPhoto(Vetement $vetement, string $dossier): ?string
    {
        if (! $vetement->photo || ! Storage::disk('public')->exists($vetement->photo)) {
            return null;
        }

        $copie = $dossier.'/'.Str::random(40).'.'.pathinfo($vetement->photo, PATHINFO_EXTENSION);
        Storage::disk('public')->copy($vetement->photo, $copie);

        return $copie;
    }
}
