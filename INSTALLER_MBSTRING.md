# Activation de l'extension PHP mbstring

## Pourquoi mbstring est nécessaire

L'extension **mbstring** (Multibyte String) est requise pour:
- Laravel Pint (formatage du code)
- Certaines commandes Artisan avec output formaté
- Manipulation correcte des chaînes UTF-8

## Comment activer mbstring sur Windows

### Étape 1: Localiser le fichier php.ini

1. Ouvrez un terminal
2. Exécutez: `php --ini`
3. Notez le chemin du fichier `Loaded Configuration File`
   - Généralement: `C:\php\php.ini`

### Étape 2: Éditer php.ini

1. Ouvrez le fichier `php.ini` avec un éditeur de texte (en tant qu'administrateur)
2. Recherchez la ligne: `;extension=mbstring`
3. Supprimez le point-virgule (`;`) au début de la ligne:
   ```ini
   ; Avant
   ;extension=mbstring
   
   ; Après
   extension=mbstring
   ```
4. Sauvegardez le fichier

### Étape 3: Redémarrer les services

- Si vous utilisez **Apache**: Redémarrez Apache
- Si vous utilisez **php artisan serve**: Arrêtez et relancez le serveur
- Si vous utilisez **Laragon/XAMPP**: Redémarrez l'application

### Étape 4: Vérifier l'activation

Exécutez dans le terminal:
```bash
php -m | findstr mbstring
```

Vous devriez voir `mbstring` dans la liste.

## Vérification dans Laravel

Une fois activé, vous pourrez utiliser:
```bash
# Formater le code
vendor/bin/pint --dirty

# Seeder avec output
php artisan db:seed --class=AtelierSeeder
```

## Alternative: Utiliser Laragon

Si vous rencontrez des difficultés, nous recommandons d'utiliser **Laragon** qui:
- Active automatiquement toutes les extensions nécessaires
- Simplifie la gestion de PHP et MySQL
- Téléchargement: https://laragon.org/download/

## Support

Si le problème persiste après ces étapes:
1. Vérifiez que vous éditez le bon fichier php.ini (celui retourné par `php --ini`)
2. Vérifiez qu'il n'y a pas d'erreur de syntaxe dans php.ini
3. Redémarrez complètement votre ordinateur
