#!/bin/sh
# =============================================================================
#  Démarrage du conteneur TexTileCycle
#  1. attend que MySQL accepte les connexions ;
#  2. applique les migrations (additives, jamais de migrate:fresh) ;
#  3. relie storage/app/public à public/storage (photos déposées) ;
#  4. met en cache configuration, routes, vues et événements ;
#  5. lance Apache (ou la commande passée au conteneur).
# =============================================================================
set -e
cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY est vide : définissez-la dans le fichier .env lu par docker compose." >&2
    exit 1
fi

if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
    echo "Attente de MySQL sur ${DB_HOST:-db}:${DB_PORT:-3306}..."
    essais=0
    until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }'; do
        essais=$((essais + 1))
        if [ "$essais" -ge 60 ]; then
            echo "MySQL ne répond pas après 120 secondes." >&2
            exit 1
        fi
        sleep 2
    done
fi

if [ "${MIGRER_AU_DEMARRAGE:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

# Le volume des fichiers déposés peut arriver vide : on recrée l'arborescence attendue.
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan optimize --no-interaction

# En dernier : les fichiers créés ci-dessus (caches, journaux) doivent appartenir à Apache.
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
