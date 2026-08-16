<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @php
        $countryName = app()->getLocale() == 'ar' ? $country->name : $country->e_name;
    @endphp
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} – {{ $countryName }}</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            background: #0a0e27;
        }

        /* خلفية متحركة خرافية */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: linear-gradient(270deg, #0a0e27, #1a1f4e, #2d1b69, #6b2d91, #ff006e, #ff4500, #ffd700);
            background-size: 1400% 1400%;
            animation: gradientWave 20s ease infinite;
        }

        @keyframes gradientWave {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        /* جزيئات متحركة */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: radial-gradient(circle, #ffd700 0%, transparent 70%);
            border-radius: 50%;
            animation: floatUp 15s linear infinite;
        }

        @keyframes floatUp {
            from {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            to {
                transform: translateY(-10vh) rotate(720deg);
                opacity: 0;
            }
        }

        /* نجوم متلألئة */
        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s ease-in-out infinite;
        }

        @keyframes twinkle {
            0%, 100% {
                opacity: 0.3;
                transform: scale(1);
            }
            50% {
                opacity: 1;
                transform: scale(1.5);
            }
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        /* زر تبديل اللغة */
        .language-switcher {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 5px;
            border-radius: 50px;
            border: 2px solid rgba(255, 215, 0, 0.5);
            box-shadow: 0 0 30px rgba(255, 215, 0, 0.5);
        }

        .lang-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 50px;
            background: transparent;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
        }

        .lang-btn.active {
            background: linear-gradient(45deg, #ffd700, #ff6b6b);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.8);
        }

        /* رأس الصفحة */
        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .country-flag-container {
            position: relative;
            display: inline-block;
            margin-bottom: 30px;
        }

        .country-flag {
            width: 150px;
            height: 100px;
            font-size: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            filter: drop-shadow(0 0 30px rgba(255, 215, 0, 0.8));
            animation: flagWave 3s ease-in-out infinite;
        }

        @keyframes flagWave {
            0%, 100% {
                transform: rotate(-5deg) scale(1);
            }
            50% {
                transform: rotate(5deg) scale(1.1);
            }
        }

        /* تأثير الهالة المتحركة */
        .glow-rings {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            pointer-events: none;
        }

        .glow-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 3px solid;
            border-radius: 50%;
            animation: pulseRing 3s ease-out infinite;
        }

        .glow-ring:nth-child(1) {
            border-color: #ffd700;
            animation-delay: 0s;
        }

        .glow-ring:nth-child(2) {
            border-color: #ff6b6b;
            animation-delay: 1s;
        }

        .glow-ring:nth-child(3) {
            border-color: #4ecdc4;
            animation-delay: 2s;
        }

        @keyframes pulseRing {
            0% {
                transform: scale(0.5);
                opacity: 1;
            }
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        .country-name {
            font-size: 36px;
            font-weight: 900;
            background: linear-gradient(45deg, #ffd700, #ff6b6b, #4ecdc4, #ffd700);
            background-size: 300% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientText 3s ease infinite;
            text-shadow: 0 0 50px rgba(255, 215, 0, 0.5);
        }

        @keyframes gradientText {
            0%, 100% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
        }

        /* بطاقة السوبر أدمن الفخمة */
        .super-admin-card {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 107, 107, 0.2));
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 25px;
            margin-bottom: 30px;
            border: 3px solid transparent;
            background-origin: border-box;
            background-clip: padding-box, border-box;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transform: perspective(1000px) rotateX(0deg);
            transition: all 0.5s;
            box-shadow: 0 20px 40px rgba(255, 215, 0, 0.3), inset 0 0 20px rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotateX(0deg);
            }
            50% {
                transform: translateY(-10px) rotateX(2deg);
            }
        }

        .super-admin-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ffd700, #ff6b6b, #4ecdc4, #ffd700);
            border-radius: 30px;
            z-index: -1;
            animation: borderRotate 4s linear infinite;
        }

        @keyframes borderRotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .super-admin-card:hover {
            transform: perspective(1000px) rotateX(5deg) translateY(-5px);
            box-shadow: 0 30px 60px rgba(255, 215, 0, 0.5);
        }

        .admin-crown {
            position: absolute;
            top: -15px;
            right: 20px;
            font-size: 40px;
            animation: crownBounce 2s ease-in-out infinite;
            filter: drop-shadow(0 0 20px gold);
        }

        @keyframes crownBounce {
            0%, 100% {
                transform: rotate(-10deg) translateY(0);
            }
            50% {
                transform: rotate(10deg) translateY(-5px);
            }
        }

        .admin-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .admin-avatar {
            width: 90px;
            height: 90px;
            border-radius: 20px;
            border: 4px solid #ffd700;
            margin-left: 20px;
            box-shadow: 0 0 30px rgba(255, 215, 0, 0.6);
            animation: avatarPulse 2s ease-in-out infinite;
        }

        @keyframes avatarPulse {
            0%, 100% {
                box-shadow: 0 0 30px rgba(255, 215, 0, 0.6);
            }
            50% {
                box-shadow: 0 0 50px rgba(255, 215, 0, 1);
            }
        }

        .admin-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
            background: linear-gradient(45deg, #ffd700, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 900;
        }

        .admin-levels {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .level-item {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            padding: 15px;
            border-radius: 20px;
            text-align: center;
            border: 2px solid rgba(255, 215, 0, 0.3);
            position: relative;
            overflow: hidden;
            animation: levelFloat 4s ease-in-out infinite;
        }

        .level-item:nth-child(1) {
            animation-delay: 0s;
        }

        .level-item:nth-child(2) {
            animation-delay: 0.5s;
        }

        .level-item:nth-child(3) {
            animation-delay: 1s;
        }

        @keyframes levelFloat {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        .level-item::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: rotate(45deg);
            animation: levelShine 3s linear infinite;
        }

        @keyframes levelShine {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
        }

        .level-value {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(45deg, #ffd700, #ffaa00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: numberGlow 2s ease-in-out infinite;
        }

        @keyframes numberGlow {
            0%, 100% {
                filter: brightness(1);
            }
            50% {
                filter: brightness(1.5);
            }
        }

        /* إحصائيات مبهرة */
        .stats-section {
            background: linear-gradient(135deg, rgba(78, 205, 196, 0.1), rgba(255, 107, 107, 0.1));
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 25px;
            margin-bottom: 30px;
            border: 2px solid rgba(78, 205, 196, 0.3);
            position: relative;
            overflow: hidden;
        }

        .stats-title {
            font-size: 22px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            color: #fff;
            text-shadow: 0 0 20px rgba(78, 205, 196, 0.8);
            animation: titlePulse 2s ease-in-out infinite;
        }

        @keyframes titlePulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            padding: 20px;
            border-radius: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid rgba(255, 215, 0, 0.3);
            animation: statFloat 5s ease-in-out infinite;
        }

        .stat-card:nth-child(1) {
            animation-delay: 0s;
        }

        .stat-card:nth-child(2) {
            animation-delay: 1s;
        }

        .stat-card:nth-child(3) {
            animation-delay: 2s;
        }

        @keyframes statFloat {
            0%, 100% {
                transform: translateY(0) rotateZ(0deg);
            }
            25% {
                transform: translateY(-5px) rotateZ(1deg);
            }
            75% {
                transform: translateY(5px) rotateZ(-1deg);
            }
        }

        .stat-card:hover {
            transform: scale(1.1) rotateZ(5deg);
            box-shadow: 0 10px 40px rgba(255, 215, 0, 0.5);
            border-color: #ffd700;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 900;
            background: linear-gradient(45deg, #4ecdc4, #ffd700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
            animation: countUp 2s ease-out;
        }

        @keyframes countUp {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* قوائم التوب بتصميم جديد */
        .top-section {
            margin-bottom: 35px;
            animation: sectionSlide 0.8s ease-out;
        }

        @keyframes sectionSlide {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .section-title {
            font-size: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 107, 107, 0.2));
            backdrop-filter: blur(20px);
            border-radius: 20px;
            text-align: center;
            color: #fff;
            font-weight: bold;
            border: 2px solid rgba(255, 215, 0, 0.4);
            position: relative;
            overflow: hidden;
            animation: titleGlow 3s ease-in-out infinite;
        }

        @keyframes titleGlow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
            }
            50% {
                box-shadow: 0 0 40px rgba(255, 215, 0, 0.8);
            }
        }

        .top-list {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .top-item {
            flex: 1;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px 15px;
            text-align: center;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid rgba(255, 215, 0, 0.3);
            transition: all 0.4s;
            animation: topItemFloat 4s ease-in-out infinite;
        }

        .top-item:nth-child(1) {
            animation-delay: 0s;
        }

        .top-item:nth-child(2) {
            animation-delay: 0.5s;
        }

        .top-item:nth-child(3) {
            animation-delay: 1s;
        }

        @keyframes topItemFloat {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        .top-item:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 40px rgba(255, 215, 0, 0.4);
            border-color: #ffd700;
        }

        .top-rank {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #ffd700, #ff6b6b);
            color: #000;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 18px;
            box-shadow: 0 5px 20px rgba(255, 215, 0, 0.6);
            animation: rankPulse 2s ease-in-out infinite;
        }

        @keyframes rankPulse {
            0%, 100% {
                transform: translateX(-50%) scale(1);
                box-shadow: 0 5px 20px rgba(255, 215, 0, 0.6);
            }
            50% {
                transform: translateX(-50%) scale(1.2);
                box-shadow: 0 8px 30px rgba(255, 215, 0, 1);
            }
        }

        .top-avatar {
            width: 70px;
            height: 70px;
            margin: 15px auto;
            border-radius: 15px;
            border: 3px solid #ffd700;
            box-shadow: 0 0 25px rgba(255, 215, 0, 0.5);
            background-size: cover;
            background-position: center;
            animation: avatarRotate 8s linear infinite;
        }

        @keyframes avatarRotate {
            0% {
                transform: rotateY(0deg);
            }
            100% {
                transform: rotateY(360deg);
            }
        }

        .top-name {
            font-size: 14px;
            margin-top: 10px;
            font-weight: bold;
            color: #fff;
        }

        .top-value {
            font-size: 12px;
            color: #ffd700;
            margin-top: 5px;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        /* رسالة تحفيزية ملحمية */
        .motivational-message {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.3), rgba(255, 107, 107, 0.3));
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 30px;
            margin-top: 40px;
            border: 3px solid #ffd700;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 50px rgba(255, 215, 0, 0.5), inset 0 0 30px rgba(255, 255, 255, 0.1);
            animation: messageGlow 3s ease-in-out infinite;
        }

        @keyframes messageGlow {
            0%, 100% {
                box-shadow: 0 0 50px rgba(255, 215, 0, 0.5);
            }
            50% {
                box-shadow: 0 0 80px rgba(255, 215, 0, 0.8);
            }
        }

        .motivational-message::before {
            content: '🔥';
            position: absolute;
            font-size: 150px;
            opacity: 0.1;
            top: -30px;
            right: -30px;
            animation: fireFloat 4s ease-in-out infinite;
        }

        @keyframes fireFloat {
            0%, 100% {
                transform: rotate(-15deg) scale(1);
            }
            50% {
                transform: rotate(15deg) scale(1.2);
            }
        }

        .message-title {
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 20px;
            text-align: center;
            background: linear-gradient(45deg, #ffd700, #ff6b6b, #4ecdc4, #ffd700);
            background-size: 300% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientText 3s ease infinite;
        }

        .message-text {
            font-size: 18px;
            line-height: 2;
            text-align: center;
            color: #fff;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
            animation: textGlow 2s ease-in-out infinite;
        }

        @keyframes textGlow {
            0%, 100% {
                text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
            }
            50% {
                text-shadow: 0 0 20px rgba(255, 255, 255, 0.6);
            }
        }

        .fire-emoji {
            display: inline-block;
            animation: fireJump 1s ease-in-out infinite;
        }

        @keyframes fireJump {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 14, 39, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(10px);
        }

        .loader {
            width: 80px;
            height: 80px;
            border: 6px solid rgba(255, 215, 0, 0.2);
            border-top-color: #ffd700;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* شعاع دوار */
        .rotating-beam {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 200%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #ffd700, transparent);
            transform-origin: center;
            animation: beamRotate 10s linear infinite;
            opacity: 0.3;
            pointer-events: none;
        }

        @keyframes beamRotate {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }
            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* البرق */
        .lightning {
            position: fixed;
            top: -100px;
            width: 2px;
            height: 100vh;
            background: linear-gradient(to bottom, transparent, #ffd700, transparent);
            animation: lightning 4s linear infinite;
            opacity: 0;
        }

        @keyframes lightning {
            0%, 90%, 100% {
                opacity: 0;
            }
            95% {
                opacity: 1;
            }
        }

        .hidden {
            display: none !important;
        }

        .page-title {
            text-align: center;
            font-size: 32px;
            font-weight: 900;
            color: #ffd700;
            margin-top: 40px;
            margin-bottom: 70px;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
            animation: titlePulse 2s ease-in-out infinite;
        }

        /* تأثيرات الموبايل */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .country-name {
                font-size: 28px;
            }

            .stats-grid, .admin-levels {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .top-list {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<!-- الخلفية المتحركة -->
<div class="animated-bg"></div>

<!-- الجزيئات المتحركة -->
<div class="particles" id="particles"></div>

<!-- الشعاع الدوار -->
<div class="rotating-beam"></div>

<!-- البرق -->
<div class="lightning" style="left: 20%;"></div>
<div class="lightning" style="left: 50%; animation-delay: 2s;"></div>
<div class="lightning" style="left: 80%; animation-delay: 3s;"></div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loader"></div>
</div>

<h1 class="page-title">
    {{ config('app.name') }} – {{ $countryName }}
</h1>

<!-- Language Switcher -->
<div class="language-switcher">
    @php
        $languages = \App\Models\Language::where('is_enabled', 1)->pluck('name', 'code');
    @endphp

    @foreach($languages as $key => $language)
        <button type="button" class="language lang-btn {{ app()->getLocale() === $key ? 'active' : '' }}" data-id="{{ $key }}">
            {{ $language }}
        </button>
    @endforeach
</div>

<div class="container" id="mainContent">
    <div class="header">
        <div class="country-flag-container">
            <div class="glow-rings">
                <div class="glow-ring"></div>
                <div class="glow-ring"></div>
                <div class="glow-ring"></div>
            </div>
            <div class="country-flag">{{ $country->iso }}</div>
        </div>
        <h1 class="country-name">{{ $countryName }}</h1>
    </div>

    <!-- Super Admin Card -->
    <div class="super-admin-card" id="superAdminCard" onclick="sendMessage(0)">
        <div class="admin-crown">👑</div>
        <div class="admin-header">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='90' height='90'%3E%3Cdefs%3E%3ClinearGradient id='g'%3E%3Cstop offset='0' stop-color='%23FFD700'/%3E%3Cstop offset='1' stop-color='%23FF6B6B'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='90' height='90' fill='url(%23g)'/%3E%3C/svg%3E" alt="Super Admin" class="admin-avatar"/>
            <div class="admin-info">
                <h3>🌟 {{ __('Super Admin') }} 🌟</h3>
                <p style="color: #fff; font-size: 18px; font-weight: bold;" id="adminName">...</p>
                <p style="color: #ffd700;" id="adminId">...</p>
            </div>
        </div>
        <div class="admin-levels">
            <div class="level-item">
                <div style="color: #fff; margin-bottom: 10px;">{{ __('Sending Level') }}</div>
                <div class="level-value">75</div>
            </div>
            <div class="level-item">
                <div style="color: #fff; margin-bottom: 10px;">{{ __('Receiving Level') }}</div>
                <div class="level-value">82</div>
            </div>
            <div class="level-item">
                <div style="color: #fff; margin-bottom: 10px;">{{ __('Recharge Level') }}</div>
                <div class="level-value">90</div>
            </div>
        </div>
    </div>

    <!-- Online Users -->
    <div class="stats-section">
        <h2 class="stats-title">🎮 {{ __('Active Users') }} 🎮</h2>
        <div style="display:flex;justify-content:center;">
            <div class="stat-card">
                <span class="stat-number" id="onlineUsers">0</span>
            </div>
        </div>
    </div>

    <!-- Top Rooms -->
    <div class="top-section">
        <h3 class="section-title">🏆 {{ __('Top 3 Entertainment Rooms') }} 🎉</h3>
        <div class="top-list" id="topRooms"></div>
    </div>

    <!-- Top Senders -->
    <div class="top-section">
        <h3 class="section-title">💎 {{ __('Top 3 Generous Supporters') }} 💰</h3>
        <div class="top-list" id="topSenders"></div>
    </div>

    <!-- Top Receivers -->
    <div class="top-section">
        <h3 class="section-title">🎤 {{ __('Top 3 Star Hosts') }} ⭐</h3>
        <div class="top-list" id="topReceivers"></div>
    </div>

    <!-- Top Agencies -->
    <div class="top-section">
        <h3 class="section-title">🏢 {{ __('Top 3 Host Agencies') }} 🚀</h3>
        <div class="top-list" id="topAgencies"></div>
    </div>

    <!-- Top Charge Agencies -->
    <div class="top-section">
        <h3 class="section-title">💰 {{ __('Top 3 Recharge Agencies') }} 💵</h3>
        <div class="top-list" id="topChargeAgencies"></div>
    </div>

    <!-- Top BDs -->
    <div class="top-section">
        <h3 class="section-title">👥 {{ __('Top 3 Most Active BD') }} 🎯</h3>
        <div class="top-list" id="topBds"></div>
    </div>

    <!-- Top Gamers -->
    <div class="top-section">
        <h3 class="section-title">🎮 {{ __('Top 3 Gamers') }} 🏅</h3>
        <div class="top-list" id="topGamers"></div>
    </div>

    <!-- Motivational Message -->
    <div class="motivational-message">
        <h2 class="message-title">
            <small>{{ $country->iso }}</small> {{ __('Epic Message to Heroes of :country', ['country' => $countryName]) }} <small>{{ $country->iso }}</small>
        </h2>
        <p class="message-text">
            <span class="fire-emoji">🔥</span> {{ __('TEMPO LIFE Legends') }} <span class="fire-emoji">🔥</span><br/><br/>
            {{ __('You are not just players... You are the Entertainment Army!') }} 🎮<br/>
            {{ __('Every room you open becomes an arena of joy and laughter!') }} 🎉<br/>
            {{ __('Every gift you send plants smiles on faces!') }} 💝<br/>
            {{ __('Every game you play writes :country\'s name in golden letters!', ['country' => $countryName]) }} ⚡<br/><br/>
            <strong style="font-size:24px;color:#ffd700;">🏆 {{ __('Make the World Dance to :country\'s rhythm', ['country' => $countryName]) }} 🏆</strong><br/><br/>
            {{ __('Play... Dance... Sing... Laugh... Spread Happiness!') }} 🎊<br/>
            {{ __('Make every minute in TEMPO LIFE an authentic celebration!') }} 🎪<br/><br/>
            <strong style="font-size:20px;">{{ __(':country is strong with you... First place awaits!', ['country' => $countryName]) }} 🦅</strong>
        </p>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const countryId = {{ $country->id }};
    const defaultAvatar = "{{ asset('images/businessman-icon.jpg') }}";
    const defaultRoomCover = "{{ asset('images/background_room.jpg') }}";
    const defaultAgencyImg = "{{ asset('images/icon-agency.jpg') }}";

    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    // Language Switcher
    $(".language").click(function() {
        let id = $(this).data('id');
        $.post("{{ url('/locale') }}", { locale: id }, () => location.reload());
    });

    // إنشاء الجزيئات المتحركة
    function createParticles() {
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (15 + Math.random() * 10) + 's';
            particlesContainer.appendChild(particle);
        }
    }

    // إنشاء النجوم
    function createStars() {
        const body = document.body;
        for (let i = 0; i < 100; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 3 + 's';
            body.appendChild(star);
        }
    }

    // تأثيرات صوتية عند الضغط
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            document.querySelectorAll('.top-item, .stat-card, .level-item').forEach(item => {
                item.addEventListener('click', function(event) {
                    // تأثير موجة عند الضغط
                    const ripple = document.createElement('div');
                    ripple.style.position = 'absolute';
                    ripple.style.width = '100px';
                    ripple.style.height = '100px';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(255, 255, 255, 0.5)';
                    ripple.style.transform = 'translate(-50%, -50%)';
                    ripple.style.pointerEvents = 'none';
                    ripple.style.animation = 'ripple 0.6s ease-out';

                    const rect = this.getBoundingClientRect();
                    ripple.style.left = event.clientX - rect.left + 'px';
                    ripple.style.top = event.clientY - rect.top + 'px';

                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);

                    setTimeout(() => ripple.remove(), 600);
                });
            });
        }, 1000);
    });

    // Fetch Stats via AJAX
    function fetchStats() {
        $.ajax({
            url: "{{route('country.stats', ['id' => $country->id])}}",
            method: 'GET',
            success: function(data) {
                renderStats(data);
                $('#loadingOverlay').fadeOut(500);
            },
            error: function(xhr) {
                console.error('Error fetching stats:', xhr);
                $('#loadingOverlay').fadeOut(500);
                alert('{{ __("Failed to load data. Please refresh the page.") }}');
            }
        });
    }

    function renderStats(data) {
        // Super Admin
        if (data.superAdmin) {
            $('#adminName').text(data.superAdmin.name || '---');
            $('#adminId').text('ID: ' + (data.superAdmin.id || '---'));
            $('#superAdminCard').attr('onclick', `sendMessage(${data.superAdmin.user?.id || 303})`);
        }

        // Online Users - with animation
        animateNumber($('#onlineUsers'), 0, data.onlineUsers, 1000);

        // Top Rooms
        renderTopRooms(data.topRooms);

        // Top Senders
        renderTopSenders(data.topSenders);

        // Top Receivers
        renderTopReceivers(data.topReceivers);

        // Top Agencies
        renderTopAgencies(data.topAgencies);

        // Top Charge Agencies
        renderTopChargeAgencies(data.topChargeAgencies);

        // Top BDs
        renderTopBds(data.topBds);

        // Top Gamers
        renderTopGamers(data.topGamers);
    }

    // Animate number counting
    function animateNumber(element, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if (current >= end) {
                current = end;
                clearInterval(timer);
            }
            element.text(Math.floor(current).toLocaleString());
        }, 16);
    }

    function renderTopRooms(rooms) {
        const html = rooms.map((room, index) => `
                <div class="top-item">
                    <span class="top-rank">${index + 1}</span>
                    <div class="top-avatar" style="background-image:url('${getImagePath(room.room_cover) || defaultRoomCover}');"></div>
                    <div class="top-name">${room.room_name || '---'}</div>
                    <div class="top-value">${(room.room_visitors_count || 0).toLocaleString()} {{ __('members') }}</div>
                </div>
            `).join('');
        $('#topRooms').html(html);
    }

    function renderTopSenders(senders) {
        const html = senders.map((sender, index) => `
                <div class="top-item">
                    <span class="top-rank">${index + 1}</span>
                    <div class="top-avatar" style="background-image:url('${getImagePath(sender.sender?.profile?.avatar) || defaultAvatar}');"></div>
                    <div class="top-name">${sender.sender?.name || '---'}</div>
                    <div class="top-value">${((sender.total_sent || 0) / 1000).toFixed(1)}K 💎</div>
                </div>
            `).join('');
        $('#topSenders').html(html);
    }

    function renderTopReceivers(receivers) {
        const html = receivers.map((receiver, index) => `
                <div class="top-item">
                    <span class="top-rank">${index + 1}</span>
                    <div class="top-avatar" style="background-image:url('${getImagePath(receiver.receiver?.profile?.avatar) || defaultAvatar}');"></div>
                    <div class="top-name">${receiver.receiver?.name || '---'}</div>
                    <div class="top-value">${((receiver.total_sent || 0) / 1000).toFixed(1)}K 💎</div>
                </div>
            `).join('');
        $('#topReceivers').html(html);
    }

    function renderTopAgencies(agencies) {
        const html = agencies.map((agency, index) => `
                <div class="top-item">
                    <span class="top-rank">${index + 1}</span>
                    <div class="top-avatar" style="background-image:url('${getImagePath(agency.img) || defaultAgencyImg}');"></div>
                    <div class="top-name">${agency.name || '---'}</div>
                    <div class="top-value">${(agency.members_count || 0).toLocaleString()} {{ __('hosts') }}</div>
                </div>
            `).join('');
        $('#topAgencies').html(html);
    }

    function renderTopChargeAgencies(charges) {
        const html = charges.map((charge, index) => {
            const agency = charge.sender_shipping_agency;
            return `
                    <div class="top-item">
                        <span class="top-rank">${index + 1}</span>
                        <div class="top-avatar" style="background-image:url('${getImagePath(agency?.img) || defaultAgencyImg}');"></div>
                        <div class="top-name">${agency?.name || '---'}</div>
                        <div class="top-value">${(charge.amount || 0).toFixed(2)} 💵</div>
                    </div>
                `;
        }).join('');
        $('#topChargeAgencies').html(html);
    }

    function renderTopBds(bds) {
        const html = bds.map((bd, index) => `
                <div class="top-item">
                    <span class="top-rank">${index + 1}</span>
                    <div class="top-name">${bd.name || '---'}</div>
                    <div class="top-value">${(bd.total_members || 0).toLocaleString()} {{ __('agency') }}</div>
                </div>
            `).join('');
        $('#topBds').html(html);
    }

    function renderTopGamers(gamers) {
        const html = gamers.map((gamer, index) => `
                <div class="top-item">
                    <span class="top-rank">${index + 1}</span>
                    <div class="top-avatar" style="background-image:url('${getImagePath(gamer.user?.profile?.avatar) || defaultAvatar}');"></div>
                    <div class="top-name">${gamer.user?.name || '---'}</div>
                    <div class="top-value">${((gamer.coins || 0) / 1000).toFixed(1)}K ⚡</div>
                </div>
            `).join('');
        $('#topGamers').html(html);
    }

    function getImagePath(path) {
        if (!path) return null;
        if (path.startsWith('http')) return path;
        return `/storage/${path}`;
    }

    function sendMessage(userId) {
        const message = `open_profile:${userId}`;
        window.postMessage(message, '*');
        console.log("✅ Sent message to Flutter:", message);
    }

    // إضافة تأثير ripple CSS
    const style = document.createElement('style');
    style.textContent = `
            @keyframes ripple {
                0% {
                    width: 0;
                    height: 0;
                    opacity: 1;
                }
                100% {
                    width: 200px;
                    height: 200px;
                    opacity: 0;
                }
            }
        `;
    document.head.appendChild(style);

    // Initialize on page load
    $(document).ready(function() {
        createParticles();
        createStars();
        fetchStats();

        // Auto-refresh every 5 minutes
        setInterval(fetchStats, 300000);
    });
</script>
</body>
</html>
