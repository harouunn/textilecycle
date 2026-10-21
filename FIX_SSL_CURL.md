# Fix : SSL Certificate Error

## Problème
```
cURL error 60: SSL certificate OpenSSL verify result: unable to get local issuer certificate
```

## Solution

### Méthode 1 : Télécharger le certificat CA (Recommandé)

1. Téléchargez : https://curl.se/ca/cacert.pem
2. Sauvegardez le fichier dans `C:\php\cacert.pem`
3. Ouvrez `C:\php\php.ini`
4. Trouvez la ligne `;curl.cainfo =`
5. Remplacez par : `curl.cainfo = "C:\php\cacert.pem"`
6. Redémarrez `php artisan serve`

### Méthode 2 : Désactiver la vérification SSL (TEMPORAIRE - Dev seulement)

Dans `.env`, ajoutez :
```env
CURL_VERIFY=false
```

Puis dans `config/ia.php`, modifiez la configuration Gemini pour ajouter `verify => false`.

### Méthode 3 : Quick fix pour test

Modifiez temporairement `app/Services/Ateliers/IA/VisionService.php` :

Trouvez la ligne :
```php
$response = Http::timeout(config('ia.gemini.timeout'))
```

Remplacez par :
```php
$response = Http::withoutVerifying()->timeout(config('ia.gemini.timeout'))
```

⚠️ **ATTENTION** : Ne jamais faire ça en production ! C'est juste pour tester.
