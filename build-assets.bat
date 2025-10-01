@echo off
echo Building SkillsXchange assets...

REM Install dependencies if needed
if not exist "node_modules" (
    echo Installing npm dependencies...
    npm install
)

REM Build assets
echo Building CSS and JS assets...
npm run build

REM Clear Laravel caches
echo Clearing Laravel caches...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

REM Optimize for production
echo Optimizing for production...
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo Assets built successfully!
echo CSS and JS files are now available in public/build/
pause
