<?php

namespace Database\Seeders;

use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Module « Associations & dons » : 5 associations and 20 donations.
 *
 * php artisan db:seed --class=DonsSeeder
 */
class DonsSeeder extends Seeder
{
    public const ASSOCIATIONS = [
        [
            'nom' => 'Croissant Rouge – Comité de Tunis',
            'ville' => 'Tunis',
            'description' => "Le comité local du Croissant Rouge vient en aide aux familles en difficulté et aux personnes sans abri du Grand Tunis.\nNos bénévoles trient les vêtements reçus et les distribuent lors de maraudes et de permanences hebdomadaires.",
            'besoins' => "Vêtements chauds pour l'hiver (manteaux, pulls).\nCouvertures et linge de maison.",
        ],
        [
            'nom' => 'SOS Villages d\'Enfants Sfax',
            'ville' => 'Sfax',
            'description' => "Le village d'enfants de Sfax accueille des enfants privés de soutien familial et les accompagne jusqu'à leur autonomie.\nLes vêtements donnés habillent directement les enfants des maisons familiales du village.",
            'besoins' => "Vêtements enfants de 0 à 14 ans.\nChaussures de sport en bon état.",
        ],
        [
            'nom' => 'Les Mains Solidaires',
            'ville' => 'Sousse',
            'description' => "Les Mains Solidaires accompagnent les demandeurs d'emploi dans leur retour au travail : ateliers CV, simulations d'entretien et vestiaire solidaire.\nNotre objectif : que chacun puisse se présenter à un entretien avec une tenue adaptée.",
            'besoins' => "Tenues professionnelles pour entretiens d'embauche.\nAccessoires : sacs, ceintures.",
        ],
        [
            'nom' => 'Entraide Cap Bon',
            'ville' => 'Nabeul',
            'description' => "Entraide Cap Bon soutient les familles à faibles revenus de la région de Nabeul grâce à une boutique solidaire où chaque article est proposé à prix symbolique.\nLes recettes financent nos actions d'aide scolaire.",
            'besoins' => "Vêtements homme et femme toutes tailles.\nDraps et serviettes.",
        ],
        [
            'nom' => 'Fil Vert Recyclage',
            'ville' => 'Tunis',
            'description' => "Fil Vert Recyclage donne une seconde vie aux textiles trop abîmés pour être portés : ils sont transformés en chiffons, en isolant ou en matière première pour nos ateliers de couture.\nNous formons aussi des jeunes aux métiers de la couture et de l'upcycling.",
            'besoins' => "Textiles abîmés à recycler (tous types).\nChutes de tissu pour ateliers de couture.",
        ],
    ];

    public function run(): void
    {
        $associations = collect(self::ASSOCIATIONS)
            ->map(fn (array $attributes) => Association::factory()->create($attributes));

        // Re-use existing users when there are some, otherwise create a few donors.
        $users = User::query()->inRandomOrder()->limit(5)->get();
        if ($users->count() < 3) {
            $users = $users->merge(User::factory(3)->create());
        }

        foreach (range(1, 20) as $i) {
            Don::factory()->create([
                'association_id' => $associations->random()->id,
                'user_id' => $users->random()->id,
            ]);
        }
    }
}
