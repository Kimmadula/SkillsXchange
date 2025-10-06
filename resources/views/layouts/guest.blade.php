<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description"
        content="SkillsXchangee - A legitimate educational platform for skill sharing and learning. Connect with others to exchange knowledge and develop new skills.">
    <meta name="keywords" content="skill exchange, learning, education, knowledge sharing, legitimate platform">
    <meta name="author" content="SkillsXchangee Team">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#3b82f6">

    <title>{{ config('app.name', 'SkillsXchange') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Fallback CSS for production --}}
    @if(app()->environment('production'))
    <link rel="stylesheet" href="{{ asset('css/fallback.css') }}">
    @endif

    <!-- Clean minimalist styles for auth pages -->
    <style>
        .auth-container {
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
        }

        .auth-logo {
            width: 100px;
            height: 100px;
            background: #fff;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .auth-logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .auth-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            width: 100%;
            max-width: 900px;
            max-height: 95vh;
            overflow-y: auto;
        }

        /* Login form specific styles */
        .auth-card.login-form {
            max-width: 400px;
            padding: 2rem;
        }

        .auth-card.login-form .form-input {
            padding: 0.75rem;
            font-size: 1rem;
        }

        .auth-card.login-form .form-group {
            margin-bottom: 1rem;
        }

        .auth-card.login-form .form-label {
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .form-input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
        }

        .form-input.border-red-500 {
            border-color: #dc3545;
        }

        .form-input.border-green-500 {
            border-color: #28a745;
        }

        .btn-primary {
            background: #333;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .btn-primary:hover {
            background: #555;
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.875rem;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-secondary:hover {
            background: #545b62;
            color: white;
        }

        .form-label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 0.75rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .form-row-full {
            grid-column: 1 / -1;
        }

        .form-row-triple {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        @media (max-width: 768px) {

            .form-row,
            .form-row-triple {
                grid-template-columns: 1fr;
            }
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 0.5rem;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .form-footer-left {
            display: flex;
            align-items: center;
        }

        .form-footer-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-footer a {
            color: #007bff;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        /* Firebase Authentication Styles */
        .auth-divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }

        .auth-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e5e7eb;
        }

        .auth-divider span {
            background: #f5f5f5;
            padding: 0 1rem;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .google-auth-section {
            text-align: center;
            margin-top: 1rem;
        }

        .btn-google {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            background: white;
            color: #333;
            border: 1px solid #dadce0;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            width: 100%;
            max-width: 300px;
        }

        .btn-google:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            background: #f8f9fa;
        }

        .btn-google:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .google-icon {
            margin-right: 0.75rem;
        }

        .google-description {
            margin-top: 0.75rem;
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.4;
        }

        @media (max-width: 640px) {
            .auth-container {
                padding: 1rem;
            }

            .auth-card {
                padding: 1.5rem;
            }

            .auth-logo {
                width: 80px;
                height: 80px;
            }

            .auth-logo img {
                width: 60px;
                height: 60px;
            }
        }
    </style>
    
       <!-- Firebase SDK -->
       <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
       <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
       <script src="{{ asset('firebase-config.js') }}"></script>
</head>

<body class="font-sans text-gray-900 antialiased">
    <div class="auth-container">
        <div class="auth-logo">
            <img src="{{ asset('logo.png') }}" alt="SkillsXchange Logo" class="logo-medium"
                style="width: 100%; height: 100%; object-fit: contain;">
        </div>

        <div class="auth-card {{ request()->routeIs('login') ? 'login-form' : '' }}">
            {{ $slot }}
        </div>
    </div>
</body>

</html>