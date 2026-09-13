<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" translate="no" class="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="renderer" content="webkit">
    <meta name="csrf-token" content="{{ csrf_token() }}">
{{--    <title>{{ Admin::title() }} @if($header) | {{ $header }}@endif</title>--}}
    <title>{{ config('app.name', 'Laravel') }}</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="manifest" href="{{ route('manifest.json') }}">

    @if(!is_null($favicon = Admin::favicon()))
        <link rel="shortcut icon" href="{{ $favicon }}" type="image/png">
        <link rel="icon" href="{{ $favicon }}" type="image/png">
    @else
        <link rel="shortcut icon" href="{{ getAppLogo() }}" type="image/png">
        <link rel="icon" href="{{ getAppLogo() }}" type="image/png">
    @endif
    {!! Admin::css() !!}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css"/>

    <script src="{{ Admin::jQuery() }}"></script>
    {!! Admin::headerJs() !!}

</head>

<body class="hold-transition {{config('admin.skin')}} {{join(' ', config('admin.layout'))}}">

<script>
    (function() {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapse');
        }
        document.documentElement.classList.remove('sidebar-collapse-init');
    })();
</script>

@if($alert = config('admin.top_alert'))
    <div style="text-align: center;padding: 5px;font-size: 12px;background-color: #ffffd5;color: #ff0000;">
        {!! $alert !!}
    </div>
@endif

<div class="wrapper">

    @include('admin::partials.header')

    @include('admin::partials.sidebar')

    <div class="content-wrapper" id="pjax-container">
        {!! Admin::style() !!}
        <div id="app" class="app-class" style="margin-top: 7%;">
        @yield('content')
        </div>
        {!! Admin::script() !!}
        {!! Admin::html() !!}
    </div>

    @include('admin::partials.footer')

</div>

<button id="totop" title="Go to top" style="display: none;"><i class="fa fa-chevron-up"></i></button>

<script>
    function LA() {}
    LA.token = "{{ csrf_token() }}";
    LA.user = @json($_user_);

    // Synchronize AdminLTE desktop sidebar-collapse state with localStorage
    $(document).on('collapsed.pushMenu', function () {
        try { localStorage.setItem('sidebarCollapsed', 'true'); } catch (e) {}
    });

    $(document).on('expanded.pushMenu', function () {
        try { localStorage.setItem('sidebarCollapsed', 'false'); } catch (e) {}
    });

    // Preserve sidebar-collapse state across PJAX container updates
    $(document).on('pjax:complete', function () {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapse');
        } else {
            document.body.classList.remove('sidebar-collapse');
        }
    });
</script>

<!-- REQUIRED JS SCRIPTS -->
{!! Admin::js() !!}

<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
</body>
</html>
