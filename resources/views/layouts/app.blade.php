<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Always include Bootstrap CDN for reliability -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Pusher Configuration -->
    <script>
        // Use config values instead of env() for production reliability
        window.PUSHER_APP_KEY = '{{ config("broadcasting.connections.pusher.key", "5c02e54d01ca577ae77e") }}';
        window.PUSHER_APP_CLUSTER = '{{ config("broadcasting.connections.pusher.options.cluster", "ap1") }}';
        
        // Fallback to hardcoded values if config fails
        if (!window.PUSHER_APP_KEY || window.PUSHER_APP_KEY === 'null') {
            window.PUSHER_APP_KEY = '5c02e54d01ca577ae77e';
        }
        if (!window.PUSHER_APP_CLUSTER || window.PUSHER_APP_CLUSTER === 'null') {
            window.PUSHER_APP_CLUSTER = 'ap1';
        }
        
        console.log('🔧 Pusher Configuration:');
        console.log('🔑 Key:', window.PUSHER_APP_KEY);
        console.log('🌐 Cluster:', window.PUSHER_APP_CLUSTER);
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-analytics-compat.js"></script>
    
    <!-- Firebase Configuration -->
    <script src="{{ asset('firebase-config.js') }}"></script>

    <!-- Notification Service (Inline to avoid Vite manifest issues) -->
    <script src="{{ asset('js/notification-service.js') }}"></script>

    {{-- Fallback for production if Vite fails --}}
    @if(app()->environment('production'))
    @php
    $manifestPath = public_path('build/manifest.json');
    $cssFile = null;
    $jsFile = null;

    if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
    $jsFile = $manifest['resources/js/app.js']['file'] ?? null;
    }
    @endphp

    @if(isset($cssFile) && file_exists(public_path('build/' . $cssFile)))
    <link rel="stylesheet" href="{{ secure_asset('build/' . $cssFile) }}">
    @else
    {{-- Bootstrap CDN fallback for Render deployment --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ secure_asset('css/fallback.css') }}">
    @endif

    @if(isset($jsFile) && file_exists(public_path('build/' . $jsFile)))
    <script src="{{ secure_asset('build/' . $jsFile) }}"></script>
    @else
    {{-- Bootstrap JS CDN fallback --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @endif
    @endif

    <!-- Always include Bootstrap JS for reliability -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Enhanced Session Monitor -->
    @auth
    <script src="{{ asset('js/session-monitor-enhanced.js') }}"></script>
    @endauth
</head>

<body class="bg-light">
    <div class="min-vh-100 d-flex flex-column">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @if (isset($header))
        <header class="bg-white shadow-sm border-bottom">
            <div class="container py-4">
                {{ $header }}
            </div>
        </header>
        @endif

        <!-- Page Content -->
        <main class="flex-grow-1">
            @yield('content')
        </main>
    </div>
</body>

</html>