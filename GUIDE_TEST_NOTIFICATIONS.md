# Guide de Test - Système de Notifications

Ce guide vous explique comment tester le système de notifications pour les demandes de réparation.

## Prérequis

1. Base de données configurée et migrée
2. Serveur de développement lancé (`php artisan serve`)

## Option 1 : Test Automatique avec le Seeder

### Étape 1 : Lancer le seeder

```bash
php artisan db:seed --class=NotificationTestSeeder
```

Ce seeder va créer :
- ✅ **Admin** : admin@test.com / password
- ✅ **Particulier** : particulier@test.com / password
- ✅ **1 atelier** actif
- ✅ **7 demandes** de réparation avec différents statuts
- ✅ **Plusieurs notifications** pour tester tous les cas

### Étape 2 : Test côté Admin

1. **Connexion**
   - Allez sur `/login`
   - Email : `admin@test.com`
   - Password : `password`

2. **Vérifier les notifications**
   - Regardez la cloche dans le header (en haut à droite)
   - Un badge rouge devrait afficher le nombre de notifications (5+)
   - Cliquez sur la cloche

3. **Vérifier le contenu**
   - Vous devriez voir plusieurs notifications
   - Au moins 1 notification avec badge "Priorité haute" (rouge)
   - Au moins 1 notification "Analyse IA indisponible"

4. **Tester les actions**
   - Cliquez sur une notification → doit vous rediriger vers la demande
   - La notification cliquée doit être marquée comme lue (disparition du point bleu)
   - Le compteur doit diminuer
   - Cliquez sur "Tout marquer comme lu" → toutes les notifications deviennent lues

### Étape 3 : Test côté Particulier

1. **Déconnexion puis connexion**
   - Déconnectez-vous de l'admin
   - Email : `particulier@test.com`
   - Password : `password`

2. **Vérifier les notifications**
   - La cloche devrait afficher 2+ notifications
   - Cliquez sur la cloche

3. **Vérifier le contenu**
   - Notification "Demande acceptée"
   - Notification "Demande terminée"
   - Cliquer sur une notification → redirection vers vos demandes

## Option 2 : Test Manuel Complet

### Étape 1 : Créer les comptes

```bash
# Dans tinker
php artisan tinker

# Créer un admin
$admin = User::factory()->create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'role' => 'admin',
    'email_verified_at' => now()
]);

# Créer un particulier
$user = User::factory()->create([
    'name' => 'Particulier',
    'email' => 'user@example.com',
    'role' => 'client',
    'email_verified_at' => now()
]);
```

### Étape 2 : Créer un atelier

1. Connectez-vous avec l'admin
2. Allez dans "Ateliers" → "Créer un atelier"
3. Remplissez les informations et activez l'atelier

### Étape 3 : Test notification admin (nouvelle demande)

1. **Déconnectez-vous et reconnectez-vous comme particulier**
2. **Créer une demande de réparation**
   - Allez sur la page "Ateliers"
   - Cliquez sur "Demander une réparation"
   - Remplissez le formulaire
   - Soumettez

3. **Reconnectez-vous comme admin**
   - Vérifiez la cloche → 1 notification
   - Ouvrez le dropdown → "Nouvelle demande de réparation"
   - Vérifiez le badge priorité si le dommage est important

### Étape 4 : Test notification particulier (changement de statut)

1. **En tant qu'admin**
   - Allez dans "Demandes de réparation"
   - Cliquez sur la demande créée précédemment
   - Changez le statut vers "Acceptée"
   - Sauvegardez

2. **Reconnectez-vous comme particulier**
   - Vérifiez la cloche → 1 notification
   - Message : "Votre demande a été acceptée"

3. **Testez les autres statuts**
   - Répétez avec "Refusée" et "Terminée"
   - Chaque changement génère une notification

### Étape 5 : Test anti-doublon

1. **En tant qu'admin**
   - Modifiez une demande (changez la description)
   - Sauvegardez
   - Vérifiez les notifications → pas de nouvelle notification admin (déjà notifié)

### Étape 6 : Test erreur d'analyse

Pour simuler une erreur d'analyse IA :

```bash
php artisan tinker

$demande = DemandeReparation::first();
$demande->analyse_statut = 'erreur';
$demande->admin_notifie = false;
$demande->save();
```

Vérifiez que l'admin reçoit une notification "Analyse IA indisponible".

## Scénarios de Test à Vérifier

### ✅ Notifications Admin

| Scénario | Résultat attendu |
|----------|------------------|
| Nouvelle demande créée | ✅ Admin reçoit notification |
| Demande avec analyse OK | ✅ Notification avec détails (type, gravité, priorité) |
| Demande avec analyse erreur | ✅ Notification "Analyse IA indisponible" |
| Demande priorité haute | ✅ Badge rouge "Priorité haute" |
| Modifier une demande déjà notifiée | ✅ Pas de nouvelle notification |
| Relancer l'analyse | ✅ Pas de doublon |

### ✅ Notifications Particulier

| Scénario | Résultat attendu |
|----------|------------------|
| Admin accepte la demande | ✅ Notification "acceptée" |
| Admin refuse la demande | ✅ Notification "refusée" |
| Admin termine la demande | ✅ Notification "terminée" |
| Admin met en cours | ❌ Pas de notification |
| Clic sur notification | ✅ Redirection + marquée comme lue |

### ✅ Interface Cloche

| Scénario | Résultat attendu |
|----------|------------------|
| 0 notification | ✅ Compteur masqué |
| 1-9 notifications | ✅ Compteur affiche le nombre exact |
| 10+ notifications | ✅ Compteur affiche "9+" |
| Clic sur cloche | ✅ Dropdown s'ouvre |
| Clic outside | ✅ Dropdown se ferme |
| Clic sur notification | ✅ Marque comme lue + redirige |
| "Tout marquer comme lu" | ✅ Toutes marquées comme lues + compteur à 0 |
| Rafraîchissement auto | ✅ Compteur mis à jour toutes les 30s |

### ✅ Sécurité

| Scénario | Résultat attendu |
|----------|------------------|
| User1 essaie de lire notification de User2 | ✅ Erreur 404 |
| Accès sans authentification | ✅ Redirection login |
| Chaque user voit ses notifications uniquement | ✅ Isolation correcte |

## Dépannage

### La cloche n'apparaît pas

1. Vérifiez que Alpine.js est chargé :
   ```bash
   # Dans la console du navigateur
   Alpine.version
   ```

2. Vérifiez la console pour des erreurs JavaScript

3. Vérifiez que l'utilisateur est connecté :
   ```blade
   @auth
     <x-notifications-bell />
   @endauth
   ```

### Le compteur ne se met pas à jour

1. Vérifiez les routes dans `routes/web.php`
2. Vérifiez que le CSRF token est présent :
   ```html
   <meta name="csrf-token" content="{{ csrf_token() }}">
   ```
3. Regardez la console réseau pour voir si les requêtes passent

### Les notifications ne sont pas générées

1. Vérifiez que l'Observer est bien enregistré dans `AppServiceProvider`
2. Vérifiez les logs Laravel : `storage/logs/laravel.log`
3. Vérifiez la table `notifications` en base de données

### Erreur "NotificationController does not exist"

Vérifiez que le contrôleur est importé dans `routes/web.php` :
```php
use App\Http\Controllers\NotificationController;
```

## Nettoyage

Pour nettoyer les données de test :

```bash
# Supprimer toutes les notifications
php artisan tinker
>>> DB::table('notifications')->truncate();

# Ou réinitialiser complètement la base
php artisan migrate:fresh
```

## Tests Automatisés

Pour exécuter les tests :

```bash
# Tous les tests de notifications
php artisan test --filter DemandeReparationNotificationTest

# Un test spécifique
php artisan test --filter test_admin_notifie_quand_analyse_ok
```

## Checklist Finale

Avant de valider l'implémentation, vérifiez que :

- [ ] La cloche apparaît dans le header (back et front)
- [ ] Le compteur affiche le bon nombre
- [ ] Les notifications admin sont générées à la création
- [ ] Les notifications particulier sont générées au changement de statut
- [ ] Pas de doublons de notifications
- [ ] Les notifications prioritaires ont le badge rouge
- [ ] Clic sur notification = marquée lue + redirection
- [ ] "Tout marquer comme lu" fonctionne
- [ ] Un utilisateur ne voit que ses notifications
- [ ] Le rafraîchissement automatique fonctionne
- [ ] Tous les tests unitaires passent

## Support

En cas de problème, vérifiez :
1. Les logs : `storage/logs/laravel.log`
2. La console navigateur (F12)
3. L'onglet Réseau dans les DevTools
4. La table `notifications` en base de données

Pour plus d'informations, consultez `NOTIFICATIONS_SYSTEM.md`.
