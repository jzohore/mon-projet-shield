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
       sodium \
    ;

# 🔐 KYSURE SEC : Installation dynamique de SOPS selon l'architecture du CPU (AMD64 ou ARM64)
ARG TARGETARCH
RUN curl -L -o /usr/local/bin/sops https://github.com/getsops/sops/releases/download/v3.8.1/sops-v3.8.1.linux.${TARGETARCH:-amd64} \
    && chmod +x /usr/local/bin/sops

RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/symfony.ini

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

COPY --link frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link frankenphp/Caddyfile /etc/frankenphp/Caddyfile

ENTRYPOINT ["docker-entrypoint"]
# Ce Healthcheck de base est pour le Dev/Général. Il sera écrasé en Prod.
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
ENV SERVER_NAME=":80"
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/

COPY --link composer.* symfony.* ./
# 🛡️ FIX : à ce stade, config/ (donc config/preload.php référencé par
# opcache.preload dans 20-app.prod.ini) n'est pas encore copié dans l'image.
# On invoque composer.phar directement via `php -d opcache.preload=` pour
# neutraliser UNIQUEMENT cette directive sur cette invocation précise.
# ⚠️ Contrairement à PHP_INI_SCAN_DIR=/dev/null (testé avant, cassait tout),
# ceci laisse intact le scan de conf.d/ et app.conf.d/ : toutes les extensions
# (amqp, imagick, sodium, gd...) restent bien chargées pour la résolution
# des dépendances Composer, seul opcache.preload est court-circuité.
RUN set -eux; \
    php -d opcache.preload= "$(command -v composer)" install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY --link . ./
ARG APP_SECRET="kysure_ci_dummy_secret_for_build_only"
ARG DATABASE_URL="postgresql://dummy:dummy@127.0.0.1:5432/dummy"
ARG SENTRY_DSN="https://dummy_public_key@dummy.ingest.sentry.io/1234567"

# 🛡️ À partir d'ici, config/ (et donc config/preload.php) existe bien sur le
# disque : pas besoin de neutraliser PHP_INI_SCAN_DIR pour ce bloc.
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

# 🛡️ KYSURE OPS : Healthcheck intelligent (Auto-détection Web vs Worker)
# Écrase le Healthcheck natif pour protéger les Workers d'un rollback Coolify.
HEALTHCHECK --start-period=60s --interval=15s --timeout=3s --retries=3 \
    CMD sh -c 'if [ "$KYSURE_ROLE" = "worker" ]; then exit 0; else curl -f http://localhost:2019/metrics || exit 1; fi'
