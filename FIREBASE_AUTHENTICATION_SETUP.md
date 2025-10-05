# Firebase Authentication Setup Guide

This guide will help you complete the Firebase Authentication integration for your SkillsXchange application.

## Prerequisites

1. Firebase project already configured (✅ Done)
2. Firebase configuration files created (✅ Done)
3. Laravel application with Firebase integration code (✅ Done)

## Setup Steps

### 1. Database Migration

Run the migration to add Firebase fields to your users table:

```bash
php artisan migrate
```

This will add the following fields to your `users` table:
- `firebase_uid` (string, nullable, unique)
- `firebase_provider` (string, nullable)

### 2. Environment Configuration

Add these variables to your `.env` file:

```env
# Firebase Configuration
FIREBASE_PROJECT_ID=skillsxchange-26855
FIREBASE_API_KEY=AIzaSyAL1qfUGstU2DzY864pTzZwxf812JN4jkM
FIREBASE_AUTH_DOMAIN=skillsxchange-26855.firebaseapp.com
FIREBASE_DATABASE_URL=https://skillsxchange-26855-default-rtdb.asia-southeast1.firebasedatabase.app
FIREBASE_STORAGE_BUCKET=skillsxchange-26855.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=61175608249
FIREBASE_APP_ID=1:61175608249:web:ebd30cdd178d9896d2fc68
FIREBASE_MEASUREMENT_ID=G-V1WLV98X63

# Firebase Authentication Settings
FIREBASE_AUTH_ENABLED=true
FIREBASE_EMAIL_VERIFICATION_REQUIRED=false
```

### 3. Firebase Console Configuration

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project: `skillsxchange-26855`
3. Navigate to **Authentication** > **Sign-in method**
4. Enable the following providers:
   - **Email/Password** (enable)
   - **Google** (enable and configure OAuth consent screen)

### 4. Google OAuth Setup (Optional)

If you want to enable Google sign-in:

1. In Firebase Console, go to **Authentication** > **Sign-in method**
2. Click on **Google** provider
3. Enable it and configure:
   - Project support email
   - Authorized domains (add your domain)
4. Save the configuration

### 5. Testing the Integration

1. **Access Firebase Login Page:**
   ```
   https://your-domain.com/firebase-login
   ```

2. **Test Email/Password Authentication:**
   - Create a new account using email/password
   - Sign in with existing credentials
   - Test password reset functionality

3. **Test Google Authentication (if enabled):**
   - Click "Sign in with Google"
   - Complete OAuth flow
   - Verify user is created in your Laravel database

### 6. Features Implemented

✅ **Email/Password Authentication**
- User registration with email/password
- User login with email/password
- Password reset functionality
- Email verification (optional)

✅ **Google OAuth Authentication**
- One-click Google sign-in
- Automatic user creation
- Profile data synchronization

✅ **Laravel Integration**
- Firebase token verification
- User creation/update in Laravel database
- Session management
- Middleware protection

✅ **UI Components**
- Firebase login page (`/firebase-login`)
- Integration with existing login page
- Responsive design
- Error handling and loading states

### 7. API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/firebase-login` | Firebase authentication page |
| POST | `/auth/firebase/callback` | Handle Firebase authentication |
| POST | `/auth/firebase/logout` | Firebase logout |
| GET | `/auth/firebase/user` | Get current user info |

### 8. Database Schema

The migration adds these fields to the `users` table:

```sql
ALTER TABLE users ADD COLUMN firebase_uid VARCHAR(255) NULL UNIQUE;
ALTER TABLE users ADD COLUMN firebase_provider VARCHAR(255) NULL;
CREATE INDEX idx_firebase_uid_provider ON users(firebase_uid, firebase_provider);
```

### 9. Security Considerations

1. **Token Verification:** The current implementation uses a simplified token verification for development. For production, implement proper Firebase Admin SDK verification.

2. **CORS Configuration:** Ensure your Firebase project allows your domain in CORS settings.

3. **Environment Variables:** Keep your Firebase configuration secure and never commit API keys to version control.

### 10. Troubleshooting

**Common Issues:**

1. **"Firebase not loaded" error:**
   - Check if Firebase CDN scripts are loading
   - Verify internet connection
   - Check browser console for errors

2. **"Invalid Firebase token" error:**
   - Verify Firebase configuration
   - Check if user is properly authenticated
   - Ensure token is being passed correctly

3. **Database connection errors:**
   - Run the migration: `php artisan migrate`
   - Check database configuration
   - Verify user table exists

4. **CORS errors:**
   - Add your domain to Firebase authorized domains
   - Check Firebase project settings

### 11. Production Deployment

1. **Update Firebase Configuration:**
   - Use production Firebase project
   - Update environment variables
   - Configure authorized domains

2. **Security Hardening:**
   - Implement proper token verification
   - Add rate limiting
   - Enable email verification
   - Configure CORS properly

3. **Monitoring:**
   - Set up Firebase Analytics
   - Monitor authentication logs
   - Track user registration/login metrics

## Next Steps

1. Run the database migration
2. Update your `.env` file with Firebase configuration
3. Configure Firebase Console settings
4. Test the authentication flow
5. Deploy to production with proper security settings

## Support

If you encounter any issues:

1. Check the browser console for JavaScript errors
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify Firebase Console configuration
4. Test with different browsers/incognito mode

The Firebase Authentication integration is now ready to use! 🚀
