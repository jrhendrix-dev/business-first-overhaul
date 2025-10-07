# backend/Dockerfile
FROM php:8.3-cli

# System deps + MySQL client for health/wait checks
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl gnupg ca-certificates default-mysql-client \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions for MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# (Optional) Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
 && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/app
