<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout with PayPal</title>
    <script src="https://www.paypal.com/sdk/js?client-id={{ config('paypal.client_id') }}&currency={{ config('paypal.currency','USD') }}"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 16px;
            max-width: 100%;
            overflow-x: hidden;
        }

        .language-selector {
            text-align: center;
            margin-bottom: 20px;
        }

        .language-selector select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 16px;
            width: 200px;
            outline: none;
            cursor: pointer;
        }

        .container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .header h1 {
            font-size: 22px;
            color: #253b80;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 20px 0;
        }

        .payment-button {
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .paypal-button {
            background-color: #ffc439;
            color: #000;
        }

        .paypal-button:hover {
            background-color: #f2b432;
        }

        .card-button {
            background-color: #0070ba;
            color: #fff;
        }

        .card-button:hover {
            background-color: #005ea6;
        }

        .payment-button i {
            font-size: 20px;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background-color: #ddd;
        }

        .divider-text {
            padding: 0 15px;
            color: #777;
            font-size: 14px;
        }

        .secure-notice {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .secure-notice i {
            color: #28a745;
            margin-right: 5px;
        }

        .amount-display {
            text-align: center;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            font-weight: 600;
            color: #253b80;
            font-size: 18px;
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            .header h1 {
                font-size: 20px;
            }

            .payment-button {
                padding: 14px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="language-selector">
        <select id="languageSelect">
            <option value="en">English</option>
            <option value="ar" selected>العربية</option>
            <option value="fr">Français</option>
            <option value="es">Español</option>
        </select>
    </div>

    <div class="container">
        <div class="header">
            <h1 id="title">إتمام عملية الدفع</h1>
            <p id="subtitle">اختر طريقة الدفع المناسبة لك</p>
        </div>

        <div class="amount-display">
            <span id="amountLabel">المبلغ:</span> {{ $amount }} <span id="currency">دولار</span>
        </div>

        <div class="payment-options">
            <div id="paypal-button"></div>

            <div class="divider">
                <div class="divider-line"></div>
                <div class="divider-text" id="dividerText">أو</div>
                <div class="divider-line"></div>
            </div>

            <div id="card-button"></div>
        </div>

        <div class="secure-notice">
            <i>✓</i> <span id="secureText">عملية دفع آمنة ومشفرة</span>
        </div>
    </div>

    <script>
        // دالة لاستخراج اللغة من header الـ x-localization
        function getLanguageFromHeaders() {
            // في بيئة production، سيتم الحصول على هذا من الـ header الحقيقي
            // للعرض التوضيحي، سنستخدم معلمة URL كبديل
            const urlParams = new URLSearchParams(window.location.search);
            const headerLang = urlParams.get('x-localization');

            return headerLang && ['en', 'ar', 'fr', 'es'].includes(headerLang) ? headerLang : null;
        }

        // دالة لتغيير اللغة
        function changeLanguage(lang) {
            // إضافة معلمة اللغة إلى الرابط
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.history.replaceState({}, '', url);

            // تغيير اتجاه الصفحة بناءً على اللغة
            document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
            document.documentElement.lang = lang;

            // ترجمة النصوص
            const translations = {
                en: {
                    title: "Complete Payment",
                    subtitle: "Choose your preferred payment method",
                    amountLabel: "Amount:",
                    currency: "USD",
                    dividerText: "or",
                    secureText: "Secure encrypted payment process",
                    payWithPayPal: "Pay with PayPal",
                    payWithCard: "Pay with Debit or Credit Card"
                },
                ar: {
                    title: "إتمام عملية الدفع",
                    subtitle: "اختر طريقة الدفع المناسبة لك",
                    amountLabel: "المبلغ:",
                    currency: "دولار",
                    dividerText: "أو",
                    secureText: "عملية دفع آمنة ومشفرة",
                    payWithPayPal: "الدفع باستخدام PayPal",
                    payWithCard: "الدفع باستخدام بطاقة ائتمان أو خصم"
                },
                fr: {
                    title: "Finaliser le Paiement",
                    subtitle: "Choisissez votre méthode de paiement préférée",
                    amountLabel: "Montant:",
                    currency: "USD",
                    dividerText: "ou",
                    secureText: "Processus de paiement sécurisé et crypté",
                    payWithPayPal: "Payer avec PayPal",
                    payWithCard: "Payer par carte de débit ou de crédit"
                },
                es: {
                    title: "Completar Pago",
                    subtitle: "Elija su método de pago preferido",
                    amountLabel: "Monto:",
                    currency: "USD",
                    dividerText: "o",
                    secureText: "Proceso de pago seguro y encriptado",
                    payWithPayPal: "Pagar con PayPal",
                    payWithCard: "Pagar con tarjeta de débito o crédito"
                }
            };

            // تطبيق الترجمة
            document.getElementById('title').textContent = translations[lang].title;
            document.getElementById('subtitle').textContent = translations[lang].subtitle;
            document.getElementById('amountLabel').textContent = translations[lang].amountLabel;
            document.getElementById('currency').textContent = translations[lang].currency;
            document.getElementById('dividerText').textContent = translations[lang].dividerText;
            document.getElementById('secureText').textContent = translations[lang].secureText;

            // ترجمة أزرار PayPal (سيتم إعادة إنشائها)
            recreatePayPalButtons(lang, translations[lang].payWithPayPal, translations[lang].payWithCard);
        }

        // إعادة إنشاء أزرار PayPal بالنصوص المترجمة
        function recreatePayPalButtons(lang, paypalText, cardText) {
            // إزالة الأزرار الحالية
            document.getElementById('paypal-button').innerHTML = '';
            document.getElementById('card-button').innerHTML = '';

            // ✅ BUTTON 1 (Yellow PayPal button - redirect)
            paypal.Buttons({
                fundingSource: paypal.FUNDING.PAYPAL,
                style: {
                    layout: 'vertical',
                    shape: 'pill',
                    height: 45,
                    label: 'paypal'
                },
                createOrder: function(data, actions) {
                    return fetch('/paypal/create-order', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            referenceId: "{{ $logId }}",
                            amount: "{{ $amount }}"
                        })
                    })
                        .then(res => res.json())
                        .then(orderData => {
                            window.location.href = orderData.approval_url;
                            return false; // stop popup
                        });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                    });
                },
                onCancel: function(data) {
                    window.location.href = "/api/paypal-cancel/{{ $logId }}";
                }
            }).render('#paypal-button');

            // ✅ BUTTON 2 (Debit/Credit - popup continues as normal)
            paypal.Buttons({
                fundingSource: paypal.FUNDING.CARD,
                style: {
                    layout: 'vertical',
                    shape: 'pill',
                    height: 45,
                    label: 'checkout'
                },
                createOrder: function(data, actions) {
                    return fetch('/paypal/create-order', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            referenceId: "{{ $logId }}",
                            amount: "{{ $amount }}"
                        })
                    })
                        .then(res => res.json())
                        .then(orderData => {
                            return orderData.id;
                        });
                },
                onApprove: function(data, actions) {
                    return fetch("/api/paypal-return/{{ $logId }}", {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    })
                        .then(res => res.json())
                        .then(orderData => {
                            console.log("✅ Capture Success:", orderData);

                            window.location.href = "/api/paypal-return/{{ $logId }}";
                        });
                },
                onCancel: function() {
                    window.location.href = "/api/paypal-cancel/{{ $logId }}";
                }
            }).render('#card-button');
        }

        // استجابة لتغيير اختيار اللغة
        document.getElementById('languageSelect').addEventListener('change', function() {
            changeLanguage(this.value);
        });

        // تحديد اللغة الافتراضية
        function determineDefaultLanguage() {
            // 1. أولوية للغة من الـ header
            const headerLang = getLanguageFromHeaders();
            if (headerLang) {
                return headerLang;
            }

            // 2. ثم تحقق من معلمة URL
            const urlParams = new URLSearchParams(window.location.search);
            const urlLang = urlParams.get('lang');
            if (urlLang && ['en', 'ar', 'fr', 'es'].includes(urlLang)) {
                return urlLang;
            }

            // 3. ثم لغة المتصاف
            const browserLang = navigator.language.substring(0, 2);
            if (['en', 'ar', 'fr', 'es'].includes(browserLang)) {
                return browserLang;
            }

            // 4. افتراضي العربية
            return 'ar';
        }

        // تطبيق اللغة الافتراضية عند التحميل
        const defaultLang = determineDefaultLanguage();
        document.getElementById('languageSelect').value = defaultLang;
        changeLanguage(defaultLang);
    </script>
</body>
</html>
