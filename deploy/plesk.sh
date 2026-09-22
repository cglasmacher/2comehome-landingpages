#!/bin/sh
set -eu

cd "$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan config:cache
