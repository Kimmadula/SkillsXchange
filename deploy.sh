#!/bin/bash

# Wait for database to be ready
echo "Waiting for database to be ready..."
sleep 10

# Build assets for production
echo "Building assets..."
npm install
npm run build

# Clear ALL caches first to ensure fresh configuration
echo "Clearing all caches..."
php artisan config:clear --no-interaction
php artisan route:clear --no-interaction
php artisan view:clear --no-interaction
php artisan cache:clear --no-interaction
php artisan optimize:clear --no-interaction

# Test database connection with fresh configuration
echo "Testing database connection..."
php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Database connected successfully'; } catch(Exception \$e) { echo 'Database connection failed: ' . \$e->getMessage(); exit(1); }"

# Run migrations
echo "Running migrations..."
php artisan migrate --force --no-interaction

# Run seeders
echo "Running seeders..."
php artisan db:seed --force --no-interaction

# Cache configuration for production
echo "Caching configuration for production..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# Start the server
echo "Starting PHP server..."
php -S 0.0.0.0:$PORT -t public
