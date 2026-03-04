FROM php:8.2-cli

WORKDIR /app

# installer dépendances
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

# installer composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# copier projet
COPY . .

# installer dépendances Symfony
RUN composer install --optimize-autoloader
# exposer port
EXPOSE 10000

CMD php -S 0.0.0.0:10000 -t public