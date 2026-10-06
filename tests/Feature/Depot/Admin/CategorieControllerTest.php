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

        $response = $this->actingAs(User::factory()->create())->get(route('admin.depot.categories.index'));

        $response->assertSeeTextInOrder(['Vestes', '3']);
    }

    public function test_valid_payload_creates_category(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('admin.depot.categories.store'), [
            'nom' => 'Accessoires',
            'description' => 'Sacs, écharpes et ceintures.',
            'icone' => 'bx-shopping-bag',
        ]);

        $response->assertRedirect(route('admin.depot.categories.index'));
        $response->assertSessionHas('success', 'La catégorie « Accessoires » a été créée.');
        $this->assertDatabaseHas('categories', ['nom' => 'Accessoires', 'icone' => 'bx-shopping-bag']);
    }

    public function test_duplicate_name_is_rejected_with_french_message(): void
    {
        Categorie::factory()->create(['nom' => 'Robes']);

        $response = $this->actingAs(User::factory()->create())->post(route('admin.depot.categories.store'), ['nom' => 'Robes']);

        $response->assertSessionHasErrors(['nom' => 'Une catégorie porte déjà ce nom.']);
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_missing_name_is_rejected_with_french_message(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('admin.depot.categories.store'), []);

        $response->assertSessionHasErrors(['nom' => 'Le champ nom est obligatoire.']);
    }

    public function test_update_keeps_its_own_name_but_rejects_another_category_name(): void
    {
        $user = User::factory()->create();
        $categorie = Categorie::factory()->create(['nom' => 'Pulls']);
        Categorie::factory()->create(['nom' => 'Robes']);

        $this->actingAs($user)
            ->put(route('admin.depot.categories.update', $categorie), ['nom' => 'Pulls', 'description' => 'Nouvelle description'])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->put(route('admin.depot.categories.update', $categorie), ['nom' => 'Robes'])
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

        $response = $this->actingAs(User::factory()->create())->delete(route('admin.depot.categories.destroy', $categorie));

        $response->assertRedirect(route('admin.depot.categories.index'));
        $this->assertModelMissing($categorie);
        $this->assertModelMissing($vetement);
        Storage::disk('public')->assertMissing($photo);
    }
}
