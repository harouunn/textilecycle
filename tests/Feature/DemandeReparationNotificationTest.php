<?php

namespace Tests\Feature;

use App\Enums\Ateliers\StatutDemande;
use App\Enums\Ateliers\TypeReparation;
use App\Enums\Ateliers\TypeVetement;
use App\Enums\Role;
use App\Jobs\AnalyserDemandeJob;
use App\Models\Atelier;
use App\Models\DemandeReparation;
use App\Models\User;
use App\Notifications\NouvelleDemandeReparation;
use App\Notifications\StatutDemandeReparationChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemandeReparationNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Les tests déclenchent eux-mêmes la fin d'analyse (analyse_statut). En test, la file d'attente est
     * synchrone : sans simulation, le job lancé à la création analyserait aussitôt la demande (et
     * appellerait les vraies IA), d'où une notification de trop.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /**
     * Test qu'une seule notification admin est envoyée par demande, même après relance de l'analyse.
     */
    public function test_une_seule_notification_admin_par_demande(): void
    {
        // Créer un admin et un particulier
        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::Client]);
        $atelier = Atelier::factory()->create();

        // Créer une demande sans analyse
        $demande = DemandeReparation::factory()->create([
            'user_id' => $user->id,
            'atelier_id' => $atelier->id,
            'analyse_statut' => null,
            'admin_notifie' => false,
        ]);

        // Activer le fake APRÈS la création de la demande
        Notification::fake();

        // Simuler la fin de l'analyse (déclenche l'Observer via update())
        $demande->update(['analyse_statut' => 'ok']);

        // L'admin devrait recevoir une notification
        Notification::assertSentTo($admin, NouvelleDemandeReparation::class);

        // Réinitialiser les notifications
        Notification::fake();

        // Relancer l'analyse (admin_notifie est déjà true)
        $demande->update(['analyse_statut' => 'ok']);

        // L'admin ne devrait PAS recevoir une autre notification
        Notification::assertNothingSentTo($admin);
    }

    /**
     * Test que l'admin est notifié quand analyse_statut passe à "ok".
     */
    public function test_admin_notifie_quand_analyse_ok(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::Client]);
        $atelier = Atelier::factory()->create();

        // Créer la demande sans analyse
        $demande = DemandeReparation::factory()->create([
            'user_id' => $user->id,
            'atelier_id' => $atelier->id,
            'analyse_statut' => null,
            'admin_notifie' => false,
        ]);

        // Activer le fake APRÈS la création
        Notification::fake();

        // Mettre à jour avec analyse ok
        $demande->update(['analyse_statut' => 'ok']);

        Notification::assertSentTo($admin, NouvelleDemandeReparation::class, function ($notification) use ($demande) {
            return $notification->demande->id === $demande->id && ! $notification->analyseErreur;
        });
    }

    /**
     * Test que l'admin est notifié quand analyse_statut passe à "erreur".
     */
    public function test_admin_notifie_quand_analyse_erreur(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::Client]);
        $atelier = Atelier::factory()->create();

        // Créer une demande sans analyse d'abord, en forçant les champs nécessaires
        $demande = new DemandeReparation([
            'user_id' => $user->id,
            'atelier_id' => $atelier->id,
            'titre' => 'Test demande',
            'type_vetement' => TypeVetement::Haut,
            'description' => 'Description test',
            'reparations' => [TypeReparation::AExpertiser],
            'cout_estime' => 0,
            'delai_estime_jours' => 0,
            'diagnostic' => 'Test',
            'analyse_statut' => null,
            'admin_notifie' => false,
        ]);
        $demande->save();

        // Réinitialiser les notifications
        Notification::fake();

        // Mettre à jour avec une erreur d'analyse
        $demande->update(['analyse_statut' => 'erreur']);

        Notification::assertSentTo($admin, NouvelleDemandeReparation::class, function ($notification) use ($demande) {
            return $notification->demande->id === $demande->id && $notification->analyseErreur;
        });
    }

    /**
     * Test que le particulier est notifié quand le statut change vers "acceptée".
     */
    public function test_particulier_notifie_quand_statut_acceptee(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => Role::Client]);
        $atelier = Atelier::factory()->create();

        $demande = DemandeReparation::factory()->create([
            'user_id' => $user->id,
            'atelier_id' => $atelier->id,
            'statut' => StatutDemande::EnAttente,
        ]);

        // Réinitialiser pour ignorer les notifications de création
        Notification::fake();

        // Changer le statut vers "acceptée"
        $demande->update(['statut' => StatutDemande::Acceptee]);

        Notification::assertSentTo($user, StatutDemandeReparationChange::class, function ($notification) use ($demande) {
            return $notification->demande->id === $demande->id
                && $notification->nouveauStatut === StatutDemande::Acceptee;
        });
    }

    /**
     * Test que le particulier est notifié quand le statut change vers "refusée".
     */
    public function test_particulier_notifie_quand_statut_refusee(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => Role::Client]);
        $atelier = Atelier::factory()->create();

        $demande = DemandeReparation::factory()->create([
            'user_id' => $user->id,
            'atelier_id' => $atelier->id,
            'statut' => StatutDemande::EnAttente,
        ]);

        Notification::fake();

        $demande->update(['statut' => StatutDemande::Refusee]);

        Notification::assertSentTo($user, StatutDemandeReparationChange::class, function ($notification) use ($demande) {
            return $notification->demande->id === $demande->id
                && $notification->nouveauStatut === StatutDemande::Refusee;
        });
    }

    /**
     * Test que le particulier est notifié quand le statut change vers "terminée".
     */
    public function test_particulier_notifie_quand_statut_terminee(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => Role::Client]);
        $atelier = Atelier::factory()->create();

        $demande = DemandeReparation::factory()->create([
            'user_id' => $user->id,
            'atelier_id' => $atelier->id,
            'statut' => StatutDemande::EnCours,
        ]);

        Notification::fake();

        $demande->update(['statut' => StatutDemande::Terminee]);

        Notification::assertSentTo($user, StatutDemandeReparationChange::class, function ($notification) use ($demande) {
            return $notification->demande->id === $demande->id
                && $notification->nouveauStatut === StatutDemande::Terminee;
        });
    }

    /**
     * Test que le compteur de notifications non lues diminue quand une notification est lue.
     */
    public function test_compteur_diminue_quand_notification_lue(): void
    {
        $user = User::factory()->create(['role' => Role::Admin]);
        $atelier = Atelier::factory()->create();

        $this->actingAs($user);

        // Créer 3 demandes sans analyse
        $demandes = DemandeReparation::factory()->count(3)->create([
            'atelier_id' => $atelier->id,
            'analyse_statut' => null,
            'admin_notifie' => false,
        ]);

        // Déclencher l'analyse pour chaque demande
        foreach ($demandes as $demande) {
            $demande->update(['analyse_statut' => 'ok']);
        }

        // Rafraîchir l'utilisateur pour obtenir les notifications
        $user->refresh();

        // Vérifier le compteur initial
        $response = $this->getJson(route('notifications.count'));
        $response->assertOk();
        $response->assertJson(['count' => 3]);

        // Marquer une notification comme lue
        $notification = $user->unreadNotifications->first();
        $response = $this->postJson(route('notifications.read', $notification->id));
        $response->assertOk();

        // Vérifier que le compteur a diminué
        $response = $this->getJson(route('notifications.count'));
        $response->assertJson(['count' => 2]);
    }

    /**
     * Test qu'un utilisateur ne peut pas lire les notifications d'un autre.
     */
    public function test_utilisateur_ne_peut_pas_lire_notifications_autre_utilisateur(): void
    {
        $user1 = User::factory()->create(['role' => Role::Admin]);
        $user2 = User::factory()->create(['role' => Role::Admin]);
        $atelier = Atelier::factory()->create();

        // user1 est connecté d'abord pour créer la notification
        $this->actingAs($user1);

        // Créer une notification pour user1
        $demande = DemandeReparation::factory()->create([
            'atelier_id' => $atelier->id,
            'analyse_statut' => null,
            'admin_notifie' => false,
        ]);

        $demande->update(['analyse_statut' => 'ok']);

        // Rafraîchir pour obtenir les notifications
        $user1->refresh();

        // Prendre la notification de user1
        $notification = $user1->unreadNotifications->first();
        $this->assertNotNull($notification, 'User1 devrait avoir au moins une notification');

        // user2 essaie d'accéder à la notification de user1
        $this->actingAs($user2);
        $response = $this->postJson(route('notifications.read', $notification->id));

        // Devrait échouer (404)
        $response->assertNotFound();
    }

    /**
     * Test que "Tout marquer comme lu" fonctionne correctement.
     */
    public function test_tout_marquer_comme_lu(): void
    {
        $user = User::factory()->create(['role' => Role::Admin]);
        $atelier = Atelier::factory()->create();

        $this->actingAs($user);

        // Créer plusieurs demandes
        $demandes = DemandeReparation::factory()->count(5)->create([
            'atelier_id' => $atelier->id,
            'analyse_statut' => null,
            'admin_notifie' => false,
        ]);

        // Déclencher l'analyse pour chaque demande
        foreach ($demandes as $demande) {
            $demande->update(['analyse_statut' => 'ok']);
        }

        // Rafraîchir l'utilisateur pour obtenir les notifications
        $user->refresh();

        // Vérifier qu'il y a 5 notifications non lues
        $this->assertEquals(5, $user->unreadNotifications->count());

        // Tout marquer comme lu
        $response = $this->postJson(route('notifications.readAll'));
        $response->assertOk();

        // Vérifier qu'il n'y a plus de notifications non lues
        $user->refresh();
        $this->assertEquals(0, $user->unreadNotifications->count());
    }

    /**
     * Test qu'aucune notification n'est envoyée à la création, seulement quand analyse_statut change.
     */
    public function test_pas_de_notification_a_la_creation_seulement_apres_analyse(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::Client]);
        $atelier = Atelier::factory()->create();

        Notification::fake();

        // Créer une demande sans analyse_statut
        $demande = DemandeReparation::factory()->create([
            'user_id' => $user->id,
            'atelier_id' => $atelier->id,
            'analyse_statut' => null,
            'admin_notifie' => false,
        ]);

        // Aucune notification ne doit être envoyée à la création
        Notification::assertNothingSentTo($admin);

        // Simuler la fin de l'analyse
        $demande->update(['analyse_statut' => 'ok']);

        // Maintenant l'admin doit recevoir une notification
        Notification::assertSentTo($admin, NouvelleDemandeReparation::class);
    }

    /**
     * Test qu'une seule notification admin même après relance.
     */
    public function test_pas_de_doublon_apres_relance(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::Client]);
        $atelier = Atelier::factory()->create();

        // Créer et analyser
        $demande = DemandeReparation::factory()->create([
            'user_id' => $user->id,
            'atelier_id' => $atelier->id,
            'analyse_statut' => null,
            'admin_notifie' => false,
        ]);

        Notification::fake();

        $demande->update(['analyse_statut' => 'ok']);

        Notification::assertSentToTimes($admin, NouvelleDemandeReparation::class, 1);

        // Réinitialiser
        Notification::fake();

        // Relancer (simulate reanalyse)
        $demande->update(['analyse_statut' => 'ok']);

        // Pas de nouvelle notification car admin_notifie = true
        Notification::assertNothingSentTo($admin);
    }

    /**
     * Test que les photos sont enregistrées avant que le job s'exécute.
     */
    public function test_photos_enregistrees_avant_job_execution(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::Client]);
        $atelier = Atelier::factory()->create(['actif' => true]);

        // Créer une image de test
        $fakeJpeg = str_repeat("\xFF\xD8\xFF\xE0", 20); // Simule un JPEG

        $uploadedFile = UploadedFile::fake()->createWithContent('test.jpg', $fakeJpeg);

        // Mocker Http pour l'analyse
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{"type_dommage":"trou","gravite":"elevee","temps_estime_min":45}']]],
                ]],
            ]),
            '*/api/generate' => Http::response([
                'response' => '{"resume":"Test","atelier_recommande_id":null,"priorite":"normale"}',
            ]),
        ]);

        config(['ia.gemini.api_key' => 'test_key']);

        // Créer la demande avec photo
        $this->actingAs($user)
            ->post(route('ateliers.demandes.store', $atelier), [
                'titre' => 'Jean troué',
                'type_vetement' => 'haut',
                'description' => 'Mon jean a un trou',
                'photo' => $uploadedFile,
            ])
            ->assertRedirect(route('ateliers.demandes.index'));

        // Vérifier que la demande a été créée
        $demande = DemandeReparation::latest()->first();
        $this->assertNotNull($demande);
        $this->assertNotNull($demande->photo);

        // Vérifier que la photo existe dans le storage
        Storage::disk('public')->assertExists($demande->photo);

        // Le job d'analyse a été mis en file ; on l'exécute comme le ferait le worker.
        Queue::assertPushed(AnalyserDemandeJob::class);
        app()->call([new AnalyserDemandeJob($demande), 'handle']);
        $demande->refresh();

        // Vérifier que l'analyse a été effectuée (preuve que le job a pu accéder à la photo)
        $this->assertNotNull($demande->analysed_at);
        $this->assertEquals('ok', $demande->analyse_statut);
    }
}
