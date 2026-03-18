# syntax=docker/dockerfile:1.6
FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader
COPY . ./
RUN composer dump-autoload --no-dev --optimize

FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        gd \
        intl \
        opcache \
        pdo_mysql \
        zip

COPY --from=vendor /app /var/www/html

RUN addgroup -g 1000 -S www \
    && adduser -u 1000 -S www -G www \
    && chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache

USER www

CMD ["php-fpm"]
