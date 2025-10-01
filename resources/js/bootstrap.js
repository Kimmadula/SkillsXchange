import _ from 'lodash';
window._ = _;

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Initialize Echo with proper error handling
try {
    // Get Pusher configuration from environment or use defaults
    const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY || window.PUSHER_APP_KEY || '5c02e54d01ca577ae77e';
    const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || window.PUSHER_APP_CLUSTER || 'ap1';
    
    console.log('🔧 Pusher Configuration:');
    console.log('🔑 Key:', pusherKey);
    console.log('🌐 Cluster:', pusherCluster);
    console.log('📡 Environment Vars:', {
        VITE_PUSHER_APP_KEY: import.meta.env.VITE_PUSHER_APP_KEY,
        VITE_PUSHER_APP_CLUSTER: import.meta.env.VITE_PUSHER_APP_CLUSTER
    });
    
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
        },
    });
    
    console.log('✅ Laravel Echo initialized successfully with Pusher');
    
    // Test Pusher connection
    if (window.Echo) {
        console.log('🎉 Pusher connection established');
    }
} catch (error) {
    console.error('❌ Failed to initialize Laravel Echo:', error);
    console.error('🔍 Error details:', error);
    window.Echo = null;
}
