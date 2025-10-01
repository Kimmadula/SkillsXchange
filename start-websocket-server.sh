#!/bin/bash

# WebSocket Video Call Server Startup Script

echo "Starting WebSocket Video Call Server..."

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed or not in PATH"
    exit 1
fi

# Check if Composer is available
if ! command -v composer &> /dev/null; then
    echo "Error: Composer is not installed or not in PATH"
    exit 1
fi

# Install required dependencies
echo "Installing required dependencies..."
composer require ratchet/pawl react/socket

# Check if dependencies were installed successfully
if [ $? -ne 0 ]; then
    echo "Error: Failed to install dependencies"
    exit 1
fi

# Set the port (default: 8080)
PORT=${1:-8080}

echo "Starting WebSocket server on port $PORT..."

# Start the WebSocket server
php artisan websocket:start --port=$PORT

echo "WebSocket server stopped."
