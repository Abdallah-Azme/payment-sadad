<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'SADAD Secure Payment Gateway')">
    <title>@yield('title', 'Secure Payment') — SADAD</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/payment.css') }}">

    @stack('head')
</head>
<body>

    {{-- Animated gradient background orbs --}}
    <div class="bg-orb bg-orb--1" aria-hidden="true"></div>
    <div class="bg-orb bg-orb--2" aria-hidden="true"></div>
    <div class="bg-orb bg-orb--3" aria-hidden="true"></div>

    <div class="payment-wrapper">

        {{-- Header --}}
        <header class="payment-header">
            <div class="payment-header__brand">
                <div class="brand-icon" aria-hidden="true">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <rect width="32" height="32" rx="8" fill="url(#brandGrad)"/>
                        <path d="M8 16l5 5 11-11" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs>
                            <linearGradient id="brandGrad" x1="0" y1="0" x2="32" y2="32">
                                <stop offset="0%" stop-color="#6366f1"/>
                                <stop offset="100%" stop-color="#8b5cf6"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <span class="brand-name">SecurePay</span>
            </div>
            <div class="payment-header__secure">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <span>256-bit SSL Secured</span>
            </div>
        </header>

        {{-- Main content --}}
        <main class="payment-main">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="payment-footer">
            <div class="payment-footer__badges">
                <span class="badge">🔒 SSL Encrypted</span>
                <span class="badge">✓ PCI DSS Compliant</span>
                <span class="badge">🛡️ SADAD Secured</span>
            </div>
            <p class="payment-footer__copy">Powered by <strong>SADAD Web Checkout 2.1</strong> · No card data stored on our servers</p>
        </footer>

    </div>

    @stack('scripts')
</body>
</html>
