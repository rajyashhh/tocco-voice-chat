<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link rel="stylesheet" href="{{ admin_asset('vendor/fontawesome6/css/all.min.css') }}">

    {{-- Central design system: single source of truth for colors/surfaces. --}}
    @include('css.theme-tokens')

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--page-bg);
            color: var(--text-primary);
            display: flex;
        }
        .settings-sidebar {
            width: 250px;
            background: var(--surface);
            min-height: 100vh;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
        }
        .settings-sidebar h2 {
            text-align: center;
            color: var(--accent);
        }
        .settings-content {
            flex-grow: 1;
            width: 1200px;
            padding: 20px;
        }
        form {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 5px;
            width: 900px;
            margin: auto;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            color: var(--text-primary);
        }
        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--input-text);
        }
        button {
            padding: 10px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            background: var(--accent);
            color: var(--on-color);
            border-radius: 5px;
        }

        .avatar {
            text-align: center;
            margin-bottom: 20px;
        }
        .avatar img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 3px solid var(--accent);
            cursor: pointer;
        }
        .avatar input {
            display: none;
        }
    </style>
</head>
<body>
    <div class="settings-content">
        <form action="{{ '/auth/setting' }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="avatar">
                <label for="avatarInput">
                    <img id="avatarPreview" src="{{ asset(Auth::user()->avatar) }}" alt="Avatar">
                </label>
                <input type="file" id="avatarInput" name="avatar" accept="image/*" onchange="previewAvatar(event)">
            </div>
            <label for="name">{{ __('admin.username') }}</label>
            <input type="text" id="name" name="username" value="{{ old('username', Auth::user()->username) }}" required>
            <label for="name">{{ __('Name') }}</label>
            <input type="text" id="name" name="name" value="{{ old('name', Auth::user()->name) }}" required>
            <label for="password">{{ __('Password') }}</label>
            <input type="password" id="password" name="password" value="{{ Auth::user()->password }}">
            <label for="password_confirmation">{{ __('Confirm Password') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation" value="{{ Auth::user()->password }}">
            <button type="submit">{{ __('admin.save') }}</button>
        </form>
    </div>
    <script>
        function previewAvatar(event) {
            const reader = new FileReader();
            reader.onload = function() {
                document.getElementById('avatarPreview').src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>
