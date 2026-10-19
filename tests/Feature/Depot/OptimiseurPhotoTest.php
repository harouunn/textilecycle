<?php

namespace Tests\Feature\Depot;

use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use App\Services\Depot\OptimiseurPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptimiseurPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_large_photo_is_reduced_and_gets_a_thumbnail(): void
    {
        Storage::disk('public')->put('vetements/grande.jpg', $this->jpeg(2400, 3000));

        app(OptimiseurPhoto::class)->optimiser('vetements/grande.jpg');

        $this->assertSame([1280, 1600], $this->dimensions('vetements/grande.jpg'));
        $this->assertSame([480, 600], $this->dimensions('vetements/miniatures/grande.jpg'));
    }

    public function test_small_photo_is_kept_as_is(): void
    {
        $contenu = $this->jpeg(400, 500);
        Storage::disk('public')->put('vetements/petite.jpg', $contenu);

        app(OptimiseurPhoto::class)->optimiser('vetements/petite.jpg');

        $this->assertSame($contenu, Storage::disk('public')->get('vetements/petite.jpg'));
        Storage::disk('public')->assertExists('vetements/miniatures/petite.jpg');
    }

    public function test_deposit_creates_the_thumbnail_used_in_lists_and_deletion_removes_both(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('depot.mes-depots.store'), [
            'categorie_id' => Categorie::factory()->create()->id,
            'titre' => 'Chemise',
            'description' => 'Chemise en lin.',
            'taille' => 'M',
            'genre' => 'homme',
            'matiere' => 'Lin',
            'etat' => 'bon',
            'date_depot' => today()->toDateString(),
            'photo' => UploadedFile::fake()->createWithContent('chemise.jpg', $this->jpeg(1200, 1500)),
        ]);

        $vetement = Vetement::query()->sole();
        $miniature = Vetement::cheminMiniature($vetement->photo);
        Storage::disk('public')->assertExists($miniature);
        $this->assertStringEndsWith($miniature, $vetement->miniature_url);

        $this->actingAs($user)->delete(route('depot.mes-depots.destroy', $vetement));

        Storage::disk('public')->assertMissing([$vetement->photo, $miniature]);
    }

    public function test_clothing_without_thumbnail_falls_back_to_its_photo(): void
    {
        $vetement = Vetement::factory()->make(['photo' => 'vetements/ancienne.jpg']);

        $this->assertSame($vetement->photo_url, $vetement->miniature_url);
    }

    private function jpeg(int $largeur, int $hauteur): string
    {
        $image = imagecreatetruecolor($largeur, $hauteur);
        imagefilledrectangle($image, 0, 0, $largeur - 1, $hauteur - 1, imagecolorallocate($image, 60, 120, 80));
        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }

    /**
     * @return array{int, int}
     */
    private function dimensions(string $chemin): array
    {
        $taille = getimagesizefromstring(Storage::disk('public')->get($chemin));

        return [$taille[0], $taille[1]];
    }
}
