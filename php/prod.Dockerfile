FROM php:7.4.24-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    unzip
RUN docker-php-ext-install zip

# Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer self-update
COPY composer.* .
RUN composer update

# OPCache
RUN docker-php-ext-install opcache
COPY php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY ./app/ /var/www/app/

# Give user `www-data` access to create files in `/var/www/app`
RUN mkdir -p /var/www/app && chown -R www-data:www-data /var/www/app
