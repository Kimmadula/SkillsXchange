# 🔧 SkillsXchange Environment Variables Setup Guide

This guide contains the **exact values** you need to set up your environment variables for Railway and Render.

## 🚀 **Railway Environment Variables**

Go to [Railway Dashboard](https://railway.app) → Your Project → SkillsXchange Service → Variables Tab

### **Core Application Variables:**
| Variable Name | Value | Description |
|---------------|-------|-------------|
| `APP_NAME` | `SkillsXchange` | Application name |
| `APP_ENV` | `production` | Environment mode |
| `APP_DEBUG` | `false` | Debug mode (false for production) |
| `APP_URL` | `https://your-railway-app.railway.app` | Your Railway app URL (will be generated) |
| `LOG_CHANNEL` | `stderr` | Logging channel |

### **Database Variables (Railway MySQL):**
| Variable Name | Value | Description |
|---------------|-------|-------------|
| `DB_CONNECTION` | `mysql` | Database type |
| `DB_HOST` | `mysql.railway.internal` | Internal database host |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `railway` | Database name |
| `DB_USERNAME` | `root` | Database username |
| `DB_PASSWORD` | `nBMPUzSWZaJhIrrmNKWhiSoFMgfsBBqI` | Database password |

### **Pusher Variables (Real-time Features):**
| Variable Name | Value | Description |
|---------------|-------|-------------|
| `BROADCAST_DRIVER` | `pusher` | Broadcasting driver |
| `PUSHER_APP_ID` | `2047345` | Pusher app ID |
| `PUSHER_APP_KEY` | `5c02e54d01ca577ae77e` | Pusher app key |
| `PUSHER_APP_SECRET` | `3ad793a15a653af09cd6` | Pusher app secret |
| `PUSHER_APP_CLUSTER` | `ap1` | Pusher cluster |
| `VITE_PUSHER_APP_KEY` | `5c02e54d01ca577ae77e` | Frontend Pusher key |
| `VITE_PUSHER_APP_CLUSTER` | `ap1` | Frontend Pusher cluster |

### **Laravel Configuration Variables:**
| Variable Name | Value | Description |
|---------------|-------|-------------|
| `CACHE_DRIVER` | `file` | Cache driver |
| `SESSION_DRIVER` | `file` | Session driver |
| `SESSION_LIFETIME` | `120` | Session lifetime in minutes |
| `QUEUE_CONNECTION` | `sync` | Queue connection |
| `MAIL_MAILER` | `log` | Mail driver |
| `FILESYSTEM_DISK` | `public` | File system disk |

---

## 🌐 **Render Environment Variables**

Go to [Render Dashboard](https://render.com) → skillsxchange-13vk Service → Environment Tab

### **Core Application Variables:**
| Variable Name | Value | Description |
|---------------|-------|-------------|
| `APP_NAME` | `SkillsXchange` | Application name |
| `APP_ENV` | `production` | Environment mode |
| `VITE_APP_ENV` | `production` | Vite environment |
| `APP_DEBUG` | `false` | Debug mode |
| `APP_URL` | `https://skillsxchange-13vk.onrender.com` | Your Render app URL |
| `LOG_CHANNEL` | `stderr` | Logging channel |

### **Database Variables (Railway MySQL via Public Proxy):**
| Variable Name | Value | Description |
|---------------|-------|-------------|
| `DB_CONNECTION` | `mysql` | Database type |
| `DB_HOST` | `yamanote.proxy.rlwy.net` | Public database host |
| `DB_PORT` | `45822` | Database port |
| `DB_DATABASE` | `railway` | Database name |
| `DB_USERNAME` | `root` | Database username |
| `DB_PASSWORD` | `nBMPUzSWZaJhIrrmNKWhiSoFMgfsBBqI` | Database password |

### **Pusher Variables (Real-time Features):**
| Variable Name | Value | Description |
|---------------|-------|-------------|
| `BROADCAST_DRIVER` | `pusher` | Broadcasting driver |
| `PUSHER_APP_ID` | `2047345` | Pusher app ID |
| `PUSHER_APP_KEY` | `5c02e54d01ca577ae77e` | Pusher app key |
| `PUSHER_APP_SECRET` | `3ad793a15a653af09cd6` | Pusher app secret |
| `PUSHER_APP_CLUSTER` | `ap1` | Pusher cluster |
| `VITE_PUSHER_APP_KEY` | `5c02e54d01ca577ae77e` | Frontend Pusher key |
| `VITE_PUSHER_APP_CLUSTER` | `ap1` | Frontend Pusher cluster |

### **Laravel Configuration Variables:**
| Variable Name | Value | Description |
|---------------|-------|-------------|
| `CACHE_DRIVER` | `file` | Cache driver |
| `SESSION_DRIVER` | `file` | Session driver |
| `SESSION_LIFETIME` | `120` | Session lifetime in minutes |
| `QUEUE_CONNECTION` | `sync` | Queue connection |
| `MAIL_MAILER` | `log` | Mail driver |
| `FILESYSTEM_DISK` | `public` | File system disk |

---

## 🔥 **Firebase Configuration (Already Set Up)**

Your Firebase configuration is already correctly set up in `public/firebase-config.js`:

```javascript
const firebaseConfig = {
    apiKey: "AIzaSyAL1qfUGstU2DzY864pTzZwxf812JN4jkM",
    authDomain: "skillsxchange-26855.firebaseapp.com",
    databaseURL: "https://skillsxchange-26855-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "skillsxchange-26855",
    storageBucket: "skillsxchange-26855.firebasestorage.app",
    messagingSenderId: "61175608249",
    appId: "1:61175608249:web:ebd30cdd178d9896d2fc68",
    measurementId: "G-V1WLV98X63"
};
```

**No additional environment variables needed for Firebase** - it's configured directly in the JavaScript file.

---

## 📋 **Step-by-Step Setup Instructions**

### **For Railway:**
1. Go to [Railway Dashboard](https://railway.app)
2. Select your project → SkillsXchange service
3. Click **"Variables"** tab
4. Click **"+ New Variable"** for each variable above
5. Copy and paste the exact values from the Railway table
6. Deploy your service

### **For Render:**
1. Go to [Render Dashboard](https://render.com)
2. Select your **skillsxchange-13vk** service
3. Click **"Environment"** tab
4. Click **"Add Environment Variable"** for each variable above
5. Copy and paste the exact values from the Render table
6. Redeploy your service

---

## 🧪 **Testing Your Setup**

After setting up all variables, test these endpoints:

### **Railway:**
- Health: `https://your-railway-app.railway.app/health`
- Database: `https://your-railway-app.railway.app/test-db`
- Debug: `https://your-railway-app.railway.app/debug`

### **Render:**
- Health: `https://skillsxchange-13vk.onrender.com/health`
- Database: `https://skillsxchange-13vk.onrender.com/test-db`
- Debug: `https://skillsxchange-13vk.onrender.com/debug`

---

## 🎯 **Features That Will Work After Setup**

✅ **User Registration & Login**
✅ **Skill Trading System**
✅ **Real-time Chat (Pusher)**
✅ **Video Calling (Firebase)**
✅ **Task Management**
✅ **Admin Panel**
✅ **Notifications**

---

## 🚨 **Important Notes**

1. **Copy values exactly** - no extra spaces or quotes
2. **Railway uses internal database connection** (faster)
3. **Render uses public proxy connection** (accessible from anywhere)
4. **Firebase is already configured** - no environment variables needed
5. **Pusher credentials are the same** for both platforms

---

## 🎉 **You're All Set!**

Once you've added all these environment variables to both Railway and Render, your SkillsXchange application will be fully functional with:

- ✅ Database connectivity
- ✅ Real-time features
- ✅ Video calling
- ✅ All core functionality

Your application will be live at:
- **Railway**: `https://your-railway-app.railway.app`
- **Render**: `https://skillsxchange-13vk.onrender.com`
