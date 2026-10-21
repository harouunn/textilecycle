<?php

namespace Database\Seeders;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\ModeRemise;
use App\Enums\Dons\StatutCampagne;
use App\Enums\Dons\TypeArticle;
use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Models\CreneauCollecte;
use App\Models\User;
use App\Services\Dons\DonWorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;

/**
 * Module « Associations & dons » : 5 associations (Grand Tunis), campagnes, créneaux de collecte et 24 dons
 * passés par le vrai workflow (historique, reçus PDF, points).
 * Compte responsable de démonstration : responsable@example.com / password (Espace association).
 *
 * php artisan db:seed --class=DonsSeeder
 */
class DonsSeeder extends Seeder
{
    public const ASSOCIATIONS = [
        [
            'nom' => 'Croissant Rouge – Comité de Tunis',
            'ville' => 'Tunis',
            'adresse' => 'Avenue de la Liberté',
            'latitude' => 36.8139, 'longitude' => 10.1806,
            'capacite_mensuelle_kg' => 800,
            'description' => "Le comité local du Croissant Rouge vient en aide aux familles en difficulté et aux personnes sans abri du Grand Tunis.\nNos bénévoles trient les vêtements reçus et les distribuent lors de maraudes et de permanences hebdomadaires.",
            'besoins' => "Vêtements chauds pour l'hiver (manteaux, pulls).\nCouvertures et linge de maison.",
        ],
        [
            'nom' => 'Village d\'Enfants de l\'Ariana',
            'ville' => 'Ariana',
            'adresse' => 'Cité Ennasr',
            'latitude' => 36.8580, 'longitude' => 10.1640,
            'capacite_mensuelle_kg' => 300,
            'description' => "Le village d'enfants accueille des enfants privés de soutien familial et les accompagne jusqu'à leur autonomie.\nLes vêtements donnés habillent directement les enfants des maisons familiales du village.",
            'besoins' => "Vêtements enfants de 0 à 14 ans.\nChaussures de sport en bon état.",
        ],
        [
            'nom' => 'Les Mains Solidaires',
            'ville' => 'Ben Arous',
            'adresse' => 'Rue de la République',
            'latitude' => 36.7531, 'longitude' => 10.2189,
            'capacite_mensuelle_kg' => 250,
            'description' => "Les Mains Solidaires accompagnent les demandeurs d'emploi dans leur retour au travail : ateliers CV, simulations d'entretien et vestiaire solidaire.\nNotre objectif : que chacun puisse se présenter à un entretien avec une tenue adaptée.",
            'besoins' => "Tenues professionnelles pour entretiens d'embauche.\nAccessoires : sacs, ceintures.",
        ],
        [
            'nom' => 'Entraide Mégrine',
            'ville' => 'Ben Arous',
            'adresse' => 'Avenue de Mégrine',
            'latitude' => 36.7700, 'longitude' => 10.2330,
            'capacite_mensuelle_kg' => 400,
            'description' => "Entraide Mégrine soutient les familles à faibles revenus grâce à une boutique solidaire où chaque article est proposé à prix symbolique.\nLes recettes financent nos actions d'aide scolaire.",
            'besoins' => "Vêtements homme et femme toutes tailles.\nDraps et serviettes.",
        ],
        [
            'nom' => 'Fil Vert Recyclage',
            'ville' => 'Ariana',
            'adresse' => 'Route de Raoued',
            'latitude' => 36.8625, 'longitude' => 10.1956,
            'capacite_mensuelle_kg' => 1500,
            'description' => "Fil Vert Recyclage donne une seconde vie aux textiles trop abîmés pour être portés : ils sont transformés en chiffons, en isolant ou en matière première pour nos ateliers de couture.\nNous formons aussi des jeunes aux métiers de la couture et de l'upcycling.",
            'besoins' => "Textiles abîmés à recycler (tous types).\nChutes de tissu pour ateliers de couture.",
        ],
    ];

    public function run(DonWorkflowService $workflow): void
    {
        // Données de démonstration : aucun e-mail ni notification n'est envoyé pendant le remplissage.
        Notification::fake();

        $responsable = User::query()->firstOrCreate(
            ['email' => 'responsable@example.com'],
            ['name' => 'Responsable Croissant Rouge', 'password' => 'password', 'email_verified_at' => now()],
        );
        $admin = User::query()->where('role', 'admin')->first() ?? User::factory()->admin()->create();

        $associations = collect(self::ASSOCIATIONS)->map(fn (array $attributs, int $i) => Association::query()->updateOrCreate(
            ['nom' => $attributs['nom']],
            [...Association::factory()->make()->only(['telephone', 'email', 'site_web']), ...$attributs, 'active' => true,
                'responsable_id' => $i === 0 ? $responsable->id : null],
        ));

        $this->campagnes($associations);
        $this->creneaux($associations);

        // Donateurs existants s'il y en a, sinon quelques comptes de démonstration.
        $donateurs = User::query()->where('role', '!=', 'admin')->whereKeyNot($responsable->id)->inRandomOrder()->limit(6)->get();
        if ($donateurs->count() < 4) {
            $donateurs = $donateurs->merge(User::factory(4)->create());
        }

        foreach (range(1, 24) as $i) {
            $association = $associations->random();
            $campagne = $association->campagnes()->enCours()->inRandomOrder()->first();
            $creneau = $association->creneaux()->disponibles()->inRandomOrder()->first();
            $mode = $creneau && $i % 3 === 0 ? ModeRemise::CollecteADomicile : ModeRemise::DepotSurPlace;

            $don = $workflow->proposer($association, [
                'type_article' => fake()->randomElement(TypeArticle::cases())->value,
                'quantite' => fake()->numberBetween(2, 30),
                'poids_kg' => fake()->randomFloat(1, 1, 18),
                'etat_general' => fake()->randomElement(['bon', 'bon', 'moyen', 'a_recycler']),
                'mode_remise' => $mode->value,
                'date_remise' => today()->addDays(fake()->numberBetween(1, 15))->toDateString(),
                'adresse_collecte' => $mode === ModeRemise::CollecteADomicile ? fake()->streetAddress().', '.$association->ville : null,
                'creneau_id' => $mode === ModeRemise::CollecteADomicile ? $creneau->id : null,
                'campagne_id' => $i % 2 === 0 ? $campagne?->id : null,
                'message' => fake()->optional(0.5)->randomElement(['Vêtements lavés et pliés.', 'Plusieurs tailles, surtout M et L.', 'Disponible en fin de journée.']),
            ], $donateurs->random());

            // Fait avancer une partie des dons dans le workflow (avec historique, reçu et points).
            $parcours = fake()->randomElement([
                [], [DonStatut::Refuse], [DonStatut::Accepte], [DonStatut::Accepte, DonStatut::Planifie],
                [DonStatut::Accepte, DonStatut::Recu], [DonStatut::Accepte, DonStatut::Recu],
                [DonStatut::Accepte, DonStatut::Planifie, DonStatut::Recu, DonStatut::Distribue],
            ]);
            foreach ($parcours as $statut) {
                $workflow->transition($don, $statut, null, $association->responsable ?? $admin);
            }
        }
    }

    private function campagnes($associations): void
    {
        $modeles = [
            ['titre' => 'Grand froid '.now()->year, 'objectif_kg' => 300, 'types' => [TypeArticle::VetementsHomme, TypeArticle::VetementsFemme, TypeArticle::LingeMaison]],
            ['titre' => 'Rentrée solidaire', 'objectif_kg' => 120, 'types' => [TypeArticle::VetementsEnfant, TypeArticle::Chaussures]],
            ['titre' => 'Tenues pour l\'emploi', 'objectif_kg' => 80, 'types' => [TypeArticle::VetementsHomme, TypeArticle::VetementsFemme, TypeArticle::Accessoires]],
        ];

        foreach ($associations->take(3)->values() as $i => $association) {
            $modele = $modeles[$i];
            CampagneCollecte::query()->updateOrCreate(
                ['association_id' => $association->id, 'titre' => $modele['titre']],
                [
                    'description' => "Campagne de collecte de {$association->nom} : chaque kilo reçu aide directement les personnes accompagnées par l'association.",
                    'type_articles_cibles' => array_map(fn (TypeArticle $t) => $t->value, $modele['types']),
                    'objectif_kg' => $modele['objectif_kg'],
                    'date_debut' => today()->subDays(10),
                    'date_fin' => today()->addDays(20 + 10 * $i),
                    'statut' => StatutCampagne::Active,
                ],
            );
        }

        // Une campagne expirée encore « active » (dons:cloturer-campagnes la terminera) et un brouillon.
        CampagneCollecte::query()->updateOrCreate(
            ['association_id' => $associations[3]->id, 'titre' => 'Collecte de printemps'],
            ['description' => 'Collecte de vêtements légers pour le printemps, terminée.', 'objectif_kg' => 100,
                'date_debut' => today()->subMonths(3), 'date_fin' => today()->subDays(2), 'statut' => StatutCampagne::Active],
        );
        CampagneCollecte::query()->updateOrCreate(
            ['association_id' => $associations[4]->id, 'titre' => 'Textiles à recycler'],
            ['description' => 'Campagne en préparation pour les textiles trop abîmés pour être portés.', 'objectif_kg' => 500,
                'date_debut' => today()->addMonth(), 'date_fin' => today()->addMonths(2), 'statut' => StatutCampagne::Brouillon],
        );
    }

    private function creneaux($associations): void
    {
        foreach ($associations->take(3) as $association) {
            foreach ([2, 5, 9] as $jours) {
                foreach ([['09:00', '12:00'], ['14:00', '17:00']] as [$debut, $fin]) {
                    CreneauCollecte::query()->firstOrCreate(
                        ['association_id' => $association->id, 'date' => today()->addDays($jours)->toDateString(), 'heure_debut' => $debut],
                        ['heure_fin' => $fin, 'capacite_max' => 3],
                    );
                }
            }
        }
    }
}
