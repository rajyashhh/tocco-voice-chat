<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-color: {{ config('themes.primaryColor') }};
            --secondary-color: {{ config('themes.secondaryColor') }};
            --text-primary-color: {{ config('themes.textPrimaryColor') }};
            --text-secondary-color: {{ config('themes.textSecondaryColor') }};
            --box-background-color: {{ config('themes.boxBackgroundColor') }};
        }

        * { box-sizing: border-box; }

        .sa-page {
            max-width: 1300px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            color: #1e293b;
        }

        /* ── HEADER ──────────────────────────────────── */
        .sa-header {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 12px rgba(0,0,0,.04);
            padding: 24px 28px;
            margin-bottom: 24px;
            border: 1px solid #e8ecf1;
        }

        .sa-header-top {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .sa-logo {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            overflow: hidden;
            background: #f1f5f9;
            border: 2px solid #e2e8f0;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sa-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 4px;
        }

        .sa-header-info {
            flex: 1;
            min-width: 0;
        }

        .sa-name {
            font-size: 22px;
            font-weight: 800;
            color: #1e293b;
            margin: 0 0 8px 0;
        }

        .sa-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }

        .sa-meta-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 12.5px;
            color: #64748b;
            white-space: nowrap;
        }

        .sa-meta-tag strong { color: #334155; font-weight: 700; }
        .sa-meta-tag i { font-size: 10px; color: #94a3b8; }

        .sa-owner-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 4px 12px 4px 4px;
            text-decoration: none;
            color: inherit;
            transition: border-color .2s;
        }

        .sa-owner-chip:hover { border-color: var(--primary-color); }

        .sa-owner-chip img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid #e2e8f0;
        }

        .sa-owner-chip .oc-name { font-weight: 600; font-size: 13px; color: #1e293b; }
        .sa-owner-chip .oc-uuid { font-size: 11px; color: #94a3b8; }

        .sa-header-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .sa-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            white-space: nowrap;
            text-decoration: none;
            border: none;
        }

        .sa-btn-back {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .sa-btn-back:hover { background: #e2e8f0; color: #1e293b; }

        .sa-btn-export {
            background: #16a34a;
            color: #fff;
        }
        .sa-btn-export:hover { background: #15803d; box-shadow: 0 2px 8px rgba(22,163,74,.25); }

        /* ── NOTICE ──────────────────────────────────── */
        .sa-notice {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-inline-start: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .sa-notice i { color: #f59e0b; font-size: 16px; margin-top: 2px; }
        .sa-notice-text { font-size: 13px; color: #92400e; line-height: 1.5; }

        /* ── WALLET CARD ─────────────────────────────── */
        .sa-wallet-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .sa-wallet-card {
            background: linear-gradient(135deg, #1e3a5f 0%, #1a472a 100%);
            border-radius: 16px;
            padding: 28px 32px;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,.12);
        }

        .sa-wallet-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 160px;
            height: 160px;
            background: rgba(255,255,255,.06);
            border-radius: 50%;
        }

        .sa-wallet-card::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,.04);
            border-radius: 50%;
        }

        .sa-wallet-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .sa-wallet-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,.55);
        }

        .sa-wallet-app {
            font-size: 14px;
            font-weight: 700;
            color: rgba(255,255,255,.8);
        }

        .sa-wallet-bottom {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }

        .sa-wallet-balance-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: rgba(255,255,255,.5);
            margin-bottom: 4px;
        }

        .sa-wallet-balance {
            font-size: 32px;
            font-weight: 800;
            font-family: 'Inter', monospace;
            color: #fff;
            line-height: 1;
        }

        .sa-wallet-owner {
            text-align: end;
            font-size: 13px;
            color: rgba(255,255,255,.7);
            line-height: 1.5;
        }

        .sa-wallet-coin {
            width: 36px;
            height: 36px;
            margin-bottom: 6px;
        }

        /* ── BALANCE SUMMARY ─────────────────────────── */
        .sa-balance-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .sa-balance-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e8ecf1;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .sa-balance-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #fff;
            flex-shrink: 0;
        }

        .sa-balance-icon.sent { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .sa-balance-icon.received { background: linear-gradient(135deg, #10b981, #059669); }

        .sa-balance-info { flex: 1; }
        .sa-balance-label { font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; }
        .sa-balance-value { font-size: 22px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 6px; }
        .sa-balance-value img { width: 22px; height: 22px; }

        /* ── TABS ─────────────────────────────────────── */
        .sa-tabs {
            display: flex;
            gap: 4px;
            padding: 5px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            margin-bottom: 24px;
            overflow-x: auto;
            border: 1px solid #e8ecf1;
        }

        .sa-tab {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background: transparent;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            white-space: nowrap;
            transition: all .2s;
            text-decoration: none;
        }

        .sa-tab:hover:not(.active) { background: #f1f5f9; color: #334155; }

        .sa-tab.active {
            background: var(--primary-color);
            color: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,.12);
        }

        /* ── CARDS ────────────────────────────────────── */
        .sa-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            margin-bottom: 24px;
            border: 1px solid #e8ecf1;
            overflow: hidden;
        }

        .sa-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            border-bottom: 1px solid #f1f5f9;
            background: #fafbfc;
        }

        .sa-card-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sa-card-title i { color: var(--primary-color); font-size: 14px; }

        /* ── FILTER ───────────────────────────────────── */
        .sa-filter {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 16px 24px;
            flex-wrap: wrap;
        }

        .sa-filter .form-control {
            height: 38px;
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            padding: 6px 12px;
            font-size: 13px;
            color: #334155;
            background: #f8fafc;
            transition: border-color .2s;
        }

        .sa-filter .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99,102,241,.08);
            outline: none;
        }

        .sa-filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all .2s;
        }

        .sa-filter-btn.primary { background: var(--primary-color); color: #fff; }
        .sa-filter-btn.primary:hover { opacity: .9; }

        .sa-filter-btn.secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .sa-filter-btn.secondary:hover { background: #e2e8f0; }

        /* ── TABLES ───────────────────────────────────── */
        .sa-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sa-table thead th {
            padding: 11px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 2px solid #e8ecf1;
            white-space: nowrap;
            text-align: start;
        }

        .sa-table tbody td {
            padding: 12px 16px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .sa-table tbody tr:last-child td { border-bottom: none; }
        .sa-table tbody tr:hover { background: #fafbfc; }

        .sa-user-cell {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: inherit;
        }

        .sa-user-cell img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e8ecf1;
            flex-shrink: 0;
        }

        .sa-user-cell img.agency-img { border-radius: 6px; }

        .sa-user-cell:hover img { border-color: var(--primary-color); }

        .sa-user-name { font-weight: 600; font-size: 13px; color: #1e293b; }
        .sa-user-uuid { font-size: 11px; color: #94a3b8; }

        /* ── BADGES ───────────────────────────────────── */
        .sa-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
        }

        .sa-badge-success { background: #dcfce7; color: #16a34a; }
        .sa-badge-warning { background: #fef3c7; color: #b45309; }

        /* ── EMPTY STATE ──────────────────────────────── */
        .sa-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 50px 20px;
            color: #94a3b8;
        }

        .sa-empty i { font-size: 36px; opacity: .4; }
        .sa-empty p { margin: 0; font-size: 13px; font-weight: 500; }

        /* ── PAGINATION ───────────────────────────────── */
        .sa-pagination {
            padding: 16px 24px;
            display: flex;
            justify-content: center;
        }

        .sa-pagination .pagination { gap: 3px; }

        .sa-pagination .page-item .page-link {
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 12.5px;
            padding: 6px 12px;
        }

        .sa-pagination .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }

        /* ── LOADING ──────────────────────────────────── */
        #tab-loading {
            background: rgba(30,41,59,.92) !important;
            color: #fff !important;
            border-radius: 12px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            padding: 16px 28px !important;
            transform: translate(-50%, -50%);
            box-shadow: 0 8px 24px rgba(0,0,0,.2);
        }

        /* ── SELECT2 ──────────────────────────────────── */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            padding: 4px 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 28px; color: #334155; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { top: 5px; }

        /* ── RESPONSIVE ───────────────────────────────── */
        @media (max-width: 768px) {
            .sa-header-top { flex-direction: column; align-items: flex-start; }
            .sa-header-actions { width: 100%; }
            .sa-balance-row { grid-template-columns: 1fr; }
            .sa-filter { flex-direction: column; align-items: stretch; }
            .sa-filter .form-control { width: 100% !important; }
        }
    </style>
</head>
<body>
<div class="sa-page">

    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="sa-header">
        <div class="sa-header-top">
            <div class="sa-logo">
                @php
                    $image = getImagePath($agency->img);
                    $defaultImage = asset("images/icon-agency.jpg");
                    if (!isImageExists($image)) { $image = $defaultImage; }
                @endphp
                <img src="{{ $image }}" alt="Agency Logo">
            </div>

            <div class="sa-header-info">
                <h1 class="sa-name">{{ @$agency?->name ?? '' }}</h1>
                <div class="sa-meta">
                    <span class="sa-meta-tag"><i class="fas fa-hashtag"></i> {{ __('ID') }}: <strong>{{ @$agency->id }}</strong></span>
                    <span class="sa-meta-tag"><i class="fas fa-phone"></i> {{ __('Phone') }}: <strong>{{ @$agency->phone ?? 'N/A' }}</strong></span>

                    @if($agency?->owner)
                        @php
                            $ownerImg = getImagePath($agency?->owner?->profile->avatar);
                            $defaultOwnerImg = asset("images/businessman-icon.jpg");
                            if (!isImageExists($ownerImg)) { $ownerImg = $defaultOwnerImg; }
                        @endphp
                        <a href="{{ url('admin/users/' . $agency?->owner->id) }}" class="sa-owner-chip">
                            <img src="{{ $ownerImg }}" alt="">
                            <div>
                                <div class="oc-name">{{ $agency->owner->name ?? '' }}</div>
                                <div class="oc-uuid">UUID: {{ $agency?->owner->uuid ?? 'N/A' }}</div>
                            </div>
                        </a>
                    @endif
                </div>
            </div>

            <div class="sa-header-actions">
                <a class="sa-btn sa-btn-export" href="{{ url('download-charge-agency-transactions/' . $agency?->id) }}">
                    <i class="fas fa-file-excel"></i> {{ __('Export to Excel') }}
                </a>
                <button class="sa-btn sa-btn-back" onclick="window.location.href='{{ url('admin/charge-agencies') }}'">
                    <i class="fas fa-arrow-left"></i> {{ __('Go Back') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════ NOTICE ═══════════ --}}
    @if(@$agency->notice)
        <div class="sa-notice">
            <i class="fas fa-info-circle"></i>
            <div class="sa-notice-text">{{ @$agency->notice }}</div>
        </div>
    @endif

    {{-- ═══════════ WALLET CARD ═══════════ --}}
    <div class="sa-wallet-row">
        <div class="sa-wallet-card">
            <div class="sa-wallet-top">
                <div class="sa-wallet-label">{{ __('coin shipping agency wallet') }}</div>
                <div class="sa-wallet-app">
                    {{ \App\Helpers\Common::getSettingsValue(app()->getLocale() == 'ar' ? 'app_title_ar' : 'app_title_en') }}
                </div>
            </div>
            <div class="sa-wallet-bottom">
                <div>
                    <div class="sa-wallet-balance-label">{{ __('Balance') }}</div>
                    <div class="sa-wallet-balance">{{ numToString(@$agency->coins) }}</div>
                </div>
                <div class="sa-wallet-owner">
                    <img src="{{ asset('images/coin.jpg') }}" alt="Coin" class="sa-wallet-coin"><br>
                    {{ __('id') }} {{ @$agency->owner->uuid }}<br>
                    {{ @$agency->owner->name }}
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ SENT / RECEIVED SUMMARY ═══════════ --}}
    <div class="sa-balance-row">
        <div class="sa-balance-card">
            <div class="sa-balance-icon sent"><i class="fas fa-paper-plane"></i></div>
            <div class="sa-balance-info">
                <div class="sa-balance-label">{{ __('Sent Balance') }}</div>
                <div class="sa-balance-value">
                    {{ numToString(@$totalSend) }}
                    <img src="{{ asset('images/coin.jpg') }}" alt="Coin">
                </div>
            </div>
        </div>
        <div class="sa-balance-card">
            <div class="sa-balance-icon received"><i class="fas fa-inbox"></i></div>
            <div class="sa-balance-info">
                <div class="sa-balance-label">{{ __('Received Balance') }}</div>
                <div class="sa-balance-value">
                    {{ numToString(@$totalReceive) }}
                    <img src="{{ asset('images/coin.jpg') }}" alt="Coin">
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ TABS ═══════════ --}}
    @php $activeTab = request('tab', 'charges'); @endphp
    <div class="sa-tabs">
        <a href="?tab=charges" class="sa-tab {{ $activeTab == 'charges' ? 'active' : '' }}" data-target="charges-tab">
            <i class="fas fa-arrow-up-right-from-square"></i> {{ __('Sent Transactions') }}
        </a>
        <a href="?tab=resived" class="sa-tab {{ $activeTab == 'resived' ? 'active' : '' }}" data-target="resived-tab">
            <i class="fas fa-arrow-down-to-line"></i> {{ __('Received Transactions') }}
        </a>
        <a href="?tab=coinsLog" class="sa-tab {{ $activeTab == 'coinsLog' ? 'active' : '' }}" data-target="coinsLog-tab">
            <i class="fas fa-coins"></i> {{ __('Coins Logs') }}
        </a>
    </div>

    <div id="tab-loading" style="display:none;position:fixed;top:50%;left:50%;z-index:9999;">
        <i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> {{ __('Loading...') }}
    </div>

    {{-- ═══════════ TAB: SENT ═══════════ --}}
    <div id="charges-tab" style="display:{{ $activeTab == 'charges' ? 'block' : 'none' }}">
        <div class="sa-card">
            <div class="sa-filter">
                <select id="receiver-type" class="form-control" style="width:200px;">
                    <option value="">{{ __('Select type') }}</option>
                    <option value="user" {{ request('filter_by') == 'user' ? 'selected' : '' }}>{{ __('Users') }}</option>
                    <option value="agency" {{ request('filter_by') == 'agency' ? 'selected' : '' }}>{{ __('Agencies') }}</option>
                </select>
                <select id="receiver-id" class="form-control select2" style="width:300px;">
                    <option value="">{{ __('Search') }}</option>
                </select>
                <button class="sa-filter-btn primary search-btn" data-tab="charges"><i class="fas fa-search"></i> {{ __('Search') }}</button>
                <button class="sa-filter-btn secondary reset-filters" data-tab="charges"><i class="fas fa-redo"></i> {{ __('Reset') }}</button>
            </div>

            @if($charges && $charges->count())
                <div class="table-responsive">
                    <table class="sa-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('id') }}</th>
                            <th>{{ __('receiver') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('coins') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($charges as $index => $charge)
                            @php
                                $sender = \App\Helpers\Common::getChargerInfo($charge);
                                $receiver = \App\Helpers\Common::getReceiverInfo($charge);
                                $url = $receiver['url'];
                                $cImage = getImagePath($receiver['image']);
                                $cDefault = $charge->user_type == 'agency' ? asset("images/icon-agency.jpg") : asset('images/businessman-icon.jpg');
                                if (!isImageExists($cImage)) { $cImage = $cDefault; }
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $charge?->id }}</td>
                                <td>
                                    <a href="{{ $url }}" target="_blank" class="sa-user-cell">
                                        <img src="{{ $cImage }}" alt="" class="{{ $charge->user_type == 'agency' ? 'agency-img' : '' }}">
                                        <div>
                                            <div class="sa-user-name">{{ $receiver['name'] }}</div>
                                            <div class="sa-user-uuid">UUID: {{ $receiver['uuid'] }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td><strong>{{ $charge?->usd !== null ? '$' . number_format($charge->usd, 2) : 0 }}</strong></td>
                                <td><strong>{{ number_format($charge?->amount )  ?? '-'}}</strong></td>
                                <td><span class="sa-user-uuid">{{ $charge?->created_at }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="sa-pagination">{{ $charges->withQueryString()->links('vendor.pagination.bootstrap-4') }}</div>
            @else
                <div class="sa-empty"><i class="fas fa-paper-plane"></i><p>{{ __('No data available') }}</p></div>
            @endif
        </div>
    </div>

    {{-- ═══════════ TAB: RECEIVED ═══════════ --}}
    <div id="resived-tab" style="display:{{ $activeTab == 'resived' ? 'block' : 'none' }}">
        <div class="sa-card">
            <div class="sa-filter">
                <select id="sender-type" class="form-control" style="width:200px;">
                    <option value="">{{ __('Select type') }}</option>
                    <option value="user">{{ __('Users') }}</option>
                    <option value="host_agency">{{ __('Agencies') }}</option>
                    <option value="agency">{{ __('Shipping Agencies') }}</option>
                    <option value="bd">{{ __('BD') }}</option>
                    <option value="dash">{{ __('admins') }}</option>
                </select>
                <select id="sender-id" class="form-control select2" style="width:300px;">
                    <option value="">{{ __('Search') }}</option>
                </select>
                <button class="sa-filter-btn primary search-btn" data-tab="resived"><i class="fas fa-search"></i> {{ __('Search') }}</button>
                <button class="sa-filter-btn secondary reset-filters" data-tab="resived"><i class="fas fa-redo"></i> {{ __('Reset') }}</button>
            </div>

            @if($resiveds && $resiveds->count())
                <div class="table-responsive">
                    <table class="sa-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('id') }}</th>
                            <th>{{ __('Sender') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('coins') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($resiveds as $index => $res)
                            @php
                                $rSender = \App\Helpers\Common::getChargerInfo($res);
                                $rReceiver = \App\Helpers\Common::getReceiverInfo($res);
                                $rImage = getImagePath($rSender['image']);
                                $rUrl = $rSender['url'];
                                $rDefault = $res->charger_type == 'agency' ? asset("images/icon-agency.jpg") : asset('images/businessman-icon.jpg');
                                if (!isImageExists($rImage)) { $rImage = $rDefault; }
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $res?->id }}</td>
                                <td>
                                    <a href="{{ $rUrl }}" target="_blank" class="sa-user-cell">
                                        <img src="{{ $rImage }}" alt="" class="{{ $res->charger_type == 'agency' ? 'agency-img' : '' }}">
                                        <div>
                                            <div class="sa-user-name">{{ $rSender['name'] }}</div>
                                            <div class="sa-user-uuid">UUID: {{ $rSender['uuid'] }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td><strong>{{ $res->usd !== null ? '$' . number_format($res->usd, 2) : 0 }}</strong></td>
                                <td><strong>{{ number_format($res->amount) ?? '-' }}</strong></td>
                                <td><span class="sa-user-uuid">{{ $res->created_at }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="sa-pagination">{{ $resiveds->withQueryString()->links('vendor.pagination.bootstrap-4') }}</div>
            @else
                <div class="sa-empty"><i class="fas fa-inbox"></i><p>{{ __('No received charges found.') }}</p></div>
            @endif
        </div>
    </div>

    {{-- ═══════════ TAB: COINS LOG ═══════════ --}}
    <div id="coinsLog-tab" style="display:{{ $activeTab == 'coinsLog' ? 'block' : 'none' }}">
        <div class="sa-card">
            @if($coinLogs && $coinLogs->count())
                <div class="table-responsive">
                    <table class="sa-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Owner') }}</th>
                            <th>{{ __('usd') }}</th>
                            <th>{{ __('obtained coins') }}</th>
                            <th>{{ __('payment method') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Transaction Ref') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($coinLogs as $index => $log)
                            @php
                                $ownerName = '-';
                                if ($log->user_type === 'user') { $ownerName = optional($log->user)->name; }
                                elseif ($log->user_type === 'shipping_agency') { $ownerName = optional($log->shippingAgency)->name; }
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $log->id }}</td>
                                <td><strong>{{ $ownerName }}</strong></td>
                                <td><strong>${{ number_format($log->paid_usd, 2) }}</strong></td>
                                <td><strong>{{ $log->obtained_coins }}</strong></td>
                                <td>{{ ucfirst($log->method) }}</td>
                                <td>
                                    @if($log->status == 1)
                                        <span class="sa-badge sa-badge-success"><i class="fas fa-check-circle" style="margin-inline-end:4px;"></i>{{ __('Completed') }}</span>
                                    @else
                                        <span class="sa-badge sa-badge-warning"><i class="fas fa-clock" style="margin-inline-end:4px;"></i>{{ __('Pending') }}</span>
                                    @endif
                                </td>
                                <td><code style="font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:4px;">{{ $log->trx }}</code></td>
                                <td><span class="sa-user-uuid">{{ $log->created_at }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="sa-pagination">{{ $coinLogs->withQueryString()->links() }}</div>
            @else
                <div class="sa-empty"><i class="fas fa-coins"></i><p>{{ __('No coin logs found.') }}</p></div>
            @endif
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('tab') || 'charges';

    $('.select2').select2({
        placeholder: "Search", allowClear: true, minimumInputLength: 1,
        ajax: {
            delay: 250, url: "{{ route('search.charges') }}", dataType: 'json',
            data: function(params) { return { q: params.term, type: $(this).parent().find('.form-control:first').val(), page: params.page || 1 }; },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return { results: data.data.map(item => ({ id: item.id, text: item.name })), pagination: { more: data.current_page < data.last_page } };
            },
            cache: true
        }
    });

    if (currentTab === 'charges') {
        const filterBy = urlParams.get('filter_by');
        const filterId = urlParams.get('filter_id');
        if (filterBy) $('#receiver-type').val(filterBy);
        if (filterId) {
            $.ajax({ url: "{{ route('search.charges') }}", data: { id: filterId, type: filterBy },
                success: function(response) { if (response.data && response.data.length > 0) { const option = new Option(response.data[0].name, filterId, true, true); $('#receiver-id').append(option).trigger('change'); } }
            });
        }
    } else if (currentTab === 'resived') {
        const senderType = urlParams.get('sender_type');
        const senderId = urlParams.get('sender_id');
        if (senderType) $('#sender-type').val(senderType);
        if (senderId) {
            $.ajax({ url: "{{ route('search.charges') }}", data: { id: senderId, type: senderType },
                success: function(response) { if (response.data && response.data.length > 0) { const option = new Option(response.data[0].name, senderId, true, true); $('#sender-id').append(option).trigger('change'); } }
            });
        }
    }

    $('#receiver-type, #sender-type').on('change', function() { $(this).siblings('.select2').val(null).trigger('change'); });

    $('.search-btn').on('click', function() {
        const tab = $(this).data('tab');
        const currentUrl = new URL(window.location.href);
        if (tab === 'charges') {
            const type = $('#receiver-type').val(); const id = $('#receiver-id').val();
            type ? currentUrl.searchParams.set('filter_by', type) : currentUrl.searchParams.delete('filter_by');
            id ? currentUrl.searchParams.set('filter_id', id) : currentUrl.searchParams.delete('filter_id');
            currentUrl.searchParams.set('tab', 'charges');
        } else {
            const type = $('#sender-type').val(); const id = $('#sender-id').val();
            type ? currentUrl.searchParams.set('sender_type', type) : currentUrl.searchParams.delete('sender_type');
            id ? currentUrl.searchParams.set('sender_id', id) : currentUrl.searchParams.delete('sender_id');
            currentUrl.searchParams.set('tab', 'resived');
        }
        window.location.href = currentUrl.toString();
    });

    $('.reset-filters').on('click', function() {
        const tab = $(this).data('tab');
        const currentUrl = new URL(window.location.href);
        Array.from(currentUrl.searchParams.keys()).forEach(key => { if (key !== 'tab') currentUrl.searchParams.delete(key); });
        currentUrl.searchParams.set('tab', tab);
        window.location.href = currentUrl.toString();
    });
});
</script>

<style>
    @keyframes saFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const allTabs = document.querySelectorAll('.sa-tab');
    const allPanels = ['charges-tab', 'resived-tab', 'coinsLog-tab'];

    allTabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');

            // Update active tab
            allTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Show/hide panels with animation
            allPanels.forEach(panelId => {
                const panel = document.getElementById(panelId);
                if (!panel) return;
                if (panelId === targetId) {
                    panel.style.display = 'block';
                    panel.style.animation = 'none';
                    panel.offsetHeight; // trigger reflow
                    panel.style.animation = 'saFadeIn .25s ease';
                } else {
                    panel.style.display = 'none';
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
</script>
</body>
</html>
