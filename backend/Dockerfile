# backend/Dockerfile
FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libonig-dev libzip-dev zip libpq-dev \
    default-mysql-client wget curl \
    && docker-php-ext-install intl pdo pdo_mysql pdo_pgsql zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ✅ Install Symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# --- Code coverage / debug (Xdebug) ---
# Install and enable the extension (loaded, but inert unless XDEBUG_MODE is set)
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && { \
        echo "xdebug.mode=off"; \
        echo "xdebug.start_with_request=no"; \
        echo "xdebug.log_level=0"; \
      } >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www/html

COPY . .

# Install PHP dependencies
RUN composer install

CMD ["symfony", "server:start", "--no-tls", "--port=8000", "--allow-http", "--allow-all-ip"]

