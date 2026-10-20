<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PretPourDeploiementTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_sends_the_security_headers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_unknown_page_shows_the_french_error_page(): void
    {
        $this->get('/une-page-qui-n-existe-pas')
            ->assertNotFound()
            ->assertSeeText('Page introuvable')
            ->assertSeeText('Revenir à l\'accueil');
    }

    public function test_forbidden_page_is_in_french(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')
            ->assertForbidden()
            ->assertSeeText('Accès réservé');
    }

    public function test_health_check_answers(): void
    {
        $this->get('/up')->assertOk();
    }
}
