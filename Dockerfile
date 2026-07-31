FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-install mysqli pdo_mysql

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader

RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf \
 && sed -i 's/:80/:8080/g' /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]
