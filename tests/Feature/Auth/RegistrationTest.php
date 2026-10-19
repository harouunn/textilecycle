<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSeeText(['Créer un compte', '8 caractères minimum']);
    }

    public function test_new_users_can_register_as_clients(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'Test@Example.com',
            'password' => 'Motdepasse2026',
            'password_confirmation' => 'Motdepasse2026',
            'conditions' => '1',
            'role' => 'admin',
        ]);

        $this->assertAuthenticated();
        // Le nouveau compte confirme d'abord son adresse avec le code reçu par e-mail.
        $response->assertRedirect(route('verification.notice'));
        // Une inscription crée toujours un client, même si le formulaire envoie un rôle.
        $this->assertFalse(auth()->user()->isAdmin());
        $this->assertSame('test@example.com', auth()->user()->email);
    }

    public function test_weak_password_and_missing_consent_are_rejected_in_french(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'Le mot de passe doit contenir une majuscule, une minuscule et un chiffre.',
            'conditions' => 'Vous devez accepter les conditions pour créer un compte.',
        ]);
        $this->assertGuest();
    }
}
