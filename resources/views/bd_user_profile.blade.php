@php use App\Helpers\Common;use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ admin_asset('vendor/fontawesome6/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ admin_asset('vendor/fonts/inter/inter.css') }}">

    {{-- Central design system: one source of truth for every color/surface.
         Rendered via $content->view() INSIDE the admin layout, so the tokens,
         jQuery, pjax and SweetAlert2 are already provided globally; the include
         keeps tokens resolving even if this doc is opened standalone. --}}
    @include('css.theme-tokens')

    <style>
        .filter-container {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            padding: 20px;
            margin-bottom: 24px;
        }

        .filter-content {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-actions {
            display: flex;
            gap: 12px;
            margin-inline-start: auto;
        }

        .form-floating {
            position: relative;
        }

        .form-select {
            height: 48px;
            border-radius: 8px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            padding: 12px 16px;
            font-size: 14px;
            background-color: var(--surface-raised);
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--accent-soft);
            background-color: var(--card-bg);
        }

        .form-label {
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .btn-filter {
            background-color: var(--primary-color);
            color: var(--on-accent);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            height: 48px;
        }

        .btn-filter:hover {
            background-color: var(--accent-strong);
            transform: translateY(-1px);
        }

        .btn-reset {
            background-color: var(--surface-raised);
            color: var(--text-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            height: 48px;
        }

        .btn-reset:hover {
            background-color: var(--surface-sunken);
            color: var(--danger);
            border-color: color-mix(in srgb, var(--danger) 35%, transparent);
        }

        @media (max-width: 768px) {
            .filter-content {
                flex-direction: column;
                gap: 16px;
            }

            .filter-group {
                width: 100%;
            }

            .filter-actions {
                width: 100%;
                justify-content: flex-end;
                margin-inline-start: 0;
            }
        }

        @media (max-width: 576px) {
            .filter-actions {
                flex-direction: column;
                gap: 12px;
            }

            .btn-filter, .btn-reset {
                width: 100%;
                justify-content: center;
            }
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-inline-end: 57px;
            color: var(--on-color);
            font-size: 20px;
            margin-bottom: 6px;
        }

        .stat-icon.bg-blue {
            background: var(--gradient-3);
        }

        .stat-icon.bg-green {
            background: var(--gradient-4);
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        .section-box {
            background: var(--surface-raised);
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
            color: var(--warning);
        }

        .section-header .text-red {
            color: var(--danger);
        }


        .number-badge {
            display: inline-block;
            padding: 4px 10px;
            background: var(--chip-neutral-bg);
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
        }

        .number-badge.warning {
            background: var(--chip-warning-bg);
            color: var(--chip-warning-fg);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 0;
            color: var(--empty-state-fg);
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
            color: var(--empty-state-fg);
            background: var(--card-bg);
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
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-primary);
        }

        .agency-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid var(--card-bg);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .agency-avatar .logo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .agency-info {
            flex: 1;
        }

        .agency-name {
            margin: 0 0 10px 0;
            color: var(--text-primary);
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
            color: var(--meta-label);
        }

        .meta-value {
            color: var(--meta-value);
        }

        .meta-uuid {
            color: var(--text-muted);
            font-size: 0.9em;
        }

        .agency-stats {
            display: flex;
            gap: 15px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            min-width: 200px;

        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--info);
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ltr .btn-back {
            position: absolute;
            top: 5px;
            inset-inline-end: 20px;
            background: var(--surface-sunken);
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .rtl .btn-back {
            position: absolute;
            top: 5px;
            inset-inline-end: 20px;
            background: var(--surface-sunken);
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-back:hover {
            background: var(--border);
            color: var(--text-primary);
        }

        .notice-section {
            background: var(--chip-warning-bg);
            border-inline-start: 4px solid var(--warning);
            padding: 15px;
            border-radius: 0 6px 6px 0;
            margin-bottom: 25px;
        }

        .notice-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: var(--warning);
            font-weight: 600;
        }

        .notice-content {
            color: var(--text-secondary);
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

        .performers-card {
            background: var(--surface-raised);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--profile-hairline);
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
            color: var(--text-primary);
        }

        .section-badge {
            background: var(--info);
            color: var(--on-color);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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
            border: 2px solid var(--card-bg);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .avatar-badge {
            position: absolute;
            bottom: -5px;
            inset-inline-end: -5px;
            background: var(--danger);
            color: var(--on-color);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            border: 2px solid var(--card-bg);
        }

        .avatar-badge.admin {
            background: var(--success);
        }

        .avatar-name {
            font-size: 12px;
            text-align: center;
            max-width: 80px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-scroll-container {
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .nav-pills {
            display: inline-flex;
            padding: 10px 0;
        }

        .nav-pills li {
            display: inline-block;
        }


        .nav-pills > li.active > a, .nav-pills > li.active > a:hover, .nav-pills > li.active > a:focus {
            border-top-color: var(--primary-color);
        }

        .nav-pills > li.active > a, .nav-pills > li.active > a:focus, .nav-pills > li.active > a:hover {
            color: var(--on-accent);
            background-color: var(--primary-color);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 0;
            color: var(--empty-state-fg);
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
            border-bottom: 1px solid var(--border);
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
            color: var(--text-primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-header h3 {
            margin: 0;
            font-size: 18px;
            color: var(--text-primary);
        }

        .count-badge {
            background: var(--chip-neutral-bg);
            color: var(--chip-neutral-fg);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
        }


        .table-section {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
        }

        .data-table th {
            text-align: start;
            padding: 12px 15px;
            background: var(--surface-raised);

            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--profile-hairline);
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background: var(--surface-raised);
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
            color: var(--text-muted);
        }

        .role-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            color: var(--on-color);
        }

        .role-badge.owner {
            background: var(--accent);
            color: var(--on-accent);
        }

        .role-badge.admin {
            background: var(--success);
        }

        .btn-action {
            padding: 5px 10px;
            background: var(--info);
            color: var(--on-color);
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-action:hover {
            background: color-mix(in srgb, var(--info) 82%, #000);
        }

        .empty-table {
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--empty-state-fg);
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

        .card-target-filter-phone .form-group {
            margin-bottom: 16px;
            right: 20px;
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
                margin-inline-end: 118px;
                color: var(--on-color);
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
                /* display: block; */
                width: 89%;
                padding: 6px 12px;
                font-size: 14px;
                line-height: 1.42857143;
                color: var(--text-secondary-color) !important;
                background-color: var(--input-bg);
                background-image: none;
                border: 1px solid var(--input-border) !important;
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

        .date-flex-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .date-flex-row span {
            font-weight: 600;
            white-space: nowrap;
        }

        .date-flex-row input[type="date"] {
            flex-basis: 0;
        }

        .diamond-summary-container {
            display: flex;
            justify-content: center;
            width: 100%;
            padding: 20px;
        }

        .diamond-summary-box {
            max-width: 600px;
            width: 100%;
            background: var(--gradient-1);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin: 0 auto; /* This also helps with centering */
        }

        .diamond-summary-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .diamond-title {
            font-size: 22px;
            font-weight: bold;
            color: rgba(255, 255, 255, .85);
            margin-bottom: 15px;
        }

        .diamond-count {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--on-color);
        }

        .diamond-count span {
            margin-inline-end: 10px;
        }

        .diamond-icon-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            padding: 8px;
            display: inline-flex;
        }

        .diamond-icon {
            width: 32px;
            height: 32px;
            filter: drop-shadow(0 0 3px rgba(255, 255, 255, 0.5));
        }

        .gift-log-form {
            background-color: transparent !important;
            filter: none !important;
        }

        .level-form {
            background-color: transparent !important;
            filter: none !important;
            padding: 10px;
        }

        .level-label {
            padding: 10px;
        }

        /* .rtl .gift-log-form {
            padding-right: 13%;
        } */
        .ltr .gift-log-form {
            padding-left: 13%;
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-top: 1px solid var(--card-border);
        }

        .card-header {
            border-bottom: 1px solid var(--card-border);
        }

        .card-title {
            color: var(--text-primary);
            font-weight: 500;
        }</style>

</head>
<body>

<div class="agency-profile-container">
     {{-- Error Messages --}}
                    @if ($errors->any())
                        <div class="alert alert-danger mx-3 mt-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($errors->has('msg'))
                        <div class="alert alert-danger mx-3 mt-3">
                            {{ $errors->first('msg') }}
                        </div>
                    @endif
    <!-- Header Section -->
    <div class="agency-header">
        <div class="agency-avatar">
            <img src="{{getImagePath( @$user->profile->avatar ) ?? asset("images/businessman-icon.jpg") }}" alt="Agency Logo" class="logo-img">
        </div>
        <div class="agency-info">
            <h1 class="agency-name">{{ @$user?->name ?? ''}}</h1>
            <div class="agency-meta">
                <div class="meta-item">
                    @if (@$user->uuid == @$user->original_uuid)
                        <span class="meta-label">{{ __("uuid") }}:</span>
                        <span class="meta-value">{{ @$user->original_uuid }}</span>
                    @else
                        <span class="meta-label">{{ __("uuid") }}:</span>
                        <span class="meta-value">{{ @$user->original_uuid }}</span><br>
                        <span class="meta-label">{{ __("special uuid") }}:</span>
                        <span class="meta-value">{{ @$user->uuid_v3 }}</span>
                    @endif
                </div>
                <div class="meta-item">
                    <span class="meta-label">{{__("Phone")}}:</span>
                    <span class="meta-value">{{ @$user->phone ?? 'N/A' }}</span>
                </div>

            </div>
            <div class="agency-stats">
                <div class="agency-meta">
                    <div class="meta-item">
                        <span class="meta-label">{{ __('Balance') }}:</span>
                        <span class="meta-value">{{ @$user->salary }}</span>

                    </div>
                    <div class="meta-item">
                        <span class="meta-label">{{__('level')}}:</span>
                        <span class="meta-value">
                                    <img src="{{ getImagePath(Common::level_center($user)['sender_img']) }}"
                                         style="height: 24px;">
                                </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">{{__('Receiver Level')}}:</span>
                        <span class="meta-value">
                                <img src="{{ getImagePath(Common::level_center($user)['receiver_img']) }}"
                                     style="height: 24px;">
                            </span>
                    </div>

                </div>

                <div class="agency-meta">
                    <div class="meta-item">
                        <span class="meta-label">{{ __('diamonds') }}:</span>
                        @php

                            $user_diamonds = (in_array($user->type_user, [0,3])) ? $user->exchange_diamonds :$user->monthly_diamond_received;

                        @endphp
                        <span class="meta-value">{{ @$user_diamonds }}</span>

                    </div>
                    <div class="meta-item">
                        <span class="meta-label">{{__('coins')}}:</span>
                        <span class="meta-value">{{@$user->di }}</span>
                    </div>


                </div>
            </div>
            <div class="agency-meta">
                <div class="meta-item">
                    <span class="meta-label">{{__('type')}}:</span>
                   
                    {!! @$user->userBadgeTop() !!}
                </div>

            </div>

            <div class="agency-meta">
                <div class="meta-item">
                    <span class="meta-label">{{__('badges')}}:</span>
                    {!! @$user->userBadge() !!}
                </div>

            </div>
        </div>
        <div class="card p-3 bg-danger-subtle">
            <div class="d-flex justify-content-between align-items-center">
            <a href="{{  route('bd.agencies.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> {{ __('Go Back') }}
            </a>
                     @if (\Encore\Admin\Facades\Admin::user()->can('edit-' . 'users') || \Encore\Admin\Facades\Admin::user()->can('*'))

                        <button type="submit" class="btn btn-danger edit_user_item_model_btn">
                            {{ __('edit') }}
                        </button>
                 @endif
            </div>
        </div>
    </div>


    @php
           $activeTab = request('tab', 'salary');

    @endphp
        <!-- Navigation Tabs -->
    <div class="agency-tabs">

          <a href="?tab=packs" class="tab-btn" data-target="packs-tab">{{ __('packs') }}</a>

        <a href="?tab=vips" class="tab-btn {{ $activeTab == 'vips' ? 'active' : '' }}" data-target="vips-tab">{{ __('vips') }}</a>

        @if (\Encore\Admin\Facades\Admin::user()->can('level-switch' . 'users') || \Encore\Admin\Facades\Admin::user()->can('*'))
            <a href="?tab=level" class="tab-btn" data-target="level-tab">{{ __('level') }}</a>
        @endif
        @if (\Encore\Admin\Facades\Admin::user()->can('salary-switch-' . 'users') || \Encore\Admin\Facades\Admin::user()->can('*'))
            <a href="?tab=salary" class="tab-btn {{ $activeTab == 'salary' ? 'active' : '' }}"
               data-target="salary-tab">{{ __('prof_reports') }}</a>
        @endif
        <a href="?tab=charge" class="tab-btn {{ $activeTab == 'charge' ? 'active' : '' }}"
           data-target="charge-tab">{{ __('Charge Reports') }}</a>

           <a href="?tab=gift-log" class="tab-btn {{ $activeTab == 'gift-log' ? 'active' : '' }}"
           data-target="gift-log-tab">{{ __('gifts') }}</a>
           <a href="?tab=user-agency" class="tab-btn {{ request('tab') == 'user-agency' ? 'active' : '' }}" data-target="user-agency-tab">{{ __('Agency join logs') }}</a>
           @if ($user->type_user != 1)
            <a href="?tab=user-coins" class="tab-btn {{ request('tab') == 'user-coins' ? 'active' : '' }}" data-target="user-coins-tab">{{ __('User Coins') }}</a>
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


    <!-- packs Section -->

    <div class="tab-content active" id="packs-tab">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title" style="text-align: start;">{{ __('pack') }}</h4>
            </div>
            <div class="box-body">
                <div class="nav-scroll-container">
                    <ul class="nav nav-pills">
                        @foreach($types as $id => $name)
                            @php
                             $defaultType = $types->keys()->first();
                                $selectedType = request()->get('type', $defaultType); // Default to 1
                            @endphp
                            <li class="{{ $selectedType == $id ? 'active' : '' }}">
                                <a href="{{ request()->fullUrlWithQuery(['type' => $id]) }}" class="charge_action">
                                    {{ __($name) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="table-responsive">
                <div class="box-body ">
                    <table class="table table-bordered table-hover align-middle data-table" id="pack">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('Admin') }}</th>
                            <th>{{ __('get type') }}</th>
                            <th>{{ __('type') }}</th>
                            <th>{{ __('img') }}</th>
                            <th>{{ __('expire') }}</th>
                            <!-- <th>{{ __('action') }}</th> -->

                        </tr>
                        </thead>
                        @if($packs && $packs->count())

                            <tbody style="color: var(--profile-muted);">
                            @foreach($packs as $index => $pack)
                               @php
                                    $path = @$pack->ware?->show_img ?? '';

                                    $admin = null;

                                    if ($pack->vip_user_id && optional($pack->userVip)->admin) {
                                        $admin = $pack->userVip->admin;
                                    } elseif ($pack->dash_user_id && optional($pack)->admin) {
                                        $admin = $pack->admin;
                                    }

                                    $image = optional($admin)->avatar ?? '';
                                    $defaultImage = asset("images/businessman-icon.jpg");
                                    $imagePath = getImagePath($image);
                                    $image = isImageExists($imagePath) ? $imagePath : $defaultImage;

                                    $nameRaw = optional($admin)->name;
                                    $name = is_array($nameRaw) ? reset($nameRaw) : (string) $nameRaw;

                                    $uid = optional($admin)->id ?? 0;
                                    $url = $admin ? '#' : '#';
                                @endphp
                                <tr>
                                    <td>{{ $packs->firstItem() + $index }}</td>
                                    <td>
                                        @if ($admin)
                                            <a href="{{ $url ?? '#' }}"
                                       style="display: inline-flex; align-items: center; text-decoration: none;">
                                        <img src="{{ $image }}" width="30" height="30"
                                             style="object-fit: cover; border-radius: 50%; margin-inline-end: 10px;">
                                        <span>{{ $name }} ({{ $uid }})</span>
                                    </a>
                                        @else

                                        @endif

                                </td>
                                    <td>{{ $pack->getTypeGet() }}</td>

                                    <td>{{ $pack->getType() }}</td>
                                    <td>
                                        <img src="{{ getImagePath(@$path) }}" width="30" height="30"
                                             style="object-fit: cover; border-radius: 50%; margin-inline-end: 10px;">

                                    </td>
                                    <td>{{ (!empty($pack->expire) && $pack->expire !== '0') ? \Carbon\Carbon::parse($pack->expire)->format('Y-m-d H:i:s') :'∞' }}</td>
                                    <!-- <td> -->
                                        <!-- <div class="d-flex">
                                            <button class="btn btn-falcon-info w-100 me-3 edit_item_model_btn"
                                                    data-id="{{ @$pack->id }}">
                                                {{ __('dashboard.free') }}
                                            </button>
                                            <!-- <button class="btn btn-danger delete-btn" data-id="{{ @$pack->id }}">
                                                {{ __('dashboard.delete') }}
                                            </button>
                                        </div> -->
                                    <!-- </td> -->
                                </tr>
                            @endforeach
                            </tbody>
                        @endif

                    </table>
                </div>
            </div>

            <div class="pagination-wrapper">
                {{ $packs?->appends([
                    'vip_page' => $userVips?->currentPage(),
                    'salary_page' => $salaries?->currentPage(),
                    'gift_page' => $giftSLogs?->currentPage(),
                ])->links('vendor.pagination.default') }}
            </div>


        </div>

    </div>


    <!-- vips Section -->
    <div class="tab-content" id="vips-tab" style="{{ $activeTab == 'vips' ? '' : 'display: none;' }}">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title" style="text-align: start;">{{ __('vips') }}</h4>
            </div>

            <div class="table-responsive">
                <div class="box-body ">
                    <table class="table table-bordered table-hover align-middle data-table" id="vip">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('level') }}</th>
                            <th>{{ __('expire') }}</th>
                            <th>{{ __('qty') }}</th>
                            <th>{{ __('total Price') }}</th>
                            <!-- <th>{{ __('action') }}</th> -->

                        </tr>
                        </thead>
                        @if($userVips && $userVips->count())
                            <tbody style="color: var(--profile-muted);">
                            @foreach($userVips as $index => $userVip)
                                <tr>
                                    <td>{{ $index + 1 + (($userVips->currentPage() - 1) * $userVips->perPage()) }}</td>
                                    <td>{{ $userVip->level }}</td>
                                    <td>{{ (!empty($userVip->expire) && $userVip->expire != '0') ? \Carbon\Carbon::parse($userVip->expire)->format('Y-m-d H:i:s') : '∞' }}</td>
                                    <td>{{ @$userVip->qty ?? 0 }}</td>
                                    <td>{{ @$userVip->total ?? 0 }}</td>
                                    <!-- <td>
                                        <div class="d-flex">

                                            <button class="btn btn-danger delete-vip-btn" data-id="{{ @$userVip->id }}">
                                                {{ __('dashboard.delete') }}
                                            </button>
                                        </div>
                                    </td> -->

                                </tr>
                            @endforeach
                            </tbody>
                        @endif
                    </table>

                    @if($userVips)
                        <div class="pagination-container">
                            {{ $userVips->appends([
                                'tab' => 'vips',
                                 'vip_page' => $userVips->currentPage(),
                            ])->links('vendor.pagination.bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </div>


        </div>
    </div>

    <div class="tab-content" id="salary-tab">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title" style="text-align: start;">{{ __('user wallet') }}</h4>
            </div>

            <div class="box-body p-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ url('bd/users/profile/' . $user->id) }}" class="form-horizontal gift-log-form" pjax-container="">
                            <input type="hidden" name="tab" value="salary">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="box-body">
                                        <div class="fields-group">

                                            <div class="form-group">
                                                <label class="col-sm-2 control-label">السنة</label>
                                                <div class="col-sm-8">
                                                    <div class="input-group input-group-sm">
                                                        <div class="input-group-addon">
                                                            <i class="fa fa-pencil"></i>
                                                        </div>
                                                        <input type="text" class="form-control year" placeholder="السنة" name="year"
                                                               value="{{ request('year') }}" style="text-align: end;">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-sm-2 control-label">الشهر</label>
                                                <div class="col-sm-8">
                                                    <div class="input-group input-group-sm">
                                                        <div class="input-group-addon">
                                                            <i class="fa fa-pencil"></i>
                                                        </div>
                                                        <input type="text" class="form-control month" placeholder="الشهر"
                                                               name="month" value="{{ request('month') }}"
                                                               style="text-align: end;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- /.box-body -->
                            <div class="box-footer">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="col-md-2"></div>
                                        <div class="col-md-8">
                                            <div class="btn-group pull-left">
                                                <button class="btn btn-info submit btn-sm">
                                                    <i class="fa fa-search"></i>&nbsp;&nbsp;{{__('Search')}}
                                                </button>
                                            </div>
                                            <div class="btn-group pull-left" style="margin-inline-start: 10px;">
                                                <a href="{{ url('bd/users/profile/' . $user->id. '?'.'tab=salary') }}"
                                                   class="btn btn-default btn-sm">
                                                    <i class="fa fa-undo"></i>&nbsp;&nbsp;{{__('Reset')}}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <div class="box-body ">
                        <table class="table table-bordered table-hover align-middle data-table" id="vip">
                            <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('agency') }}</th>
                                <th>{{ __('salary') }}</th>
                                <th>{{ __('Withdraw') }}</th>
                                <th>{{ __('net salary') }}</th>
                                <th>{{ __('days') }}</th>
                                <th>{{ __('hours') }}</th>
                                <th>{{ __('Moments') }}</th>
                                <th>{{ __('Reels') }}</th>
                                <th>{{ __('diamonds') }}</th>
                                <th>{{ __('date') }}</th>

                            </tr>
                            </thead>
                            @if($salaries && $salaries->count())
                                <tbody style="color: var(--profile-muted);">
                                @foreach($salaries as $index => $salary)

                                    @php
                                        $agency = $salary->agency;
                                        $name = $agency->name ?? '';

                                        $path = @$agency->img;
                                        $defaultImage = asset("images/icon-agency.jpg");
                                        $url = getImagePath($path) ?? $defaultImage;

                                        if (!isImageExists($url)) {
                                            $url = $defaultImage;
                                        }

                                        $image = handleShowImageWithTypes($user->id, $url, 40, 40);
                                        $profileUrl = route('bd.agency.profile', ['id' => @$agency->id ?? 0]);

                                        $extras = json_decode($salary->extras, true);
                                        $moment = $extras['moment'] ?? [];
                                        $reel = $extras['reel'] ?? [];

                                        $momentUpload = $moment['upload'] ?? '0/0';
                                        $momentLikes = $moment['likes'] ?? '0/0';
                                        $momentComments = $moment['comments'] ?? '0/0';

                                        $reelUpload = $reel['upload'] ?? '0/0';
                                        $reelLikes = $reel['likes'] ?? '0/0';
                                        $reelComments = $reel['comments'] ?? '0/0';


                                    @endphp

                                    <tr>
                                        <td>{{ $index + 1 + (($salaries->currentPage() - 1) * $salaries->perPage()) }}</td>
                                        <td>
                                            <a href="{{ $profileUrl }}" style="text-decoration: none; color: inherit;">
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    {!! $image !!}
                                                    <div style="display: flex; flex-direction: column;">
                                                    <span
                                                        style="text-decoration: underline; cursor: pointer;">{{ $name }}</span>
                                                        <span style="font-size: smaller;">ID: {{ @$agency->id ?? 0 }}</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </td>


                                        <td>{{truncateAndTrim($salary->sallary)}}</td>
                                        <td>{{ $salary->cut_amount}}</td>
                                        <td>{{ truncateAndTrim($salary->sallary - $salary->cut_amount) }}</td>
                                        <td>{{ $salary->days }}</td>
                                        <td>{{ $salary->hours }}</td>
                                        <td>
                                            <div style="line-height: 1.6;">
                                                <ul style="margin-inline-start: 8px;">
                                                    <li><b>{{ __('Uploads:') }}</b> {{ $momentUpload }}</li>
                                                    <li><b>{{ __('Likes:') }}</b> {{ $momentLikes }}</li>
                                                    <li><b>{{ __('Comments:') }}</b> {{ $momentComments }}</li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="line-height: 1.6;">
                                                <ul style="margin-inline-start: 8px;">
                                                    <li><b>{{ __('Uploads:') }}</b> {{ $reelUpload }}</li>
                                                    <li><b>{{ __('Likes:') }}</b> {{ $reelLikes }}</li>
                                                    <li><b>{{ __('Comments:') }}</b> {{ $reelComments }}</li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td>{{ $salary->achieved_diamond }}</td>
                                        <td>{{ $salary->month .'/'. $salary->year }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            @endif
                        </table>

                        @if($salaries)
                            <div class="pagination-container">
                                {{ $salaries->appends([
                                    'pack_page' => $packs?->currentPage(),
                                    'vip_page' => $userVips?->currentPage(),
                                    'gift_page' => $giftSLogs?->currentPage(),

                                ])->links('vendor.pagination.bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="tab-content" id="level-tab">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title text-left">{{ __('level') }}</h4>
            </div>
            <div class="box-body p-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="{{ url('/admin/edit-level') }}" id="user_level_update_form" method="POST"
                              enctype="multipart/form-data" class="level-form">
                            @csrf
                            <div class="row" style="justify-content:space-evenly">
                                <input type="hidden" name="id" class="item_id" value="{{ $user->id }}">
                                <div class=" col-lg-6 form-Roles mb-3">
                                    <label class="form-label level-label"> {{ __('Sender Level') }}</label>
                                    <input type="number" min="0" value="{{ $user->total_sender_level }}" class="form-control "
                                           id="total_sender_level" name="total_sender_level" >
                                </div>

                                <div class=" col-lg-6 form-Roles mb-3">
                                    <label class="form-label level-label"> {{ __('Received Level') }}</label>
                                    <input type="number" min="0" value="{{ $user->total_received_level }}" class="form-control "
                                           id="total_received_level" name="total_received_level" >
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" type="button"
                                        data-bs-dismiss="modal">{{ __('cancel') }} </button>
                                <button class="btn btn-primary " type="submit">{{ __('save') }} </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


<div class="tab-content" id="user-agency-tab" style="{{ request('tab') == 'user-agency' ? 'display: block;' : 'display: none;' }}">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title" style="text-align: start;">{{ __('Agency join logs') }}</h4>
        </div>
        <div class="box-body p-3">
            <div class="card mb-4">
                <div class="card-body">
                    <form action="{{ url('bd/users/profile/' . $user->id) }}" class="form-horizontal user-agency-form" method="GET" pjax-container>
                        <input type="hidden" name="tab" value="user-agency">

                        <input type="hidden" name="user_agency_page" value="{{ request()->get('user_agency_page', 1) }}">

                        <div class="row mb-3" style="align-items: flex-end;">
                            <!-- From Date -->
                            <div class="col-md-4">
                                <div class="date-flex-row">
                                    <i class="fa fa-calendar"></i>
                                    <span>{{ __('Join date') }}</span>
                                    <input type="date" class="form-control" id="from_date" name="join_date" value="{{ request('join_date') }}">
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="col-md-4 d-flex align-items-end justify-content-end" style="gap: 8px;">
                                <button type="submit" class="btn btn-info btn-sm me-2">
                                    <i class="fa fa-search"></i> {{__('Search')}}
                                </button>
                                <a href="{{ url('bd/users/profile/' . $user->id. '?tab=user-agency') }}" class="btn btn-default btn-sm">
                                    <i class="fa fa-undo"></i> {{__('Reset')}}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <div class="box-body ">
                    <table class="table table-bordered table-hover align-middle data-table" id="user-agency">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('agency') }}</th>
                            <th>{{ __('status') }}</th>
                            <th>{{ __('kicked By') }}</th>
                            <th>{{ __('kicked By status') }}</th>
                            <th>{{ __('Join date') }}</th>
                            <th>{{ __('Leave date') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                            @if($userJoinAgencies && $userJoinAgencies->count())
                                @foreach($userJoinAgencies as $index => $userJoinAgency)
                                    @php
                                        $kickedBy = null;
                                        $kickedByName = '';
                                        $kickedByUuid = '';
                                        $kickedByImage = '';
                                        $kickedByUrl = '';
                                        $status = '';
                                    @endphp

                                    @php
                                        $agency = $userJoinAgency->agency;
                                        $name = $agency->name ?? '';
                                        $path = @$agency->img;
                                        $defaultImage = asset("images/icon-agency.jpg");
                                        $url = getImagePath($path) ?? $defaultImage;
                                        if (!isImageExists($url)) {
                                            $url = $defaultImage;
                                        }
                                        $image = "<img src='" . e($url) . "' width='40' height='40' style='object-fit: cover; border-radius: 6px;'>";
                                        $profileUrl = route('bd.agency.profile', ['id' => @$agency->id ?? 0]);
                                    @endphp

                                    @php
                                       if ($userJoinAgency->status == 'kick off'){
                                           if ($userJoinAgency->kicked_by_app){
                                            $status = 'app';
                                            $kickedBy = $userJoinAgency['kickedByApp'];
                                            $kickedByName = $kickedBy->name ?? '';
                                            $kickedByUuid = $kickedBy->uuid ?? '';
                                            $kickedByPath = @$kickedBy->profile?->avatar;
                                            $defaultImage = asset("images/businessman-icon.jpg");
                                            $url = getImagePath($kickedByPath) ?? $defaultImage;
                                            if (!isImageExists($url)) {
                                                $url = $defaultImage;
                                            }
                                            $kickedByImage = "<img src='" . e($url) . "' width='40' height='40' style='object-fit: cover; border-radius: 6px;'>";
                                            $kickedByUrl = url("bd/users/profile/" . ($kickedBy->id) ?? 0);
                                        }

                                        if ($userJoinAgency->kicked_by_admin){
                                            $status = 'admin';
                                            $kickedBy = $userJoinAgency['kickedByAdmin'];
                                            $kickedByName = $kickedBy->name ?? '';
                                            $kickedByUuid = $kickedBy->id ?? '';
                                            $kickedByPath = @$kickedBy?->avatar;
                                            $defaultImage = asset("images/businessman-icon.jpg");
                                            $url = getImagePath($kickedByPath) ?? $defaultImage;
                                            if (!isImageExists($url)) {
                                                $url = $defaultImage;
                                            }
                                            $kickedByImage = "<img src='" . e($url) . "' width='40' height='40' style='object-fit: cover; border-radius: 6px;'>";
                                            $kickedByUrl = '#';
                                        }
                                    }
                                    @endphp

                                    <tr>
                                        <td>{{ $index + 1 + (($userJoinAgencies->currentPage() - 1) * $userJoinAgencies->perPage()) }}</td>
                                        <td>
                                            <a href="{{ $profileUrl }}" style="text-decoration: none; color: inherit;">
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    {!! $image !!}
                                                    <div style="display: flex; flex-direction: column;">
                                                    <span style="text-decoration: underline; cursor: pointer;">{{ $name }}</span>
                                                        <span style="font-size: smaller;">ID: {{ @$agency->id ?? 0 }}</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </td>
                                        <td>{{ $userJoinAgency->status }}</td>
                                        <td>
                                            @if(!empty($kickedBy) && !empty($kickedBy->id))
                                                <a href="{{ $kickedByUrl ?? '#' }}"
                                                   style="display: inline-flex; align-items: center; text-decoration: none;">
                                                    {!! $kickedByImage !!}
                                                    <span>{{ $kickedByName }} ({{ $kickedByUuid }})</span>
                                                </a>
                                            @endif
                                        </td>
                                        <td>{{ @$status }}</td>
                                        <td>{{ $userJoinAgency->join_date }}</td>
                                        <td>{{ $userJoinAgency->leave_date }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="text-center">{{ __('No agency join logs found') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    @if($userJoinAgencies && $userJoinAgencies->count())
                        <div class="pagination-container">
                            {{ $userJoinAgencies->appends([
                                'tab' => 'user-agency',
                                'user_agency_page' => $userJoinAgencies?->currentPage(),
                            ])->links('vendor.pagination.bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@if($activeTab == 'user-coins')

<div class="tab-content" id="user-coins-tab" style="{{ request('tab') == 'user-coins' ? 'display: block;' : 'display: none;' }}">
    <div class="card">
    <div class="card-header">
            <h4 class="card-title" style="text-align: start;">{{ __('Users Coins Logs') }}</h4>
        </div>
        <div class="box-body p-3">
            <div class="card mb-4">
                <div class="card-body">
                    <form action="{{ url('bd/users/profile/' . $user->id) }}" class="form-horizontal user-agency-form" method="GET" pjax-container>
                        <input type="hidden" name="tab" value="user-coins">
                        <input type="hidden" name="coins_page" value="{{ request()->get('coins_page', 1) }}">

                        <div class="row mb-3" style="align-items: flex-end;">
                            <!-- From Date -->
                            <div class="col-md-3">
                                <label>{{ __('From Date') }}</label>
                                <input type="date" class="form-control" name="from_date" value="{{ request('from_date') }}">
                            </div>

                            <!-- To Date -->
                            <div class="col-md-3">
                                <label>{{ __('To Date') }}</label>
                                <input type="date" class="form-control" name="to_date" value="{{ request('to_date') }}">
                            </div>

                            <!-- Sub Type -->
                            <div class="col-md-3">
                                <label>{{ __('Sub Type') }}</label>
                                <select name="sub_type" class="form-control">
                                    <option value="">{{ __('All') }}</option>
                                    @foreach(\App\Helpers\Common::getCoinSubTypes() as $type)
                                        <option value="{{ $type }}" {{ request('sub_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Search/Reset Buttons -->
                            <div class="col-md-3 d-flex align-items-end justify-content-end" style="gap: 8px;top: 23px;">
                                <button type="submit" class="btn btn-info btn-sm me-2">
                                    <i class="fa fa-search"></i> {{ __('Search') }}
                                </button>
                                <a href="{{ url('bd/users/profile/' . $user->id. '?tab=user-coins') }}" class="btn btn-default btn-sm">
                                    <i class="fa fa-undo"></i> {{ __('Reset') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <div class="table-responsive">
            <div class="box-body ">
                <table class="table table-bordered table-hover align-middle data-table" id="vip">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('type') }}</th>
                            <th>{{ __('sub type') }}</th>
                            <th>{{ __('Item Name') }}</th>
                            <th>{{ __('balance before') }}</th>
                            <th>{{ __('amount') }}</th>
                            <th>{{ __('balance yet') }}</th>
                            <th>{{ __('from date') }}</th>
                            <th>{{ __('to date') }}</th>
                            <!-- <th>{{ __('action') }}</th> -->
                        </tr>
                    </thead>
                    @if($usersCoins && $usersCoins->count())
                        <tbody style="color: var(--profile-muted);">
                            @foreach($usersCoins as $index => $coin)
                                <tr>
                                    <td>{{ ($usersCoins->currentPage() - 1) * $usersCoins->perPage() + $index + 1 }}</td>
                                    <td>{{ $coin->type }}</td>
                                    <td>{{ @$coin->sub_type ?? 0 }}</td>
                                    <td>{{ @$coin->item_name ?? '' }}</td>
                                    <td>{{ @$coin->amount_before ?? 0 }}</td>
                                    <td class="{{ ($coin->amount ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $coin->amount ?? 0 }}
                                    </td>
                                    <td>{{ ($coin->amount_before ?? 0) + ($coin->amount ?? 0) }}</td>
                                    <td>{{ @$coin->from_date ?? 0 }}</td>
                                    <td>{{ @$coin->to_date ?? 0 }}</td>
                                    <!-- <td>
                                        <div class="d-flex"> -->
                                            <!-- <button class="btn btn-danger delete-coins-log-btn" data-id="{{ @$coin->id }}">
                                                {{ __('dashboard.delete') }}
                                            </button> -->
                                        <!-- </div>
                                    </td> -->
                                </tr>
                            @endforeach
                        </tbody>
                    @endif
                </table>

                @if($usersCoins)
                    <div class="pagination-container">
                        {{ $usersCoins->appends([
                            'tab' => 'user-coins',
                            'pack_page' => $packs?->currentPage(),
                            'salary_page' => $salaries?->currentPage(),
                            'gift_page' => $giftSLogs?->currentPage(),
                            'coins_page' => $usersCoins?->currentPage(),
                        ])->links('vendor.pagination.bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
      </div>
    </div>
</div>
@endif

@if($activeTab == 'charge')
    <div class="tab-content active" id="charge-tab">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{ __('Charge Reports') }}</h4>
            </div>

            <div class="box-body">
                <div class="nav-scroll-container">
                    <ul class="nav nav-pills">

                        <li class="{{ $chargeTabType == 'receiver' ? 'active' : '' }}">
                            <a class="nav-link @if($chargeTabType == 'receiver') active @endif"
                               href="?tab=charge&type=receiver"
                               role="tab">
                                {{ __('Receiver') }}
                            </a>
                        </li>
                        <li class="{{ $chargeTabType == 'charger' ? 'active' : '' }}">
                            <a class="nav-link @if($chargeTabType == 'charger') active @endif"
                               href="?tab=charge&type=charger"
                               role="tab">
                                {{ __('Charger') }}
                            </a>
                        </li>

                    </ul>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>
                                @if($chargeTabType == 'receiver')
                                    {{ __('Charger') }}
                                @else
                                    {{ __('Receiver') }}
                                @endif
                            </th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('usd') }}</th>
                            <th>{{ __('Created at') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($charges as $index => $charge)
                            @php
                                if($chargeTabType == 'receiver') {
                                    $userCharges = Common::getChargerInfo($charge);
                                  } else {
                                    $userCharges = Common::getReceiverInfo($charge);
                                  }
                                  $name = $userCharges['name'] ?? '-';
                                  $uid = $userCharges['uuid'] ?? '-';
                                  $type = $userCharges['type'] ?? '-';
                                  $image = $userCharges['image'] ?? asset('images/businessman-icon.jpg');
                            @endphp
                            <tr>
                                <td>{{ @$charge->id ?? 0 }}</td>
                                <td>
                                    <a href="{{  '#' }}"
                                       style="display: inline-flex; align-items: center; text-decoration: none;">
                                        <img src="{{ getImagePath( $image) }}" width="30" height="30"
                                             style="object-fit: cover; border-radius: 50%; margin-inline-end: 10px;">
                                        <span>{{ $name }} ({{ $uid }})</span>
                                    </a>
                                </td>
                                <td>{{ $type }} </td>
                                <td>{{ $charge->amount }} </td>
                                <td>{{ $formattedUsd = number_format((float)$charge->usd, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($charge->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>

            {{-- Pagination --}}
            @if($charges instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="pagination-container mt-3">
                    {{ $charges->appends([
                        'tab' => 'charge',
                        'pack_page' => $packs?->currentPage(),
                        'vip_page' => $userVips?->currentPage(),
                        'salary_page' => $salaries?->currentPage(),
                        'gift_page' => $giftSLogs?->currentPage(),
                    ])->links('vendor.pagination.bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
@endif



@if($activeTab == 'gift-log')
    <div class="tab-content active" id="gift-log-tab">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{ __('gift Reports') }}</h4>
            </div>

            <div class="box-body p-3">
                <div class="nav-scroll-container mb-3">
                    <ul class="nav nav-pills">
                        <li class="{{ $giftType == 'receiver' ? 'active' : '' }}">
                            <a class="nav-link @if($giftType == 'receiver') active @endif"
                               href="?tab=gift-log&gift_type=receiver"
                               role="tab">
                                {{ __('received gift') }}
                            </a>
                        </li>
                        <li class="{{ $giftType == 'sender' ? 'active' : '' }}">
                            <a class="nav-link @if($giftType == 'sender') active @endif"
                               href="?tab=gift-log&gift_type=sender"
                               role="tab">
                                {{ __('sent gift') }}
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <form action="{{ url('bd/users/profile/' . $user->id) }}" class="form-horizontal gift-log-form" method="GET" pjax-container>
                            <input type="hidden" name="tab" value="gift-log">
                            <input type="hidden" name="gift_type" value="{{ $giftType }}">
                            <input type="hidden" name="gift_page" value="{{ request()->get('gift_page', 1) }}">

                            <div class="row mb-2" style="align-items: flex-end;">
                                <!-- From Date -->
                                <div class="col-md-4">
                                    <div class="date-flex-row">
                                        <i class="fa fa-calendar"></i>
                                        <span>{{ __('From Date') }}</span>
                                        <input type="date" class="form-control" id="from_date" name="start_at" value="{{ request('start_at') }}">
                                    </div>
                                </div>
                                <!-- To Date -->
                                <div class="col-md-4">
                                    <div class="date-flex-row">
                                        <i class="fa fa-calendar"></i>
                                        <span>{{ __('To Date') }}</span>
                                        <input type="date" class="form-control" id="to_date" name="end_at" value="{{ request('end_at') }}">
                                    </div>
                                </div>
                                @if ($giftType == 'receiver')
                                    <div class="col-md-4">
                                            <div class="date-flex-row">
                                                <label for="agency_id">{{ __('Agency') }}</label>
                                                <select class="form-control" id="agency_id" name="agency_id">
                                                    @if(request('agency_id'))
                                                        <option value="{{ request('agency_id') }}" selected>
                                                            {{ \App\Models\Agency::find(request('agency_id'))?->name . ' - ' . request('agency_id') }}
                                                        </option>
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                @endif
                                 <!-- Buttons -->
                                <div class="col-md-4 d-flex align-items-end justify-content-end" style="gap: 8px;">
                                    <button type="submit" class="btn btn-info btn-sm me-2">
                                        <i class="fa fa-search"></i> {{__('Search')}}
                                    </button>
                                    <a href="{{ url('bd/users/profile/' . $user->id. '?'.'tab=gift-log&gift_type=' . $giftType) }}" class="btn btn-default btn-sm">
                                        <i class="fa fa-undo"></i> {{__('Reset')}}
                                    </a>
                                </div>
                             </div>

                        </form>
                    </div>
                </div>
                <!-- Summary Box -->
                <div class="diamond-summary-container">
                    <div class="diamond-summary-box">
                        <div class="diamond-title">
                            {{ $giftType == 'receiver' ? __('total diamonds received') : __('total diamonds sent') }}
                        </div>
                        <div class="diamond-count">
                            <span>{{ number_format(@$diamonds) }}</span>
                            <div class="diamond-icon-container">
                                <img src="{{ asset('images/diamond.jpg') }}" alt="Diamond" class="diamond-icon">
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ $giftType == 'receiver' ? __('Sender') : __('Receiver') }}</th>
                            <th>{{ __('room') }}</th>
                            <th>{{ __('moment') }}</th>
                            <th>{{ __('gift') }}</th>
                            @if($giftType == 'receiver')
                                <th>{{ __('agency') }}</th>
                            @endif
                            <th>{{ __('quantity') }}</th>
                            <th>{{ __('price') }}</th>
                            <th>{{ __('Created at') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($giftSLogs ?? [] as $index => $giftSLog)
                            @php
                                $userImageDefault = asset('images/businessman-icon.jpg');
                                $defaultImage = asset("images/background_room.jpg");

                                $userCharges = $giftType === 'receiver' ? $giftSLog->sender : $giftSLog->receiver;
                                $name = @$userCharges->name ?? '';
                                $uid = @$userCharges->uuid ?? '';
                                $id = @$userCharges->id ?? 0;

                                $avatar = @$userCharges->profile->avatar;
                                $image = getImagePath($avatar) ?? $userImageDefault;
                                if (!isImageExists($image)) {
                                    $image = $userImageDefault;
                                }

                                $roomName = @$giftSLog->room->room_name ?? '-';
                                $path = @$giftSLog->room->room_cover;
                                $ownerRoom = @$giftSLog->room->uid ?? 0;
                                $url = getImagePath($path) ?? $defaultImage;
                                if (!isImageExists($url)) {
                                    $url = $defaultImage;
                                }

                                $giftName = app()->getLocale() == 'ar'
                                    ? (@$giftSLog->gift->name ?? '')
                                    : (@$giftSLog->gift->e_name ?? '');

                                    $agency =$giftSLog->agency;
                                    $agencyName = $agency->name ?? '';
                                    $agencyId = $agency->id ?? 0;
                                    $agencyDefaultImage = asset("images/icon-agency.jpg");
                                    $agencyImage =getImagePath(@$agency->img) ?? $agencyDefaultImage;
                                    if (!isImageExists($agencyImage)) {
                                    $agencyImage = $agencyDefaultImage;
                                }
                            @endphp
                            @php
                                $moment = $giftSLog->moment;
                                $galleries = @$moment->images;
                            @endphp
                            <tr>
                                <td>{{ @$giftSLog->id ?? 0 }}</td>
                                <td>
                                    <a href="{{ url('bd/users/profile/' . $id) }}"
                                       class="d-flex align-items-center text-decoration-none">
                                        <img src="{{ $image }}" width="40" height="40"
                                             style="object-fit: cover; border-radius: 50%; margin-inline-end: 10px;">
                                        <div>
                                            <strong style="font-size: 14px;">{{ $name }}</strong><br>
                                            <small class="text-muted">UUID: {{ $uid }}</small>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    @if(!empty($giftSLog->room))
                                        <a href="{{ url('bd/users/profile/' . $ownerRoom) }}"
                                           class="d-flex align-items-center text-decoration-none">
                                            <img src="{{ $url }}"
                                                 width="30" height="30"
                                                 style="object-fit: cover; border-radius: 50%; margin-inline-end: 10px;">
                                            <div>
                                                <span>{{ $roomName }}</span><br>
                                                <small class="text-muted">Type: {{ $giftSLog->room->type ?? '-' }}</small>
                                            </div>
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    @if($galleries && $galleries->count() > 0)
                                        <div id="image-gallery-{{ $moment->id }}" style="display: none;">
                                            @foreach($galleries as $image)
                                                @php
                                                    $imgUrl = getDriverUrl() . '/' . $image->image;
                                                @endphp
                                                <img src="{{ $imgUrl }}"
                                                     style="width: 100%; height: 200px; object-fit: cover;"
                                                     data-original="{{ $imgUrl }}"
                                                     loading="lazy"
                                                     class="gallery-image">
                                            @endforeach
                                        </div>

                                        {{-- Show first image as thumbnail --}}
                                        @php
                                            $firstImageUrl = getDriverUrl() . '/' . $galleries->first()->image;
                                        @endphp
                                        <img src="{{ $firstImageUrl }}"
                                             style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"
                                             onclick="document.querySelector('#image-gallery-{{ $moment->id }} img').click()">

                                        @push('scripts')
                                            <script>
                                                new Viewer(document.getElementById('image-gallery-{{ $moment->id }}'));
                                            </script>
                                        @endpush
                                    @else
                                        No Image
                                    @endif
                                </td>
                                <td>
                                    <a href="#"
                                       class="d-flex align-items-center text-decoration-none">
                                        <img src="{{ getImagePath($giftSLog->gift->img ??'') }}"
                                             width="30" height="30"
                                             style="object-fit: cover; border-radius: 50%; margin-inline-end: 10px;">
                                        <span>{{ $giftName  }}</span>
                                    </a>
                                </td>
                                @if($giftType == 'receiver')
                                    <td>
                                        @if ($giftSLog->agency_id)
                                            <a href="{{ url('bd/agencies/profile/' . $agencyId) }}"
                                               class="d-flex align-items-center text-decoration-none">
                                                <img src="{{ $agencyImage }}"
                                                     width="50" height="30"
                                                     style="object-fit: cover; border-radius: 4px; border: 1px solid var(--border); padding: 2px; margin-inline-end: 10px;">
                                                <div>
                                                    <span>{{ $agencyName }}</span><br>
                                                    <small class="text-muted">id: {{ $agencyId ?? 0 }}</small>
                                                </div>
                                            </a>
                                        @else
                                            <span class="text-danger">{{__('not join to agency')}}</span>
                                        @endif
                                    </td>
                                @endif
                                <td>{{ $giftSLog->giftNum }}</td>
                                <td>{{  $giftSLog->giftPrice}}</td>
                                <td>{{ \Carbon\Carbon::parse($giftSLog->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                @if($giftSLogs instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="d-flex justify-content-center mt-3">
                        {{ $giftSLogs->appends([
                            'tab' => 'gift-log',
                            'gift_type' => $giftType,
                            'start_at' => request('start_at'),
                            'end_at' => request('end_at'),
                            'gift_page' => $giftSLogs->currentPage()
                        ])->links('vendor.pagination.bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif



<div class="modal fade" id="Add_model" tabindex="-1" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg mt-6" role="document">
        <div class="modal-content border-0">
            <div class="modal-content position-relative">
                <div class="position-absolute top-0 end-0 mt-2 me-2 z-index-1">
                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="add_form">
                    @csrf
                    <div class="modal-body p-0">
                        <div class="rounded-top-lg py-3 ps-4 pe-6 bg-light">
                            <h4 class="mb-1" id="modalExampleDemoLabel"> {{ __('dashboard.free') }}</h4>
                        </div>
                        <div class="p-4">

                            <div class="row" style="justify-content:space-evenly">

                                <input type="hidden" name=id class="item_id">
                                <div class="mb-3 col-md-12">
                                    <label for="type" class="form-label">{{ __('type') }}</label>
                                    <select name="type" id="type" class="form-select">
                                        <option value="0">{{ __('dashboard.raise') }}</option>
                                        <option value="1">{{ __('dashboard.lower') }}</option>
                                    </select>
                                </div>

                                <!-- Days Input -->
                                <div class="mb-3 col-md-6">
                                    <label for="days" class="form-label">{{ __('days') }}</label>
                                    <input type="number" class="form-control" id="days" name="days"
                                           placeholder="{{ __('days') }}">
                                </div>

                            </div>

                        </div>
                    </div>
                    <div class="modal-footer mt-3">
                        <button class="btn btn-secondary close-modal-btn" type="button" data-bs-dismiss="modal">{{ __('Cancel') }} </button>
                        <button class="btn btn-primary add_country" type="submit">{{ __('save') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>




    <div class="modal fade" id="item_modal_update" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg mt-6" role="document">
            <div class="modal-content border-0">
                <div class="modal-content position-relative">
                    <div class="position-absolute top-0 end-0 mt-2 me-2 z-index-1">
                        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base"
                                data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form action="" id="country_update_form" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body p-0">

                            <div class="p-4">
                                <div class="row flex-evenly">
                                    <input type="hidden" name="id" value="{{ old('id', $user->id) }}">

                                    <div class="col-lg-6 mb-3 form-group">
                                        <label class="form-label">{{ __('Name') }}</label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" >
                                    </div>

                                    <div class="col-lg-6 mb-3 form-group">
                                        <label class="form-label">{{ __('uuid') }}</label>
                                        <input type="text" name="uuid" class="form-control" value="{{ old('uuid', $user->uuid ?? '') }}" >
                                    </div>

                                    <div class="col-lg-6 mb-3 form-group">
                                        <label class="form-label">{{ __('email') }}</label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}">
                                    </div>

                                    <div class="col-lg-6 mb-3 form-group">
                                        <label class="form-label">{{ __('phone') }}</label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone ?? '') }}" >
                                    </div>

                                    <div class="mb-3 col-lg-12 form-group">
                                        <label class="form-label">{{ __('Gender') }}</label>
                                        <select class="form-select col-lg-6" name="gender">
                                            <option value="">{{ __('Choose gender') }}</option>
                                            <option value="0" {{ old('gender', $user->profile->gender ?? '') == '0' ? 'selected' : '' }}>{{ __('female') }}</option>
                                            <option value="1" {{ old('gender', $user->profile->gender ?? '') == '1' ? 'selected' : '' }}>{{ __('male') }}</option>
                                        </select>
                                    </div>

                                    <div class="col-lg-6 form-group mb-3">
                                        <label class="form-label">{{ __('image') }}</label>
                                        <input class="form-control" name="image" accept="image/*" type="file" />
                                        <div class="mt-2">
                                            <img src="{{ getImagePath($user->profile->avatar ?? '') ?? asset('images/default-avatar.png') }}"
                                                class="rounded"
                                                style="width: 100px; height: 100px"
                                                id="img_edit"
                                                alt="{{ $user->name ?? '' }}">
                                        </div>
                                    </div>

                                    <div class="mb-3 col-lg-12 form-group">
                                        <label class="form-label">{{ __('Country') }}</label>
                                        <select class="form-select col-lg-6" name="country_id" id="country_id">
                                            @foreach($countries as $id => $name)
                                                <option value="{{ $id }}" {{ old('country_id', $user->country_id ?? null) == $id ? 'selected' : '' }}>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3 col-lg-12 form-group">
                                        <label class="form-label">{{ __('bio') }}</label>
                                        <textarea name="bio" id="" cols="30" rows="10" class="form-control" value="{{ old('bio', $user->bio ?? '') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-secondary cancel_user_item_model_btn" type="button" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button class="btn btn-primary" type="submit">{{ __('edit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>



{{-- jQuery and SweetAlert2 are loaded globally by the admin layout; no CDN. --}}


<script>



    $(document).ready(function () {

     $(document).on('click', '.edit_user_item_model_btn', function() {
                $('#item_modal_update').modal('show');
       });

       $(document).on('click', '.cancel_user_item_model_btn', function() {
            $('#item_modal_update').modal('hide');
        });
    $('#add_form').on('submit', function (e) {
        e.preventDefault(); // prevent default form submit

        let form = $(this);
        let formData = form.serialize();

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            success: function (response) {
                // ✅ close modal
                $('#Add_model').modal('hide');

                $('#Add_model').modal('hide');

            // ✅ Reload the page
            location.reload();

            },
            error: function (xhr) {
                // show error message
                let errors = xhr.responseJSON.errors;
                let msg = '';
                for (let key in errors) {
                    msg += errors[key][0] + '\n';
                }
                alert(msg || 'Something went wrong!');
            }
        });
    });
});

    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedTab = urlParams.get('tab') || 'packs';

        const allTabs = document.querySelectorAll('.tab-btn');
        const allTabContents = document.querySelectorAll('[id$="-tab"]');

        let targetElement = null;

        allTabs.forEach(tab => {
            const target = tab.getAttribute('data-target');
            const content = document.getElementById(target);

            if (target.startsWith(selectedTab)) {
                tab.classList.add('active');
                content.style.display = 'block';
                targetElement = content; // خزن العنصر لعمل scroll إليه لاحقًا
            } else {
                tab.classList.remove('active');
                content.style.display = 'none';
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



         $('#agency_id').select2({
        placeholder: 'Select agency',
        allowClear: true,
        ajax: {
            url: '/api/search/host-agency', // ✅ make sure this matches your route
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                return {
                    results: data.data.map(item => ({
                        id: item.id,
                        text: item.name
                    })),
                    pagination: {
                        more: data.next_page_url !== null
                    }
                };
            },
            cache: true
        }
    });

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

        $(document).on('click', '.edit_item_model_btn', function () {
            let itemId = $(this).data('id');

            // Clear the form
            $('#add_form')[0].reset();

            // Set the hidden ID field
            $('.item_id').val(itemId);

            // Open the modal
            $('#Add_model').modal('show');
        });
            $(document).on('click', '.close-modal-btn', function () {
                $('#Add_model').modal('hide');
            });

        $(document).on('click', '.delete-btn', function () {
            let itemId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '/admin/delete-pack/' + itemId,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'An error occurred.'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.delete-vip-btn', function () {
            let itemId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '/admin/delete-user-vip/' + itemId,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'An error occurred.'
                            });
                        }
                    });
                }
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


    });

</script>

