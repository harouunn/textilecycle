<?php

namespace Tests\Feature;

use App\Enums\Upcycling\StatutProjet;
use App\Models\EtapeProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use App\Services\Upcycling\EnregistrerAvecPhotos;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class UpcyclingValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Storage::fake('public');
    }

    private function image(string $name = 'image.png'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 2, 2);
    }

    private function projetData(): array
    {
        return ['titre' => 'Sac cabas en jean', 'description' => 'Transformer un vieux jean en un sac cabas solide.',
            'vetement_origine' => 'Vieux jean', 'resultat' => 'Sac cabas', 'difficulte' => 'facile',
            'duree_minutes' => 60, 'materiel_necessaire' => "Ciseaux\nFil", 'statut' => 'publie',
            'photo_avant' => $this->image('avant.png'), 'photo_apres' => $this->image('apres.png')];
    }

    private function etapeData(): array
    {
        return ['numero' => 1, 'titre' => 'Découper le jean', 'contenu' => 'Découper les jambes en suivant les coutures.', 'photo' => $this->image()];
    }

    private function existingProject(): ProjetUpcycling
    {
        Storage::disk('public')->put('existing/avant.png', $this->image()->getContent());
        Storage::disk('public')->put('existing/apres.png', $this->image()->getContent());

        return ProjetUpcycling::factory()->create(['photo_avant' => 'existing/avant.png', 'photo_apres' => 'existing/apres.png']);
    }

    private function existingStep(ProjetUpcycling $p): EtapeProjet
    {
        Storage::disk('public')->put('existing/etape.png', $this->image()->getContent());

        return EtapeProjet::factory()->for($p, 'projet')->create(['photo' => 'existing/etape.png']);
    }

    public static function projectInvalidData(): iterable
    {
        $cases = [];
        foreach (['titre', 'description', 'vetement_origine', 'resultat', 'materiel_necessaire', 'difficulte', 'duree_minutes'] as $field) {
            foreach (['absent' => '__absent__', 'vide' => '', 'espaces' => " \t\n ", 'unicode' => "\u{00a0}\u{2003}"] as $label => $value) {
                $cases[$field.' '.$label] = [$field, $value];
            }
        }
        foreach (['titre' => [4, 151], 'description' => [19, 5001], 'vetement_origine' => [2, 256], 'resultat' => [2, 256], 'materiel_necessaire' => [2, 2001]] as $field => $lengths) {
            foreach ($lengths as $length) {
                $cases[$field.' longueur '.$length] = [$field, str_repeat('a', $length)];
            }
        }
        foreach (['abc', -1, 0, 2, 5.5, 1441, '1e2'] as $value) {
            $cases['duree '.$value] = ['duree_minutes', $value];
        }
        $cases['enum difficulte'] = ['difficulte', 'impossible'];
        $cases['enum statut'] = ['statut', 'archive'];
        $cases['titre tableau'] = ['titre', ['abc']];
        foreach (['upcycling', 'admin.upcycling'] as $office) {
            foreach ([false, true] as $update) {
                foreach ($cases as $label => $case) {
                    yield $office.' '.($update ? 'update' : 'create').' '.$label => [$office, $update, ...$case];
                }
                if ($office === 'admin.upcycling') {
                    foreach (['__absent__', '', " \t "] as $value) {
                        yield $office.' statut '.($update ? 'update' : 'create').' '.serialize($value) => [$office, $update, 'statut', $value];
                    }
                }
            }
        }
    }

    #[DataProvider('projectInvalidData')]
    public function test_project_rejects_invalid_fields(string $office, bool $update, string $field, mixed $value): void
    {
        $p = $this->existingProject();
        $this->actingAs($p->user);
        $data = $this->projetData();
        if ($value === '__absent__') {
            unset($data[$field]);
        } else {
            $data[$field] = $value;
        }
        $before = $p->fresh()->getRawOriginal();
        $response = $update ? $this->put(route($office.'.projets.update', $p), $data) : $this->post(route($office.'.projets.store'), $data);
        $response->assertSessionHasErrorsIn('projet', [$field]);
        $this->assertSame(1, ProjetUpcycling::count());
        $this->assertSame($before, $p->fresh()->getRawOriginal());
        Storage::disk('public')->assertExists(['existing/avant.png', 'existing/apres.png']);
        $this->assertCount(2, Storage::disk('public')->allFiles());
    }

    public static function stepInvalidData(): iterable
    {
        $cases = [];
        foreach (['titre', 'contenu', 'numero'] as $field) {
            foreach (['__absent__', '', " \n\t ", "\u{00a0}\u{2003}"] as $value) {
                $cases[$field.' '.serialize($value)] = [$field, $value];
            }
        }
        foreach (['titre' => [2, 151], 'contenu' => [9, 5001]] as $field => $lengths) {
            foreach ($lengths as $length) {
                $cases[$field.' '.$length] = [$field, str_repeat('a', $length)];
            }
        }
        foreach (['abc', -1, 0, 1.5, 101, '1e2'] as $value) {
            $cases['numero '.$value] = ['numero', $value];
        }
        foreach (['upcycling', 'admin.upcycling'] as $office) {
            foreach ([false, true] as $update) {
                foreach ($cases as $label => $case) {
                    yield $office.' '.($update ? 'update' : 'create').' '.$label => [$office, $update, ...$case];
                }
            }
        }
    }

    #[DataProvider('stepInvalidData')]
    public function test_step_rejects_invalid_fields(string $office, bool $update, string $field, mixed $value): void
    {
        $p = $this->existingProject();
        $e = $this->existingStep($p);
        $this->actingAs($p->user);
        $data = $this->etapeData();
        if (! $update) {
            $data['numero'] = 2;
        }
        if ($value === '__absent__') {
            unset($data[$field]);
        } else {
            $data[$field] = $value;
        }
        $before = $e->fresh()->getRawOriginal();
        $response = $update ? $this->put(route($office.'.projets.etapes.update', [$p, $e]), $data) : $this->post(route($office.'.projets.etapes.store', $p), $data);
        $response->assertSessionHasErrorsIn('etape', [$field]);
        $this->assertSame(1, EtapeProjet::count());
        $this->assertSame($before, $e->fresh()->getRawOriginal());
        Storage::disk('public')->assertExists('existing/etape.png');
        $this->assertCount(3, Storage::disk('public')->allFiles());
    }

    public static function offices(): array
    {
        return [['upcycling'], ['admin.upcycling']];
    }

    #[DataProvider('offices')]
    public function test_valid_creation_update_and_existing_images(string $office): void
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        $this->post(route($office.'.projets.store'), $this->projetData())->assertSessionHasNoErrors()->assertRedirect();
        $p = ProjetUpcycling::firstOrFail();
        $this->assertSame($u->id, $p->user_id);
        $this->assertSame($office === 'upcycling' ? StatutProjet::Brouillon : StatutProjet::Publie, $p->statut);
        Storage::disk('public')->assertExists([$p->photo_avant, $p->photo_apres]);
        $paths = [$p->photo_avant, $p->photo_apres];
        $data = $this->projetData();
        unset($data['photo_avant'],$data['photo_apres']);
        $data['titre'] = 'Sac cabas modifié';
        $this->put(route($office.'.projets.update', $p), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Sac cabas modifié', $p->fresh()->titre);
        $this->assertSame($paths, [$p->fresh()->photo_avant, $p->fresh()->photo_apres]);
        $this->post(route($office.'.projets.etapes.store', $p), $this->etapeData())->assertSessionHasNoErrors()->assertRedirect();
        $e = $p->etapes()->firstOrFail();
        $path = $e->photo;
        $data = $this->etapeData();
        unset($data['photo']);
        $data['titre'] = 'Découper puis assembler';
        $this->put(route($office.'.projets.etapes.update', [$p, $e]), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Découper puis assembler', $e->fresh()->titre);
        $this->assertSame($path, $e->fresh()->photo);
        Storage::disk('public')->assertExists([...$paths, $path]);
    }

    #[DataProvider('offices')]
    public function test_photos_required_and_missing_stored_images_do_not_satisfy_requirement(string $office): void
    {
        $p = ProjetUpcycling::factory()->create(['photo_avant' => 'missing.png', 'photo_apres' => null]);
        $this->actingAs($p->user);
        foreach ([false, true] as $update) {
            foreach (['__absent__', '', " \t "] as $value) {
                $d = $this->projetData();
                foreach (['photo_avant', 'photo_apres'] as $f) {
                    if ($value === '__absent__') {
                        unset($d[$f]);
                    } else {
                        $d[$f] = $value;
                    }
                }
                ($update ? $this->put(route($office.'.projets.update', $p), $d) : $this->post(route($office.'.projets.store'), $d))->assertSessionHasErrorsIn('projet', ['photo_avant', 'photo_apres']);
            }
        }
        $e = EtapeProjet::factory()->for($p, 'projet')->create();
        foreach ([false, true] as $update) {
            foreach (['__absent__', '', " \t "] as $value) {
                $d = $this->etapeData();
                if (! $update) {
                    $d['numero'] = 2;
                }
                if ($value === '__absent__') {
                    unset($d['photo']);
                } else {
                    $d['photo'] = $value;
                }
                ($update ? $this->put(route($office.'.projets.etapes.update', [$p, $e]), $d) : $this->post(route($office.'.projets.etapes.store', $p), $d))->assertSessionHasErrorsIn('etape', ['photo']);
            }
        }
    }

    #[DataProvider('offices')]
    public function test_images_are_checked_by_real_content_and_size(string $office): void
    {
        $p = $this->existingProject();
        $e = $this->existingStep($p);
        $this->actingAs($p->user);
        foreach ([false, true] as $update) {
            foreach (['fake', 'gif', 'svg', 'corrupt', 'oversize'] as $kind) {
                $make = fn () => match ($kind) {
                    'fake' => UploadedFile::fake()->createWithContent('fake.jpg', 'This is not an image'),
                    'gif' => UploadedFile::fake()->image('image.gif', 2, 2),
                    'corrupt' => UploadedFile::fake()->createWithContent('image.png', "\x89PNG\r\n\x1a\nNot an actual PNG"),
                    'svg' => UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>'),
                    'oversize' => UploadedFile::fake()->createWithContent('large.png', $this->image()->getContent().str_repeat('x', 2097152)),
                };
                $d = $this->projetData();
                $d['photo_avant'] = $make();
                $d['photo_apres'] = $make();
                ($update ? $this->put(route($office.'.projets.update', $p), $d) : $this->post(route($office.'.projets.store'), $d))->assertSessionHasErrorsIn('projet', ['photo_avant', 'photo_apres']);
                $d = $this->etapeData();
                if (! $update) {
                    $d['numero'] = 2;
                } $d['photo'] = $make();
                ($update ? $this->put(route($office.'.projets.etapes.update', [$p, $e]), $d) : $this->post(route($office.'.projets.etapes.store', $p), $d))->assertSessionHasErrorsIn('etape', ['photo']);
                Storage::disk('public')->assertExists(['existing/avant.png', 'existing/apres.png', 'existing/etape.png']);
                $this->assertCount(3, Storage::disk('public')->allFiles());
            }
        }
    }

    #[DataProvider('offices')]
    public function test_jpeg_png_webp_and_boundary_values_are_accepted(string $office): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['jpg', 'png', 'webp'] as $ext) {
            $d = $this->projetData();
            $d['titre'] = '12345';
            $d['description'] = str_repeat('d', 20);
            $d['vetement_origine'] = str_repeat('v', 255);
            $d['resultat'] = str_repeat('r', 255);
            $d['duree_minutes'] = 5;
            $d['photo_avant'] = $this->image('avant.'.$ext);
            $d['photo_apres'] = $this->image('apres.'.$ext);
            $this->post(route($office.'.projets.store'), $d)->assertSessionHasNoErrors()->assertRedirect();
            $p = ProjetUpcycling::latest('id')->firstOrFail();
            $d = $this->etapeData();
            $d['titre'] = '123';
            $d['contenu'] = str_repeat('c', 10);
            $d['numero'] = 100;
            $d['photo'] = $this->image('etape.'.$ext);
            $this->post(route($office.'.projets.etapes.store', $p), $d)->assertSessionHasNoErrors()->assertRedirect();
        }
    }

    #[DataProvider('offices')]
    public function test_duplicate_number_is_scoped_and_current_step_is_ignored(string $office): void
    {
        $p = $this->existingProject();
        $e = $this->existingStep($p);
        $this->actingAs($p->user);
        $this->post(route($office.'.projets.etapes.store', $p), $this->etapeData())->assertSessionHasErrorsIn('etape', ['numero']);
        $other = EtapeProjet::factory()->for($p, 'projet')->create(['numero' => 2]);
        $d = $this->etapeData();
        $d['numero'] = 2;
        $this->put(route($office.'.projets.etapes.update', [$p, $e]), $d)->assertSessionHasErrorsIn('etape', ['numero']);
        $this->put(route($office.'.projets.etapes.update', [$p, $e]), $this->etapeData())->assertSessionHasNoErrors();
        $otherP = ProjetUpcycling::factory()->for($p->user)->create();
        $this->post(route($office.'.projets.etapes.store', $otherP), $this->etapeData())->assertSessionHasNoErrors();
    }

    #[DataProvider('offices')]
    public function test_route_project_controls_association_and_foreign_step_is_rejected(string $office): void
    {
        $p = $this->existingProject();
        $e = $this->existingStep($p);
        $this->actingAs($p->user);
        $d = $this->etapeData();
        $d['numero'] = 2;
        $d['projet_upcycling_id'] = 999999;
        $this->post(route($office.'.projets.etapes.store', $p), $d)->assertSessionHasNoErrors();
        $this->assertSame($p->id, EtapeProjet::latest('id')->firstOrFail()->projet_upcycling_id);
        $this->post(route($office.'.projets.etapes.store', 999999), $this->etapeData())->assertNotFound();
        $other = ProjetUpcycling::factory()->for($p->user)->create();
        $this->put(route($office.'.projets.etapes.update', [$other, $e]), $this->etapeData())->assertNotFound();
        $this->assertSame($p->id, $e->fresh()->projet_upcycling_id);
    }

    public function test_front_permissions_and_publication_cannot_be_bypassed(): void
    {
        $p = $this->existingProject();
        $e = $this->existingStep($p);
        $this->actingAs(User::factory()->create());
        $this->put(route('upcycling.projets.update', $p), $this->projetData())->assertForbidden();
        $this->post(route('upcycling.projets.etapes.store', $p), $this->etapeData())->assertForbidden();
        $this->put(route('upcycling.projets.etapes.update', [$p, $e]), $this->etapeData())->assertForbidden();
        $this->actingAs($p->user);
        $p->update(['statut' => 'brouillon']);
        $d = $this->projetData();
        $d['user_id'] = 999999;
        $this->put(route('upcycling.projets.update', $p), $d)->assertSessionHasNoErrors();
        $this->assertSame(StatutProjet::Brouillon, $p->fresh()->statut);
        $this->assertSame($p->user_id, $p->fresh()->user_id);
    }

    #[DataProvider('offices')]
    public function test_trim_preserves_material_lines_and_old_values_are_scoped(string $office): void
    {
        $p = $this->existingProject();
        $this->actingAs($p->user);
        $d = $this->projetData();
        $d['titre'] = '  Sac propre  ';
        $d['materiel_necessaire'] = " \nCiseaux\nFil\n ";
        $this->put(route($office.'.projets.update', $p), $d)->assertSessionHasNoErrors();
        $this->assertSame('Sac propre', $p->fresh()->titre);
        $this->assertSame("Ciseaux\nFil", $p->fresh()->materiel_necessaire);
        $d = $this->projetData();
        $d['description'] = 'trop court';
        $d['titre'] = 'Titre conservé';
        $url = route($office.'.projets.create');
        $this->from($url)->post(route($office.'.projets.store'), $d)->assertRedirect($url)->assertSessionHasErrorsIn('projet', ['description']);
        // Les assertions de session démarrent un cycle supplémentaire dans le client de test.
        session()->reflash();
        session()->save();
        $this->get($url)->assertOk()->assertSee('Titre conservé')->assertSeeText('La description doit contenir au moins 20 caractères.')->assertSeeText('Après une erreur, sélectionnez à nouveau');
        $bags = (new ViewErrorBag)->put('projet', new MessageBag(['titre' => 'ERREUR PROJET SEULEMENT']));
        $this->withSession(['errors' => $bags, '_old_input' => ['titre' => 'TITRE PROJET SEULEMENT']]);
        $url = $office === 'upcycling' ? route('upcycling.projets.etapes.index', $p) : route('admin.upcycling.projets.show', $p);
        $this->get($url)->assertOk()->assertDontSee('ERREUR PROJET SEULEMENT')->assertDontSee('TITRE PROJET SEULEMENT');
    }

    public function test_failed_persistence_preserves_old_images_and_removes_new_uploads(): void
    {
        $p = $this->existingProject();
        $e = $this->existingStep($p);
        foreach ([[$p, ['photo_avant', 'photo_apres']], [$e, ['photo']]] as [$model,$fields]) {
            $before = $model->fresh()->getRawOriginal();
            $files = [];
            foreach ($fields as $field) {
                $files[$field] = $this->image();
            }
            $request = Request::create('/', 'POST', [], [], $files);
            $class = $model::class;
            $class::saving(fn () => false);
            try {
                app(EnregistrerAvecPhotos::class)->enregistrer($model, ['titre' => 'Titre non sauvegardé'], $request, $fields, 'upcycling/test');
                $this->fail('Un enregistrement annulé doit échouer.');
            } catch (RuntimeException $ex) {
                $this->assertSame('Impossible d’enregistrer les données.', $ex->getMessage());
            } finally {
                $class::flushEventListeners();
                $class::clearBootedModels();
            }
            $this->assertSame($before, $model->fresh()->getRawOriginal());
            Storage::disk('public')->assertExists(['existing/avant.png', 'existing/apres.png', 'existing/etape.png']);
            $this->assertCount(3, Storage::disk('public')->allFiles());
        }
    }

    public function test_successful_replacement_removes_old_file_only_after_commit(): void
    {
        $p = $this->existingProject();
        $old = $p->photo_avant;
        $p->getConnection()->beginTransaction();
        $request = Request::create('/', 'POST', [], [], ['photo_avant' => $this->image()]);
        app(EnregistrerAvecPhotos::class)->enregistrer($p, [], $request, ['photo_avant'], 'upcycling/projets');
        $this->assertNotSame($old, $p->fresh()->photo_avant);
        Storage::disk('public')->assertExists([$old, $p->photo_avant]);
        // Le gestionnaire de tests ignore sa transaction englobante.
        $p->getConnection()->commit();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($p->photo_avant);
    }

    #[DataProvider('offices')]
    public function test_exactly_two_megabytes_is_accepted(string $office): void
    {
        $p = $this->existingProject();
        $this->actingAs($p->user);
        $content = $this->image()->getContent();
        $image = UploadedFile::fake()->createWithContent('large.png', $content.str_repeat('x', 2097152 - strlen($content)));
        $data = $this->projetData();
        $data['photo_avant'] = $image;
        $this->put(route($office.'.projets.update', $p), $data)->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists($p->fresh()->photo_avant);
    }

    public function test_database_exception_preserves_old_images_and_data(): void
    {
        $p = $this->existingProject();
        $before = $p->fresh()->getRawOriginal();
        $request = Request::create('/', 'POST', [], [], ['photo_avant' => $this->image()]);
        try {
            app(EnregistrerAvecPhotos::class)->enregistrer($p, ['titre' => null], $request, ['photo_avant'], 'upcycling/projets');
            $this->fail('La contrainte SQL doit refuser le titre nul.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
        $this->assertSame($before, $p->fresh()->getRawOriginal());
        Storage::disk('public')->assertExists(['existing/avant.png', 'existing/apres.png']);
        $this->assertCount(2, Storage::disk('public')->allFiles());
    }
}
