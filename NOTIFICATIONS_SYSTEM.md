# Système de Notifications pour les Demandes de Réparation

## Vue d'ensemble

Ce document décrit le système de notifications implémenté pour les demandes de réparation dans l'application TexTileCycle.

## Fonctionnalités implémentées

### 1. Notifications Admin

**Déclenchement** : Quand `analyse_statut` passe à "ok" ou "erreur"

**Contenu** :
- **Titre** : "Nouvelle demande de réparation"
- **Message** : 
  - Si succès : Résumé avec type_dommage, gravité, priorité
  - Si erreur : "Analyse IA indisponible, à traiter manuellement"
- **Lien** : Vers la page de détail de la demande
- **Badge priorité haute** : Affiché en rouge si la demande a une priorité haute

**Anti-doublon** : Une seule notification par demande, même si l'analyse est relancée (grâce au champ `admin_notifie`)

### 2. Notifications Particulier

**Déclenchement** : Quand l'admin change le statut vers :
- Acceptée
- Refusée
- Terminée

**Contenu** :
- **Titre** : "Demande de réparation [statut]"
- **Message** : "Votre demande de réparation « [titre] » a été [statut]"
- **Lien** : Vers la liste des demandes du particulier

### 3. Cloche de notifications (Header)

**Présente dans** :
- Header du back office (admin)
- Header du front office (particuliers connectés)

**Fonctionnalités** :
- Icône cloche avec compteur de notifications non lues
- Compteur masqué si 0 notifications
- Affichage "9+" au-delà de 9 notifications
- Clic sur la cloche : ouvre un dropdown avec les dernières notifications
- Clic sur une notification : la marque comme lue et redirige vers la demande
- Bouton "Tout marquer comme lu"
- Rafraîchissement automatique du compteur toutes les 30 secondes
- Chaque utilisateur ne voit que ses propres notifications

**Badges visuels** :
- Point bleu pour les notifications non lues
- Badge rouge "Priorité haute" pour les demandes urgentes
- Bordure rouge à gauche pour les notifications prioritaires

## Structure technique

### Migrations

**Fichier** : `database/migrations/2026_10_20_130553_add_analyse_statut_to_demandes_reparation_table.php`

Champs ajoutés à `demandes_reparation` :
- `analyse_statut` : enum('ok', 'erreur'), nullable - Statut de l'analyse IA
- `admin_notifie` : boolean, default false - Flag anti-doublon pour les notifications admin

### Observer

**Fichier** : `app/Observers/DemandeReparationObserver.php`

**Événements surveillés** :
- `created` : Notifie les admins si l'analyse est déjà terminée
- `updated` : 
  - Notifie les admins si `analyse_statut` change
  - Notifie le particulier si `statut` change vers acceptée/refusée/terminée

**Méthode clé** : `notifierAdminSiAnalyseTerminee()`
- Vérifie que `analyse_statut` n'est pas null
- Vérifie que `admin_notifie` est false (anti-doublon)
- Récupère tous les admins
- Envoie la notification à chaque admin
- Marque `admin_notifie` à true avec `saveQuietly()` pour éviter une boucle

### Notifications

**Fichiers** :
- `app/Notifications/NouvelleDemandeReparation.php`
- `app/Notifications/StatutDemandeReparationChange.php`

Les notifications utilisent le canal `database` de Laravel.

### Contrôleur

**Fichier** : `app/Http/Controllers/NotificationController.php`

**Routes** :
- `GET /notifications` : Liste des 10 dernières notifications
- `GET /notifications/count` : Compteur de notifications non lues
- `POST /notifications/{id}/read` : Marquer une notification comme lue
- `POST /notifications/read-all` : Marquer toutes les notifications comme lues

**Sécurité** : 
- Toutes les routes sont protégées par le middleware `auth`
- Un utilisateur ne peut accéder qu'à ses propres notifications

### Composant Vue

**Fichier** : `resources/views/components/notifications-bell.blade.php`

**Utilise** :
- Alpine.js pour la réactivité
- CSS custom pour le style
- Fetch API pour charger les notifications
- CSRF token pour les requêtes POST

**Paramètres personnalisables** :
- `variant` : Style (back-office ou front-office)
- `iconClass` : Classes CSS pour l'icône
- `badgeClass` : Classes CSS pour le badge compteur
- `dropdownClass` : Classes CSS pour le dropdown

### Modèle

**Modifications dans** `app/Models/DemandeReparation.php` :

La méthode `diagnostiquer()` a été modifiée pour :
- Entourer l'analyse d'un try/catch
- Définir `analyse_statut` à 'ok' en cas de succès
- Définir `analyse_statut` à 'erreur' en cas d'échec
- Fournir un diagnostic par défaut en cas d'erreur

## Tests

**Fichier** : `tests/Feature/DemandeReparationNotificationTest.php`

**Tests implémentés** (9 tests, 15 assertions) :
1. ✅ Une seule notification admin par demande (anti-doublon)
2. ✅ Admin notifié quand analyse = "ok"
3. ✅ Admin notifié quand analyse = "erreur"
4. ✅ Particulier notifié quand statut = "acceptée"
5. ✅ Particulier notifié quand statut = "refusée"
6. ✅ Particulier notifié quand statut = "terminée"
7. ✅ Le compteur diminue quand une notification est lue
8. ✅ Un utilisateur ne peut pas lire les notifications d'un autre
9. ✅ "Tout marquer comme lu" fonctionne correctement

## Utilisation

### Pour les développeurs

**Ajouter la cloche dans un layout** :

```blade
{{-- Back office --}}
<x-notifications-bell 
  variant="back-office" 
  iconClass="v-icon notranslate v-theme--light v-icon--size-default"
  badgeClass="badge-back"
  dropdownClass="dropdown-back"
/>

{{-- Front office --}}
<x-notifications-bell 
  variant="front-office"
  iconClass="bx"
  badgeClass="badge-front"
  dropdownClass="dropdown-front"
/>
```

**Déclencher une notification admin manuellement** :

```php
use App\Notifications\NouvelleDemandeReparation;
use App\Models\User;
use App\Enums\Role;

$admins = User::where('role', Role::Admin)->get();
foreach ($admins as $admin) {
    $admin->notify(new NouvelleDemandeReparation($demande, $analyseErreur));
}
```

**Déclencher une notification particulier manuellement** :

```php
use App\Notifications\StatutDemandeReparationChange;

$demande->user->notify(
    new StatutDemandeReparationChange($demande, $nouveauStatut)
);
```

### Pour les testeurs

1. **Créer une demande de réparation** → Les admins reçoivent une notification
2. **Changer le statut d'une demande** (acceptée/refusée/terminée) → Le particulier reçoit une notification
3. **Cliquer sur la cloche** → Voir la liste des notifications
4. **Cliquer sur une notification** → Redirection + marquée comme lue
5. **Bouton "Tout marquer comme lu"** → Toutes les notifications sont marquées comme lues

## Dépendances

- Laravel 11.x (système de notifications intégré)
- Alpine.js 3.x (réactivité côté client)
- BoxIcons (icônes)
- Bootstrap 5.x (styles front office)

## Notes importantes

1. **Pas de temps réel** : Le compteur est rafraîchi toutes les 30 secondes côté client, pas via WebSocket/Pusher
2. **Table notifications** : Utilise la table Laravel standard avec le champ `read_at`
3. **Politique de rétention** : Les notifications ne sont pas automatiquement supprimées (à implémenter si nécessaire)
4. **Performance** : Avec un grand nombre de notifications, envisager une pagination dans le dropdown

## Améliorations futures possibles

- [ ] Pagination dans le dropdown de notifications
- [ ] Suppression manuelle de notifications
- [ ] Nettoyage automatique des anciennes notifications (>30 jours)
- [ ] Notifications par email (optionnel)
- [ ] Notifications push (navigateur)
- [ ] WebSocket pour mise à jour temps réel
- [ ] Filtrage des notifications par type
- [ ] Son lors de l'arrivée d'une nouvelle notification
