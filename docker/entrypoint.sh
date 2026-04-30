#!/bin/sh
set -e

if [ ! -f /var/www/app/.env ]; then
    cp /var/www/app/.env.example /var/www/app/.env
fi

php -r "
\$env = file_get_contents('/var/www/app/.env');
\$env = preg_replace('/^APP_URL=.*/m', 'APP_URL=http://localhost:8000', \$env);
\$env = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=sqlite', \$env);
\$env = preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE=/var/www/app/database/database.sqlite', \$env);
file_put_contents('/var/www/app/.env', \$env);
"

touch /var/www/app/database/database.sqlite
mkdir -p /var/www/app/storage/framework/cache
mkdir -p /var/www/app/storage/framework/sessions
mkdir -p /var/www/app/storage/framework/views
mkdir -p /var/www/app/storage/logs
mkdir -p /var/www/app/database/migrations
mkdir -p /var/www/app/database/seeders

if [ -d /opt/database-files/migrations ]; then
    cp -R /opt/database-files/migrations/. /var/www/app/database/migrations/
fi

if [ -d /opt/database-files/seeders ]; then
    cp -R /opt/database-files/seeders/. /var/www/app/database/seeders/
fi

php artisan key:generate --force
php artisan migrate --force

ROLE_COUNT=$(sqlite3 /var/www/app/database/database.sqlite "select count(*) from roles;" 2>/dev/null || echo 0)
if [ "$ROLE_COUNT" = "0" ]; then
    php artisan db:seed --force
fi

exec "$@"