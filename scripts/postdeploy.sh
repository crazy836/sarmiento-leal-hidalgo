#!/bin/bash

# Generate application key if not already present
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "null" ]; then
  echo "Generating application key..."
  php artisan key:generate --force
fi

# Create storage link
echo "Creating storage link..."
php artisan storage:link

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear caches
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "Post-deployment tasks completed!"