FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 80
