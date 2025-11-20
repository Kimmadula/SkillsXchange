#!/bin/bash

# SkillsXchange Render Deployment Script with Firebase Video Calling
echo "Starting SkillsXchange on Render with Firebase video calling..."

# Set proper permissions
chmod -R 755 storage bootstrap/cache

# Ensure Firebase files are accessible
chmod 644 public/firebase-config.js public/firebase-video-integration.js public/firebase-video-call.js

# Wait for database to be ready (reduced wait time)
echo "Waiting for database to be ready..."
sleep 3

# Test database connection (with timeout)
echo "Testing database connection..."
if timeout 5 php test-db-connection.php 2>/dev/null; then
    echo "Database connection successful!"
    
    # Check if migrations are needed (only run if database is new/empty)
    echo "Checking if database needs migrations..."
    if timeout 5 php check-migrations.php 2>/dev/null; then
        echo "Database needs migrations, running..."
        php artisan migrate --force --no-interaction || echo "Migration failed, continuing..."
    else
        echo "Database already has migrations, skipping..."
    fi
else
    echo "Database connection test skipped (timeout or not ready), continuing..."
fi

# Clear and cache configurations
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# No WebSocket server needed - Firebase handles video call signaling
echo "Firebase video calling enabled - no WebSocket server needed"

# Start the main application
echo "Starting main application on port $PORT..."
php artisan serve --host=0.0.0.0 --port=$PORT
