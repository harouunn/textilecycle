<?php

namespace Tests\Feature\Dons;

use App\Enums\Dons\DonStatut;
use App\Models\AiLog;
use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Models\CreneauCollecte;
use App\Models\Don;
use App\Models\User;
use App\Services\Dons\DonWorkflowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Revue du module : chaque page s'affiche sans requête N+1 (chargement paresseux interdit),
 * et chaque page avec une liste affiche un message quand elle est vide.
 */
class RevuePagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $donateur;

    private User $responsable;

    private Association $association;

    private Don $don;

    private CampagneCollecte $campagne;

    private CreneauCollecte $creneau;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->admin = User::factory()->admin()->create();
        $this->donateur = User::factory()->create();
        $this->responsable = User::factory()->create();
    }

    /**
     * Plusieurs associations, dons, campagnes et créneaux : un chargement paresseux dans une boucle serait détecté.
     */
    private function remplir(): void
    {
        foreach (range(1, 3) as $i) {
            $association = Association::factory()->gereePar($this->responsable)->create(['latitude' => 36.8 + $i / 100, 'longitude' => 10.18]);
            $campagne = CampagneCollecte::factory()->create(['association_id' => $association->id]);
            $creneau = CreneauCollecte::factory()->create(['association_id' => $association->id]);
            foreach ([DonStatut::Propose, DonStatut::Accepte, DonStatut::Planifie] as $statut) {
                Don::factory()->statut($statut)->create(['association_id' => $association->id, 'user_id' => $this->donateur->id,
                    'campagne_id' => $campagne->id, 'creneau_id' => $creneau->id, 'score_risque' => 50]);
            }
        }

        $this->association = $association;
        $this->campagne = $campagne;
        $this->creneau = $creneau;
        $this->don = Don::query()->latest('id')->first();
        app(DonWorkflowService::class)->transition($this->don, DonStatut::Recu, 'Bien reçu', $this->responsable);
        AiLog::query()->create(['user_id' => $this->donateur->id, 'fonctionnalite' => 'assistant', 'duree_ms' => 10, 'succes' => true]);

        // Une notification de chaque type pour la page Notifications.
        $this->actingAs($this->responsable)->patch(route('dons.espace.statut', Don::query()->first()), ['statut' => 'accepte']);
    }

    /**
     * @return iterable<string, array{string, callable(self): string}>
     */
    public static function pages(): iterable
    {
        yield 'associations (public)' => ['invite', fn (self $t) => route('dons.index')];
        yield 'associations près de moi' => ['invite', fn (self $t) => route('dons.index', ['lat' => 36.8, 'lng' => 10.18, 'rayon' => 25])];
        yield 'fiche association' => ['invite', fn (self $t) => route('dons.associations.show', $t->association)];
        yield 'campagnes' => ['invite', fn (self $t) => route('dons.campagnes.index')];
        yield 'classement' => ['invite', fn (self $t) => route('dons.classement')];
        yield 'vérification reçu' => ['invite', fn (self $t) => route('dons.verifier', $t->don->fresh()->reference)];
        yield 'faire un don' => ['donateur', fn (self $t) => route('dons.create', $t->association)];
        yield 'mes dons' => ['donateur', fn (self $t) => route('dons.mes-dons')];
        yield 'mon don' => ['donateur', fn (self $t) => route('dons.mes-dons.show', $t->don)];
        yield 'mon impact' => ['donateur', fn (self $t) => route('dons.mon-impact')];
        yield 'notifications' => ['donateur', fn (self $t) => route('dons.notifications.index')];
        yield 'espace : dons reçus' => ['responsable', fn (self $t) => route('dons.espace.index')];
        yield 'espace : un don' => ['responsable', fn (self $t) => route('dons.espace.show', $t->don)];
        yield 'espace : créneaux' => ['responsable', fn (self $t) => route('dons.espace.creneaux.index')];
        yield 'espace : présentation' => ['responsable', fn (self $t) => route('dons.espace.presentation.edit', $t->association)];
        yield 'espace : rapport' => ['responsable', fn (self $t) => route('dons.espace.rapport', $t->association)];
        yield 'admin : tableau de bord' => ['admin', fn (self $t) => route('admin.dons.tableau-de-bord')];
        yield 'admin : dons' => ['admin', fn (self $t) => route('admin.dons.dons.index')];
        yield 'admin : dons à vérifier' => ['admin', fn (self $t) => route('admin.dons.dons.index', ['risque' => 1])];
        yield 'admin : don' => ['admin', fn (self $t) => route('admin.dons.dons.show', $t->don)];
        yield 'admin : nouveau don' => ['admin', fn (self $t) => route('admin.dons.dons.create')];
        yield 'admin : modifier don' => ['admin', fn (self $t) => route('admin.dons.dons.edit', $t->don)];
        yield 'admin : associations' => ['admin', fn (self $t) => route('admin.dons.associations.index')];
        yield 'admin : association' => ['admin', fn (self $t) => route('admin.dons.associations.show', $t->association)];
        yield 'admin : modifier association' => ['admin', fn (self $t) => route('admin.dons.associations.edit', $t->association)];
        yield 'admin : campagnes' => ['admin', fn (self $t) => route('admin.dons.campagnes.index')];
        yield 'admin : campagne' => ['admin', fn (self $t) => route('admin.dons.campagnes.show', $t->campagne)];
        yield 'admin : modifier campagne' => ['admin', fn (self $t) => route('admin.dons.campagnes.edit', $t->campagne)];
        yield 'admin : journal IA' => ['admin', fn (self $t) => route('admin.dons.journal-ia')];
    }

    #[DataProvider('pages')]
    public function test_page_renders_without_lazy_loading(string $role, callable $url): void
    {
        $this->remplir();
        Model::preventLazyLoading();

        try {
            $requete = match ($role) {
                'invite' => $this,
                'donateur' => $this->actingAs($this->donateur),
                'responsable' => $this->actingAs($this->responsable),
                'admin' => $this->actingAs($this->admin),
            };
            $requete->get($url($this))->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function pagesVides(): iterable
    {
        yield 'associations' => ['invite', 'dons.index', 'Aucune association active pour le moment.'];
        yield 'campagnes' => ['invite', 'dons.campagnes.index', 'Aucune campagne de collecte en cours pour le moment.'];
        yield 'classement' => ['invite', 'dons.classement', 'Le classement apparaîtra dès qu\'un premier don aura été reçu'];
        yield 'mes dons' => ['donateur', 'dons.mes-dons', 'Aucun don pour le moment.'];
        yield 'notifications' => ['donateur', 'dons.notifications.index', 'Aucune notification pour le moment.'];
        yield 'admin : dons' => ['admin', 'admin.dons.dons.index', 'Aucun don ne correspond à ces critères.'];
        yield 'admin : associations' => ['admin', 'admin.dons.associations.index', 'Aucune association'];
        yield 'admin : campagnes' => ['admin', 'admin.dons.campagnes.index', 'Aucune campagne ne correspond à ces critères.'];
        yield 'admin : journal IA' => ['admin', 'admin.dons.journal-ia', 'Aucun appel enregistré.'];
    }

    #[DataProvider('pagesVides')]
    public function test_empty_lists_show_a_message(string $role, string $route, string $message): void
    {
        $requete = match ($role) {
            'invite' => $this,
            'donateur' => $this->actingAs($this->donateur),
            'admin' => $this->actingAs($this->admin),
        };

        $requete->get(route($route))->assertOk()->assertSeeText($message);
    }

    public function test_empty_espace_pages_show_a_message(): void
    {
        $association = Association::factory()->gereePar($this->responsable)->create();

        $this->actingAs($this->responsable)->get(route('dons.espace.index'))->assertOk()->assertSeeText('Aucun don pour le moment.');
        $this->actingAs($this->responsable)->get(route('dons.espace.creneaux.index'))->assertOk()->assertSeeText('Aucun créneau à venir.');
        $this->actingAs($this->responsable)->get(route('dons.espace.rapport', $association))->assertOk()->assertSeeText('Aucun don reçu ce mois-ci.');
    }
}
