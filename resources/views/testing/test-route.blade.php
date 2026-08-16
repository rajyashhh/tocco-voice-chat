<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>اختبار Route</title>
</head>
<body>
    <h1>اختبار نقطة النهاية</h1>

    <button onclick="testRoute()">اختبر Route</button>

    <div id="result"></div>

    <script>
        async function testRoute() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>جاري الاختبار...</p>';

            try {
                const response = await fetch('{{ route("stress-test.run") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        sender_ids: '1',
                        receiver_ids: '2',
                        gift_id: 1,
                        room_id: 1,
                        num: 1,
                        count: 1,
                        requests_per_user: 1,
                        concurrent: false
                    })
                });

                const data = await response.json();
                resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';

            } catch (error) {
                resultDiv.innerHTML = '<p style="color:red">خطأ: ' + error.message + '</p>';
            }
        }
    </script>
</body>
</html>
