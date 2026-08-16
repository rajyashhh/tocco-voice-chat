<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الطلبات المكررة - أرشيف لعبة الكوينات</title>
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
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .summary-section {
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .summary-card h3 {
            color: #667eea;
            font-size: 0.9em;
            text-transform: uppercase;
            margin-bottom: 10px;
            opacity: 0.8;
        }
        
        .summary-card .value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
        
        .records-section {
            padding: 30px;
        }
        
        .records-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        th {
            padding: 15px;
            text-align: right;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-deduct {
            background: #ffe5e5;
            color: #c92a2a;
        }
        
        .badge-add {
            background: #e5ffe5;
            color: #2f7c31;
        }
        
        .badge-none {
            background: #f0f0f0;
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9em;
            font-weight: 600;
        }
        
        .status-both {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-deduct {
            background: #ffe5e5;
            color: #c92a2a;
        }
        
        .status-add {
            background: #e5ffe5;
            color: #2f7c31;
        }
        
        .number {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
        
        .positive {
            color: #2f7c31;
        }
        
        .negative {
            color: #c92a2a;
        }
        
        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #666;
            font-size: 0.9em;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .no-data p {
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .container {
                box-shadow: none;
            }
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.9em;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 تقرير الطلبات المكررة</h1>
            <p>أرشيف لعبة الكوينات - تحليل السجلات المكررة</p>
        </div>
        
        <div class="summary-section">
            <h2>📈 ملخص الإحصائيات</h2>
            <div class="summary-grid">
                <div class="summary-card">
                    <h3>إجمالي الطلبات المكررة</h3>
                    <div class="value">{{ $summary['total_duplicate_orders'] }}</div>
                </div>
                <div class="summary-card">
                    <h3>إجمالي السجلات المتأثرة</h3>
                    <div class="value">{{ $summary['total_records_affected'] }}</div>
                </div>
                <div class="summary-card">
                    <h3>إجمالي الكوينات المخصومة</h3>
                    <div class="value negative">{{ number_format($summary['total_coins_deducted']) }}</div>
                </div>
                <div class="summary-card">
                    <h3>إجمالي الكوينات المضافة</h3>
                    <div class="value positive">{{ number_format($summary['total_coins_added']) }}</div>
                </div>
                <div class="summary-card">
                    <h3>صافي التغيير</h3>
                    <div class="value {{ $summary['total_net_change'] >= 0 ? 'positive' : 'negative' }}">
                        {{ $summary['total_net_change'] >= 0 ? '+' : '' }}{{ number_format($summary['total_net_change']) }}
                    </div>
                </div>
                <div class="summary-card">
                    <h3>عدد المستخدمين المتأثرين</h3>
                    <div class="value">{{ $summary['affected_users_count'] }}</div>
                </div>
            </div>
        </div>
        
        <div class="records-section">
            <h2>📋 تفاصيل الطلبات المكررة</h2>
            
            @if($records->count() > 0)
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>معرف الطلب</th>
                                <th>المستخدم</th>
                                <th>عدد السجلات</th>
                                <th>الكوينات المخصومة</th>
                                <th>الكوينات المضافة</th>
                                <th>صافي التغيير</th>
                                <th>الرصيد الحالي</th>
                                <th>الرصيد المتوقع</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $record)
                                <tr>
                                    <td class="number">{{ $record['order_id'] }}</td>
                                    <td>
                                        <strong>{{ $record['user_name'] }}</strong><br>
                                        <small style="color: #999;">#{{ $record['user_id'] }}</small>
                                    </td>
                                    <td class="number">{{ $record['record_count'] }}</td>
                                    <td class="number negative">
                                        @if($record['total_deduct'] > 0)
                                            -{{ number_format($record['total_deduct']) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="number positive">
                                        @if($record['total_add'] > 0)
                                            +{{ number_format($record['total_add']) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="number {{ $record['net_change'] >= 0 ? 'positive' : 'negative' }}">
                                        {{ $record['net_change'] >= 0 ? '+' : '' }}{{ number_format($record['net_change']) }}
                                    </td>
                                    <td class="number">{{ number_format($record['current_di']) }}</td>
                                    <td class="number">{{ number_format($record['expected_di_after_changes']) }}</td>
                                    <td>
                                        @if($record['total_deduct'] > 0 && $record['total_add'] > 0)
                                            <span class="status-badge status-both">نقصان + زيادة</span>
                                        @elseif($record['total_deduct'] > 0)
                                            <span class="status-badge status-deduct">نقصان</span>
                                        @else
                                            <span class="status-badge status-add">زيادة</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($record['first_created_at'])->format('Y-m-d H:i') }}</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="no-data">
                    <p>✅ لا توجد طلبات مكررة</p>
                    <p>جميع الطلبات فريدة ولا توجد تكرارات</p>
                </div>
            @endif
        </div>
        
        <div class="footer">
            <p>تم إنشاء التقرير في: <strong>{{ $timestamp->format('Y-m-d H:i:s') }}</strong></p>
            <p>هذا التقرير يعرض جميع الطلبات التي لها أكثر من سجل واحد في جدول أرشيف لعبة الكوينات</p>
        </div>
    </div>
</body>
</html>
