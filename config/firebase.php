<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase services including Authentication,
    | Realtime Database, and other Firebase features.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID', 'skillsxchange-26855'),
    'api_key' => env('FIREBASE_API_KEY', 'AIzaSyAL1qfUGstU2DzY864pTzZwxf812JN4jkM'),
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'skillsxchange-26855.firebaseapp.com'),
    'database_url' => env('FIREBASE_DATABASE_URL', 'https://skillsxchange-26855-default-rtdb.asia-southeast1.firebasedatabase.app'),
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'skillsxchange-26855.firebasestorage.app'),
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', '61175608249'),
    'app_id' => env('FIREBASE_APP_ID', '1:61175608249:web:ebd30cdd178d9896d2fc68'),
    'measurement_id' => env('FIREBASE_MEASUREMENT_ID', 'G-V1WLV98X63'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Admin SDK Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Admin SDK server-side operations.
    | You'll need to download the service account key from Firebase Console.
    |
    */

    'admin' => [
        'service_account_path' => env('FIREBASE_SERVICE_ACCOUNT_PATH', storage_path('app/firebase-service-account.json')),
        'database_url' => env('FIREBASE_DATABASE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Settings
    |--------------------------------------------------------------------------
    |
    | Settings for Firebase Authentication integration.
    |
    */

    'auth' => [
        'enabled' => env('FIREBASE_AUTH_ENABLED', true),
        'providers' => [
            'email' => true,
            'google' => true,
            'facebook' => false,
            'twitter' => false,
            'github' => false,
        ],
        'email_verification_required' => env('FIREBASE_EMAIL_VERIFICATION_REQUIRED', false),
    ],
];
