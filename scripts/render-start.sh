#!/usr/bin/env bash

set -e

echo "Starting Shinagawa LMS on Render..."

# Renderの公開URLをLaravelへ反映
if [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"
    export ASSET_URL="${ASSET_URL:-$RENDER_EXTERNAL_URL}"
fi

# Renderが指定したPORTでApacheを待受
PORT="${PORT:-10000}"

sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

echo "Clearing Laravel caches..."
php artisan config:clear

echo "Running database migrations..."
php artisan migrate --force

# デモ用管理者を作成・更新
if [ "${SEED_INITIAL_ADMIN:-false}" = "true" ]; then
    echo "Creating demo administrator..."
    php artisan db:seed \
        --class=Database\\Seeders\\InitialAdminSeeder \
        --force
fi

echo "Caching Laravel config..."
php artisan config:cache

echo "Starting Apache on port ${PORT}..."
exec apache2-foreground
