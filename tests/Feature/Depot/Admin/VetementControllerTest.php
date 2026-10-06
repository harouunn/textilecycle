<?php

namespace Tests\Feature\Depot\Admin;

use App\Enums\Depot\Etat;
use App\Enums\Depot\StatutVetement;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VetementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.depot.vetements.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_shows_clothes_with_their_category_name(): void
    {
        Vetement::factory()->for(Categorie::factory()->create(['nom' => 'Manteaux']))->create(['titre' => 'Caban marine']);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.depot.vetements.index'));

        $response->assertSeeTextInOrder(['Caban marine', 'Manteaux']);
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string, 2: string}>
     */
    public static function filtres(): array
    {
        return [
            'recherche par titre' => [['search' => 'lin'], 'Chemise en lin', 'Pull en laine'],
            'état' => [['etat' => 'a_reparer'], 'Pull en laine', 'Chemise en lin'],
            'statut' => [['statut' => 'donne'], 'Pull en laine', 'Chemise en lin'],
        ];
    }

    /**
     * @param  array<string, string>  $filtre
     */
    #[DataProvider('filtres')]
    public function test_index_filters_clothes(array $filtre, string $attendu, string $exclu): void
    {
        Vetement::factory()->create(['titre' => 'Chemise en lin', 'etat' => Etat::Bon, 'statut' => StatutVetement::Disponible]);
        Vetement::factory()->create(['titre' => 'Pull en laine', 'etat' => Etat::AReparer, 'statut' => StatutVetement::Donne]);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.depot.vetements.index', $filtre));

        $response->assertSeeText($attendu);
        $response->assertDontSeeText($exclu);
    }

    public function test_index_filters_clothes_by_category(): void
    {
        $robes = Categorie::factory()->create();
        Vetement::factory()->for($robes)->create(['titre' => 'Robe fleurie']);
        Vetement::factory()->create(['titre' => 'Jean brut']);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.depot.vetements.index', ['categorie' => $robes->id]));

        $response->assertSeeText('Robe fleurie');
        $response->assertDontSeeText('Jean brut');
    }

    public function test_valid_payload_creates_clothing_with_photo(): void
    {
        Storage::fake('public');
        $deposant = User::factory()->create();

        $response = $this->actingAs(User::factory()->create())->post(route('admin.depot.vetements.store'), [
            ...$this->payload(),
            'user_id' => $deposant->id,
            'statut' => 'reserve',
            'photo' => UploadedFile::fake()->create('chemise.png', 100, 'image/png'),
        ]);

        $response->assertRedirect(route('admin.depot.vetements.index'));
        $response->assertSessionHas('success', 'Le vêtement « Chemise en lin » a été ajouté.');
        $vetement = Vetement::query()->sole();
        $this->assertSame($deposant->id, $vetement->user_id);
        $this->assertSame(StatutVetement::Reserve, $vetement->statut);
        $this->assertStringStartsWith('vetements/', $vetement->photo);
        Storage::disk('public')->assertExists($vetement->photo);
    }

    public function test_missing_fields_are_rejected_with_french_messages(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('admin.depot.vetements.store'), []);

        $response->assertSessionHasErrors([
            'titre' => 'Le champ titre est obligatoire.',
            'categorie_id' => 'Le champ catégorie est obligatoire.',
            'user_id' => 'Le champ déposant est obligatoire.',
            'statut' => 'Le champ statut est obligatoire.',
            'date_depot' => 'Le champ date de dépôt est obligatoire.',
        ]);
    }

    public function test_future_deposit_date_is_rejected(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('admin.depot.vetements.store'), [
            ...$this->payload(),
            'date_depot' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['date_depot' => 'La date de dépôt ne peut pas être dans le futur.']);
        $this->assertDatabaseCount('vetements', 0);
    }

    public function test_photo_with_unsupported_format_or_too_large_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.depot.vetements.store'), [...$this->payload(), 'photo' => UploadedFile::fake()->create('photo.gif', 100, 'image/gif')])
            ->assertSessionHasErrors(['photo' => 'La photo doit être au format JPG, PNG ou WEBP.']);

        $this->actingAs($user)
            ->post(route('admin.depot.vetements.store'), [...$this->payload(), 'photo' => UploadedFile::fake()->create('photo.jpg', 3000, 'image/jpeg')])
            ->assertSessionHasErrors(['photo' => 'La photo ne doit pas dépasser 2 Mo.']);
    }

    public function test_update_replaces_the_photo_and_deletes_the_old_one(): void
    {
        Storage::fake('public');
        $anciennePhoto = UploadedFile::fake()->create('avant.jpg', 100, 'image/jpeg')->store('vetements', 'public');
        $vetement = Vetement::factory()->create(['photo' => $anciennePhoto]);

        $response = $this->actingAs(User::factory()->create())->put(route('admin.depot.vetements.update', $vetement), [
            ...$this->payload(),
            'user_id' => $vetement->user_id,
            'statut' => 'recycle',
            'photo' => UploadedFile::fake()->create('apres.webp', 100, 'image/webp'),
        ]);

        $response->assertRedirect(route('admin.depot.vetements.index'));
        $vetement->refresh();
        $this->assertSame(StatutVetement::Recycle, $vetement->statut);
        $this->assertNotSame($anciennePhoto, $vetement->photo);
        Storage::disk('public')->assertMissing($anciennePhoto);
        Storage::disk('public')->assertExists($vetement->photo);
    }

    public function test_update_without_new_photo_keeps_the_current_one(): void
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')->store('vetements', 'public');
        $vetement = Vetement::factory()->create(['photo' => $photo]);

        $this->actingAs(User::factory()->create())->put(route('admin.depot.vetements.update', $vetement), [
            ...$this->payload(),
            'user_id' => $vetement->user_id,
            'statut' => 'disponible',
        ]);

        $this->assertSame($photo, $vetement->fresh()->photo);
        Storage::disk('public')->assertExists($photo);
    }

    public function test_destroy_deletes_the_clothing_and_its_photo(): void
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')->store('vetements', 'public');
        $vetement = Vetement::factory()->create(['photo' => $photo]);

        $response = $this->actingAs(User::factory()->create())->delete(route('admin.depot.vetements.destroy', $vetement));

        $response->assertRedirect(route('admin.depot.vetements.index'));
        $this->assertModelMissing($vetement);
        Storage::disk('public')->assertMissing($photo);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'categorie_id' => Categorie::factory()->create()->id,
            'titre' => 'Chemise en lin',
            'description' => 'Chemise légère, très peu portée.',
            'taille' => 'M',
            'genre' => 'homme',
            'matiere' => 'Lin',
            'etat' => 'tres_bon',
            'date_depot' => now()->format('Y-m-d'),
        ];
    }
}
