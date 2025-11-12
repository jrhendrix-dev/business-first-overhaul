#!/bin/sh
set -euo pipefail

# Ensure www-data user/group exist on Alpine with uid/gid 82
addgroup -g 82 -S www-data 2>/dev/null || true
adduser  -u 82 -D -S -G www-data www-data 2>/dev/null || true

# Writable dirs
mkdir -p /var/www/app/var /var/www/app/config/jwt
chown -R www-data:www-data /var/www/app/var /var/www/app/config/jwt

# Run Symfony tasks as www-data
su-exec www-data php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction || true
su-exec www-data php bin/console cache:clear --no-warmup --env=prod
su-exec www-data php bin/console cache:warmup --env=prod
su-exec www-data php bin/console doctrine:migrations:migrate -n || true

# Hand over to php-fpm (root starts master, workers run as www-data via FPM pool)
exec php-fpm -F
