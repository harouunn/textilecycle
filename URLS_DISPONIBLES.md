# URLs disponibles après connexion

## En tant qu'ADMIN (Msekni Aziz)

### Pages principales
- **Page d'accueil** : http://localhost:8000/
- **Dashboard admin** : http://localhost:8000/admin
- **Profil** : http://localhost:8000/profile

### Notifications (Interface Web)
- **Liste des notifications** : http://localhost:8000/notifications
- La cloche de notifications apparaît dans le menu du site

### APIs JSON (pour AJAX, pas pour navigation)
- `/notifications/count` → Retourne `{"count": 0}` (nombre de notifications non lues)
- Ces URLs sont appelées automatiquement par JavaScript, pas directement

### Modules disponibles (Admin)
- **Dépôt** : http://localhost:8000/admin/depot
- **Ateliers** : http://localhost:8000/admin/ateliers
- **Upcycling** : http://localhost:8000/admin/upcycling
- **Dons** : http://localhost:8000/admin/dons

## Modules Front Office (Client)
- **Dépôt** : http://localhost:8000/depot
- **Ateliers** : http://localhost:8000/ateliers
- **Upcycling** : http://localhost:8000/upcycling
- **Dons** : http://localhost:8000/dons

## 🚀 Accès rapide recommandé

Après connexion, allez directement sur :
```
http://localhost:8000/admin
```

Ou sur la page d'accueil :
```
http://localhost:8000/
```

## ⚠️ Ne PAS accéder directement à :
- `/notifications/count` ❌ (API JSON seulement)
- `/notifications/{id}/read` ❌ (POST seulement)
- `/notifications/read-all` ❌ (POST seulement)

Ces URLs sont utilisées par JavaScript en arrière-plan, pas pour la navigation.
