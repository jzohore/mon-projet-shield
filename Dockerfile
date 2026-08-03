#syntax=docker/dockerfile:1

# 🛡️ KYSURE STANDARD : On sécurise la version à PHP 8.4
FROM dunglas/frankenphp:1-php8.4 AS frankenphp_upstream

# Base FrankenPHP image
FROM frankenphp_upstream AS frankenphp_base

WORKDIR /app
VOLUME /app/var/

# persistent / runtime deps
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
       file \
       git \
       libmagickwand-dev \
       imagemagick \
       ghostscript \
       ffmpeg \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    ; \
    rm -rf /var/lib/apt/lists/*; \
    install-php-extensions \
       @composer \
       apcu \
       intl \
       opcache \
       zip \
       amqp \
       sockets \
       gd \
       pdo_pgsql \
    ;

# 🔐 KYSURE SEC : Installation de SOPS pour le déchiffrement Age au démarrage
RUN curl -L -o /usr/local/bin/sops https://github.com/getsops/sops/releases/download/v3.8.1/sops-v3.8.1.linux.amd64 \
    && chmod +x /usr/local/bin/sops

RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/symfony.ini

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

COPY --link frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link frankenphp/Caddyfile /etc/frankenphp/Caddyfile

ENTRYPOINT ["docker-entrypoint"]
HEALTHCHECK --start-period=60s CMD curl http://localhost:2019/metrics --silent --show-error --fail --output /dev/null || exit 1
CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile" ]

# Dev FrankenPHP image
FROM frankenphp_base AS frankenphp_dev
ENV APP_ENV=dev
ENV XDEBUG_MODE=off
ENV FRANKENPHP_WORKER_CONFIG=watch

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN set -eux; install-php-extensions xdebug;
COPY --link frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/
CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--watch" ]

# Prod FrankenPHP image
FROM frankenphp_base AS frankenphp_prod
ENV APP_ENV=prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/

COPY --link composer.* symfony.* ./
RUN set -eux; \
    composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY --link --exclude=frankenphp/ . ./

RUN set -eux; \
    mkdir -p var/cache var/log var/share; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    # 🚨 ON SUPPRIME composer dump-env prod ICI POUR LAISSER SOPS AGIR AU DEMARRAGE
    composer run-script --no-dev post-install-cmd; \
    php bin/console tailwind:build; \
    if [ -f importmap.php ]; then \
        php bin/console asset-map:compile; \
    fi; \
    chmod +x bin/console; sync;
