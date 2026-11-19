# syntax=docker/dockerfile:1

############ 1) Assets (Vite + Tailwind) ############
FROM node:22-alpine AS assets
WORKDIR /build
RUN corepack enable
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile
COPY . .
RUN pnpm run build

############ 2) PHP-FPM: misma base y extensiones que desarrollo ############
FROM php:8.3-fpm AS app
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        gd \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Los límites de subida son los mismos que usas en desarrollo
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Solo dependencias de producción (sin Debugbar, PHPUnit ni Larastan)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /build/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
 && mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

# Ajustes de producción: opcache sin revisar archivos, sin cabecera X-Powered-By
RUN printf "opcache.enable=1\nopcache.validate_timestamps=0\nexpose_php=Off\n" \
    > /usr/local/etc/php/conf.d/zz-prod.ini

USER www-data

############ 3) Nginx: solo sirve lo público ############
FROM nginx:1.27-alpine AS web
COPY docker/prod/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public