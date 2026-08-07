#!/bin/sh
set -e

# On s'assure qu'on lance bien l'application PHP (et pas juste un bash interactif)
if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then

    echo "🚀 [KYSURE OPS] Initialisation du conteneur..."

    # ---------------------------------------------------------
    # 1. 🔐 DÉCHIFFREMENT DES SECRETS (SOPS + Age)
    # ---------------------------------------------------------
    if [ "$APP_ENV" = 'prod' ] || [ "$APP_ENV" = 'staging' ]; then
        TARGET_ENV_FILE=".env.${APP_ENV}.enc"

        if [ -f "$TARGET_ENV_FILE" ] && [ -n "$SOPS_AGE_KEY" ]; then
            echo "🔑 [KYSURE SEC] Déchiffrement de $TARGET_ENV_FILE via SOPS..."
            sops -d "$TARGET_ENV_FILE" > .env.local
        else
            echo "⚠️ [KYSURE OPS] Fichier $TARGET_ENV_FILE ou clé SOPS manquante. Impossible de déchiffrer."
        fi
    fi

    # ---------------------------------------------------------
    # 2. 📦 INSTALLATION DES DÉPENDANCES (Dev uniquement)
    # ---------------------------------------------------------
    if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
       echo "📦 [KYSURE DEV] Installation des vendors manquants..."
       composer install --prefer-dist --no-progress --no-interaction
    fi

    # ---------------------------------------------------------
    # 3. 🗄️ VÉRIFICATION ET MIGRATION DE LA BDD
    # ---------------------------------------------------------
    if grep -q ^DATABASE_URL= .env* 2>/dev/null; then
       echo '⏳ [KYSURE BDD] En attente de la connexion PostgreSQL...'
       ATTEMPTS_LEFT_TO_REACH_DATABASE=60
       until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || DATABASE_ERROR=$(php bin/console dbal:run-sql -q "SELECT 1" 2>&1); do
          if [ $? -eq 255 ]; then
             ATTEMPTS_LEFT_TO_REACH_DATABASE=0
             break
          fi
          sleep 1
          ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
          echo "Toujours en attente... $ATTEMPTS_LEFT_TO_REACH_DATABASE tentatives restantes."
       done

       if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
          echo '❌ [KYSURE BDD FATAL] La base de données est injoignable :'
          echo "$DATABASE_ERROR"
          exit 1
       else
          echo '✅ [KYSURE BDD] Connexion établie.'
       fi

       if [ "$(find ./migrations -iname '*.php' -print -quit)" ]; then
          echo "📦 [KYSURE BDD] Application des migrations Doctrine..."
          php bin/console doctrine:scheme:update --force
          php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing
       fi
    fi

    # ---------------------------------------------------------
    # 4. 🧹 OPTIMISATION DU CACHE & WORKERS (Prod/Staging)
    # ---------------------------------------------------------
    if [ "$APP_ENV" = 'prod' ] || [ "$APP_ENV" = 'staging' ]; then
        echo "⚡ [KYSURE CACHE] Vidage et préchauffage du cache..."
        php bin/console cache:clear --no-warmup
        php bin/console cache:warmup

        echo "🔄 [KYSURE WORKER] Arrêt propre des workers Messenger..."
        php bin/console messenger:stop-workers || true
    fi

    echo '🟢 [KYSURE OPS] Initialisation terminée !'

    # ---------------------------------------------------------
    # 5. 🔀 ROUTAGE DU PROCESSUS (WEB vs WORKER)
    # ---------------------------------------------------------
    if [ "$KYSURE_ROLE" = 'worker' ]; then
       echo "⚙️ [KYSURE OPS] Démarrage en mode WORKER Messenger asynchrone..."
       # On passe les queues standards explicites pour la production (modifie selon tes besoins)
       exec php bin/console messenger:consume --all --memory-limit=128M --time-limit=3600
    fi

    echo "🌐 [KYSURE OPS] Démarrage en mode WEB (FrankenPHP)..."
fi

# Si le rôle n'est pas "worker", on lance le processus Web par défaut de l'image
exec docker-php-entrypoint "$@"
