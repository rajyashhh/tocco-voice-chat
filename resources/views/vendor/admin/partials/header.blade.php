<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-messaging-compat.js"></script>
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<link rel="stylesheet" href="{{ asset('css/desktop.css') }}" media="screen and (min-width: 1200px)">

<script>
    window.PUSHER_CONFIG = @json(config('broadcasting.connections.pusher'));
    window.ADMIN_TYPE = @json(Auth::user()->type);
    window.ADMIN_ID = @json(Auth::user()->id);
    window.firebaseConfig = {
        apiKey: "{{ config('firebase.apiKey') }}",
        authDomain: "{{ config('firebase.authDomain') }}",
        projectId: "{{ config('firebase.projectId') }}",
        storageBucket: "{{ config('firebase.storageBucket') }}",
        messagingSenderId: "{{ config('firebase.messagingSenderId') }}",
        appId: "{{ config('firebase.appId') }}",
        vapidKey: "{{ config('firebase.vapid_key') }}"
    };
    window.ADMIN_ID = @json(Auth::user()->id);

</script>


<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
    // Setup CSRF token for all AJAX requests (required for Octane)
    (function() {
        var token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            window.Laravel = { csrfToken: token.content };
            
            // Setup jQuery AJAX defaults
            if (typeof $ !== 'undefined') {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': token.content
                    }
                });
            }
            
            // Also setup when jQuery loads later
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof $ !== 'undefined' || typeof jQuery !== 'undefined') {
                    ($ || jQuery).ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                }
            });
        }
    })();
</script>

<style>
    :root {
        /* Fixed panel theme (owner decision: not admin-configurable) */
        --primary-color: #FF9428;
        --secondary-color: #1A1A1A;
        --text-primary-color: #fdf8f8;
        --text-secondary-color: #c1b9b9;
        --panel-text-color: #ffffff;
        --box-background-color: #222222;
        --table-background-color: #c88213;
        --background-image: none;
        --brand_background-image: none;
        --second-alpha: rgba(31, 41, 55, 0.1);
        --primary-hover-alpha: rgba(37, 99, 235, 0.1);
        --scroll-second-color: rgba(255, 255, 255, 0.8);
        --scroll-first-color: rgba(37, 99, 235, 0.2);

        --inverse-color: #ffffff;
        --inverse-box-color: #1f2937;
        --success-button: linear-gradient(135deg, #10b981 0%, #059669 100%);
        --primary-button: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);

        /* Additional unified color variables */
        --white: #ffffff;
        --gray-800: #1f2937;
        --gray-700: #374151;
        --gray-50: #f9fafb;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-900: #111827;

        /* Modern Design Variables */
        --sidebar-width: 280px;
        --header-height: 70px;
        --border-radius: 12px;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --crs-red-hover: #e74c3c;
        --crs-white-faint: rgba(255, 255, 255, 0.06);
        --crs-white-faint-2: rgba(255, 255, 255, 0.16);
        --crs-transition: 320ms;
        --crs-ease: cubic-bezier(0.25, 0.8, 0.25, 1);
        --crs-font: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
</style>

</script>

<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-no {
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        animation: modal-appear 0.3s ease-out;
    }

    @keyframes modal-appear {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #e9ecef;
        background-color: #f8f9fa;
    }

    .rtl .modal-header {
        display: block !important;
    }

    .rtl .close {
        float: left;
    }

    .modal-header h5 {
        margin: 0;
        font-weight: 600;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6c757d;
        transition: color 0.2s;
        line-height: 1;
    }

    .close-btn:hover {
        color: #343a40;
    }

    .modal-body2 {
        padding: 0;
        max-height: 400px;
        overflow-y: auto;
    }

    .notification-item {
        padding: 14px 20px;
        border-bottom: 1px solid #f1f3f4;
        transition: background-color 0.2s;
        cursor: pointer;
    }

    .notification-item:hover {
        background-color: #f8f9fa;
    }

    .notification-item.unread {
        background-color: #e7f1ff;
    }

    .notification-item.unread:hover {
        background-color: #dbe9fd;
    }

    .notification-title {
        font-weight: 500;
        margin-bottom: 4px;
        color: #212529;
    }

    .notification-time {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .modal-footer {
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-top: 1px solid #e9ecef;
        background-color: #f8f9fa;
    }

    .btn-footer {
        border-radius: 6px;
        font-weight: 500;
        padding: 8px 16px;
        transition: all 0.2s;
    }

    .btn-mark-all {
        background-color: var(--primary-color);
        border: 1px solid #0d6efd;
        color: white;
    }

    .btn-mark-all:hover {
        background-color: var(--primary-color);
        border-color: #0a58ca;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
    }

    .btn-show-more {
        background-color: var(--primary-color);
        border: 1px solid #6c757d;
        color: white;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .btn-show-more:hover {
        background-color: var(--primary-color);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.2);
    }

    .btn-show-more i {
        margin-left: 6px;
        font-size: 0.9em;
    }

    .text-center {
        text-align: center;
    }

    .text-muted {
        color: #6c757d !important;
    }

    .p-3 {
        padding: 1rem !important;
    }

    #preview-buttons-wrapper {
        display: inline-block;
        vertical-align: middle;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .modal-no {
            width: 95%;
        }

        .btn-footer {
            width: 100%;
        }
    }

    .logo:hover {
        background-color: none !important;
    }

    .logo:hover {
        background-color: inherit !important;
    }

    .main-header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 9999;
    }

    .menu-link {
        opacity: 1 !important;
    }

    .logo {
        box-shadow: none !important;
        /* Sidebar header (logo "M" area) blends with the menu surface in both
           modes instead of a fixed orange gradient (owner decision 2026-08). */
        background: var(--sidebar-bg) !important;
        border-bottom: 1px solid var(--sidebar-border) !important;
    }

    .skin-black-light .main-header {
        -webkit-box-shadow: none !important;
        box-shadow: none !important;
        margin-bottom: 13px !important;
    }

    @media (max-width: 767px) {

        .logo {
            display: none !important;
        }

        .select-country-wrapper {
            display: none !important;
        }
    }

    #mobileSelectBtn {
        display: none;
    }

    .mobile-select-toggle {
        display: none;

    }

    @media (max-width: 768px) {
        #preview-buttons-wrapper,
        #preview-buttons-wrapper li,
        #preview-buttons-wrapper button {
            display: none !important;
        }
    }

    @media (max-width: 768px) {
        .mobile-preview-buttons {
            display: block;
        }
    }

    @media (min-width: 769px) {
        .mobile-preview-buttons {
            display: none !important;
        }
    }

    .mobile-preview-buttons button {
        width: 100%;
        text-align: center;
        margin-bottom: 10px;
        border-radius: 6px;
    }
</style>
<header class="main-header">
    <a href="{{ admin_url('/') }}" class="menu-link logo d-flex align-items-center gap-2">
        <div class="logo-icon ms-2">
            @php
                $logo   = getAppLogo();
                $locale = $lang ?? app()->getLocale();
                $appName = $locale == 'ar'
                    ? Cache::get('app_title_ar')
                    : Cache::get('app_title_en');
            @endphp

            @if(!empty($logo))
                <img src="{{ $logo }}"
                     alt="{{ $appName }}">
            @else
                <span class="rounded-circle bg-primary text-white d-flex justify-content-center align-items-center"
                      style="height:32px; width:32px; font-weight:bold;">
                {{ strtoupper(substr($appName, 0, 1)) }}
            </span>
            @endif
        </div>
    </a>

    <nav class="navbar navbar-static-top" role="navigation">

        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>

        @php
            $areaManagers = \Illuminate\Support\Facades\Cache::remember('header_area_managers', 300, fn() => \Modules\Region\Entities\AreaManager::select(['id','name','username','avatar'])->get());
            $selectAreaManagerId = session('area_manager_id') ?? request('area_manager_id');
            $selectedAreaManager   = $areaManagers->firstWhere('id', (int) $selectAreaManagerId);
            if (Admin::user()->type == 'superadmin'){
                $country  = \Illuminate\Support\Facades\Cache::remember('header_country_'.Admin::user()->country_id, 300, fn() => \App\Models\Country::find(Admin::user()->country_id));
            }
//            $countries = \App\Models\Country::query()->when($selectAreaManagerId, fn($q) => $q->where('area_manager_id', $selectAreaManagerId))->select(['id', 'name', 'flag'])->get();

            $countries = collect();
            if ($selectAreaManagerId) {
                $areaManager = \Illuminate\Support\Facades\Cache::remember('header_area_manager_'.$selectAreaManagerId, 300, fn() => \Modules\Region\Entities\AreaManager::find($selectAreaManagerId));
                if ($areaManager && method_exists($areaManager, 'countries')) {
                    $countries = \Illuminate\Support\Facades\Cache::remember('header_am_countries_'.$selectAreaManagerId, 300, fn() => $areaManager->countriesQuery()->select(['id', 'name', 'e_name','flag'])->get());
                }
            } else {
                $countries = \Illuminate\Support\Facades\Cache::remember('header_all_countries', 300, fn() => \App\Models\Country::select(['id', 'name', 'e_name','flag'])->get());
            }

//            $authId = auth()->user()->type == 'area-manager' ? auth()->id() : auth()->user()->parent_id;
//            $authAdmin = \Modules\Region\Entities\AreaManager::find($authId);

            if(auth()->user()->type == 'area-manager'){
                $authAdmin = auth()->user();
            } else {
                $authId = auth()->user()->parent_id;
                $authAdmin = $authId ? \Illuminate\Support\Facades\Cache::remember('header_auth_admin_'.$authId, 300, fn() => \Modules\Region\Entities\AreaManager::find($authId)) : null;
            }

            if ($authAdmin && method_exists($authAdmin, 'countriesQuery')) {
                $cacheKey = 'header_auth_admin_countries_'.($authAdmin->id ?? 0);
                $areaManagerCountries = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, fn() => $authAdmin->countriesQuery()->select(['id', 'name','e_name', 'flag'])->get());
            } else {
                $areaManagerCountries = collect();
            }

            $selectedCountryId = session('filter_country_id') ?? request('filter_country_id') ?? Admin::user()->country_id;
            $selectedCountry = $countries->firstWhere('id', (int) $selectedCountryId);

            $selectedAreaManagerCountryId = session('area_manager_country_id') ?? request('area_manager_country_id') ?? Admin::user()->country_id;
            $selectedAreaManagerCountry   = $areaManagerCountries->firstWhere('id', (int) $selectedAreaManagerCountryId);

        @endphp

        @if (request()->is('admin*'))
            <div class="select-country-wrapper">
                <a class="nav-item select-country">
                    <select id="area-Manager-select" class="form-control">
                        <option value="">{{ __('Select area manager') }}</option>
                        @foreach($areaManagers as $areaManager)
                            <option
                                value="{{ $areaManager->id }}"
                                data-flag="{{ getImagePath($areaManager->avatar) }}"
                                {{ (string)$selectAreaManagerId === (string)$areaManager->id ? 'selected' : '' }}>
                                {{ $areaManager->name ?? $areaManager->username }}
                            </option>
                        @endforeach
                    </select>
                </a>
                <a class="nav-item select-country">
                    <select id="country-select" class="form-control">
                        <option value="">{{ __('Select Country...') }}</option>
                        @foreach($countries as $currentCountry)
                            <option
                                value="{{ $currentCountry->id }}"
                                data-flag="{{ getImagePath($currentCountry->flag) }}"
                                {{ (string)$selectedCountryId === (string)$currentCountry->id ? 'selected' : '' }}>
                                {{app()->getLocale() === 'ar' ?  $currentCountry->name :$currentCountry->e_name }}
                            </option>
                        @endforeach
                    </select>
                </a>
            </div>

            <div class="mobile-select-toggle d-lg-none">
                <a href="javascript:void(0);"
                   id="mobileSelectBtn" class="sidebar-toggle mobile-select-toggle-btn" role="button">
                    <i class="fa fa-sliders"></i>
                </a>

                <div id="mobileSelectMenu" class="mobile-select-menu">
                    <select id="area-Manager-select-mobile" class="form-control" style="margin: 6% 0%;">
                        <option value="">{{ __('Select area manager') }}</option>
                        @foreach($areaManagers as $areaManager)
                            <option value="{{ $areaManager->id }}">
                                {{ $areaManager->name ?? $areaManager->username }}
                            </option>
                        @endforeach
                    </select>

                    <select id="country-select-mobile" class="form-control mt-2" style="margin: 6% 0%;">
                        <option value="">{{ __('Select Country...') }}</option>
                        @foreach($countries as $currentCountry)
                            <option value="{{ $currentCountry->id }}">
                                {{ app()->getLocale() === 'ar' ? $currentCountry->name : $currentCountry->e_name }}
                            </option>
                        @endforeach
                    </select>

                    @if (request()->is('areaManager*'))
                        <a class="nav-item select-country">
                            <select id="country-select" class="form-control" style="margin: 6% 0%;">
                                <option value="">{{ __('Select Country...') }}</option>
                                @foreach($areaManagerCountries as $currentCountry)
                                    <option
                                        value="{{ $currentCountry->id }}"
                                        data-flag="{{ getImagePath($currentCountry->flag) }}"
                                        {{ (string)$selectedAreaManagerCountryId === (string)$currentCountry->id ? 'selected' : '' }}>
                                        {{app()->getLocale() === 'ar' ?  $currentCountry->name :$currentCountry->e_name }}
                                    </option>
                                @endforeach
                            </select>
                        </a>
                    @endif

                    @if(!session('preview_superadmin') && session('filter_country_id') && !session('area_manager_id'))
                        @if (request()->is('admin*'))
                            <button id="preview-superadmin-btn-mobile" class="btn btn-default btn-block"
                                    style="margin: 6% 0%;">
                                <i class="fa fa-eye"></i> {{ __('go to the country') }}
                            </button>
                        @endif
                    @elseif(!session('preview_area_manager')  && session('area_manager_id') )
                        @if (request()->is('admin*'))
                            <button id="preview-area-manger-btn-mobile" class="btn btn-default btn-block"
                                    style="margin: 6% 0%;">
                                <i class="fa fa-eye"></i> {{ __('go to the preview') }}
                            </button>
                        @endif
                    @endif

                    @if(session('preview_superadmin') || session('preview_area_manager'))
                        @if (request()->is('admin*'))
                            <button id="exit-preview-btn-mobile" class="btn btn-danger btn-block mt-2"
                                    style="margin: 6% 0%;">
                                <i class="fa fa-times"></i> {{ __('Back to the main dashboard') }}
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        @endif

        @if (request()->is('areaManager*'))
            <a class="nav-item select-country">
                <select id="country-select" class="form-control">
                    <option value="">{{ __('Select Country...') }}</option>
                    @foreach($areaManagerCountries as $currentCountry)
                        <option
                            value="{{ $currentCountry->id }}"
                            data-flag="{{ getImagePath($currentCountry->flag) }}"
                            {{ (string)$selectedAreaManagerCountryId === (string)$currentCountry->id ? 'selected' : '' }}>
                            {{app()->getLocale() === 'ar' ?  $currentCountry->name :$currentCountry->e_name }}
                        </option>
                    @endforeach
                </select>
            </a>
        @endif
        <script>window.enableCountryHeader = true;</script>


        <ul class="nav navbar-nav hidden-sm visible-lg-block">
            {!! Admin::getNavbar()->render('left') !!}
        </ul>

        <div class="navbar-custom-menu">

            @if (Admin::user()->type == 'superadmin' && $country && $country->flag)
                <img src="{{ getImagePath($country->flag) }}"
                     class="flag-image"
                     alt="flag Image"
                     title="{{ app()->getLocale() === 'ar' ? $country->name : $country->e_name }}">
            @endif

            <ul class="nav navbar-nav">

                <li>
                    <button type="button" id="theme-toggle-btn" title="{{ __('Toggle theme') }}" aria-label="{{ __('Toggle theme') }}">
                        <span class="tt-moon">🌙</span><span class="tt-sun">☀️</span>
                    </button>
                </li>

                <ul class="nav navbar-nav hidden-sm visible-lg-block" style="    padding: 0px !important;">
                    @if (!Admin::user()->type || Admin::user()->type == '')

                    @endif


                    @php
                        $admin = Auth::user();

                    @endphp

                    @if (empty($admin->type))
                        @include('admin.notifications.admin')
                    @endif

                    @if ($admin->type == 'superadmin')

                        @include('SuperAdmin::notifications.super')

                    @endif

                </ul>
                {!! Admin::getNavbar()->render() !!}

                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        {!! adminAvatarTag(Admin::user()->image, Admin::user()->name, Admin::user()->username, 'user-image') !!}
                        <span class="hidden-xs">{{ Admin::user()->name ?? Admin::user()->username }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="user-header">
                            {!! adminAvatarTag(Admin::user()->image, Admin::user()->name, Admin::user()->username, 'img-circle') !!}
                            <p>
                                {{ Admin::user()->name }}
                                <small>Member since admin {{ Admin::user()->created_at }}</small>
                            </p>
                        </li>
                        <li class="user-footer">
                            <div class="pull-left">
                                @if (Admin::user()->type == 'bd')
                                    <a href="{{ bd_url('setting') }}"
                                       class="btn btn-default btn-flat">{{ trans('admin.setting') }}</a>
                                @else
                                    <a href="{{ admin_url('auth/setting') }}"
                                       class="btn btn-default btn-flat">{{ trans('admin.setting') }}</a>
                                @endif
                            </div>
                            <div class="pull-right">
                                @if (Admin::user()->type == 'bd')
                                    <a href="{{ bd_url('/logout') }}"
                                       class="btn btn-default btn-flat">{{ trans('admin.logout') }}</a>
                                @else
                                    <a href="{{ admin_url('auth/logout') }}"
                                       class="btn btn-default btn-flat">{{ trans('admin.logout') }}</a>
                                @endif
                            </div>
                        </li>
                    </ul>
                </li>

                <span id="preview-buttons-wrapper">
                    @if(!session('preview_superadmin') && session('filter_country_id') && !session('area_manager_id'))
                        @if (request()->is('admin*'))
                            <li style="padding: 10px;">
                                <button id="preview-superadmin-btn" class="btn btn-default preview-superadmin-btn">
                                    <i class="fa fa-eye"></i> {{ __('go to the country') }}
                                </button>
                            </li>
                        @endif
                    @elseif(!session('preview_area_manager')  && session('area_manager_id') )
                        @if (request()->is('admin*'))
                            <li style="padding: 10px;">
                                <button id="preview-area-manger-btn" class="btn btn-default preview-area-manger-btn">
                                    <i class="fa fa-eye"></i> {{ __('go to the preview') }}
                                </button>
                            </li>
                        @endif
                    @endif

                    @if(session('preview_superadmin') || session('preview_area_manager') )
                        @if (request()->is('admin*'))
                            <li style="padding: 10px;">
                                <button id="exit-preview-btn" class="btn btn-danger exit-preview-btn"">
                                <i class="fa fa-times"></i> {{ __('Back to the main dashboard') }}
                                </button>
                            </li>
                        @endif
                    @endif
                </span>
                <!-- Control Sidebar Toggle Button -->
                {{-- <li><a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a></li> --}}
            </ul>
        </div>
    </nav>
</header>

<script>

    $('.container-refresh').off('click').on('click', function () {
        location.reload();
        toastr.success('{{ __('admin.refresh_succeeded') }}', '', {positionClass: "toast-top-center"});
    });

    $('#area-Manager-select-mobile').on('change', function () {
        const val = $(this).val();
        $('#area-Manager-select').val(val).trigger('change');
    });

    $('#country-select-mobile').on('change', function () {
        const val = $(this).val();
        $('#country-select').val(val).trigger('change');
    });


    $(document).ready(function () {
        const $countrySelect = $('#country-select');
        const $AreaManagerSelect = $('#area-Manager-select');
        const isPreviewSuperadmin = @json(session('preview_superadmin'));
        const isPreviewAreaManager = @json(session('preview_area_manager'));

        $('#area-Manager-select').select2({
            placeholder: '{{ __("Select area manager") }}',
            allowClear: true,
            width: '190px'
        });

        $AreaManagerSelect.on('change', function () {
            const $this = $(this);
            if (!$this.val()) {
                setTimeout(() => $this.select2('close'), 0);

                const url = new URL(window.location.href);
                url.searchParams.set('clear_area_manager', 1);
                url.searchParams.delete('area_manager_id');

                if ($.pjax) {
                    setTimeout(() => {
                        $.pjax({url: url.toString(), container: '#pjax-container'});
                    }, 1);
                } else {
                    window.location.href = url.toString();
                }
                return;
            }

            const areaManagerId = $(this).val();
            const url = new URL(window.location.href);

            if (areaManagerId && areaManagerId !== 'null') {
                url.searchParams.set('area_manager_id', areaManagerId);
                url.searchParams.delete('clear_area_manager');
            } else {
                url.searchParams.set('clear_area_manager', 1);
                url.searchParams.delete('area_manager_id');
            }

            // if ($.pjax) {

            //     setTimeout(() => {
            //         $.pjax({url: url.toString(), container: '#pjax-container'});
            //     }, 1);
            // } else {
            window.location.href = url.toString();
            // }
        });

        if ($countrySelect.length) {
            $countrySelect.select2({
                placeholder: "{{ __('Select Country') }}",
                allowClear: true,
                templateResult: formatCountry,
                templateSelection: formatCountry,
                escapeMarkup: function (markup) {
                    return markup;
                }
            });

            $countrySelect.on('change', function () {
                const $this = $(this);
                if (!$this.val()) {
                    setTimeout(() => $this.select2('close'), 0);

                    const url = new URL(window.location.href);
                    url.searchParams.set('clear_country', 1);
                    url.searchParams.delete('filter_country_id');

                    if ($.pjax) {
                        setTimeout(() => {
                            $.pjax({url: url.toString(), container: '#pjax-container'});
                        }, 1);
                    } else {
                        window.location.href = url.toString();
                    }
                    return;
                }

                const countryId = $(this).val();
                const url = new URL(window.location.href);

                if (countryId && countryId !== 'null') {
                    url.searchParams.set('filter_country_id', countryId);
                } else {
                    url.searchParams.set('filter_country_id', 'null');
                }

                if ($.pjax) {
                    setTimeout(() => {
                        $.pjax({url: url.toString(), container: '#pjax-container'});
                    }, 1);
                } else {
                    window.location.href = url.toString();
                }
            });

            @if(request()->is('areaManager*'))
            $countrySelect.on('change', function () {
                const $this = $(this);
                if (!$this.val()) {
                    setTimeout(() => $this.select2('close'), 0);

                    const url = new URL(window.location.href);
                    url.searchParams.set('clear_area_manager_country', 1);
                    url.searchParams.delete('area_manager_country_id');

                    if ($.pjax) {
                        setTimeout(() => {
                            $.pjax({url: url.toString(), container: '#pjax-container'});
                        }, 1);
                    } else {
                        window.location.href = url.toString();
                    }
                    return;
                }

                const countryId = $(this).val();
                const url = new URL(window.location.href);

                if (countryId && countryId !== 'null') {
                    url.searchParams.set('area_manager_country_id', countryId);
                    url.searchParams.delete('clear_area_manager_country');
                } else {
                    url.searchParams.set('clear_area_manager_country', 1);
                    url.searchParams.delete('area_manager_country_id');
                }

                if ($.pjax) {
                    setTimeout(() => {
                        $.pjax({url: url.toString(), container: '#pjax-container'});
                    }, 1);
                } else {
                    window.location.href = url.toString();
                }
            });
            @endif
        }

        function formatCountry(country) {
            if (!country.id) {
                return country.text;
            }
            const flag = $(country.element).data('flag');
            const name = country.text;
            if (flag) {
                return `
                <span>
                    <img src="${flag}" style="width:20px; height:14px; margin-right:5px; vertical-align:middle;">
                    ${name}
                </span>
            `;
            }
            return name;
        }

        // Preserve language parameter in all links and form submissions
        function preserveLanguageInUrl(url) {
            const currentLocale = '{{ app()->getLocale() }}';
            const urlObj = new URL(url, window.location.origin);
            
            // Add locale parameter if not present and different from default
            if (currentLocale !== 'en' && !urlObj.searchParams.has('locale')) {
                urlObj.searchParams.set('locale', currentLocale);
            }
            
            return urlObj.toString();
        }

        // Override window.open to preserve language
        const originalWindowOpen = window.open;
        window.open = function(url, target, features) {
            if (url && typeof url === 'string') {
                url = preserveLanguageInUrl(url);
            }
            return originalWindowOpen.call(this, url, target, features);
        };

        // Handle all links with target="_blank"
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('a[target="_blank"]').forEach(function(link) {
                const href = link.getAttribute('href');
                if (href && !href.startsWith('javascript:') && !href.startsWith('#')) {
                    link.setAttribute('href', preserveLanguageInUrl(href));
                }
            });

            // Handle form submissions that might open in new windows
            document.querySelectorAll('form').forEach(function(form) {
                const originalSubmit = form.submit;
                form.submit = function() {
                    const currentLocale = '{{ app()->getLocale() }}';
                    if (currentLocale !== 'en') {
                        // Add hidden input for locale if not present
                        if (!form.querySelector('input[name="locale"]')) {
                            const localeInput = document.createElement('input');
                            localeInput.type = 'hidden';
                            localeInput.name = 'locale';
                            localeInput.value = currentLocale;
                            form.appendChild(localeInput);
                        }
                    }
                    return originalSubmit.call(this);
                };
            });
        });

        // Handle dynamic content loaded via AJAX/PJAX
        $(document).on('pjax:complete', function() {
            // Re-process links in dynamically loaded content
            document.querySelectorAll('a[target="_blank"]').forEach(function(link) {
                const href = link.getAttribute('href');
                if (href && !href.startsWith('javascript:') && !href.startsWith('#')) {
                    link.setAttribute('href', preserveLanguageInUrl(href));
                }
            });
        });

        // Also observe DOM changes for dynamically added content
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        node.querySelectorAll('a[target="_blank"]').forEach(function(link) {
                            const href = link.getAttribute('href');
                            if (href && !href.startsWith('javascript:') && !href.startsWith('#')) {
                                link.setAttribute('href', preserveLanguageInUrl(href));
                            }
                        });
                    }
                });
            });
        });

        // Start observing the document body for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        const originalFetch = window.fetch;
        window.fetch = function (url, options = {}) {
            options.headers = options.headers || {};

            if (window.enableCountryHeader) {
                const countryId = $('#country-select').val();
                options.headers['X-Country-ID'] = countryId ? countryId : 'null';
            }

            // Add locale header for AJAX requests
            const currentLocale = '{{ app()->getLocale() }}';
            if (currentLocale !== 'en') {
                options.headers['X-Locale'] = currentLocale;
            }

            if (!options.headers['Accept'])
                options.headers['Accept'] = 'application/json';

            return originalFetch(url, options);
        };

        const csrf = '{{ csrf_token() }}';
        const previewBtn = document.getElementById('preview-superadmin-btn');
        const exitBtn = document.getElementById('exit-preview-btn');

        const previewBtnArea = document.getElementById('preview-area-manger-btn');


        if (previewBtn) {
            previewBtn.addEventListener('click', function () {
                fetch('/admin/set-preview-superadmin', {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrf}
                }).then(() => window.location.reload());
            });
        }

        // if (exitBtn) {
        //     exitBtn.addEventListener('click', function () {
        //         fetch('/admin/unset-preview-superadmin', {
        //             method: 'POST',
        //             headers: { 'X-CSRF-TOKEN': csrf }
        //         }).then(() => window.location.reload());
        //     });
        // }

        if (previewBtnArea) {
            previewBtnArea.addEventListener('click', function () {
                fetch('/admin/set-preview-area-manager', {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrf}
                }).then(() => window.location.reload());
            });
        }

        if (exitBtn) {
            if (isPreviewSuperadmin) {
                exitBtn.addEventListener('click', function () {
                    fetch('/admin/unset-preview-superadmin', {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': csrf}
                    }).then(() => window.location.reload());
                });
            } else if (isPreviewAreaManager) {
                exitBtn.addEventListener('click', function () {
                    fetch('/admin/unset-preview-area-manager', {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': csrf}
                    }).then(() => window.location.reload());
                });
            }
        }


    });
</script>

<style>
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 25px;
    }

    .rtl .select2-container--default .select2-selection--single .select2-selection__clear {
        left: 5px !important;
    }

    .select2-container .select2-selection--single .select2-selection__rendered img {
        margin-right: 5px;
        vertical-align: middle;
    }
</style>


<script>
    window.handleNotificationClick = function (id, url) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        if (!id) return;

        fetch(`/admin/notifications/mark-as-read/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
        })
            .then(res => res.json())
            .then(data => {
                const el = document.querySelector(`.notification-item[data-id='${id}']`);
                if (el) {
                    el.classList.remove('unread');
                    el.classList.add('read');
                }
                if (url) {
                    window.location.href = url;
                }
            })
            .catch(err => console.error('Error marking notification:', err));
    };


    window.superAdminhandleNotificationClick = function (id, url) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        if (!id) return;

        fetch(`/superadmin/notifications/mark-as-read/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
        })
            .then(res => res.json())
            .then(data => {
                const el = document.querySelector(`.notification-item[data-id='${id}']`);
                if (el) {
                    el.classList.remove('unread');
                    el.classList.add('read');
                }
                if (url) {
                    window.location.href = url;
                }
            })
            .catch(err => console.error('Error marking notification:', err));
    };
</script>

<script>
    $(document).ready(function () {
        const $countrySelect = $('#country-select');
        const $AreaManagerSelect = $('#area-Manager-select');
        const isPreviewSuperadmin = @json(session('preview_superadmin'));
        const isPreviewAreaManager = @json(session('preview_area_manager'));

        // Function to format country options with flags
        function formatCountry(country) {
            if (!country.id) {
                return country.text;
            }
            const flag = $(country.element).data('flag');
            const name = country.text;
            if (flag) {
                return `
                <span>
                    <img src="${flag}" style="width:20px; height:14px; margin-right:5px; vertical-align:middle;">
                    ${name}
                </span>
            `;
            }
            return name;
        }

        // Function to initialize country select
        function initCountrySelect() {
            const $select = $('#country-select');
            if ($select.length) {
                // Destroy existing Select2 if it exists
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                // Initialize Select2
                $select.select2({
                    placeholder: "{{ __('Select Country') }}",
                    allowClear: true,
                    templateResult: formatCountry,
                    templateSelection: formatCountry,
                    escapeMarkup: function (markup) {
                        return markup;
                    }
                });

                // Remove any existing change handlers to prevent duplicates
                $select.off('change');

                // Add change handler
                $select.on('change', function () {
                    const $this = $(this);
                    if (!$this.val()) {
                        setTimeout(() => $this.select2('close'), 0);

                        const url = new URL(window.location.href);

                        @if(request()->is('admin*'))
                        url.searchParams.set('clear_country', 1);
                        url.searchParams.delete('filter_country_id');
                        @else
                        url.searchParams.set('clear_area_manager_country', 1);
                        url.searchParams.delete('area_manager_country_id');
                        @endif

                        if ($.pjax) {
                            setTimeout(() => {
                                $.pjax({url: url.toString(), container: '#pjax-container'});
                            }, 1);
                        } else {
                            window.location.href = url.toString();
                        }
                        return;
                    }

                    const countryId = $(this).val();
                    const url = new URL(window.location.href);

                    @if(request()->is('admin*'))
                    if (countryId && countryId !== 'null') {
                        url.searchParams.set('filter_country_id', countryId);
                        url.searchParams.delete('clear_country');
                    } else {
                        url.searchParams.set('clear_country', 1);
                        url.searchParams.delete('filter_country_id');
                    }
                    @else
                    if (countryId && countryId !== 'null') {
                        url.searchParams.set('area_manager_country_id', countryId);
                        url.searchParams.delete('clear_area_manager_country');
                    } else {
                        url.searchParams.set('clear_area_manager_country', 1);
                        url.searchParams.delete('area_manager_country_id');
                    }
                    @endif

                    if ($.pjax) {
                        setTimeout(() => {
                            $.pjax({url: url.toString(), container: '#pjax-container'});
                        }, 1);
                    } else {
                        window.location.href = url.toString();
                    }
                });
            }
        }
    })

    document.addEventListener("DOMContentLoaded", function () {
        const mobileBtn = document.getElementById("mobileSelectBtn");
        const mobileMenu = document.getElementById("mobileSelectMenu");

        if (mobileBtn) {
            mobileBtn.addEventListener("click", function (e) {
                e.stopPropagation();
                mobileMenu.classList.toggle("show");
                mobileBtn.classList.toggle("active");
            });
        }

        document.addEventListener("click", function (e) {
            if (!mobileMenu.contains(e.target) && !mobileBtn.contains(e.target)) {
                mobileMenu.classList.remove("show");
                mobileBtn.classList.remove("active");
            }
        });
    });

</script>
