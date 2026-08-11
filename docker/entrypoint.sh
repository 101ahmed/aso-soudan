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

# Strip accidental quotes from dashboard paste
if [[ -n "${APP_KEY:-}" ]]; then
  APP_KEY="${APP_KEY%\"}"
  APP_KEY="${APP_KEY#\"}"
  APP_KEY="${APP_KEY%\'}"
  APP_KEY="${APP_KEY#\'}"
  export APP_KEY
fi

# If APP_KEY still missing, generate one so the container can boot.
if [[ -z "${APP_KEY:-}" ]]; then
  export APP_KEY="$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")"
  echo "WARNING: APP_KEY was empty — generated a temporary key for this boot."
  echo "WARNING: Set a permanent APP_KEY in Render → Environment to keep sessions stable."
fi

php artisan config:clear || true
php artisan migrate --force

# Always ensure roles + Super Admin exist (fixes empty prod DB / missed first seed)
if [[ "${SKIP_SEEDERS:-false}" != "true" ]]; then
  echo "Ensuring roles + Super Admin…"
  php artisan rdp:ensure-admin
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache || true
php artisan storage:link || true

exec "$@"
