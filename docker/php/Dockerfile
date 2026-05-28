FROM php:8.4-fpm-bookworm AS base

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        git \
        libicu72 \
        libicu-dev \
        libonig5 \
        libonig-dev \
        libxml2 \
        libxml2-dev \
        libzip4 \
        libzip-dev \
        unzip \
        zip \
    && apt-mark manual \
        libicu72 \
        libonig5 \
        libxml2 \
        libzip4 \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        zip \
    && apt-get purge -y --auto-remove \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS development

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

FROM base AS production

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
