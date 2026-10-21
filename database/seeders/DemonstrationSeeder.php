<?php

namespace Database\Seeders;

use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutVetement;
use App\Models\Association;
use App\Models\Atelier;
use App\Models\CampagneCollecte;
use App\Models\DemandeReparation;
use App\Models\ProjetUpcycling;
use App\Models\Vetement;
use App\Services\Depot\CycleVetement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * À lancer après les seeders des 4 gestions (voir DatabaseSeeder) :
 *  1. fait passer 3 vêtements du catalogue dans le cycle complet (réparation, upcycling, don),
 *     par le vrai service CycleVetement : les 4 gestions sont reliées entre elles ;
 *  2. donne une photo aux éléments de démonstration qui n'en ont pas, sans modifier les seeders des autres
 *     gestions : demandes de réparation, projets d'upcycling, logos d'associations, images de campagnes.
 * Les images viennent du template (public/front/images) et sont copiées dans storage/app/public.
 */
class DemonstrationSeeder extends Seeder
{
    private const IMAGES = 'front/images/';

    /** Photo selon le type de vêtement d'une demande de réparation. */
    private const PHOTOS_REPARATION = [
        'haut' => 'product-item-8.jpg',
        'pantalon' => 'product-item-3.jpg',
        'robe_jupe' => 'product-item-5.jpg',
        'maille' => 'product-item-10.jpg',
        'veste_manteau' => 'banner-image-2.jpg',
        'autre' => 'cat-item3.jpg',
    ];

    public function run(CycleVetement $cycle): void
    {
        $this->relierLesGestions($cycle);

        DemandeReparation::query()->whereNull('photo')->get()->each(function (DemandeReparation $demande) {
            // Une demande créée depuis un vêtement du catalogue reprend la photo de ce vêtement
            // (lue par le disque « public » : fonctionne aussi avec un disque simulé dans les tests).
            $photoVetement = $demande->vetement?->photo;
            if ($photoVetement && Storage::disk('public')->exists($photoVetement)) {
                $copie = DemandeReparation::PHOTO_DIRECTORY."/demo-reparation-{$demande->id}.".pathinfo($photoVetement, PATHINFO_EXTENSION);
                Storage::disk('public')->copy($photoVetement, $copie);
                $demande->update(['photo' => $copie]);

                return;
            }
            $demande->update(['photo' => $this->copier(
                public_path(self::IMAGES.self::PHOTOS_REPARATION[$demande->type_vetement->value ?? 'autre']),
                DemandeReparation::PHOTO_DIRECTORY,
                "reparation-{$demande->id}",
            )]);
        });

        ProjetUpcycling::query()->get()->each(function (ProjetUpcycling $projet) {
            $texte = Str::lower(Str::ascii($projet->vetement_origine.' '.$projet->resultat));
            $avant = match (true) {
                str_contains($texte, 'jean') => 'product-item-7.jpg',
                str_contains($texte, 'chemise') => 'insta-item5.jpg',
                str_contains($texte, 't-shirt') => 'product-item-4.jpg',
                str_contains($texte, 'pull') => 'product-item-10.jpg',
                str_contains($texte, 'robe') => 'product-item-5.jpg',
                default => 'post-small-image1.jpg',
            };
            $apres = str_contains($texte, 'sac') || str_contains($texte, 'pochette') ? 'cat-item3.jpg' : null;

            $projet->update(array_filter([
                'photo_avant' => $projet->photo_avant ?? $this->copier(public_path(self::IMAGES.$avant), 'upcycling/projets', "projet-{$projet->id}-avant"),
                'photo_apres' => $projet->photo_apres ?? ($apres ? $this->copier(public_path(self::IMAGES.$apres), 'upcycling/projets', "projet-{$projet->id}-apres") : null),
            ]));
        });

        Association::query()->whereNull('logo')->orderBy('id')->get()->each(fn (Association $association, int $index) => $association->update([
            'logo' => $this->copier(public_path(self::IMAGES.'logo'.($index % 5 + 1).'.png'), 'associations', "association-{$association->id}"),
        ]));

        $images = ['insta-item4.jpg', 'post-large-image1.jpg', 'newsletter-image.jpg', 'post-small-image1.jpg', 'collection-banner.jpg', 'banner-image-3.jpg'];
        CampagneCollecte::query()->whereNull('image')->orderBy('id')->get()->each(fn (CampagneCollecte $campagne, int $index) => $campagne->update([
            'image' => $this->copier(public_path(self::IMAGES.$images[$index % count($images)]), 'campagnes', "campagne-{$campagne->id}"),
        ]));
    }

    /**
     * Le scénario complet : un vêtement part en réparation, un autre devient un projet d'upcycling,
     * un troisième est donné à une association.
     */
    private function relierLesGestions(CycleVetement $cycle): void
    {
        $vetement = fn (string $titre) => Vetement::query()
            ->where('titre', $titre)
            ->where('moderation', Moderation::Approuve)
            ->where('statut', StatutVetement::Disponible)
            ->first();

        if (($v = $vetement('Manteau à carreaux')) && ($atelier = Atelier::query()->where('actif', true)->first())) {
            $cycle->envoyerEnReparation($v, $atelier);
        }
        if ($v = $vetement('Sweat court marron')) {
            $cycle->transformerEnUpcycling($v);
        }
        if (($v = $vetement('Robe longue noire')) && ($association = Association::query()->where('active', true)->first())) {
            $cycle->donnerAAssociation($v, $association);
        }
    }

    private function copier(string $source, string $dossier, string $nom): ?string
    {
        if (! is_file($source)) {
            return null;
        }

        // Nom fixe : relancer le seeder remplace les photos au lieu d'en ajouter.
        $chemin = $dossier.'/demo-'.$nom.'.'.pathinfo($source, PATHINFO_EXTENSION);
        Storage::disk('public')->put($chemin, (string) file_get_contents($source));

        return $chemin;
    }
}
