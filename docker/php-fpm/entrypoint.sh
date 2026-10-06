#!/bin/sh
set -e

# Initialize storage directory if empty
# -----------------------------------------------------------
# If the storage directory is empty, copy the initial contents
# and set the correct permissions.
# -----------------------------------------------------------
if [ ! "$(ls -A /var/www/storage)" ]; then
    echo "Initializing storage directory..."
    cp -R /var/www/storage-init/. /var/www/storage
    chown -R www-data:www-data /var/www/storage
fi

# Remove storage-init directory

# Copy public directory if empty
# -----------------------------------------------------------
if [ "$(ls -A /var/www/public-init)" ]; then
    echo "Deploying public directory..."
    cp -R /var/www/public-init/. /var/www/public
    chown -fR www-data:www-data /var/www/public
fi

# Remove storage-init directory
rm -rf /var/www/public-init

# Run Laravel migrations
# -----------------------------------------------------------
# Ensure the database schema is up to date.
# Sui servizi worker/scheduler (SKIP_MIGRATIONS=1) si salta, per evitare
# migrazioni concorrenti: le esegue solo il container php-fpm.
# -----------------------------------------------------------
if [ "${SKIP_MIGRATIONS:-0}" != "1" ]; then
    echo "Running migrations, publishing assets and caching..."
    php artisan migrate --force
    php artisan storage:link
    php artisan filament:upgrade

    # Clear and cache configurations
    # -----------------------------------------------------------
    # Improves performance by caching config, routes and views.
    # -----------------------------------------------------------
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    echo "SKIP_MIGRATIONS=1: salto migrazioni, asset e cache"
fi

# Run the default command
exec "$@"
