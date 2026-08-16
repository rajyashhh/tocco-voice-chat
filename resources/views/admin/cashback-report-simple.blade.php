<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تقرير الكاش باك المفقود</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        h1 { color: #333; margin-bottom: 10px; font-size: 28px; }
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
        .summary-card.danger { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .summary-card.success { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .summary-card.warning { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .summary-card h3 { font-size: 14px; opacity: 0.9; margin-bottom: 10px; }
        .summary-card .value { font-size: 32px; font-weight: bold; }
        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
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
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
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
        thead { background: #f8f9fa; }
        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            font-weight: 600;
            color: #495057;
            font-size: 13px;
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 10;
        }
        tr:hover { background: #f8f9fa; }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-danger { background: #fee; color: #c00; }
        .badge-warning { background: #ffc; color: #c80; }
        .badge-success { background: #efe; color: #0a0; }
        .meta { color: #6c757d; font-size: 14px; margin-bottom: 20px; }
        .pagination {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            align-items: center;
        }
        .page-info { color: #6c757d; margin: 0 10px; }
        #scroll-top {
            position: fixed;
            bottom: 30px;
            left: 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 تقرير الكاش باك المفقود</h1>
        <p class="meta">آخر 30 يوم - يتم التحميل بشكل تدريجي</p>

        <div class="controls">
            <button class="btn-success" onclick="exportCSV()">📥 تصدير CSV كامل</button>
            <button class="btn-primary" onclick="compensateAll()">💰 تعويض الكل (جماعي)</button>
            <button class="btn-secondary" onclick="clearCache()">🗑️ مسح الكاش</button>
            <span class="page-info">صفحة <span id="current-page">1</span></span>
        </div>

        <div id="summary" class="summary"></div>

        <div id="loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p>جاري تحميل البيانات...</p>
        </div>

        <div id="table-container"></div>

        <div class="pagination">
            <button class="btn-primary" id="prev-btn" onclick="prevPage()" disabled>السابق</button>
            <span class="page-info">صفحة <span id="page-num">1</span></span>
            <button class="btn-primary" id="next-btn" onclick="nextPage()">التالي</button>
            <button class="btn-primary" onclick="loadAll()">تحميل الكل</button>
        </div>
    </div>

    <button id="scroll-top" onclick="scrollToTop()">↑</button>

    <script>
        let currentPage = 1;
        let allUsers = [];
        let summary = null;

        // تحميل عند فتح الصفحة
        window.onload = function() {
            loadPage(1);
            window.addEventListener('scroll', handleScroll);
        };

        async function loadPage(page) {
            showLoading();
            currentPage = page;
            document.getElementById('current-page').textContent = page;
            document.getElementById('page-num').textContent = page;

            try {
                const response = await fetch(`/api/admin/cashback-report-simple?page=${page}`);
                const data = await response.json();

                if (data.status === 'success') {
                    summary = data.summary;
                    renderSummary(summary);
                    renderTable(data.data);

                    // تحديث أزرار pagination
                    document.getElementById('prev-btn').disabled = page === 1;
                    document.getElementById('next-btn').disabled = !data.pagination.has_more;
                }

                hideLoading();
            } catch (error) {
                console.error('Error:', error);
                hideLoading();
            }
        }

        async function loadAll() {
            if (!confirm('هل تريد تحميل كل البيانات؟ قد يستغرق وقتاً طويلاً.')) {
                return;
            }

            showLoading();
            allUsers = [];
            let page = 1;
            let hasMore = true;

            while (hasMore) {
                try {
                    const response = await fetch(`/api/admin/cashback-report-simple?page=${page}`);
                    const data = await response.json();

                    if (data.status === 'success') {
                        allUsers = allUsers.concat(data.data);
                        hasMore = data.pagination.has_more;
                        page++;

                        // تحديث العداد
                        document.querySelector('.loading p').textContent =
                            `تم تحميل ${allUsers.length} مستخدم...`;
                    } else {
                        break;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    break;
                }
            }

            renderTable(allUsers);
            hideLoading();
            document.querySelector('.pagination').style.display = 'none';
        }

        function renderSummary(summary) {
            const html = `
                <div class="summary-card danger">
                    <h3>👥 المستخدمين المتأثرين</h3>
                    <div class="value">${summary.total_affected_users.toLocaleString('ar-EG')}</div>
                </div>
                <div class="summary-card warning">
                    <h3>💰 إجمالي الكاش باك الضايع</h3>
                    <div class="value">${summary.total_missing_cashback.toLocaleString('ar-EG')}</div>
                </div>
                <div class="summary-card success">
                    <h3>📊 إجمالي الكاش باك المسجل</h3>
                    <div class="value">${summary.total_cashback_logged.toLocaleString('ar-EG')}</div>
                </div>
                <div class="summary-card">
                    <h3>📈 متوسط نسبة الفقد</h3>
                    <div class="value">${summary.average_loss_percentage}%</div>
                </div>
            `;
            document.getElementById('summary').innerHTML = html;
        }

        function renderTable(users) {
            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>عدد العمليات</th>
                            <th>المرات الضايعة</th>
                            <th>الكاش باك المسجل</th>
                            <th style="background: #fff3cd;">حقه (الضايع)</th>
                            <th>نسبة الفقد</th>
                            <th>الفترة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            users.forEach(user => {
                const lossClass = user.loss_percentage > 50 ? 'badge-danger' :
                                  user.loss_percentage > 25 ? 'badge-warning' : 'badge-success';

                html += `
                    <tr id="row-${user.user_id}">
                        <td><strong>${user.user_id}</strong></td>
                        <td>${user.total_cashback_operations.toLocaleString('ar-EG')}</td>
                        <td>${user.times_missing.toLocaleString('ar-EG')}</td>
                        <td>${user.total_cashback_logged.toLocaleString('ar-EG')}</td>
                        <td style="background: #fff3cd;"><strong style="color: #d63384;">${user.his_right.toLocaleString('ar-EG')}</strong></td>
                        <td><span class="badge ${lossClass}">${user.loss_percentage}%</span></td>
                        <td style="font-size: 11px;">${formatDateRange(user.first_cashback_at, user.last_cashback_at)}</td>
                        <td>
                            <button class="btn-success" style="font-size: 12px; padding: 8px 16px;"
                                    onclick="compensateUser(${user.user_id}, ${user.his_right})">
                                💰 تعويض
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            document.getElementById('table-container').innerHTML = html;
        }

        function formatDateRange(start, end) {
            const s = new Date(start).toLocaleDateString('ar-EG', {month: 'short', day: 'numeric'});
            const e = new Date(end).toLocaleDateString('ar-EG', {month: 'short', day: 'numeric'});
            return `${s} - ${e}`;
        }

        function nextPage() {
            loadPage(currentPage + 1);
            window.scrollTo(0, 0);
        }

        function prevPage() {
            if (currentPage > 1) {
                loadPage(currentPage - 1);
                window.scrollTo(0, 0);
            }
        }

        function exportCSV() {
            window.location.href = '/api/admin/cashback-report-simple/export';
        }

        async function clearCache() {
            try {
                await fetch('/api/admin/cashback-report-simple/clear-cache', {method: 'POST'});
                alert('تم مسح الكاش بنجاح');
                location.reload();
            } catch (error) {
                alert('فشل: ' + error.message);
            }
        }

        async function compensateUser(userId, amount) {
            // تأكيد من المستخدم
            const confirmed = confirm(
                `هل تريد تعويض المستخدم ${userId}؟\n` +
                `المبلغ: ${amount.toLocaleString('ar-EG')} دايموند\n\n` +
                `⚠️ هذا الإجراء لا يمكن التراجع عنه!`
            );

            if (!confirmed) return;

            // عرض loading
            const row = document.getElementById(`row-${userId}`);
            const btn = row.querySelector('button');
            const originalBtnText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '⏳ جاري التعويض...';

            try {
                const response = await fetch('/api/admin/cashback-compensation/user', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        amount: amount,
                        reason: 'تعويض كاش باك مفقود - من صفحة التقرير'
                    })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    // نجح التعويض
                    alert(
                        `✅ تم التعويض بنجاح!\n\n` +
                        `المستخدم: ${userId}\n` +
                        `المبلغ: ${amount.toLocaleString('ar-EG')}\n` +
                        `الرصيد قبل: ${data.data.balance_before.toLocaleString('ar-EG')}\n` +
                        `الرصيد بعد: ${data.data.balance_after.toLocaleString('ar-EG')}`
                    );

                    // إخفاء السطر من الجدول
                    row.style.transition = 'opacity 0.5s';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();

                        // إذا الجدول فاضي، إعادة تحميل الصفحة
                        const remainingRows = document.querySelectorAll('tbody tr').length;
                        if (remainingRows === 0) {
                            alert('تم تعويض كل المستخدمين في هذه الصفحة ✅');
                            loadPage(currentPage);
                        }
                    }, 500);

                    // تحديث الـ Summary
                    if (summary) {
                        summary.total_affected_users--;
                        summary.total_missing_cashback -= amount;
                        renderSummary(summary);
                    }

                } else {
                    // فشل التعويض
                    alert('❌ فشل التعويض: ' + (data.message || 'خطأ غير معروف'));
                    btn.disabled = false;
                    btn.innerHTML = originalBtnText;
                }

            } catch (error) {
                alert('❌ خطأ في الاتصال: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
            }
        }

        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        function handleScroll() {
            const btn = document.getElementById('scroll-top');
            if (window.pageYOffset > 300) {
                btn.style.display = 'block';
            } else {
                btn.style.display = 'none';
            }
        }

        function scrollToTop() {
            window.scrollTo({top: 0, behavior: 'smooth'});
        }

        async function compensateAll() {
            // التأكيد من المستخدم
            const confirmed = confirm(
                `⚠️ تحذير: تعويض جماعي لكل المستخدمين!\n\n` +
                `إجمالي المستخدمين: ${summary?.total_affected_users || '؟'}\n` +
                `إجمالي المبلغ: ${summary?.total_missing_cashback?.toLocaleString('ar-EG') || '؟'} ماسة\n\n` +
                `هل أنت متأكد من تعويض الكل؟\n` +
                `⚠️ هذا الإجراء لا يمكن التراجع عنه!`
            );

            if (!confirmed) return;

            // تأكيد نهائي
            const finalConfirm = confirm(
                `⚠️ تأكيد نهائي!\n\n` +
                `سيتم تعويض ${summary?.total_affected_users || '؟'} مستخدم\n` +
                `بمبلغ إجمالي ${summary?.total_missing_cashback?.toLocaleString('ar-EG') || '؟'} ماسة\n\n` +
                `هل أنت متأكد 100%؟`
            );

            if (!finalConfirm) return;

            // عرض loading
            showLoading();
            document.querySelector('.loading p').textContent = 'جاري تعويض كل المستخدمين... قد يستغرق بضع دقائق';

            try {
                const response = await fetch('/cashback-report-compensate-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        confirm: true,
                        reason: 'تعويض جماعي - من صفحة التقرير',
                        min_amount: 0
                    })
                });

                const data = await response.json();
                hideLoading();

                if (data.status === 'success') {
                    // نجح التعويض
                    alert(
                        `✅ تم التعويض الجماعي بنجاح!\n\n` +
                        `إجمالي المستخدمين: ${data.message}\n` +
                        `نجح: ${data.results?.success || 0}\n` +
                        `فشل: ${data.results?.failed || 0}\n` +
                        `إجمالي المبلغ المدفوع: ${data.results?.total_compensated?.toLocaleString('ar-EG') || 0} ماسة`
                    );

                    // إعادة تحميل الصفحة
                    setTimeout(() => {
                        location.reload();
                    }, 2000);

                } else {
                    alert('❌ فشل التعويض الجماعي: ' + (data.message || 'خطأ غير معروف'));
                }

            } catch (error) {
                hideLoading();
                alert('❌ خطأ في الاتصال: ' + error.message);
            }
        }
    </script>
</body>
</html>
