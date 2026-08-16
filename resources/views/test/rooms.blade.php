<!DOCTYPE html>
<html>
<head>
    <title>My Data Test</title>
</head>
<body>
<h2>Test: User Data Processing</h2>

<form method="POST" action="{{ url('admin/rooms-test') }}">
    @csrf

{{--    <label>Device Token:</label>--}}
{{--    <input type="text" name="X-Device-Token" placeholder="Enter X-Device-Token">--}}
{{--    <br><br>--}}

{{--    <label>Latitude:</label>--}}
{{--    <input type="text" name="lat" placeholder="Enter latitude">--}}
{{--    <br><br>--}}

{{--    <label>Longitude:</label>--}}
{{--    <input type="text" name="long" placeholder="Enter longitude">--}}
{{--    <br><br>--}}

    <button type="submit">Process My Data</button>
</form>

<br>

@isset($message)
    @if ($success)
        <p style="color: green;">✅ {{ $message }}</p>
        @if(isset($data))
            <pre>
                {{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
            </pre>
        @endif
    @else
        <p style="color: red;">❌ {{ $message }}</p>
    @endif
@endisset

</body>
</html>
