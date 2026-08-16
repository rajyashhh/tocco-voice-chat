<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة تحكم الريلز')</title>

</head>
<body class="bg-gray-100">
    <div class="flex h-screen">


        <!-- Main Content -->
        <main class="flex-1 overflow-hidden">
            @yield('content')
        </main>
    </div>
</body>
</html>
