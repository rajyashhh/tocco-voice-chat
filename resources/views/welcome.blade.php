<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tocco Voice Live — Official Website</title>
    <meta name="description" content="Tocco Voice Live — Go live, connect with people worldwide through live streaming, video calls, and real-time chat. Download now.">
    <meta name="keywords" content="Tocco Voice Live, live streaming, video chat, social app, go live, real-time translation">
    <meta name="author" content="Tocco Voice Live">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Tocco Voice Live — Official Website">
    <meta property="og:description" content="Go live, connect with people worldwide through live streaming, video calls, and real-time chat.">
    <meta property="og:image" content="{{ asset('images/app-logo.png') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="Tocco Voice Live">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Tocco Voice Live — Official Website">
    <meta name="twitter:description" content="Go live, connect with people worldwide through live streaming, video calls, and real-time chat.">
    <meta name="twitter:image" content="{{ asset('images/app-logo.png') }}">
    <link rel="icon" type="image/png" href="{{ getFavIcon() }}">
    <link rel="apple-touch-icon" href="{{ getFavIcon() }}">
    <link rel="manifest" href="{{ route('manifest.json') }}">
    <meta name="theme-color" content="#F0D060">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"SoftwareApplication","name":"Tocco Voice Live","applicationCategory":"SocialNetworkingApplication","operatingSystem":"iOS, Android","description":"Go live, connect with people worldwide through live streaming, video calls, and real-time chat.","offers":{"@type":"Offer","price":"0","priceCurrency":"USD"}}
    </script>
    <style>
        :root {
            --primary: {{ data_get($settings, 'app_primary_color', '#F0D060') }};
            --primary-rgb: 240, 208, 96;
            --bg: #08080d;
            --bg-elevated: #0f0f18;
            --surface: #161622;
            --surface-hover: #1e1e30;
            --border: #2a2a3d;
            --text: #f0f0f5;
            --text-secondary: #9898b0;
            --text-muted: #686880;
            --radius: 16px;
            --max-w: 1200px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg); color: var(--text);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        .scroll-progress {
            position: fixed; top: 0; left: 0;
            width: 0; height: 2px;
            background: var(--primary); z-index: 10001;
            transition: width 0.1s;
        }
        .navbar {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 10000; padding: 16px 24px;
            display: flex; align-items: center; justify-content: space-between;
            transition: all 0.3s; background: transparent;
        }
        .navbar.scrolled {
            background: rgba(8, 8, 13, 0.92);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
        }
        .nav-brand {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }
        .nav-logo {
            width: 36px; height: 36px;
            background: var(--primary); border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .nav-logo img { width: 100%; height: 100%; object-fit: cover; }
        .nav-name {
            font-size: 18px; font-weight: 700;
            color: var(--text); letter-spacing: -0.3px;
        }
        .nav-links {
            display: flex; align-items: center; gap: 32px;
            list-style: none;
        }
        .nav-links a {
            text-decoration: none; color: var(--text-secondary);
            font-size: 14px; font-weight: 500; transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--text); }
        .nav-cta {
            background: var(--primary) !important;
            color: #000 !important;
            padding: 10px 24px !important;
            border-radius: 100px !important;
            font-weight: 600 !important;
            transition: all 0.2s !important;
        }
        .nav-cta:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(var(--primary-rgb), 0.4);
        }
        .mobile-toggle {
            display: none; background: none; border: none;
            cursor: pointer; padding: 8px;
        }
        .mobile-toggle span {
            display: block; width: 22px; height: 2px;
            background: var(--text); margin: 5px 0;
            transition: all 0.3s; border-radius: 2px;
        }
        .mobile-nav {
            display: none; position: fixed; top: 0; right: -100%;
            width: 280px; height: 100vh; background: var(--surface);
            z-index: 10001; padding: 80px 32px 32px;
            transition: right 0.3s; flex-direction: column;
        }
        .mobile-nav.active { right: 0; }
        .mobile-nav a {
            display: block; padding: 16px 0; text-decoration: none;
            color: var(--text-secondary); font-size: 16px; font-weight: 500;
            border-bottom: 1px solid var(--border); transition: color 0.2s;
        }
        .mobile-nav a:hover { color: var(--primary); }
        .mobile-nav-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 10000;
        }
        .mobile-nav-overlay.active { display: block; }
        .hero {
            min-height: 100vh; display: flex; align-items: center;
            padding: 120px 24px 80px; position: relative; overflow: hidden;
        }
        .hero-glow {
            position: absolute; width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(var(--primary-rgb), 0.12) 0%, transparent 70%);
            top: -100px; right: -100px; pointer-events: none;
        }
        .hero-inner {
            max-width: var(--max-w); margin: 0 auto; width: 100%;
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 80px; align-items: center;
        }
        .hero-content { z-index: 2; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(var(--primary-rgb), 0.1);
            border: 1px solid rgba(var(--primary-rgb), 0.2);
            border-radius: 100px; padding: 6px 16px;
            font-size: 13px; font-weight: 500; color: var(--primary);
            margin-bottom: 24px;
        }
        .hero-badge .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--primary); animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .hero h1 {
            font-size: 64px; font-weight: 900; line-height: 1.05;
            letter-spacing: -2px; margin-bottom: 20px;
        }
        .hero h1 .accent { color: var(--primary); }
        .hero-desc {
            font-size: 18px; line-height: 1.7; color: var(--text-secondary);
            margin-bottom: 36px; max-width: 480px;
        }
        .hero-actions { display: flex; gap: 14px; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 14px 32px; border-radius: 100px;
            font-size: 15px; font-weight: 600; text-decoration: none;
            border: none; cursor: pointer; transition: all 0.25s;
        }
        .btn-primary { background: var(--primary); color: #000; }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(var(--primary-rgb), 0.35);
        }
        .btn-secondary {
            background: var(--surface); color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { background: var(--surface-hover); border-color: var(--primary); }
        .hero-visual {
            display: flex; justify-content: center; align-items: center;
            position: relative; z-index: 2;
        }
        .phone-frame {
            width: 280px; height: 580px; background: #1a1a2a;
            border-radius: 36px; border: 3px solid #2a2a3d;
            padding: 12px; position: relative;
            box-shadow: 0 40px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);
            overflow: hidden;
        }
        .phone-notch {
            position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
            width: 100px; height: 22px; background: #0a0a14;
            border-radius: 0 0 14px 14px; z-index: 5;
        }
        .phone-screen {
            width: 100%; height: 100%; border-radius: 26px;
            overflow: hidden; background: #000; position: relative;
        }
        .screenshot-slides { width: 100%; height: 100%; position: relative; }
        .screenshot-slide {
            position: absolute; inset: 0; opacity: 0;
            transition: opacity 0.6s ease;
        }
        .screenshot-slide.active { opacity: 1; }
        .screenshot-slide img { width: 100%; height: 100%; object-fit: cover; }
        .screenshot-slide .placeholder {
            width: 100%; height: 100%;
            background: linear-gradient(135deg, var(--surface) 0%, var(--bg-elevated) 100%);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 12px; color: var(--text-muted); font-size: 13px;
        }
        .screenshot-slide .placeholder .icon { font-size: 40px; opacity: 0.3; }
        .slide-dots {
            position: absolute; bottom: 16px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 6px; z-index: 10;
        }
        .slide-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: rgba(255,255,255,0.3); cursor: pointer;
            transition: all 0.3s;
        }
        .slide-dot.active {
            width: 20px; border-radius: 3px; background: var(--primary);
        }
        .showcase { padding: 100px 24px; background: var(--bg-elevated); }
        .showcase-inner { max-width: var(--max-w); margin: 0 auto; }
        .section-header { text-align: center; margin-bottom: 64px; }
        .section-tag {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13px; font-weight: 600; color: var(--primary);
            text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 16px;
        }
        .section-title {
            font-size: 44px; font-weight: 800; letter-spacing: -1.5px;
            line-height: 1.15; margin-bottom: 16px;
        }
        .section-subtitle {
            font-size: 17px; color: var(--text-secondary);
            max-width: 560px; margin: 0 auto; line-height: 1.6;
        }
        .screenshots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
        }
        .screenshot-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden; transition: all 0.3s;
        }
        .screenshot-card:hover {
            transform: translateY(-4px);
            border-color: rgba(var(--primary-rgb), 0.3);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }
        .screenshot-card .card-image {
            width: 100%; aspect-ratio: 9 / 16; background: var(--bg);
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .screenshot-card .card-image img { width: 100%; height: 100%; object-fit: cover; }
        .screenshot-card .card-image .placeholder {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 8px; color: var(--text-muted); font-size: 13px;
        }
        .screenshot-card .card-image .placeholder .icon { font-size: 32px; opacity: 0.3; }
        .screenshot-card .card-body { padding: 16px 20px; }
        .screenshot-card .card-label {
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 1px; color: var(--primary); margin-bottom: 6px;
        }
        .screenshot-card h3 { font-size: 15px; font-weight: 600; margin-bottom: 4px; }
        .screenshot-card p {
            font-size: 13px; color: var(--text-muted); line-height: 1.5;
        }
        .features { padding: 100px 24px; }
        .features-inner { max-width: var(--max-w); margin: 0 auto; }
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .feature-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 36px 28px;
            transition: all 0.3s; position: relative; overflow: hidden;
        }
        .feature-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0;
            height: 2px; background: var(--primary);
            transform: scaleX(0); transition: transform 0.3s;
        }
        .feature-card:hover::before { transform: scaleX(1); }
        .feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(var(--primary-rgb), 0.2);
        }
        .feature-icon {
            width: 52px; height: 52px;
            background: rgba(var(--primary-rgb), 0.1); border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; margin-bottom: 20px;
        }
        .feature-card h3 { font-size: 18px; font-weight: 700; margin-bottom: 10px; }
        .feature-card p {
            font-size: 14px; line-height: 1.65; color: var(--text-secondary);
        }
        .stats {
            padding: 80px 24px; background: var(--bg-elevated);
            border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
        }
        .stats-inner {
            max-width: var(--max-w); margin: 0 auto;
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 32px; text-align: center;
        }
        .stat-item h2 {
            font-size: 40px; font-weight: 800; color: var(--primary);
            letter-spacing: -1px;
        }
        .stat-item p { font-size: 14px; color: var(--text-secondary); margin-top: 4px; }
        .cta { padding: 100px 24px; text-align: center; }
        .cta-inner { max-width: 640px; margin: 0 auto; }
        .cta h2 { font-size: 40px; font-weight: 800; letter-spacing: -1px; margin-bottom: 16px; }
        .cta p {
            font-size: 17px; color: var(--text-secondary);
            margin-bottom: 40px; line-height: 1.6;
        }
        .store-links { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
        .store-link {
            display: inline-flex; align-items: center; gap: 12px;
            background: #000; border: 1px solid var(--border);
            color: var(--text); padding: 14px 28px; border-radius: 14px;
            text-decoration: none; transition: all 0.25s;
        }
        .store-link:hover {
            transform: translateY(-2px); border-color: var(--primary);
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .store-link .store-icon { width: 28px; height: 28px; }
        .store-link .store-text { text-align: left; }
        .store-link .store-sub {
            font-size: 10px; opacity: 0.6; text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .store-link .store-name { font-size: 16px; font-weight: 600; }
        .footer {
            background: var(--bg-elevated); border-top: 1px solid var(--border);
            padding: 64px 24px 32px;
        }
        .footer-inner {
            max-width: var(--max-w); margin: 0 auto;
            display: grid; grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px;
        }
        .footer-brand .brand-name {
            font-size: 20px; font-weight: 700; margin-bottom: 12px; color: var(--primary);
        }
        .footer-brand p {
            font-size: 14px; color: var(--text-muted); line-height: 1.6;
            max-width: 300px;
        }
        .footer-col h4 {
            font-size: 14px; font-weight: 600; margin-bottom: 16px; color: var(--text);
        }
        .footer-col a {
            display: block; padding: 4px 0; font-size: 13px;
            color: var(--text-muted); text-decoration: none; transition: color 0.2s;
        }
        .footer-col a:hover { color: var(--primary); }
        .footer-bottom {
            max-width: var(--max-w); margin: 40px auto 0;
            padding-top: 24px; border-top: 1px solid var(--border);
            text-align: center; font-size: 13px; color: var(--text-muted);
        }
        @media (max-width: 968px) {
            .nav-links { display: none; }
            .mobile-toggle { display: block; }
            .hero-inner { grid-template-columns: 1fr; gap: 48px; text-align: center; }
            .hero h1 { font-size: 44px; letter-spacing: -1px; }
            .hero-desc { margin: 0 auto 36px; }
            .hero-actions { justify-content: center; }
            .hero-badge { margin: 0 auto 24px; }
            .features-grid { grid-template-columns: 1fr; }
            .stats-inner { grid-template-columns: repeat(2, 1fr); }
            .section-title { font-size: 32px; }
            .footer-inner { grid-template-columns: 1fr 1fr; gap: 32px; }
        }
        @media (max-width: 640px) {
            .hero h1 { font-size: 34px; }
            .hero-actions { flex-direction: column; align-items: center; }
            .store-links { flex-direction: column; align-items: center; }
            .stats-inner { grid-template-columns: 1fr 1fr; gap: 24px; }
            .stat-item h2 { font-size: 32px; }
            .screenshots-grid { grid-template-columns: 1fr; max-width: 320px; margin: 0 auto; }
            .footer-inner { grid-template-columns: 1fr; }
        }
        .fade-up { opacity: 0; transform: translateY(24px); transition: opacity 0.6s, transform 0.6s; }
        .fade-up.visible { opacity: 1; transform: translateY(0); }
        .skip-link {
            position: absolute; top: -100%; left: 16px;
            padding: 12px 24px; background: var(--primary); color: #000;
            border-radius: 8px; font-weight: 600; z-index: 99999; text-decoration: none;
        }
        .skip-link:focus { top: 16px; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <div class="scroll-progress" id="scrollProgress"></div>
    <nav class="navbar" id="navbar" role="navigation" aria-label="Main navigation">
        <a href="/" class="nav-brand" aria-label="Tocco Voice Live Home">
            <div class="nav-logo">
                @php $logo = getAppLogo(); @endphp
                @if(!empty($logo))
                    <img src="{{ $logo }}" alt="Tocco Voice Live">
                @else
                    <span style="font-weight:800;font-size:16px;color:#000;">T</span>
                @endif
            </div>
            <span class="nav-name">Tocco Voice Live</span>
        </a>
        <ul class="nav-links">
            <li><a href="#features">Features</a></li>
            <li><a href="#app">App</a></li>
            <li><a href="#download">Download</a></li>
            <li><a href="/payment">Payment</a></li>
            <li><a href="#download" class="nav-cta">Get the App</a></li>
        </ul>
        <button class="mobile-toggle" id="mobileToggle" aria-label="Open navigation menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </nav>
    <div class="mobile-nav-overlay" id="mobileOverlay"></div>
    <div class="mobile-nav" id="mobileNav" role="navigation" aria-label="Mobile navigation">
        <a href="#features">Features</a>
        <a href="#app">App</a>
        <a href="#download">Download</a>
        <a href="/payment">Payment</a>
        <a href="#download" style="color:var(--primary);font-weight:600;">Get the App</a>
    </div>
    <main id="main-content">
        <section class="hero" id="home">
            <div class="hero-glow"></div>
            <div class="hero-inner">
                <div class="hero-content">
                    <div class="hero-badge"><span class="dot"></span>Now Available Worldwide</div>
                    <h1>Go Live.<br>Connect <span class="accent">Everywhere.</span></h1>
                    <p class="hero-desc">Tocco Voice Live brings people together through live streaming, video calls, and real-time translation. Join millions of users across 100+ countries.</p>
                    <div class="hero-actions">
                        <a href="#download" class="btn btn-primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Download Now
                        </a>
                        <a href="#features" class="btn btn-secondary">Explore Features</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="phone-frame">
                        <div class="phone-notch"></div>
                        <div class="phone-screen">
                            <div class="screenshot-slides" id="heroScreenshots">
                                <div class="screenshot-slide active">
                                    <div class="placeholder">
                                        <span class="icon">📱</span>
                                        <span>Add screenshots to<br><code>public/app-screens/</code></span>
                                    </div>
                                </div>
                            </div>
                            <div class="slide-dots" id="slideDots"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="showcase" id="app">
            <div class="showcase-inner">
                <div class="section-header fade-up">
                    <div class="section-tag">📱 The App</div>
                    <h2 class="section-title">Experience Tocco Voice Live</h2>
                    <p class="section-subtitle">See the features that make Tocco Voice Live the platform of choice for millions worldwide.</p>
                </div>
                <div class="screenshots-grid" id="screenshotsGrid"></div>
            </div>
        </section>
        <section class="features" id="features">
            <div class="features-inner">
                <div class="section-header fade-up">
                    <div class="section-tag">✨ Features</div>
                    <h2 class="section-title">Built for Connection</h2>
                    <p class="section-subtitle">Everything you need to stream, chat, and build your community.</p>
                </div>
                <div class="features-grid" id="featuresGrid"></div>
            </div>
        </section>
        <section class="stats" id="stats">
            <div class="stats-inner">
                <div class="stat-item fade-up">
                    <h2>{{ numToStringNew((int)@$settings['landing_users_count'] ?? 0) }}{{ (int)@$settings['landing_users_count'] > 0 ? '+' : '' }}</h2>
                    <p>Active Users</p>
                </div>
                <div class="stat-item fade-up">
                    <h2>{{ numToStringNew((int)@$settings['landing_countries_count'] ?? 0) }}{{ (int)@$settings['landing_countries_count'] > 0 ? '+' : '' }}</h2>
                    <p>Countries</p>
                </div>
                <div class="stat-item fade-up">
                    <h2>{{ numToStringNew((int)@$settings['landing_live_count'] ?? 0) }}{{ (int)@$settings['landing_live_count'] > 0 ? '+' : '' }}</h2>
                    <p>Daily Streams</p>
                </div>
                <div class="stat-item fade-up">
                    <h2>24/7</h2>
                    <p>Support</p>
                </div>
            </div>
        </section>
        <section class="cta" id="download">
            <div class="cta-inner fade-up">
                <h2>Download Tocco Voice Live</h2>
                <p>Start connecting with millions of people worldwide. Available on iOS, Android, and Huawei AppGallery.</p>
                <div class="store-links">
                    @if(!empty(@$settings['ios_link']))
                    <a href="{{ @$settings['ios_link'] }}" class="store-link" target="_blank" rel="noopener" aria-label="Download on App Store">
                        <svg class="store-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                        <div class="store-text"><div class="store-sub">Download on the</div><div class="store-name">App Store</div></div>
                    </a>
                    @endif
                    @if(!empty(@$settings['android_link']))
                    <a href="{{ @$settings['android_link'] }}" class="store-link" target="_blank" rel="noopener" aria-label="Get it on Google Play">
                        <svg class="store-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.61 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.53,12.9 20.18,13.18L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z"/></svg>
                        <div class="store-text"><div class="store-sub">Get it on</div><div class="store-name">Google Play</div></div>
                    </a>
                    @endif
                    @if(!empty(@$settings['gallery_app_link']))
                    <a href="{{ @$settings['gallery_app_link'] }}" class="store-link" target="_blank" rel="noopener" aria-label="Explore it on AppGallery">
                        <svg class="store-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                        <div class="store-text"><div class="store-sub">Explore it on</div><div class="store-name">AppGallery</div></div>
                    </a>
                    @endif
                </div>
            </div>
        </section>
    </main>
    <footer class="footer" role="contentinfo">
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="brand-name">Tocco Voice Live</div>
                <p>Go live, connect with people worldwide through live streaming, video calls, and real-time chat. Available on iOS and Android.</p>
            </div>
            <div class="footer-col">
                <h4>Product</h4>
                <a href="#features">Features</a>
                <a href="#download">Download</a>
                <a href="#app">App Showcase</a>
                <a href="/payment">Payment</a>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <a href="{{ @$settings['about_us_link'] ?? '#' }}">About Us</a>
                <a href="/privacy-policy">Privacy Policy</a>
                <a href="#">Help Center</a>
                <a href="#">Contact</a>
            </div>
            <div class="footer-col">
                <h4>Legal</h4>
                <a href="/privacy-policy">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Community Guidelines</a>
            </div>
        </div>
        <div class="footer-bottom">
            © {{ date('Y') }} Tocco Voice Live. All rights reserved.
        </div>
    </footer>
    <script>
        const SCREENSHOT_DIR = '/app-screens/';
        const screenshots = [
            { file: 'home.png', title: 'Home Feed', desc: 'Discover live streams and trending content', feature: 'Discovery' },
            { file: 'rooms.png', title: 'Live Rooms', desc: 'Browse and join live streaming rooms', feature: 'Live Streaming' },
            { file: 'room.png', title: 'In-Room Experience', desc: 'Interactive live streaming with hosts and viewers', feature: 'Streaming' },
            { file: 'chat.png', title: 'Real-Time Chat', desc: 'Instant messaging with real-time translation', feature: 'Communication' },
            { file: 'profile.png', title: 'Your Profile', desc: 'Manage your profile and followers', feature: 'Social' },
            { file: 'settings.png', title: 'Settings', desc: 'Customize your Tocco Voice Live experience', feature: 'Settings' },
        ];
        const features = [
            { icon: '🎬', title: 'Live Streaming', desc: 'Go live instantly and share your talents with a global audience. Get real-time reactions, gifts, and build your fan community.' },
            { icon: '💬', title: 'Instant Chat', desc: 'Connect through text, voice, and video chat. Real-time translation breaks down language barriers across 100+ countries.' },
            { icon: '👥', title: 'Group Parties', desc: 'Host video calls with up to 9 people simultaneously. Create private rooms for intimate gatherings or go public.' },
            { icon: '🌍', title: 'Global Community', desc: 'Meet new friends from over 100 countries. Discover diverse cultures and make meaningful connections worldwide.' },
            { icon: '🎯', title: 'Gifts & Rewards', desc: 'Send and receive virtual gifts to support your favorite streamers. Build relationships through generosity.' },
            { icon: '🔒', title: 'Safe & Secure', desc: 'Your privacy matters. Enjoy a safe environment with robust moderation and security features built in.' },
        ];
        function renderScreenshots() {
            const grid = document.getElementById('screenshotsGrid');
            const heroSlides = document.getElementById('heroScreenshots');
            const dotsContainer = document.getElementById('slideDots');
            grid.innerHTML = screenshots.map(s => {
                const imgSrc = SCREENSHOT_DIR + s.file;
                return '<div class="screenshot-card fade-up"><div class="card-image"><img src="' + imgSrc + '" alt="' + s.title + ' — Tocco Voice Live" loading="lazy" onerror="this.parentElement.innerHTML=\'<div class=placeholder><span class=icon>📱</span><span>' + s.title + '</span></div>\'"></div><div class="card-body">' + (s.feature ? '<div class="card-label">' + s.feature + '</div>' : '') + '<h3>' + s.title + '</h3><p>' + s.desc + '</p></div></div>';
            }).join('');
            heroSlides.innerHTML = screenshots.map(function(s, i) {
                var imgSrc = SCREENSHOT_DIR + s.file;
                return '<div class="screenshot-slide' + (i === 0 ? ' active' : '') + '" data-index="' + i + '"><img src="' + imgSrc + '" alt="' + s.title + '" loading="lazy" onerror="this.parentElement.innerHTML=\'<div class=placeholder><span class=icon>📱</span><span>' + s.title + '</span></div>\'"></div>';
            }).join('');
            dotsContainer.innerHTML = screenshots.map(function(_, i) {
                return '<span class="slide-dot' + (i === 0 ? ' active' : '') + '" data-slide="' + i + '" role="button" tabindex="0" aria-label="Show screenshot ' + (i + 1) + '"></span>';
            }).join('');
        }
        function renderFeatures() {
            document.getElementById('featuresGrid').innerHTML = features.map(function(f) {
                return '<div class="feature-card fade-up"><div class="feature-icon">' + f.icon + '</div><h3>' + f.title + '</h3><p>' + f.desc + '</p></div>';
            }).join('');
        }
        var currentSlide = 0, slideInterval;
        function showSlide(index) {
            var slides = document.querySelectorAll('#heroScreenshots .screenshot-slide');
            var dots = document.querySelectorAll('#slideDots .slide-dot');
            slides.forEach(function(s) { s.classList.remove('active'); });
            dots.forEach(function(d) { d.classList.remove('active'); });
            if (slides[index]) slides[index].classList.add('active');
            if (dots[index]) dots[index].classList.add('active');
            currentSlide = index;
        }
        function nextSlide() {
            var total = document.querySelectorAll('#heroScreenshots .screenshot-slide').length;
            if (total <= 1) return;
            showSlide((currentSlide + 1) % total);
        }
        function startSlideshow() {
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 4000);
        }
        var navbar = document.getElementById('navbar');
        var mobileToggle = document.getElementById('mobileToggle');
        var mobileNav = document.getElementById('mobileNav');
        var mobileOverlay = document.getElementById('mobileOverlay');
        window.addEventListener('scroll', function() {
            navbar.classList.toggle('scrolled', window.scrollY > 40);
            var h = document.documentElement.scrollHeight - window.innerHeight;
            document.getElementById('scrollProgress').style.width = (window.scrollY / h * 100) + '%';
        });
        mobileToggle.addEventListener('click', function() {
            var isActive = mobileNav.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
            mobileToggle.setAttribute('aria-expanded', isActive);
        });
        mobileOverlay.addEventListener('click', function() {
            mobileNav.classList.remove('active');
            mobileOverlay.classList.remove('active');
            mobileToggle.setAttribute('aria-expanded', 'false');
        });
        mobileNav.querySelectorAll('a').forEach(function(a) {
            a.addEventListener('click', function() {
                mobileNav.classList.remove('active');
                mobileOverlay.classList.remove('active');
                mobileToggle.setAttribute('aria-expanded', 'false');
            });
        });
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) { if (e.isIntersecting) e.target.classList.add('visible'); });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
        function observeElements() {
            document.querySelectorAll('.fade-up').forEach(function(el) { observer.observe(el); });
        }
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
            });
        });
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('slide-dot')) {
                clearInterval(slideInterval);
                showSlide(parseInt(e.target.dataset.slide));
                startSlideshow();
            }
        });
        renderScreenshots();
        renderFeatures();
        startSlideshow();
        observeElements();
    </script>
</body>
</html>
