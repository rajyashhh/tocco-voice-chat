<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطأ - تقرير الطلبات المكررة</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 600px;
            text-align: center;
        }
        
        .error-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #c92a2a;
            margin-bottom: 15px;
            font-size: 2em;
        }
        
        .error-message {
            background: #ffe5e5;
            border: 2px solid #c92a2a;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            color: #c92a2a;
            text-align: right;
            direction: rtl;
        }
        
        .error-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: right;
            direction: rtl;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #666;
            word-break: break-all;
        }
        
        .back-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .back-button:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">❌</div>
        <h1>حدث خطأ في التقرير</h1>
        
        <div class="error-message">
            <strong>رسالة الخطأ:</strong><br>
            {{ $error }}
        </div>
        
        <div class="error-details">
            <strong>التفاصيل:</strong><br>
            يرجى التحقق من اتصال قاعدة البيانات والمحاولة مرة أخرى
        </div>
        
        <a href="javascript:history.back()" class="back-button">العودة للخلف</a>
    </div>
</body>
</html>
