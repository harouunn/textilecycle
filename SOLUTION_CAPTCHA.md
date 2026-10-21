# Solution : CAPTCHA n'affiche pas l'image

## Problème
L'image de vérification anti-robot ne s'affiche pas car l'extension **GD** n'est pas activée dans PHP.

## Solution

### 1. Activer l'extension GD dans PHP

Ouvrez le fichier : `C:\php\php.ini`

Cherchez la ligne :
```ini
;extension=gd
```

Décommentez-la (enlevez le `;`) :
```ini
extension=gd
```

### 2. Redémarrer le serveur

```bash
# Arrêtez php artisan serve (Ctrl+C)
# Puis relancez :
php artisan serve
```

### 3. Vérifier que GD est activé

```bash
php -m | findstr gd
```

Vous devriez voir `gd` dans la liste.

## Test
Allez sur `/login` - l'image CAPTCHA devrait maintenant s'afficher.

## Alternative si GD ne peut pas être installé
Désactivez temporairement le CAPTCHA dans `.env` :
```env
AUTH_CAPTCHA=false
```

Puis dans `config/auth.php`, ajoutez :
```php
'captcha' => env('AUTH_CAPTCHA', true),
```
