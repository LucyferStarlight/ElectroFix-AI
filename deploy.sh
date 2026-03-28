#!/usr/bin/env bash
set -euo pipefail

php artisan migrate --force

if [[ "${1:-}" == "--seed" ]]; then
  php artisan db:seed --force
fi

php artisan optimize:clear
php artisan storage:link

if [[ "${APP_ENV:-}" == "production" ]]; then
  php artisan config:cache
  php artisan route:cache
  php artisan optimize
fi
