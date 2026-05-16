FROM php:8.2-cli-alpine

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["sh", "-c", "composer install --no-interaction --prefer-dist && php -S 0.0.0.0:8080 -t public"]
