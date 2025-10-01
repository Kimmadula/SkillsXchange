<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <title>SkillsXchange - Trade Your Skills</title>
  <meta name="description"
    content="Connect with students to trade skills and learn together. A peer-to-peer learning platform for skill exchange.">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">

  {{-- Always include Bootstrap CDN for reliability --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  {{-- Try Vite first, fallback to built assets --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- Fallback for production if Vite fails --}}
  @if(app()->environment('production'))
  @php
  $manifestPath = public_path('build/manifest.json');
  if (file_exists($manifestPath)) {
  $manifest = json_decode(file_get_contents($manifestPath), true);
  $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
  $jsFile = $manifest['resources/js/app.js']['file'] ?? null;
  }
  @endphp
  @if(isset($cssFile) && file_exists(public_path('build/' . $cssFile)))
  <link rel="stylesheet" href="{{ asset('build/' . $cssFile) }}">
  @else
  {{-- Additional fallback CSS for Render deployment --}}
  <link rel="stylesheet" href="{{ asset('css/fallback.css') }}">
  @endif

  @if(isset($jsFile) && file_exists(public_path('build/' . $jsFile)))
  <script src="{{ asset('build/' . $jsFile) }}"></script>
  @else
  {{-- Bootstrap JS CDN fallback --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  @endif
  @endif

  <!-- Always include Bootstrap JS for reliability -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .hero-gradient {
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    }

    .btn-primary {
      background-color: #2563eb;
      border-color: #2563eb;
      font-weight: 600;
    }

    .btn-primary:hover {
      background-color: #1d4ed8;
      border-color: #1d4ed8;
    }

    .btn-outline-secondary {
      color: #6b7280;
      border-color: #e5e7eb;
      font-weight: 500;
    }

    .btn-outline-secondary:hover {
      background-color: #f9fafb;
      border-color: #d1d5db;
      color: #374151;
    }

    .feature-icon {
      width: 50px;
      height: 50px;
      background: #3b82f6;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
    }

    .feature-icon svg {
      width: 24px;
      height: 24px;
      color: white;
    }

    .feature-icon.search {
      background: #2563eb;
    }

    .feature-icon.match {
      background: #2563eb;
    }

    .feature-icon.rate {
      background: #2563eb;
    }
  </style>
</head>

<body>
  <!-- Header -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold text-primary fs-5" href="/">
        <img src="{{ asset('logo.png') }}" alt="SkillsXchange Logo" class="me-2" style="width: 100px; height: 100px;">
        SkillsXchange
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link text-muted" href="#how-it-works">How It Works</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-muted" href="#about">About</a>
          </li>
        </ul>

        <div class="d-flex ms-3">
          <a href="{{ route('login') }}" class="btn btn-outline-secondary me-2">Login</a>
          <a href="{{ route('register') }}" class="btn btn-primary">Sign Up</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-gradient py-5 d-flex align-items-center" style="min-height: 60vh;">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
          <h1 class="display-4 fw-bold text-dark mb-4">
            Trade Your Skills. Learn from Others.
          </h1>
          <p class="lead text-muted mb-5">
            A student-to-student platform to share and grow your skills together.
          </p>
          <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4 py-3">
            Get Started
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section id="how-it-works" class="py-5 bg-white">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 text-center mb-5">
          <h2 class="display-5 fw-bold text-dark mb-4">How It Works</h2>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-lg-4">
          <div class="text-center">
            <div class="feature-icon search">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">
                </path>
              </svg>
            </div>
            <h3 class="h4 fw-bold text-dark mb-3">Find a Skill</h3>
            <p class="text-muted">
              Browse through the list of skills offered by other students.
            </p>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="text-center">
            <div class="feature-icon match">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                </path>
              </svg>
            </div>
            <h3 class="h4 fw-bold text-dark mb-3">Match and Trade</h3>
            <p class="text-muted">
              Send a trade request and match based on availability and interest.
            </p>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="text-center">
            <div class="feature-icon rate">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                </path>
              </svg>
            </div>
            <h3 class="h4 fw-bold text-dark mb-3">Rate and Review</h3>
            <p class="text-muted">
              Leave feedback and build a trustworthy skill-sharing community.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-dark text-white py-4">
    <div class="container">
      <div class="row">
        <div class="col-12 text-center">
          <div class="fw-bold text-primary mb-2">SkillsXchange</div>
          <p class="text-muted mb-0">Connect, Learn, and Grow Together</p>
        </div>
      </div>
    </div>
  </footer>

  <script>
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
  </script>
</body>

</html>