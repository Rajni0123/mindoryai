#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/www/wwwroot/blinkstudy.in}"
cd "$APP_DIR"

echo "==> Pull latest code"
git fetch origin main
git reset --hard origin/main

echo "==> Install PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Run migrations"
php artisan migrate --force

echo "==> Clear all caches"
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
php artisan cache:clear

echo "==> Fix storage permissions"
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
touch storage/logs/laravel.log
chmod -R ug+rwx storage bootstrap/cache || true

echo "==> Done. Test: curl -s https://blinkstudy.in/api/health"
