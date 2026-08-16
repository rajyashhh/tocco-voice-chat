<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-color:
                {{ config('themes.primaryColor') }}
            ;
            --secondary-color:
                {{ config('themes.secondaryColor') }}
            ;
            --text-primary-color:
                {{ config('themes.textPrimaryColor') }}
            ;
            --text-secondary-color:
                {{ config('themes.textSecondaryColor') }}
            ;
            --box-background-color:
                {{ config('themes.boxBackgroundColor') }}
            ;
            --table-background-color:
                {{ config('themes.tableBackGroundColor')}}
                --background-image:
                {{ config('themes.backgroundImage') }}
            ;
            --brand_background-image: url({{ getImagePath(config('themes.brandBackgroundImage')) }});
            --second-alpha:
                {{ adjustColor(config('themes.boxBackgroundColor'), -30, -30, -30) }}
                55;
            --primary-hover-alpha:
                {{ config('themes.primaryColor')}}
                33;
            --scroll-second-color:
                {{ config('themes.boxBackgroundColor') }}
                cc;
            --scroll-first-color:
                {{ adjustColor(config('themes.primaryColor'), 40, 40, 40) }}
                33;


            --inverse-color:
                {{getLighterColor(config('themes.primaryColor'))}}
            ;
            --inverse-box-color:
                {{adjustTextColor(config('themes.boxBackgroundColor'))}}
            ;
            --success-button: linear-gradient(90deg,
                    {{adjustColor(config('themes.primaryColor'))}}
                    0%,
                    {{config('themes.primaryColor')}}
                    100%);
            --primary-button: linear-gradient(90deg,
                    {{adjustColor(config('themes.primaryColor'))}}
                    0%,
                    {{config('themes.primaryColor')}}
                    100%);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 57px;
            color: white;
            font-size: 20px;
            margin-bottom: 6px;
        }

        .stat-icon.bg-blue {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }

        .stat-icon.bg-green {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1;
        }

        .stat-label {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .section-box {
            background: var(--secondary-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }


        .section-header h4 {
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header .text-yellow {
            color: #f39c12;
        }

        .section-header .text-red {
            color: #e74c3c;
        }


        .number-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #ecf0f1;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
        }

        .number-badge.warning {
            background: #fef9e7;
            color: #f39c12;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 0;
            color: #95a5a6;
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        .empty-table {
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #95a5a6;
            background: white;
            border-radius: 8px;
            margin: 15px 0;
        }

        .empty-table i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .empty-table p {
            margin: 0;
            font-size: 14px;
        }

        .agency-profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            /* background: var(--secondary-color); */
            /* filter: brightness(0.85); */

        }

        .agency-avatar {
            width: 120px;
            height: 120px;
            overflow: hidden;
            border: 4px solid #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .agency-avatar .logo-img {
            width: 100%;
            height: 100%;
            /* object-fit: cover; */
        }

        .agency-info {
            flex: 1;
        }

        .agency-name {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
        }

        .agency-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }

        .meta-label {
            font-weight: 600;
            color: #7f8c8d;
        }

        .meta-value {
            color: #34495e;
        }

        .meta-uuid {
            color: #95a5a6;
            font-size: 0.9em;
        }

        .agency-stats {
            display: flex;
            gap: 15px;
        }

        .stat-card {
            background: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            min-width: 200px;

        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #3498db;
        }

        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ltr .btn-back {
            position: absolute;
            top: 5px;
            right: 20px;
            background: #ecf0f1;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            color: #7f8c8d;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .rtl .btn-back {
            position: absolute;
            top: 5px;
            left: 20px;
            background: #ecf0f1;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            color: #7f8c8d;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-back:hover {
            background: #d6e0e3;
            color: #34495e;
        }

        .notice-section {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 0 6px 6px 0;
            margin-bottom: 25px;
        }

        .notice-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #ff9800;
            font-weight: 600;
        }

        .notice-content {
            color: #5d4037;
            line-height: 1.5;
        }

        .top-performers-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .top-performers-section {
                grid-template-columns: 1fr;
            }
        }

        .stats-row {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .section-title {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2c3e50;
        }

        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 15px;
        }

        .avatar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s;
        }

        .avatar-item:hover {
            transform: translateY(-3px);
        }

        .avatar-img-container {
            position: relative;
            width: 60px;
            height: 60px;
            margin-bottom: 8px;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .avatar-badge {
            position: absolute;
            bottom: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            border: 2px solid white;
        }

        .avatar-badge.admin {
            background: #27ae60;
        }

        .avatar-name {
            font-size: 12px;
            text-align: center;
            max-width: 80px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 0;
            color: #95a5a6;
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        .agency-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            overflow-x: auto;
        }

        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 600;

            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-btn:hover:not(.active) {
            color: #34495e;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--secondary-color);
        }

        .card-header h3 {
            margin: 0;
            font-size: 18px;
            color: #2c3e50;
        }

        .count-badge {
            background: #ecf0f1;
            color: #7f8c8d;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--secondary-color);
        }


        .table-section {
            width: 100%;
            border-collapse: collapse;
            background: var(--secondary-color);
        }

        .data-table th {
            /* text-align: left; */
            padding: 12px 15px;
            background: var(--secondary-color);

            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-info strong {
            font-size: 14px;
        }

        .user-info small {
            font-size: 11px;
            color: #95a5a6;
        }

        .role-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .role-badge.owner {
            background: #9b59b6;
        }

        .role-badge.admin {
            background: #27ae60;
        }

        .btn-action {
            padding: 5px 10px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-action:hover {
            background: #2980b9;
        }

        .empty-table {
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #95a5a6;
        }

        .empty-table i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .empty-table p {
            margin: 0;
            font-size: 14px;
        }

        .user-avatar,
        .supporter-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .supporters-avatars {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }


        .pagination-wrapper {
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            border-top: 1px solid #eee;
            background: var(--secondary-color);
        }



        .target-card-section-1 {
            /* display: inline-flex; */
            width: 100%;
            padding-top: 26px;
            margin-bottom: 35px;

        }

        .card-target-filter {
            display: inline;
            width: 34%;
            left: 33px;
            position: absolute;
        }

        .card-target-filter .form-group {
            margin-bottom: 16px;
            right: 20px;
            position: relative;
            top: 10px;
        }

        .card-target-filter button {
            position: relative;
            left: -49px;
            bottom: -29px;
        }

        .card-target-filter-phone {
            width: 51%;
            margin-bottom: 27px;
            position: relative;
        }

        .rtl .card-target-filter-phone .form-group {
            margin-bottom: 16px;
            right: 20px;
            position: relative;
            top: 10px;
        }

        .ltr .card-target-filter-phone .form-group {
            margin-bottom: 16px;
            left: 20px;
            position: relative;
            top: 10px;
        }

        .card-target-filter-phone button {
            position: relative;
            left: -49px;
            bottom: -29px;
        }

        .target-card-stat {
            width: 50%;
        }

        .filter-form {
            border-radius: 13px;
            height: 165px;

        }

        .card-target-filter-phone {
            /* display: none; */
        }

        .card-target-filter {
            display: none;
        }

        @media (max-width: 768px) {
            .stats-row {
                flex-direction: column;
                width: 108%;

            }

            .avatar-grid {
                grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
            }

            .target-card-section-1 {
                display: grid;
                width: 100%;
                padding-top: 26px;
                margin-bottom: 35px;
            }


            .stat-icon {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 118px;
                color: white;
                font-size: 20px;
                margin-bottom: 6px;

            }


            .target-card-stat {
                width: 92%;

            }

            .card-target-filter {
                display: none;
            }

            .card-target-filter-phone {
                display: block;
                width: 100%;
                left: 0px;
                position: relative;
                margin-bottom: 31px;

            }

            .card-target-filter-phone .col-md-7 {
                float: none;
            }

            .card-target-filter-phone .form-control {
                display: block;
                width: 89%;
                padding: 6px 12px;
                font-size: 14px;
                line-height: 1.42857143;
                color: var(--text-secondary-color) !important;
                background-color: #fff;
                background-image: none;
                border: 1px solid var(--primary-hover-alpha) !important;
                border-radius: 4px;
            }

            .card-target-filter-phone .filter-form {
                border-radius: 13px;
                height: 238px;
            }

            .card-target-filter-phone .align-items-end {
                display: grid;
            }

            .card-target-filter-phone button {
                left: -224px;

            }
        }
    </style>
</head>

<body>

    <div class="agency-profile-container">
        <!-- Header Section -->
        <div class="agency-header">
            <div class="agency-avatar" style="width: 183px !important;">
                <img src="{{ @$imageUrl ?? asset('images/icon-agency.jpg') }}" alt="Agency Logo" class="logo-img">
            </div>
            <div class="agency-info">
                <h1 class="agency-name">{{ @$agency?->name ?? ''}}</h1>
                <div class="agency-meta">
                    <div class="meta-item">
                        <span class="meta-label">{{__('dashboard.agency_id')}}:</span>
                        <span class="meta-value">{{ $agency->id }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">{{ __("Phone") }}:</span>
                        <span class="meta-value" style="direction: ltr; display: inline-block;">
                            {{ ($agency->phone_code ?? '') . ($agency->phone ?? '') ?: 'N/A' }}
                        </span>
                    </div>
                </div>
                <div class="agency-stats">
                <div class="stat-card">
                        <div class="stat-value">{{ truncateAndTrim(@$agency->salary ?? 0) }}</div>
                        <div class="stat-label">{{__("salary")}}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ truncateAndTrim(@$sumTargets ?? 0) }}</div>
                        <div class="stat-label">{{__("diamonds")}}</div>
                    </div>

                </div>
            </div>
            <a class="btn-back" href="{{ route('bd.agencies.index') }}">
                <i class="fas fa-arrow-left"></i> {{__("Go Back")}}
            </a>
        </div>

        <div class="agency-header">


            <div class="agency-avatar" style="border-radius: 50%;">
                @php
                $url =  url($prefix ."/users/profile/{$agency?->owner?->id}");
                @endphp
            <a href="{{ $url }}">
                <img src="{{ getImagePath($agency?->owner?->profile?->avatar) ?? asset('images/businessman-icon.jpg') }}" class="logo-img">
             </a>
            </div>
            <div class="agency-info">
            <a href="{{ $url }}">
                <h1 class="agency-name">{{ $agency?->owner?->name ?? 'N/A'}}</h1>
                <div class="agency-meta">
                    <div class="meta-item">
                        <span class="meta-label">{{__('UUID')}}:</span>

                        <span class="meta-value">{{ @$agency?->owner?->uuid ?? 'N/A' }}</span>

                    </div>
                    <!-- <div class="meta-item">
                        <span class="meta-label">{{__('dashboard.id')}}:</span>
                        <span class="meta-value">{{ @$agency?->owner?->id ?? 'N/A' }}</span>
                    </div> -->
                </div>
                </a>
            </div>


        </div>

        <div class="agency-header">

           <h4> BD : </h4>
            <div class="agency-avatar" style="border-radius: 50%;">
                @php
                $url =  url($prefix ."/user-Bds/{$agency?->bd?->id}");
                @endphp
            <a href="{{ $url }}">
                <img src="{{ getImagePath($agency?->bd?->avatar) ?? asset('images/businessman-icon.jpg') }}" class="logo-img">
             </a>
            </div>
            <div class="agency-info">
            <a href="{{ $url }}">
                <h1 class="agency-name">{{ $agency?->bd?->name ?? $agency?->bd?->username}}</h1>
                <div class="agency-meta">
                    <div class="meta-item">
                        <span class="meta-label">{{__('id')}}:</span>

                        <span class="meta-value">{{ @$agency?->bd?->id ?? 'N/A' }}</span>

                    </div>
                </div>
                </a>
            </div>
        </div>

        <!-- Notice Section -->
        @if(@$agency->notice)
            <div class="notice-section">
                <div class="notice-header">
                    <i class="fas fa-info-circle"></i>
                    <span>{{__("Notice")}}</span>
                </div>
                <div class="notice-content">
                    {{ @$agency->notice }}
                </div>
            </div>
        @endif

        <!-- Stars & Admins Section -->
        <div class="top-performers-section">
            <!-- Stars Section -->
            <div class="performers-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-star"></i>
                        {{ __('Agency Stars') }}
                    </h2>
                    <div class="section-badge">{{ $giftLog->count() ?? 0 }}</div>
                </div>

                @if($giftLog && $giftLog->count())
                    <div class="avatar-grid">
                        @foreach($giftLog as $log)
                            @php
                                    $user = $log->receiver;
                                    $path = $user->profile?->avatar ?? null;
                                    $defaultImage = asset("images/businessman-icon.jpg");
                                    $url = isImageExists(getImagePath($path)) ? getImagePath($path) : $defaultImage;
                                    $username = htmlspecialchars($user->name ?? 'Unknown');
                                    $userUrl = route('bd.user.profile', $user->id);
                                    $exp = number_format($log->exp);
                            @endphp

                            <a href="{{ $userUrl }}" class="avatar-item" title="{{ $username }} ({{ $exp }} EXP)">
                                <div class="avatar-img-container">
                                    <img src="{{ $url }}" alt="{{ $username }}" class="avatar-img">
                                    <div class="avatar-badge">{{ $exp }}</div>
                                </div>
                                <div class="avatar-name">{{ $username }}</div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>{{ __('No stars data available') }}</p>
                    </div>
                @endif
            </div>

            <!-- Admins Section -->
            <div class="performers-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-user-shield"></i>
                        {{ __('Agency Admins') }}
                    </h2>
                    <div class="section-badge">{{ $agency->admins->count() ?? 0 }}</div>
                </div>

                @if($agency->admins && $agency->admins->count())
                    <div class="avatar-grid">
                        @foreach($agency->admins as $admin)
                            @php
                                    $user = $admin->user;
                                    $path = $user->profile?->avatar ?? null;
                                    $defaultImage = asset("images/businessman-icon.jpg");
                                    $url = isImageExists(getImagePath($path)) ? getImagePath($path) : $defaultImage;
                                    $username = htmlspecialchars($user->name ?? 'Unknown');
                                    $userUrl = route('bd.user.profile', $user->id);
                            @endphp

                            <a href="{{ $userUrl }}" class="avatar-item" title="{{ $username }}">
                                <div class="avatar-img-container">
                                    <img src="{{ $url }}" alt="{{ $username }}" class="avatar-img">
                                    <div class="avatar-badge admin"><i class="fas fa-shield-alt"></i></div>
                                </div>
                                <div class="avatar-name">{{ $username }}</div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>{{ __('No admins found') }}</p>
                    </div>
                @endif
            </div>
        </div>
        @php
                $activeTab = request('tab', 'tab=targets');
        @endphp
        <!-- Navigation Tabs -->
        <div class="agency-tabs">
           @if (\Encore\Admin\Facades\Admin::user()->can('member-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
               <a href="?tab=members" class="tab-btn" data-target="members-tab">{{ __('Members') }}</a>
            @endif
           @if (\Encore\Admin\Facades\Admin::user()->can('charge-history-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
             <a href="?tab=charges" class="tab-btn" data-target="charges-tab">{{ __('Charge History') }}</a>
            @endif
            @if (\Encore\Admin\Facades\Admin::user()->can('salary-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))

             <a href="?tab=salary" class="tab-btn" data-target="salary-tab">{{ __('Salary') }}</a>
            @endif
            @if (\Encore\Admin\Facades\Admin::user()->can('join-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
             <a href="?tab=requests" class="tab-btn" data-target="requests-tab">{{ __('Join Requests') }}</a>
            @endif
            @if (\Encore\Admin\Facades\Admin::user()->can('target-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
             <a href="?tab=targets" class="tab-btn" data-target="targets-tab">{{ __('Targets') }}</a>
            @endif
        </div>
        <div id="tab-loading" style="
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            /* transform: translate(-50%, -50%); */
            background: var(--primary-color);
            color: var(--text-primary-color);
            z-index: 9999;
            padding: 30px 40px;
            border-radius: 10px;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        ">
            {{ __('Loading...') }}
        </div>

        @if (\Encore\Admin\Facades\Admin::user()->can('member-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))

<div class="tab-content active" id="members-tab">

        <div class="card">
            <div class="card-header">
                <h3>{{ __('Agency Members') }}</h3>
                <span class="badge count-badge">{{ optional($members)->total() ?? 0 }}</span>
            </div>
            <div class="card">
            <div class="card-body">
                <form action="{{ url('bd/agencies/profile/' . $agency->id) }}" class="form-horizontal member-form" method="GET" pjax-container>
                    <input type="hidden" name="tab" value="members">
                    <input type="hidden" name="members_page" value="{{ request()->get('members_page', 1) }}">

                    <div class="row mb-4" style="align-items: flex-end;">
                        <!-- From Date -->
                        <div class="col-md-4">
                            <div class="card shadow-sm border">
                                <div class="card-body p-3">
                                    <label for="from_date" class="form-label fw-bold">
                                        <i class="fa fa-calendar me-1"></i> {{ __('UUID') }}
                                    </label>
                                    <input type="text"
                                        class="form-control"
                                        id="from_date"
                                        name="uuid"
                                        value="{{ request('uuid') }}"
                                        placeholder="{{ __('Enter UUID') }}">
                                </div>
                            </div>
                        </div>


                        <!-- Buttons -->
                        <div class="col-md-4 d-flex align-items-end justify-content-end" style="gap: 8px;">
                            <button type="submit" class="btn btn-info btn-sm me-2">
                                <i class="fa fa-search"></i> {{__('Search')}}
                            </button>
                            <a href="{{ url('bd/agencies/profile/' . $agency->id. '?'.'tab=members' ) }}" class="btn btn-default btn-sm">
                                <i class="fa fa-undo"></i> {{__('Reset')}}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
            @if($members && $members->count())
                <div class="table-responsive">


                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Member') }}</th>
                                <th>{{ __('Reals') }}</th>
                                <th>{{ __('Moments') }}</th>
                                <th>{{ __('Live Hours') }}</th>
                                <th>{{ __('Monthly DI') }}</th>
                                <th>{{ __('Salary') }}</th>
                                <th>{{ __('Role') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $index => $member)
                                @php
                                    $isAdmin = \App\Models\AgencyUserJob::where('user_id', $member->id)
                                        ->where('agency_id', $member->agency_id)
                                        ->where('type', 'requestManger')
                                        ->exists();
                                    $isOwner = \App\Models\Agency::where('app_owner_id', $member->id)
                                        ->where('id', $member->agency_id)
                                        ->exists();
                                    $showUrl = $member ? url($prefix."/users/{$member->id}") : "#";
                                    $moment = App\Helpers\Common::getUserMediaStats($member->id, 'moment',$member->agency_id) ?? [];
                                    $reel = App\Helpers\Common::getUserMediaStats($member->id, 'reel',$member->agency_id) ?? [];

                                                $momentUpload = $moment['upload'] ?? '0/0';
                                                $momentLikes = $moment['likes'] ?? '0/0';
                                                $momentComments = $moment['comments'] ?? '0/0';

                                                $reelUpload = $reel['upload'] ?? '0/0';
                                                $reelLikes = $reel['likes'] ?? '0/0';
                                                $reelComments = $reel['comments'] ?? '0/0';

                                @endphp

                                <tr>
                                    <td>{{ $index + 1 + (($members->currentPage() - 1) * $members->perPage()) }}</td>
                                    <td class="user-cell">
                                        <div class="user-avatar">
                                            <a href='{{$showUrl}}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                                            <img src="{{ getImagePath(@$member->profile->avatar) }}"
                                                alt="{{ $member->name }}">
                                        </div>
                                        <div class="user-info">
                                            <strong>{{ @$member->name ?? '' }}</strong>
                                            <small>UID: {{ @$member->uuid ?? '' }}</small>
                                        </div>
                                    </td>
                                                <td>
                                                    <div style="line-height: 1.6;">
                                                        <ul style="margin-left: 8px; width: 141px;">
                                                            <li><b>{{ __('Uploads:') }}</b> {{ $reelUpload }}</li>
                                                            <li><b>{{ __('Likes:') }}</b> {{ $reelLikes }}</li>
                                                            <li><b>{{ __('Comments:') }}</b> {{ $reelComments }}</li>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="line-height: 1.6;">
                                                        <ul style="margin-left: 8px; width: 141px;">
                                                            <li><b>{{ __('Uploads:') }}</b> {{ $momentUpload }}</li>
                                                            <li><b>{{ __('Likes:') }}</b> {{ $momentLikes }}</li>
                                                            <li><b>{{ __('Comments:') }}</b> {{ $momentComments }}</li>
                                                        </ul>
                                                    </div>
                                                </td>
                                    <td>{{ $member->getLiveTimeThisMonth() }}</td>
                                    <td>{{ $member->monthly_diamond_received ?? 0 }}</td>
                                    <td>{{ $member->salary_by_agency ?? 0 }}</td>
                                    <td>
                                        @if($isOwner)
                                            <span class="role-badge owner">{{ __('Owner') }}</span>
                                        @elseif($isAdmin)
                                            <!-- <span class="role-badge admin">{{ __('Admin') }}</span> -->
                                            @if (\Encore\Admin\Facades\Admin::user()->can('remove-admin-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                                <button class="btn-action remove-admin-btn btn-danger" style="background-color: red;" data-id="{{ $member->id }}">
                                                    {{ __('remove_admin') }}
                                                </button>
                                            @endif
                                            @if (\Encore\Admin\Facades\Admin::user()->can('kick-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                                <button class="btn-action kick-member-btn" data-id="{{ $member->id }}">
                                                    {{ __('kick') }}
                                                </button>
                                            @endif
                                        @else
                                                @if (\Encore\Admin\Facades\Admin::user()->can('make-admin-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                                        <button class="btn-action make-admin-btn" data-id="{{ $member->id }}">
                                                            {{ __('Make Admin') }}
                                                        </button>
                                                    @endif
                                        @if (\Encore\Admin\Facades\Admin::user()->can('kick-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                            <button class="btn-action kick-member-btn" data-id="{{ $member->id }}">
                                                {{ __('kick') }}
                                            </button>
                                        @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrapper">
                    {{ $members?->appends([
                                'charges_page' => $charges?->currentPage(),
                                'salaries_page' => $salaries?->currentPage(),
                                'join_page' => $agencyJoinRequests?->currentPage(),
                                'target_page' => $memberTargets?->currentPage(),
                            ])->links('vendor.pagination.default') }}
                </div>
            @else
                <div class="empty-table">
                    <i class="fas fa-users-slash"></i>
                    <p>{{ __('No members found') }}</p>
                </div>
            @endif
        </div>
    </div>
 @endif
        <!-- Charges Section -->
        <div class="tab-content" id="charges-tab">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title" style="text-align: left;">{{ __('Charge History') }}</h4>
                </div>

                <div class="table-responsive">
                    <div class="box-body ">
                        <table class="data-table" id="charge">
                            <thead>
                                <tr>
                                <th>#</th>
                                <th>{{ __('receiver') }}</th>
                                <th>{{ __('coins') }}</th>
                                <th>{{ __('USD') }}</th>
                                <th>{{ __('Created at') }}</th>
                                </tr>
                            </thead>


                            @if($charges && $charges->count())
                                    <tbody style="color: rgb(208, 115, 43);">
                                    @foreach($charges as $index => $charge)
                                                @php
                                                    $receiver = \App\Helpers\Common::getReceiverInfo($charge);
                                                    $name = $receiver['name'] ?? '-';
                                                    $uid = $receiver['uuid'] ?? '-';
                                                    $image = getImagePath( $receiver['image']) ?? asset('default-user.png');
                                                @endphp
                                                <tr>
                                                    <td>{{ $charge->id }}</td>
                                                    <td>
                                                        <img src="{{ $image }}" width="30" height="30"
                                                            style="object-fit: cover; border-radius: 50%; margin-right: 10px;">
                                                        {{ $name }} ({{ $uid }})
                                                    </td>
                                                    <td>{{ $charge->amount }} </td>
                                                    <td>{{ $charge->usd }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($charge->created_at)->format('Y-m-d H:i') }}</td>
                                                </tr>
                                            @endforeach
                                    </tbody>
                            @endif
                        </table>
                    </div>
                </div>

                <div class="pagination-wrapper">
                    {{ $members?->appends([
                                'charges_page' => $charges?->currentPage(),
                                'salaries_page' => $salaries?->currentPage(),
                                'join_page' => $agencyJoinRequests?->currentPage(),
                                'target_page' => $memberTargets?->currentPage(),
                            ])->links('vendor.pagination.default') }}
                </div>


            </div>

        </div>

        <!-- salary Section -->
        <div class="tab-content" id="salary-tab">
        <div class="target-card-stat">
                            <!-- <div class="stats-row">
                                <div class="stat-card">
                                    <div class="stat-icon bg-blue">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-value">{{ $sumTargets }}</div>
                                        <div class="stat-label">{{ __('Target') }}</div>
                                    </div>
                                </div>


                            </div> -->
                        </div>
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title" style="text-align: left;">{{ __('salary') }}</h4>
                </div>

                <div class="table-responsive">
                    <div class="box-body ">
                        <table class="data-table" id="salary">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('salary') }}</th>
                                    <th>{{ __('Target') }}</th>
                                    <th>{{ __('month') }}</th>
                                    <th>{{ __('year') }}</th>


                                </tr>
                            </thead>
                            @if($salaries && $salaries->count())
                                <tbody style="color: rgb(208, 115, 43);">
                                    @foreach($salaries as $index => $salary)
                                        <tr>
                                            <td>{{ $index + 1 + (($salaries->currentPage() - 1) * $salaries->perPage()) }}</td>
                                            <td>{{ @$salary->sallary - $salary->cut_amount }}</td>
                                            <td>{{ @$sumTargets }}</td>
                                            <td>{{ @$salary->month ?? '' }}</td>
                                            <td>{{ @$salary->year ?? '' }}</td>


                                        </tr>
                                    @endforeach
                                </tbody>
                            @endif
                        </table>

                        @if($salaries)
                                                <div class="pagination-container">
                                                    {{ $salaries->appends([
                                                            'charges_page' => $charges?->currentPage(),
                                                            'join_page' => $agencyJoinRequests?->currentPage(),
                                                            'members_page' => $members?->currentPage(),
                                                            'target_page' => $memberTargets?->currentPage(),
                                                        ])->links('vendor.pagination.bootstrap-4') }}
                                                </div>
                        @endif
                    </div>
                </div>



            </div>
        </div>

        <div class="tab-content" id="requests-tab">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title text-left">{{ __('Agency join request') }}</h4>
                </div>
                <div class="table-responsive">
                    <div class="box-body ">
                        <table class="data-table" id="join">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('WhatsApp') }}</th>
                                    <th>{{ __('Country') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>

                            @if($agencyJoinRequests && $agencyJoinRequests->count())
                                <tbody style="color: rgb(208, 115, 43);">
                                    @foreach($agencyJoinRequests as $index => $agencyJoinRequest)
                                        @php
                                                    $user = $agencyJoinRequest->user;
                                                    $name = $user->name ?? '-';
                                                    $uid = $user->uuid ?? '-';
                                                    $avatarPath = $user->profile?->avatar;
                                                    $defaultImage = asset("images/businessman-icon.jpg");
                                                    $avatarUrl = getImagePath($avatarPath) ?? $defaultImage;
                                                    if (!isImageExists($avatarUrl)) {
                                                        $avatarUrl = $defaultImage;
                                                    }
                                                    $image = handleShowImageWithTypes($user->id, $avatarUrl, 40, 40);

                                                    $iconUrl = asset('images/whatsapp.png');
                                                    $country = $user->country;
                                                    $countryName = app()->getLocale() == 'ar' ? $country?->name : $country?->e_name;
                                                    $countryFlag = getImagePath($country?->flag ?? '');
                                        @endphp

                                        <tr>
                                            <td>{{ $agencyJoinRequests->firstItem() + $index }}</td>

                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    {!! $image !!}
                                                    <div>
                                                        <strong>{{ $name }}</strong><br>
                                                        <span style=" font-size: smaller;">UID: {{ $uid }}</span>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <div style="display: flex; align-items: center;">
                                                    <span>{{ $agencyJoinRequest->whatsapp }}</span>
                                                    <img src="{{ $iconUrl }}" alt="WhatsApp" width="20" height="20"
                                                        style="margin-left: 5px; filter: invert(1);">
                                                </div>
                                            </td>

                                            <td>
                                                <div style="display: flex; flex-direction: column; align-items: start;">
                                                    <span>{{ $countryName }}</span>
                                                    @if($countryFlag)
                                                        <img src="{{ $countryFlag }}" alt="Flag" width="20" height="20"
                                                            style="margin-top: 3px; filter: invert(1);">
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <button class="btn btn-success btn-sm accept-btn"
                                                    data-id="{{ $agencyJoinRequest->id }}">
                                                    {{ __('Accept') }}
                                                </button>

                                                <button class="btn btn-danger btn-sm reject-btn"
                                                    data-id="{{ $agencyJoinRequest->id }}">
                                                    {{ __('Reject') }}
                                                </button>
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>

                <div class="pagination-container mt-3">
                    @if($agencyJoinRequests)
                                        {{ $agencyJoinRequests->appends([
                                                'members_page' => $members ? $members->currentPage() : 1,
                                                'salaries_page' => $salaries ? $salaries->currentPage() : 1,
                                                'charges_page' => $charges ? $charges->currentPage() : 1,
                                                'target_page' => $memberTargets ? $memberTargets->currentPage() : 1,
                                            ])->links('vendor.pagination.bootstrap-4') }}
                    @endif
                </div>

            </div>
        </div>

        <div class="tab-content" id="targets-tab">
            <div class="agency-card">
                <div class="card-header with-border">
                    <h3 class="card-title">
                        <i class="fas fa-bullseye"></i> {{ __('Agency Targets') }}
                    </h3>
                    <div class="card-tools">
                        <span class="badge bg-purple">
                            {{ $memberTargets ? $memberTargets->total() : 0 }}
                        </span>

                    </div>
                </div>

                <div class="card-body">
                    <div class="target-card-section-1">


                        <div class="card-target-filter-phone ">
                            <!-- Filter Form -->
                            <form method="GET" action="{{ url($prefix.'/agencies/profile/' . $agency->id) }}"
                                class="filter-form">
                                <div class="row">
                                    <input type="hidden" name="tab" value="targets">
                                    <div class="col-sm-12 col-md-7 ">
                                        <div class="form-group">
                                            <label for="month">{{ __('Month') }}</label>
                                            <select name="month" id="month" class="form-control">
                                                <option value="">All Months</option>
                                                @for($m = 1; $m <= 12; $m++)
                                                    <option value="{{ $m }}" {{ request('month', now()->month) == $m ? 'selected' : '' }}>
                                                        {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-7 col-sm-12">
                                        <div class="form-group">
                                            <label for="year">{{ __('Year') }}</label>
                                            <select name="year" id="year" class="form-control">
                                                <option value="">All Years</option>
                                                @for($y = now()->year; $y >= 2020; $y--)
                                                    <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>
                                                        {{ $y }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter"></i> {{ __('Apply') }}
                                        </button>
                                        @if(request()->has('month') || request()->has('year'))
                                            <a href="{{ url('bd/agencies/profile/' . $agency->id) }}"
                                                class="btn btn-outline-secondary ml-2" title="Reset filters">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="target-card-stat">
                            <div class="stats-row">
                                <div class="stat-card">
                                    <div class="stat-icon bg-blue">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-value">{{ $agencyTarget }}</div>
                                        <div class="stat-label">{{ __('Target') }}</div>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-icon bg-green">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-value">{{ $rate }}</div>
                                        <div class="stat-label">{{ __('Agency Rate') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <div class="card-target-filter">
                            <form method="GET" action="{{ url($prefix.'/agencies/profile/' . $agency->id) }}"
                                class="filter-form">
                                <div class="row">
                                    <input type="hidden" name="tab" value="targets">
                                    <div class="col-sm-12 col-md-7 ">
                                        <div class="form-group">
                                            <label for="month">{{ __('Month') }}</label>
                                            <select name="month" id="month" class="form-control">
                                                <option value="">All Months</option>
                                                @for($m = 1; $m <= 12; $m++)
                                                    <option value="{{ $m }}" {{ request('month', now()->month) == $m ? 'selected' : '' }}>
                                                        {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-7 col-sm-12">
                                        <div class="form-group">
                                            <label for="year">{{ __('Year') }}</label>
                                            <select name="year" id="year" class="form-control">
                                                <option value="">All Years</option>
                                                @for($y = now()->year; $y >= 2020; $y--)
                                                    <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>
                                                        {{ $y }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter"></i> {{ __('Apply') }}
                                        </button>
                                        @if(request()->has('month') || request()->has('year'))
                                            <a href="{{ url('bd/agencies/profile/' . $agency->id) }}"
                                                class="btn btn-outline-secondary ml-2" title="Reset filters">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>



                    <!-- Stars Section -->
                    <div class="section-box">
                        <div class="section-header">
                            <h4>
                                <i class="fas fa-star text-yellow"></i>
                                {{ __('Agency Stars') }}
                            </h4>
                        </div>

                        @if($stars && $stars->count())
                            <div class="avatar-grid">
                                @foreach($stars as $log)
                                    @php
                                            $user = $log->receiver;
                                            $path = $user->profile?->avatar ?? null;
                                            $defaultImage = asset("images/businessman-icon.jpg");
                                            $url = isImageExists(getImagePath($path)) ? getImagePath($path) : $defaultImage;
                                            $username = htmlspecialchars($user->name ?? 'Unknown');
                                            $userUrl = route('bd.user.profile', $user->id);
                                            $exp = number_format($log->exp);
                                    @endphp

                                    <a href="{{ $userUrl }}" class="avatar-item" title="{{ $username }} ({{ $exp }} EXP)">
                                        <div class="avatar-img-container">
                                            <img src="{{ $url }}" alt="{{ $username }}" class="avatar-img">
                                            <div class="avatar-badge">{{ $exp }}</div>
                                        </div>
                                        <div class="avatar-name">{{ $username }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                <p>{{ __('No stars data available') }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Heroes Section -->
                    <div class="section-box">
                        <div class="section-header">
                            <h4>
                                <i class="fas fa-user-shield text-red"></i>
                                {{ __('Agency Heroes') }}
                            </h4>
                        </div>

                        @if($heroes && $heroes->count())
                            <div class="avatar-grid">
                                @foreach($heroes as $log)
                                    @php
                                            $user = $log->sender;
                                            $path = $user->profile?->avatar ?? null;
                                            $defaultImage = asset("images/businessman-icon.jpg");
                                            $url = isImageExists(getImagePath($path)) ? getImagePath($path) : $defaultImage;
                                            $username = htmlspecialchars($user->name ?? 'Unknown');
                                            $userUrl = route('bd.user.profile', $user->id);
                                            $exp = number_format($log->exp);
                                    @endphp

                                    <a href="{{ $userUrl }}" class="avatar-item" title="{{ $username }} ({{ $exp }} EXP)">
                                        <div class="avatar-img-container">
                                            <img src="{{ $url }}" alt="{{ $username }}" class="avatar-img">
                                            <div class="avatar-badge">{{ $exp }}</div>
                                        </div>
                                        <div class="avatar-name">{{ $username }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                <p>{{ __('No heroes available') }}</p>
                            </div>
                        @endif
                    </div>
                    <br>

                    <div class="tab-content active" id="members-tab">


                        <div class="table-section card">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="25%">{{ __('User') }}</th>
                                            <th width="12%">{{ __('Diamonds') }}</th>
                                            <th width="18%">{{ __('Remaining') }}</th>
                                            <th width="12%">{{ __('Days') }}</th>
                                            <th width="12%">{{ __('Hours') }}</th>
                                            <th>{{ __('Moments') }}</th>
                                            <th>{{ __('Reels') }}</th>
                                            <th width="16%">{{ __('Supporters') }}</th>
                                        </tr>
                                    </thead>

                                    @if($memberTargets && $memberTargets->count())
                                        <tbody>
                                            @foreach($memberTargets as $index => $memberTarget)
                                                @php
                                                        $name = $memberTarget->name ?? '-';
                                                        $uid = $memberTarget->uuid ?? '-';
                                                        $avatarPath = $memberTarget->profile?->avatar;
                                                        $defaultImage = asset("images/businessman-icon.jpg");
                                                        $avatarUrl = getImagePath($avatarPath) ?? $defaultImage;
                                                        if (!isImageExists($avatarUrl)) {
                                                            $avatarUrl = $defaultImage;
                                                        }
                                                        $month = request('month') ?? now()->month;
                                                        $year = request('year') ?? now()->year;

                                                        $giftLogs = \App\Models\GiftLog::where('agency_id', $memberTarget->agency_id)
                                                            ->where('receiver_id', $memberTarget->id)
                                                            ->whereHas('sender')
                                                            ->with('sender.profile') // assuming sender has a 'profile' with 'avatar'
                                                            ->whereYear('created_at', $year)
                                                            ->whereMonth('created_at', $month)
                                                            ->selectRaw("sum(giftPrice) as exp, sender_id")
                                                            ->groupBy('sender_id')
                                                            ->orderByRaw("exp desc")
                                                            ->limit(3)
                                                            ->get()
                                                            ->reject(fn($q) => $q->exp == 0);

                                                        $memberTarget->topSupporters = $giftLogs;

                                                        $moment = App\Helpers\Common::getUserMediaStats($memberTarget->id, 'moment', $memberTarget->agency_id) ?? [];
                                                        $reel = App\Helpers\Common::getUserMediaStats($memberTarget->id, 'reel', $memberTarget->agency_id) ?? [];

                                                        $momentUpload = $moment['upload'] ?? '0/0';
                                                        $momentLikes = $moment['likes'] ?? '0/0';
                                                        $momentComments = $moment['comments'] ?? '0/0';

                                                        $reelUpload = $reel['upload'] ?? '0/0';
                                                        $reelLikes = $reel['likes'] ?? '0/0';
                                                        $reelComments = $reel['comments'] ?? '0/0';


                                                            $target = $memberTarget->targets->first();
                                                 @endphp

                                                <tr>
                                                    <td>{{ $memberTargets->firstItem() + $index }}</td>
                                                    <td>
                                                        <div class="user-info-cell">
                                                            <img src="{{ $avatarUrl }}" class="user-avatar" alt="{{ $name }}">
                                                            <div>
                                                                <div class="user-name">{{ $name }}</div>
                                                                <div class="user-uuid">{{ $uid }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="number-badge">
                                                            {{ $target->user_diamonds ?? 0 }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="number-badge warning">
                                                            {{ $target->next_diamond ?? 0 }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $target->user_hours ?? 0 }}</td>
                                                    <td>{{ $target->user_days ?? 0 }}</td>
                                                    <td>
                                                            <div style="line-height: 1.6;">
                                                                <ul style="margin-left: 8px; width: 141px;">
                                                                    <li><b>{{ __('Uploads:') }}</b> {{ $momentUpload }}</li>
                                                                    <li><b>{{ __('Likes:') }}</b> {{ $momentLikes }}</li>
                                                                    <li><b>{{ __('Comments:') }}</b> {{ $momentComments }}</li>
                                                                </ul>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div style="line-height: 1.6;">
                                                                <ul style="margin-left: 8px; width: 141px;">
                                                                    <li><b>{{ __('Uploads:') }}</b> {{ $reelUpload }}</li>
                                                                    <li><b>{{ __('Likes:') }}</b> {{ $reelLikes }}</li>
                                                                    <li><b>{{ __('Comments:') }}</b> {{ $reelComments }}</li>
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    <td>
                                                        <div class="supporters-avatars">
                                                            @foreach($memberTarget->topSupporters ?? [] as $supporter)
                                                                @php
                                                                        $sender = $supporter->sender;
                                                                        $supporterAvatar = $sender->profile->avatar ?? null;
                                                                        $supporterUrl = getImagePath($supporterAvatar) ?? $defaultImage;
                                                                        if (!isImageExists($supporterUrl)) {
                                                                            $supporterUrl = $defaultImage;
                                                                        }
                                                                @endphp
                                                                <img src="{{ $supporterUrl }}" class="supporter-avatar"
                                                                    title="{{ $sender->name ?? '' }}" alt="Supporter">
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    @endif
                                </table>

                            </div>

                            @if($memberTargets && $memberTargets->isEmpty())
                                <div class="empty-table">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <p>{{ __('No target data available') }}</p>
                                </div>
                            @endif

                            @if($memberTargets)
                                <div class="pagination-wrapper">
                                    {{ $memberTargets->appends([
                                            'members_page' => $members?->currentPage() ?? 1,
                                            'salaries_page' => $salaries?->currentPage() ?? 1,
                                            'charges_page' => $charges?->currentPage() ?? 1,
                                            'join_page' => $agencyJoinRequests?->currentPage() ?? 1,
                                            'month' => request('month'),
                                            'year' => request('year'),
                                        ])->links('vendor.pagination.bootstrap-4') }}
                                </div>
                            @endif

                        </div>


                    </div>
                </div>




            </div>

            <!-- jQuery -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

            <!-- SweetAlert2 -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>


            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const urlParams = new URLSearchParams(window.location.search);
                    const selectedTab = urlParams.get('tab') || 'members';

                    const allTabs = document.querySelectorAll('.tab-btn');
                    const allTabContents = document.querySelectorAll('[id$="-tab"]');

                    let targetElement = null;

                    allTabs.forEach(tab => {
                        const target = tab.getAttribute('data-target');
                        const content = document.getElementById(target);

                        if (target && target.startsWith(selectedTab)) {
                            tab.classList.add('active');
                            if (content) { content.style.display = 'block'; content.classList.add('active'); }
                        } else {
                            tab.classList.remove('active');
                            if (content) { content.style.display = 'none'; content.classList.remove('active'); }
                        }

                        tab.addEventListener('click', function (e) {
                            e.preventDefault();
                            allTabs.forEach(t => t.classList.remove('active'));
                            this.classList.add('active');
                            allTabs.forEach(t => {
                                const panelId = t.getAttribute('data-target');
                                const panel = document.getElementById(panelId);
                                if (!panel) return;
                                if (panelId === target) { panel.style.display = 'block'; panel.classList.add('active'); }
                                else { panel.style.display = 'none'; panel.classList.remove('active'); }
                            });
                            const url = new URL(window.location.href);
                            const tabParam = new URLSearchParams(this.getAttribute('href').replace('?', ''));
                            url.searchParams.set('tab', tabParam.get('tab'));
                            window.history.replaceState({}, '', url.toString());
                        });
                    });
                });







                $(document).ready(function () {
                    console.log("Document ready");

                    function showLoader() {
                        console.log("Showing loader");
                        Swal.fire({
                            title: 'Loading...',  // تغيير النص ليوضح الرسالة
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    }

                    function showSuccess(message, callback = null) {
                        console.log("Showing success:", message);
                        Swal.fire({
                            icon: 'success',  // استبدال type بـ icon
                            title: message,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            console.log("Success confirmed");
                            if (callback) {
                                console.log("Running success callback");
                                callback();
                            }
                        });
                    }

                    function showError(message) {
                        console.log("Showing error:", message);
                        Swal.fire({
                            icon: 'error',  // استبدال type بـ icon
                            title: message,
                            confirmButtonText: 'OK'
                        });
                    }

                    function confirmAction(message, onConfirm) {
                        console.log("Confirm action:", message);
                        Swal.fire({
                            title: message,
                            icon: 'question',  // استبدال type بـ icon
                            showCancelButton: true,
                            confirmButtonText: 'Yes',
                            cancelButtonText: 'Cancel'
                        }).then(result => {
                            console.log("Confirmation result:", result);
                            console.log("isConfirmed:", result.isConfirmed);

                            if (result.value) {
                                console.log("User confirmed action");
                                onConfirm();
                            } else {
                                console.log("User cancelled action");
                            }
                        });
                    }


                    // قبول الطلب
                    $('.accept-btn').click(function () {
                        const id = $(this).data('id');
                        console.log("Accept clicked, ID:", id);
                        confirmAction('{{ __("are_you_sure_accept") }}', () => {
                            showLoader();
                            $.post(`/admin/agencies/accept_join/${id}`, {
                                _token: '{{ csrf_token() }}'
                            }, function (response) {
                                Swal.close();
                                console.log("Accept response:", response);
                                if (response.status) {
                                    showSuccess(response.message, () => {
                                        const url = new URL(window.location.href);
                                        url.searchParams.set('tab', 'requests');
                                        window.location.href = url.toString();
                                    });
                                } else {
                                    showError(response.message);
                                }
                            }).fail(function (xhr) {
                                Swal.close();
                                console.error("Accept failed", xhr);
                                const res = xhr.responseJSON;
                                showError(res?.message ?? '{{ __("failed_accept_request") }}');
                            });
                        });
                    });

                    // رفض الطلب
                    $('.reject-btn').click(function () {
                        const id = $(this).data('id');
                        console.log("Reject clicked, ID:", id);
                        confirmAction('{{ __("are_you_sure_reject") }}', () => {
                            showLoader();
                            $.post(`/admin/agencies/reject_join/${id}`, {
                                _token: '{{ csrf_token() }}'
                            }, function (response) {
                                Swal.close();
                                console.log("Reject response:", response);
                                if (response.status) {
                                    showSuccess(response.message, () => {
                                        const url = new URL(window.location.href);
                                        url.searchParams.set('tab', 'requests');
                                        window.location.href = url.toString();
                                    });
                                } else {
                                    showError(response.message);
                                }
                            }).fail(function (xhr) {
                                Swal.close();
                                console.error("Reject failed", xhr);
                                const res = xhr.responseJSON;
                                showError(res?.message ?? '{{ __("failed_reject_request") }}');
                            });
                        });
                    });

                    // ترقية إلى Admin
                    $('.make-admin-btn').click(function () {
                        const id = $(this).data('id');
                        console.log("Make admin clicked, ID:", id);
                        confirmAction('{{ __("are_you_sure_make_admin") }}', () => {
                            showLoader();
                            $.post(`/admin/agencies/admin/${id}`, {
                                _token: '{{ csrf_token() }}'
                            }, function (response) {
                                Swal.close();
                                console.log("Make admin response:", response);
                                if (response.status) {
                                    showSuccess(response.message, () => {
                                        location.reload();
                                    });
                                } else {
                                    showError(response.message);
                                }
                            }).fail(function (xhr) {
                                Swal.close();
                                console.error("Make admin failed", xhr);
                                const res = xhr.responseJSON;
                                showError(res?.message ?? '{{ __("failed_make_admin") }}');
                            });
                        });
                    });

                    $('.remove-admin-btn').click(function () {
                        const id = $(this).data('id');
                        console.log("Make admin clicked, ID:", id);
                        confirmAction('{{ __("are_you_sure_remove_admin") }}', () => {
                            showLoader();
                            $.post(`/admin/agencies/admin/${id}`, {
                                _token: '{{ csrf_token() }}'
                            }, function (response) {
                                Swal.close();
                                console.log("Make admin response:", response);
                                if (response.status) {
                                    showSuccess(response.message, () => {
                                        location.reload();
                                    });
                                } else {
                                    showError(response.message);
                                }
                            }).fail(function (xhr) {
                                Swal.close();
                                console.error("Make admin failed", xhr);
                                const res = xhr.responseJSON;
                                showError(res?.message ?? '{{ __("failed_make_admin") }}');
                            });
                        });
                    });

                    $('.kick-member-btn').click(function () {
                        const id = $(this).data('id');
                        console.log("Make admin clicked, ID:", id);
                        confirmAction('{{ __("are_you_sure_remove_member") }}', () => {
                            showLoader();
                            $.post(`/admin/agencies/kick/${id}`, {
                                _token: '{{ csrf_token() }}'
                            }, function (response) {
                                Swal.close();
                                console.log("Make admin response:", response);
                                if (response.status) {
                                    showSuccess(response.message, () => {
                                        location.reload();
                                    });
                                } else {
                                    showError(response.message);
                                }
                            }).fail(function (xhr) {
                                Swal.close();
                                console.error("Make admin failed", xhr);
                                const res = xhr.responseJSON;
                                showError(res?.message ?? '{{ __("failed_make_admin") }}');
                            });
                        });
                    });
                });

            </script>
