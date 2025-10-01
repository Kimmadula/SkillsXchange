@echo off
REM WebSocket Video Call Server Startup Script for Windows

echo Starting WebSocket Video Call Server...

REM Check if PHP is available
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: PHP is not installed or not in PATH
    exit /b 1
)

REM Check if Composer is available
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: Composer is not installed or not in PATH
    exit /b 1
)

REM Install required dependencies
echo Installing required dependencies...
composer require ratchet/pawl react/socket

REM Check if dependencies were installed successfully
if %errorlevel% neq 0 (
    echo Error: Failed to install dependencies
    exit /b 1
)

REM Set the port (default: 8080)
set PORT=%1
if "%PORT%"=="" set PORT=8080

echo Starting WebSocket server on port %PORT%...

REM Start the WebSocket server
php artisan websocket:start --port=%PORT%

echo WebSocket server stopped.
pause
