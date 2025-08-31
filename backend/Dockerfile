# backend/Dockerfile
FROM php:8.3-cli

# System deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip wget curl zip \
    libicu-dev libzip-dev libpq-dev default-mysql-client \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
# - intl, pdo_mysql, zip as before
# - pcntl enabled (required for messenger:consume signals)
RUN docker-php-ext-install -j"$(nproc)" intl pdo_mysql zip \
 && docker-php-ext-configure pcntl --enable-pcntl \
 && docker-php-ext-install -j"$(nproc)" pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Symfony CLI
RUN wget -qO- https://get.symfony.com/cli/installer | bash \
 && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Xdebug (loaded but inert unless XDEBUG_MODE is set)
RUN pecl install xdebug \
 && docker-php-ext-enable xdebug \
 && { \
      echo "xdebug.mode=off"; \
      echo "xdebug.start_with_request=no"; \
      echo "xdebug.log_level=0"; \
    } > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# --- Fast code coverage: pcov ---
RUN pecl install pcov \
 && docker-php-ext-enable pcov \
 && { \
      echo "pcov.enabled=1"; \
      echo "pcov.directory=/var/www/app"; \
      echo "pcov.exclude='#/vendor/#'"; \
    } > /usr/local/etc/php/conf.d/pcov.ini

# Match docker-compose working dir
WORKDIR /var/www/app

# (Optional) install deps at build for faster cold starts
# You can keep composer install at runtime in docker-compose if you prefer.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist || true

# Copy the rest of the app (after deps for better layer caching)
COPY . .

# Finalize PHP config (ensure sane defaults; opcache off for dev)
RUN { \
      echo "memory_limit=512M"; \
      echo "post_max_size=32M"; \
      echo "upload_max_filesize=32M"; \
      echo "max_execution_time=60"; \
      echo "opcache.enable=0"; \
    } > /usr/local/etc/php/conf.d/app.ini

# Default command (overridden by docker-compose)
CMD ["symfony", "server:start", "--no-tls", "--port=8000", "--allow-http", "--allow-all-ip"]
