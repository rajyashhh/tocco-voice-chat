<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} - Download Live Streaming App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Animated background particles */
        .bg-animation {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 480px;
            width: 100%;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 40px 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .app-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 25px;
            border-radius: 30px;
            object-fit: cover;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            animation: pulse 2s infinite;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .app-title {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .app-subtitle {
            font-size: 16px;
            color: #718096;
            margin-bottom: 10px;
            font-weight: 400;
        }

        .features {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 25px 0 30px;
            flex-wrap: wrap;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
        }

        .feature-icon {
            color: #667eea;
            font-size: 18px;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 25px 0;
        }

        .download-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
        }

        #download-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .store-btn {
            display: block;
            text-decoration: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .store-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .store-btn:hover::before {
            left: 100%;
        }

        .store-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            position: relative;
            z-index: 1;
        }

        .store-icon {
            width: 32px;
            height: 32px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .store-text {
            text-align: left;
        }

        .store-text-small {
            font-size: 11px;
            opacity: 0.9;
            font-weight: 400;
        }

        .store-text-large {
            font-size: 18px;
            font-weight: 600;
            line-height: 1.2;
        }

        .footer-text {
            margin-top: 25px;
            font-size: 13px;
            color: #a0aec0;
            font-weight: 400;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .card {
                padding: 30px 20px;
            }

            .app-title {
                font-size: 26px;
            }

            .features {
                gap: 15px;
            }

            .feature-item {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation" id="particles"></div>

    <div class="container">
        <div class="card">
            <!-- App Logo -->
            <div class="app-logo">
                <i class="fas fa-video"></i>
            </div>

            <!-- App Title -->
            <h1 class="app-title">{{ $appName }}</h1>
            <p class="app-subtitle">{{ __('Join us and enjoy live streaming like never before!') }}</p>

            <!-- Features -->
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-broadcast-tower feature-icon"></i>
                    <span>{{ __('Live Streaming') }}</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-users feature-icon"></i>
                    <span>{{ __('Connect') }}</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-gift feature-icon"></i>
                    <span>{{ __('Send Gifts') }}</span>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Download Section -->
            <h2 class="download-section-title">{{ __('Download Our App') }}</h2>

            <div id="download-buttons">
                <!-- Dynamic download buttons will be inserted here -->
            </div>

            <p class="footer-text">{{ __('Available on multiple platforms') }}</p>
        </div>
    </div>

<script>
    // Configuration
    const androidLink = "{{ $androidLink }}";
    const iosLink = "{{ $iosLink }}";
    const huaweiLink = "{{ $huaweiLink }}";

    const androidLogo = "{{ asset('images/android_logo_PNG27.png') }}";
    const appleLogo = "{{ asset('images/Apple-IOS-jpg.png') }}";
    const huaweiLogo = "{{ asset('images/huawel.jpg') }}";

    // Create animated background particles
    function createParticles() {
        const particlesContainer = document.getElementById('particles');
        const particleCount = 20;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';

            const size = Math.random() * 60 + 20;
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${Math.random() * 100}%`;
            particle.style.animationDelay = `${Math.random() * 15}s`;
            particle.style.animationDuration = `${Math.random() * 10 + 10}s`;

            particlesContainer.appendChild(particle);
        }
    }

    // Device detection functions
    function isAndroid() {
        return /Android/i.test(navigator.userAgent);
    }

    function isIOS() {
        return /iPhone|iPad|iPod/i.test(navigator.userAgent);
    }

    function isHuawei() {
        return /Huawei|HONOR|HMSCore/i.test(navigator.userAgent);
    }

    // Create store buttons with enhanced design
    function showButtons() {
        const container = document.getElementById('download-buttons');
        let buttons = '';

        const playBtn = `
            <a href="${androidLink}" class="store-btn" style="background: linear-gradient(135deg, #34A853 0%, #4CAF50 100%);">
                <div class="btn-content">
                    <svg class="store-icon" viewBox="0 0 24 24" fill="white">
                        <path d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.5,12.92 20.16,13.19L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z"/>
                    </svg>
                    <div class="store-text">
                        <div class="store-text-small">GET IT ON</div>
                        <div class="store-text-large">Google Play</div>
                    </div>
                </div>
            </a>
        `;

        const appleBtn = `
            <a href="${iosLink}" class="store-btn" style="background: linear-gradient(135deg, #000000 0%, #434343 100%);">
                <div class="btn-content">
                    <svg class="store-icon" viewBox="0 0 24 24" fill="white">
                        <path d="M17.05,20.28C16.03,21.23 14.96,20.74 13.94,20.23C12.86,19.71 11.85,19.66 10.71,20.23C9.33,20.92 8.58,20.37 7.67,19.36C3.17,14.5 3.88,7.36 9.11,7.08C10.37,7.15 11.23,7.82 12.01,7.88C13.17,7.65 14.29,6.96 15.53,7.05C16.99,7.16 18.1,7.72 18.83,8.75C15.84,10.46 16.5,14.43 19.22,15.55C18.67,17 17.96,18.43 17.04,20.26M12.03,7C11.88,5.06 13.43,3.41 15.24,3.2C15.5,5.38 13.13,7.11 12.03,7Z"/>
                    </svg>
                    <div class="store-text">
                        <div class="store-text-small">Download on the</div>
                        <div class="store-text-large">App Store</div>
                    </div>
                </div>
            </a>
        `;

        const huaweiBtn = `
            <a href="${huaweiLink}" class="store-btn" style="background: linear-gradient(135deg, #E31E24 0%, #FF0000 100%);">
                <div class="btn-content">
                    <svg class="store-icon" viewBox="0 0 24 24" fill="white">
                        <path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M12,6A6,6 0 0,1 18,12A6,6 0 0,1 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6Z"/>
                    </svg>
                    <div class="store-text">
                        <div class="store-text-small">EXPLORE IT ON</div>
                        <div class="store-text-large">AppGallery</div>
                    </div>
                </div>
            </a>
        `;

        // Device-specific button display logic
        if (isAndroid()) {
            buttons += playBtn;
        } else if (isIOS()) {
            buttons += appleBtn;
        } else if (isHuawei()) {
            buttons += huaweiBtn;
        } else {
            // Desktop: Show all buttons
            buttons += playBtn + appleBtn + huaweiBtn;
        }

        container.innerHTML = buttons;
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        createParticles();
        showButtons();
    });
</script>
</body>
</html>
