# Comment modifier le rôle d'un utilisateur

## Valeurs possibles
- `admin` - Administrateur (accès back-office /admin)
- `client` - Client (accès site public uniquement)

## Méthode 1 : Via Tinker (Recommandé)

```bash
php artisan tinker
```

Puis dans Tinker :

```php
# Trouver l'utilisateur par email
$user = App\Models\User::where('email', 'votre@email.com')->first();

# Changer le rôle en admin
$user->role = 'admin';
$user->save();

# Vérifier
echo $user->role; // Devrait afficher "admin"
```

Ou en une seule ligne :
```bash
php artisan tinker --execute="App\Models\User::where('email', 'votre@email.com')->update(['role' => 'admin']);"
```

## Méthode 2 : Via SQL directement

```bash
# Ouvrir la base de données SQLite
sqlite3 database/database.sqlite
```

Puis dans SQLite :

```sql
-- Voir tous les utilisateurs
SELECT id, name, email, role FROM users;

-- Changer le rôle de l'utilisateur avec ID=1
UPDATE users SET role='admin' WHERE id=1;

-- Ou par email
UPDATE users SET role='admin' WHERE email='votre@email.com';

-- Vérifier
SELECT id, name, email, role FROM users WHERE id=1;

-- Quitter
.quit
```

## Méthode 3 : Via un script artisan

Créez un fichier `modifier-role.php` :

```php
<?php
// Dans le dossier racine du projet
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$email = $argv[1] ?? null;
$role = $argv[2] ?? 'admin';

if (!$email) {
    echo "Usage: php modifier-role.php email@example.com [admin|client]\n";
    exit(1);
}

$user = App\Models\User::where('email', $email)->first();

if (!$user) {
    echo "Utilisateur non trouvé.\n";
    exit(1);
}

$user->role = $role;
$user->save();

echo "✅ Rôle de {$user->name} changé en {$role}\n";
```

Utilisation :
```bash
php modifier-role.php votre@email.com admin
```

## Exemple complet

```bash
# Lister tous les utilisateurs
php artisan tinker --execute="App\Models\User::all(['id', 'name', 'email', 'role'])->each(fn(\$u) => print(\$u->id . ' - ' . \$u->name . ' (' . \$u->email . ') : ' . \$u->role . PHP_EOL));"

# Mettre l'utilisateur ID=1 en admin
php artisan tinker --execute="App\Models\User::find(1)->update(['role' => 'admin']);"

# Vérifier
php artisan tinker --execute="echo App\Models\User::find(1)->role;"
```
