<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الكاش باك المفقود</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .summary-card.danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .summary-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .summary-card.warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .summary-card h3 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .summary-card .value {
            font-size: 32px;
            font-weight: bold;
        }

        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        button {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #667eea;
            font-size: 18px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        thead {
            background: #f8f9fa;
        }

        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            font-weight: 600;
            color: #495057;
            font-size: 13px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-danger {
            background: #fee;
            color: #c00;
        }

        .badge-warning {
            background: #ffc;
            color: #c80;
        }

        .badge-success {
            background: #efe;
            color: #0a0;
        }

        .meta {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee;
            color: #c00;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 تقرير الكاش باك المفقود</h1>
        <p class="meta">آخر 30 يوم</p>

        <div class="controls">
            <button class="btn-primary" onclick="loadReport()">🔄 تحديث التقرير</button>
            <button class="btn-success" onclick="exportCSV()">📥 تصدير CSV</button>
            <button class="btn-primary" onclick="refreshReport()">♻️ إعادة حساب</button>
        </div>

        <div id="summary" class="summary"></div>

        <div id="loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p>جاري تحميل التقرير...</p>
        </div>

        <div id="error" class="error" style="display: none;"></div>

        <div id="table-container"></div>
    </div>

    <script>
        let reportData = null;

        // تحميل التقرير عند فتح الصفحة
        window.onload = function() {
            loadReport();
        };

        async function loadReport() {
            showLoading();
            hideError();

            try {
                const response = await fetch('/admin/cashback-report');
                const data = await response.json();

                if (data.status === 'processing') {
                    showError('التقرير قيد الإنشاء، سيتم إعادة المحاولة بعد 10 ثواني...');
                    setTimeout(loadReport, 10000);
                    return;
                }

                reportData = data;
                renderReport(data);
                hideLoading();

            } catch (error) {
                showError('فشل تحميل التقرير: ' + error.message);
                hideLoading();
            }
        }

        async function refreshReport() {
            if (!confirm('هل تريد إعادة حساب التقرير؟ قد يستغرق هذا بضع دقائق.')) {
                return;
            }

            try {
                const response = await fetch('/admin/cashback-report/refresh', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const data = await response.json();
                alert(data.message);

                // إعادة تحميل بعد 10 ثواني
                setTimeout(loadReport, 10000);

            } catch (error) {
                alert('فشلت العملية: ' + error.message);
            }
        }

        function renderReport(data) {
            // عرض الإحصائيات
            const summaryHtml = `
                <div class="summary-card danger">
                    <h3>👥 المستخدمين المتأثرين</h3>
                    <div class="value">${data.summary.total_affected_users.toLocaleString('ar-EG')}</div>
                </div>
                <div class="summary-card warning">
                    <h3>💰 إجمالي الكاش باك الضايع</h3>
                    <div class="value">${data.summary.total_missing_cashback.toLocaleString('ar-EG')}</div>
                </div>
                <div class="summary-card success">
                    <h3>📊 إجمالي الكاش باك المسجل</h3>
                    <div class="value">${data.summary.total_cashback_logged.toLocaleString('ar-EG')}</div>
                </div>
                <div class="summary-card">
                    <h3>📈 متوسط نسبة الفقد</h3>
                    <div class="value">${data.summary.average_loss_percentage}%</div>
                </div>
            `;
            document.getElementById('summary').innerHTML = summaryHtml;

            // عرض الجدول
            let tableHtml = `
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>عدد العمليات</th>
                            <th>المرات الضايعة</th>
                            <th>الكاش باك المسجل</th>
                            <th>حقه (الضايع)</th>
                            <th>نسبة الفقد</th>
                            <th>أول كاش باك</th>
                            <th>آخر كاش باك</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            data.data.forEach(user => {
                const lossClass = user.loss_percentage > 50 ? 'badge-danger' :
                                  user.loss_percentage > 25 ? 'badge-warning' : 'badge-success';

                tableHtml += `
                    <tr>
                        <td><strong>${user.user_id}</strong></td>
                        <td>${user.total_cashback_operations.toLocaleString('ar-EG')}</td>
                        <td>${user.times_missing.toLocaleString('ar-EG')}</td>
                        <td>${user.total_cashback_logged.toLocaleString('ar-EG')}</td>
                        <td><strong>${user.his_right.toLocaleString('ar-EG')}</strong></td>
                        <td><span class="badge ${lossClass}">${user.loss_percentage}%</span></td>
                        <td>${new Date(user.first_cashback_at).toLocaleDateString('ar-EG')}</td>
                        <td>${new Date(user.last_cashback_at).toLocaleDateString('ar-EG')}</td>
                    </tr>
                `;
            });

            tableHtml += `
                    </tbody>
                </table>
            `;

            document.getElementById('table-container').innerHTML = tableHtml;
        }

        function exportCSV() {
            window.location.href = '/admin/cashback-report/export';
        }

        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        function showError(message) {
            const errorDiv = document.getElementById('error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        function hideError() {
            document.getElementById('error').style.display = 'none';
        }
    </script>
</body>
</html>
