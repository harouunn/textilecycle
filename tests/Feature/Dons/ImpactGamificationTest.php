<?php

namespace Tests\Feature\Dons;

use App\Enums\Dons\DonStatut;
use App\Models\Don;
use App\Models\DonPoint;
use App\Models\User;
use App\Services\Dons\BadgeService;
use App\Services\Dons\DonWorkflowService;
use App\Services\Dons\ImpactCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImpactGamificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['textilecycle.dons.impact.co2_kg_par_kg' => 10, 'textilecycle.dons.impact.eau_litres_par_kg' => 1000]);
    }

    private function recevoir(User $donateur, float $kg): Don
    {
        $don = Don::factory()->statut(DonStatut::Accepte)->create(['user_id' => $donateur->id, 'poids_kg' => $kg]);

        return app(DonWorkflowService::class)->transition($don, DonStatut::Recu, null, User::factory()->admin()->create());
    }

    // Impact

    public function test_impact_uses_the_configured_coefficients(): void
    {
        $this->assertSame(['kg' => 2.5, 'co2_kg' => 25.0, 'eau_litres' => 2500.0], app(ImpactCalculator::class)->pourKg(2.5));
    }

    public function test_user_impact_counts_only_received_donations(): void
    {
        $user = User::factory()->create();
        $this->recevoir($user, 3);
        Don::factory()->statut(DonStatut::Distribue)->create(['user_id' => $user->id, 'poids_kg' => 2]);
        Don::factory()->statut(DonStatut::Propose)->create(['user_id' => $user->id, 'poids_kg' => 40]);

        $impact = app(ImpactCalculator::class)->pourUtilisateur($user);

        $this->assertSame(5.0, $impact['kg']);
        $this->assertSame(50.0, $impact['co2_kg']);
        $this->assertSame(2, $impact['dons']);
    }

    public function test_impact_is_shown_per_donation_per_user_and_globally(): void
    {
        $user = User::factory()->create();
        $don = $this->recevoir($user, 4);

        $this->actingAs($user)->get(route('dons.mes-dons.show', $don))->assertOk()->assertSeeText(['Impact de ce don', '40 kg CO₂']);
        $this->actingAs($user)->get(route('dons.mon-impact'))->assertOk()->assertSeeText(['40 points', '4 000 litres d\'eau']);
        $this->get(route('dons.index'))->assertOk()->assertSeeText(['Notre impact ensemble', '4,0 kg']);
    }

    // Points et badges

    public function test_receiving_a_donation_earns_ten_points_per_kilo_once(): void
    {
        $user = User::factory()->create();
        $don = $this->recevoir($user, 2.5);

        $this->assertSame(25, app(BadgeService::class)->points($user));

        // Distribuer ensuite (ou créditer à nouveau) ne rapporte rien de plus.
        app(DonWorkflowService::class)->transition($don, DonStatut::Distribue, null, User::factory()->admin()->create());
        app(BadgeService::class)->crediterDonRecu($don);
        $this->assertSame(1, DonPoint::query()->count());
        $this->assertSame(25, app(BadgeService::class)->points($user));
    }

    public function test_badges_follow_the_received_donations(): void
    {
        $user = User::factory()->create();
        $badges = app(BadgeService::class);
        $this->assertSame([], $badges->badgesObtenus($user));

        $this->recevoir($user, 6);
        $this->assertSame(['Premier don'], $badges->badgesObtenus($user));

        $this->recevoir($user, 5);
        $this->assertSame(['Premier don', '10 kg'], $badges->badgesObtenus($user));

        foreach (range(1, 3) as $i) {
            $this->recevoir($user, 13);
        }
        $this->assertSame(['Premier don', '10 kg', '50 kg', 'Fidèle'], $badges->badgesObtenus($user));
    }

    public function test_leaderboard_shows_top_ten_first_names_only(): void
    {
        foreach (range(1, 12) as $i) {
            $this->recevoir(User::factory()->create(['name' => "Prenom{$i} Nomdefamille{$i}"]), $i);
        }

        $classement = app(BadgeService::class)->classement();
        $this->assertCount(10, $classement);
        $this->assertSame(['rang' => 1, 'prenom' => 'Prenom12', 'points' => 120], $classement[0]);
        $this->assertSame(array_map(fn ($i) => "Prenom{$i}", range(12, 3)), array_column($classement, 'prenom'));

        $this->get(route('dons.classement'))
            ->assertOk()
            ->assertSeeTextInOrder(['Prenom12', '120 pts', 'Prenom11', 'Prenom3', '30 pts'])
            ->assertDontSeeText('Nomdefamille');
    }
}
