# =========================
# STAGE 1: Composer
# =========================
FROM composer:2 AS vendor

WORKDIR /app

COPY backend/composer.json backend/composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts

COPY backend/ ./

RUN composer dump-autoload \
    --optimize \
    --no-dev


# =========================
# STAGE 2: Laravel
# =========================
FROM php:8.2-fpm-alpine AS production

WORKDIR /var/www/html

RUN apk add --no-cache \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    mariadb-connector-c-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
        opcache \
        pcntl \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Crear usuario sin privilegios root
RUN addgroup -g 1000 laravel \
    && adduser -D -u 1000 -G laravel laravel

COPY --from=vendor --chown=laravel:laravel /app /var/www/html

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R laravel:laravel storage bootstrap/cache

USER laravel

EXPOSE 9000

CMD ["php-fpm", "-F"]