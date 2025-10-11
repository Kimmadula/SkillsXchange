# 🚀 Render Environment Variables Update Guide

## 🚨 **URGENT: Fix Mailgun Connection Timeout Error**

The error you're seeing indicates that Render is still trying to use Mailgun instead of Brevo. Here's how to fix it:

---

## 🔧 **Step 1: Update Render Environment Variables**

### **Go to Render Dashboard:**
1. Visit: https://dashboard.render.com/
2. Find your SkillsXchange service
3. Click on the service name
4. Go to **"Environment"** tab

### **Add/Update These Variables:**

```bash
# Remove any existing MAIL_MAILER variable first, then add:
MAIL_MAILER=smtp

# Remove any existing MAIL_HOST variable first, then add:
MAIL_HOST=smtp-relay.brevo.com

# Remove any existing MAIL_PORT variable first, then add:
MAIL_PORT=587

# Remove any existing MAIL_USERNAME variable first, then add:
MAIL_USERNAME=98f98d001@smtp-brevo.com

# Remove any existing MAIL_PASSWORD variable first, then add:
MAIL_PASSWORD=J9VE1v50n72BTSm6

# Remove any existing MAIL_ENCRYPTION variable first, then add:
MAIL_ENCRYPTION=tls

# Remove any existing MAIL_FROM_ADDRESS variable first, then add:
MAIL_FROM_ADDRESS=johnninonavares7@gmail.com

# Remove any existing MAIL_FROM_NAME variable first, then add:
MAIL_FROM_NAME=SkillsXchange
```

### **Remove These Variables (if they exist):**
```bash
# DELETE these variables if they exist:
MAILGUN_DOMAIN
MAILGUN_SECRET
MAILGUN_ENDPOINT
```

---

## 🔧 **Step 2: Clear Configuration Cache**

After updating the environment variables, you need to clear the configuration cache:

### **Option A: Manual Deploy (Recommended)**
1. In Render dashboard, go to your service
2. Click **"Manual Deploy"**
3. Select **"Deploy latest commit"**
4. This will clear all caches and apply new configuration

### **Option B: Add Cache Clear Command**
Add this environment variable to force cache clearing:
```bash
CACHE_CLEAR=true
```

---

## 🔧 **Step 3: Verify Configuration**

### **Check Render Logs:**
1. Go to your service in Render dashboard
2. Click **"Logs"** tab
3. Look for any email-related errors
4. The logs should show Brevo SMTP connection, not Mailgun

### **Test Email Functionality:**
1. Go to your deployed application
2. Try to register a new user
3. Check if email verification works
4. Monitor logs for any connection errors

---

## 🎯 **Expected Results After Fix:**

### **✅ Success Indicators:**
- No more "smtp.mailgun.org:587" connection timeout errors
- Email verification emails are sent successfully
- Password reset emails work
- Logs show "smtp-relay.brevo.com" connections

### **❌ If Still Getting Errors:**
1. Double-check all environment variables are set correctly
2. Ensure no Mailgun variables remain
3. Try manual deploy to clear all caches
4. Check if there are any hardcoded Mailgun references in the code

---

## 📋 **Complete Environment Variables List:**

Here's the complete list of environment variables your Render service should have:

```bash
# App Configuration
APP_NAME=SkillsXchange
APP_ENV=production
APP_KEY=[auto-generated]
APP_DEBUG=false
APP_URL=https://skillsxchange-crus.onrender.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=yamanote.proxy.rlwy.net
DB_PORT=45822
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=nBMPUzSWZaJhIrrmNKWhiSoFMgfsBBqI

# Brevo Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=98f98d001@smtp-brevo.com
MAIL_PASSWORD=J9VE1v50n72BTSm6
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=johnninonavares7@gmail.com
MAIL_FROM_NAME=SkillsXchange

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

---

## 🚀 **Quick Fix Summary:**

1. **Update Render Environment Variables** with Brevo configuration
2. **Remove any Mailgun variables**
3. **Manual Deploy** to clear caches
4. **Test email functionality**
5. **Monitor logs** for success

This should completely resolve the Mailgun connection timeout error and enable proper email functionality with Brevo SMTP.

**Status: ✅ READY TO APPLY**
