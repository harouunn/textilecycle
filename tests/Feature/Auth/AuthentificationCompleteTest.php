<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\CodeVerificationEmail;
use App\Notifications\ReinitialisationMotDePasse;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Grille « Authentification » : reset en français, rôles et permissions Spatie, photo de profil.
 */
class AuthentificationCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_email_is_in_french_with_expiry(): void
    {
        Notification::fake();
        $user = User::factory()->create(['name' => 'Inès']);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user, ReinitialisationMotDePasse::class, function (ReinitialisationMotDePasse $notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Réinitialisation de votre mot de passe TexTileCycle'
                && str_contains(implode(' ', array_merge($mail->introLines, $mail->outroLines)), 'valable 60 minutes')
                && str_contains($mail->actionUrl, '/reset-password/'.$notification->token);
        });
    }

    public function test_forgot_password_does_not_reveal_unknown_emails(): void
    {
        $this->post('/forgot-password', ['email' => 'inconnu@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('passwords.sent'));
    }

    public function test_expired_reset_token_is_refused(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ReinitialisationMotDePasse::class, function ($notification) use ($user) {
            $this->travel(61)->minutes();

            $this->post('/reset-password', [
                'token' => $notification->token, 'email' => $user->email,
                'password' => 'NouveauMdp2026', 'password_confirmation' => 'NouveauMdp2026',
            ])->assertSessionHasErrors(['email' => __('passwords.token')]);

            return true;
        });
    }

    public function test_spatie_roles_follow_the_role_column(): void
    {
        $client = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->assertTrue($client->hasRole('client'));
        $this->assertTrue($client->can(Permissions::DEPOSER));
        $this->assertFalse($client->can(Permissions::MODERER_DEPOTS));
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->can(Permissions::MODERER_DEPOTS));

        // Passage en administrateur : le rôle Spatie suit.
        $client->update(['role' => 'admin']);
        $this->assertTrue($client->fresh()->hasRole('admin'));
        $this->assertFalse($client->fresh()->hasRole('client'));
    }

    public function test_permission_is_checked_on_the_server_not_only_in_the_interface(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.depot.statistiques'))->assertOk();

        // Un admin à qui on retire une permission est refusé sur la route, même s'il connaît l'URL.
        $admin->roles()->first()->revokePermissionTo(Permissions::VOIR_STATISTIQUES);
        $this->actingAs($admin->fresh())->get(route('admin.depot.statistiques'))->assertForbidden();
    }

    public function test_profile_photo_can_be_uploaded_replaced_and_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name, 'email' => $user->email,
            'photo_profil' => UploadedFile::fake()->image('moi.jpg', 300, 300),
        ])->assertSessionHasNoErrors();
        $premiere = $user->fresh()->photo_profil;
        Storage::disk('public')->assertExists($premiere);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name, 'email' => $user->email,
            'photo_profil' => UploadedFile::fake()->image('nouvelle.png', 300, 300),
        ]);
        Storage::disk('public')->assertMissing($premiere);

        $this->actingAs($user)->patch('/profile', ['name' => $user->name, 'email' => $user->email, 'supprimer_photo' => '1']);
        $this->assertNull($user->fresh()->photo_profil);
    }

    public function test_profile_photo_must_be_a_small_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name, 'email' => $user->email,
            'photo_profil' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors(['photo_profil' => 'La photo de profil doit être une image.']);
    }

    public function test_verification_code_cannot_be_resent_more_than_once_a_minute(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('verification.send'))->assertSessionHasErrors('code');
        Notification::assertSentToTimes($user, CodeVerificationEmail::class, 1);

        $this->travel(61)->seconds();
        $this->actingAs($user)->post(route('verification.send'))->assertSessionHasNoErrors();
        Notification::assertSentToTimes($user, CodeVerificationEmail::class, 2);
    }

    public function test_forgot_password_is_limited_per_ip_address(): void
    {
        Notification::fake();

        foreach (range(1, 3) as $i) {
            $this->post('/forgot-password', ['email' => "personne{$i}@example.com"])->assertRedirect();
        }
        $this->post('/forgot-password', ['email' => 'personne4@example.com'])->assertStatus(429);
    }
}
