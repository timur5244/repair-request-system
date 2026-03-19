#!/usr/bin/env sh
set -eu

cd /var/www

# Use docker env if provided
if [ ! -f ".env" ] && [ -f ".env.docker" ]; then
  cp .env.docker .env
fi

# Ensure writable dirs
mkdir -p storage bootstrap/cache database
chmod -R ug+rwX storage bootstrap/cache database || true

echo "[entrypoint] composer install"
composer install --no-interaction --prefer-dist

if [ ! -f "database/database.sqlite" ]; then
  echo "[entrypoint] create database/database.sqlite"
  : > database/database.sqlite
fi

if ! php artisan key:show >/dev/null 2>&1; then
  echo "[entrypoint] generate APP_KEY"
  php artisan key:generate --force
else
  # If key is empty in env, force-generate
  if [ "${APP_KEY:-}" = "" ]; then
    echo "[entrypoint] generate APP_KEY (empty)"
    php artisan key:generate --force
  fi
fi

echo "[entrypoint] migrate --seed"
php artisan migrate --seed --force

echo "[entrypoint] storage:link"
php artisan storage:link || true

echo "[entrypoint] serve"
exec php artisan serve --host=0.0.0.0 --port=8000

