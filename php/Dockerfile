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

# XDebug
RUN pecl install xdebug-3.1.5 \
    && docker-php-ext-enable xdebug
COPY php/xdebug.ini "${PHP_INI_DIR}/conf.d"




