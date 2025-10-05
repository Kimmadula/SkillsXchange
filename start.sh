#!/bin/bash

# Enable error reporting but don't exit on non-critical errors
set -e

# Wait for database to be ready
echo "Waiting for database to be ready..."
sleep 10

# Ensure .env file exists with proper structure
echo "Ensuring .env file exists..."
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << EOF
APP_NAME=SkillsXchange
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://skillsxchange-crus.onrender.com
LOG_CHANNEL=stderr
DB_CONNECTION=mysql
DB_HOST=mysql.railway.internal
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
EOF
fi

# Generate application key first
echo "Generating application key..."
php artisan key:generate --force --no-interaction

# Clear any cached config first
echo "Clearing cached configuration..."
php artisan config:clear --no-interaction

# Test database connection (non-blocking)
echo "Testing database connection..."
php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Database connected successfully'; } catch(Exception \$e) { echo 'Database connection failed: ' . \$e->getMessage(); }" || echo "Database connection test failed, but continuing..."

# Cache configuration for production (after APP_KEY is available)
echo "Caching configuration for production..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force --no-interaction

# Run database seeders (only if not already seeded)
echo "Running database seeders..."
php artisan db:seed --force --no-interaction || echo "Seeder completed with warnings (some data may already exist)"

# Start the PHP server
echo "Starting PHP server..."
php -S 0.0.0.0:$PORT -t public
