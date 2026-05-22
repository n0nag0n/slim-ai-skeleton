#!/bin/sh
set -e

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

php migrate

exec php -S 0.0.0.0:8080 -t public
