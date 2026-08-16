<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - لوحة التحكم</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f0c29;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated Stars Background */
        .stars {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.5); }
        }

        /* Floating Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.5;
            animation: float 15s infinite ease-in-out;
        }

        .orb1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            top: -200px;
            right: -100px;
            animation-delay: 0s;
        }

        .orb2 {
            width: 350px;
            height: 350px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            bottom: -150px;
            left: -100px;
            animation-delay: 3s;
        }

        .orb3 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            top: 50%;
            left: 50%;
            animation-delay: 6s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(50px, -50px) rotate(120deg); }
            66% { transform: translate(-50px, 50px) rotate(240deg); }
        }

        /* Glass Card */
        .login-card {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            padding: 50px 45px;
            max-width: 480px;
            width: 90%;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.5),
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: cardEntry 1s ease;
        }

        @keyframes cardEntry {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-wrapper {
            width: 120px;
            height: 120px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.5),
                        0 0 0 5px rgba(255, 255, 255, 0.1);
            animation: logoFloat 3s ease-in-out infinite;
            position: relative;
        }

        .logo-wrapper::before {
            content: '';
            position: absolute;
            inset: -5px;
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
            border-radius: 32px;
            z-index: -1;
            opacity: 0;
            animation: glow 3s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(5deg); }
        }

        @keyframes glow {
            0%, 100% { opacity: 0; }
            50% { opacity: 0.8; }
        }

        .welcome-text {
            color: white;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, #b8c6ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 15px;
            line-height: 1.5;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .input-label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 14px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .input-container {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 18px 55px 18px 20px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            color: white;
            font-size: 15px;
            outline: none;
            transition: all 0.4s ease;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(102, 126, 234, 0.6);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1),
                        inset 0 2px 10px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 22px;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.3));
            transition: all 0.3s ease;
        }

        .form-input:focus ~ .input-icon {
            transform: translateY(-50%) scale(1.1);
        }

        .password-toggle {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 22px;
            cursor: pointer;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.3));
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            transform: translateY(-50%) scale(1.15);
        }

        /* Options Row */
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .custom-checkbox {
            width: 22px;
            height: 22px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .custom-checkbox.checked {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.5);
        }

        .custom-checkbox::after {
            content: '✓';
            color: white;
            font-size: 14px;
            font-weight: bold;
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s ease;
        }

        .custom-checkbox.checked::after {
            opacity: 1;
            transform: scale(1);
        }

        .remember-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            user-select: none;
        }

        .forgot-password {
            color: #b8c6ff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .forgot-password:hover {
            color: #fff;
            text-decoration: underline;
        }

        /* Login Button */
        .login-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .login-button:hover::before {
            left: 100%;
        }

        .login-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .login-button:active {
            transform: translateY(-1px);
        }

        .button-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 30px 0;
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        }

        /* Social Login */
        .social-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .social-btn {
            padding: 14px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .social-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Footer */
        .card-footer {
            text-align: center;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .signup-link {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .signup-link a {
            color: #b8c6ff;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .signup-link a:hover {
            color: white;
            text-decoration: underline;
        }

        /* Language Toggle */
        .language-toggle {
            position: absolute;
            top: 30px;
            left: 30px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }

        .lang-btn {
            padding: 10px 18px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .lang-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.5);
        }

        .lang-btn:hover:not(.active) {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.25);
        }

        /* Loading State */
        .loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 600px) {
            .login-card {
                padding: 40px 30px;
            }

            .logo-wrapper {
                width: 100px;
                height: 100px;
                font-size: 50px;
            }

            .welcome-text {
                font-size: 26px;
            }

            .social-login {
                grid-template-columns: 1fr;
            }

            .language-toggle {
                top: 20px;
                left: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Generate Stars -->
    <div class="stars" id="stars"></div>

    <!-- Floating Orbs -->
    <div class="orb orb1"></div>
    <div class="orb orb2"></div>
    <div class="orb orb3"></div>

    <!-- Language Toggle -->
    <div class="language-toggle">
        <button class="lang-btn active">العربية</button>
        <button class="lang-btn">English</button>
    </div>

    <!-- Login Card -->
    <div class="login-card">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo-wrapper">🐱</div>
            <h1 class="welcome-text">أهلاً بعودتك</h1>
            <p class="subtitle">سجّل دخولك للوصول إلى لوحة التحكم</p>
        </div>

        <!-- Login Form -->
        <form id="loginForm">
            <!-- Username -->
            <div class="form-group">
                <label class="input-label">اسم المستخدم</label>
                <div class="input-container">
                    <input
                        type="text"
                        class="form-input"
                        placeholder="أدخل اسم المستخدم"
                        value="mktest23"
                        required
                    >
                    <span class="input-icon">👤</span>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label class="input-label">كلمة المرور</label>
                <div class="input-container">
                    <input
                        type="password"
                        class="form-input"
                        id="passwordInput"
                        placeholder="أدخل كلمة المرور"
                        value="password123"
                        required
                    >
                    <span class="input-icon">🔒</span>
                    <span class="password-toggle" onclick="togglePassword()">👁️</span>
                </div>
            </div>

            <!-- Options -->
            <div class="options-row">
                <div class="remember-me" onclick="toggleCheckbox()">
                    <div class="custom-checkbox checked" id="checkbox"></div>
                    <span class="remember-label">تذكرني</span>
                </div>
                <a href="#" class="forgot-password">نسيت كلمة المرور؟</a>
            </div>

            <!-- Login Button -->
            <button type="submit" class="login-button" id="loginBtn">
                <span class="button-content">
                    <span>تسجيل الدخول</span>
                    <span>→</span>
                </span>
            </button>
        </form>

        <!-- Divider -->
        <div class="divider">أو سجّل الدخول باستخدام</div>

        <!-- Social Login -->
        <div class="social-login">
            <button class="social-btn">
                <span>📱</span>
                <span>Google</span>
            </button>
            <button class="social-btn">
                <span>🔵</span>
                <span>Facebook</span>
            </button>
        </div>

        <!-- Footer -->
        <div class="card-footer">
            <p class="signup-link">
                ليس لديك حساب؟ <a href="#">إنشاء حساب جديد</a>
            </p>
        </div>
    </div>

    <script>
        // Generate stars
        const starsContainer = document.getElementById('stars');
        for (let i = 0; i < 100; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 3 + 's';
            starsContainer.appendChild(star);
        }

        // Toggle password visibility
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const toggle = document.querySelector('.password-toggle');
            if (input.type === 'password') {
                input.type = 'text';
                toggle.textContent = '🙈';
            } else {
                input.type = 'password';
                toggle.textContent = '👁️';
            }
        }

        // Toggle checkbox
        function toggleCheckbox() {
            const checkbox = document.getElementById('checkbox');
            checkbox.classList.toggle('checked');
        }

        // Language toggle
        document.querySelectorAll('.lang-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<span class="button-content"><span class="spinner"></span><span>جاري التحقق...</span></span>';

            setTimeout(() => {
                btn.classList.remove('loading');
                btn.innerHTML = '<span class="button-content"><span>✓ تم بنجاح</span></span>';
                setTimeout(() => {
                    alert('تم تسجيل الدخول بنجاح! 🎉');
                    btn.innerHTML = '<span class="button-content"><span>تسجيل الدخول</span><span>→</span></span>';
                }, 1000);
            }, 2000);
        });

        // Input animations
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
