#!/bin/sh
set -e

# 1. Fix root-owned named volume permissions
chown -R www-data:www-data /var/www/html/storage

# 2. Ensure the storage symlink exists (manual ln to avoid host-path leakage)
rm -f /var/www/html/public/storage
ln -sf /var/www/html/storage/app/public /var/www/html/public/storage
echo "Storage symlink created."

# 3. Laravel's `artisan serve` spawns a child php -S process that does NOT
#    inherit the container environment, so the app cannot read the variables
#    docker-compose.yml injects. This .env is therefore required, not
#    redundant. Keep its DB values in sync with docker-compose.yml.
if [ ! -f /var/www/html/.env ] && [ -f /var/www/html/.env.example ]; then
    cp /var/www/html/.env.example /var/www/html/.env
    echo "Created .env from .env.example."
fi

# 4. Generate the application key if it is missing or empty
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY not set — generating an ephemeral key for this container."
    export APP_KEY=$(php artisan key:generate --show)
fi

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