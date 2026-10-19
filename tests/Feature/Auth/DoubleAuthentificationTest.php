<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class DoubleAuthentificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_the_security_page(): void
    {
        $this->get(route('securite.show'))->assertRedirect(route('login'));
    }

    public function test_user_enables_two_factor_by_scanning_the_qr_code_then_confirming_a_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('securite.activer'))->assertRedirect(route('securite.show'));
        $secret = $user->fresh()->two_factor_secret;
        $this->assertNotNull($secret);
        $this->assertFalse($user->fresh()->aDoubleAuthentification());

        $this->actingAs($user)->get(route('securite.show'))
            ->assertSee('<svg', false)
            ->assertSeeText('Scannez le QR code');

        $response = $this->actingAs($user)->post(route('securite.confirmer'), ['code' => $this->codeActuel($secret)]);

        $response->assertRedirect(route('securite.show'));
        $response->assertSessionHas('codes_secours');
        $this->assertTrue($user->fresh()->aDoubleAuthentification());
        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);
    }

    public function test_wrong_confirmation_code_keeps_two_factor_disabled(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('securite.activer'));

        $this->actingAs($user)->post(route('securite.confirmer'), ['code' => '000000'])
            ->assertSessionHasErrors(['code' => 'Code incorrect ou expiré. Vérifiez l\'heure de votre téléphone et réessayez.']);

        $this->assertFalse($user->fresh()->aDoubleAuthentification());
    }

    public function test_secret_is_encrypted_in_the_database(): void
    {
        $user = $this->utilisateurProtege();

        $brut = \DB::table('users')->where('id', $user->id)->value('two_factor_secret');

        $this->assertNotSame($user->two_factor_secret, $brut);
    }

    public function test_login_with_two_factor_asks_for_the_code_before_connecting(): void
    {
        $user = $this->utilisateurProtege();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('deux-facteurs.create'));
        $this->assertGuest();

        $this->post(route('deux-facteurs.store'), ['code' => $this->codeActuel($user->two_factor_secret)])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_with_two_factor_lands_on_the_back_office(): void
    {
        $admin = $this->utilisateurProtege(User::factory()->admin()->create());

        $this->post('/login', ['email' => $admin->email, 'password' => 'password']);

        $this->post(route('deux-facteurs.store'), ['code' => $this->codeActuel($admin->two_factor_secret)])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_wrong_code_is_refused_and_attempts_are_limited(): void
    {
        $user = $this->utilisateurProtege();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('deux-facteurs.store'), ['code' => '000000'])->assertSessionHasErrors(['code' => 'Code incorrect. Réessayez.']);
        }

        $this->post(route('deux-facteurs.store'), ['code' => $this->codeActuel($user->two_factor_secret)])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_works_only_once(): void
    {
        $user = $this->utilisateurProtege();
        $code = $user->two_factor_recovery_codes[0];

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('deux-facteurs.store'), ['code_secours' => strtolower($code)])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotContains($code, $user->fresh()->two_factor_recovery_codes);

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('deux-facteurs.store'), ['code_secours' => $code])->assertSessionHasErrors('code_secours');
        $this->assertGuest();
    }

    public function test_challenge_page_without_pending_login_goes_back_to_login(): void
    {
        $this->get(route('deux-facteurs.create'))->assertRedirect(route('login'));
    }

    public function test_user_regenerates_recovery_codes(): void
    {
        $user = $this->utilisateurProtege();
        $anciens = $user->two_factor_recovery_codes;

        $this->actingAs($user)->post(route('securite.codes'))->assertSessionHas('codes_secours');

        $this->assertNotEquals($anciens, $user->fresh()->two_factor_recovery_codes);
    }

    public function test_disabling_requires_the_current_password(): void
    {
        $user = $this->utilisateurProtege();

        $this->actingAs($user)->delete(route('securite.desactiver'), ['password' => 'mauvais'])
            ->assertSessionHasErrors(['password' => 'Le mot de passe est incorrect.']);
        $this->assertTrue($user->fresh()->aDoubleAuthentification());

        $this->actingAs($user)->delete(route('securite.desactiver'), ['password' => 'password']);
        $this->assertFalse($user->fresh()->aDoubleAuthentification());
    }

    private function utilisateurProtege(?User $user = null): User
    {
        $user ??= User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => app(Google2FA::class)->generateSecretKey(32),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['AAAA-BBBB', 'CCCC-DDDD'],
        ])->save();

        return $user->fresh();
    }

    private function codeActuel(string $secret): string
    {
        return app(Google2FA::class)->getCurrentOtp($secret);
    }
}
