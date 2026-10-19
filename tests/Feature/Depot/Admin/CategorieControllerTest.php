<?php

namespace Tests\Feature\Depot\Admin;

use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategorieControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.depot.categories.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_shows_the_number_of_clothes_per_category(): void
    {
        $categorie = Categorie::factory()->create(['nom' => 'Vestes']);
        Vetement::factory(3)->for($categorie)->create();

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.depot.categories.index'));

        $response->assertSeeTextInOrder(['Vestes', '3']);
    }

    public function test_valid_payload_creates_category(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.depot.categories.store'), [
            'nom' => 'Accessoires & sacs',
            'description' => 'Sacs, écharpes et ceintures.',
            'icone' => 'bx-shopping-bag',
            'couleur' => '#8a5a44',
            'ordre' => 7,
            'active' => '1',
        ]);

        $response->assertRedirect(route('admin.depot.categories.index'));
        $response->assertSessionHas('success', 'La catégorie « Accessoires & sacs » a été créée.');
        $this->assertDatabaseHas('categories', [
            'nom' => 'Accessoires & sacs',
            'slug' => 'accessoires-sacs',
            'couleur' => '#8a5a44',
            'ordre' => 7,
            'active' => true,
        ]);
    }

    public function test_unchecking_active_hides_the_category_from_the_catalogue_and_the_deposit_form(): void
    {
        $categorie = Categorie::factory()->create(['nom' => 'Hiver']);

        // On suit la redirection pour que le message flash (qui cite le nom) soit consommé.
        $this->actingAs(User::factory()->admin()->create())->followingRedirects()
            ->put(route('admin.depot.categories.update', $categorie), $this->payload(['nom' => 'Hiver']));

        $this->assertFalse($categorie->fresh()->active);
        $this->get(route('depot.catalogue.index'))->assertDontSeeText('Hiver');
        $this->get(route('depot.mes-depots.create'))->assertDontSeeText('Hiver');
    }

    public function test_invalid_colour_and_order_are_rejected(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.depot.categories.store'), $this->payload([
            'couleur' => 'vert',
            'ordre' => -1,
        ]));

        $response->assertSessionHasErrors([
            'couleur' => 'La couleur doit être au format #RRGGBB (ex. #3d7a4f).',
            'ordre' => 'Le champ ordre d\'affichage doit être supérieur ou égal à 0.',
        ]);
    }

    public function test_two_categories_with_similar_names_get_distinct_slugs(): void
    {
        $premiere = Categorie::factory()->create(['nom' => 'Été']);
        $seconde = Categorie::factory()->create(['nom' => 'Ete']);

        $this->assertSame('ete', $premiere->slug);
        $this->assertSame('ete-2', $seconde->slug);
    }

    public function test_index_lists_categories_in_display_order_with_available_clothes(): void
    {
        $seconde = Categorie::factory()->create(['nom' => 'Zèbre', 'ordre' => 2]);
        Categorie::factory()->create(['nom' => 'Ananas', 'ordre' => 9]);
        Vetement::factory(2)->for($seconde)->create();
        Vetement::factory()->for($seconde)->enAttente()->create();

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.depot.categories.index'));

        $response->assertSeeTextInOrder(['Zèbre', '3', '2 en ligne', 'Ananas']);
    }

    public function test_duplicate_name_is_rejected_with_french_message(): void
    {
        Categorie::factory()->create(['nom' => 'Robes']);

        $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.depot.categories.store'), ['nom' => 'Robes']);

        $response->assertSessionHasErrors(['nom' => 'Une catégorie porte déjà ce nom.']);
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_missing_name_is_rejected_with_french_message(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.depot.categories.store'), []);

        $response->assertSessionHasErrors(['nom' => 'Le champ nom est obligatoire.']);
    }

    public function test_update_keeps_its_own_name_but_rejects_another_category_name(): void
    {
        $user = User::factory()->admin()->create();
        $categorie = Categorie::factory()->create(['nom' => 'Pulls']);
        Categorie::factory()->create(['nom' => 'Robes']);

        $this->actingAs($user)
            ->put(route('admin.depot.categories.update', $categorie), $this->payload(['nom' => 'Pulls', 'description' => 'Nouvelle description']))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->put(route('admin.depot.categories.update', $categorie), $this->payload(['nom' => 'Robes']))
            ->assertSessionHasErrors(['nom' => 'Une catégorie porte déjà ce nom.']);

        $this->assertSame('Nouvelle description', $categorie->fresh()->description);
        $this->assertSame('Pulls', $categorie->fresh()->nom);
    }

    public function test_destroy_deletes_the_category_its_clothes_and_their_photos(): void
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->create('veste.jpg', 100, 'image/jpeg')->store('vetements', 'public');
        $categorie = Categorie::factory()->create();
        $vetement = Vetement::factory()->for($categorie)->create(['photo' => $photo]);

        $response = $this->actingAs(User::factory()->admin()->create())->delete(route('admin.depot.categories.destroy', $categorie));

        $response->assertRedirect(route('admin.depot.categories.index'));
        $this->assertModelMissing($categorie);
        $this->assertModelMissing($vetement);
        Storage::disk('public')->assertMissing($photo);
    }

    /**
     * @param  array<string, mixed>  $champs
     * @return array<string, mixed>
     */
    private function payload(array $champs = []): array
    {
        return [
            'nom' => 'Accessoires',
            'description' => null,
            'icone' => 'bx-closet',
            'couleur' => '#3d7a4f',
            'ordre' => 1,
            ...$champs,
        ];
    }
}
