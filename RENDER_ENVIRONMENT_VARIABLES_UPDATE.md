# 🚀 Render Environment Variables Update Guide

## 🚨 **URGENT: Fix Resend API Key Error**

The error shows: `The Resend API key is missing. Please set the RESEND_API_KEY variable`

### 🔧 **Step 1: Update Render Environment Variables**

1. **Go to Render Dashboard:** https://dashboard.render.com/
2. **Find your SkillsXchange service**
3. **Click "Environment" tab**
4. **Remove the old variable** (if it exists):
   - Delete: `RESEND_KEY`
5. **Add the correct variable:**
   - **Key:** `RESEND_API_KEY`
   - **Value:** `re_KZXcNx4W_7fdSyXJjjHYkokLUsN5czjWt`

### 📋 **Complete Environment Variables for Render:**

Make sure these variables are set in your Render dashboard:

```bash
# Email Configuration
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=asdtumakay@gmail.com
MAIL_FROM_NAME=SkillsXchange
RESEND_API_KEY=re_KZXcNx4W_7fdSyXJjjHYkokLUsN5czjWt

# App Configuration
APP_NAME=SkillsXchange
APP_ENV=production
APP_DEBUG=false
APP_URL=https://skillsxchange-crus.onrender.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=yamanote.proxy.rlwy.net
DB_PORT=45822
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=nBMPUzSWZaJhIrrmNKWhiSoFMgfsBBqI

# Pusher Configuration
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=2047345
PUSHER_APP_KEY=5c02e54d01ca577ae77e
PUSHER_APP_SECRET=3ad793a15a653af09cd6
PUSHER_APP_CLUSTER=ap1

# Firebase Configuration
FIREBASE_PROJECT_ID=skillsxchange-26855
FIREBASE_API_KEY=AIzaSyAL1qfUGstU2DzY864pTzZwxf812JN4jkM
FIREBASE_AUTH_DOMAIN=skillsxchange-26855.firebaseapp.com
FIREBASE_DATABASE_URL=https://skillsxchange-26855-default-rtdb.asia-southeast1.firebasedatabase.app
FIREBASE_STORAGE_BUCKET=skillsxchange-26855.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=61175608249
FIREBASE_APP_ID=1:61175608249:web:ebd30cdd178d9896d2fc68
FIREBASE_MEASUREMENT_ID=G-V1WLV98X63
```

### 🔄 **Step 2: Clear Cache (Optional)**

After updating environment variables, you can clear the cache:

1. **Go to your service in Render**
2. **Click "Shell" tab**
3. **Run these commands:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan config:cache
   ```

### 🚀 **Step 3: Deploy**

1. **Click "Manual Deploy"** in Render dashboard
2. **Select "Deploy latest commit"**
3. **Wait for deployment to complete**

### ✅ **Step 4: Test**

1. **Go to your app:** https://skillsxchange-crus.onrender.com
2. **Try registering a new user**
3. **Check if email verification works**
4. **Monitor logs for any errors**

### 🎯 **Expected Result:**

- ✅ **No more "Resend API key is missing" error**
- ✅ **Email verification emails are sent successfully**
- ✅ **Users receive verification emails in their inbox**

### 📞 **If Still Having Issues:**

1. **Double-check** the environment variable name: `RESEND_API_KEY`
2. **Verify** the API key value is correct
3. **Check** Render logs for any other errors
4. **Test** with a different email address

**The key change:** `RESEND_KEY` → `RESEND_API_KEY` 🎉
