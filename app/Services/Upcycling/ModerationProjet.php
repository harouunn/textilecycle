<?php

namespace App\Services\Upcycling;

use App\Enums\Upcycling\Difficulte;
use App\Enums\Upcycling\StatutProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ModerationProjet
{
    public static function reglesRefus(): array
    {
        return ['commentaire_refus' => 'required|string|max:2000'];
    }

    public static function messagesRefus(): array
    {
        return ['commentaire_refus.required' => 'Le commentaire de refus est obligatoire.',
            'commentaire_refus.string' => 'Le commentaire de refus doit être un texte.',
            'commentaire_refus.max' => 'Le commentaire de refus ne peut pas dépasser 2000 caractères.'];
    }

    public function verifierComplet(ProjetUpcycling $projet): void
    {
        Validator::make($projet->getAttributes(), [
            'titre' => 'required|string|min:5|max:150', 'description' => 'required|string|min:20|max:5000',
            'vetement_origine' => 'required|string|min:3|max:255', 'resultat' => 'required|string|min:3|max:255',
            'materiel_necessaire' => 'required|string|min:3|max:2000', 'duree_minutes' => 'required|integer|min:5|max:1440',
            'difficulte' => ['required', Rule::enum(Difficulte::class)],
        ], [
            'required' => 'Le champ :attribute est obligatoire avant soumission.',
            'string' => 'Le champ :attribute doit être un texte.',
            'min' => 'Le champ :attribute doit respecter le minimum de :min.',
            'max' => 'Le champ :attribute ne doit pas dépasser :max.',
            'integer' => 'Le champ :attribute doit être un nombre entier.',
            'enum' => 'La difficulté doit être facile, moyen ou difficile.',
        ])->validate();
        foreach (['photo_avant', 'photo_apres'] as $champ) {
            if (! $projet->$champ || ! Storage::disk('public')->exists($projet->$champ)) {
                throw ValidationException::withMessages([$champ => 'Ajoutez une photo valide avant de soumettre le projet.']);
            }
        }
        $etapes = $projet->etapes()->get();
        if ($etapes->isEmpty()) {
            throw ValidationException::withMessages(['etapes' => 'Ajoutez au moins une étape avec sa photo.']);
        }
        foreach ($etapes as $etape) {
            Validator::make($etape->getAttributes(), ['numero' => 'required|integer|min:1|max:100', 'titre' => 'required|string|min:3|max:150', 'contenu' => 'required|string|min:10|max:5000'], [
                'required' => 'Le champ :attribute de chaque étape est obligatoire.',
                'string' => 'Le champ :attribute de chaque étape doit être un texte.',
                'integer' => 'Le numéro de chaque étape doit être un nombre entier.',
                'min' => 'Le champ :attribute de chaque étape doit respecter le minimum de :min.',
                'max' => 'Le champ :attribute de chaque étape ne doit pas dépasser :max.',
            ])->validate();
            if (! $etape->photo || ! Storage::disk('public')->exists($etape->photo)) {
                throw ValidationException::withMessages(['etapes' => 'Chaque étape doit avoir sa photo.']);
            }
        }
    }

    public function soumettre(ProjetUpcycling $projet, User $user): void
    {
        DB::transaction(function () use ($projet, $user) {
            $projet = ProjetUpcycling::query()->lockForUpdate()->findOrFail($projet->id);
            Gate::forUser($user)->authorize('submit', $projet);
            $this->verifierComplet($projet);
            $projet->forceFill(['statut' => StatutProjet::Soumis, 'soumis_le' => now(), 'commentaire_refus' => null, 'decision_le' => null, 'decision_par' => null])->save();
        });
    }

    public function decider(ProjetUpcycling $projet, User $user, bool $publier, ?string $commentaire = null): void
    {
        DB::transaction(function () use ($projet, $user, $publier, $commentaire) {
            $projet = ProjetUpcycling::query()->lockForUpdate()->findOrFail($projet->id);
            Gate::forUser($user)->authorize('moderate', $projet);
            if ($projet->statut !== StatutProjet::Soumis) {
                throw ValidationException::withMessages(['statut' => 'Seul un projet soumis peut recevoir une décision.']);
            }
            if ($publier) {
                $this->verifierComplet($projet);
            } else {
                Validator::make(['commentaire_refus' => trim($commentaire ?? '')], self::reglesRefus(), self::messagesRefus())->validate();
            }
            $projet->forceFill(['statut' => $publier ? StatutProjet::Publie : StatutProjet::Rejete, 'commentaire_refus' => $publier ? null : trim($commentaire), 'decision_le' => now(), 'decision_par' => $user->id])->save();
        });
    }

    public static function remettreEnBrouillon(ProjetUpcycling $projet): void
    {
        if (in_array($projet->statut, [StatutProjet::Soumis, StatutProjet::Publie], true)) {
            $projet->forceFill(['statut' => StatutProjet::Brouillon, 'soumis_le' => null, 'decision_le' => null, 'decision_par' => null])->save();
        }
    }
}
