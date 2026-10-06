<?php

namespace Database\Seeders;

use App\Enums\Upcycling\Difficulte;
use App\Enums\Upcycling\StatutProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Module Upcycling : 6 projets avec 3 à 5 étapes chacun.
 * php artisan db:seed --class=UpcyclingSeeder
 */
class UpcyclingSeeder extends Seeder
{
    public function run(): void
    {
        $auteurs = User::query()->take(3)->get();

        if ($auteurs->isEmpty()) {
            $auteurs = User::factory()->count(3)->create();
        }

        foreach ($this->projets() as $index => $donnees) {
            $etapes = $donnees['etapes'];
            unset($donnees['etapes']);

            $projet = ProjetUpcycling::factory()
                ->for($auteurs[$index % $auteurs->count()])
                ->create($donnees);

            foreach ($etapes as $numero => [$titre, $contenu]) {
                $projet->etapes()->create([
                    'numero' => $numero + 1,
                    'titre' => $titre,
                    'contenu' => $contenu,
                ]);
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function projets(): array
    {
        return [
            [
                'titre' => 'Sac cabas en jean recyclé',
                'description' => 'Transformez un vieux jean trop petit ou abîmé en un sac cabas solide et pratique pour vos courses. Les jambes servent de corps du sac et la ceinture devient les anses.',
                'vetement_origine' => 'vieux jean',
                'resultat' => 'sac cabas',
                'difficulte' => Difficulte::Facile,
                'duree_minutes' => 90,
                'materiel_necessaire' => "Un vieux jean\nCiseaux de couture\nMachine à coudre\nFil épais assorti\nÉpingles",
                'statut' => StatutProjet::Publie,
                'etapes' => [
                    ['Découper les jambes', 'Coupez les deux jambes du jean juste sous l\'entrejambe pour obtenir deux tubes de tissu de même longueur.'],
                    ['Former le fond du sac', 'Ouvrez un tube le long de la couture intérieure, posez-le à plat et cousez le bas endroit contre endroit pour fermer le fond.'],
                    ['Préparer les anses', 'Découpez deux bandes de 6 cm dans la seconde jambe, pliez-les en trois dans la longueur et surpiquez de chaque côté.'],
                    ['Assembler', 'Retournez le sac, faites un ourlet en haut puis fixez les anses avec une double couture en croix pour qu\'elles résistent au poids.'],
                ],
            ],
            [
                'titre' => 'Tablier de cuisine à partir d\'une chemise',
                'description' => 'Une chemise d\'homme en coton devient un tablier élégant avec poche poitrine. Le boutonnage d\'origine est conservé pour un effet original.',
                'vetement_origine' => 'chemise en coton',
                'resultat' => 'tablier de cuisine',
                'difficulte' => Difficulte::Moyen,
                'duree_minutes' => 120,
                'materiel_necessaire' => "Une chemise d'homme\nCraie de tailleur\nMètre ruban\nMachine à coudre\nBiais de 2 cm",
                'statut' => StatutProjet::Publie,
                'etapes' => [
                    ['Retirer les manches', 'Découpez les manches au ras des coutures d\'emmanchure, conservez-les pour les liens.'],
                    ['Tracer le patron', 'Boutonnez la chemise, posez-la à plat et tracez la forme du tablier à la craie en arrondissant les côtés.'],
                    ['Découper et border', 'Découpez en suivant le tracé, puis bordez tous les bords coupés avec le biais.'],
                    ['Coudre les liens', 'Taillez deux bandes dans les manches, cousez-les en tubes et fixez-les à la taille et au cou.'],
                    ['Finitions', 'Repassez soigneusement le tablier et vérifiez la solidité des liens.'],
                ],
            ],
            [
                'titre' => 'Tapis tressé en t-shirts',
                'description' => 'Recyclez une pile de vieux t-shirts en un tapis tressé coloré, idéal pour une salle de bain ou une chambre d\'enfant. Aucune machine à coudre nécessaire.',
                'vetement_origine' => 't-shirts usés',
                'resultat' => 'tapis tressé',
                'difficulte' => Difficulte::Facile,
                'duree_minutes' => 180,
                'materiel_necessaire' => "8 à 10 t-shirts\nCiseaux\nAiguille à laine\nFil de coton solide",
                'statut' => StatutProjet::Publie,
                'etapes' => [
                    ['Préparer le fil textile', 'Découpez les t-shirts en bandes continues de 3 cm de large, en spirale, puis étirez-les pour qu\'elles s\'enroulent.'],
                    ['Tresser', 'Nouez trois bandes ensemble et tressez-les serré. Ajoutez de nouvelles bandes au fur et à mesure.'],
                    ['Enrouler et coudre', 'Enroulez la tresse en spirale à plat et cousez chaque tour au précédent avec l\'aiguille à laine.'],
                ],
            ],
            [
                'titre' => 'Coussin douillet en pull en laine',
                'description' => 'Un pull en laine feutré au lavage retrouve une seconde vie en housse de coussin chaleureuse, avec les côtes du pull en guise de rabat.',
                'vetement_origine' => 'pull en laine',
                'resultat' => 'housse de coussin',
                'difficulte' => Difficulte::Facile,
                'duree_minutes' => 60,
                'materiel_necessaire' => "Un pull en laine\nUn coussin de 40 x 40 cm\nÉpingles\nMachine à coudre ou aiguille\nTrois boutons",
                'statut' => StatutProjet::Publie,
                'etapes' => [
                    ['Mesurer', 'Posez le coussin sur le pull et marquez un carré de 42 cm de côté, en gardant le bas côtelé du pull.'],
                    ['Couper', 'Découpez le devant et le dos en même temps pour obtenir deux carrés identiques.'],
                    ['Assembler', 'Cousez trois côtés endroit contre endroit, retournez la housse et insérez le coussin.'],
                    ['Fermer', 'Cousez les trois boutons sur le côté côtelé pour fermer la housse.'],
                ],
            ],
            [
                'titre' => 'Pochette patchwork en cravates',
                'description' => 'Assemblez d\'anciennes cravates en soie pour créer une pochette de soirée unique. Un projet minutieux qui demande de la précision dans l\'assemblage.',
                'vetement_origine' => 'cravates en soie',
                'resultat' => 'pochette de soirée',
                'difficulte' => Difficulte::Difficile,
                'duree_minutes' => 240,
                'materiel_necessaire' => "6 cravates\nTissu de doublure\nEntoilage thermocollant\nFermeture à glissière de 20 cm\nMachine à coudre\nFer à repasser",
                'statut' => StatutProjet::Publie,
                'etapes' => [
                    ['Découdre les cravates', 'Ouvrez chaque cravate, retirez la doublure intérieure et repassez la soie à basse température.'],
                    ['Assembler le patchwork', 'Cousez les cravates côte à côte en alternant les sens pour former un panneau de 25 x 40 cm.'],
                    ['Entoiler', 'Thermocollez l\'entoilage sur l\'envers du panneau pour lui donner de la tenue.'],
                    ['Poser la fermeture', 'Cousez la fermeture à glissière entre le panneau et la doublure, sur le bord supérieur.'],
                    ['Fermer la pochette', 'Cousez les côtés, retournez par la fermeture ouverte et repassez les angles.'],
                ],
            ],
            [
                'titre' => 'Jupe portefeuille à partir d\'une robe d\'été',
                'description' => 'Une robe d\'été dont le haut est abîmé devient une jupe portefeuille fluide. Projet en cours de rédaction.',
                'vetement_origine' => 'robe d\'été',
                'resultat' => 'jupe portefeuille',
                'difficulte' => Difficulte::Moyen,
                'duree_minutes' => 150,
                'materiel_necessaire' => "Une robe longue\nMètre ruban\nCiseaux de couture\nMachine à coudre\nRuban de 1,5 m",
                'statut' => StatutProjet::Brouillon,
                'etapes' => [
                    ['Séparer le haut', 'Coupez la robe à la taille et mettez le haut de côté pour les liens.'],
                    ['Ouvrir la jupe', 'Ouvrez la jupe sur le devant et ourlez les deux bords verticaux.'],
                    ['Poser la ceinture', 'Cousez une bande de ceinture prolongée par le ruban pour pouvoir nouer la jupe.'],
                ],
            ],
        ];
    }
}
