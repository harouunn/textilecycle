<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Vetement;
use App\Notifications\CodeVerificationEmail;
use App\Services\Auth\VerificationEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_a_six_digit_code_by_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Nour', 'email' => 'nour@example.com',
            'password' => 'Motdepasse2026', 'password_confirmation' => 'Motdepasse2026', 'conditions' => '1',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'nour@example.com')->sole();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, CodeVerificationEmail::class, fn ($notification) => preg_match('/^\d{6}$/', $notification->code) === 1);
        $this->assertNotNull($user->code_verification);
        $this->assertNotSame($this->codeEnvoye($user), $user->code_verification, 'Le code doit être stocké haché.');
    }

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verify-email')->assertOk()->assertSeeText('Vérifiez votre e-mail');
    }

    public function test_email_is_verified_with_the_right_code(): void
    {
        Event::fake([Verified::class]);
        $user = User::factory()->unverified()->create();
        $code = $this->codeEnvoye($user);

        $response = $this->actingAs($user)->post(route('verification.verify'), ['code' => $code]);

        $response->assertRedirect(route('home'));
        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNull($user->fresh()->code_verification);
    }

    public function test_wrong_code_is_refused_and_attempts_are_limited(): void
    {
        $user = User::factory()->unverified()->create();
        $code = $this->codeEnvoye($user);

        for ($i = 0; $i < VerificationEmail::ESSAIS_MAX; $i++) {
            $this->actingAs($user)->post(route('verification.verify'), ['code' => '000000'])
                ->assertSessionHasErrors(['code' => 'Code incorrect. Vérifiez l\'e-mail reçu et réessayez.']);
        }

        $this->actingAs($user)->post(route('verification.verify'), ['code' => $code])
            ->assertSessionHasErrors(['code' => 'Trop d\'essais : demandez un nouveau code.']);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_expired_code_is_refused(): void
    {
        $user = User::factory()->unverified()->create();
        $code = $this->codeEnvoye($user);
        $this->travel(VerificationEmail::DUREE_MINUTES + 1)->minutes();

        $this->actingAs($user)->post(route('verification.verify'), ['code' => $code])
            ->assertSessionHasErrors(['code' => 'Ce code a expiré : demandez-en un nouveau.']);
    }

    public function test_user_can_ask_for_a_new_code(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status', 'Un nouveau code vient de vous être envoyé.');

        Notification::assertSentTo($user, CodeVerificationEmail::class);
    }

    public function test_unverified_user_cannot_deposit_or_reserve(): void
    {
        $user = User::factory()->unverified()->create();
        $vetement = Vetement::factory()->create();

        $this->actingAs($user)->get(route('depot.mes-depots.create'))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('depot.reservations.create', $vetement))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('dons.mes-dons'))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('ateliers.demandes.index'))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('upcycling.mes-projets'))->assertRedirect(route('verification.notice'));
    }

    /**
     * Génère un code comme le ferait l'inscription, sans envoyer d'e-mail, et le renvoie en clair.
     */
    private function codeEnvoye(User $user): string
    {
        Notification::fake();
        app(VerificationEmail::class)->envoyerCode($user);

        $code = null;
        Notification::assertSentTo($user, CodeVerificationEmail::class, function ($notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        return $code;
    }
}
