<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>اختبار ضغط Lucky Gift - Race Condition Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .test-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .header h1 {
            color: #667eea;
            font-weight: bold;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .btn-test {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 40px;
            font-size: 18px;
            font-weight: bold;
        }
        .btn-test:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .results-container {
            display: none;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card h5 {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .badge-success {
            background-color: #28a745;
        }
        .badge-danger {
            background-color: #dc3545;
        }
        .badge-warning {
            background-color: #ffc107;
        }
        .progress-container {
            display: none;
            margin-top: 20px;
        }
        .error-item {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .balance-table {
            font-size: 14px;
        }
        .balance-table td {
            padding: 8px;
        }
        .diff-positive {
            color: #28a745;
            font-weight: bold;
        }
        .diff-negative {
            color: #dc3545;
            font-weight: bold;
        }
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="header">
            <h1><i class="bi bi-lightning-charge-fill"></i> اختبار ضغط Lucky Gift</h1>
            <p class="text-muted">اختبار Race Conditions والتحقق من سلامة الأرصدة تحت الضغط</p>
        </div>

        <form id="testForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">معرفات المرسلين (Sender IDs) <i class="bi bi-person-fill text-primary"></i></label>
                        <input type="text" class="form-control" id="sender_ids" name="sender_ids"
                               placeholder="مثال: 1,2,3,4,5" required>
                        <small class="text-muted">افصل بينها بفاصلة</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">معرفات المستلمين (Receiver IDs) <i class="bi bi-people-fill text-success"></i></label>
                        <input type="text" class="form-control" id="receiver_ids" name="receiver_ids"
                               placeholder="مثال: 10,11,12" required>
                        <small class="text-muted">افصل بينها بفاصلة</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">الهدية <i class="bi bi-gift-fill text-danger"></i></label>
                        <select class="form-select" id="gift_id" name="gift_id" required>
                            <option value="">اختر الهدية...</option>
                            @foreach($gifts as $gift)
                                <option value="{{ $gift->id }}" data-price="{{ $gift->price }}">
                                    {{ $gift->name ?? $gift->e_name }} ({{ $gift->price }} كوين)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">الغرفة <i class="bi bi-house-door-fill text-info"></i></label>
                        <select class="form-select" id="room_id" name="room_id" required>
                            <option value="">اختر الغرفة...</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">
                                    {{ $room->room_name ?? 'غرفة #' . $room->id }} (Owner: {{ $room->uid }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">عدد الهدايا (num)</label>
                        <input type="number" class="form-control" id="num" name="num" value="1" min="1" max="100" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">عدد المرات (count)</label>
                        <input type="number" class="form-control" id="count" name="count" value="1" min="1" max="10" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">عدد الطلبات لكل مرسل</label>
                        <input type="number" class="form-control" id="requests_per_user" name="requests_per_user"
                               value="5" min="1" max="20" required>
                        <small class="text-muted">كل مرسل سيرسل هذا العدد من الطلبات (حد أقصى: 20)</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">نوع الاختبار</label>
                        <select class="form-select" id="concurrent" name="concurrent" required>
                            <option value="1">متزامن (Concurrent) - اختبار Race Conditions ⚡</option>
                            <option value="0">تسلسلي (Sequential) - للمقارنة</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <strong><i class="bi bi-info-circle-fill"></i> التكلفة المتوقعة:</strong>
                        <span id="expected_cost">قم بتحديد البيانات أولاً</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="alert alert-warning">
                        <strong><i class="bi bi-exclamation-triangle-fill"></i> تحذير:</strong>
                        <ul class="mb-0 mt-2">
                            <li>الاختبار المتزامن قد يستغرق وقتاً طويلاً (30-120 ثانية)</li>
                            <li>يُنصح بالبدء بعدد صغير من الطلبات (5-10) للاختبار</li>
                            <li>تأكد من تشغيل Queue Workers قبل البدء</li>
                            <li>في حالة الفشل، قلل عدد الطلبات أو استخدم الاختبار التسلسلي</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-test">
                    <i class="bi bi-play-fill"></i> بدء الاختبار
                </button>
                <a href="{{ route('stress-test.reports') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-text"></i> عرض التقارير السابقة
                </a>
            </div>
        </form>

        <div class="progress-container" id="progressContainer">
            <div class="text-center">
                <div class="loading-spinner"></div>
                <p class="mt-3"><strong>جاري تنفيذ الاختبار...</strong></p>
                <p class="text-muted">يرجى الانتظار، قد يستغرق هذا عدة ثوانٍ</p>
            </div>
        </div>

        <div class="results-container" id="resultsContainer">
            <h3 class="text-center mb-4"><i class="bi bi-clipboard-data"></i> نتائج الاختبار</h3>

            <div class="alert alert-info">
                <h6><i class="bi bi-clock-history"></i> ملاحظة مهمة عن توقيت البيانات:</h6>
                <ul class="mb-0 small">
                    <li><strong>الأرصدة (di):</strong> تتحدث فوراً ✓</li>
                    <li><strong>الماسات الشهرية:</strong> تتحدث فوراً ✓</li>
                    <li><strong>سجلات الهدايا (gift_logs):</strong> في Queue - قد تتأخر حتى 5 دقائق ⏰</li>
                    <li><strong>سجلات الكاش باك (user_coin_logs):</strong> في Queue - قد يتأخر حسب طريقة المعالجة ⏰</li>
                </ul>
                <p class="mb-0 mt-2"><small><i class="bi bi-info-circle"></i> تم الانتظار 13 ثانية بعد الاختبار. إذا وجدت فروقات، انتظر دقائق وتحقق من الجداول يدوياً.</small></p>
            </div>

            <div class="stat-card">
                <h5><i class="bi bi-speedometer2"></i> ملخص الأداء</h5>
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>إجمالي الطلبات:</strong> <span id="total_requests" class="badge bg-primary">0</span></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>نجح:</strong> <span id="successful_requests" class="badge badge-success">0</span></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>فشل:</strong> <span id="failed_requests" class="badge badge-danger">0</span></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>الوقت:</strong> <span id="duration" class="badge bg-info">0s</span></p>
                    </div>
                </div>
                <div id="performance_stats"></div>
            </div>

            <div class="stat-card">
                <h5><i class="bi bi-shield-check"></i> التحقق من السلامة (Integrity Check)</h5>
                <div id="integrity_status"></div>
            </div>

            <div class="stat-card">
                <h5><i class="bi bi-wallet2"></i> تحليل الأرصدة</h5>
                <div id="balance_analysis"></div>
            </div>

            <div class="stat-card" id="errors_card" style="display: none;">
                <h5><i class="bi bi-exclamation-triangle-fill"></i> الأخطاء</h5>
                <div id="errors_list"></div>
            </div>

            <div class="stat-card" id="discrepancies_card" style="display: none;">
                <h5><i class="bi bi-bug-fill"></i> الفروقات المكتشفة</h5>
                <div id="discrepancies_list"></div>
            </div>

            <hr style="border: 2px solid #667eea; margin: 30px 0;">

            <div class="stat-card" id="sender_cashback_card" style="display: none;">
                <h5><i class="bi bi-cash-coin"></i> تحليل الكاش باك للمرسلين (Sender Cashback)</h5>
                <p class="text-muted small">المرسل رصيده بيزيد من الكاش باك أثناء الإرسال</p>
                <div class="alert alert-info" id="sender_receiver_warning" style="display: none;">
                    <small><i class="bi bi-info-circle"></i> <strong>ملاحظة:</strong> إذا كان المستخدم مرسل ومستلم في نفس الوقت، الرصيد النهائي = (التكلفة - الكاش باك + مكسب الاستقبال)</small>
                </div>
                <div id="sender_cashback_list"></div>
            </div>

            <hr style="border: 2px solid #667eea; margin: 30px 0;">

            <div class="stat-card" id="monthly_diamonds_card" style="display: none;">
                <h5><i class="bi bi-gem"></i> تحليل الماسات الشهرية (monthly_diamond_receives)</h5>
                <div id="monthly_diamonds_list"></div>
            </div>

            <hr style="border: 2px solid #667eea; margin: 30px 0;">


            <div class="stat-card" id="gift_logs_card" style="display: none;">
                <h5><i class="bi bi-gift-fill"></i> تحليل سجلات الهدايا (gift_logs)</h5>
                <div id="gift_logs_analysis"></div>
            </div>

            <hr style="border: 2px solid #667eea; margin: 30px 0;">

            <div class="stat-card" id="coin_logs_card" style="display: none;">
                <h5><i class="bi bi-coin"></i> تحليل سجلات الكاش باك (user_coin_logs)</h5>
                <div id="coin_logs_analysis"></div>
            </div>

            <div class="text-center mt-4">
                <button class="btn btn-secondary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> اختبار جديد
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // حساب التكلفة المتوقعة
        function calculateExpectedCost() {
            const giftSelect = document.getElementById('gift_id');
            const selectedOption = giftSelect.options[giftSelect.selectedIndex];
            const giftPrice = parseFloat(selectedOption.getAttribute('data-price')) || 0;

            const num = parseInt(document.getElementById('num').value) || 0;
            const count = parseInt(document.getElementById('count').value) || 0;
            const requestsPerUser = parseInt(document.getElementById('requests_per_user').value) || 0;

            const senderIds = document.getElementById('sender_ids').value.split(',').filter(id => id.trim());
            const receiverIds = document.getElementById('receiver_ids').value.split(',').filter(id => id.trim());

            const costPerRequest = giftPrice * num * receiverIds.length * count;
            const totalCostPerSender = costPerRequest * requestsPerUser;
            const totalCost = totalCostPerSender * senderIds.length;

            document.getElementById('expected_cost').innerHTML = `
                <strong>لكل طلب:</strong> ${costPerRequest.toLocaleString()} كوين |
                <strong>لكل مرسل:</strong> ${totalCostPerSender.toLocaleString()} كوين |
                <strong>الإجمالي:</strong> ${totalCost.toLocaleString()} كوين
            `;
        }

        // تحديث التكلفة عند تغيير أي حقل
        ['gift_id', 'num', 'count', 'requests_per_user', 'sender_ids', 'receiver_ids'].forEach(id => {
            document.getElementById(id).addEventListener('change', calculateExpectedCost);
            document.getElementById(id).addEventListener('input', calculateExpectedCost);
        });

        // إرسال النموذج
        document.getElementById('testForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            // التحقق من إجمالي الطلبات
            const senderIds = data.sender_ids.split(',').filter(id => id.trim());
            const totalRequests = senderIds.length * parseInt(data.requests_per_user);

            if (totalRequests > 100) {
                if (!confirm(`⚠️ تحذير: سيتم إرسال ${totalRequests} طلب! هذا قد يستغرق وقتاً طويلاً ويسبب ضغطاً على السيرفر.\n\nهل تريد المتابعة؟`)) {
                    return;
                }
            }

            // إخفاء النتائج وإظهار التحميل
            document.getElementById('resultsContainer').style.display = 'none';
            document.getElementById('progressContainer').style.display = 'block';
            document.getElementById('progressContainer').querySelector('p').innerHTML = '<strong>جاري تنفيذ الاختبار...</strong><br>يرجى الانتظار، هذا قد يستغرق عدة دقائق حسب عدد الطلبات.';

            // إنشاء AbortController للتحكم في timeout
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 600000); // 10 دقائق

            try {
                const response = await fetch('{{ route("stress-test.run") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data),
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();

                if (result.success) {
                    // النتائج جاهزة مباشرة
                    if (result.results) {
                        document.getElementById('progressContainer').style.display = 'none';
                        displayResults(result);
                    }
                    // إذا كان الاختبار يعمل في الخلفية (لن يحدث الآن)
                    else if (result.test_id && result.status_url) {
                        pollTestStatus(result.test_id, result.status_url);
                    }
                } else {
                    alert('❌ خطأ: ' + (result.message || 'حدث خطأ غير متوقع'));
                    document.getElementById('progressContainer').style.display = 'none';
                }
            } catch (error) {
                clearTimeout(timeoutId);

                let errorMessage = 'خطأ في الاتصال';

                if (error.name === 'AbortError') {
                    errorMessage = '⏱️ انتهى الوقت المسموح (10 دقائق). حاول تقليل عدد الطلبات أو استخدم الاختبار التسلسلي.';
                } else if (error.message.includes('ERR_CONNECTION')) {
                    errorMessage = '🔌 فشل الاتصال بالسيرفر. قد يكون السيرفر تحت ضغط كبير.\n\nحلول مقترحة:\n1. قلل عدد الطلبات (جرب 5-10)\n2. استخدم الاختبار التسلسلي بدلاً من المتزامن\n3. تأكد من تشغيل السيرفر بشكل صحيح';
                } else {
                    errorMessage = error.message;
                }

                alert('❌ ' + errorMessage);
                document.getElementById('progressContainer').style.display = 'none';
            }
        });

        // متابعة حالة الاختبار في الخلفية
        async function pollTestStatus(testId, statusUrl) {
            const progressDiv = document.getElementById('progressContainer');
            const progressText = progressDiv.querySelector('p');

            let attempts = 0;
            const maxAttempts = 120; // 2 دقيقة (كل ثانية)

            const checkStatus = async () => {
                try {
                    const response = await fetch(statusUrl);
                    const data = await response.json();

                    if (!data.success) {
                        throw new Error('فشل في الحصول على حالة الاختبار');
                    }

                    const progress = data.progress;

                    // تحديث رسالة التقدم
                    if (progress) {
                        progressText.innerHTML = `<strong>${progress.message}</strong><br>التقدم: ${progress.percentage}%`;
                    }

                    // إذا اكتمل الاختبار
                    if (progress && progress.status === 'completed' && data.results) {
                        clearInterval(pollInterval);
                        displayResults(data);
                        return;
                    }

                    // إذا فشل الاختبار
                    if (progress && progress.status === 'failed') {
                        clearInterval(pollInterval);
                        alert('❌ فشل الاختبار: ' + progress.message);
                        progressDiv.style.display = 'none';
                        return;
                    }

                    attempts++;
                    if (attempts >= maxAttempts) {
                        clearInterval(pollInterval);
                        alert('⏱️ انتهى وقت الانتظار. قد يكون الاختبار لا يزال يعمل في الخلفية.');
                        progressDiv.style.display = 'none';
                    }

                } catch (error) {
                    console.error('Error polling status:', error);
                    attempts++;
                    if (attempts >= maxAttempts) {
                        clearInterval(pollInterval);
                        alert('❌ خطأ في متابعة حالة الاختبار: ' + error.message);
                        progressDiv.style.display = 'none';
                    }
                }
            };

            // بدء المتابعة كل ثانية
            const pollInterval = setInterval(checkStatus, 1000);
            checkStatus(); // تنفيذ أول مرة مباشرة
        }

        function displayResults(result) {
            // ملخص الأداء
            document.getElementById('total_requests').textContent = result.results.total_requests || 0;
            document.getElementById('successful_requests').textContent = result.results.successful || 0;
            document.getElementById('failed_requests').textContent = result.results.failed || 0;
            document.getElementById('duration').textContent = (result.duration || 0) + 's';

            // إحصائيات الأداء
            if (result.analysis.performance) {
                document.getElementById('performance_stats').innerHTML = `
                    <div class="mt-3">
                        <p><strong>متوسط زمن الاستجابة:</strong> ${result.analysis.performance.avg_response_time}s</p>
                        <p><strong>أسرع طلب:</strong> ${result.analysis.performance.min_response_time}s</p>
                        <p><strong>أبطأ طلب:</strong> ${result.analysis.performance.max_response_time}s</p>
                    </div>
                `;
            }

            // حالة السلامة
            const integrityStatus = result.analysis?.integrity_check || 'UNKNOWN';
            const integrityColor = integrityStatus === 'PASSED' ? 'success' : 'danger';
            const integrityIcon = integrityStatus === 'PASSED' ? 'check-circle-fill' : 'x-circle-fill';

            document.getElementById('integrity_status').innerHTML = `
                <div class="alert alert-${integrityColor}">
                    <h4><i class="bi bi-${integrityIcon}"></i> ${integrityStatus}</h4>
                    ${integrityStatus === 'PASSED' ?
                        '<p>جميع الأرصدة متطابقة ولا توجد مشاكل ✓</p>' :
                        '<p class="mb-0">تم اكتشاف مشاكل في الأرصدة! راجع التفاصيل أدناه.</p>'
                    }
                </div>
            `;

            // تحليل الأرصدة
            displayBalanceAnalysis(result);

            // الأخطاء
            if (result.results.errors && result.results.errors.length > 0) {
                document.getElementById('errors_card').style.display = 'block';
                let errorsHtml = '';
                result.results.errors.forEach((error, index) => {
                    errorsHtml += `
                        <div class="error-item">
                            <strong>خطأ #${index + 1}:</strong> ${JSON.stringify(error)}
                        </div>
                    `;
                });
                document.getElementById('errors_list').innerHTML = errorsHtml;
            }

            // الفروقات
            if (result.analysis.discrepancies && result.analysis.discrepancies.length > 0) {
                document.getElementById('discrepancies_card').style.display = 'block';
                let discrepanciesHtml = '<div class="table-responsive"><table class="table table-bordered">';
                discrepanciesHtml += '<thead><tr><th>النوع</th><th>المستخدم</th><th>قبل</th><th>بعد</th><th>المتوقع</th><th>الفعلي</th><th>الفرق</th></tr></thead><tbody>';

                result.analysis.discrepancies.forEach(disc => {
                    const before = disc.before || 0;
                    const after = disc.after || 0;
                    const expected = disc.expected_deduction || disc.expected_gain || disc.expected || 0;
                    const actual = disc.actual_deduction || disc.actual_gain || disc.actual || 0;
                    const difference = disc.difference || 0;
                    const diffClass = difference > 0 ? 'diff-positive' : 'diff-negative';
                    const userName = disc.name || disc.message || 'N/A';
                    const userId = disc.user_id || '-';

                    discrepanciesHtml += `
                        <tr>
                            <td>${disc.type || 'N/A'}</td>
                            <td>${userName} (${userId})</td>
                            <td>${before.toLocaleString()}</td>
                            <td>${after.toLocaleString()}</td>
                            <td>${expected.toLocaleString()}</td>
                            <td>${actual.toLocaleString()}</td>
                            <td class="${diffClass}">${difference.toLocaleString()}</td>
                        </tr>
                    `;
                });

                discrepanciesHtml += '</tbody></table></div>';
                document.getElementById('discrepancies_list').innerHTML = discrepanciesHtml;
            }

            // ✨ تحليل الكاش باك للمرسلين
            if (result.analysis.sender_cashback_analysis) {
                document.getElementById('sender_cashback_card').style.display = 'block';

                // تحقق إذا كان في مستخدم مرسل ومستلم
                let hasAlsoReceiver = false;
                for (let [userId, data] of Object.entries(result.analysis.sender_cashback_analysis)) {
                    if (data.is_also_receiver) {
                        hasAlsoReceiver = true;
                        break;
                    }
                }
                if (hasAlsoReceiver) {
                    document.getElementById('sender_receiver_warning').style.display = 'block';
                }

                let cashbackHtml = '<div class="table-responsive"><table class="table table-bordered">';
                cashbackHtml += '<thead><tr><th>المستخدم</th><th>الرصيد قبل</th><th>الرصيد بعد</th><th>التكلفة المتوقعة</th><th>التغيير الفعلي</th><th>الكاش باك المقدر</th><th>ملاحظة</th></tr></thead><tbody>';

                for (let [userId, data] of Object.entries(result.analysis.sender_cashback_analysis)) {
                    const balanceBefore = data.balance_before || 0;
                    const balanceAfter = data.balance_after || 0;
                    const expectedCost = data.expected_cost || 0;
                    const actualChange = data.actual_change || 0;
                    const estimatedCashback = data.estimated_cashback || 0;
                    const changeClass = actualChange >= 0 ? 'diff-positive' : 'diff-negative';
                    const cashbackClass = estimatedCashback > 0 ? 'diff-positive' : '';

                    // إذا كان مرسل ومستلم في نفس الوقت
                    const isAlsoReceiver = data.is_also_receiver || false;
                    const expectedReceiverGain = data.expected_receiver_gain || 0;
                    const rowClass = isAlsoReceiver ? 'table-warning' : '';

                    let noteHtml = `<small>${data.note || ''}</small>`;
                    if (isAlsoReceiver) {
                        noteHtml = `
                            <small class="text-primary">
                                <strong>⚠️ ${data.note}</strong><br>
                                مكسب متوقع كمستلم: +${expectedReceiverGain.toLocaleString()}<br>
                                الحساب: ${expectedCost.toLocaleString()}- (تكلفة) + ${estimatedCashback.toLocaleString()} (كاش باك) + ${expectedReceiverGain.toLocaleString()} (مكسب) = ${actualChange.toLocaleString()}
                            </small>
                        `;
                    }

                    cashbackHtml += `
                        <tr class="${rowClass}">
                            <td>${data.name || 'N/A'} (${userId})</td>
                            <td>${balanceBefore.toLocaleString()}</td>
                            <td>${balanceAfter.toLocaleString()}</td>
                            <td class="diff-negative">-${expectedCost.toLocaleString()}</td>
                            <td class="${changeClass}">${actualChange >= 0 ? '+' : ''}${actualChange.toLocaleString()}</td>
                            <td class="${cashbackClass}">${estimatedCashback > 0 ? '+' : ''}${estimatedCashback.toLocaleString()}</td>
                            <td>${noteHtml}</td>
                        </tr>
                    `;
                }

                cashbackHtml += '</tbody></table></div>';
                document.getElementById('sender_cashback_list').innerHTML = cashbackHtml;
            }

            // ✨ تحليل الماسات الشهرية
            if (result.analysis.monthly_diamonds_analysis) {
                document.getElementById('monthly_diamonds_card').style.display = 'block';
                let monthlyHtml = '<div class="table-responsive"><table class="table table-bordered">';
                monthlyHtml += '<thead><tr><th>المستخدم</th><th>قبل</th><th>بعد</th><th>الزيادة</th></tr></thead><tbody>';

                for (let [userId, data] of Object.entries(result.analysis.monthly_diamonds_analysis)) {
                    const before = data.before || 0;
                    const after = data.after || 0;
                    const increase = data.increase || 0;
                    const increaseClass = increase > 0 ? 'diff-positive' : '';
                    monthlyHtml += `
                        <tr>
                            <td>${data.name || 'N/A'} (${userId})</td>
                            <td>${before.toLocaleString()}</td>
                            <td>${after.toLocaleString()}</td>
                            <td class="${increaseClass}">${increase > 0 ? '+' : ''}${increase.toLocaleString()}</td>
                        </tr>
                    `;
                }

                monthlyHtml += '</tbody></table></div>';
                document.getElementById('monthly_diamonds_list').innerHTML = monthlyHtml;
            }

            // ✨ تحليل سجلات الهدايا
            if (result.analysis.gift_logs_analysis) {
                document.getElementById('gift_logs_card').style.display = 'block';
                const gla = result.analysis.gift_logs_analysis;
                const matchBadge = gla.match.includes('MATCHED')
                    ? '<span class="badge badge-success">✓ متطابق</span>'
                    : '<span class="badge badge-warning">✗ غير متطابق</span>';

                const warningHtml = gla.warning
                    ? `<div class="alert alert-warning mt-2"><small><i class="bi bi-exclamation-triangle"></i> ${gla.warning}</small></div>`
                    : '';

                let giftLogsHtml = `
                    <table class="table table-bordered">
                        <tr><td><strong>عدد السجلات قبل الاختبار:</strong></td><td>${gla.before_count}</td></tr>
                        <tr><td><strong>عدد السجلات بعد الاختبار:</strong></td><td>${gla.after_count}</td></tr>
                        <tr><td><strong>سجلات جديدة:</strong></td><td class="diff-positive">+${gla.new_records}</td></tr>
                        <tr><td><strong>المتوقع (الطلبات الناجحة):</strong></td><td>${gla.expected_records}</td></tr>
                        <tr><td><strong>الحالة:</strong></td><td>${matchBadge}</td></tr>
                    </table>
                    ${warningHtml}
                    <p class="text-muted small mt-2"><i class="bi bi-info-circle"></i> ملاحظة: gift_logs في Queue - قد تتأخر السجلات حتى 5 دقائق. تم الانتظار 13 ثانية بعد الاختبار.</p>
                `;
                document.getElementById('gift_logs_analysis').innerHTML = giftLogsHtml;
            }

            // ✨ تحليل سجلات الكاش باك
            if (result.analysis.coin_logs_analysis) {
                document.getElementById('coin_logs_card').style.display = 'block';
                const cla = result.analysis.coin_logs_analysis;

                let coinLogsHtml = `
                    <table class="table table-bordered">
                        <tr><td><strong>عدد السجلات قبل الاختبار:</strong></td><td>${cla.before_count}</td></tr>
                        <tr><td><strong>عدد السجلات بعد الاختبار:</strong></td><td>${cla.after_count}</td></tr>
                        <tr><td><strong>سجلات كاش باك جديدة:</strong></td><td class="diff-positive">+${cla.new_cashback_records}</td></tr>
                        <tr><td colspan="2"><small class="text-muted">${cla.note}</small></td></tr>
                    </table>
                    <div class="alert alert-warning mt-2">
                        <small><i class="bi bi-exclamation-triangle"></i> ${cla.warning || 'الكاش باك يُعالج في Queue'}</small>
                    </div>
                    <p class="text-muted small mt-2"><i class="bi bi-info-circle"></i> ملاحظة: الكاش باك قد يتأخر حسب طريقة المعالجة. تم الانتظار 13 ثانية بعد الاختبار.</p>
                `;
                document.getElementById('coin_logs_analysis').innerHTML = coinLogsHtml;
            }

            // إظهار النتائج
            document.getElementById('resultsContainer').style.display = 'block';
        }

        function displayBalanceAnalysis(result) {
            let html = '<div class="table-responsive"><table class="table balance-table table-bordered">';

            // المرسلين
            html += '<thead class="table-primary"><tr><th colspan="4">المرسلين (Senders)</th></tr></thead>';
            html += '<tbody><tr><th>المستخدم</th><th>قبل</th><th>بعد</th><th>الفرق</th></tr>';

            for (let [userId, beforeData] of Object.entries(result.before_balances.senders)) {
                const afterData = result.after_balances.senders[userId] || {};
                const beforeDi = beforeData.di || 0;
                const afterDi = afterData.di || 0;
                const diff = afterDi - beforeDi;
                const diffClass = diff >= 0 ? 'diff-positive' : 'diff-negative';

                html += `
                    <tr>
                        <td>${beforeData.name || 'N/A'} (${userId})</td>
                        <td>${beforeDi.toLocaleString()}</td>
                        <td>${afterDi.toLocaleString()}</td>
                        <td class="${diffClass}">${diff.toLocaleString()}</td>
                    </tr>
                `;
            }
            html += '</tbody>';

            // المستلمين
            html += '<thead class="table-success"><tr><th colspan="4">المستلمين (Receivers)</th></tr></thead>';
            html += '<tbody><tr><th>المستخدم</th><th>قبل</th><th>بعد</th><th>الفرق</th></tr>';

            for (let [userId, beforeData] of Object.entries(result.before_balances.receivers)) {
                const afterData = result.after_balances.receivers[userId] || {};
                const beforeDi = beforeData.di || 0;
                const afterDi = afterData.di || 0;
                const diff = afterDi - beforeDi;
                const diffClass = diff >= 0 ? 'diff-positive' : 'diff-negative';

                html += `
                    <tr>
                        <td>${beforeData.name || 'N/A'} (${userId})</td>
                        <td>${beforeDi.toLocaleString()}</td>
                        <td>${afterDi.toLocaleString()}</td>
                        <td class="${diffClass}">${diff >= 0 ? '+' : ''}${diff.toLocaleString()}</td>
                    </tr>
                `;
            }
            html += '</tbody>';

            // المحافظ المركزية
            if (result.before_balances.core_wallets) {
                html += '<thead class="table-warning"><tr><th colspan="4">المحافظ المركزية (Core Wallets)</th></tr></thead>';
                html += '<tbody>';

                for (let [walletName, beforeData] of Object.entries(result.before_balances.core_wallets)) {
                    const afterData = result.after_balances.core_wallets[walletName] || {};
                    const beforeCoins = beforeData.coins || 0;
                    const afterCoins = afterData.coins || 0;
                    const diff = afterCoins - beforeCoins;
                    const diffClass = diff >= 0 ? 'diff-positive' : 'diff-negative';

                    html += `
                        <tr>
                            <td>${walletName}</td>
                            <td>${beforeCoins.toLocaleString()}</td>
                            <td>${afterCoins.toLocaleString()}</td>
                            <td class="${diffClass}">${diff.toLocaleString()}</td>
                        </tr>
                    `;
                }
                html += '</tbody>';
            }

            html += '</table></div>';

            // الملخص
            const totalDeduction = (result.analysis.summary.total_actual_deduction || 0).toLocaleString();
            const totalReceiverGain = (result.analysis.summary.total_actual_receiver_gain || 0).toLocaleString();
            const appWalletChange = result.analysis.summary.app_wallet_change || 0;
            const ownerWalletChange = result.analysis.summary.owner_wallet_change || 0;

            html += `
                <div class="mt-3 alert alert-info">
                    <h6>الملخص المالي:</h6>
                    <p><strong>إجمالي الخصم من المرسلين:</strong> ${totalDeduction} كوين</p>
                    <p><strong>إجمالي الزيادة للمستلمين:</strong> ${totalReceiverGain} كوين</p>
                    ${appWalletChange ? `<p><strong>تغيير App Wallet:</strong> ${appWalletChange.toLocaleString()} كوين</p>` : ''}
                    ${ownerWalletChange ? `<p><strong>تغيير Owner Wallet:</strong> ${ownerWalletChange.toLocaleString()} كوين</p>` : ''}
                </div>
            `;

            document.getElementById('balance_analysis').innerHTML = html;
        }
    </script>
</body>
</html>
