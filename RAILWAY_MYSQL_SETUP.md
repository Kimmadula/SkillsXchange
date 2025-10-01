# 🚀 SkillsXchange Railway MySQL Integration Guide

This guide will help you integrate your SkillsXchange project with Railway MySQL database and deploy it on both Railway and Render.

## 📊 Railway MySQL Database Configuration

Based on your Railway dashboard, here are your MySQL database credentials:

### Database Connection Details:
- **Host (Internal)**: `mysql.railway.internal`
- **Host (Public)**: `yamanote.proxy.rlwy.net`
- **Port**: `45822`
- **Database**: `railway`
- **Username**: `root`
- **Password**: `nBMPUzSWZaJhIrrmNKWhiSoFMgfsBBqI`

## 🔧 Step 1: Local Development Setup

### 1.1 Create Local Environment File
Copy the `railway.env` file to `.env` for local development:

```bash
cp railway.env .env
```

### 1.2 Update Local Database Configuration
Edit your `.env` file and update these values:

```env
APP_NAME=SkillsXchange
DB_CONNECTION=mysql
DB_HOST=yamanote.proxy.rlwy.net
DB_PORT=45822
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=nBMPUzSWZaJhIrrmNKWhiSoFMgfsBBqI
```

### 1.3 Test Local Connection
```bash
# Generate application key
php artisan key:generate

# Test database connection
php artisan tinker
# In tinker, run:
DB::connection()->getPdo();
```

## 🚀 Step 2: Railway Deployment

### 2.1 Railway Configuration
Your `railway.toml` is already configured with:
- Docker build using `Dockerfile.railway`
- Health check endpoint: `/health`
- Environment variables for MySQL connection

### 2.2 Deploy to Railway
1. **Connect your GitHub repository to Railway**
2. **Railway will automatically detect the `railway.toml` configuration**
3. **The deployment will use the internal MySQL connection** (`mysql.railway.internal`)

### 2.3 Railway Environment Variables
Railway will automatically use these environment variables:
- `DB_HOST=mysql.railway.internal`
- `DB_PORT=3306`
- `DB_DATABASE=railway`
- `DB_USERNAME=root`
- `DB_PASSWORD=nBMPUzSWZaJhIrrmNKWhiSoFMgfsBBqI`

## 🌐 Step 3: Render Deployment

### 3.1 Render Configuration
Your `render.yaml` is configured to use the **public proxy connection** for external access.

### 3.2 Deploy to Render
1. **Connect your GitHub repository to Render**
2. **Render will use the `render.yaml` configuration**
3. **The deployment will use the public proxy connection** (`yamanote.proxy.rlwy.net:45822`)

### 3.3 Render Environment Variables
Render will automatically use these environment variables:
- `DB_HOST=yamanote.proxy.rlwy.net`
- `DB_PORT=45822`
- `DB_DATABASE=railway`
- `DB_USERNAME=root`
- `DB_PASSWORD=nBMPUzSWZaJhIrrmNKWhiSoFMgfsBBqI`

## 🔍 Step 4: Database Migration and Seeding

### 4.1 Run Migrations
Both Railway and Render will automatically run migrations during deployment:

```bash
# This happens automatically in start.sh
php artisan migrate --force --no-interaction
```

### 4.2 Seed Database
The deployment script will also seed the database:

```bash
# This happens automatically in start.sh
php artisan db:seed --force --no-interaction
```

## 🧪 Step 5: Testing the Setup

### 5.1 Test Database Connection
Visit these endpoints to test your setup:

**Railway:**
- Health check: `https://your-railway-app.railway.app/health`
- Database test: `https://your-railway-app.railway.app/test-db`

**Render:**
- Health check: `https://skillsxchange-13vk.onrender.com/health`
- Database test: `https://skillsxchange-13vk.onrender.com/test-db`

### 5.2 Test Application Features
1. **User Registration/Login**
2. **Skill Trading System**
3. **Video Calling (Firebase)**
4. **Task Management**
5. **Real-time Chat**

## 🔧 Step 6: Additional Configuration

### 6.1 Pusher Configuration
You'll need to set up Pusher for real-time features:

1. **Create a Pusher account** at https://pusher.com
2. **Create a new app**
3. **Update environment variables** with your Pusher credentials:

```env
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=ap1
```

### 6.2 Firebase Configuration
For video calling, you'll need Firebase:

1. **Create a Firebase project** at https://console.firebase.google.com
2. **Enable Realtime Database**
3. **Update `public/firebase-config.js`** with your Firebase configuration

## 📱 Step 7: Domain Configuration

### 7.1 Update APP_URL
Make sure your `APP_URL` matches your deployment:

**Railway:**
```env
APP_URL=https://your-railway-app.railway.app
```

**Render:**
```env
APP_URL=https://skillsxchange-13vk.onrender.com
```

### 7.2 Update SANCTUM_DOMAINS
Update the Sanctum stateful domains in your environment:

```env
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,127.0.0.1:8000,::1,your-railway-app.railway.app,skillsxchange-13vk.onrender.com
```

## 🚨 Troubleshooting

### Common Issues:

1. **Database Connection Failed**
   - Check if the MySQL service is running in Railway
   - Verify the connection credentials
   - Test with the public proxy URL

2. **Migration Errors**
   - Check database permissions
   - Ensure the database exists
   - Run migrations manually if needed

3. **Asset Building Issues**
   - Check Node.js version compatibility
   - Verify Vite configuration
   - Check build logs for errors

4. **Video Call Issues**
   - Verify Firebase configuration
   - Check WebRTC permissions
   - Test with HTTPS (required for video calls)

## 📊 Monitoring

### Railway Monitoring:
- Check Railway dashboard for service status
- Monitor database metrics
- Check deployment logs

### Render Monitoring:
- Check Render dashboard for service status
- Monitor application logs
- Check health check endpoints

## 🎉 Success Checklist

- [ ] Railway MySQL database is running
- [ ] Local development environment connects to Railway MySQL
- [ ] Railway deployment is successful
- [ ] Render deployment is successful
- [ ] Database migrations completed
- [ ] Application features working
- [ ] Pusher configuration complete
- [ ] Firebase configuration complete
- [ ] Domain configuration correct
- [ ] Health checks passing

## 🔗 Useful Links

- **Railway Dashboard**: https://railway.app
- **Render Dashboard**: https://render.com
- **Pusher Dashboard**: https://dashboard.pusher.com
- **Firebase Console**: https://console.firebase.google.com

Your SkillsXchange application is now ready for production deployment! 🚀
