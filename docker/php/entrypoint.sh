#!/bin/sh
set -e

SERVICE_NAME="${SERVICE_NAME:-app}"
echo "==> [${SERVICE_NAME}] Starting entrypoint..."

cd /var/www

# ------------------------------------------------------------------
# 1. .env
# ------------------------------------------------------------------
if [ ! -f /var/www/.env ]; then
    if [ -f /var/www/.env.example ]; then
        echo "==> [${SERVICE_NAME}] Creating .env from .env.example..."
        cp /var/www/.env.example /var/www/.env
    else
        echo "==> [${SERVICE_NAME}] ERROR: .env.example not found."
        exit 1
    fi

    # DB credentials from environment
    echo "==> [${SERVICE_NAME}] Applying DB credentials from environment..."
    sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST:-localhost}|" /var/www/.env
    sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT:-5432}|" /var/www/.env
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-laravel}|" /var/www/.env
    sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-root}|" /var/www/.env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD:-}|" /var/www/.env

    sed -i "s|^RABBITMQ_HOST=.*|RABBITMQ_HOST=${RABBITMQ_HOST:-rabbitmq}|" /var/www/.env
    sed -i "s|^RABBITMQ_PORT=.*|RABBITMQ_PORT=${RABBITMQ_PORT:-5672}|" /var/www/.env
    sed -i "s|^RABBITMQ_USER=.*|RABBITMQ_USER=${RABBITMQ_USER:-guest}|" /var/www/.env
    sed -i "s|^RABBITMQ_PASSWORD=.*|RABBITMQ_PASSWORD=${RABBITMQ_PASSWORD:-guest}|" /var/www/.env
else
    echo "==> [${SERVICE_NAME}] .env already exists."
fi

# ------------------------------------------------------------------
# 2. Composer install
# ------------------------------------------------------------------
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "==> [${SERVICE_NAME}] Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "==> [${SERVICE_NAME}] Composer dependencies already installed."
fi

# ------------------------------------------------------------------
# 3. APP_KEY
# ------------------------------------------------------------------
if ! grep -q "^APP_KEY=base64:" /var/www/.env 2>/dev/null; then
    echo "==> [${SERVICE_NAME}] Generating APP_KEY..."
    php artisan key:generate --force
else
    echo "==> [${SERVICE_NAME}] APP_KEY already set."
fi

# ------------------------------------------------------------------
# 4. Wait PostgreSQL
# ------------------------------------------------------------------
if [ -n "${DB_HOST:-}" ]; then
    echo "==> [${SERVICE_NAME}] Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT:-5432}..."
    MAX_TRIES=30
    TRIES=0
    until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" >/dev/null 2>&1; do
        TRIES=$((TRIES + 1))
        if [ "$TRIES" -ge "$MAX_TRIES" ]; then
            echo "==> [${SERVICE_NAME}] ERROR: PostgreSQL not ready after ${MAX_TRIES} attempts."
            break
        fi
        printf "    waiting (%s/%s)\n" "$TRIES" "$MAX_TRIES"
        sleep 2
    done
    echo "==> [${SERVICE_NAME}] PostgreSQL is up."
fi

# ------------------------------------------------------------------
# 5. Migrations
# ------------------------------------------------------------------
MARKER="/var/www/storage/.migrated"

if [ ! -f "$MARKER" ]; then
    echo "==> [${SERVICE_NAME}] Running migrations (first run)..."
    php artisan migrate --force
    touch "$MARKER"
    echo "==> [${SERVICE_NAME}] Migrations done."
else
    echo "==> [${SERVICE_NAME}] Already migrated."
fi

# ------------------------------------------------------------------
# 6. Permissions
# ------------------------------------------------------------------
echo "==> [${SERVICE_NAME}] Fixing permissions..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# ------------------------------------------------------------------
# 7. Finish
# ------------------------------------------------------------------
echo "==> [${SERVICE_NAME}] Bootstrap finished. Starting: $@"
exec "$@"