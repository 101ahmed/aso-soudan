#!/bin/bash
set -euo pipefail

PORT="${PORT:-8080}"
export PORT

# Apache must listen on Render's assigned PORT
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\${PORT}/${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Render Postgres often provides DATABASE_URL; Laravel reads DB_URL
if [[ -n "${DATABASE_URL:-}" && -z "${DB_URL:-}" ]]; then
  export DB_URL="$DATABASE_URL"
fi

# Render injects public URL automatically
if [[ -z "${APP_URL:-}" && -n "${RENDER_EXTERNAL_URL:-}" ]]; then
  export APP_URL="$RENDER_EXTERNAL_URL"
fi
if [[ -z "${FRONTEND_URL:-}" && -n "${RENDER_EXTERNAL_URL:-}" ]]; then
  export FRONTEND_URL="$RENDER_EXTERNAL_URL"
fi
if [[ -z "${SANCTUM_STATEFUL_DOMAINS:-}" && -n "${RENDER_EXTERNAL_HOSTNAME:-}" ]]; then
  export SANCTUM_STATEFUL_DOMAINS="$RENDER_EXTERNAL_HOSTNAME"
fi

cd /var/www/html

# Ensure writable dirs
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

if [[ -z "${APP_KEY:-}" ]]; then
  echo "ERROR: APP_KEY is not set. Generate one locally with: php artisan key:generate --show"
  exit 1
fi

php artisan config:clear || true
php artisan migrate --force

if [[ "${RUN_SEEDERS:-false}" == "true" ]]; then
  php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache || true
php artisan storage:link || true

exec "$@"
