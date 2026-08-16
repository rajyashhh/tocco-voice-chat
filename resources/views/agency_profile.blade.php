<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ admin_asset('vendor/fontawesome6/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ admin_asset('vendor/fonts/inter/inter.css') }}">

    {{-- Central design system: one source of truth for every color/surface.
         This view is rendered via $content->view() INSIDE the admin layout,
         so html.dark-mode + these tokens already cascade here; the include
         guarantees the tokens resolve even if this doc is opened standalone. --}}
    @include('css.theme-tokens')

    <style>
        /* Inter is loaded locally via <link> in the head — no external CDN. */

        /* No local :root here — all tokens come from css/theme-tokens.
           Recolor the whole panel by editing the base tokens in ONE file. */

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--page-bg);
            color: var(--text-primary);
            margin: 0;
        }

        /* ========== CONTAINER ========== */
        .agency-profile-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px;
        }

        /* ========== MAIN HEADER CARD ========== */
        .agency-header {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 0;
            margin-bottom: 20px;
            overflow: hidden;
            position: relative;
        }

        .agency-header-banner {
            background: var(--gradient-1);
            height: 120px;
            position: relative;
        }

        .agency-header-banner .btn-back {
            position: absolute;
            top: 16px;
            right: 16px;
            background: var(--card-bg);
            border: none;
            padding: 10px 22px;
            border-radius: 50px;
            color: var(--accent);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            z-index: 10;
        }

        .agency-header-banner .btn-back:hover {
            background: var(--profile-subtle-bg);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        .agency-header-body {
            padding: 0 32px 28px;
            display: flex;
            align-items: flex-end;
            gap: 24px;
            margin-top: -50px;
            position: relative;
            flex-wrap: wrap;
        }

        .agency-avatar {
            width: 110px;
            height: 110px;
            border-radius: 20px;
            overflow: hidden;
            border: 4px solid var(--card-bg);
            box-shadow: var(--shadow-md);
            flex-shrink: 0;
            background: var(--card-bg);
        }

        .agency-avatar .logo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .agency-info {
            flex: 1;
            min-width: 200px;
            padding-top: 10px;
        }

        .agency-name {
            margin: 0 0 8px 0;
            color: var(--profile-heading);
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .agency-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 12px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            background: var(--profile-subtle-bg);
            padding: 5px 12px;
            border-radius: 8px;
        }

        .meta-label {
            font-weight: 600;
            color: var(--profile-muted);
        }

        .meta-value {
            color: var(--profile-heading);
            font-weight: 500;
        }

        .agency-stats {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
        }

        .stat-card {
            background: var(--profile-subtle-bg);
            border-radius: 14px;
            padding: 14px 22px;
            min-width: 120px;
            text-align: center;
            border: 1px solid var(--card-border);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .stat-value {
            font-size: 22px;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 11px;
            color: var(--profile-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-top: 2px;
        }

        /* ========== PERSON CARDS (Owner & BD) ========== */
        .people-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .people-row { grid-template-columns: 1fr; }
        }

        .person-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s;
            border: 1px solid var(--card-border);
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }

        .person-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            border-radius: 4px 0 0 4px;
        }

        .person-card.owner-card::before { background: var(--gradient-2); }
        .person-card.bd-card::before { background: var(--gradient-3); }

        .person-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .person-card .person-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .person-badge.owner { background: var(--gradient-2); color: var(--on-color); }
        .person-badge.bd { background: var(--gradient-3); color: var(--on-color); }

        .person-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--card-border);
            flex-shrink: 0;
        }

        .person-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .person-info h3 {
            margin: 0 0 4px;
            font-size: 17px;
            font-weight: 700;
            color: var(--profile-heading);
        }

        .person-info .person-meta {
            font-size: 12px;
            color: var(--profile-muted);
            font-weight: 500;
        }

        /* ========== NOTICE ========== */
        .notice-section {
            background: var(--chip-warning-bg);
            border-inline-start: 5px solid var(--warning);
            padding: 18px 22px;
            border-radius: 0 var(--radius) var(--radius) 0;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .notice-section i {
            font-size: 20px;
            color: var(--warning);
            margin-top: 2px;
        }

        .notice-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            color: var(--chip-warning-fg);
            font-weight: 700;
            font-size: 14px;
        }

        .notice-content {
            color: var(--text-secondary);
            line-height: 1.6;
            font-size: 13px;
        }

        /* ========== TOP PERFORMERS ========== */
        .top-performers-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .top-performers-section { grid-template-columns: 1fr; }
        }

        .performers-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            padding: 20px;
            border: 1px solid var(--card-border);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .section-title {
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--profile-heading);
            font-weight: 700;
        }

        .section-badge {
            background: var(--gradient-1);
            color: var(--on-color);
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(75px, 1fr));
            gap: 12px;
        }

        .avatar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s;
        }

        .avatar-item:hover { transform: translateY(-4px); }

        .avatar-img-container {
            position: relative;
            width: 56px;
            height: 56px;
            margin-bottom: 6px;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--card-border);
            box-shadow: var(--shadow-sm);
        }

        .avatar-badge {
            position: absolute;
            bottom: -4px;
            right: -4px;
            background: var(--gradient-2);
            color: var(--on-color);
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: 800;
            border: 2px solid var(--card-bg);
        }

        .avatar-badge.admin { background: var(--gradient-4); }

        .avatar-name {
            font-size: 11px;
            text-align: center;
            max-width: 75px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
            color: var(--meta-value);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 0;
            color: var(--profile-faint);
        }

        .empty-state i { font-size: 36px; margin-bottom: 10px; }
        .empty-state p { margin: 0; font-size: 13px; }

        /* ========== TABS ========== */
        .agency-tabs {
            display: flex;
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            padding: 6px;
            margin-bottom: 24px;
            overflow-x: auto;
            gap: 4px;
        }

        .tab-btn {
            padding: 10px 18px;
            background: none;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: var(--profile-muted);
            text-decoration: none;
        }

        .tab-btn i { font-size: 14px; }

        .tab-btn.active {
            background: var(--gradient-1);
            color: var(--on-color) !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.18);
        }

        .tab-btn:hover:not(.active) {
            background: var(--profile-subtle-bg);
            color: var(--profile-heading);
        }

        /* ========== CARDS ========== */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            border: 1px solid var(--card-border);
            overflow: hidden;
        }

        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--profile-hairline);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h3, .card-header h4 {
            margin: 0;
            font-size: 16px;
            color: var(--profile-heading);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .count-badge {
            background: var(--gradient-1);
            color: var(--on-color);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        /* ========== TABLES ========== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead th {
            background: var(--profile-subtle-bg);
            padding: 12px 16px;
            text-align: start;
            font-size: 12px;
            font-weight: 700;
            color: var(--profile-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--card-border);
        }

        .data-table thead th i {
            font-size: 13px;
        }

        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--profile-hairline);
            vertical-align: middle;
            font-size: 13px;
        }

        .data-table tbody tr {
            transition: background 0.2s;
        }

        .data-table tbody tr:hover {
            background: var(--profile-subtle-bg);
        }

        .data-table tr:last-child td { border-bottom: none; }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info { display: flex; flex-direction: column; }
        .user-info strong { font-size: 13px; font-weight: 600; }
        .user-info small { font-size: 11px; color: var(--profile-faint); }

        /* ========== BADGES & BUTTONS ========== */
        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            color: var(--on-color);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .role-badge.owner { background: var(--gradient-2); }
        .role-badge.admin { background: var(--gradient-4); color: var(--profile-heading); }

        .btn-action {
            padding: 6px 14px;
            color: var(--on-color);
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--gradient-1);
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-action.btn-danger {
            background: var(--chip-danger-bg) !important;
        }

        .number-badge {
            display: inline-block;
            padding: 4px 12px;
            background: var(--meta-chip-bg);
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            color: var(--meta-value);
        }

        .number-badge.warning {
            background: var(--chip-warning-bg);
            color: var(--chip-warning-fg);
        }

        /* ========== STAT ICONS ========== */
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--on-color);
            font-size: 18px;
            flex-shrink: 0;
        }

        .stat-icon.bg-blue { background: var(--gradient-3); }
        .stat-icon.bg-green { background: var(--gradient-4); color: var(--profile-heading); }

        .stat-info { flex: 1; }

        .stats-row {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }

        .stats-row .stat-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            flex: 1;
        }

        /* ========== DIAMOND SUMMARY ========== */
        .diamond-summary-container {
            display: flex;
            justify-content: center;
            width: 100%;
            padding: 20px;
        }

        .diamond-summary-box {
            max-width: 500px;
            width: 100%;
            background: var(--gradient-1);
            border-radius: var(--radius);
            padding: 24px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0,0,0,0.18);
            transition: all 0.3s ease;
            margin: 0 auto;
        }

        .diamond-summary-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.18);
        }

        .diamond-title {
            font-size: 14px;
            font-weight: 700;
            color: rgba(255,255,255,0.85);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .diamond-count {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 800;
            color: var(--on-color);
            gap: 10px;
        }

        .diamond-icon-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            padding: 8px;
            display: inline-flex;
        }

        .diamond-icon {
            width: 28px;
            height: 28px;
            filter: drop-shadow(0 0 3px rgba(255, 255, 255, 0.5));
        }

        .gift-log-form {
            background-color: transparent !important;
            filter: none !important;
        }

        .ltr .gift-log-form { padding-left: 13%; }

        /* ========== EMPTY TABLE ========== */
        .empty-table {
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--profile-faint);
            background: var(--profile-subtle-bg);
            border-radius: 12px;
            margin: 16px;
        }

        .empty-table i { font-size: 40px; margin-bottom: 12px; }
        .empty-table p { margin: 0; font-size: 14px; font-weight: 500; }

        .user-avatar, .supporter-avatar {
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

        .user-name { font-weight: 600; font-size: 13px; }
        .user-uuid { font-size: 11px; color: var(--profile-faint); }

        /* ========== SECTION HEADER ========== */
        .section-header h4 {
            margin: 0;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
        }

        .section-header .text-yellow { color: var(--warning); }
        .section-header .text-red { color: var(--danger); }

        /* ========== FILTER AREA ========== */
        .target-card-section-1 {
            width: 100%;
            padding-top: 20px;
            margin-bottom: 30px;
        }

        .card-target-filter { display: none; }

        .card-target-filter-phone {
            width: 51%;
            margin-bottom: 27px;
            position: relative;
        }

        .rtl .card-target-filter-phone .form-group { margin-bottom: 16px; right: 20px; position: relative; top: 10px; }
        .ltr .card-target-filter-phone .form-group { margin-bottom: 16px; left: 20px; position: relative; top: 10px; }
        .card-target-filter-phone button { position: relative; left: -49px; bottom: -29px; }

        .target-card-stat { width: 50%; }
        .filter-form { border-radius: 13px; height: 165px; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .date-flex-row { display: flex; align-items: center; gap: 8px; }
        .date-flex-row span { font-weight: 600; white-space: nowrap; }

        /* ========== PAGINATION ========== */
        .pagination-wrapper, .pagination-container {
            padding: 16px 24px;
        }

        /* ========== LOADING ========== */
        #tab-loading {
            font-family: 'Inter', sans-serif;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .agency-header-body { padding: 0 16px 20px; gap: 16px; }
            .agency-name { font-size: 20px; }
            .stats-row { flex-direction: column; }
            .avatar-grid { grid-template-columns: repeat(auto-fill, minmax(65px, 1fr)); }
            .target-card-section-1 { display: grid; width: 100%; }
            .target-card-stat { width: 92%; }
            .card-target-filter-phone { display: block; width: 100%; left: 0; position: relative; margin-bottom: 31px; }
            .card-target-filter-phone .col-md-7 { float: none; }
            .card-target-filter-phone .form-control {
                display: block; width: 89%; padding: 6px 12px; font-size: 14px; line-height: 1.42857143;
                color: var(--text-secondary-color) !important;
                background-color: var(--text-secondary-color) !important;
                background-image: none;
                border: 1px solid rgba(0,0,0,0.1) !important;
                border-radius: 8px;
            }
            .control {
                width: 89%; padding: 6px 12px; border-radius: 8px; font-size: 14px; line-height: 1.42857143;
                color: var(--input-text) !important;
                background-color: var(--input-bg) !important;
                border-color: var(--input-border) !important;
            }
            .card-target-filter-phone .filter-form { border-radius: 13px; height: 238px; }
            .card-target-filter-phone .align-items-end { display: grid; }
            .card-target-filter-phone button { left: -224px; }
            .agency-tabs { border-radius: 12px; }
        }
    </style>
</head>

<body>

    <div class="agency-profile-container">
        <!-- ===== GO BACK BUTTON ===== -->
        <div style="margin-bottom:16px; display:flex; justify-content:flex-end;">
            <a href="{{ route('admin.agencies.index') }}" style="display:inline-flex !important; align-items:center; gap:8px; padding:10px 22px; background:var(--card-bg); color:var(--accent); border-radius:50px; font-weight:700; font-size:14px; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.1); transition:all 0.3s; border:2px solid var(--card-border);">
                <i class="fas fa-arrow-left"></i> {{__("Go Back")}}
            </a>
        </div>

        <!-- ===== MAIN AGENCY HEADER WITH GRADIENT BANNER ===== -->
        <div class="agency-header">
            <div class="agency-header-banner"></div>
            <div class="agency-header-body">
                <div class="agency-avatar">
                    <img src="{{ @$imageUrl??asset('images/icon-agency.jpg') }}" alt="Agency Logo" class="logo-img">
                </div>
                <div class="agency-info">
                    <h1 class="agency-name">{{ @$agency?->name ?? ''}}</h1>
                    <div class="agency-meta">
                        <div class="meta-item">
                            <i class="fas fa-id-badge" style="color:var(--accent);"></i>
                            <span class="meta-label">{{__('dashboard.agency_id')}}:</span>
                            <span class="meta-value">{{ $agency->id }}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-phone" style="color:var(--success);"></i>
                            <span class="meta-label">{{__("Phone")}}:</span>
                            <span class="meta-value">{{ @$agency->phone ?? 'N/A' }}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar" style="color:var(--danger);"></i>
                            <span class="meta-label">{{__('created at')}}:</span>
                            <span class="meta-value">{{ \Carbon\Carbon::parse($agency->created_at)->format('Y-m-d') }}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-user-cog" style="color:var(--info);"></i>
                            <span class="meta-label">{{__("created by")}}:</span>
                            <img src="{{ $imageUrlAdmin }}" style="width:20px;height:20px;border-radius:50%;object-fit:cover;">
                            <span class="meta-value">{{ @$adminUser->name ?? '' }}</span>
                        </div>
                    </div>
                    <div class="agency-stats">
                        <div class="stat-card">
                            <div class="stat-value">{{ number_format(truncateAndTrim(@$agency->salary ?? 0)) }}</div>
                            <div class="stat-label"><i class="fas fa-money-bill-wave"></i> {{__("salary")}}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">{{ number_format(truncateAndTrim(@$sumTargets ?? 0)) }}</div>
                            <div class="stat-label"><i class="fas fa-gem"></i> {{__("diamonds")}}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== OWNER & BD CARDS ROW ===== -->
        <div class="people-row">
            @php
                $ownerUrl = url($prefix."/users/{$agency?->owner?->id}");
                $bdUrl = url($prefix."/user-Bds/{$agency?->bd?->id}");
            @endphp

            <!-- Owner Card -->
            <a href="{{ $ownerUrl }}" class="person-card owner-card">
                <span class="person-badge owner"><i class="fas fa-crown"></i> Owner</span>
                <div class="person-avatar">
                    <img src="{{ getImagePath($agency?->owner?->profile?->avatar) ?? asset('images/businessman-icon.jpg') }}" alt="Owner">
                </div>
                <div class="person-info">
                    <h3>{{ $agency?->owner?->name ?? 'N/A' }}</h3>
                    <div class="person-meta">
                        <i class="fas fa-fingerprint"></i> UUID: {{ @$agency?->owner?->uuid ?? 'N/A' }}
                    </div>
                </div>
            </a>

            <!-- BD Card -->
            <a href="{{ $bdUrl }}" class="person-card bd-card">
                <span class="person-badge bd"><i class="fas fa-headset"></i> BD</span>
                <div class="person-avatar">
                    <img src="{{ getImagePath($agency?->bd?->avatar) ?? asset('images/businessman-icon.jpg') }}" alt="BD">
                </div>
                <div class="person-info">
                    <h3>{{ $agency?->bd?->name ?? $agency?->bd?->username ?? 'N/A' }}</h3>
                    <div class="person-meta">
                        <i class="fas fa-id-card"></i> ID: {{ @$agency?->bd?->id ?? 'N/A' }}
                    </div>
                </div>
            </a>
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
                                    $userUrl = $user ? route('admin.users.show', $user->id) : '#';

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
                                    $userUrl = $user ? route('admin.users.show', $user->id) : '#';

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
               <a href="?tab=members" class="tab-btn {{ $activeTab == 'members' ? 'active' : '' }}" data-target="members-tab"><i class="fas fa-users"></i> {{ __('Members') }}</a>
            @endif
           @if (\Encore\Admin\Facades\Admin::user()->can('charge-history-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
             <a href="?tab=charges" class="tab-btn" data-target="charges-tab"><i class="fas fa-credit-card"></i> {{ __('Charge History') }}</a>
            @endif
            @if (\Encore\Admin\Facades\Admin::user()->can('salary-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
             <a href="?tab=salary" class="tab-btn" data-target="salary-tab"><i class="fas fa-money-bill-wave"></i> {{ __('Salary') }}</a>
            @endif
            @if (\Encore\Admin\Facades\Admin::user()->can('join-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
             <a href="?tab=requests" class="tab-btn" data-target="requests-tab"><i class="fas fa-user-plus"></i> {{ __('Join Requests') }}</a>
            @endif
            @if (\Encore\Admin\Facades\Admin::user()->can('target-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
             <a href="?tab=targets" class="tab-btn" data-target="targets-tab"><i class="fas fa-bullseye"></i> {{ __('Targets') }}</a>
            @endif
            <a href="?tab=gift-log" class="tab-btn {{ $activeTab == 'gift-log' ? 'active' : '' }}"
           data-target="gift-log-tab"><i class="fas fa-gift"></i> {{ __('gifts') }}</a>
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
                <div class="card" style="border:none;">
                    <!-- Card Header with gradient accent -->
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:2px solid var(--profile-hairline); background:var(--profile-subtle-bg);">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:42px; height:42px; border-radius:12px; background:var(--gradient-3); display:flex; align-items:center; justify-content:center;">
                                <i class="fas fa-users" style="color:var(--on-color); font-size:18px;"></i>
                            </div>
                            <div>
                                <h3 style="margin:0; font-size:18px; font-weight:800; color:var(--profile-heading);">{{ __('Agency Members') }}</h3>
                                <span style="font-size:12px; color:var(--profile-muted);">{{ __('Manage agency members and roles') }}</span>
                            </div>
                        </div>
                        <span style="background:var(--gradient-1); color:var(--on-color); padding:6px 16px; border-radius:50px; font-size:13px; font-weight:700;">
                            {{ optional($members)->total() ?? 0 }} {{ __('Members') }}
                        </span>
                    </div>

                    <!-- Modern Search Bar -->
                    <div style="padding:16px 24px; background:var(--profile-subtle-bg); border-bottom:1px solid var(--profile-hairline);">
                        <form action="{{ url('admin/agencies/' . $agency->id) }}" class="form-horizontal member-form" method="GET" pjax-container>
                            <input type="hidden" name="tab" value="members">
                            <input type="hidden" name="members_page" value="{{ request()->get('members_page', 1) }}">
                            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                <div style="flex:1; min-width:200px; position:relative;">
                                    <i class="fas fa-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--profile-faint); font-size:14px;"></i>
                                    <input type="text" name="uuid" value="{{ request('uuid') }}" placeholder="{{ __('Search by UUID...') }}"
                                        style="width:100%; padding:10px 14px 10px 40px; border:2px solid var(--card-border); border-radius:12px; font-size:14px; outline:none; transition:border 0.3s; background:var(--card-bg); font-family:'Inter',sans-serif;"
                                        onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--card-border)'">
                                </div>
                                <button type="submit" style="padding:10px 20px; background:var(--gradient-1); color:var(--on-color); border:none; border-radius:12px; font-weight:700; font-size:13px; cursor:pointer; display:flex; align-items:center; gap:6px; transition:all 0.3s; box-shadow:0 4px 12px rgba(0,0,0,0.18);">
                                    <i class="fas fa-search"></i> {{ __('Search') }}
                                </button>
                                <a href="{{ url('admin/agencies/' . $agency->id. '?tab=members') }}" style="padding:10px 20px; background:var(--card-bg); color:var(--profile-muted); border:2px solid var(--card-border); border-radius:12px; font-weight:600; font-size:13px; cursor:pointer; display:flex; align-items:center; gap:6px; text-decoration:none; transition:all 0.3s;">
                                    <i class="fas fa-redo"></i> {{ __('Reset') }}
                                </a>
                            </div>
                        </form>
                    </div>

                    @if($members && $members->count())
                        <div class="table-responsive" style="padding:0;">
                            <table class="data-table" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th style="width:50px; text-align:center;">#</th>
                                        <th><i class="fas fa-user" style="color:var(--info);margin-inline-end:6px;"></i>{{ __('Member') }}</th>
                                        <th><i class="fas fa-film" style="color:var(--danger);margin-inline-end:6px;"></i>{{ __('Reals') }}</th>
                                        <th><i class="fas fa-images" style="color:var(--accent);margin-inline-end:6px;"></i>{{ __('Moments') }}</th>
                                        <th><i class="fas fa-clock" style="color:var(--danger);margin-inline-end:6px;"></i>{{ __('Live Hours') }}</th>
                                        <th><i class="fas fa-gem" style="color:var(--success);margin-inline-end:6px;"></i>{{ __('Monthly DI') }}</th>
                                        <th><i class="fas fa-money-bill-wave" style="color:var(--success);margin-inline-end:6px;"></i>{{ __('Salary') }}</th>
                                        <th><i class="fas fa-user-tag" style="color:var(--accent);margin-inline-end:6px;"></i>{{ __('Role') }}</th>
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
                                            $showUrl = $member ? url("admin/users/{$member->id}") : "#";
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
                                            <td style="text-align:center; font-weight:600; color:var(--profile-faint);">{{ $index + 1 + (($members->currentPage() - 1) * $members->perPage()) }}</td>
                                            <td>
                                                <a href='{{$showUrl}}' style='text-decoration:none; color:inherit; display:flex; align-items:center; gap:12px;'>
                                                    <div style="width:42px; height:42px; border-radius:50%; overflow:hidden; border:2px solid var(--card-border); flex-shrink:0;">
                                                        <img src="{{ getImagePath(@$member->profile->avatar) }}" alt="{{ $member->name }}" style="width:100%; height:100%; object-fit:cover;">
                                                    </div>
                                                    <div>
                                                        <div style="font-weight:700; font-size:13px; color:var(--profile-heading);">{{ @$member->name ?? '' }}</div>
                                                        <div style="font-size:11px; color:var(--profile-faint);">UID: {{ @$member->uuid ?? '' }}</div>
                                                    </div>
                                                </a>
                                            </td>
                                            <td>
                                                <div style="font-size:12px; line-height:1.8;">
                                                    <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--success);">▲</span> <b>{{ $reelUpload }}</b></div>
                                                    <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--danger);">♥</span> {{ $reelLikes }}</div>
                                                    <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--info);">💬</span> {{ $reelComments }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size:12px; line-height:1.8;">
                                                    <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--success);">▲</span> <b>{{ $momentUpload }}</b></div>
                                                    <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--danger);">♥</span> {{ $momentLikes }}</div>
                                                    <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--info);">💬</span> {{ $momentComments }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="background:var(--chip-warning-bg); color:var(--chip-warning-fg); padding:4px 10px; border-radius:8px; font-weight:700; font-size:12px;">
                                                    <i class="fas fa-clock" style="font-size:10px;"></i> {{ $member->getLiveTimeThisMonth() }}
                                                </span>
                                            </td>
                                            <td>
                                                <span style="background:var(--chip-success-bg); color:var(--chip-success-fg); padding:4px 10px; border-radius:8px; font-weight:700; font-size:12px;">
                                                    <i class="fas fa-gem" style="font-size:10px;"></i> {{ number_format($member->monthly_diamond_received ?? 0) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span style="background:var(--chip-info-bg); color:var(--chip-info-fg); padding:4px 10px; border-radius:8px; font-weight:700; font-size:12px;">
                                                    {{ $member->salary_by_latest_join ?? 0 }}
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display:flex; flex-direction:column; gap:6px; align-items:flex-start;">
                                                @if($isOwner)
                                                    <span class="role-badge owner"><i class="fas fa-crown" style="margin-inline-end:4px;"></i>{{ __('Owner') }}</span>
                                                @elseif($isAdmin)
                                                    @if (\Encore\Admin\Facades\Admin::user()->can('remove-admin-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                                        <button class="btn-action remove-admin-btn btn-danger" style="background:var(--chip-danger-bg)!important; font-size:11px; padding:5px 12px; border-radius:8px;" data-id="{{ $member->id }}">
                                                            <i class="fas fa-user-minus"></i> {{ __('remove_admin') }}
                                                        </button>
                                                    @endif
                                                    @if (\Encore\Admin\Facades\Admin::user()->can('kick-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                                        <button class="btn-action kick-member-btn" style="background:var(--chip-warning-bg)!important; font-size:11px; padding:5px 12px; border-radius:8px;" data-id="{{ $member->id }}">
                                                            <i class="fas fa-sign-out-alt"></i> {{ __('kick') }}
                                                        </button>
                                                    @endif
                                                @else
                                                    @if (\Encore\Admin\Facades\Admin::user()->can('make-admin-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                                        <button class="btn-action make-admin-btn" style="background:var(--gradient-1)!important; font-size:11px; padding:5px 12px; border-radius:8px;" data-id="{{ $member->id }}">
                                                            <i class="fas fa-user-shield"></i> {{ __('Make Admin') }}
                                                        </button>
                                                    @endif
                                                    @if (\Encore\Admin\Facades\Admin::user()->can('kick-switch-' . 'agencies') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                                        <button class="btn-action kick-member-btn" style="background:var(--chip-warning-bg)!important; font-size:11px; padding:5px 12px; border-radius:8px;" data-id="{{ $member->id }}">
                                                            <i class="fas fa-sign-out-alt"></i> {{ __('kick') }}
                                                        </button>
                                                    @endif
                                                @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="padding:16px 24px; display:flex; justify-content:center; background:var(--profile-subtle-bg); border-top:1px solid var(--profile-hairline);">
                            {{ $members?->appends([
                                        'charges_page' => $charges?->currentPage(),
                                        'salaries_page' => $salaries?->currentPage(),
                                        'join_page' => $agencyJoinRequests?->currentPage(),
                                        'target_page' => $memberTargets?->currentPage(),
                                    ])->links('vendor.pagination.default') }}
                        </div>
                    @else
                        <div style="padding:60px 24px; text-align:center;">
                            <div style="width:80px; height:80px; border-radius:50%; background:var(--profile-subtle-bg); display:inline-flex; align-items:center; justify-content:center; margin-bottom:16px;">
                                <i class="fas fa-users-slash" style="font-size:32px; color:var(--profile-faint);"></i>
                            </div>
                            <p style="font-size:16px; font-weight:600; color:var(--profile-muted); margin:0 0 4px;">{{ __('No members found') }}</p>
                            <p style="font-size:13px; color:var(--profile-faint); margin:0;">{{ __('Members will appear here once they join the agency') }}</p>
                        </div>
                    @endif
                </div>
            </div>
         @endif
        <!-- Charges Section -->

        
        <div class="tab-content" id="charges-tab">
            <div class="card" style="border:none;">
                <!-- Card Header with gradient accent -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:2px solid var(--profile-hairline); background:var(--profile-subtle-bg);">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:42px; height:42px; border-radius:12px; background:var(--gradient-5); display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-credit-card" style="color:var(--on-color); font-size:18px;"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:18px; font-weight:800; color:var(--profile-heading);">{{ __('Charge History') }}</h3>
                            <span style="font-size:12px; color:var(--profile-muted);">{{ __('Track all agency charge transactions') }}</span>
                        </div>
                    </div>
                    <span style="background:var(--gradient-5); color:var(--on-color); padding:6px 16px; border-radius:50px; font-size:13px; font-weight:700;">
                        {{ optional($charges)->total() ?? 0 }} {{ __('Records') }}
                    </span>
                </div>

                @if($charges && $charges->count())
                <div class="table-responsive" style="padding:0;">
                    <table class="data-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th style="width:60px; text-align:center;">#</th>
                                <th><i class="fas fa-user-check" style="color:var(--info);margin-inline-end:6px;"></i>{{ __('receiver') }}</th>
                                <th><i class="fas fa-coins" style="color:var(--warning);margin-inline-end:6px;"></i>{{ __('coins') }}</th>
                                <th><i class="fas fa-dollar-sign" style="color:var(--success);margin-inline-end:6px;"></i>{{ __('USD') }}</th>
                                <th><i class="fas fa-calendar-alt" style="color:var(--accent);margin-inline-end:6px;"></i>{{ __('Created at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($charges as $index => $charge)
                                @php
                                    $receiver = \App\Helpers\Common::getReceiverInfo($charge);
                                    $name = $receiver['name'] ?? '-';
                                    $uid = $receiver['uuid'] ?? '-';
                                    $image = getImagePath($receiver['image']) ?? asset('default-user.png');
                                @endphp
                                <tr>
                                    <td style="text-align:center; font-weight:600; color:var(--profile-faint);">{{ $charge->id }}</td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <div style="width:38px; height:38px; border-radius:50%; overflow:hidden; border:2px solid var(--card-border); flex-shrink:0;">
                                                <img src="{{ $image }}" style="width:100%; height:100%; object-fit:cover;">
                                            </div>
                                            <div>
                                                <div style="font-weight:700; font-size:13px; color:var(--profile-heading);">{{ $name }}</div>
                                                <div style="font-size:11px; color:var(--profile-faint);">UID: {{ $uid }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="background:var(--chip-warning-bg); color:var(--chip-warning-fg); padding:4px 12px; border-radius:8px; font-weight:700; font-size:12px;">
                                            <i class="fas fa-coins" style="font-size:10px;"></i> {{ number_format($charge->amount) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="background:var(--chip-success-bg); color:var(--chip-success-fg); padding:4px 12px; border-radius:8px; font-weight:700; font-size:12px;">
                                            $ {{ number_format($charge->usd, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size:12px; color:var(--profile-muted);">
                                            <i class="fas fa-clock" style="color:var(--profile-faint); font-size:10px; margin-inline-end:4px;"></i>
                                            {{ \Carbon\Carbon::parse($charge->created_at)->format('Y-m-d H:i') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:16px 24px; display:flex; justify-content:center; background:var(--profile-subtle-bg); border-top:1px solid var(--profile-hairline);">
                    {{ $members?->appends([
                                'charges_page' => $charges?->currentPage(),
                                'salaries_page' => $salaries?->currentPage(),
                                'join_page' => $agencyJoinRequests?->currentPage(),
                                'target_page' => $memberTargets?->currentPage(),
                            ])->links('vendor.pagination.default') }}
                </div>
                @else
                    <div style="padding:60px 24px; text-align:center;">
                        <div style="width:80px; height:80px; border-radius:50%; background:var(--chip-warning-bg); display:inline-flex; align-items:center; justify-content:center; margin-bottom:16px;">
                            <i class="fas fa-credit-card" style="font-size:32px; color:var(--chip-warning-fg);"></i>
                        </div>
                        <p style="font-size:16px; font-weight:600; color:var(--profile-muted); margin:0 0 4px;">{{ __('No charge history') }}</p>
                        <p style="font-size:13px; color:var(--profile-faint); margin:0;">{{ __('Charge transactions will appear here') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- salary Section -->
        <div class="tab-content" id="salary-tab">
            <div class="card" style="border:none;">
                <!-- Card Header with gradient accent -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:2px solid var(--profile-hairline); background:var(--profile-subtle-bg);">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:42px; height:42px; border-radius:12px; background:var(--gradient-4); display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-money-bill-wave" style="color:var(--on-color); font-size:18px;"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:18px; font-weight:800; color:var(--profile-heading);">{{ __('salary') }}</h3>
                            <span style="font-size:12px; color:var(--profile-muted);">{{ __('Agency salary records and withdrawals') }}</span>
                        </div>
                    </div>
                    <span style="background:var(--gradient-4); color:var(--profile-heading); padding:6px 16px; border-radius:50px; font-size:13px; font-weight:700;">
                        {{ optional($salaries)->total() ?? 0 }} {{ __('Records') }}
                    </span>
                </div>

                @if($salaries && $salaries->count())
                <div class="table-responsive" style="padding:0;">
                    <table class="data-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th style="width:60px; text-align:center;">#</th>
                                <th><i class="fas fa-hand-holding-usd" style="color:var(--success);margin-inline-end:6px;"></i>{{ __('net salary') }}</th>
                                <th><i class="fas fa-money-bill" style="color:var(--success);margin-inline-end:6px;"></i>{{ __('salary') }}</th>
                                <th><i class="fas fa-money-check-alt" style="color:var(--danger);margin-inline-end:6px;"></i>{{ __('withdrawal') }}</th>
                                <th><i class="fas fa-bullseye" style="color:var(--info);margin-inline-end:6px;"></i>{{ __('Target') }}</th>
                                <th><i class="fas fa-calendar" style="color:var(--accent);margin-inline-end:6px;"></i>{{ __('month') }}</th>
                                <th><i class="fas fa-calendar-check" style="color:var(--warning);margin-inline-end:6px;"></i>{{ __('year') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salaries as $index => $salary)
                                <tr>
                                    <td style="text-align:center; font-weight:600; color:var(--profile-faint);">{{ $index + 1 + (($salaries->currentPage() - 1) * $salaries->perPage()) }}</td>
                                    <td>
                                        <span style="background:var(--chip-success-bg); color:var(--chip-success-fg); padding:5px 14px; border-radius:8px; font-weight:700; font-size:13px;">
                                            {{ truncateAndTrim(@$salary->sallary - $salary->cut_amount) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="background:var(--chip-info-bg); color:var(--chip-info-fg); padding:5px 14px; border-radius:8px; font-weight:700; font-size:13px;">
                                            {{ truncateAndTrim(@$salary->sallary) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="background:var(--chip-danger-bg); color:var(--chip-danger-fg); padding:5px 14px; border-radius:8px; font-weight:700; font-size:13px;">
                                            {{ truncateAndTrim(@$salary->cut_amount) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="background:var(--chip-info-bg); color:var(--chip-info-fg); padding:5px 14px; border-radius:8px; font-weight:700; font-size:13px;">
                                            <i class="fas fa-gem" style="font-size:10px;"></i> {{ @$sumTargets }}
                                        </span>
                                    </td>
                                    <td style="font-weight:600; color:var(--meta-value);">{{ @$salary->month ?? '' }}</td>
                                    <td style="font-weight:600; color:var(--meta-value);">{{ @$salary->year ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:16px 24px; display:flex; justify-content:center; background:var(--profile-subtle-bg); border-top:1px solid var(--profile-hairline);">
                    @if($salaries)
                        {{ $salaries->appends([
                                'charges_page' => $charges?->currentPage(),
                                'join_page' => $agencyJoinRequests?->currentPage(),
                                'members_page' => $members?->currentPage(),
                                'target_page' => $memberTargets?->currentPage(),
                            ])->links('vendor.pagination.bootstrap-4') }}
                    @endif
                </div>
                @else
                    <div style="padding:60px 24px; text-align:center;">
                        <div style="width:80px; height:80px; border-radius:50%; background:var(--chip-success-bg); display:inline-flex; align-items:center; justify-content:center; margin-bottom:16px;">
                            <i class="fas fa-money-bill-wave" style="font-size:32px; color:var(--chip-success-fg);"></i>
                        </div>
                        <p style="font-size:16px; font-weight:600; color:var(--profile-muted); margin:0 0 4px;">{{ __('No salary records') }}</p>
                        <p style="font-size:13px; color:var(--profile-faint); margin:0;">{{ __('Salary data will appear here') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="tab-content" id="requests-tab">
            <div class="card" style="border:none;">
                <!-- Card Header with gradient accent -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:2px solid var(--profile-hairline); background:var(--profile-subtle-bg);">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:42px; height:42px; border-radius:12px; background:var(--gradient-1); display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-user-plus" style="color:var(--on-color); font-size:18px;"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:18px; font-weight:800; color:var(--profile-heading);">{{ __('Agency join request') }}</h3>
                            <span style="font-size:12px; color:var(--profile-muted);">{{ __('Review and manage join requests') }}</span>
                        </div>
                    </div>
                    <span style="background:var(--gradient-1); color:var(--on-color); padding:6px 16px; border-radius:50px; font-size:13px; font-weight:700;">
                        {{ optional($agencyJoinRequests)->total() ?? 0 }} {{ __('Requests') }}
                    </span>
                </div>

                @if($agencyJoinRequests && $agencyJoinRequests->count())
                <div class="table-responsive" style="padding:0;">
                    <table class="data-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th style="width:60px; text-align:center;">#</th>
                                <th><i class="fas fa-user" style="color:var(--info);margin-inline-end:6px;"></i>{{ __('User') }}</th>
                                <th><i class="fab fa-whatsapp" style="color:var(--success);margin-inline-end:6px;"></i>{{ __('WhatsApp') }}</th>
                                <th><i class="fas fa-globe-americas" style="color:var(--danger);margin-inline-end:6px;"></i>{{ __('Country') }}</th>
                                <th><i class="fas fa-bolt" style="color:var(--warning);margin-inline-end:6px;"></i>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                    $showUrl = $agencyJoinRequest->user ? url("admin/users/{$agencyJoinRequest->user->id}") : "#";
                                @endphp
                                <tr>
                                    <td style="text-align:center; font-weight:600; color:var(--profile-faint);">{{ $agencyJoinRequests->firstItem() + $index }}</td>
                                    <td>
                                        <a href='{{$showUrl}}' style='text-decoration:none; color:inherit; display:flex; align-items:center; gap:12px;'>
                                            {!! $image !!}
                                            <div>
                                                <div style="font-weight:700; font-size:13px; color:var(--profile-heading);">{{ $name }}</div>
                                                <div style="font-size:11px; color:var(--profile-faint);">UID: {{ $uid }}</div>
                                            </div>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="https://wa.me/{{ $agencyJoinRequest->user->phone }}" target="_blank" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px; background:var(--chip-success-bg); color:var(--chip-success-fg); padding:6px 14px; border-radius:10px; font-weight:600; font-size:12px;">
                                            <i class="fab fa-whatsapp" style="font-size:16px;"></i> {{ $agencyJoinRequest->user->phone }}
                                        </a>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            @if($countryFlag)
                                                <img src="{{ $countryFlag }}" alt="Flag" width="22" height="16" style="border-radius:3px; box-shadow:0 1px 3px rgba(0,0,0,0.15);">
                                            @endif
                                            <span style="font-weight:600; font-size:13px; color:var(--meta-value);">{{ $countryName }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                            <button class="accept-btn" data-id="{{ $agencyJoinRequest->id }}"
                                                style="padding:6px 14px; background:var(--gradient-4); color:var(--profile-heading); border:none; border-radius:8px; font-weight:700; font-size:11px; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all 0.3s; box-shadow:0 2px 8px rgba(0,0,0,0.18);">
                                                <i class="fas fa-check"></i> {{ __('Accept') }}
                                            </button>
                                            <button class="reject-btn" data-id="{{ $agencyJoinRequest->id }}"
                                                style="padding:6px 14px; background:var(--chip-danger-bg); color:var(--on-color); border:none; border-radius:8px; font-weight:700; font-size:11px; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all 0.3s; box-shadow:0 2px 8px rgba(0,0,0,0.18);">
                                                <i class="fas fa-times"></i> {{ __('Reject') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:16px 24px; display:flex; justify-content:center; background:var(--profile-subtle-bg); border-top:1px solid var(--profile-hairline);">
                    @if($agencyJoinRequests)
                        {{ $agencyJoinRequests->appends([
                                'members_page' => $members ? $members->currentPage() : 1,
                                'salaries_page' => $salaries ? $salaries->currentPage() : 1,
                                'charges_page' => $charges ? $charges->currentPage() : 1,
                                'target_page' => $memberTargets ? $memberTargets->currentPage() : 1,
                            ])->links('vendor.pagination.bootstrap-4') }}
                    @endif
                </div>
                @else
                    <div style="padding:60px 24px; text-align:center;">
                        <div style="width:80px; height:80px; border-radius:50%; background:var(--chip-info-bg); display:inline-flex; align-items:center; justify-content:center; margin-bottom:16px;">
                            <i class="fas fa-user-plus" style="font-size:32px; color:var(--chip-info-fg);"></i>
                        </div>
                        <p style="font-size:16px; font-weight:600; color:var(--profile-muted); margin:0 0 4px;">{{ __('No join requests') }}</p>
                        <p style="font-size:13px; color:var(--profile-faint); margin:0;">{{ __('New join requests will appear here') }}</p>
                    </div>
                @endif
            </div>
        </div>

    <div class="tab-content" id="gift-log-tab">
        <div class="card" style="border:none;">
            <!-- Card Header -->
            <div style="display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:2px solid var(--profile-hairline); background:var(--profile-subtle-bg);">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:42px; height:42px; border-radius:12px; background:var(--gradient-2); display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-gift" style="color:var(--on-color); font-size:18px;"></i>
                    </div>
                    <div>
                        <h3 style="margin:0; font-size:18px; font-weight:800; color:var(--profile-heading);">{{ __('gift Reports') }}</h3>
                        <span style="font-size:12px; color:var(--profile-muted);">{{ __('Track gift transactions') }}</span>
                    </div>
                </div>
            </div>

            <div style="padding:20px 24px;">
                <!-- Search Bar -->
                <div style="background:var(--profile-subtle-bg); border-radius:14px; padding:18px; border:1px solid var(--card-border); margin-bottom:20px;">
                    <form action="{{ url('admin/agencies/' . $agency->id) }}" class="form-horizontal gift-log-form" method="GET" pjax-container>
                        <input type="hidden" name="tab" value="gift-log">
                        <input type="hidden" name="gift_page" value="{{ request()->get('gift_page', 1) }}">
                        <div style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
                            <div style="flex:1; min-width:140px;">
                                <label style="font-size:11px; font-weight:700; color:var(--profile-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; display:block;"><i class="fas fa-fingerprint" style="margin-inline-end:4px;"></i>UUID</label>
                                <input type="text" name="uuid" value="{{ request('uuid') }}" placeholder="Enter UUID..."
                                    style="width:100%; padding:8px 12px; border:2px solid var(--card-border); border-radius:10px; font-size:13px; font-family:'Inter',sans-serif; outline:none; background:var(--card-bg);">
                            </div>
                            <div style="flex:1; min-width:140px;">
                                <label style="font-size:11px; font-weight:700; color:var(--profile-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; display:block;"><i class="fas fa-calendar" style="margin-inline-end:4px;"></i>{{ __('From Date') }}</label>
                                <input type="date" name="start_at" value="{{ request('start_at') }}"
                                    style="width:100%; padding:8px 12px; border:2px solid var(--card-border); border-radius:10px; font-size:13px; font-family:'Inter',sans-serif; outline:none; background:var(--card-bg);">
                            </div>
                            <div style="flex:1; min-width:140px;">
                                <label style="font-size:11px; font-weight:700; color:var(--profile-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; display:block;"><i class="fas fa-calendar-check" style="margin-inline-end:4px;"></i>{{ __('To Date') }}</label>
                                <input type="date" name="end_at" value="{{ request('end_at') }}"
                                    style="width:100%; padding:8px 12px; border:2px solid var(--card-border); border-radius:10px; font-size:13px; font-family:'Inter',sans-serif; outline:none; background:var(--card-bg);">
                            </div>
                            <div style="display:flex; gap:6px;">
                                <button type="submit" style="padding:8px 18px; background:var(--gradient-1); color:var(--on-color); border:none; border-radius:10px; font-weight:700; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:5px; box-shadow:0 3px 10px rgba(0,0,0,0.18);">
                                    <i class="fas fa-search"></i> {{ __('Search') }}
                                </button>
                                <a href="{{ url('admin/agencies/' . $agency->id.'?tab=gift-log') }}" style="padding:8px 14px; background:var(--card-bg); color:var(--profile-muted); border:2px solid var(--card-border); border-radius:10px; font-weight:600; font-size:12px; text-decoration:none; display:inline-flex; align-items:center;">
                                    <i class="fas fa-redo"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Diamond Summary -->
                <div class="diamond-summary-container">
                    <div class="diamond-summary-box">
                        <div class="diamond-title">{{ __('total diamonds') }}</div>
                        <div class="diamond-count">
                            <span>{{ number_format(@$diamonds) }}</span>
                            <div class="diamond-icon-container">
                                <img src="{{ asset('images/diamond.jpg') }}" alt="Diamond" class="diamond-icon">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive" style="margin-top:16px;">
                    <table class="data-table" style="margin:0;">
                        <thead>
                        <tr>
                            <th style="width:60px; text-align:center;">#</th>
                            <th><i class="fas fa-paper-plane" style="color:var(--info);margin-inline-end:6px;"></i>{{ __('Sender') }}</th>
                            <th><i class="fas fa-user-check" style="color:var(--success);margin-inline-end:6px;"></i>{{ __('Receiver') }}</th>
                            <th><i class="fas fa-door-open" style="color:var(--danger);margin-inline-end:6px;"></i>{{ __('room') }}</th>
                            <th><i class="fas fa-gift" style="color:var(--danger);margin-inline-end:6px;"></i>{{ __('gift') }}</th>
                            <th><i class="fas fa-sort-numeric-up" style="color:var(--accent);margin-inline-end:6px;"></i>{{ __('quantity') }}</th>
                            <th><i class="fas fa-tag" style="color:var(--warning);margin-inline-end:6px;"></i>{{ __('price') }}</th>
                            <th><i class="fas fa-calendar-alt" style="color:var(--accent);margin-inline-end:6px;"></i>{{ __('Created at') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($giftSLogs ?? [] as $index => $giftSLog)
                            @php
                                $userImageDefault = asset('images/businessman-icon.jpg');
                                $defaultImage = asset("images/background_room.jpg");

                                $userSender =  $giftSLog->sender;
                                $name = @$userSender->name ?? '';
                                $uid = @$userSender->uuid ?? '';
                                $id = @$userSender->id ?? 0;
                               $receiver = $giftSLog->receiver;
                                $receiverName = @$receiver->name ?? '';
                                $receiverUid = @$receiver->uuid ?? '';
                                $receiverId = @$receiver->id ?? 0;

                                $avatar = @$userSender->profile->avatar;
                                $image = getImagePath($avatar) ?? $userImageDefault;
                                $receiverAvatar = @$receiver->profile->avatar;

                                $receiverImage = getImagePath($receiverAvatar) ?? $userImageDefault;
                                if (!isImageExists($receiverImage)) {
                                    $receiverImage = $userImageDefault;
                                }
                                if (!isImageExists($image)) {
                                    $image = $userImageDefault;
                                }

                                $roomName = @$giftSLog->room->room_name ?? '-';
                                $path = @$giftSLog->room->room_cover;
                                $url = getImagePath($path) ?? $defaultImage;
                                if (!isImageExists($url)) {
                                    $url = $defaultImage;
                                }

                                $giftName = app()->getLocale() == 'ar'
                                    ? (@$giftSLog->gift->name ?? '')
                                    : (@$giftSLog->gift->e_name ?? '');
                            @endphp

                            <tr>
                                <td style="text-align:center; font-weight:600; color:var(--profile-faint);">{{ $giftSLog->id }}</td>
                                <td>
                                    <a href="{{ url('admin/users/' . $id) }}" target="_blank" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px;">
                                        <div style="width:36px; height:36px; border-radius:50%; overflow:hidden; border:2px solid var(--card-border); flex-shrink:0;">
                                            <img src="{{ $image }}" style="width:100%; height:100%; object-fit:cover;">
                                        </div>
                                        <div>
                                            <div style="font-weight:700; font-size:12px; color:var(--profile-heading);">{{ $name }}</div>
                                            <div style="font-size:10px; color:var(--profile-faint);">{{ $uid }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ url('admin/users/' .$receiverId) }}" target="_blank" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px;">
                                        <div style="width:36px; height:36px; border-radius:50%; overflow:hidden; border:2px solid var(--card-border); flex-shrink:0;">
                                            <img src="{{ $receiverImage }}" style="width:100%; height:100%; object-fit:cover;">
                                        </div>
                                        <div>
                                            <div style="font-weight:700; font-size:12px; color:var(--profile-heading);">{{ $receiverName }}</div>
                                            <div style="font-size:10px; color:var(--profile-faint);">{{ $receiverUid }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="width:28px; height:28px; border-radius:8px; overflow:hidden; flex-shrink:0;">
                                            <img src="{{ $url }}" style="width:100%; height:100%; object-fit:cover;">
                                        </div>
                                        <div>
                                            <div style="font-weight:600; font-size:12px;">{{ $roomName }}</div>
                                            <div style="font-size:10px; color:var(--profile-faint);">{{ $giftSLog->room->type ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ url('admin/gifts/' . @$giftSLog->gift->id) }}" target="_blank" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:8px;">
                                        <div style="width:28px; height:28px; border-radius:8px; overflow:hidden; flex-shrink:0;">
                                            <img src="{{ getImagePath($giftSLog->gift->img ?? '') }}" style="width:100%; height:100%; object-fit:cover;">
                                        </div>
                                        <div>
                                            <div style="font-weight:600; font-size:12px;">{{ $giftName }}</div>
                                            <div style="font-size:10px; color:var(--profile-faint);">#{{ $giftSLog->gift->id ?? 0 }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td><span style="background:var(--meta-chip-bg); padding:3px 10px; border-radius:8px; font-weight:700; font-size:12px;">{{ $giftSLog->giftNum }}</span></td>
                                <td><span style="background:var(--chip-success-bg); color:var(--chip-success-fg); padding:3px 10px; border-radius:8px; font-weight:700; font-size:12px;"><i class="fas fa-gem" style="font-size:9px;"></i> {{ $giftSLog->giftPrice }}</span></td>
                                <td><span style="font-size:12px; color:var(--profile-muted);"><i class="fas fa-clock" style="color:var(--profile-faint); font-size:10px; margin-inline-end:3px;"></i>{{ \Carbon\Carbon::parse($giftSLog->created_at)->format('Y-m-d H:i') }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($giftSLogs instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div style="padding:16px 0; display:flex; justify-content:center;">
                        {{ $giftSLogs->appends([
                            'tab' => 'gift-log',
                            'start_at' => request('start_at'),
                            'end_at' => request('end_at'),
                            'gift_page' => $giftSLogs->currentPage()
                        ])->links('vendor.pagination.bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

        <div class="tab-content" id="targets-tab">
            <div class="card" style="border:none;">
                <!-- Card Header -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:2px solid var(--profile-hairline); background:var(--profile-subtle-bg);">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:42px; height:42px; border-radius:12px; background:var(--gradient-2); display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-bullseye" style="color:var(--on-color); font-size:18px;"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:18px; font-weight:800; color:var(--profile-heading);">{{ __('Agency Targets') }}</h3>
                            <span style="font-size:12px; color:var(--profile-muted);">{{ __('Track member targets and performance') }}</span>
                        </div>
                    </div>
                    <span style="background:var(--gradient-2); color:var(--on-color); padding:6px 16px; border-radius:50px; font-size:13px; font-weight:700;">
                        {{ $memberTargets ? $memberTargets->total() : 0 }} {{ __('Members') }}
                    </span>
                </div>

                <div style="padding:20px 24px;">
                    <!-- Filter & Stats Row -->
                    <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:24px;">
                        <!-- Filter Card -->
                        <div style="flex:1; min-width:280px; background:var(--profile-subtle-bg); border-radius:14px; padding:18px; border:1px solid var(--card-border);">
                            <form method="GET" action="{{ url('admin/agencies/profile/' . $agency->id) }}">
                                <input type="hidden" name="tab" value="targets">
                                <div style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
                                    <div style="flex:1; min-width:120px;">
                                        <label style="font-size:11px; font-weight:700; color:var(--profile-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; display:block;"><i class="fas fa-calendar" style="margin-inline-end:4px;"></i>{{ __('Month') }}</label>
                                        <select name="month" style="width:100%; padding:8px 12px; border:2px solid var(--card-border); border-radius:10px; font-size:13px; font-family:'Inter',sans-serif; outline:none; background:var(--card-bg);">
                                            <option value="">All Months</option>
                                            @for($m = 1; $m <= 12; $m++)
                                                <option value="{{ $m }}" {{ request('month', now()->month) == $m ? 'selected' : '' }}>
                                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div style="flex:1; min-width:120px;">
                                        <label style="font-size:11px; font-weight:700; color:var(--profile-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; display:block;"><i class="fas fa-calendar-alt" style="margin-inline-end:4px;"></i>{{ __('Year') }}</label>
                                        <select name="year" style="width:100%; padding:8px 12px; border:2px solid var(--card-border); border-radius:10px; font-size:13px; font-family:'Inter',sans-serif; outline:none; background:var(--card-bg);">
                                            <option value="">All Years</option>
                                            @for($y = now()->year; $y >= 2020; $y--)
                                                <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div style="display:flex; gap:6px;">
                                        <button type="submit" style="padding:8px 18px; background:var(--gradient-1); color:var(--on-color); border:none; border-radius:10px; font-weight:700; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:5px; box-shadow:0 3px 10px rgba(0,0,0,0.18);">
                                            <i class="fas fa-filter"></i> {{ __('Apply') }}
                                        </button>
                                        @if(request()->has('month') || request()->has('year'))
                                            <a href="{{ url('admin/agencies/profile/' . $agency->id.'?tab=targets') }}" style="padding:8px 14px; background:var(--card-bg); color:var(--profile-muted); border:2px solid var(--card-border); border-radius:10px; font-weight:600; font-size:12px; text-decoration:none; display:inline-flex; align-items:center;">
                                                <i class="fas fa-redo"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Stat Cards -->
                        <div style="display:flex; gap:12px; flex-wrap:wrap;">
                            <div style="background:var(--card-bg); border-radius:14px; padding:16px 22px; border:1px solid var(--card-border); display:flex; align-items:center; gap:14px; min-width:160px;">
                                <div style="width:44px; height:44px; border-radius:12px; background:var(--gradient-3); display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-bullseye" style="color:var(--on-color); font-size:18px;"></i>
                                </div>
                                <div>
                                    <div style="font-size:20px; font-weight:800; background:var(--gradient-3); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">{{ truncateAndTrim($agencyTarget) }}</div>
                                    <div style="font-size:11px; color:var(--profile-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">{{ __('Target') }}</div>
                                </div>
                            </div>
                            <div style="background:var(--card-bg); border-radius:14px; padding:16px 22px; border:1px solid var(--card-border); display:flex; align-items:center; gap:14px; min-width:160px;">
                                <div style="width:44px; height:44px; border-radius:12px; background:var(--gradient-4); display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-chart-line" style="color:var(--on-color); font-size:18px;"></i>
                                </div>
                                <div>
                                    <div style="font-size:20px; font-weight:800; background:var(--gradient-4); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">{{ $rate }}</div>
                                    <div style="font-size:11px; color:var(--profile-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">{{ __('Agency Rate') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stars & Heroes -->
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
                        <div style="background:var(--profile-subtle-bg); border-radius:14px; padding:16px; border:1px solid var(--card-border);">
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                                <i class="fas fa-star" style="color:var(--warning); font-size:16px;"></i>
                                <h4 style="margin:0; font-size:14px; font-weight:700; color:var(--profile-heading);">{{ __('Agency Stars') }}</h4>
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
                                            $userUrl = $user ? route('admin.users.show', $user->id) : '#';
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
                                <div class="empty-state"><i class="fas fa-star" style="color:var(--profile-faint);"></i><p>{{ __('No stars data available') }}</p></div>
                            @endif
                        </div>

                        <div style="background:var(--profile-subtle-bg); border-radius:14px; padding:16px; border:1px solid var(--card-border);">
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                                <i class="fas fa-shield-alt" style="color:var(--danger); font-size:16px;"></i>
                                <h4 style="margin:0; font-size:14px; font-weight:700; color:var(--profile-heading);">{{ __('Agency Heroes') }}</h4>
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
                                            $userUrl = $user ? route('admin.users.show', $user->id) : '#';
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
                                <div class="empty-state"><i class="fas fa-shield-alt" style="color:var(--profile-faint);"></i><p>{{ __('No heroes available') }}</p></div>
                            @endif
                        </div>
                    </div>

                    <!-- Targets Table -->
                    <div class="card" style="border:1px solid var(--card-border); margin-bottom:0;">
                        <div class="table-responsive" style="padding:0;">
                            <table class="data-table" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th style="width:50px; text-align:center;">#</th>
                                        <th><i class="fas fa-user" style="color:var(--info);margin-inline-end:6px;"></i>{{ __('User') }}</th>
                                        <th><i class="fas fa-gem" style="color:var(--success);margin-inline-end:6px;"></i>{{ __('Diamonds') }}</th>
                                        <th><i class="fas fa-hourglass-half" style="color:var(--danger);margin-inline-end:6px;"></i>{{ __('Remaining') }}</th>
                                        <th><i class="fas fa-calendar-day" style="color:var(--warning);margin-inline-end:6px;"></i>{{ __('Days') }}</th>
                                        <th><i class="fas fa-clock" style="color:var(--accent);margin-inline-end:6px;"></i>{{ __('Hours') }}</th>
                                        <th><i class="fas fa-images" style="color:var(--danger);margin-inline-end:6px;"></i>{{ __('Moments') }}</th>
                                        <th><i class="fas fa-film" style="color:var(--success);margin-inline-end:6px;"></i>{{ __('Reels') }}</th>
                                        <th><i class="fas fa-hands-helping" style="color:var(--info);margin-inline-end:6px;"></i>{{ __('Supporters') }}</th>
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
                                                if (!isImageExists($avatarUrl)) { $avatarUrl = $defaultImage; }
                                                $month = request('month') ?? now()->month;
                                                $year = request('year') ?? now()->year;
                                                $giftLogs = \App\Models\GiftLog::where('agency_id', $memberTarget->agency_id)
                                                    ->where('receiver_id', $memberTarget->id)->whereHas('sender')->with('sender.profile')
                                                    ->whereYear('created_at', $year)->whereMonth('created_at', $month)
                                                    ->selectRaw("sum(giftPrice) as exp, sender_id")->groupBy('sender_id')
                                                    ->orderByRaw("exp desc")->limit(3)->get()->reject(fn($q) => $q->exp == 0);
                                                $memberTarget->topSupporters = $giftLogs;
                                                $moment = App\Helpers\Common::getUserMediaStats($memberTarget->id, 'moment', $memberTarget->agency_id) ?? [];
                                                $reel = App\Helpers\Common::getUserMediaStats($memberTarget->id, 'reel', $memberTarget->agency_id) ?? [];
                                                $momentUpload = $moment['upload'] ?? '0/0'; $momentLikes = $moment['likes'] ?? '0/0'; $momentComments = $moment['comments'] ?? '0/0';
                                                $reelUpload = $reel['upload'] ?? '0/0'; $reelLikes = $reel['likes'] ?? '0/0'; $reelComments = $reel['comments'] ?? '0/0';
                                                $target = $memberTarget->targets->first();
                                            @endphp
                                            <tr>
                                                <td style="text-align:center; font-weight:600; color:var(--profile-faint);">{{ $memberTargets->firstItem() + $index }}</td>
                                                <td>
                                                    <div style="display:flex; align-items:center; gap:10px;">
                                                        <div style="width:38px; height:38px; border-radius:50%; overflow:hidden; border:2px solid var(--card-border); flex-shrink:0;">
                                                            <img src="{{ $avatarUrl }}" style="width:100%; height:100%; object-fit:cover;" alt="{{ $name }}">
                                                        </div>
                                                        <div>
                                                            <div style="font-weight:700; font-size:13px; color:var(--profile-heading);">{{ $name }}</div>
                                                            <div style="font-size:11px; color:var(--profile-faint);">{{ $uid }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span style="background:var(--chip-success-bg); color:var(--chip-success-fg); padding:4px 12px; border-radius:8px; font-weight:700; font-size:12px;">
                                                        <i class="fas fa-gem" style="font-size:10px;"></i> {{ $target->user_diamonds ?? 0 }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span style="background:var(--chip-warning-bg); color:var(--chip-warning-fg); padding:4px 12px; border-radius:8px; font-weight:700; font-size:12px;">
                                                        {{ $target->next_diamond ?? 0 }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span style="background:var(--meta-chip-bg); padding:4px 10px; border-radius:8px; font-weight:600; font-size:12px; color:var(--meta-value);">
                                                        {{ ($target->user_days ?? 0) }}/{{ ($target->target_days ?? 0) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span style="background:var(--meta-chip-bg); padding:4px 10px; border-radius:8px; font-weight:600; font-size:12px; color:var(--meta-value);">
                                                        {{ ($target->user_hours ?? 0) }}/{{ ($target->target_hours ?? 0) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="font-size:12px; line-height:1.8;">
                                                        <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--success);">▲</span> <b>{{ $momentUpload }}</b></div>
                                                        <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--danger);">♥</span> {{ $momentLikes }}</div>
                                                        <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--info);">💬</span> {{ $momentComments }}</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="font-size:12px; line-height:1.8;">
                                                        <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--success);">▲</span> <b>{{ $reelUpload }}</b></div>
                                                        <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--danger);">♥</span> {{ $reelLikes }}</div>
                                                        <div style="display:flex; align-items:center; gap:4px;"><span style="color:var(--info);">💬</span> {{ $reelComments }}</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="display:flex; gap:-4px; align-items:center;">
                                                        @foreach($memberTarget->topSupporters ?? [] as $supporter)
                                                            @php
                                                                $sender = $supporter->sender;
                                                                $supporterAvatar = $sender->profile->avatar ?? null;
                                                                $supporterUrl = getImagePath($supporterAvatar) ?? $defaultImage;
                                                                if (!isImageExists($supporterUrl)) { $supporterUrl = $defaultImage; }
                                                            @endphp
                                                            <img src="{{ $supporterUrl }}" style="width:30px; height:30px; border-radius:50%; object-fit:cover; border:2px solid var(--card-bg); margin-inline-start:-6px; box-shadow:0 1px 4px rgba(0,0,0,0.1);"
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
                            <div style="padding:50px 24px; text-align:center;">
                                <div style="width:70px; height:70px; border-radius:50%; background:var(--chip-danger-bg); display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px;">
                                    <i class="fas fa-bullseye" style="font-size:28px; color:var(--chip-danger-fg);"></i>
                                </div>
                                <p style="font-size:15px; font-weight:600; color:var(--profile-muted); margin:0 0 4px;">{{ __('No target data available') }}</p>
                                <p style="font-size:12px; color:var(--profile-faint); margin:0;">{{ __('Target data will appear here when available') }}</p>
                            </div>
                        @endif

                        @if($memberTargets)
                            <div style="padding:16px 24px; display:flex; justify-content:center; background:var(--profile-subtle-bg); border-top:1px solid var(--profile-hairline);">
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

            {{-- jQuery: the admin layout already bundles jQuery 2.1.4 locally.
                 The external code.jquery.com 3.6.0 tag was removed (white-label:
                 no external CDN) — the bundled jQuery drives the handlers below. --}}

            {{-- SweetAlert2 (local vendor copy). Swal.* is called in ~14 places
                 below; without this include those calls throw "Swal is not
                 defined". Loaded from the local vendor dir, not a CDN. --}}
            <link rel="stylesheet" href="{{ admin_asset('vendor/laravel-admin/sweetalert2/dist/sweetalert2.css') }}">
            <script src="{{ admin_asset('vendor/laravel-admin/sweetalert2/dist/sweetalert2.min.js') }}"></script>


            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const urlParams = new URLSearchParams(window.location.search);
                    const selectedTab = urlParams.get('tab') || 'members';

                    const allTabs = document.querySelectorAll('.tab-btn');
                    const allTabContents = document.querySelectorAll('[id$="-tab"]');

                    let targetElement = null;

                    // Set initial visibility based on URL param
                    allTabs.forEach(tab => {
                        const target = tab.getAttribute('data-target');
                        const content = document.getElementById(target);

                        if (target.startsWith(selectedTab)) {
                            tab.classList.add('active');
                            if (content) { content.style.display = 'block'; content.classList.add('active'); }
                            targetElement = content;
                        } else {
                            tab.classList.remove('active');
                            if (content) { content.style.display = 'none'; content.classList.remove('active'); }
                        }

                        // Client-side tab switching — no page reload
                        tab.addEventListener('click', function (e) {
                            e.preventDefault();

                            // Update active tab button
                            allTabs.forEach(t => t.classList.remove('active'));
                            this.classList.add('active');

                            // Show/hide panels
                            allTabs.forEach(t => {
                                const panelId = t.getAttribute('data-target');
                                const panel = document.getElementById(panelId);
                                if (!panel) return;
                                if (panelId === target) {
                                    panel.style.display = 'block';
                                    panel.classList.add('active');
                                } else {
                                    panel.style.display = 'none';
                                    panel.classList.remove('active');
                                }
                            });

                            // Update URL without reload
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
