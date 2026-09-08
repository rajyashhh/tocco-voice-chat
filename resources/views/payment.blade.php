<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tocco Voice Live — Payment</title>
    <meta name="description" content="Official Tocco Voice Live TRON TRC20 payment page. Send payment securely through the TRON network.">
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #F0D060;
            --primary-dark: #d4b84e;
            --bg: #0a0a0f;
            --surface: #14141f;
            --surface-hover: #1c1c2a;
            --border: #2a2a3a;
            --text: #f0f0f5;
            --text-muted: #8888a0;
            --success: #22c55e;
            --warning: #f59e0b;
            --radius: 16px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .top-bar {
            width: 100%;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }

        .top-bar a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .top-bar a:hover { color: var(--primary); }

        .top-bar .brand {
            font-weight: 700;
            color: var(--primary);
            font-size: 16px;
        }

        .container {
            width: 100%;
            max-width: 520px;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 32px;
            flex: 1;
        }

        .page-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 8px 32px rgba(240, 208, 96, 0.25);
        }

        .page-title {
            text-align: center;
        }

        .page-title h1 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .page-title p {
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.5;
        }

        .network-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
        }

        .network-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .card {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
        }

        .qr-wrapper {
            width: 220px;
            height: 220px;
            background: #ffffff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            position: relative;
        }

        .qr-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .qr-wrapper .placeholder-qr {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 13px;
            text-align: center;
        }

        .qr-wrapper .placeholder-qr .icon {
            font-size: 40px;
            opacity: 0.4;
        }

        .address-section {
            width: 100%;
            text-align: center;
        }

        .address-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .address-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }

        .address-text {
            flex: 1;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 13px;
            word-break: break-all;
            line-height: 1.6;
            color: var(--text);
            user-select: all;
            text-align: left;
        }

        .copy-btn {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            background: var(--primary);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.2s;
            color: #000;
        }

        .copy-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .copy-btn.copied {
            background: var(--success);
        }

        .copy-btn svg {
            width: 20px;
            height: 20px;
        }

        .copy-btn .check-icon {
            display: none;
        }

        .copy-btn.copied .copy-icon {
            display: none;
        }

        .copy-btn.copied .check-icon {
            display: block;
        }

        .warning-box {
            width: 100%;
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 13px;
            line-height: 1.6;
            color: var(--text-muted);
        }

        .warning-box .warn-icon {
            flex-shrink: 0;
            font-size: 18px;
            margin-top: 1px;
        }

        .warning-box strong {
            color: var(--warning);
        }

        .instructions {
            width: 100%;
        }

        .instructions h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--text);
        }

        .instructions ol {
            list-style: none;
            counter-reset: step;
        }

        .instructions li {
            counter-increment: step;
            position: relative;
            padding-left: 36px;
            padding-bottom: 16px;
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-muted);
        }

        .instructions li:last-child {
            padding-bottom: 0;
        }

        .instructions li::before {
            content: counter(step);
            position: absolute;
            left: 0;
            top: 0;
            width: 24px;
            height: 24px;
            background: var(--surface-hover);
            border: 1px solid var(--border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
        }

        .footer-note {
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
            padding: 24px;
            border-top: 1px solid var(--border);
            width: 100%;
        }

        @media (max-width: 480px) {
            .container { padding: 24px 16px; }
            .card { padding: 24px 16px; }
            .page-title h1 { font-size: 22px; }
            .qr-wrapper { width: 180px; height: 180px; }
            .address-box { flex-direction: column; }
            .copy-btn { width: 100%; height: 44px; border-radius: 10px; }
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <a href="/" class="brand">Tocco Voice Live</a>
        <a href="/">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            Back to Home
        </a>
    </div>

    <div class="container">
        <div class="page-icon">💎</div>

        <div class="page-title">
            <h1>Official Payment</h1>
            <p>Send payment through the TRON (TRC20) network to the official Tocco Voice Live wallet.</p>
        </div>

        <div class="network-badge">
            <span class="dot"></span>
            TRON TRC20 Network
        </div>

        <div class="card">
            <div class="qr-wrapper" id="qrWrapper">
                <div class="placeholder-qr">
                    <div class="icon">📷</div>
                    <span>QR Code</span>
                </div>
            </div>

            <div class="address-section">
                <div class="address-label">Official Wallet Address</div>
                <div class="address-box">
                    <span class="address-text" id="walletAddress">{{ $walletAddress ?? 'YOUR_TRC20_WALLET_ADDRESS_HERE' }}</span>
                    <button class="copy-btn" id="copyBtn" onclick="copyAddress()" title="Copy address" aria-label="Copy wallet address to clipboard">
                        <svg class="copy-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </button>
                </div>
            </div>

            <div class="warning-box">
                <span class="warn-icon">⚠️</span>
                <div>
                    <strong>Security Warning:</strong> Send payment <strong>only</strong> through the <strong>TRON (TRC20) network</strong>. Verify the wallet address and network before confirming your transaction. Payments sent on the wrong network may be permanently lost and cannot be recovered.
                </div>
            </div>

            <div class="instructions">
                <h3>Payment Instructions</h3>
                <ol>
                    <li>Open your TRON-compatible wallet (Trust Wallet, TronLink, etc.)</li>
                    <li>Select <strong>TRC20</strong> as the transfer network</li>
                    <li>Copy and paste the official wallet address above</li>
                    <li>Verify the address matches exactly before sending</li>
                    <li>Enter the payment amount and confirm the transaction</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="footer-note">
        © {{ date('Y') }} Tocco Voice Live. All rights reserved.
    </div>

    <script>
        function copyAddress() {
            const address = document.getElementById('walletAddress').textContent.trim();
            const btn = document.getElementById('copyBtn');

            if (address === 'YOUR_TRC20_WALLET_ADDRESS_HERE') return;

            navigator.clipboard.writeText(address).then(function() {
                btn.classList.add('copied');
                setTimeout(function() {
                    btn.classList.remove('copied');
                }, 2000);
            }).catch(function() {
                // Fallback for older browsers
                var textarea = document.createElement('textarea');
                textarea.value = address;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                btn.classList.add('copied');
                setTimeout(function() {
                    btn.classList.remove('copied');
                }, 2000);
            });
        }
    </script>
</body>
</html>
