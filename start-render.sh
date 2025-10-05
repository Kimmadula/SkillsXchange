#!/bin/bash

# SkillsXchange Render Deployment Script with Firebase Video Calling
echo "Starting SkillsXchange on Render with Firebase video calling..."

# Set proper permissions
chmod -R 755 storage bootstrap/cache

# Ensure Firebase files are accessible
chmod 644 public/firebase-config.js public/firebase-video-integration.js public/firebase-video-call.js

# Wait for database to be ready
echo "Waiting for database to be ready..."
sleep 10

# Test database connection
echo "Testing database connection..."
php test-db-connection.php || echo "Database not ready, will retry..."

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force || echo "Migration failed, continuing..."

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
