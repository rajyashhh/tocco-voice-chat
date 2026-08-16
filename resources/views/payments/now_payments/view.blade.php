<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الدفع</title>
<!-- jQuery (قبل toastr) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
            direction: rtl;
        }

        .container {
            max-width: 650px;
            margin: 60px auto;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 40px 30px;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 35px;
        }

        .data-group {
            margin-bottom: 22px;
        }

        label {
            font-weight: bold;
            display: block;
            color: #444;
            margin-bottom: 5px;
        }

        .value-box {
            background: #f1f1f1;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 16px;
            word-wrap: break-word;
            position: relative;
            direction: ltr;
        }

        .copy-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #3498db;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        .copy-btn:hover {
            background-color: #2980b9;
        }

        #qrcode {
            margin: 30px auto 10px;
            display: block;
        }

        .note {
            text-align: center;
            margin-top: 15px;
            color: #666;
            font-size: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>تفاصيل عملية الدفع</h2>

    <div class="data-group">
        <label>رقم الطلب:</label>
        <div class="value-box">{{ $payment['order_id'] }}</div>
    </div>

    <div class="data-group">
        <label>الوصف:</label>
        <div class="value-box">{{ $payment['order_description'] }}</div>
    </div>

    <div class="data-group">
        <label>العملة الرقمية:</label>
        <div class="value-box">{{ strtoupper($payment['pay_currency']) }}</div>
    </div>

    <div class="data-group">
        <label>المبلغ المطلوب:</label>
        <div class="value-box">{{ number_format($payment['pay_amount'], 8) }}</div>
    </div>

    <div class="data-group">
    <label>عنوان المحفظة:</label>
    <div style="display: flex; align-items: center; gap: 10px;">
        <h3 id="wallet-address" class="value-box" style="margin: 0;">{{ $payment['pay_address'] }}</h3>
        <button onclick="copyAddress()" style="cursor: pointer;">📋</button>
    </div>
</div>

    <canvas id="qrcode" width="200" height="200"></canvas>
    <p class="note">امسح رمز QR أو انسخ العنوان لإرسال المبلغ المطلوب</p>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    const qr = new QRious({
        element: document.getElementById('qrcode'),
        value: document.getElementById('wallet-address').innerText.trim(),
        size: 200,
        level: 'H'
    });

    function copyAddress() {
    const address = document.getElementById("wallet-address").innerText.trim();
    navigator.clipboard.writeText(address).then(() => {
        toastr.success("تم نسخ عنوان المحفظة بنجاح ✅");
    }).catch(() => {
        toastr.error("فشل نسخ العنوان ❌");
    });
}
</script>

</body>
</html>
