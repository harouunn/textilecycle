<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\Captcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Désactivé par défaut dans les tests (phpunit.xml) : on l'active ici.
        config(['auth.captcha' => true]);
    }

    public function test_captcha_image_is_a_fresh_uncached_png(): void
    {
        $response = $this->get(route('captcha'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
        $this->assertSame([200, 64], array_slice(getimagesizefromstring($response->getContent()), 0, 2));
    }

    public function test_login_and_register_pages_show_the_captcha(): void
    {
        $this->get('/login')->assertSeeText('Vérification : êtes-vous humain ?');
        $this->get('/register')->assertSee(route('captcha'));
    }

    public function test_login_without_captcha_is_refused(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['captcha' => 'Recopiez les caractères de l\'image pour prouver que vous êtes humain.']);
        $this->assertGuest();
    }

    public function test_login_with_a_wrong_captcha_is_refused(): void
    {
        $user = User::factory()->create();
        $this->get(route('captcha'));

        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'captcha' => 'ZZZZZ'])
            ->assertSessionHasErrors(['captcha' => 'Le code de vérification est incorrect. Recopiez les caractères de la nouvelle image.']);
        $this->assertGuest();
    }

    public function test_login_with_the_right_captcha_works_and_the_code_is_single_use(): void
    {
        $user = User::factory()->create();
        $code = $this->codeAffiche();

        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'captcha' => strtolower($code)])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'captcha' => $code])
            ->assertSessionHasErrors('captcha');
    }

    public function test_expired_captcha_is_refused(): void
    {
        $code = $this->codeAffiche();
        $this->travel(11)->minutes();

        $this->assertFalse(app(Captcha::class)->verifier($code));
    }

    public function test_registration_requires_the_captcha(): void
    {
        $this->post('/register', [
            'name' => 'Robot', 'email' => 'robot@example.com',
            'password' => 'Motdepasse2026', 'password_confirmation' => 'Motdepasse2026', 'conditions' => '1',
        ])->assertSessionHasErrors('captcha');

        $this->assertGuest();
    }

    /**
     * Affiche une image CAPTCHA puis remplace son code (aléatoire, invisible en test) par un code connu.
     */
    private function codeAffiche(): string
    {
        $this->get(route('captcha'));
        session()->put('captcha', ['empreinte' => hash('sha256', 'K7M2P'), 'expire' => now()->addMinutes(10)->getTimestamp()]);

        return 'K7M2P';
    }
}
