<?php

namespace Database\Seeders;

use App\Enums\Ateliers\StatutDemande;
use App\Enums\Role;
use App\Models\Atelier;
use App\Models\DemandeReparation;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationTestSeeder extends Seeder
{
    /**
     * Seeder pour tester le système de notifications.
     * 
     * Crée :
     * - Un compte admin
     * - Un compte particulier
     * - Un atelier
     * - Plusieurs demandes de réparation avec différents états
     * 
     * Usage: php artisan db:seed --class=NotificationTestSeeder
     */
    public function run(): void
    {
        $this->command->info('🔔 Création de données de test pour les notifications...');

        // Créer un admin
        $admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => Role::Admin,
            'email_verified_at' => now(),
        ]);
        $this->command->info("✅ Admin créé : {$admin->email} / password");

        // Créer un particulier
        $particulier = User::factory()->create([
            'name' => 'Particulier Test',
            'email' => 'particulier@test.com',
            'password' => bcrypt('password'),
            'role' => Role::Client,
            'email_verified_at' => now(),
        ]);
        $this->command->info("✅ Particulier créé : {$particulier->email} / password");

        // Créer un atelier
        $atelier = Atelier::factory()->create([
            'nom' => 'Atelier Test',
            'actif' => true,
        ]);
        $this->command->info("✅ Atelier créé : {$atelier->nom}");

        // Créer 3 demandes en attente (génèrent des notifications pour l'admin)
        $this->command->info("\n📝 Création de demandes en attente...");
        DemandeReparation::factory()->count(3)->create([
            'user_id' => $particulier->id,
            'atelier_id' => $atelier->id,
            'statut' => StatutDemande::EnAttente,
            'analyse_statut' => 'ok',
            'admin_notifie' => false, // Pour déclencher les notifications
        ]);
        $this->command->info("✅ 3 demandes en attente créées (notifications admin générées)");

        // Créer 1 demande avec erreur d'analyse (notification admin avec message d'erreur)
        $this->command->info("\n⚠️  Création d'une demande avec erreur d'analyse...");
        DemandeReparation::factory()->create([
            'user_id' => $particulier->id,
            'atelier_id' => $atelier->id,
            'statut' => StatutDemande::EnAttente,
            'analyse_statut' => 'erreur',
            'admin_notifie' => false,
            'diagnostic' => 'Analyse automatique indisponible. Cette demande sera traitée manuellement.',
        ]);
        $this->command->info("✅ 1 demande avec erreur d'analyse créée");

        // Créer 1 demande prioritaire (notification admin avec badge rouge)
        $this->command->info("\n🚨 Création d'une demande prioritaire...");
        $demandePrioritaire = DemandeReparation::factory()->create([
            'user_id' => $particulier->id,
            'atelier_id' => $atelier->id,
            'statut' => StatutDemande::EnAttente,
            'description' => 'Très gros trou dans le cuir, plusieurs déchirures importantes',
            'analyse_statut' => 'ok',
            'admin_notifie' => false,
        ]);
        $demandePrioritaire->diagnostiquer()->save();
        $this->command->info("✅ 1 demande prioritaire créée");

        // Créer des demandes avec différents statuts (pour tester les notifications particulier)
        $this->command->info("\n📋 Création de demandes avec différents statuts...");
        
        // Créer une demande et la marquer comme acceptée
        $demandeAcceptee = DemandeReparation::factory()->create([
            'user_id' => $particulier->id,
            'atelier_id' => $atelier->id,
            'statut' => StatutDemande::EnAttente,
        ]);
        $demandeAcceptee->statut = StatutDemande::Acceptee;
        $demandeAcceptee->save();
        $this->command->info("✅ 1 demande acceptée (notification particulier générée)");

        // Créer une demande et la marquer comme terminée
        $demandeTerminee = DemandeReparation::factory()->create([
            'user_id' => $particulier->id,
            'atelier_id' => $atelier->id,
            'statut' => StatutDemande::EnCours,
        ]);
        $demandeTerminee->statut = StatutDemande::Terminee;
        $demandeTerminee->save();
        $this->command->info("✅ 1 demande terminée (notification particulier générée)");

        // Résumé
        $this->command->info("\n" . str_repeat('=', 60));
        $this->command->info('🎉 Données de test créées avec succès !');
        $this->command->info(str_repeat('=', 60));
        $this->command->info("\n📊 Résumé :");
        $this->command->info("  - Comptes créés : 2 (1 admin + 1 particulier)");
        $this->command->info("  - Ateliers créés : 1");
        $this->command->info("  - Demandes créées : 7");
        $this->command->info("\n👤 Comptes de test :");
        $this->command->info("  Admin     : admin@test.com / password");
        $this->command->info("  Particulier : particulier@test.com / password");
        $this->command->info("\n🔔 Notifications générées :");
        $this->command->info("  - Admin : " . $admin->unreadNotifications->count() . " notifications non lues");
        $this->command->info("  - Particulier : " . $particulier->unreadNotifications->count() . " notifications non lues");
        $this->command->info("\n💡 Pour tester :");
        $this->command->info("  1. Connectez-vous avec admin@test.com");
        $this->command->info("  2. Cliquez sur la cloche dans le header");
        $this->command->info("  3. Vérifiez les notifications (dont 1 prioritaire)");
        $this->command->info("  4. Connectez-vous avec particulier@test.com");
        $this->command->info("  5. Vérifiez les notifications de changement de statut");
        $this->command->info(str_repeat('=', 60) . "\n");
    }
}
