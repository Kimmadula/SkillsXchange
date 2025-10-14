<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Chat</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Firebase v9 CDN (Compatibility Version) -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-analytics-compat.js"></script>
    
    <!-- Firebase Configuration -->
    <script src="{{ asset('firebase-config.js') }}"></script>

    <!-- Firebase Video Integration -->
    <script src="{{ asset('firebase-video-integration.js') }}"></script>

    <!-- Fallback for Pusher and Laravel Echo if Vite fails -->
    <script>
        // Check if Echo is loaded after Vite
            setTimeout(() => {
                if (typeof window.Echo === 'undefined') {
                    console.warn('⚠️ Laravel Echo not loaded via Vite, loading fallback...');
                    
                    // Load Pusher
                    if (typeof window.Pusher === 'undefined') {
                        const pusherScript = document.createElement('script');
                        pusherScript.src = 'https://js.pusher.com/8.2.0/pusher.min.js';
                        pusherScript.onload = () => {
                            console.log('✅ Pusher loaded via fallback');
                            loadEchoFallback();
                        };
                        document.head.appendChild(pusherScript);
                    } else {
                        loadEchoFallback();
                    }
                } else {
                    console.log('✅ Laravel Echo loaded via Vite');
                }
            }, 1000);
            
            function loadEchoFallback() {
                if (typeof window.Echo === 'undefined') {
                    const echoScript = document.createElement('script');
                    echoScript.src = 'https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js';
                    echoScript.onload = () => {
                        console.log('✅ Laravel Echo loaded via fallback');
                        initializeEchoFallback();
                    };
                    document.head.appendChild(echoScript);
                }
            }
            
            function initializeEchoFallback() {
                try {
                    window.Echo = new Echo({
                        broadcaster: 'pusher',
                        key: '5c02e54d01ca577ae77e',
                        cluster: 'ap1',
                        forceTLS: true,
                        enabledTransports: ['ws', 'wss'],
                        authEndpoint: '/broadcasting/auth',
                        auth: {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json'
                            }
                        }
                    });
                    console.log('✅ Laravel Echo initialized via fallback');
                } catch (error) {
                    console.error('❌ Failed to initialize Laravel Echo fallback:', error);
                }
            }
    </script>

    <!-- Notification Service for Video Calls -->
    <script src="{{ asset('js/notification-service.js') }}"></script>
</head>

<body class="font-sans antialiased">
    @yield('content')
</body>

</html>