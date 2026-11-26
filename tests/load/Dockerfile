FROM composer:2 AS composer

FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libonig-dev libzip-dev unzip git \
    && docker-php-ext-install -j$(nproc) pdo_pgsql intl mbstring zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
