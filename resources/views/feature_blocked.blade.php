<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>Feature Unavailable</title>

    <style>
        body {
            background: #f5f6fa;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        .box {
            background: #fff;
            padding: 50px;
            margin: 120px auto;
            width: 450px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        h1 {
            font-size: 70px;
            margin: 0;
            color: #ff4d4f;
        }

        h3 {
            color: #333;
            font-size: 24px;
        }

        p {
            color: #666;
            font-size: 16px;
        }

        a.btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background: #3c8dbc;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
        }

        a.btn:hover {
            background: #367fa9;
        }
    </style>
</head>
<body>

<div class="box">
    <h1>403</h1>

    <h3>{{ __('This feature is currently unavailable.') }}</h3>

    <p>{{ __('Please contact the application administration.') }}</p>

    <a href="/admin" class="btn">← {{__('Back to Dashboard')}}</a>
</div>

</body>
</html>
