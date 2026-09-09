#!/bin/sh
set -e

APP_DIR="/var/www"
cd "$APP_DIR"

# ---- Required runtime directories & permissions -----------------------------
mkdir -p "$APP_DIR/storage/logs"
mkdir -p "$APP_DIR/storage/framework/cache"
mkdir -p "$APP_DIR/storage/framework/sessions"
mkdir -p "$APP_DIR/storage/framework/views"
mkdir -p "$APP_DIR/storage/app/public"
mkdir -p "$APP_DIR/bootstrap/cache"
chmod -R ug+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# ---- Database (SQLite default; set DB_CONNECTION=pgsql for Render Postgres) --
DB_CONN="${DB_CONNECTION:-sqlite}"
if [ "$DB_CONN" = "sqlite" ]; then
    DB_DATABASE="${DB_DATABASE:-$APP_DIR/database/database.sqlite}"
    export DB_CONNECTION=sqlite
    export DB_DATABASE
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
fi

# ---- APP_KEY -----------------------------------------------------------------
# Prefer setting APP_KEY in the Render dashboard. If it is missing, generate an
# ephemeral one so the app still boots (it changes on restart, so encrypted
# cookies/sessions won't survive a redeploy until a persistent key is set).
if [ -z "$APP_KEY" ]; then
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    echo "WARNING: APP_KEY was not set; generated an ephemeral key."
    echo "         Set APP_KEY in the Render dashboard so it survives restarts."
fi

echo "Database connection: $DB_CONNECTION (${DB_DATABASE:-from env})"
echo "Queue connection:    ${QUEUE_CONNECTION:-database}"

# ---- Bootstrap ---------------------------------------------------------------
php artisan config:clear  >/dev/null 2>&1 || true
php artisan cache:clear   >/dev/null 2>&1 || true
php artisan view:clear    >/dev/null 2>&1 || true

# Migrations are idempotent, so they are safe on every boot.
php artisan migrate --force

# Seed ONLY a fresh database (users table empty). Never wipe data on restart.
if php artisan tinker --execute='exit(\App\Models\User::count() === 0 ? 0 : 1);' >/dev/null 2>&1; then
    echo "Fresh database detected - running seeders..."
    php artisan db:seed --force
fi

# ---- Optimise ----------------------------------------------------------------
php artisan config:cache
php artisan view:cache

# ---- Background queue worker --------------------------------------------------
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-database}"
php artisan queue:work --sleep=3 --tries=3 --timeout=120 \
    >> "$APP_DIR/storage/logs/queue-worker.log" 2>&1 &

# ---- Web server (Render injects PORT; default to 10000) -----------------------
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
