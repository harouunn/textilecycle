# Mettre TexTileCycle en ligne

Ce guide explique comment installer le site sur un serveur (VPS Linux, ou hébergement mutualisé avec accès SSH).

## 1. Prérequis

| Élément | Version / détail |
|---|---|
| PHP | **8.3** ou plus, avec les extensions `gd`, `pdo_mysql`, `mbstring`, `fileinfo`, `zip`, `curl`, `openssl` |
| Base de données | MySQL 8 ou MariaDB 10.6+ |
| Composer | 2.x |
| Serveur web | Apache ou Nginx, avec le **dossier `public/` comme racine du site** |
| Ollama (facultatif) | Pour l'IA de vision du module Dépôt. Sans Ollama, l'analyse locale prend le relais. |

## 2. Installation

```bash
git clone <url-du-depot> textilecycle && cd textilecycle
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Ensuite, ouvrir `.env` et remplir la configuration de la section 3, puis lancer :

```bash
php artisan migrate --force          # crée les tables
# Créer ensuite le compte administrateur : s'inscrire sur le site, puis
# php artisan tinker --execute="App\Models\User::where('email','vous@exemple.fr')->update(['role'=>'admin']);"
# NE PAS lancer db:seed en production : il crée des comptes de démo au mot de passe « password ».
php artisan storage:link             # rend les photos déposées accessibles
php artisan optimize                 # met en cache config, routes, vues et événements
```

Droits d'écriture pour le serveur web :

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## 3. Fichier `.env` de production

```dotenv
APP_NAME=TexTileCycle
APP_ENV=production
APP_DEBUG=false                      # obligatoire : n'affiche jamais le code en cas d'erreur
APP_URL=https://votre-domaine.fr     # en https : le site force alors tous ses liens en https
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=textilecycle
DB_USERNAME=textilecycle
DB_PASSWORD=un-mot-de-passe-solide

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true           # cookies envoyés seulement en https
CACHE_STORE=file

# E-mails (code de vérification à 6 chiffres, rappels de retrait)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=adresse@gmail.com
MAIL_PASSWORD=mot-de-passe-d-application
MAIL_FROM_ADDRESS=adresse@gmail.com

# CAPTCHA anti-robot à l'inscription
CAPTCHA_ACTIF=true

# IA de vision du module Dépôt (mettre false si Ollama n'est pas installé sur le serveur)
DEPOT_IA_ACTIVE=true
DEPOT_OLLAMA_URL=http://127.0.0.1:11434
DEPOT_OLLAMA_MODELE=gemma3:4b
```

## 4. Tâche planifiée (obligatoire)

Chaque jour à 8 h, `depot:reservations` annule les réservations expirées et envoie les rappels de retrait. Il suffit d'ajouter **une seule ligne** dans la crontab du serveur (`crontab -e`) :

```cron
* * * * * cd /chemin/vers/textilecycle && php artisan schedule:run >> /dev/null 2>&1
```

Aucun « worker » de file d'attente n'est nécessaire : les e-mails partent directement.

## 5. IA Ollama sur le serveur (facultatif)

```bash
curl -fsSL https://ollama.com/install.sh | sh
ollama pull gemma3:4b
php artisan depot:ia                 # doit répondre « Ollama est en ligne et le modèle est installé »
```

Sans carte graphique, une analyse prend environ 20 à 60 s. Si c'est trop lent, mettre `DEPOT_IA_ACTIVE=false` : l'analyse locale sans IA reste disponible et le dépôt fonctionne toujours.

## 6. Mise à jour du site

```bash
php artisan down                     # affiche la page « Site en maintenance »
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan up
```

## 7. Ce qui est déjà prêt dans le code

| Point | Où |
|---|---|
| Pages d'erreur en français (403, 404, 419, 429, 500, 503), sans dépendance à la base | `resources/views/errors/` |
| En-têtes de sécurité (anti-iframe, anti-MIME sniffing, HSTS en https…) | `app/Http/Middleware/EnTetesSecurite.php` |
| Liens forcés en https quand `APP_URL` est en https | `app/Providers/AppServiceProvider.php` |
| Proxy / load balancer reconnu (https et IP du visiteur) | `bootstrap/app.php` (`trustProxies`) |
| Pages privées non indexées par Google | `public/robots.txt` |
| Vérification de santé pour l'hébergeur | `GET /up` répond 200 si le site fonctionne |
| Fichiers CSS/JS versionnés (le cache navigateur est vidé à chaque modification) | `asset_v()` dans `app/Support/helpers.php` |
| Inscription protégée : CAPTCHA, e-mail vérifié par code, double authentification (QR code) | module Authentification |
| Tests automatiques | `php artisan test` |

## 8. Vérifications après la mise en ligne

1. `https://votre-domaine.fr/up` : la page répond « Application up ».
2. Une adresse inconnue affiche la page « Page introuvable » en français, sans détail technique.
3. Inscription : le code à 6 chiffres arrive bien par e-mail.
4. Déposer un vêtement avec une photo : la photo s'affiche (sinon, relancer `php artisan storage:link`).
5. `php artisan schedule:list` affiche la tâche `depot:reservations`.
