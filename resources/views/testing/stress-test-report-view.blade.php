<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الاختبار - {{ $filename }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .report-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="text-center mb-4">
            <h1><i class="bi bi-file-earmark-text"></i> تقرير الاختبار</h1>
            <p class="text-muted">{{ $filename }}</p>
            <a href="{{ route('stress-test.reports') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> العودة للتقارير
            </a>
        </div>

        <div class="stat-card">
            <h4>البيانات الكاملة</h4>
            <pre>{{ json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
</body>
</html>
