
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{config('admin.title')}} | {{ trans('admin.login') }}</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;700&display=swap" rel="stylesheet">

    @if(!is_null($favicon = Admin::favicon()))
        <link rel="shortcut icon" href="{{$favicon}}">
    @endif
    <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/AdminLTE/bootstrap/css/bootstrap.min.css") }}">
    <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/font-awesome/css/font-awesome.min.css") }}">
    <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/AdminLTE/dist/css/AdminLTE.min.css") }}">
    <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/AdminLTE/plugins/iCheck/flat/green.css") }}">
    <link rel="stylesheet" href="{{asset('css/dashboard.css')}}">
</head>

<body class="hold-transition login-page" @if(config('admin.login_background_image'))style="background: url({{config('admin.login_background_image')}}) no-repeat;background-size: cover;"@endif>

<div class="login-box">
    <div class="login-logo">
        @php
            $logo = App\Models\Setting::where('key', 'app_logo')->first();
            $logo_url =  $logo?->value;
        @endphp
        <div>
            <img src="{{ empty($logo)? asset('images/app-logo.png') : getImagePath($logo_url)}}" style="width: 150px;">
        </div>
        <div class="box-title">
            <a href="{{ admin_url('/') }}" style="color: var(--green-color);">{{__('change password')}}</a>
        </div>
    </div>

    <div class="login-box-body">
        <form id="password-form" action="{{ areaManager_url('change-password') }}" method="post">
            @csrf

            <div class="form-group">
                <label for="password" style="font-weight: bold;">
                    {{ __('please enter new password') }}
                </label>
                <input type="password" id="password" class="form-control input-lg text-center" 
                       placeholder="{{ trans('admin.password') }}" 
                       name="password" required>
                <input type="hidden" name="username" value="{{ @$userName }}">
                 {{-- <input type="hidden" name="type" value="{{ @$type }}"> --}}
            </div>

            <div class="form-group">
                <label for="password_confirmation" style="font-weight: bold;">
                    {{ __('please confirm new password') }}
                </label>
                <input type="password" id="password_confirmation" class="form-control input-lg text-center" 
                       placeholder="{{ __('Confirm Password') }}" 
                       name="password_confirmation" required>
                <span id="password-error" style="color:red; display:none; font-weight:bold;">
                    {{ __('Passwords do not match') }}
                </span>
            </div>

            <button type="submit" class="btn btn-success btn-block btn-lg btn-flat rounded submit">
                {{ trans('change') }}
            </button>
        </form>

        <!-- <div class="language-switch text-center">
            <a href="#" id="language-switcher" style="color: var(--green-color);">{{__('dashboard.login.language.switch')}} <span style="font-weight: bold;">{{__('dashboard.login.language.lang')}}</span></a>
        </div> -->

    </div>
</div>

<script src="{{ admin_asset("vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js")}}"></script>
<script>
    $(function () {

        $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $('#language-switcher').on('click', function (e) {
    e.preventDefault();
        let current_locale = '{{$current}}';
        let locale = (current_locale === 'ar') ? 'en' : 'ar';

        $.ajax({
            url: "{{ areaManager_url('/locale') }}",
            type: "POST",
            data: { locale: locale },
            success: function () {
                location.reload();
            },
            error: function (xhr) {
                console.log("Error:", xhr.responseText);
                alert("CSRF error - check token setup!");
            }
        });
    });

        // تحقق من تطابق الباسورد قبل الإرسال
        $('#password-form').on('submit', function(e) {
            let pass = $('#password').val();
            let confirm = $('#password_confirmation').val();

            if (pass !== confirm) {
                e.preventDefault();
                $('#password-error').show();
            } else {
                $('#password-error').hide();
            }
        });
    });
</script>
</body>
</html>
