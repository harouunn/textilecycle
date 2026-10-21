<?php

namespace Tests\Feature\Dons;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\EtatGeneral;
use App\Enums\Dons\ModeRemise;
use App\Enums\Dons\TypeArticle;
use App\Events\Dons\DonStatutChanged;
use App\Exceptions\Dons\InvalidDonTransitionException;
use App\Models\Don;
use App\Models\User;
use App\Services\Dons\DonWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DonWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Passer un don à « Reçu » génère son reçu PDF.
        Storage::fake('local');
    }

    /** @var array<string, list<DonStatut>> Transitions attendues, écrites à la main pour verrouiller la machine à états. */
    private const ATTENDUES = [
        'propose' => [DonStatut::Accepte, DonStatut::Refuse, DonStatut::Annule],
        'accepte' => [DonStatut::Planifie, DonStatut::Recu, DonStatut::Refuse, DonStatut::Annule],
        'planifie' => [DonStatut::Recu, DonStatut::Annule],
        'recu' => [DonStatut::Distribue],
        'distribue' => [],
        'refuse' => [],
        'annule' => [],
    ];

    /**
     * @return iterable<string, array{DonStatut, DonStatut, bool}>
     */
    public static function toutesLesTransitions(): iterable
    {
        foreach (DonStatut::cases() as $from) {
            foreach (DonStatut::cases() as $to) {
                yield "{$from->value} → {$to->value}" => [$from, $to, in_array($to, self::ATTENDUES[$from->value], true)];
            }
        }
    }

    #[DataProvider('toutesLesTransitions')]
    public function test_state_machine_allows_only_the_expected_transitions(DonStatut $from, DonStatut $to, bool $autorisee): void
    {
        $this->assertSame($autorisee, $from->canTransitionTo($to));

        $don = Don::factory()->statut($from)->create();
        $admin = User::factory()->admin()->create();

        if (! $autorisee) {
            $this->expectException(InvalidDonTransitionException::class);
        }

        app(DonWorkflowService::class)->transition($don, $to, null, $admin);

        $this->assertSame($to, $don->statut);
        $this->assertSame($to, $don->fresh()->statut);
    }

    public function test_forbidden_transition_changes_nothing(): void
    {
        Event::fake([DonStatutChanged::class]);
        $don = Don::factory()->statut(DonStatut::Refuse)->create();

        try {
            app(DonWorkflowService::class)->transition($don, DonStatut::Recu, 'Essai', User::factory()->admin()->create());
            $this->fail('La transition refusé → reçu aurait dû être rejetée.');
        } catch (InvalidDonTransitionException $e) {
            $this->assertSame(DonStatut::Refuse, $e->from);
            $this->assertSame(DonStatut::Recu, $e->to);
        }

        $this->assertSame(DonStatut::Refuse, $don->fresh()->statut);
        $this->assertSame(1, $don->historiques()->count());
        Event::assertNotDispatched(DonStatutChanged::class);
    }

    public function test_transition_is_recorded_in_the_history_and_dispatches_an_event(): void
    {
        Event::fake([DonStatutChanged::class]);
        $don = Don::factory()->propose()->create();
        $responsable = User::factory()->create();

        app(DonWorkflowService::class)->transition($don, DonStatut::Accepte, '  Rendez-vous mardi.  ', $responsable);

        $this->assertDatabaseHas('don_historiques', [
            'don_id' => $don->id,
            'user_id' => $responsable->id,
            'ancien_statut' => 'propose',
            'nouveau_statut' => 'accepte',
            'commentaire' => 'Rendez-vous mardi.',
        ]);
        Event::assertDispatched(DonStatutChanged::class, fn (DonStatutChanged $event) => $event->don->is($don)
            && $event->ancien === DonStatut::Propose
            && $event->nouveau === DonStatut::Accepte
            && $event->par->is($responsable)
            && $event->commentaire === 'Rendez-vous mardi.');
    }

    public function test_creating_a_donation_opens_its_history(): void
    {
        $don = Don::factory()->propose()->create();

        $etape = $don->historiques()->sole();
        $this->assertTrue($etape->estCreation());
        $this->assertSame(DonStatut::Propose, $etape->nouveau_statut);
    }

    public function test_full_lifecycle_builds_an_ordered_timeline(): void
    {
        $don = Don::factory()->propose()->create();
        $admin = User::factory()->admin()->create();
        $workflow = app(DonWorkflowService::class);

        foreach ([DonStatut::Accepte, DonStatut::Planifie, DonStatut::Recu, DonStatut::Distribue] as $statut) {
            $workflow->transition($don, $statut, null, $admin);
        }

        $this->assertSame(
            ['propose', 'accepte', 'planifie', 'recu', 'distribue'],
            $don->historiques()->get()->map(fn ($etape) => $etape->nouveau_statut->value)->all(),
        );
        $this->assertTrue($don->fresh()->statut->isFinal());
    }

    public function test_enum_constants_kept_for_other_modules_match_the_enums(): void
    {
        $this->assertSame(DonStatut::options(), Don::STATUTS);
        $this->assertSame(TypeArticle::options(), Don::TYPES);
        $this->assertSame(EtatGeneral::options(), Don::ETATS);
        $this->assertSame(ModeRemise::options(), Don::MODES);
        foreach (DonStatut::cases() as $statut) {
            $this->assertSame($statut->color(), Don::STATUT_COLORS[$statut->value]);
        }
    }
}
