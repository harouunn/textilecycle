<?php

namespace Tests\Feature\Depot;

use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalysePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_guest_cannot_analyse_a_photo(): void
    {
        $this->post(route('depot.mes-depots.analyser'), ['photo' => $this->photoBleue()])->assertRedirect(route('login'));
    }

    public function test_analysis_prefills_the_deposit_form(): void
    {
        $categorie = Categorie::factory()->create(['nom' => 'Hauts', 'description' => 'T-shirts, chemises et tops.']);

        $response = $this->actingAs(User::factory()->create())->post(route('depot.mes-depots.analyser'), [
            'titre' => 'Chemise en lin homme taille M',
            'photo' => $this->photoBleue(),
        ]);

        $response->assertRedirect(route('depot.mes-depots.create'));
        $response->assertSessionHasInput('categorie_id', $categorie->id);
        $response->assertSessionHasInput('matiere', 'Lin');
        $response->assertSessionHasInput('etat', 'bon');
        $response->assertSessionHasInput('genre', 'homme');
        $response->assertSessionHasInput('taille', 'M');
        $response->assertSessionHasInput('description', 'Chemise en lin, coloris bleu, uni. Bon état général. Coupe homme. Taille M.');
        $response->assertSessionHas('analyse');
        Storage::disk('public')->assertExists(session(Vetement::SESSION_PHOTO_ANALYSEE));

        $this->get(route('depot.mes-depots.create'))
            ->assertSeeText('Classement automatique de votre photo')
            ->assertSeeText('Titre : matière Lin.');
    }

    public function test_fields_already_filled_by_the_user_are_kept(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('depot.mes-depots.analyser'), [
            'titre' => 'Chemise en lin',
            'matiere' => 'Coton bio',
            'description' => 'Ma propre description.',
            'photo' => $this->photoBleue(),
        ]);

        $response->assertSessionHasInput('matiere', 'Coton bio');
        $response->assertSessionHasInput('description', 'Ma propre description.');
    }

    public function test_analysis_requires_a_valid_photo(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('depot.mes-depots.analyser'), [
            'titre' => 'Chemise',
            'photo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors(['photo' => 'Le fichier doit être une image.']);
    }

    public function test_deposit_after_analysis_uses_the_analysed_photo(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('depot.mes-depots.analyser'), ['titre' => 'Chemise', 'photo' => $this->photoBleue()]);
        $photo = session(Vetement::SESSION_PHOTO_ANALYSEE);

        $this->actingAs($user)->post(route('depot.mes-depots.store'), $this->payload())
            ->assertRedirect(route('depot.mes-depots.index'));

        $this->assertSame($photo, Vetement::query()->sole()->photo);
        $this->assertNull(session(Vetement::SESSION_PHOTO_ANALYSEE));
    }

    public function test_choosing_another_photo_replaces_the_analysed_one(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('depot.mes-depots.analyser'), ['titre' => 'Chemise', 'photo' => $this->photoBleue()]);
        $analysee = session(Vetement::SESSION_PHOTO_ANALYSEE);

        $this->actingAs($user)->post(route('depot.mes-depots.store'), [
            ...$this->payload(),
            'photo' => UploadedFile::fake()->image('autre.jpg'),
        ]);

        $this->assertNotSame($analysee, Vetement::query()->sole()->photo);
        Storage::disk('public')->assertMissing($analysee);
    }

    public function test_admin_can_analyse_a_photo_too(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('admin.depot.vetements.analyser'), [
            'titre' => 'Jean',
            'photo' => $this->photoBleue(),
        ]);

        $response->assertRedirect(route('admin.depot.vetements.create'));
        $response->assertSessionHasInput('matiere', 'Denim');

        $this->get(route('admin.depot.vetements.create'))->assertSeeText('Classement automatique de la photo');
    }

    private function photoBleue(): UploadedFile
    {
        $img = imagecreatetruecolor(60, 80);
        imagefilledrectangle($img, 0, 0, 59, 79, imagecolorallocate($img, 45, 95, 205));
        ob_start();
        imagepng($img);

        return UploadedFile::fake()->createWithContent('chemise.png', ob_get_clean());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'categorie_id' => Categorie::factory()->create()->id,
            'titre' => 'Chemise bleue',
            'description' => 'Chemise en lin, coloris bleu, uni.',
            'taille' => 'M',
            'genre' => 'homme',
            'matiere' => 'Lin',
            'etat' => 'bon',
            'date_depot' => now()->format('Y-m-d'),
        ];
    }
}
