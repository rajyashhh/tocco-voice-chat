<!DOCTYPE html>
<html>
<head>
    <title>Send Gift Test</title>
</head>
<body>
<h2>Send Gift Test</h2>

<form method="POST" action="{{ url('admin/send-test') }}">
    @csrf

    <input type="hidden" name="id" value="463">
    <input type="hidden" name="owner_id" value="303">
    <input type="hidden" name="toUid" value="1206">
    <input type="hidden" name="num" value="400">
    <input type="hidden" name="type" value="test">

    <button type="submit">Send Gift</button>
</form>

<br>

@isset($message)
    @if ($success)
        <p style="color: green;">✅ {{ $message }}</p>
    @else
        <p style="color: red;">❌ {{ $message }}</p>
    @endif
@endisset
</body>
</html>
