FROM dunglas/frankenphp:1-php8.2

RUN install-php-extensions mysqli pdo_mysql

WORKDIR /app

COPY . .

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader

EXPOSE 8080

CMD ["frankenphp", "php-server", "--listen", ":8080", "--root", "/app"]
