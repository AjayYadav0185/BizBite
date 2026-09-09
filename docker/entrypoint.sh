#!/bin/sh
set -e

# Ensure logs directory
mkdir -p /var/www/storage/logs

# Prepare SQLite database file only when DB_CONNECTION is sqlite (default)
DB_CONN="${DB_CONNECTION:-sqlite}"
if [ "$DB_CONN" = "sqlite" ]; then
    mkdir -p /var/www/database
    DB_DATABASE="${DB_DATABASE:-/var/www/database/database.sqlite}"
    export DB_CONNECTION=sqlite
    export DB_DATABASE
    touch "$DB_DATABASE"
fi

echo "Using database connection: $DB_CONNECTION"
echo "Using database file: $DB_DATABASE"
echo "Using queue connection: ${QUEUE_CONNECTION:-database}"
echo "Clearing config and cache..."
echo "Running migrations and seeders..."

# FIXED: Removed Docker 'RUN' syntax and backslashes
composer config platform.php 8.3.0
composer config "policy.advisories.block" false
# touch /tmp/database.sqlite
composer install --no-dev --optimize-autoloader --no-interaction

# FIXED: Removed Docker 'RUN' syntax
chmod -R 775 storage bootstrap/cache

# Optimise Laravel application
php artisan config:clear
php artisan cache:clear

# Default queue connection to 'database' if not provided
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-database}"

# Run migrations and seeders

php artisan db:wipe --force
php artisan migrate --force
php artisan db:seed --force

# Start the queue worker in the background
php artisan queue:work --sleep=3 --tries=3 --timeout=120 \
        >> /var/www/storage/logs/queue-worker.log 2>&1 &

# Start the application server

exec php artisan serve --host=0.0.0.0 --port=10000
