<?php

namespace Tests\Feature\Depot;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\StatutVetement;
use App\Enums\Depot\Taille;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use App\Services\Depot\ClassificateurIA;
use App\Services\Depot\DetecteurDoublons;
use App\Services\Depot\ImpactEcologique;
use App\Services\Depot\Recommandations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_ai_is_skipped_when_disabled(): void
    {
        Http::fake();
        config(['depot.ia.active' => false]);

        $this->assertFalse(app(ClassificateurIA::class)->disponible());
        $this->assertNull(app(ClassificateurIA::class)->classer('photo.jpg', 'Chemise', collect()));
        Http::assertNothingSent();
    }

    public function test_ollama_vision_model_prefills_the_form(): void
    {
        $categorie = Categorie::factory()->create(['nom' => 'Hauts']);
        $this->simulerOllama(Http::response(['done' => true, 'message' => ['role' => 'assistant', 'content' => json_encode([
            'categorie' => 'Hauts', 'type_vetement' => 'chemise', 'matiere' => 'lin', 'etat' => 'tres_bon',
            'genre' => 'homme', 'taille' => 'M', 'couleur' => 'bleu ciel', 'uni' => true,
            'defauts' => ['petite tache au col'], 'description' => 'Chemise en lin bleu ciel, coupe droite.', 'confiance' => 88,
        ])]]));

        $response = $this->actingAs(User::factory()->create())->post(route('depot.mes-depots.analyser'), [
            'titre' => 'Chemise',
            'photo' => UploadedFile::fake()->image('chemise.jpg', 300, 400),
        ]);

        $response->assertSessionHasInput('categorie_id', $categorie->id);
        $response->assertSessionHasInput('matiere', 'Lin');
        $response->assertSessionHasInput('etat', 'tres_bon');
        $response->assertSessionHasInput('description', 'Chemise en lin bleu ciel, coupe droite.');
        $this->assertContains('Défauts repérés sur la photo : petite tache au col.', session('analyse'));
        $this->assertStringContainsString('Ollama (gemma3:4b, confiance 88 %)', session('analyse')[0]);

        // La photo est envoyée au modèle local, avec un schéma qui impose les valeurs du projet.
        Http::assertSent(fn ($requete) => $requete->url() === 'http://127.0.0.1:11434/api/chat'
            && $requete['model'] === 'gemma3:4b'
            && filled($requete['messages'][1]['images'][0])
            && in_array('Hauts', $requete['format']['properties']['categorie']['enum'], true));
    }

    public function test_local_analysis_takes_over_when_ollama_is_down(): void
    {
        Categorie::factory()->create(['nom' => 'Hauts', 'description' => 'Chemises et tops.']);
        $this->simulerOllama(fn () => throw new ConnectionException('Connexion refusée'));

        $response = $this->actingAs(User::factory()->create())->post(route('depot.mes-depots.analyser'), [
            'titre' => 'Chemise en lin',
            'photo' => UploadedFile::fake()->image('chemise.jpg', 300, 400),
        ]);

        $response->assertSessionHasInput('matiere', 'Lin');
        $this->assertStringStartsWith('Photo : couleur dominante', session('analyse')[0]);
    }

    public function test_local_analysis_takes_over_when_the_model_is_missing(): void
    {
        Categorie::factory()->create(['nom' => 'Hauts']);
        $this->simulerOllama(Http::response(['error' => 'model "gemma3:4b" not found'], 404));

        $this->actingAs(User::factory()->create())->post(route('depot.mes-depots.analyser'), [
            'titre' => 'Chemise en lin',
            'photo' => UploadedFile::fake()->image('chemise.jpg', 300, 400),
        ]);

        $this->assertStringStartsWith('Photo : couleur dominante', session('analyse')[0]);
    }

    public function test_status_reports_whether_the_model_is_installed(): void
    {
        config(['depot.ia.active' => true]);
        Http::fake(['127.0.0.1:11434/api/tags' => Http::response(['models' => [['name' => 'gemma3:4b'], ['name' => 'qwen3:8b']]])]);

        $this->assertSame(['en_ligne' => true, 'modele_installe' => true, 'modele' => 'gemma3:4b'], app(ClassificateurIA::class)->etat());
    }

    public function test_ecological_impact_depends_on_the_type_of_clothing(): void
    {
        $impact = app(ImpactEcologique::class);

        $jean = Vetement::factory()->make(['titre' => 'Jean slim']);
        $tshirt = Vetement::factory()->make(['titre' => 'T-shirt blanc']);

        $this->assertSame(['eau' => 7500, 'co2' => 25], $impact->pourVetement($jean));
        $this->assertSame(['eau' => 2700, 'co2' => 6], $impact->pourVetement($tshirt));
    }

    public function test_catalogue_shows_the_impact_of_clothes_given_a_second_life(): void
    {
        Vetement::factory()->statut(StatutVetement::Donne)->create(['titre' => 'Jean donné']);
        Vetement::factory()->create(['titre' => 'Pull disponible']);

        $this->get(route('depot.catalogue.index'))
            ->assertSeeText(['1 vêtement(s) ont trouvé une seconde vie', '7 500 litres', '25 kg']);
        $this->get(route('depot.catalogue.show', Vetement::query()->where('titre', 'Pull disponible')->first()))
            ->assertSeeText('Un choix responsable.');
    }

    public function test_recommendations_rank_the_most_similar_clothes_first(): void
    {
        $categorie = Categorie::factory()->create();
        $reference = Vetement::factory()->for($categorie)->create(['taille' => Taille::M, 'genre' => Genre::Femme, 'matiere' => 'Lin', 'etat' => Etat::Bon]);
        $tresProche = Vetement::factory()->for($categorie)->create(['titre' => 'Très proche', 'taille' => Taille::M, 'genre' => Genre::Femme, 'matiere' => 'lin', 'etat' => Etat::Bon]);
        $proche = Vetement::factory()->for($categorie)->create(['titre' => 'Proche', 'taille' => Taille::XL, 'genre' => Genre::Homme, 'matiere' => 'Laine', 'etat' => Etat::Use]);
        Vetement::factory()->create(['titre' => 'Rien à voir', 'taille' => Taille::XS, 'genre' => Genre::Enfant, 'matiere' => 'Soie', 'etat' => Etat::Neuf]);
        Vetement::factory()->for($categorie)->enAttente()->create(['titre' => 'Pas encore validé']);

        $titres = app(Recommandations::class)->pour($reference)->pluck('titre')->all();

        $this->assertSame(['Très proche', 'Proche'], $titres);
    }

    public function test_duplicate_photos_are_detected_even_when_resized(): void
    {
        $original = $this->photoMotif(400, 500);
        $copieReduite = $this->photoMotif(200, 250);
        Storage::disk('public')->put('vetements/a.jpg', $original);
        Storage::disk('public')->put('vetements/b.jpg', $copieReduite);
        Storage::disk('public')->put('vetements/c.jpg', $this->photoMotif(400, 500, inverse: true));

        $detecteur = app(DetecteurDoublons::class);
        $a = Vetement::factory()->create(['photo' => 'vetements/a.jpg', 'photo_empreinte' => $detecteur->empreinte('vetements/a.jpg')]);
        $b = Vetement::factory()->create(['photo' => 'vetements/b.jpg', 'photo_empreinte' => $detecteur->empreinte('vetements/b.jpg')]);
        Vetement::factory()->create(['photo' => 'vetements/c.jpg', 'photo_empreinte' => $detecteur->empreinte('vetements/c.jpg')]);

        $this->assertSame([$b->id], $detecteur->doublonsDe($a)->modelKeys());
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.depot.vetements.show', $a))
            ->assertSeeText('Photo en double possible.');
    }

    public function test_uploaded_photo_gets_a_fingerprint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('depot.mes-depots.store'), [
            'categorie_id' => Categorie::factory()->create()->id,
            'titre' => 'Chemise', 'description' => 'Chemise.', 'taille' => 'M', 'genre' => 'homme',
            'matiere' => 'Lin', 'etat' => 'bon', 'date_depot' => today()->toDateString(),
            'photo' => UploadedFile::fake()->createWithContent('c.jpg', $this->photoMotif(300, 300)),
        ]);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', Vetement::query()->sole()->photo_empreinte);
    }

    /**
     * Simule le serveur Ollama local (aucun vrai appel réseau pendant les tests).
     */
    private function simulerOllama(mixed $reponse): void
    {
        config(['depot.ia.active' => true, 'depot.ia.modele' => 'gemma3:4b', 'depot.ia.url' => 'http://127.0.0.1:11434']);
        Http::fake(['127.0.0.1:11434/api/chat' => $reponse]);
    }

    private function photoMotif(int $largeur, int $hauteur, bool $inverse = false): string
    {
        $image = imagecreatetruecolor($largeur, $hauteur);
        for ($x = 0; $x < $largeur; $x++) {
            $niveau = (int) (255 * $x / $largeur);
            $niveau = $inverse ? 255 - $niveau : $niveau;
            imageline($image, $x, 0, $x, $hauteur, imagecolorallocate($image, $niveau, (int) ($niveau / 2), 255 - $niveau));
        }
        imagefilledrectangle($image, (int) ($largeur * .3), (int) ($hauteur * .3), (int) ($largeur * .6), (int) ($hauteur * .7), imagecolorallocate($image, 20, 20, 20));
        ob_start();
        imagejpeg($image, null, 90);

        return (string) ob_get_clean();
    }
}
