<?php

namespace Database\Factories;

use App\Models\Association;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Association>
 */
class AssociationFactory extends Factory
{
    public function definition(): array
    {
        $besoins = [
            'Vêtements chauds pour l\'hiver (manteaux, pulls, écharpes).',
            'Vêtements enfants de 0 à 12 ans, chaussures en bon état.',
            'Linge de maison : draps, couvertures, serviettes.',
            'Tenues professionnelles pour les entretiens d\'embauche.',
            'Chaussures de sport et vêtements homme (tailles M à XL).',
            'Accessoires : sacs, ceintures, bonnets et gants.',
        ];

        return [
            'nom' => 'Association '.fake()->unique()->lastName().' Solidarité',
            'description' => fake()->randomElement([
                "Notre association accompagne les familles en difficulté en leur offrant des vêtements triés et en bon état.\nLes dons sont distribués lors de permanences hebdomadaires.",
                "Nous gérons un vestiaire solidaire ouvert à tous, où chaque article est proposé à prix symbolique.\nLes recettes financent nos actions sociales.",
                'Nos bénévoles collectent, trient et redistribuent les textiles aux personnes sans abri et aux foyers d\'accueil de la région.',
                'Nous donnons une seconde vie aux textiles abîmés grâce à nos ateliers de couture et d\'upcycling, ouverts aux jeunes en insertion.',
            ]),
            'adresse' => fake()->streetAddress(),
            'ville' => fake()->randomElement(['Tunis', 'Sfax', 'Sousse', 'Nabeul', 'Bizerte', 'Monastir']),
            'telephone' => '+216 '.fake()->numerify('## ### ###'),
            'email' => fake()->unique()->safeEmail(),
            'site_web' => fake()->optional(0.6)->url(),
            'logo' => null,
            'besoins' => implode("\n", fake()->randomElements($besoins, 2)),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
