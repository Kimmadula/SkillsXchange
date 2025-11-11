# 🚀 SkillsXchange Local Development Setup Guide

## ✅ Prerequisites Check

Your system is ready:
- ✅ PHP 8.2.12 (Required: 8.0.2+)
- ✅ Node.js v22.18.0
- ✅ Composer 2.8.11
- ✅ XAMPP with Apache and MySQL running

## 📋 Step-by-Step Setup Instructions

### Step 1: Create Environment File

Copy the template to create your `.env` file:

```bash
copy env.template .env
```

### Step 2: Configure Environment for Local Development

Edit the `.env` file and update these key settings for local development:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/SkillsXchange/public

# Database Configuration (XAMPP MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skillsxchangee
DB_USERNAME=root
DB_PASSWORD=

# Session Configuration (for localhost)
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=false

# Google OAuth (update redirect URI for local)
GOOGLE_REDIRECT_URI=http://localhost/SkillsXchange/public/auth/google/callback
```

### Step 3: Create Database

1. Open phpMyAdmin: http://localhost/phpmyadmin/
2. Click "New" to create a new database
3. Name it: `skillsxchangee`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"

### Step 4: Install PHP Dependencies

```bash
composer install
```

### Step 5: Generate Application Key

```bash
php artisan key:generate
```

### Step 6: Run Database Migrations

```bash
php artisan migrate
```

This will create all the necessary tables in your database.

### Step 7: (Optional) Seed Database with Sample Data

```bash
php artisan db:seed
```

Or seed specific seeders:
```bash
php artisan db:seed --class=SkillSeeder
php artisan db:seed --class=AdminUserSeeder
```

### Step 8: Install Node.js Dependencies

```bash
npm install
```

### Step 9: Build Frontend Assets

For development (with hot reload):
```bash
npm run dev
```

For production build:
```bash
npm run build
```

### Step 10: Set Storage Permissions (if needed)

```bash
php artisan storage:link
```

### Step 11: Clear and Cache Configuration

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 12: Access Your Application

Since you're using XAMPP, access your application at:
- **URL**: http://localhost/SkillsXchange/public

Or if you have a virtual host configured:
- **URL**: http://skillsxchange.local (or your configured domain)

## 🔧 Alternative: Using Laravel's Built-in Server

If you prefer using Laravel's development server instead of Apache:

1. Stop Apache in XAMPP (or use a different port)
2. Run:
   ```bash
   php artisan serve
   ```
3. Access at: http://localhost:8000

## 📝 Important Notes

### Database Configuration
- Database name: `skillsxchangee`
- Username: `root`
- Password: (empty - default XAMPP)
- Host: `127.0.0.1`
- Port: `3306`

### Environment Variables
- For local development, set `APP_ENV=local` and `APP_DEBUG=true`
- Update `APP_URL` to match your local setup
- Set `SESSION_SECURE_COOKIE=false` for HTTP (not HTTPS) on localhost

### Third-Party Services
The application uses:
- **Pusher** - for real-time features (already configured in template)
- **Firebase** - for authentication (already configured in template)
- **Resend** - for email (already configured in template)
- **Google OAuth** - update redirect URI for local development

### Troubleshooting

#### Database Connection Issues
- Verify MySQL is running in XAMPP
- Check database name matches in `.env`
- Ensure database exists in phpMyAdmin

#### Permission Issues
- Make sure `storage/` and `bootstrap/cache/` directories are writable
- On Windows, usually not an issue, but check if needed

#### Asset Issues
- Run `npm run build` or `npm run dev`
- Clear browser cache
- Check browser console for errors

#### Route Not Found
- Run `php artisan route:clear`
- Check `.htaccess` file in `public/` directory
- Verify Apache mod_rewrite is enabled

## 🎯 Quick Start Checklist

- [ ] Copy `env.template` to `.env`
- [ ] Update `.env` with local settings
- [ ] Create database `skillsxchangee` in phpMyAdmin
- [ ] Run `composer install`
- [ ] Run `php artisan key:generate`
- [ ] Run `php artisan migrate`
- [ ] Run `npm install`
- [ ] Run `npm run build` or `npm run dev`
- [ ] Clear caches: `php artisan config:clear`
- [ ] Access application at http://localhost/SkillsXchange/public

## 🆘 Need Help?

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console for JavaScript errors
3. Verify all services are running (Apache, MySQL)
4. Ensure all dependencies are installed

