<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إصلاح تكرار الماسات الشهرية</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 30px;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-box.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stat-box.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .log-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .log-item {
            padding: 8px;
            margin-bottom: 5px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
        }

        .log-item.info {
            background: #e3f2fd;
            color: #1976d2;
        }

        .log-item.success {
            background: #e8f5e9;
            color: #388e3c;
        }

        .log-item.warning {
            background: #fff3e0;
            color: #f57c00;
        }

        .log-item.error {
            background: #ffebee;
            color: #c62828;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f5f5f5;
            font-weight: bold;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-danger {
            background: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔧 إصلاح تكرار الماسات الشهرية</h1>
            <p class="subtitle">هذا الإصلاح آمن ويمكن تشغيله عدة مرات بدون مشاكل (Idempotent)</p>

            <!-- Statistics -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-box">
                    <div class="stat-label">جاري التحميل...</div>
                    <div class="stat-value">⏳</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <button class="btn btn-primary" onclick="loadStats()" id="refreshBtn">
                    🔄 تحديث الإحصائيات
                </button>
                <button class="btn btn-success" onclick="runFix()" id="fixBtn">
                    ⚡ تنفيذ الإصلاح الآن
                </button>
            </div>

            <!-- Logs -->
            <div class="log-container" id="logContainer" style="display: none;">
                <strong>📋 سجل التنفيذ:</strong>
                <div id="logs"></div>
            </div>
        </div>

        <!-- Top Duplicates Table -->
        <div class="card" id="duplicatesCard" style="display: none;">
            <h2>📊 أكثر 20 مستخدم بهم تكرار</h2>
            <div class="table-container">
                <table id="duplicatesTable">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>الشهر/السنة</th>
                            <th>عدد السجلات</th>
                            <th>مجموع الماسات</th>
                            <th>تاريخ الإنشاء</th>
                            <th>آخر تحديث</th>
                        </tr>
                    </thead>
                    <tbody id="duplicatesBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const API_STATS = '{{ route("fix-monthly-diamonds.stats") }}';
        const API_FIX = '{{ route("fix-monthly-diamonds.run") }}';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

        // Load statistics on page load
        window.addEventListener('DOMContentLoaded', loadStats);

        async function loadStats() {
            const btn = document.getElementById('refreshBtn');
            btn.disabled = true;
            btn.innerHTML = '🔄 جاري التحميل... <span class="loading"></span>';

            try {
                const response = await fetch(API_STATS);
                const data = await response.json();

                displayStats(data);
                displayDuplicates(data.top_100_duplicates || []);

            } catch (error) {
                console.error('Error loading stats:', error);
                showLog('error', '❌ خطأ في تحميل الإحصائيات: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '🔄 تحديث الإحصائيات';
            }
        }

        function displayStats(data) {
            const statsGrid = document.getElementById('statsGrid');
            statsGrid.innerHTML = `
                <div class="stat-box ${data.index_exists ? 'success' : 'warning'}">
                    <div class="stat-label">حالة المفتاح الفريد</div>
                    <div class="stat-value">${data.index_exists ? '✅' : '❌'}</div>
                </div>
                <div class="stat-box warning">
                    <div class="stat-label">المستخدمين المتأثرين</div>
                    <div class="stat-value">${data.total_users_with_duplicates.toLocaleString()}</div>
                </div>
                <div class="stat-box warning">
                    <div class="stat-label">السجلات المكررة</div>
                    <div class="stat-value">${data.total_duplicate_records.toLocaleString()}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">مجموع الماسات</div>
                    <div class="stat-value">${Math.round(data.total_diamonds_in_duplicates).toLocaleString()}</div>
                </div>
            `;

            // Show/hide fix button based on duplicates
            const fixBtn = document.getElementById('fixBtn');
            if (data.index_exists && data.total_users_with_duplicates === 0) {
                fixBtn.disabled = true;
                fixBtn.innerHTML = '✅ تم الإصلاح مسبقاً';
            } else {
                fixBtn.disabled = false;
                fixBtn.innerHTML = '⚡ تنفيذ الإصلاح الآن';
            }
        }

        function displayDuplicates(duplicates) {
            if (duplicates.length === 0) {
                document.getElementById('duplicatesCard').style.display = 'none';
                return;
            }

            document.getElementById('duplicatesCard').style.display = 'block';
            const tbody = document.getElementById('duplicatesBody');
            tbody.innerHTML = duplicates.slice(0, 20).map(dup => `
                <tr>
                    <td>${dup.user_id}</td>
                    <td>${dup.month}/${dup.year}</td>
                    <td><span class="badge badge-danger">${dup.record_count}</span></td>
                    <td>${Math.round(dup.total_diamonds).toLocaleString()}</td>
                    <td>${new Date(dup.first_created).toLocaleString('ar-EG')}</td>
                    <td>${new Date(dup.last_updated).toLocaleString('ar-EG')}</td>
                </tr>
            `).join('');
        }

        async function runFix() {
            if (!confirm('هل أنت متأكد من تنفيذ الإصلاح؟\n\nسيتم:\n1. دمج السجلات المكررة\n2. إضافة مفتاح فريد\n3. منع التكرار مستقبلاً')) {
                return;
            }

            const btn = document.getElementById('fixBtn');
            const logContainer = document.getElementById('logContainer');
            const logsDiv = document.getElementById('logs');

            btn.disabled = true;
            btn.innerHTML = '⏳ جاري التنفيذ... <span class="loading"></span>';
            logContainer.style.display = 'block';
            logsDiv.innerHTML = '';

            try {
                showLog('info', '🚀 بدء عملية الإصلاح...');

                const response = await fetch(API_FIX, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    }
                });

                const data = await response.json();

                // Display logs
                if (data.steps) {
                    data.steps.forEach(step => {
                        const type = step.includes('✅') ? 'success' :
                                    step.includes('⚠️') ? 'warning' :
                                    step.includes('❌') ? 'error' : 'info';
                        showLog(type, step);
                    });
                }

                // Display stats
                if (data.stats) {
                    showLog('info', `\n📊 الإحصائيات:\n${JSON.stringify(data.stats, null, 2)}`);
                }

                // Display execution time
                if (data.execution_time) {
                    showLog('success', `⏱️ وقت التنفيذ: ${data.execution_time}`);
                }

                // Reload stats after 2 seconds
                setTimeout(loadStats, 2000);

            } catch (error) {
                console.error('Error running fix:', error);
                showLog('error', '❌ خطأ في تنفيذ الإصلاح: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '⚡ تنفيذ الإصلاح الآن';
            }
        }

        function showLog(type, message) {
            const logsDiv = document.getElementById('logs');
            const logItem = document.createElement('div');
            logItem.className = `log-item ${type}`;
            logItem.textContent = message;
            logsDiv.appendChild(logItem);
            logsDiv.scrollTop = logsDiv.scrollHeight;
        }
    </script>
</body>
</html>
