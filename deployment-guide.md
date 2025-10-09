# SkillsXchange Deployment Guide

## 🚀 Production Deployment Checklist

### 1. Environment Configuration
- ✅ Production `.env` file created (`.env.production`)
- ✅ Gmail SMTP configured with app password
- ✅ Firebase/Google authentication removed
- ✅ Production assets built

### 2. Required Environment Variables for Production

Update these variables in your hosting platform:

```env
# Application
APP_NAME=SkillsXchange
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (Update with your production database)
DB_CONNECTION=mysql
DB_HOST=your_production_db_host
DB_DATABASE=your_production_database
DB_USERNAME=your_production_username
DB_PASSWORD=your_production_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=asdtumakay@gmail.com
MAIL_PASSWORD=stpxhddxjztrcwdt
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=asdtumakay@gmail.com
MAIL_FROM_NAME="SkillsXchange"

# Cache (Recommended: Redis for production)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Pusher (Update with your actual values)
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=ap1
```

### 3. Deployment Steps

#### For Shared Hosting (cPanel, etc.):
1. Upload all files to your web root
2. Set document root to `/public` folder
3. Update `.env` with production values
4. Run `php artisan key:generate` on server
5. Run `php artisan migrate` to set up database
6. Set proper file permissions (755 for folders, 644 for files)

#### For VPS/Cloud (DigitalOcean, AWS, etc.):
1. Install PHP 8.0+, MySQL, Nginx/Apache
2. Install Composer and Node.js
3. Clone repository to server
4. Run `composer install --optimize-autoloader --no-dev`
5. Run `npm install && npm run build`
6. Copy `.env.production` to `.env` and update values
7. Run `php artisan key:generate`
8. Run `php artisan migrate --force`
9. Run `php artisan config:cache`
10. Run `php artisan route:cache`
11. Run `php artisan view:cache`
12. Set up web server to point to `/public` directory

#### For Platform-as-a-Service (Heroku, Railway, etc.):
1. Connect your Git repository
2. Set environment variables in platform dashboard
3. Deploy automatically on git push
4. Run migrations: `php artisan migrate --force`

### 4. Post-Deployment Commands

Run these commands after deployment:

```bash
# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Cache configuration for better performance
php artisan config:cache

# Cache routes for better performance
php artisan route:cache

# Cache views for better performance
php artisan view:cache

# Clear all caches (if needed)
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 5. Security Considerations

- ✅ Firebase/Google authentication removed
- ✅ Email verification system in place
- ✅ Password reset functionality working
- ✅ CSRF protection enabled
- ✅ SQL injection protection via Eloquent ORM

### 6. Performance Optimizations

- ✅ Production assets built and minified
- ✅ Redis recommended for caching and sessions
- ✅ Database indexing on user fields
- ✅ Optimized autoloader for production

### 7. Monitoring & Maintenance

- Monitor email delivery logs
- Set up database backups
- Monitor application logs
- Update dependencies regularly
- Monitor disk space and memory usage

## 📧 Email Configuration Notes

The application is configured to use Gmail SMTP with your credentials:
- **Email**: asdtumakay@gmail.com
- **App Password**: stpxhddxjztrcwdt

If you need to change the email settings, update the `MAIL_*` variables in your production environment.

## 🔧 Troubleshooting

### Common Issues:
1. **Email not sending**: Verify Gmail app password is correct
2. **Database connection**: Check database credentials and host
3. **Asset loading**: Ensure `public/build` folder is accessible
4. **Permission errors**: Set proper file permissions (755/644)

### Support:
- Check Laravel logs: `storage/logs/laravel.log`
- Check web server error logs
- Verify all environment variables are set correctly
