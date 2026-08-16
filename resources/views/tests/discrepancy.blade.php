<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختلاف الماسات - {{ $month }}/{{ $year }}</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        h2 {
            margin-bottom: 1.5rem;
            text-align: center;
            color: #333;
        }
        .card {
            border-radius: 0.8rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .table thead th {
            background-color: #4a69bd;
            color: #fff;
        }
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #f1f2f6;
        }
        .text-diff {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container py-5">

    <h2>اختلاف الماسات - الشهر: <span class="text-primary">{{ $month }}</span>, السنة: <span class="text-primary">{{ $year }}</span></h2>

    @if(count($results) === 0)
        <div class="alert alert-success text-center fs-5">
            ✅ لا يوجد اختلاف بين الهدايا المسجلة والمستلمة.
        </div>
    @else
        <div class="card p-3">
            <div class="mb-3">
                <h4 class="text-danger text-center">⚠ يوجد اختلاف بين المسجل والمستلم</h4>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>معرف المستخدم</th>
                            <th>العدد المسجل</th>
                            <th>العدد الفعلي المستلم</th>
                            <th>الفرق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $index => $r)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $r['user_id'] }}</td>
                                <td>{{ $r['registered'] }}</td>
                                <td>{{ $r['actual'] }}</td>
                                <td class="text-diff {{ $r['actual'] > $r['registered'] ? 'text-success' : 'text-danger' }}">
                                    {{ $r['actual'] - $r['registered'] 
                                    }}

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
