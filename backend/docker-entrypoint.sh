#!/bin/sh
set -e

# 1. Fix root-owned named volume permissions
chown -R www-data:www-data /var/www/html/storage

# 2. Ensure the storage symlink exists (manual ln to avoid host-path leakage)
rm -f /var/www/html/public/storage
ln -sf /var/www/html/storage/app/public /var/www/html/public/storage
echo "Storage symlink created."

# 3. Generate the application key if none was supplied
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY not set — generating an ephemeral key for this container."
    export APP_KEY=$(php artisan key:generate --show)
fi

# 4. Write .env from the container environment. Laravel's `artisan serve`
#    spawns a child php -S process that does NOT inherit the container
#    environment, so the app reads this file rather than the injected vars.
#    Every value is quoted: dotenv treats an unquoted space as the end of
#    the value and then fails to parse the entire file.
#    Defaults match docker-compose.yml so local behaviour is unchanged.
cat > /var/www/html/.env <<EOF
APP_NAME="${APP_NAME:-Global Rental Car}"
APP_ENV="${APP_ENV:-local}"
APP_KEY="${APP_KEY}"
APP_DEBUG="${APP_DEBUG:-true}"
APP_TIMEZONE="UTC"
APP_URL="${APP_URL:-http://localhost:8000}"

LOG_CHANNEL="stack"
LOG_STACK="single"
LOG_LEVEL="${LOG_LEVEL:-debug}"

DB_CONNECTION="${DB_CONNECTION:-pgsql}"
DB_HOST="${DB_HOST:-database}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-globalrental}"
DB_USERNAME="${DB_USERNAME:-grader}"
DB_PASSWORD="${DB_PASSWORD:-secret}"
DB_SSLMODE="${DB_SSLMODE:-prefer}"

SESSION_DRIVER="${SESSION_DRIVER:-database}"
CACHE_STORE="${CACHE_STORE:-database}"
QUEUE_CONNECTION="database"
FILESYSTEM_DISK="local"
BROADCAST_CONNECTION="log"
MAIL_MAILER="log"

AI_SERVICE_URL="${AI_SERVICE_URL:-http://ai_service:5000}"
EOF
echo ".env written from container environment."

# 5. Wait for Postgres & Run Migrations natively
echo "Waiting for database and running migrations..."
until php artisan migrate --force; do
  echo "Database not ready, retrying in 2s..."
  sleep 2
done

# 6. Conditional Seeding (Lockfile lives in persistent /storage/app volume)
if [ ! -f /var/www/html/storage/app/seeder.lock ]; then
    echo "First boot detected. Seeding the database..."
    php artisan db:seed --force
    touch /var/www/html/storage/app/seeder.lock
    echo "Database seeded successfully."
else
    echo "Database already seeded. Skipping."
fi

# 7. Hand off execution to CMD (php artisan serve)
exec "$@"