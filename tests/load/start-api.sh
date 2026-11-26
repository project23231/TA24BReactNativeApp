#!/bin/sh
set -eu

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Keep dependencies inside this stand's volume, separate from the host vendor folder.
lock_hash=$(sha256sum composer.lock | cut -d ' ' -f 1)
if [ ! -f vendor/autoload.php ] || [ "$(cat vendor/.load-lock-hash 2>/dev/null || true)" != "$lock_hash" ]; then
    composer install --no-interaction --prefer-dist --no-progress
    printf '%s' "$lock_hash" > vendor/.load-lock-hash
fi

php artisan config:clear
php artisan migrate --force

# One worker is the default PHP development server. On Linux, a value above
# one enables the requested number of child workers in addition to the parent.
if [ "${PHP_CLI_SERVER_WORKERS:-4}" = "1" ]; then
    unset PHP_CLI_SERVER_WORKERS
fi

exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
