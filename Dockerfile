# -----------------------------
# Frontend build
# -----------------------------
FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build


# -----------------------------
# Laravel / Apache
# -----------------------------
FROM php:8.4-apache-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        libxml2-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        mbstring \
        intl \
        zip \
        curl \
        dom \
        opcache \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

RUN sed -ri \
    -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

COPY scripts/render-start.sh /usr/local/bin/render-start
RUN chmod +x /usr/local/bin/render-start

CMD ["render-start"]
