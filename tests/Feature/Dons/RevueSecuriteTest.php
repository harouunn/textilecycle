<?php

namespace Tests\Feature\Dons;

use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Models\CreneauCollecte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Revue du module : modification d'une association (seule action sans test jusqu'ici)
 * et sécurité des téléversements (formats, taille, stockage hors de public/).
 */
class RevueSecuriteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Image PNG valide de 1 × 1 pixel (sans dépendre de l'extension GD).
     */
    private function png(string $nom = 'logo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nom, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    }

    private function svg(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    }

    /**
     * @return array<string, mixed>
     */
    private function champs(Association $association, array $surcharge = []): array
    {
        return [...$association->only(['nom', 'description', 'adresse', 'ville', 'telephone', 'email', 'besoins']), 'active' => '1', ...$surcharge];
    }

    public function test_admin_updates_an_association_and_its_logo(): void
    {
        $association = Association::factory()->create(['nom' => 'Ancien nom']);

        $this->actingAs($this->admin)->put(route('admin.dons.associations.update', $association), $this->champs($association, [
            'nom' => 'Nouveau nom', 'capacite_mensuelle_kg' => 400, 'logo' => $this->png(),
        ]))->assertRedirect(route('admin.dons.associations.show', $association))->assertSessionHas('success');

        $association->refresh();
        $this->assertSame('Nouveau nom', $association->nom);
        $this->assertSame(400, $association->capacite_mensuelle_kg);
        // Stocké sur le disque « public » (storage/app/public), sous un nom aléatoire, pas sous le nom envoyé.
        Storage::disk('public')->assertExists($association->logo);
        $this->assertStringStartsWith('associations/', $association->logo);
        $this->assertStringNotContainsString('logo.png', $association->logo);

        // Remplacer le logo supprime l'ancien fichier ; « supprimer le logo » le retire.
        $ancien = $association->logo;
        $this->actingAs($this->admin)->put(route('admin.dons.associations.update', $association), $this->champs($association, ['logo' => $this->png('autre.png')]));
        Storage::disk('public')->assertMissing($ancien);

        $this->actingAs($this->admin)->put(route('admin.dons.associations.update', $association), $this->champs($association->fresh(), ['supprimer_logo' => '1']));
        $this->assertNull($association->fresh()->logo);
        $this->assertSame([], Storage::disk('public')->allFiles('associations'));
    }

    public function test_svg_and_oversized_logos_are_rejected(): void
    {
        $association = Association::factory()->create();

        $this->actingAs($this->admin)->put(route('admin.dons.associations.update', $association), $this->champs($association, ['logo' => $this->svg()]))
            ->assertSessionHasErrors('logo');
        $this->actingAs($this->admin)->put(route('admin.dons.associations.update', $association), $this->champs($association, [
            'logo' => UploadedFile::fake()->create('lourd.png', 3000, 'image/png'),
        ]))->assertSessionHasErrors('logo');

        $this->assertNull($association->fresh()->logo);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_website_must_be_http_or_https(): void
    {
        $association = Association::factory()->create();

        foreach (['javascript:alert(1)', 'ftp://exemple.org', 'data:text/html,<b>x</b>'] as $lien) {
            $this->actingAs($this->admin)->put(route('admin.dons.associations.update', $association), $this->champs($association, ['site_web' => $lien]))
                ->assertSessionHasErrors('site_web');
        }

        $this->actingAs($this->admin)->put(route('admin.dons.associations.update', $association), $this->champs($association, ['site_web' => 'https://exemple.org']))
            ->assertSessionHasNoErrors();
    }

    public function test_campaign_image_is_stored_publicly_and_svg_is_rejected(): void
    {
        $campagne = CampagneCollecte::factory()->create();
        $champs = [
            ...$campagne->only(['association_id', 'titre', 'description', 'objectif_kg']),
            'date_debut' => $campagne->date_debut->toDateString(),
            'date_fin' => $campagne->date_fin->toDateString(),
            'statut' => 'active',
        ];

        $this->actingAs($this->admin)->put(route('admin.dons.campagnes.update', $campagne), [...$champs, 'image' => $this->svg()])->assertSessionHasErrors('image');
        $this->actingAs($this->admin)->put(route('admin.dons.campagnes.update', $campagne), [...$champs, 'image' => $this->png('affiche.png')])->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists($campagne->fresh()->image);
        $this->get(route('dons.campagnes.index'))->assertSee(Storage::disk('public')->url($campagne->fresh()->image), false);
    }

    public function test_responsable_cannot_use_the_admin_update(): void
    {
        $responsable = User::factory()->create();
        $association = Association::factory()->gereePar($responsable)->create(['nom' => 'Intouchable']);

        $this->actingAs($responsable)->put(route('admin.dons.associations.update', $association), $this->champs($association, ['nom' => 'Piraté']))->assertForbidden();
        $this->assertSame('Intouchable', $association->fresh()->nom);
    }

    public function test_dates_and_pagination_are_in_french(): void
    {
        $associations = Association::factory()->count(12)->create();
        $creneau = CreneauCollecte::factory()->create(['association_id' => $associations->first()->id, 'date' => '2026-11-02']);
        $this->assertStringStartsWith('Lundi 02/11/2026', $creneau->libelle());

        $this->get(route('dons.index'))->assertOk()->assertSeeText('1 à 9 sur 12 résultats')->assertDontSeeText('Showing');
    }
}
