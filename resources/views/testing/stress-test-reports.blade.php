<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقارير اختبارات الضغط</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .reports-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .report-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .badge-passed {
            background-color: #28a745;
        }
        .badge-failed {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="reports-container">
        <div class="header">
            <h1><i class="bi bi-file-earmark-text-fill"></i> تقارير اختبارات الضغط</h1>
            <a href="{{ route('stress-test.index') }}" class="btn btn-primary mt-3">
                <i class="bi bi-arrow-left"></i> العودة للاختبار
            </a>
        </div>

        @if(empty($reports))
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle-fill"></i> لا توجد تقارير حتى الآن. قم بتشغيل اختبار أولاً.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>اسم الملف</th>
                            <th>المدة</th>
                            <th>حالة السلامة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $report)
                            <tr>
                                <td>{{ $report['filename'] }}</td>
                                <td>{{ $report['duration'] }}s</td>
                                <td>
                                    <span class="badge {{ $report['integrity'] === 'PASSED' ? 'badge-passed' : 'badge-failed' }}">
                                        {{ $report['integrity'] }}
                                    </span>
                                </td>
                                <td>{{ date('Y-m-d H:i:s', $report['created_at']) }}</td>
                                <td>
                                    <a href="{{ route('stress-test.view-report', $report['filename']) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> عرض
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</body>
</html>
