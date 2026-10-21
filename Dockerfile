# =============================================================================
#  TexTileCycle : image de production (PHP 8.3 + Apache)
#  Construite et publiée par le pipeline CI/CD (ghcr.io), lancée avec docker-compose.yml.
#  Le code est copié dans l'image ; la configuration (.env) et les données (MySQL, photos)
#  restent à l'extérieur : variables d'environnement et volumes.
# =============================================================================

# --- Étape 1 : dépendances Composer de production (sans outils de développement) ---
FROM composer:2 AS dependances
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# --- Étape 2 : image finale ---
FROM php:8.3-apache

# Extensions PHP demandées par le projet (composer check-platform-reqs) + MySQL + OPcache.
# GD avec FreeType/JPEG/WebP : miniatures des photos, empreintes visuelles et CAPTCHA.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql zip opcache \
    && rm -rf /var/lib/apt/lists/*

# Réglages PHP de production : OPcache sans revérification des fichiers, envois de photos jusqu'à 5 Mo,
# 240 s pour les analyses IA (chargement du modèle compris).
COPY docker/php.ini /usr/local/etc/php/conf.d/textilecycle.ini

# Apache sert le dossier public/ et laisse Laravel gérer les URL (mod_rewrite).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && a2enmod rewrite headers expires deflate

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=dependances /app/vendor ./vendor

COPY docker/entrypoint.sh /usr/local/bin/textilecycle-entrypoint
RUN chmod +x /usr/local/bin/textilecycle-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

# Docker sait si le site fonctionne : /up répond 200 quand Laravel démarre correctement.
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 CMD curl -fsS http://localhost/up || exit 1

ENTRYPOINT ["textilecycle-entrypoint"]
CMD ["apache2-foreground"]
