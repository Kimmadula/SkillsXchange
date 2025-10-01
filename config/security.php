<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the
    | SkillsXchange application to prevent blocking by firewalls and
    | anti-malware systems.
    |
    */

    'headers' => [
        /*
        |--------------------------------------------------------------------------
        | Security Headers
        |--------------------------------------------------------------------------
        |
        | These headers help establish trust with security tools and prevent
        | false positive blocking by firewalls and anti-malware systems.
        |
        */
        'x_content_type_options' => 'nosniff',
        'x_frame_options' => 'SAMEORIGIN',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'strict_transport_security' => 'max-age=31536000; includeSubDomains; preload',
        
        'permissions_policy' => [
            'geolocation' => '()',
            'microphone' => '(self)',
            'camera' => '(self)',
            'payment' => '()',
            'usb' => '()',
            'magnetometer' => '()',
            'gyroscope' => '()',
            'accelerometer' => '()',
            'fullscreen' => '(self)',
            'display-capture' => '(self)',
        ],
    ],

    'csp' => [
        /*
        |--------------------------------------------------------------------------
        | Content Security Policy
        |--------------------------------------------------------------------------
        |
        | Comprehensive CSP to allow necessary resources while maintaining
        | security. This prevents XSS attacks and establishes trust.
        |
        */
        'default_src' => "'self'",
        'script_src' => [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            'https://cdnjs.cloudflare.com',
            'https://cdn.jsdelivr.net',
            'https://fonts.bunny.net',
            'https://js.pusher.com',
            'https://www.gstatic.com',
            'https://*.firebaseio.com',
            'https://*.firebasedatabase.app',
            'https://*.googleapis.com',
            'https://unpkg.com',
        ],
        'style_src' => [
            "'self'",
            "'unsafe-inline'",
            'https://cdnjs.cloudflare.com',
            'https://cdn.jsdelivr.net',
            'https://fonts.bunny.net',
            'https://fonts.googleapis.com',
        ],
        'img_src' => [
            "'self'",
            'data:',
            'https:',
            'blob:',
        ],
        'font_src' => [
            "'self'",
            'https://cdnjs.cloudflare.com',
            'https://cdn.jsdelivr.net',
            'https://fonts.bunny.net',
            'https://fonts.gstatic.com',
        ],
        'connect_src' => [
            "'self'",
            'wss:',
            'https:',
            'wss://*.pusher.com',
            'wss://*.pusherapp.com',
            'https://*.pusher.com',
            'https://*.pusherapp.com',
            'https://*.firebaseio.com',
            'https://*.firebasedatabase.app',
            'https://stun.l.google.com',
            'https://stun1.l.google.com',
            'https://stun2.l.google.com',
            'https://stun3.l.google.com',
            'https://stun4.l.google.com',
            'https://turn.relay.metered.ca',
            'https://asia.relay.metered.ca',
            'https://europe.relay.metered.ca',
            'https://us.relay.metered.ca',
        ],
        'media_src' => [
            "'self'",
            'blob:',
            'data:',
        ],
        'frame_src' => [
            "'self'",
            'https://*.pusher.com',
        ],
        'object_src' => "'none'",
        'base_uri' => "'self'",
        'form_action' => "'self'",
        'frame_ancestors' => "'self'",
        'upgrade_insecure_requests' => true,
    ],

    'rate_limiting' => [
        /*
        |--------------------------------------------------------------------------
        | Rate Limiting Configuration
        |--------------------------------------------------------------------------
        |
        | Different rate limits for different types of requests to prevent
        | abuse while allowing legitimate usage.
        |
        */
        'api' => [
            'max_attempts' => 60,
            'decay_minutes' => 15,
        ],
        'auth' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
        'video_call' => [
            'max_attempts' => 30,
            'decay_minutes' => 15,
        ],
        'chat' => [
            'max_attempts' => 100,
            'decay_minutes' => 15,
        ],
        'general' => [
            'max_attempts' => 200,
            'decay_minutes' => 15,
        ],
    ],

    'ddos_protection' => [
        /*
        |--------------------------------------------------------------------------
        | DDoS Protection Configuration
        |--------------------------------------------------------------------------
        |
        | Configuration for DDoS protection and suspicious activity detection.
        |
        */
        'max_requests_per_minute' => 100,
        'suspicious_patterns' => [
            '/\.\./',  // Directory traversal
            '/<script/i',  // XSS attempts
            '/union\s+select/i',  // SQL injection
            '/drop\s+table/i',  // SQL injection
            '/exec\s*\(/i',  // Command injection
            '/eval\s*\(/i',  // Code injection
            '/javascript:/i',  // JavaScript injection
            '/vbscript:/i',  // VBScript injection
            '/onload=/i',  // Event handler injection
            '/onerror=/i',  // Event handler injection
        ],
        'suspicious_user_agents' => [
            'sqlmap',
            'nikto',
            'nmap',
            'masscan',
            'zap',
            'burp',
            'w3af',
            'acunetix',
            'nessus',
            'openvas',
            'skipfish',
            'dirb',
            'gobuster',
            'wfuzz',
            'ffuf',
        ],
    ],

    'application_identification' => [
        /*
        |--------------------------------------------------------------------------
        | Application Identification
        |--------------------------------------------------------------------------
        |
        | Headers to identify the application as legitimate to security tools.
        |
        */
        'name' => 'SkillsXchange - Educational Skill Exchange Platform',
        'version' => '1.0.0',
        'type' => 'Educational Platform',
        'purpose' => 'Skill Learning and Exchange',
        'content_type' => 'Educational Web Application',
    ],

    'cors' => [
        /*
        |--------------------------------------------------------------------------
        | CORS Configuration
        |--------------------------------------------------------------------------
        |
        | Cross-Origin Resource Sharing configuration for security.
        |
        */
        'allowed_origins' => [
            'https://skillsxchange-13vk.onrender.com',
            'https://your-railway-app.railway.app',
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'X-CSRF-TOKEN',
        ],
        'allow_credentials' => true,
        'max_age' => 86400,
    ],
];
