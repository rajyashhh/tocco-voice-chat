<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @php
        $countryName = app()->getLocale() == 'ar' ? $country->name : $country->e_name;
    @endphp
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#050510">
    <title>{{ config('app.name') }} – {{ $countryName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}

        :root {
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
            --c-bg: #050510;
            --c-surface: rgba(255,255,255,0.04);
            --c-surface-elevated: rgba(255,255,255,0.06);
            --c-border: rgba(255,255,255,0.06);
            --c-border-glow: rgba(139,92,246,0.25);
            --c-text: #ffffff;
            --c-text-secondary: rgba(255,255,255,0.55);
            --c-text-tertiary: rgba(255,255,255,0.35);
            --c-violet: #8b5cf6;
            --c-indigo: #6366f1;
            --c-pink: #ec4899;
            --c-cyan: #22d3ee;
            --c-gold: #fbbf24;
            --c-emerald: #34d399;
            --c-rose: #f43f5e;
            --radius-sm: 14px;
            --radius-md: 20px;
            --radius-lg: 24px;
            --radius-xl: 28px;
            /* Type scale — mobile-optimized */
            --font-display: 'Cairo', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-body: 'Inter', 'Cairo', -apple-system, BlinkMacSystemFont, sans-serif;
            --fs-hero: clamp(26px, 7.5vw, 34px);
            --fs-section-title: clamp(13px, 3.6vw, 15px);
            --fs-card-title: clamp(18px, 5vw, 22px);
            --fs-epic-title: clamp(18px, 5.2vw, 23px);
            --fs-stat-value: clamp(22px, 6.5vw, 30px);
            --fs-live-count: clamp(42px, 13vw, 64px);
            --fs-body: clamp(13px, 3.6vw, 15px);
            --fs-caption: clamp(10px, 2.8vw, 12px);
            --fs-micro: clamp(9px, 2.5vw, 11px);
            --lh-tight: 1.2;
            --lh-normal: 1.5;
            --lh-relaxed: 1.8;
        }

        html {
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        body {
            font-family: var(--font-body);
            font-size: var(--fs-body);
            line-height: var(--lh-normal);
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
            background: var(--c-bg);
            color: var(--c-text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            padding-top: var(--safe-top);
            padding-bottom: var(--safe-bottom);
        }

        /* ── Accessibility ── */
        .skip-link {
            position: absolute; top: -100%; left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--c-violet), var(--c-pink));
            color: #fff; padding: 12px 24px;
            border-radius: 0 0 12px 12px;
            font-weight: 700; font-size: 14px;
            z-index: 200; text-decoration: none;
            transition: top 0.3s ease;
        }
        .skip-link:focus { top: 0; }
        .sr-only {
            position: absolute; width: 1px; height: 1px;
            padding: 0; margin: -1px; overflow: hidden;
            clip: rect(0,0,0,0); white-space: nowrap; border: 0;
        }
        :focus-visible {
            outline: 2px solid var(--c-violet);
            outline-offset: 3px;
        }
        :focus:not(:focus-visible) { outline: none; }

        /* ══════════════════════════════════════════════════
           COSMIC BACKGROUND SYSTEM
        ══════════════════════════════════════════════════ */

        /* Layer 0 — Deep space gradient */
        .cosmos {
            position: fixed; inset: 0; z-index: 0; overflow: hidden;
            background:
                radial-gradient(ellipse 120% 80% at 15% 5%, rgba(88,28,135,0.25) 0%, transparent 55%),
                radial-gradient(ellipse 100% 70% at 85% 90%, rgba(157,23,77,0.18) 0%, transparent 50%),
                radial-gradient(ellipse 90% 90% at 50% 40%, rgba(30,58,138,0.1) 0%, transparent 55%),
                radial-gradient(ellipse 80% 60% at 70% 15%, rgba(6,182,212,0.08) 0%, transparent 45%),
                var(--c-bg);
            animation: cosmosShift 30s ease-in-out infinite;
        }
        @keyframes cosmosShift {
            0%,100% { filter: hue-rotate(0deg) brightness(1); }
            33% { filter: hue-rotate(12deg) brightness(1.04); }
            66% { filter: hue-rotate(-8deg) brightness(0.97); }
        }

        /* Layer 1 — Nebula orbs (soft glowing blobs) */
        .nebula-layer { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; max-width: 100vw; }
        .nebula {
            position: absolute; border-radius: 50%;
            filter: blur(90px);
            mix-blend-mode: screen;
            will-change: transform;
        }
        .nebula-1 {
            width: 320px; height: 320px;
            background: radial-gradient(circle, rgba(139,92,246,0.22), rgba(88,28,135,0.08), transparent);
            top: -8%; left: -12%;
            animation: nebulaOrbit1 22s ease-in-out infinite;
        }
        .nebula-2 {
            width: 280px; height: 280px;
            background: radial-gradient(circle, rgba(236,72,153,0.16), rgba(157,23,77,0.06), transparent);
            bottom: -5%; right: -10%;
            animation: nebulaOrbit2 18s ease-in-out infinite;
        }
        .nebula-3 {
            width: 220px; height: 220px;
            background: radial-gradient(circle, rgba(6,182,212,0.12), rgba(30,58,138,0.04), transparent);
            top: 35%; left: 55%;
            animation: nebulaOrbit3 25s ease-in-out infinite;
        }
        .nebula-4 {
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(251,191,36,0.08), rgba(245,158,11,0.03), transparent);
            top: 60%; left: 15%;
            animation: nebulaOrbit1 28s ease-in-out infinite reverse;
        }
        @keyframes nebulaOrbit1 {
            0%,100% { transform: translate(0,0) scale(1); opacity: 0.6; }
            30% { transform: translate(40px,-30px) scale(1.15); opacity: 0.8; }
            60% { transform: translate(-20px,35px) scale(0.9); opacity: 0.5; }
        }
        @keyframes nebulaOrbit2 {
            0%,100% { transform: translate(0,0) scale(1); }
            50% { transform: translate(-35px,25px) scale(1.1); }
        }
        @keyframes nebulaOrbit3 {
            0%,100% { transform: translate(0,0) scale(1); }
            40% { transform: translate(-30px,-25px) scale(1.18); }
            70% { transform: translate(20px,15px) scale(0.95); }
        }

        /* Layer 2 — Twinkling stars (CSS) */
        .stars-layer { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .twinkle-star {
            position: absolute; border-radius: 50%; background: #fff;
        }
        .twinkle-star.s-bright {
            animation: starBright 3s ease-in-out infinite;
        }
        .twinkle-star.s-dim {
            animation: starDim 4s ease-in-out infinite;
        }
        .twinkle-star.s-cold {
            animation: starCold 2.5s ease-in-out infinite;
        }
        @keyframes starBright {
            0%,100% { opacity: 0.12; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.8); box-shadow: 0 0 6px #fff, 0 0 12px rgba(139,92,246,0.5); }
        }
        @keyframes starDim {
            0%,100% { opacity: 0.08; transform: scale(0.8); }
            50% { opacity: 0.7; transform: scale(1.3); box-shadow: 0 0 4px rgba(236,72,153,0.6); }
        }
        @keyframes starCold {
            0%,100% { opacity: 0.2; }
            50% { opacity: 0.9; box-shadow: 0 0 6px #fff, 0 0 14px rgba(6,182,212,0.4); }
        }

        /* Layer 3 — Shooting stars */
        .shooting-star-layer { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; max-width: 100vw; }
        .shooting-star {
            position: absolute; opacity: 0;
            width: 140px; height: 1.5px;
            background: linear-gradient(90deg, rgba(255,255,255,0.9), rgba(139,92,246,0.5), rgba(236,72,153,0.2), transparent);
            border-radius: 100px;
        }
        .shooting-star::before {
            content: ''; position: absolute;
            left: 0; top: -2.5px;
            width: 6px; height: 6px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 0 8px #fff, 0 0 20px rgba(139,92,246,0.6);
        }
        .shooting-star:nth-child(1) {
            top: 10%; transform: rotate(-15deg);
            animation: shootStar 7s ease-in 1s infinite;
        }
        .shooting-star:nth-child(2) {
            top: 45%; transform: rotate(-20deg);
            animation: shootStar 8s ease-in 4.5s infinite;
        }
        .shooting-star:nth-child(3) {
            top: 75%; transform: rotate(-10deg);
            animation: shootStar 9s ease-in 7s infinite;
        }
        @keyframes shootStar {
            0% { left: -160px; opacity: 0; }
            3% { opacity: 1; }
            15% { opacity: 0; left: 110vw; }
            100% { opacity: 0; }
        }

        /* Layer 4 — Cosmic dust particles (rising) */
        .dust-layer { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; max-width: 100vw; }
        .dust-particle {
            position: absolute; border-radius: 50%;
            animation: dustFloat linear infinite;
        }
        @keyframes dustFloat {
            0% { transform: translateY(105vh) translateX(0) scale(0) rotate(0deg); opacity: 0; }
            8% { opacity: 0.7; transform: translateY(92vh) translateX(8px) scale(1) rotate(30deg); }
            50% { transform: translateY(50vh) translateX(-12px) scale(0.8) rotate(180deg); opacity: 0.5; }
            85% { opacity: 0.2; }
            100% { transform: translateY(-5vh) translateX(15px) scale(0.2) rotate(360deg); opacity: 0; }
        }

        /* Layer 5 — Floating planets */
        .planet-layer { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; max-width: 100vw; }
        .planet {
            position: absolute; border-radius: 50%;
            animation: planetDrift ease-in-out infinite;
        }
        .planet-1 {
            width: 18px; height: 18px;
            background: radial-gradient(circle at 35% 35%, rgba(139,92,246,0.5), rgba(88,28,135,0.8));
            box-shadow: 0 0 15px rgba(139,92,246,0.25), inset -3px -3px 6px rgba(0,0,0,0.4);
            top: 18%; right: 12%;
            animation-duration: 20s;
        }
        .planet-2 {
            width: 12px; height: 12px;
            background: radial-gradient(circle at 30% 30%, rgba(236,72,153,0.5), rgba(157,23,77,0.7));
            box-shadow: 0 0 12px rgba(236,72,153,0.2), inset -2px -2px 5px rgba(0,0,0,0.4);
            top: 55%; left: 8%;
            animation-duration: 26s;
            animation-direction: reverse;
        }
        .planet-3 {
            width: 24px; height: 24px;
            background: radial-gradient(circle at 35% 35%, rgba(6,182,212,0.4), rgba(30,58,138,0.7));
            box-shadow: 0 0 20px rgba(6,182,212,0.15), inset -4px -4px 8px rgba(0,0,0,0.3);
            bottom: 22%; right: 18%;
            animation-duration: 32s;
        }
        .planet-3::after {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%,-50%) rotateX(72deg);
            width: 38px; height: 38px;
            border: 1.5px solid rgba(6,182,212,0.2);
            border-radius: 50%;
        }
        @keyframes planetDrift {
            0%,100% { transform: translate(0,0) rotate(0deg); }
            25% { transform: translate(12px,-18px) rotate(90deg); }
            50% { transform: translate(-8px,-10px) rotate(180deg); }
            75% { transform: translate(15px,8px) rotate(270deg); }
        }

        /* Layer 6 — Orbiting lights */
        .orbit-layer {
            position: fixed; top: 50%; left: 50%;
            width: 0; height: 0; z-index: 0; pointer-events: none;
            overflow: visible; clip-path: inset(-50vh -50vw -50vh -50vw);
        }
        .orbit-light {
            position: absolute;
            width: 4px; height: 4px;
            border-radius: 50%;
        }
        .orbit-light-1 {
            background: var(--c-violet);
            box-shadow: 0 0 10px var(--c-violet), 0 0 25px rgba(139,92,246,0.3);
            animation: orbitSpin 20s linear infinite;
        }
        .orbit-light-2 {
            background: var(--c-pink);
            box-shadow: 0 0 10px var(--c-pink), 0 0 25px rgba(236,72,153,0.3);
            animation: orbitSpin 28s linear infinite reverse;
        }
        @keyframes orbitSpin {
            0% { transform: rotate(0deg) translateX(min(42vw, 180px)) rotate(0deg); }
            100% { transform: rotate(360deg) translateX(min(42vw, 180px)) rotate(-360deg); }
        }

        /* Layer 7 — Canvas particle field */
        #particleCanvas {
            position: fixed; inset: 0; z-index: 0;
            pointer-events: none;
            width: 100%; height: 100%;
        }

        /* ══════════════════════════════════════════════════
           MAIN CONTAINER
        ══════════════════════════════════════════════════ */
        .app-container {
            position: relative; z-index: 2;
            max-width: 430px;
            margin: 0 auto;
            padding: 0 16px;
            padding-bottom: calc(48px + var(--safe-bottom));
        }

        /* ── Language Switcher ── */
        .lang-bar {
            display: flex; justify-content: center;
            padding: 12px 0 4px;
            position: sticky; top: 0; z-index: 50;
        }
        .lang-bar-inner {
            display: flex; gap: 3px; padding: 4px;
            background: rgba(5,5,16,0.82);
            backdrop-filter: blur(28px) saturate(1.5);
            -webkit-backdrop-filter: blur(28px) saturate(1.5);
            border-radius: 50px;
            border: 1px solid var(--c-border);
            box-shadow: 0 4px 24px rgba(0,0,0,0.3);
        }
        .lang-btn {
            padding: 8px 20px; border: none; border-radius: 50px;
            background: transparent; color: var(--c-text-secondary);
            font-family: var(--font-display); font-size: var(--fs-caption); font-weight: 700;
            cursor: pointer; transition: all 0.3s ease;
            -webkit-tap-highlight-color: transparent;
            min-height: 36px;
        }
        .lang-btn:active:not(.active) { transform: scale(0.95); }
        .lang-btn.active {
            background: linear-gradient(135deg, var(--c-violet), var(--c-pink));
            color: #fff;
            box-shadow: 0 4px 18px rgba(139,92,246,0.45);
        }

        /* ── Hero Section ── */
        .hero {
            text-align: center;
            padding: 36px 0 30px;
            animation: heroEnter 1s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes heroEnter {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .flag-container {
            position: relative;
            display: inline-flex;
            align-items: center; justify-content: center;
            margin-bottom: 22px;
            overflow: visible;
            max-width: 100%;
        }
        .flag-ring {
            position: absolute; border-radius: 50%;
            border: 2px solid;
        }
        .flag-ring-1 {
            width: 120px; height: 120px;
            border-color: rgba(139,92,246,0.15);
            animation: ringPulse 3s ease-in-out infinite;
        }
        .flag-ring-2 {
            width: 145px; height: 145px;
            border-color: rgba(236,72,153,0.08);
            animation: ringPulse 3s ease-in-out 1s infinite;
        }
        .flag-ring-3 {
            width: 170px; height: 170px;
            border-color: rgba(6,182,212,0.05);
            animation: ringPulse 3s ease-in-out 2s infinite;
        }
        @keyframes ringPulse {
            0%,100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.06); opacity: 1; }
        }
        .flag-sparkle {
            position: absolute;
            width: 6px; height: 6px;
            border-radius: 50%;
            top: 50%; left: 50%;
        }
        .flag-sparkle-1 {
            background: var(--c-gold);
            box-shadow: 0 0 8px var(--c-gold), 0 0 20px rgba(251,191,36,0.4);
            animation: sparkleOrbit 6s linear infinite;
        }
        .flag-sparkle-2 {
            background: var(--c-violet);
            box-shadow: 0 0 8px var(--c-violet), 0 0 20px rgba(139,92,246,0.4);
            width: 5px; height: 5px;
            animation: sparkleOrbit 8s linear infinite reverse;
        }
        @keyframes sparkleOrbit {
            0% { transform: rotate(0deg) translateX(55px) rotate(0deg); }
            100% { transform: rotate(360deg) translateX(55px) rotate(-360deg); }
        }
        .flag-emoji {
            font-size: 76px;
            filter: drop-shadow(0 0 35px rgba(139,92,246,0.35));
            animation: flagFloat 5s ease-in-out infinite;
            position: relative; z-index: 2;
        }
        @keyframes flagFloat {
            0%,100% { transform: translateY(0) scale(1) perspective(500px) rotateY(0deg); }
            25% { transform: translateY(-10px) scale(1.06) perspective(500px) rotateY(5deg); }
            50% { transform: translateY(-6px) scale(1.03) perspective(500px) rotateY(-3deg); }
            75% { transform: translateY(-8px) scale(1.05) perspective(500px) rotateY(4deg); }
        }
        .hero-title {
            font-family: var(--font-display);
            font-size: var(--fs-hero); font-weight: 900;
            line-height: var(--lh-tight);
            background: linear-gradient(135deg, #fff 0%, #c4b5fd 30%, #f0abfc 60%, #fbbf24 90%);
            background-size: 400% auto;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            animation: megaShimmer 6s ease infinite;
            margin-bottom: 8px;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        @keyframes megaShimmer {
            0%,100% { background-position: 0% center; }
            50% { background-position: 400% center; }
        }
        .hero-subtitle {
            font-family: var(--font-body);
            font-size: var(--fs-micro); font-weight: 600;
            letter-spacing: 0.18em; text-transform: uppercase;
            color: var(--c-text-tertiary);
            animation: fadeUp 0.8s ease-out 0.5s both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Glass Card Base ── */
        .glass-card {
            position: relative;
            background: var(--c-surface);
            backdrop-filter: blur(20px) saturate(1.3);
            -webkit-backdrop-filter: blur(20px) saturate(1.3);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            max-width: 100%;
            transition: transform 0.4s cubic-bezier(0.22,1,0.36,1), border-color 0.4s ease, box-shadow 0.4s ease;
        }
        .glass-card::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.04) 0%, transparent 50%);
            pointer-events: none; z-index: 1;
        }
        .glass-card::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.03) 45%, transparent 55%);
            background-size: 200% 200%;
            animation: cardShine 5s ease-in-out infinite;
            pointer-events: none;
        }
        @keyframes cardShine {
            0%,100% { background-position: -100% -100%; }
            50% { background-position: 200% 200%; }
        }

        /* ── Super Admin Card ── */
        .sa-section {
            margin-bottom: 16px;
            animation: cardEnter 0.9s cubic-bezier(0.22,1,0.36,1) 0.2s both;
        }
        @keyframes cardEnter {
            from { opacity: 0; transform: translateY(35px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .sa-card {
            padding: 26px 20px;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .sa-card:active {
            transform: scale(0.98);
            border-color: var(--c-border-glow);
            box-shadow: 0 0 30px rgba(139,92,246,0.1);
        }
        .sa-glow-top {
            position: absolute; top: -1px; left: -1px; right: -1px;
            height: 3px; z-index: 2;
            background: linear-gradient(90deg, var(--c-violet), var(--c-pink), var(--c-gold), var(--c-cyan), var(--c-violet));
            background-size: 300% auto;
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            animation: glowSlide 3.5s linear infinite;
        }
        @keyframes glowSlide {
            0% { background-position: 0% center; }
            100% { background-position: 300% center; }
        }
        .sa-crown {
            position: absolute; top: -8px;
            font-size: 30px; z-index: 5;
            animation: crownFloat 3.5s ease-in-out infinite;
            filter: drop-shadow(0 0 14px rgba(251,191,36,0.6));
        }
        [dir="rtl"] .sa-crown { right: 18px; }
        [dir="ltr"] .sa-crown { left: 18px; }
        @keyframes crownFloat {
            0%,100% { transform: rotate(-10deg) translateY(0) scale(1); }
            30% { transform: rotate(5deg) translateY(-8px) scale(1.08); }
            60% { transform: rotate(-3deg) translateY(-5px) scale(1.04); }
        }
        .sa-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 14px;
            background: linear-gradient(135deg, rgba(251,191,36,0.12), rgba(236,72,153,0.08));
            border: 1px solid rgba(251,191,36,0.2);
            border-radius: 50px;
            font-family: var(--font-display);
            font-size: var(--fs-caption); font-weight: 800; color: var(--c-gold);
            margin-bottom: 18px;
            position: relative; z-index: 2;
            animation: badgeGlow 3s ease-in-out infinite;
        }
        @keyframes badgeGlow {
            0%,100% { box-shadow: 0 0 0 0 rgba(251,191,36,0.2); }
            50% { box-shadow: 0 0 0 8px rgba(251,191,36,0); }
        }
        .sa-profile {
            display: flex; align-items: center; gap: 16px;
            margin-bottom: 20px;
            position: relative; z-index: 2;
        }
        .sa-avatar-wrap {
            position: relative; flex-shrink: 0;
        }
        .sa-avatar {
            width: 76px; height: 76px;
            border-radius: 22px;
            object-fit: cover; display: block;
            border: 2.5px solid rgba(139,92,246,0.35);
            animation: avatarGlow 3.5s ease-in-out infinite;
        }
        @keyframes avatarGlow {
            0%,100% { box-shadow: 0 0 20px rgba(139,92,246,0.2), 0 8px 24px rgba(0,0,0,0.3); }
            50% { box-shadow: 0 0 35px rgba(139,92,246,0.35), 0 12px 32px rgba(0,0,0,0.35); }
        }
        .sa-avatar-aura {
            position: absolute; inset: -7px;
            border-radius: 26px;
            border: 1.5px solid rgba(139,92,246,0.1);
            animation: auraExpand 3s ease-in-out infinite;
        }
        .sa-avatar-aura:nth-child(3) { animation-delay: 1.5s; }
        @keyframes auraExpand {
            0%,100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.07); opacity: 0.9; }
        }
        .sa-info { min-width: 0; flex: 1; overflow: hidden; }
        .sa-name {
            font-family: var(--font-display);
            font-size: var(--fs-card-title); font-weight: 900;
            background: linear-gradient(135deg, #fff, #c4b5fd, #f0abfc);
            background-size: 200% auto;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            animation: megaShimmer 5s ease infinite;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            line-height: var(--lh-tight);
            max-width: 100%;
        }
        .sa-id {
            font-family: var(--font-body);
            font-size: var(--fs-micro); color: var(--c-text-secondary);
            font-weight: 500; margin-top: 3px;
            letter-spacing: 0.02em;
        }
        .sa-stats {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;
            position: relative; z-index: 2;
            max-width: 100%;
            overflow: hidden;
        }
        .sa-stat-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: var(--radius-sm);
            padding: 16px 6px;
            text-align: center;
            transition: all 0.3s ease;
            -webkit-tap-highlight-color: transparent;
            position: relative; overflow: hidden;
        }
        .sa-stat-card::after {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at center, rgba(139,92,246,0.06), transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sa-stat-card:active {
            transform: scale(0.96);
        }
        .sa-stat-card:active::after { opacity: 1; }
        .sa-stat-label {
            font-family: var(--font-body);
            font-size: var(--fs-micro); font-weight: 600;
            color: var(--c-text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
            line-height: var(--lh-normal);
            position: relative; z-index: 1;
        }
        .sa-stat-value {
            font-family: var(--font-display);
            font-size: var(--fs-stat-value); font-weight: 900;
            background: linear-gradient(135deg, var(--c-gold), var(--c-pink), var(--c-violet));
            background-size: 200% auto;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            animation: megaShimmer 4s ease infinite;
            line-height: 1;
            position: relative; z-index: 1;
        }
        .sa-stat-card:nth-child(1) .sa-stat-value { animation-delay: 0s; }
        .sa-stat-card:nth-child(2) .sa-stat-value { animation-delay: 0.5s; }
        .sa-stat-card:nth-child(3) .sa-stat-value { animation-delay: 1s; }

        /* ── Live Users Card ── */
        .live-section {
            margin-bottom: 16px;
            animation: cardEnter 0.9s cubic-bezier(0.22,1,0.36,1) 0.35s both;
        }
        .live-card {
            padding: 26px 20px;
            text-align: center;
        }
        .live-indicator {
            display: inline-flex; align-items: center; gap: 8px;
            font-family: var(--font-body);
            font-size: var(--fs-micro); font-weight: 700; color: var(--c-emerald);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }
        .live-dot-wrap {
            position: relative;
            width: 10px; height: 10px;
        }
        .live-dot {
            width: 8px; height: 8px;
            background: var(--c-emerald); border-radius: 50%;
            box-shadow: 0 0 8px var(--c-emerald);
            position: absolute; top: 1px; left: 1px;
        }
        .live-dot-ping {
            position: absolute; inset: 0;
            border-radius: 50%;
            border: 1.5px solid var(--c-emerald);
            animation: pingWave 2s ease-out infinite;
        }
        .live-dot-ping:nth-child(3) { animation-delay: 1s; }
        @keyframes pingWave {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(2.5); opacity: 0; }
        }
        .live-count {
            font-family: var(--font-display);
            font-size: var(--fs-live-count); font-weight: 900; line-height: 1;
            background: linear-gradient(135deg, var(--c-emerald), var(--c-cyan), var(--c-violet), var(--c-pink));
            background-size: 400% auto;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            animation: megaShimmer 5s ease infinite;
            filter: drop-shadow(0 0 25px rgba(52,211,153,0.15));
            position: relative; z-index: 2;
        }
        .live-label {
            font-family: var(--font-body);
            font-size: var(--fs-caption); color: var(--c-text-secondary);
            font-weight: 600; margin-top: 8px;
            position: relative; z-index: 2;
            letter-spacing: 0.02em;
        }

        /* ── Ranking Sections ── */
        .rankings-wrap {
            display: flex; flex-direction: column; gap: 16px;
            max-width: 100%;
            overflow: hidden;
        }
        .ranking-section {
            animation: cardEnter 0.7s cubic-bezier(0.22,1,0.36,1) both;
        }
        .ranking-section:nth-child(1) { animation-delay: 0.4s; }
        .ranking-section:nth-child(2) { animation-delay: 0.48s; }
        .ranking-section:nth-child(3) { animation-delay: 0.56s; }
        .ranking-section:nth-child(4) { animation-delay: 0.64s; }
        .ranking-section:nth-child(5) { animation-delay: 0.72s; }
        .ranking-section:nth-child(6) { animation-delay: 0.80s; }
        .ranking-section:nth-child(7) { animation-delay: 0.88s; }

        .ranking-header {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 16px;
            background: var(--c-surface);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            font-family: var(--font-display);
            font-size: var(--fs-section-title); font-weight: 800;
            line-height: var(--lh-tight);
            margin-bottom: 10px;
            position: relative; overflow: hidden;
        }
        .ranking-header::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.025), transparent);
            animation: sweepShine 5s ease-in-out infinite;
            pointer-events: none;
        }
        @keyframes sweepShine {
            0%,100% { transform: translateX(-100%); }
            50% { transform: translateX(100%); }
        }
        .rh-icon {
            width: 40px; height: 40px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .rh-icon.v1 { background: linear-gradient(135deg, rgba(139,92,246,0.25), rgba(99,102,241,0.25)); }
        .rh-icon.v2 { background: linear-gradient(135deg, rgba(236,72,153,0.25), rgba(244,114,182,0.25)); }
        .rh-icon.v3 { background: linear-gradient(135deg, rgba(6,182,212,0.25), rgba(34,211,238,0.25)); }
        .rh-icon.v4 { background: linear-gradient(135deg, rgba(16,185,129,0.25), rgba(52,211,153,0.25)); }
        .rh-icon.v5 { background: linear-gradient(135deg, rgba(245,158,11,0.25), rgba(251,191,36,0.25)); }
        .rh-icon.v6 { background: linear-gradient(135deg, rgba(99,102,241,0.25), rgba(139,92,246,0.25)); }
        .rh-icon.v7 { background: linear-gradient(135deg, rgba(239,68,68,0.25), rgba(248,113,113,0.25)); }

        .ranking-cards {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;
            max-width: 100%;
            overflow: hidden;
        }

        .rank-card {
            background: var(--c-surface);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            padding: 28px 8px 16px;
            text-align: center;
            position: relative; overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .rank-card::after {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 50% 0%, rgba(139,92,246,0.05), transparent 60%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        .rank-card:active {
            transform: scale(0.96);
            border-color: var(--c-border-glow);
            box-shadow: 0 8px 30px rgba(139,92,246,0.12);
        }
        .rank-card:active::after { opacity: 1; }

        /* Medal badges */
        .rank-badge {
            position: absolute; top: 6px; left: 50%; transform: translateX(-50%);
            width: 26px; height: 26px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-body);
            font-size: var(--fs-micro); font-weight: 900; color: #fff; z-index: 3;
        }
        .badge-gold {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 3px 14px rgba(245,158,11,0.5);
            animation: goldPulse 2.5s ease-in-out infinite;
        }
        @keyframes goldPulse {
            0%,100% { box-shadow: 0 3px 14px rgba(245,158,11,0.5); transform: translateX(-50%) scale(1); }
            50% { box-shadow: 0 3px 22px rgba(245,158,11,0.8); transform: translateX(-50%) scale(1.1); }
        }
        .badge-silver {
            background: linear-gradient(135deg, #9ca3af, #6b7280);
            box-shadow: 0 3px 10px rgba(156,163,175,0.3);
        }
        .badge-bronze {
            background: linear-gradient(135deg, #cd7f32, #a0522d);
            box-shadow: 0 3px 10px rgba(205,127,50,0.3);
        }

        .rank-avatar {
            width: 58px; height: 58px;
            border-radius: 18px;
            margin: 8px auto 10px;
            background-size: cover; background-position: center;
            border: 2px solid rgba(255,255,255,0.06);
            box-shadow: 0 6px 22px rgba(0,0,0,0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .rank-card:active .rank-avatar {
            transform: scale(1.08);
            box-shadow: 0 8px 28px rgba(139,92,246,0.15);
        }
        .rank-name {
            font-family: var(--font-display);
            font-size: var(--fs-caption); font-weight: 700;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            padding: 0 4px; margin-bottom: 4px;
            color: var(--c-text);
            line-height: var(--lh-normal);
        }
        .rank-val {
            font-family: var(--font-body);
            font-size: var(--fs-micro); font-weight: 700;
            background: linear-gradient(135deg, var(--c-gold), var(--c-pink));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        /* ── Empty / Error States ── */
        .ranking-empty, .ranking-error {
            grid-column: 1 / -1;
            text-align: center;
            padding: 28px 16px;
        }
        .ranking-empty-icon, .ranking-error-icon {
            font-size: 28px; margin-bottom: 6px;
        }
        .ranking-empty-text, .ranking-error-text {
            color: var(--c-text-secondary);
            font-family: var(--font-body);
            font-size: var(--fs-caption); font-weight: 600;
        }
        .ranking-retry-btn {
            margin-top: 10px;
            padding: 8px 20px;
            background: rgba(139,92,246,0.15);
            border: 1px solid rgba(139,92,246,0.25);
            border-radius: 50px;
            color: var(--c-violet);
            font-family: var(--font-body);
            font-size: var(--fs-caption); font-weight: 700;
            cursor: pointer;
            min-height: 36px;
            transition: all 0.3s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .ranking-retry-btn:active {
            transform: scale(0.95);
            background: rgba(139,92,246,0.25);
        }

        /* ── Skeleton Loading ── */
        .skeleton-pulse {
            background: linear-gradient(90deg, rgba(255,255,255,0.03) 25%, rgba(255,255,255,0.07) 50%, rgba(255,255,255,0.03) 75%);
            background-size: 400% 100%;
            animation: skeletonMove 1.5s ease-in-out infinite;
        }
        @keyframes skeletonMove {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .skeleton-card .rank-avatar {
            background-image: none !important;
            background-color: rgba(255,255,255,0.04);
        }
        .skeleton-line { display: block; }
        .rank-card.loaded {
            animation: cardReveal 0.45s ease both;
        }
        @keyframes cardReveal {
            from { opacity: 0; transform: translateY(14px) scale(0.94); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── Epic Message ── */
        .epic-section {
            margin-top: 16px;
            margin-bottom: 8px;
            animation: cardEnter 0.9s cubic-bezier(0.22,1,0.36,1) 1.2s both;
        }
        .epic-card {
            padding: 30px 20px;
        }
        .epic-glow-bar {
            position: absolute; top: 0; left: 0; right: 0; height: 3px; z-index: 2;
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            background: linear-gradient(90deg, var(--c-violet), var(--c-pink), var(--c-gold), var(--c-emerald), var(--c-cyan), var(--c-violet));
            background-size: 300% auto;
            animation: glowSlide 3s linear infinite;
        }
        .epic-title {
            font-family: var(--font-display);
            font-size: var(--fs-epic-title); font-weight: 900;
            text-align: center;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--c-gold), var(--c-pink), var(--c-violet), var(--c-cyan));
            background-size: 300% auto;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            animation: megaShimmer 5s ease infinite;
            line-height: 1.35;
            position: relative; z-index: 2;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        .epic-text {
            font-family: var(--font-body);
            font-size: var(--fs-body); line-height: var(--lh-relaxed);
            text-align: center;
            color: var(--c-text-secondary);
            position: relative; z-index: 2;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        .epic-text strong {
            color: var(--c-gold);
            -webkit-text-fill-color: unset;
        }
        .epic-highlight {
            display: block;
            font-family: var(--font-display);
            font-size: clamp(15px, 4.5vw, 19px); font-weight: 900;
            color: var(--c-gold);
            margin: 16px 0;
            animation: epicPulse 3s ease-in-out infinite;
        }
        @keyframes epicPulse {
            0%,100% { text-shadow: 0 0 10px rgba(251,191,36,0.2); transform: scale(1); }
            50% { text-shadow: 0 0 20px rgba(251,191,36,0.45); transform: scale(1.02); }
        }
        .bounce-emoji {
            display: inline-block;
            animation: emojiBounce 2s ease-in-out infinite;
        }
        .bounce-emoji:nth-child(even) { animation-delay: 0.3s; }
        @keyframes emojiBounce {
            0%,100% { transform: translateY(0) rotate(0deg); }
            30% { transform: translateY(-7px) rotate(-4deg); }
            60% { transform: translateY(-4px) rotate(3deg); }
        }

        /* ── Ripple Effect ── */
        @keyframes rippleFx {
            to { transform: scale(3); opacity: 0; }
        }

        /* ══════════════════════════════════════════════════
           RESPONSIVE — MOBILE FIRST
        ══════════════════════════════════════════════════ */
        @media (min-width: 430px) {
            .app-container { padding: 0 20px; padding-bottom: calc(48px + var(--safe-bottom)); }
        }

        @media (max-width: 360px) {
            .flag-emoji { font-size: 60px; }
            .sa-avatar { width: 64px; height: 64px; border-radius: 18px; }
            .sa-avatar-aura { border-radius: 22px; }
            .rank-avatar { width: 48px; height: 48px; border-radius: 14px; }
            .rank-badge { width: 22px; height: 22px; font-size: 10px; border-radius: 7px; }
            .ranking-header { padding: 12px 14px; }
            .rh-icon { width: 36px; height: 36px; font-size: 18px; }
            .nebula { transform: scale(0.7); }
        }

        @media (max-width: 320px) {
            .app-container { padding: 0 12px; }
            .flag-emoji { font-size: 52px; }
            .sa-card { padding: 22px 14px; }
            .sa-stat-card { padding: 12px 4px; }
            .live-card { padding: 22px 14px; }
            .rank-card { padding: 24px 4px 12px; border-radius: 14px; }
            .rank-avatar { width: 40px; height: 40px; border-radius: 12px; }
            .epic-card { padding: 24px 12px; }
            .nebula { transform: scale(0.5); }
        }

        @media (min-width: 390px) {
            .rank-avatar { width: 62px; height: 62px; }
        }

        /* ── Reduced Motion ── */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            .cosmos, .nebula-layer, .stars-layer, .shooting-star-layer,
            .dust-layer, .planet-layer, .orbit-layer, #particleCanvas {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<a href="#main-content" class="skip-link">{{ __('Skip to main content') }}</a>

<!-- ══ COSMIC BACKGROUND LAYERS ══ -->
<!-- L0: Deep space -->
<div class="cosmos" aria-hidden="true"></div>

<!-- L1: Nebula orbs -->
<div class="nebula-layer" aria-hidden="true">
    <div class="nebula nebula-1"></div>
    <div class="nebula nebula-2"></div>
    <div class="nebula nebula-3"></div>
    <div class="nebula nebula-4"></div>
</div>

<!-- L2: Twinkling stars -->
<div class="stars-layer" id="starsLayer" aria-hidden="true"></div>

<!-- L3: Shooting stars -->
<div class="shooting-star-layer" aria-hidden="true">
    <div class="shooting-star"></div>
    <div class="shooting-star"></div>
    <div class="shooting-star"></div>
</div>

<!-- L4: Cosmic dust -->
<div class="dust-layer" id="dustLayer" aria-hidden="true"></div>

<!-- L5: Floating planets -->
<div class="planet-layer" aria-hidden="true">
    <div class="planet planet-1"></div>
    <div class="planet planet-2"></div>
    <div class="planet planet-3"></div>
</div>

<!-- L6: Orbiting lights -->
<div class="orbit-layer" aria-hidden="true">
    <div class="orbit-light orbit-light-1"></div>
    <div class="orbit-light orbit-light-2"></div>
</div>

<!-- L7: Canvas particles -->
<canvas id="particleCanvas" aria-hidden="true"></canvas>

<!-- ══ LANGUAGE SWITCHER ══ -->
<nav class="lang-bar" aria-label="{{ __('Language switcher') }}">
    <div class="lang-bar-inner">
        @php $languages = \App\Models\Language::where('is_enabled', 1)->pluck('name', 'code'); @endphp
        @foreach($languages as $key => $language)
            <button type="button"
                    class="language lang-btn {{ app()->getLocale() === $key ? 'active' : '' }}"
                    data-id="{{ $key }}"
                    aria-label="{{ __('Switch language to :lang', ['lang' => $language]) }}"
                    {{ app()->getLocale() === $key ? 'aria-current=true' : '' }}>
                {{ $language }}
            </button>
        @endforeach
    </div>
</nav>

<main id="main-content">
<div class="app-container">

    <!-- ══ HERO ══ -->
    <header class="hero">
        <div class="flag-container">
            <div class="flag-ring flag-ring-1" aria-hidden="true"></div>
            <div class="flag-ring flag-ring-2" aria-hidden="true"></div>
            <div class="flag-ring flag-ring-3" aria-hidden="true"></div>
            <div class="flag-sparkle flag-sparkle-1" aria-hidden="true"></div>
            <div class="flag-sparkle flag-sparkle-2" aria-hidden="true"></div>
            <span class="flag-emoji" role="img" aria-label="{{ $countryName }} {{ __('flag') }}">{{ $country->iso }}</span>
        </div>
        <h1 class="hero-title">{{ $countryName }}</h1>
        <p class="hero-subtitle">{{ config('app.name') }}</p>
    </header>

    <!-- ══ SUPER ADMIN ══ -->
    @if($superAdmin)
    <section class="sa-section" aria-label="{{ __('Super Admin') }}">
        <div class="glass-card sa-card"
             role="button"
             tabindex="0"
             aria-label="{{ __('View Super Admin profile') }}: {{ $superAdmin->name ?? __('Unknown') }}"
             data-user-id="{{ $superAdmin->appUser?->id ?? '' }}">
            <div class="sa-glow-top" aria-hidden="true"></div>
            <div class="sa-crown" aria-hidden="true">👑</div>
            <div class="sa-badge" aria-hidden="true">🌟 {{ __('Super Admin') }} 🌟</div>
            <div class="sa-profile">
                <div class="sa-avatar-wrap">
                    @php
                        $avatarUrl = getImagePath($superAdmin->appUser?->profile?->avatar)
                            ?? "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='76' height='76'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%238b5cf6'/%3E%3Cstop offset='1' stop-color='%23ec4899'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='76' height='76' rx='22' fill='url(%23g)'/%3E%3C/svg%3E";
                    @endphp
                    <img src="{{ $avatarUrl }}" alt="{{ $superAdmin->name ?? __('Admin') }}" class="sa-avatar" loading="eager"/>
                    <div class="sa-avatar-aura" aria-hidden="true"></div>
                    <div class="sa-avatar-aura" aria-hidden="true"></div>
                </div>
                <div class="sa-info">
                    <div class="sa-name">{{ $superAdmin->name ?? '' }}</div>
                    <div class="sa-id">ID: {{ $superAdmin->id ?? '' }}</div>
                </div>
            </div>
            <div class="sa-stats">
                <div class="sa-stat-card">
                    <div class="sa-stat-label">{{ __('Sending Level') }}</div>
                    <div class="sa-stat-value">75</div>
                </div>
                <div class="sa-stat-card">
                    <div class="sa-stat-label">{{ __('Receiving Level') }}</div>
                    <div class="sa-stat-value">82</div>
                </div>
                <div class="sa-stat-card">
                    <div class="sa-stat-label">{{ __('Recharge Level') }}</div>
                    <div class="sa-stat-value">90</div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- ══ LIVE USERS ══ -->
    <section class="live-section" aria-label="{{ __('Active Users') }}">
        <div class="glass-card live-card" role="status" aria-live="polite" aria-atomic="true">
            <div class="live-indicator">
                <span class="live-dot-wrap" aria-hidden="true">
                    <span class="live-dot"></span>
                    <span class="live-dot-ping"></span>
                    <span class="live-dot-ping"></span>
                </span>
                {{ __('LIVE NOW') }}
            </div>
            <div class="live-count" id="liveNum" aria-hidden="true">{{ $onlineUsers }}</div>
            <span class="sr-only" id="liveNumA11y">{{ number_format($onlineUsers) }} {{ __('Active Users') }}</span>
            <div class="live-label">{{ __('Active Users') }}</div>
        </div>
    </section>

    <!-- ══ RANKINGS ══ -->
    <div id="rankings-container" class="rankings-wrap">
        @php
            $sections = [
                ['key' => 'topRooms',          'icon' => '🏠', 'class' => 'v1', 'title' => __('Top 3 Entertainment Rooms') . ' 🎉',    'unit' => __('members'),  'emoji' => ''],
                ['key' => 'topSenders',        'icon' => '💎', 'class' => 'v2', 'title' => __('Top 3 Generous Supporters') . ' 💰',     'unit' => '',             'emoji' => '💎'],
                ['key' => 'topReceivers',      'icon' => '🎤', 'class' => 'v3', 'title' => __('Top 3 Star Hosts') . ' ⭐',               'unit' => '',             'emoji' => '💎'],
                ['key' => 'topAgencies',       'icon' => '🏢', 'class' => 'v4', 'title' => __('Top 3 Host Agencies') . ' 🚀',           'unit' => __('hosts'),    'emoji' => ''],
                ['key' => 'topChargeAgencies', 'icon' => '💰', 'class' => 'v5', 'title' => __('Top 3 Recharge Agencies') . ' 💵',       'unit' => '',             'emoji' => '💵'],
                ['key' => 'topBds',            'icon' => '👥', 'class' => 'v6', 'title' => __('Top 3 Most Active BD') . ' 🎯',          'unit' => __('agency'),   'emoji' => ''],
                ['key' => 'topGamers',         'icon' => '🎮', 'class' => 'v7', 'title' => __('Top 3 Gamers') . ' 🏅',                  'unit' => '',             'emoji' => '⚡'],
            ];
        @endphp

        @foreach($sections as $sec)
        <section class="ranking-section" data-section="{{ $sec['key'] }}" aria-labelledby="heading-{{ $sec['key'] }}">
            <h3 class="ranking-header" id="heading-{{ $sec['key'] }}">
                <span class="rh-icon {{ $sec['class'] }}" aria-hidden="true">{{ $sec['icon'] }}</span>
                {{ $sec['title'] }}
            </h3>
            <div class="ranking-cards" id="cards-{{ $sec['key'] }}" data-unit="{{ $sec['unit'] }}" data-emoji="{{ $sec['emoji'] }}" data-rank-label="{{ __('Rank') }}" aria-busy="true" aria-label="{{ __('Loading rankings') }}">
                @for($i = 0; $i < 3; $i++)
                <div class="rank-card skeleton-card" aria-hidden="true">
                    <div class="rank-badge {{ $i===0?'badge-gold':($i===1?'badge-silver':'badge-bronze') }}">{{ $i+1 }}</div>
                    <div class="rank-avatar skeleton-pulse"></div>
                    <div class="skeleton-line skeleton-pulse" style="width:65%;height:12px;margin:5px auto;border-radius:6px;"></div>
                    <div class="skeleton-line skeleton-pulse" style="width:45%;height:10px;margin:3px auto;border-radius:6px;"></div>
                </div>
                @endfor
            </div>
        </section>
        @endforeach
    </div>

    <!-- ══ EPIC MESSAGE ══ -->
    <section class="epic-section" aria-labelledby="epic-heading">
        <div class="glass-card epic-card">
            <div class="epic-glow-bar" aria-hidden="true"></div>
            <h2 class="epic-title" id="epic-heading">
                {{ $country->iso }} {{ __('Epic Message to Heroes of :country', ['country' => $countryName]) }} {{ $country->iso }}
            </h2>
            <p class="epic-text">
                <span class="bounce-emoji" aria-hidden="true">🔥</span> {{ __(':app LIFE Legends', ['app' => config('app.name')]) }} <span class="bounce-emoji" aria-hidden="true">🔥</span><br/><br/>
                {{ __('You are not just players... You are the Entertainment Army!') }} <span class="bounce-emoji" aria-hidden="true">🎮</span><br/>
                {{ __('Every room you open becomes an arena of joy and laughter!') }} <span class="bounce-emoji" aria-hidden="true">🎉</span><br/>
                {{ __('Every gift you send plants smiles on faces!') }} <span class="bounce-emoji" aria-hidden="true">💝</span><br/>
                {{ __('Every game you play writes :country\'s name in golden letters!', ['country' => $countryName]) }} <span class="bounce-emoji" aria-hidden="true">⚡</span><br/><br/>
                <strong class="epic-highlight"><span class="bounce-emoji" aria-hidden="true">🏆</span> {{ __('Make the World Dance to :country\'s rhythm', ['country' => $countryName]) }} <span class="bounce-emoji" aria-hidden="true">🏆</span></strong>
                {{ __('Play... Dance... Sing... Laugh... Spread Happiness!') }} <span class="bounce-emoji" aria-hidden="true">🎊</span><br/>
                {{ __('Make every minute in :app LIFE an authentic celebration!', ['app' => config('app.name')]) }} <span class="bounce-emoji" aria-hidden="true">🎪</span><br/><br/>
                <strong>{{ __(':country is strong with you... First place awaits!', ['country' => $countryName]) }} <span class="bounce-emoji" aria-hidden="true">🦅</span></strong>
            </p>
        </div>
    </section>

</div>
</main>

<!-- ══ SCRIPTS ══ -->
<script>
(function(){
    'use strict';

    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var isMobile = window.innerWidth <= 768;

    // ── Language Switcher ──
    document.querySelectorAll('.language').forEach(function(btn) {
        btn.addEventListener('click', function() {
            fetch("{{ url('/locale') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: new URLSearchParams({ locale: this.dataset.id })
            }).then(function() { location.reload(); });
        });
    });

    function sendMessage(userId) {
        window.postMessage('open_profile:' + userId, '*');
    }

    // ══ BACKGROUND ANIMATIONS ══
    if (!prefersReducedMotion) {

        // ── Twinkling Stars (CSS-driven) ──
        (function() {
            var layer = document.getElementById('starsLayer');
            if (!layer) return;
            var types = ['s-bright', 's-dim', 's-cold'];
            var count = isMobile ? 55 : 100;
            for (var i = 0; i < count; i++) {
                var s = document.createElement('div');
                s.className = 'twinkle-star ' + types[Math.floor(Math.random() * 3)];
                var sz = 1 + Math.random() * 2.5;
                s.style.cssText = 'width:' + sz + 'px;height:' + sz + 'px;left:' +
                    Math.random() * 100 + '%;top:' + Math.random() * 100 +
                    '%;animation-delay:' + (Math.random() * 6) +
                    's;animation-duration:' + (2 + Math.random() * 4) + 's';
                layer.appendChild(s);
            }
        })();

        // ── Cosmic Dust Particles ──
        (function() {
            var layer = document.getElementById('dustLayer');
            if (!layer) return;
            var colors = [
                'rgba(167,139,250,0.45)',
                'rgba(244,114,182,0.35)',
                'rgba(34,211,238,0.35)',
                'rgba(251,191,36,0.3)',
                'rgba(52,211,153,0.3)',
                'rgba(248,113,113,0.25)'
            ];
            var count = isMobile ? 18 : 35;
            for (var i = 0; i < count; i++) {
                var d = document.createElement('div');
                d.className = 'dust-particle';
                var sz = 2 + Math.random() * 5;
                var c = colors[Math.floor(Math.random() * colors.length)];
                d.style.cssText = 'width:' + sz + 'px;height:' + sz + 'px;left:' +
                    Math.random() * 100 + '%;animation-duration:' +
                    (14 + Math.random() * 20) + 's;animation-delay:' +
                    (Math.random() * 18) + 's;background:' + c +
                    ';box-shadow:0 0 ' + (sz * 2.5) + 'px ' + c;
                layer.appendChild(d);
            }
        })();

        // ── Canvas Particle System (lightweight floating particles with parallax) ──
        (function() {
            var canvas = document.getElementById('particleCanvas');
            if (!canvas) return;
            var ctx = canvas.getContext('2d');
            var dpr = Math.min(window.devicePixelRatio || 1, 2);
            var W, H;
            var particles = [];
            var particleCount = isMobile ? 25 : 45;
            var raf;

            function resize() {
                W = window.innerWidth;
                H = window.innerHeight;
                canvas.width = W * dpr;
                canvas.height = H * dpr;
                canvas.style.width = W + 'px';
                canvas.style.height = H + 'px';
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            }

            function createParticle() {
                var depth = 0.3 + Math.random() * 0.7;
                return {
                    x: Math.random() * W,
                    y: Math.random() * H,
                    r: (0.5 + Math.random() * 1.5) * depth,
                    vx: (Math.random() - 0.5) * 0.15 * depth,
                    vy: -0.08 - Math.random() * 0.12 * depth,
                    alpha: (0.1 + Math.random() * 0.25) * depth,
                    baseAlpha: 0,
                    pulse: Math.random() * Math.PI * 2,
                    pulseSpeed: 0.005 + Math.random() * 0.015,
                    hue: Math.random() > 0.6 ? (260 + Math.random() * 40) : (180 + Math.random() * 30),
                    depth: depth
                };
            }

            function init() {
                resize();
                particles = [];
                for (var i = 0; i < particleCount; i++) {
                    particles.push(createParticle());
                }
            }

            function draw() {
                ctx.clearRect(0, 0, W, H);
                for (var i = 0; i < particles.length; i++) {
                    var p = particles[i];
                    p.x += p.vx;
                    p.y += p.vy;
                    p.pulse += p.pulseSpeed;
                    p.baseAlpha = p.alpha * (0.6 + 0.4 * Math.sin(p.pulse));

                    if (p.y < -10) { p.y = H + 10; p.x = Math.random() * W; }
                    if (p.x < -10) p.x = W + 10;
                    if (p.x > W + 10) p.x = -10;

                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = 'hsla(' + p.hue + ',70%,70%,' + p.baseAlpha + ')';
                    ctx.fill();

                    if (p.r > 1 && p.baseAlpha > 0.15) {
                        ctx.beginPath();
                        ctx.arc(p.x, p.y, p.r * 3, 0, Math.PI * 2);
                        ctx.fillStyle = 'hsla(' + p.hue + ',70%,70%,' + (p.baseAlpha * 0.15) + ')';
                        ctx.fill();
                    }
                }
                raf = requestAnimationFrame(draw);
            }

            init();
            draw();

            var resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    resize();
                }, 200);
            });

            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    cancelAnimationFrame(raf);
                } else {
                    draw();
                }
            });
        })();
    }

    // ══ UI INTERACTIONS ══
    document.addEventListener('DOMContentLoaded', function() {

        // ── Counter Animation ──
        var el = document.getElementById('liveNum');
        if (el) {
            var target = parseInt(el.textContent.replace(/,/g, ''));
            if (!isNaN(target)) {
                if (prefersReducedMotion) {
                    el.textContent = target.toLocaleString();
                } else {
                    var current = 0;
                    var step = Math.ceil(target / 55);
                    var timer = setInterval(function() {
                        current += step;
                        if (current >= target) { current = target; clearInterval(timer); }
                        el.textContent = current.toLocaleString();
                    }, 22);
                }
            }
        }

        // ── SA Card Interaction ──
        var saCard = document.querySelector('.sa-card');
        if (saCard) {
            var userId = saCard.dataset.userId;
            function handleActivate() {
                if (userId) sendMessage(userId);
            }
            saCard.addEventListener('click', handleActivate);
            saCard.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    handleActivate();
                }
            });
        }

        // ── Ripple on Tap ──
        document.querySelectorAll('.rank-card, .sa-stat-card').forEach(function(el) {
            el.addEventListener('click', function(e) {
                var r = document.createElement('div');
                var rect = this.getBoundingClientRect();
                var sz = Math.max(rect.width, rect.height) * 2.5;
                Object.assign(r.style, {
                    position: 'absolute', width: sz + 'px', height: sz + 'px', borderRadius: '50%',
                    background: 'radial-gradient(circle,rgba(139,92,246,0.2),rgba(236,72,153,0.08),transparent)',
                    left: (e.clientX - rect.left - sz / 2) + 'px',
                    top: (e.clientY - rect.top - sz / 2) + 'px',
                    transform: 'scale(0)', animation: 'rippleFx 0.7s ease-out',
                    pointerEvents: 'none', zIndex: '10'
                });
                this.appendChild(r);
                setTimeout(function() { r.remove(); }, 700);
            });
        });

        // ── Scroll Reveal ──
        if ('IntersectionObserver' in window) {
            var obs = new IntersectionObserver(function(entries) {
                entries.forEach(function(e) {
                    if (e.isIntersecting) {
                        e.target.style.animationPlayState = 'running';
                        obs.unobserve(e.target);
                    }
                });
            }, { threshold: 0.08 });
            document.querySelectorAll('.ranking-section').forEach(function(s) { obs.observe(s); });
        }

        // ── AJAX Lazy Load Rankings ──
        var statsUrl = "{{ url('country/' . $country->id . '/stats') }}";
        var badges = ['badge-gold', 'badge-silver', 'badge-bronze'];
        var defaultAvatar = "{{ asset('images/businessman-icon.jpg') }}";
        var defaultBdBg = 'linear-gradient(135deg,rgba(99,102,241,0.3),rgba(168,85,247,0.3))';
        var noDataText = "{{ __('No data available yet') }}";
        var errorText = "{{ __('Could not load data. Please refresh the page.') }}";
        var retryText = "{{ __('Retry') }}";

        fetch(statsUrl, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var sectionKeys = ['topRooms', 'topSenders', 'topReceivers', 'topAgencies', 'topChargeAgencies', 'topBds', 'topGamers'];
            sectionKeys.forEach(function(key, si) {
                var container = document.getElementById('cards-' + key);
                if (!container) return;
                var items = data[key] || [];
                var unit = container.dataset.unit || '';
                var emoji = container.dataset.emoji || '';
                var rankLabel = container.dataset.rankLabel || 'Rank';

                container.setAttribute('aria-busy', 'false');
                container.removeAttribute('aria-label');
                container.innerHTML = '';

                if (items.length === 0) {
                    container.innerHTML = '<div class="ranking-empty" role="status">' +
                        '<div class="ranking-empty-icon">📭</div>' +
                        '<div class="ranking-empty-text">' + noDataText + '</div>' +
                        '</div>';
                    return;
                }

                items.forEach(function(item, i) {
                    var card = document.createElement('div');
                    card.className = 'rank-card loaded';
                    card.style.animationDelay = (si * 0.06 + i * 0.1) + 's';
                    card.setAttribute('aria-label', (item.name || '-') + ', ' + rankLabel + ' ' + (i + 1));

                    var avatarStyle = (key === 'topBds' && !item.image)
                        ? 'background:' + defaultBdBg
                        : "background-image:url('" + (item.image || defaultAvatar) + "');background-size:cover;background-position:center";

                    var valText = unit ? item.value + ' ' + unit : item.value + (emoji ? ' ' + emoji : '');

                    card.innerHTML =
                        '<div class="rank-badge ' + badges[i] + '" aria-hidden="true">' + (i + 1) + '</div>' +
                        '<div class="rank-avatar" style="' + avatarStyle + '" role="img" aria-label="' + (item.name || '') + '"></div>' +
                        '<div class="rank-name">' + (item.name || '-') + '</div>' +
                        '<div class="rank-val">' + valText + '</div>';

                    container.appendChild(card);
                });
            });
        })
        .catch(function(err) {
            console.error('Stats load error:', err);
            document.querySelectorAll('.ranking-cards').forEach(function(container) {
                container.setAttribute('aria-busy', 'false');
                container.removeAttribute('aria-label');
                container.innerHTML = '<div class="ranking-error" role="alert">' +
                    '<div class="ranking-error-icon">⚠️</div>' +
                    '<div class="ranking-error-text">' + errorText + '</div>' +
                    '<button type="button" class="ranking-retry-btn" onclick="location.reload()">' + retryText + '</button>' +
                    '</div>';
            });
        });
    });
})();
</script>
</body>
</html>
