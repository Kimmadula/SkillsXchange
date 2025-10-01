# 🔒 Firebase Security Rules Setup Guide

## ⚠️ CRITICAL SECURITY ISSUE

Your Firebase Realtime Database currently has **public security rules**, which means anyone can read/write to your database. This is a major security vulnerability for your video call system.

## 🛠️ How to Fix

### Step 1: Access Firebase Console
1. Go to [Firebase Console](https://console.firebase.google.com)
2. Select your project: `skillsxchange-c2604`
3. Navigate to **Realtime Database** → **Rules**

### Step 2: Replace Security Rules
Replace the current rules with these secure rules:

```json
{
  "rules": {
    "video_rooms": {
      "$roomId": {
        ".read": "auth != null && (data.child('metadata/user1').val() == auth.uid || data.child('metadata/user2').val() == auth.uid)",
        ".write": "auth != null && (data.child('metadata/user1').val() == auth.uid || data.child('metadata/user2').val() == auth.uid)",
        "metadata": {
          ".read": "auth != null && (data.child('user1').val() == auth.uid || data.child('user2').val() == auth.uid)",
          ".write": "auth != null && (data.child('user1').val() == auth.uid || data.child('user2').val() == auth.uid)"
        },
        "users": {
          "$userId": {
            ".read": "auth != null && (data.parent().parent().child('metadata/user1').val() == auth.uid || data.parent().parent().child('metadata/user2').val() == auth.uid)",
            ".write": "auth != null && (data.parent().parent().child('metadata/user1').val() == auth.uid || data.parent().parent().child('metadata/user2').val() == auth.uid)"
          }
        },
        "calls": {
          "$callId": {
            ".read": "auth != null && (data.parent().parent().child('metadata/user1').val() == auth.uid || data.parent().parent().child('metadata/user2').val() == auth.uid)",
            ".write": "auth != null && (data.parent().parent().child('metadata/user1').val() == auth.uid || data.parent().parent().child('metadata/user2').val() == auth.uid)"
          }
        }
      }
    },
    "test_connection": {
      ".read": "auth != null",
      ".write": "auth != null"
    },
    "security_test": {
      ".read": "auth != null",
      ".write": "auth != null"
    }
  }
}
```

### Step 3: Publish Rules
1. Click **"Publish"** to apply the new rules
2. Confirm the changes

## 🔐 What These Rules Do

### Video Rooms Security
- **Room Access**: Only users who are part of the room (user1 or user2) can read/write
- **Metadata Protection**: Room metadata is only accessible to room participants
- **User Data**: User presence data is only accessible to room participants
- **Call Data**: Signaling data (offers, answers, ICE candidates) is only accessible to room participants

### Authentication Required
- All operations require authentication (`auth != null`)
- Users can only access rooms they're part of
- No anonymous access allowed

## 🧪 Testing Security

After applying the rules, test them using:
```
https://skillsxchangee-c2ml.onrender.com/test-video-call
```

The security test will verify that:
- ✅ Authenticated users can access their rooms
- ❌ Unauthenticated users cannot access any data
- ❌ Users cannot access rooms they're not part of

## 🚨 Important Notes

1. **Backup First**: Make sure to backup your current rules before changing them
2. **Test Thoroughly**: Test all video call functionality after applying the rules
3. **Monitor Logs**: Check Firebase logs for any access denied errors
4. **User Authentication**: Ensure your Laravel app properly authenticates users with Firebase

## 🔧 Troubleshooting

### If Video Calls Stop Working
1. Check Firebase logs for authentication errors
2. Verify user authentication is working
3. Ensure user IDs match between Laravel and Firebase
4. Test with the comprehensive test page

### If You Get "Permission Denied" Errors
1. Verify the security rules are correctly applied
2. Check that users are properly authenticated
3. Ensure room metadata contains correct user IDs
4. Test with a simple database write operation

## 📞 Support

If you encounter issues:
1. Check the Firebase Console logs
2. Use the comprehensive test page to diagnose
3. Verify your Laravel authentication setup
4. Test with the security test in the comprehensive test page
