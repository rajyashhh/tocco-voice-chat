<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>اختبار إرسال هدايا الحظ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Tahoma; background:#f7f7f7; padding:30px }
.card { background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 15px rgba(0,0,0,0.1) }
pre { background:#222; color:#0f0; padding:15px; border-radius:8px; white-space:pre-wrap; max-height:400px; overflow:auto }
.success { color:green; font-weight:bold }
.fail { color:red; font-weight:bold }
.table th, .table td { text-align: center; }
</style>
</head>

<body>
<div class="container">

    <!-- FORM CARD -->
    <div class="card">
        <h2 class="mb-4">🚀 اختبار إرسال الهدايا (هدايا الحظ)</h2>
        <form action="{{ route('lucky.gift.test.run') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label>رابط API</label>
                <input type="text" name="url" class="form-control" placeholder="https://your-backend-domain.com" value="{{ old('url','https://your-backend-domain.com') }}" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>عدد الطلبات</label>
                    <input type="number" name="count" class="form-control" value="{{ old('count',100) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>عدد المتزامنين (Concurrency)</label>
                    <input type="number" name="concurrency" class="form-control" value="{{ old('concurrency',10) }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label>Authorization Token</label>
                <input type="text" name="token" class="form-control" placeholder="Bearer xxxxxxxx" required>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label>ID</label>
                    <input type="number" name="id" class="form-control" value="{{ old('id',389	) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Owner ID</label>
                    <input type="number" name="owner_id" class="form-control" value="{{ old('owner_id',303) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>toUid</label>
                    <input type="number" name="toUid" class="form-control" value="{{ old('toUid',303) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>num</label>
                    <input type="number" name="num" class="form-control" value="{{ old('num',1) }}" required>
                </div>
            </div>
            <button type="submit" class="btn btn-success w-100">ابدأ الاختبار 🚀</button>
        </form>
    </div>

@if(isset($summary))
<div class="card mt-4">
    <h3>📊 نتائج الاختبار</h3>
    <p class="success">✔️ الطلبات الناجحة: {{ $summary['success'] }}</p>
    <p class="fail">❌ الطلبات الفاشلة: {{ $summary['failed'] }}</p>

    <hr>

    <h3>💰 رصيد المرسل</h3>
    <table class="table table-bordered">
        <tr>
            <th>قبل</th>
            <th>المتوقع بعد</th>
            <th>الفعلي بعد</th>
            <th>الفرق</th>
            <th>المكتسب (Win Coins)</th>
        </tr>
        <tr>
            <td>{{ $before_sender }}</td>
            <td>{{ $expected_sender }}</td>
            <td>{{ $after_sender }}</td>
            <td>{{ $expected_sender - $after_sender }}</td>
            <td>{{ $total_wins ?? 0 }}</td>
        </tr>
    </table>

    <h3>💰 رصيد المستلم</h3>
    <table class="table table-bordered">
        <tr>
            <th>قبل</th>
            <th>المتوقع بعد</th>
            <th>الفعلي بعد</th>
            <th>الفرق</th>
        </tr>
        <tr>
            <td>{{ $before_receiver }}</td>
            <td>{{ $expected_receiver }}</td>
            <td>{{ $after_receiver }}</td>
            <td>{{ $expected_receiver - $after_receiver }}</td>
        </tr>
    </table>

    <hr>

    <h4>🎯 نصيب الأطراف من المكاسب</h4>
    <table class="table table-bordered">
        <tr>
            <th>التطبيق</th>
            <th>صاحب الغرفة</th>
            <th>المضيف</th>
        </tr>
        <tr>
            <td>{{ $total_app_share ?? 0 }}</td>
            <td>{{ $total_roomr_share ?? 0 }}</td>
            <td>{{ $total_host_share ?? 0 }}</td>
        </tr>
    </table>

    <hr>

    <h4>🎁 معلومات الهدية</h4>
    <p>قيمة الخصم من المرسل: <b>{{ $gift_value }}</b></p>
    <p>قيمة الربح للمستلم: <b>{{ $gift_receive }}</b></p>
    <p>عدد الهدايا المرسلة فعلياً: <b>{{ $sent_gifts }}</b></p>

    <hr>

    <h4>📋 تفاصيل كل طلب</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>رقم الطلب</th>
                <th>الحالة</th>
                <th>عدد الهدايا</th>
                <th>التفاصيل</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['errors'] as $error)
            <tr>
                <td>{{ $error['index'] }}</td>
                <td class="{{ $error['status'] == 'SUCCESS' ? 'success' : 'fail' }}">{{ $error['status'] }}</td>
                <td>{{ $error['status'] == 'SUCCESS' ? $num_per_request ?? 0 : 0 }}</td>
                <td>
                    <pre>{{ json_encode($error['response'] ?? $error['error'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
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
