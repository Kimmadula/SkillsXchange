# 🔧 Environment Setup Guide

## 📋 Quick Setup

### **Option 1: Use Setup Scripts**

#### **Windows:**
```bash
# Run the setup script
setup-env.bat
```

#### **Linux/Mac:**
```bash
# Make executable and run
chmod +x setup-env.sh
./setup-env.sh
```

### **Option 2: Manual Setup**

1. **Copy the template:**
   ```bash
   copy env.template .env
   # or on Linux/Mac:
   cp env.template .env
   ```

2. **Edit .env file** with your actual values

## 🔑 Required Pusher Configuration

### **Get Your Pusher Credentials:**

1. Go to [Pusher Dashboard](https://dashboard.pusher.com/)
2. Create a new app or use existing
3. Go to "App Keys" tab
4. Copy your credentials

### **Update .env file with your Pusher values:**

```env
# Replace these with your actual Pusher credentials
PUSHER_APP_ID=your_actual_app_id
PUSHER_APP_KEY=your_actual_key
PUSHER_APP_SECRET=your_actual_secret
PUSHER_APP_CLUSTER=your_actual_cluster

# These will automatically use the values above
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

## 🚀 Complete Setup Steps

### **1. Environment Setup**
```bash
# Copy template to .env
copy env.template .env

# Edit .env with your values
notepad .env  # or your preferred editor
```

### **2. Generate App Key**
```bash
php artisan key:generate
```

### **3. Build Assets**
```bash
npm run build
```

### **4. Test Pusher Connection**
1. Open your app in browser
2. Check browser console for:
   - ✅ Laravel Echo initialized successfully with Pusher
   - 🔑 Pusher Key: [your_key]
   - 🌐 Pusher Cluster: [your_cluster]

## 🔍 Troubleshooting

### **If Pusher is not working:**

1. **Check credentials** - Verify all Pusher values are correct
2. **Check app status** - Ensure Pusher app is active
3. **Check cluster** - Verify cluster matches your Pusher app
4. **Check network** - Look for WebSocket connections in Network tab

### **Common Issues:**

- **"Laravel Echo not available"** - Check Pusher credentials
- **"Pusher not available"** - Check if Pusher library is loaded
- **WebSocket connection failed** - Check Pusher app status

## 📝 Environment Variables Reference

### **Essential Variables:**
```env
APP_NAME=SkillsXchange
APP_ENV=production
APP_KEY=base64:your_app_key_here
APP_URL=https://skillsxchangee.onrender.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skillsxchangee
DB_USERNAME=root
DB_PASSWORD=

# Pusher (REQUIRED)
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=ap1
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### **Optional Variables:**
```env
# Broadcasting
BROADCAST_DRIVER=pusher

# Cache
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

## ✅ Verification Checklist

- [ ] .env file created with correct values
- [ ] Pusher credentials are valid
- [ ] App key generated (`php artisan key:generate`)
- [ ] Assets built (`npm run build`)
- [ ] Pusher connection working (check console)
- [ ] Video calls working (test functionality)

## 🆘 Need Help?

If you're still having issues:

1. **Check Pusher Dashboard** - Verify app is active
2. **Check Browser Console** - Look for error messages
3. **Check Network Tab** - Look for failed requests
4. **Verify Environment** - Ensure all variables are set correctly
