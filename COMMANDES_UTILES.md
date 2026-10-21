# Commandes Utiles - Système de Notifications

## 🚀 Installation et Migration

```bash
# Exécuter toutes les migrations
php artisan migrate

# Exécuter uniquement la migration des notifications
php artisan migrate --path=database/migrations/2026_10_20_130553_add_analyse_statut_to_demandes_reparation_table.php

# Rollback de la dernière migration
php artisan migrate:rollback

# Reset complet de la base (ATTENTION: supprime toutes les données)
php artisan migrate:fresh
```

## 🧪 Tests

```bash
# Exécuter tous les tests de notifications
php artisan test --filter DemandeReparationNotificationTest

# Exécuter un test spécifique
php artisan test --filter test_admin_notifie_quand_analyse_ok

# Exécuter tous les tests avec couverture
php artisan test

# Mode verbose (plus de détails)
php artisan test --filter DemandeReparationNotificationTest -v
```

## 🌱 Seeding (Données de Test)

```bash
# Générer les données de test complètes
php artisan db:seed --class=NotificationTestSeeder

# Si besoin de recommencer à zéro
php artisan migrate:fresh --seed --seeder=NotificationTestSeeder
```

## 🔍 Inspection de la Base de Données

```bash
# Ouvrir tinker pour inspecter la BDD
php artisan tinker
```

### Dans tinker :

```php
// Compter les notifications
DB::table('notifications')->count();

// Voir toutes les notifications
DB::table('notifications')->get();

// Notifications d'un utilisateur spécifique
$user = User::find(1);
$user->notifications;
$user->unreadNotifications;

// Nombre de notifications non lues par utilisateur
User::withCount('unreadNotifications')->get();

// Supprimer toutes les notifications
DB::table('notifications')->truncate();

// Créer manuellement une demande qui génère une notification
$atelier = Atelier::first();
$user = User::where('role', 'client')->first();
$demande = DemandeReparation::factory()->create([
    'user_id' => $user->id,
    'atelier_id' => $atelier->id,
    'analyse_statut' => 'ok',
    'admin_notifie' => false
]);

// Voir les demandes avec leur statut de notification
DemandeReparation::select('id', 'titre', 'analyse_statut', 'admin_notifie')->get();
```

## 🎨 Serveur de Développement

```bash
# Lancer le serveur Laravel
php artisan serve

# Lancer sur un port spécifique
php artisan serve --port=8080

# Lancer accessible depuis le réseau
php artisan serve --host=0.0.0.0
```

## 🧹 Nettoyage

```bash
# Supprimer toutes les notifications via tinker
php artisan tinker
>>> DB::table('notifications')->truncate();
>>> exit

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Tout clear d'un coup
php artisan optimize:clear
```

## 🔧 Maintenance

```bash
# Voir les routes liées aux notifications
php artisan route:list --name=notifications

# Voir les observers enregistrés
php artisan tinker
>>> Event::getListeners('eloquent.created: App\Models\DemandeReparation')

# Régénérer l'autoload
composer dump-autoload
```

## 📊 Debug et Logs

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Sur Windows (PowerShell)
Get-Content storage/logs/laravel.log -Wait -Tail 50

# Vider les logs
echo "" > storage/logs/laravel.log
```

## 🛠️ Génération de Code

```bash
# Créer une nouvelle notification
php artisan make:notification NomDeLaNotification

# Créer un observer
php artisan make:observer NomObserver --model=NomModele

# Créer un seeder
php artisan make:seeder NomSeeder

# Créer un test
php artisan make:test NomTest
```

## 🔐 Utilisateurs de Test

```php
# Dans tinker
php artisan tinker

# Créer un admin
User::factory()->create([
    'name' => 'Admin',
    'email' => 'admin@test.com',
    'password' => bcrypt('password'),
    'role' => 'admin',
    'email_verified_at' => now()
]);

# Créer un particulier
User::factory()->create([
    'name' => 'User',
    'email' => 'user@test.com',
    'password' => bcrypt('password'),
    'role' => 'client',
    'email_verified_at' => now()
]);

# Lister tous les admins
User::where('role', 'admin')->get();

# Changer le rôle d'un utilisateur
$user = User::find(1);
$user->role = 'admin';
$user->save();
```

## 📱 Test Manuel

```bash
# 1. Générer les données
php artisan db:seed --class=NotificationTestSeeder

# 2. Lancer le serveur
php artisan serve

# 3. Ouvrir dans le navigateur
# http://localhost:8000/login

# 4. Se connecter avec :
# admin@test.com / password
# OU
# particulier@test.com / password
```

## 🐛 Dépannage

```bash
# Si erreur "Class not found"
composer dump-autoload
php artisan config:clear

# Si erreur de migration
php artisan migrate:refresh

# Si erreur de cache
php artisan optimize:clear

# Si Alpine.js ne fonctionne pas
# Vérifier dans la console navigateur :
# F12 → Console → Taper : Alpine.version

# Si les routes ne fonctionnent pas
php artisan route:list
php artisan route:cache
php artisan route:clear

# Si problème de permissions (Linux/Mac)
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## 📈 Performance

```bash
# Optimiser pour la production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Annuler les optimisations (développement)
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 🗄️ Base de Données

```bash
# Exporter la structure
mysqldump -u root -p nom_base --no-data > structure.sql

# Exporter les données
mysqldump -u root -p nom_base > dump.sql

# Importer
mysql -u root -p nom_base < dump.sql

# Avec SQLite (tests)
sqlite3 database/database.sqlite ".dump" > backup.sql
```

## 🔄 Git (Commandes Recommandées)

```bash
# Créer une branche pour les notifications
git checkout -b feature/notifications-system

# Ajouter tous les fichiers
git add .

# Commit avec message descriptif
git commit -m "feat: Système de notifications pour les demandes de réparation

- Ajout migration analyse_statut et admin_notifie
- Création Observer DemandeReparationObserver
- Composant notifications-bell avec Alpine.js
- 9 tests unitaires (100% passants)
- Documentation complète
- Seeder de test"

# Push de la branche
git push origin feature/notifications-system
```

## 📋 Checklist de Déploiement

```bash
# 1. Tests
php artisan test

# 2. Optimisation
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Migration en production
php artisan migrate --force

# 4. Vérification
php artisan route:list | grep notifications

# 5. Monitoring des logs
tail -f storage/logs/laravel.log
```

## 🎯 Commandes Rapides

```bash
# Reset + seed + test (développement)
php artisan migrate:fresh && php artisan db:seed --class=NotificationTestSeeder && php artisan test --filter DemandeReparationNotificationTest

# Clear all + serve
php artisan optimize:clear && php artisan serve

# Full refresh
php artisan migrate:fresh --seed && php artisan optimize:clear
```

## 💡 Astuces

```bash
# Voir une migration sans l'exécuter
php artisan migrate --pretend

# Voir le SQL d'une query dans tinker
DB::enableQueryLog();
// ... votre code ...
dd(DB::getQueryLog());

# Compter rapidement les notifications
php artisan tinker
>>> "Admin: " . User::where('role', 'admin')->first()->unreadNotifications->count()
>>> "Particulier: " . User::where('role', 'client')->first()->unreadNotifications->count()
```

---

**Tip** : Ajoutez ces commandes à un fichier `.bash_aliases` ou créez des alias dans votre `~/.bashrc` pour les utiliser plus rapidement !
