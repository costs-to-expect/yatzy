FROM php:8.2-apache

# Composer is part of the image so dependencies are installed with the same PHP the app runs on,
# docker exec yatzy.app composer install
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends git zip unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql bcmath \
    && a2enmod rewrite

COPY .docker/app/vhost.conf /etc/apache2/sites-available/000-default.conf

# The code goes last so changing it doesn't rebuild the layers above
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html
