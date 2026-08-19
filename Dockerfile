FROM dunglas/frankenphp:php8.2

RUN install-php-extensions mysqli pdo_mysql

WORKDIR /app

COPY . /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader

EXPOSE 8080

CMD ["frankenphp", "run", "--listen", ":8080"]
