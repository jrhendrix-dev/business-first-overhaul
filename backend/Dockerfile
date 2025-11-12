FROM php:8.3-fpm-alpine

RUN apk add --no-cache bash icu-dev libzip-dev oniguruma-dev git curl tzdata su-exec \
 && docker-php-ext-install pdo pdo_mysql intl opcache

WORKDIR /var/www/app

# Opcache tuned for prod
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.enable_cli=0'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.preload=/var/www/app/config/preload.php'; \
      echo 'opcache.preload_user=www-data'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Vendors first (no scripts)
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --ansi --no-scripts

# App code (owning only runtime dirs reduces chown cost)
COPY . ./

RUN composer dump-autoload -o --ansi \
 && composer run-script post-install-cmd --no-interaction || true

# Entrypoint handles perms, keys, cache, migrations, then php-fpm
COPY docker/entrypoint.sh /usr/local/bin/entry.sh
RUN chmod +x /usr/local/bin/entry.sh
ENTRYPOINT ["/usr/local/bin/entry.sh"]
