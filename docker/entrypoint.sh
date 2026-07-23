#!/bin/sh
set -e

if [ -f /var/www/app/.env.adminer ]; then
    cp /var/www/app/.env.adminer /var/www/app/.env
elif [ ! -f /var/www/app/.env ] && [ -f /var/www/app/.env.example ]; then
    cp /var/www/app/.env.example /var/www/app/.env
fi

mkdir -p /var/www/app/database
mkdir -p /var/www/app/storage/framework/cache
mkdir -p /var/www/app/storage/framework/sessions
mkdir -p /var/www/app/storage/framework/views
mkdir -p /var/www/app/storage/logs
mkdir -p /var/www/app/database/migrations
mkdir -p /var/www/app/database/seeders
mkdir -p /var/www/app/public/images

if [ -d /opt/database-files/migrations ]; then
    cp -R /opt/database-files/migrations/. /var/www/app/database/migrations/
fi

if [ -d /opt/database-files/seeders ]; then
    cp -R /opt/database-files/seeders/. /var/www/app/database/seeders/
fi

# Copy logo files if they exist in the mounted volume
if [ -f /var/www/app/public/images-source/logo-comav-transportes.png ]; then
    cp /var/www/app/public/images-source/logo-comav-transportes.png /var/www/app/public/images/
fi

php artisan key:generate --force
php artisan migrate --force

ROLE_COUNT=$(php -r "require '/var/www/app/vendor/autoload.php'; \$app = require_once '/var/www/app/bootstrap/app.php'; \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class); \$kernel->bootstrap(); try { echo \Illuminate\Support\Facades\DB::table('roles')->count(); } catch (\Throwable \$e) { echo 0; }" 2>/dev/null || echo 0)
if [ "$ROLE_COUNT" = "0" ]; then
    php artisan db:seed --force
fi

exec "$@"