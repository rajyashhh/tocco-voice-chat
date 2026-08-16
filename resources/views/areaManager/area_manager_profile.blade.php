<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --primary-color: {{ config('themes.primaryColor') }};
        --secondary-color: {{ config('themes.secondaryColor') }};
        --text-primary-color: {{ config('themes.textPrimaryColor') }};
        --text-secondary-color: {{ config('themes.textSecondaryColor') }};
        --box-background-color: {{ config('themes.boxBackgroundColor') }};
    }

    .am-page * { box-sizing: border-box; }

    .am-page {
        max-width: 1300px;
        margin: 0 auto;
        padding: 20px;
        font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        color: #1e293b;
    }

    /* ── HEADER ────────────────────────────────── */
    .am-header {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 12px rgba(0,0,0,.04);
        padding: 24px 28px;
        margin-bottom: 24px;
        border: 1px solid #e8ecf1;
    }

    .am-header-top {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .am-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid #e8ecf1;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
        flex-shrink: 0;
    }

    .am-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .am-header-info { flex: 1; min-width: 0; }

    .am-name {
        font-size: 22px;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 10px 0;
    }

    .am-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 10px;
    }

    .am-meta-tag {
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

    .am-meta-tag strong { color: #334155; font-weight: 700; }
    .am-meta-tag i { font-size: 10px; color: #94a3b8; }

    .am-flags {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 5px 12px;
        font-size: 12.5px;
        color: #64748b;
    }

    .am-flags i { font-size: 10px; color: #94a3b8; }
    .am-flags img { height: 18px; border-radius: 2px; }

    .am-header-actions { flex-shrink: 0; }

    .am-btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        cursor: pointer;
        transition: all .2s;
    }

    .am-btn-back:hover { background: #e2e8f0; color: #1e293b; }

    /* ── STAT CARDS ────────────────────────────── */
    .am-stats-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }

    .am-stat-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .am-stat-icon {
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

    .am-stat-icon.charges { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .am-stat-icon.spent { background: linear-gradient(135deg, #ef4444, #dc2626); }

    .am-stat-info { flex: 1; }
    .am-stat-label { font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; }
    .am-stat-value { font-size: 22px; font-weight: 800; color: #1e293b; }

    /* ── TABS ──────────────────────────────────── */
    .am-tabs {
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

    .am-tab {
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

    .am-tab:hover:not(.active) { background: #f1f5f9; color: #334155; }

    .am-tab.active {
        background: var(--primary-color);
        color: #fff;
        box-shadow: 0 1px 4px rgba(0,0,0,.12);
    }

    /* ── CARDS ─────────────────────────────────── */
    .am-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        margin-bottom: 24px;
        border: 1px solid #e8ecf1;
        overflow: hidden;
    }

    .am-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 24px;
        border-bottom: 1px solid #f1f5f9;
        background: #fafbfc;
    }

    .am-card-title {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .am-card-title i { color: var(--primary-color); font-size: 14px; }

    .am-count-badge {
        background: var(--primary-color);
        color: #fff;
        padding: 3px 10px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
    }

    /* ── SUB TABS (charge nav pills) ──────────── */
    .am-sub-tabs {
        display: flex;
        gap: 4px;
        padding: 16px 24px 0;
    }

    .am-sub-tab {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        color: #64748b;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        transition: all .2s;
    }

    .am-sub-tab:hover { background: #e2e8f0; color: #334155; }
    .am-sub-tab.active { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }

    /* ── TABLES ────────────────────────────────── */
    .am-table {
        width: 100%;
        border-collapse: collapse;
    }

    .am-table thead th {
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

    .am-table tbody td {
        padding: 12px 16px;
        font-size: 13px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .am-table tbody tr:last-child td { border-bottom: none; }
    .am-table tbody tr:hover { background: #fafbfc; }

    .am-user-cell {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: inherit;
    }

    .am-user-cell img {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e8ecf1;
        flex-shrink: 0;
    }

    .am-user-cell:hover img { border-color: var(--primary-color); }

    .am-user-name { font-weight: 600; font-size: 13px; color: #1e293b; display: block; }
    .am-user-meta { font-size: 11px; color: #94a3b8; }

    .am-flag-inline {
        width: 18px;
        height: 13px;
        object-fit: cover;
        border-radius: 2px;
        margin-inline-end: 4px;
        vertical-align: middle;
    }

    /* ── BUTTONS ───────────────────────────────── */
    .am-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 7px;
        font-weight: 600;
        font-size: 12px;
        padding: 6px 14px;
        border: none;
        cursor: pointer;
        transition: all .2s;
        text-decoration: none;
    }

    .am-btn-edit { background: #e0e7ff; color: #4f46e5; border: 1px solid #c7d2fe; }
    .am-btn-edit:hover { background: #4f46e5; color: #fff; }

    .am-btn-delete { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .am-btn-delete:hover { background: #dc2626; color: #fff; }

    .am-status-active { color: #16a34a; font-weight: 700; }
    .am-status-inactive { color: #dc2626; font-weight: 700; }

    /* ── EMPTY STATE ───────────────────────────── */
    .am-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 50px 20px;
        color: #94a3b8;
    }

    .am-empty i { font-size: 36px; opacity: .4; }
    .am-empty p { margin: 0; font-size: 13px; font-weight: 500; }

    /* ── PAGINATION ────────────────────────────── */
    .am-pagination {
        padding: 16px 24px;
        display: flex;
        justify-content: center;
    }

    .am-pagination .pagination { gap: 3px; }

    .am-pagination .page-item .page-link {
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 12.5px;
        padding: 6px 12px;
    }

    .am-pagination .page-item.active .page-link {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: #fff;
    }

    /* ── LOADING ───────────────────────────────── */
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

    /* ── MODAL ─────────────────────────────────── */
    .modal-content { border-radius: 14px; border: none; box-shadow: 0 8px 30px rgba(0,0,0,.12); }
    .modal-header { border-bottom: 1px solid #f1f5f9; padding: 16px 24px; }
    .modal-title { font-weight: 700; font-size: 16px; }
    .modal-body { padding: 24px; }
    .modal-footer { border-top: 1px solid #f1f5f9; padding: 14px 24px; }

    /* ── HIDDEN TAB PANEL ─────────────────────── */
    .am-hidden { display: none !important; }

    .am-tab-panel {
        animation: amFadeIn .25s ease;
    }

    @keyframes amFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ── RESPONSIVE ────────────────────────────── */
    @media (max-width: 768px) {
        .am-header-top { flex-direction: column; align-items: center; text-align: center; }
        .am-meta { justify-content: center; }
        .am-stats-row { grid-template-columns: 1fr; }
        .am-avatar { width: 64px; height: 64px; }
    }
</style>

<body>
<div class="am-page">

    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="am-header">
        <div class="am-header-top">
            <div class="am-avatar">
                <img src="{{ $areaManager->display_image }}" alt="">
            </div>

            <div class="am-header-info">
                <h1 class="am-name">{{ $areaManager->name ?? '' }}</h1>
                <div class="am-meta">
                    <span class="am-meta-tag"><i class="fas fa-hashtag"></i> {{ __('ID') }}: <strong>{{ $areaManager->id }}</strong></span>
                    <span class="am-meta-tag"><i class="fas fa-user"></i> {{ __('username') }}: <strong>{{ $areaManager->username ?? '' }}</strong></span>
                    <span class="am-meta-tag"><i class="fas fa-coins"></i> {{ __('salary') }}: <strong>{{ $areaManager->di }}</strong></span>
                </div>
                <div class="am-flags">
                    <i class="fas fa-globe"></i> {{ __('country') }}:
                    {!! @$areaManager->flag() !!}
                </div>
            </div>

            <div class="am-header-actions">
                <a href="{{ url('admin/usersBd') }}" class="am-btn-back">
                    <i class="fas fa-arrow-left"></i> {{ __('Go Back') }}
                </a>
            </div>
        </div>
    </div>

    {{-- ═══════════ STATS ═══════════ --}}
    <div class="am-stats-row">
        <div class="am-stat-card">
            <div class="am-stat-icon charges"><i class="fas fa-coins"></i></div>
            <div class="am-stat-info">
                <div class="am-stat-label">{{ __('total charges') }}</div>
                <div class="am-stat-value">{{ truncateAndTrim($totalCharges, 2) }}</div>
            </div>
        </div>
        <div class="am-stat-card">
            <div class="am-stat-icon spent"><i class="fas fa-arrow-trend-down"></i></div>
            <div class="am-stat-info">
                <div class="am-stat-label">{{ __('total spent') }}</div>
                <div class="am-stat-value">{{ truncateAndTrim($totalSpent, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- ═══════════ TABS ═══════════ --}}
    @php $activeTab = request('tab', 'agencies'); @endphp

    <div class="am-tabs">
        <a href="?tab=agencies" class="am-tab {{ $activeTab === 'agencies' ? 'active' : '' }}" data-target="members-tab">
            <i class="fas fa-building"></i> {{ __('agencies') }}
        </a>
        <a href="?tab=charge" class="am-tab {{ $activeTab == 'charge' ? 'active' : '' }}" data-target="charge-tab">
            <i class="fas fa-file-invoice-dollar"></i> {{ __('Charge Reports') }}
        </a>
        <a href="?tab=superAdmin" class="am-tab {{ $activeTab == 'superAdmin' ? 'active' : '' }}" data-target="superAdmin-tab">
            <i class="fas fa-globe"></i> {{ __('country manager') }}
        </a>
        <a href="?tab=users" class="am-tab {{ $activeTab === 'users' ? 'active' : '' }}" data-target="users-tab">
            <i class="fas fa-users-gear"></i> {{ __('Employees Country Manager') }}
        </a>
    </div>

    <div id="tab-loading" style="display:none;position:fixed;top:50%;left:50%;z-index:9999;">
        <i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> {{ __('Loading...') }}
    </div>

    {{-- ═══════════ TAB: AGENCIES ═══════════ --}}
    <div class="am-tab-panel {{ $activeTab === 'agencies' ? '' : 'am-hidden' }}" id="members-tab">
        <div class="am-card">
            <div class="am-card-header">
                <h4 class="am-card-title"><i class="fas fa-building"></i> {{ __('Agencies') }}</h4>
                <span class="am-count-badge">{{ optional($agencies)->total() ?? 0 }}</span>
            </div>
            @if($agencies && $agencies->count())
                <div class="table-responsive">
                    <table class="am-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('owner') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($agencies as $index => $agency)
                            <tr>
                                <td>{{ $index + 1 + (($agencies->currentPage() - 1) * $agencies->perPage()) }}</td>
                                <td>
                                    <a href="{{ url('admin/agencies/profile/' . $agency->id) }}" class="am-user-cell">
                                        <img src="{{ getImagePath($agency->img) }}" alt="">
                                        <div><span class="am-user-name">{{ $agency->name ?? '' }}</span></div>
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ url('admin/users/' . $agency->owner?->id) }}" class="am-user-cell">
                                        <img src="{{ getImagePath($agency->owner?->profile?->avatar) }}" alt="">
                                        <div><span class="am-user-name">{{ $agency->owner?->name ?? '' }}</span></div>
                                    </a>
                                </td>
                                <td>
                                    @if($agency->status == 1)
                                        <span class="am-status-active"><i class="fas fa-check-circle"></i> {{ __('Active') }}</span>
                                    @else
                                        <span class="am-status-inactive"><i class="fas fa-times-circle"></i> {{ __('Inactive') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="am-pagination">{{ $agencies->appends(['tab' => 'agencies'])->links('vendor.pagination.default') }}</div>
            @else
                <div class="am-empty"><i class="fas fa-building"></i><p>{{ __('No agencies found') }}</p></div>
            @endif
        </div>
    </div>

    {{-- ═══════════ TAB: COUNTRY MANAGER ═══════════ --}}
    <div class="am-tab-panel {{ $activeTab === 'superAdmin' ? '' : 'am-hidden' }}" id="superAdmin-tab">
            <div class="am-card">
                <div class="am-card-header">
                    <h4 class="am-card-title"><i class="fas fa-globe"></i> {{ __('country manager') }}</h4>
                    <span class="am-count-badge">{{ optional($superAdmins)->total() ?? 0 }}</span>
                </div>
                @if($superAdmins && $superAdmins->count())
                    <div class="table-responsive">
                        <table class="am-table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('user') }}</th>
                                <th>{{ __('country') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($superAdmins as $index => $superAdmin)
                                <tr>
                                    <td>{{ $index + 1 + (($superAdmins->currentPage() - 1) * $superAdmins->perPage()) }}</td>
                                    <td>
                                        <a href="{{ url($prefix.'/superadmin-users/' . $superAdmin->id) }}" class="am-user-cell">
                                            <img src="{{ $superAdmin->avatar ? getImagePath($superAdmin->avatar) : $defaultImage }}" alt="">
                                            <div>
                                                <span class="am-user-name">{{ $superAdmin->username ?? '' }}</span>
                                                <span class="am-user-meta">ID: {{ $superAdmin->id }}</span>
                                            </div>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ url($prefix.'/users/' . ($superAdmin->appUser?->id ?? 0)) }}" class="am-user-cell">
                                            <img src="{{ $superAdmin->appUser?->profile?->avatar ? getImagePath($superAdmin->appUser->profile->avatar) : $defaultImage }}" alt="">
                                            <div>
                                                <span class="am-user-name">
                                                    @if(@$superAdmin->appUser->country->flag)
                                                        <img src="{{ getImagePath($superAdmin->appUser->country->flag) }}" alt="" class="am-flag-inline">
                                                    @endif
                                                    {{ $superAdmin->appUser?->name ?? '' }}
                                                </span>
                                                <span class="am-user-meta">ID: {{ $superAdmin->appUser?->uuid ?? 'N/A' }}</span>
                                            </div>
                                        </a>
                                    </td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            @if($superAdmin->country?->flag)
                                                <img src="{{ getImagePath($superAdmin->country->flag) }}" alt="" class="am-flag-inline">
                                            @endif
                                            <strong>{{ app()->getLocale() === 'ar' ? ($superAdmin->country?->name ?? '') : ($superAdmin->country?->e_name ?? '') }}</strong>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="am-pagination">{{ $superAdmins->appends(['tab' => 'superAdmin'])->links('vendor.pagination.default') }}</div>
                @else
                    <div class="am-empty"><i class="fas fa-globe"></i><p>{{ __('No country manager found') }}</p></div>
                @endif
            </div>
        </div>

    {{-- ═══════════ TAB: CHARGE REPORTS ═══════════ --}}
    <div class="am-tab-panel {{ $activeTab == 'charge' ? '' : 'am-hidden' }}" id="charge-tab">
            <div class="am-card">
                <div class="am-card-header">
                    <h4 class="am-card-title"><i class="fas fa-file-invoice-dollar"></i> {{ __('Charge Reports') }}</h4>
                </div>

                <div class="am-sub-tabs">
                    <a href="?tab=charge&type=receiver" class="am-sub-tab {{ $chargeTabType == 'receiver' ? 'active' : '' }}">
                        <i class="fas fa-inbox"></i> {{ __('Receiver') }}
                    </a>
                    <a href="?tab=charge&type=charger" class="am-sub-tab {{ $chargeTabType == 'charger' ? 'active' : '' }}">
                        <i class="fas fa-paper-plane"></i> {{ __('Charger') }}
                    </a>
                </div>

                <div class="table-responsive" style="padding:0 24px 24px;">
                    <table class="am-table" style="margin-top:16px;">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>@if($chargeTabType == 'receiver') {{ __('Charger') }} @else {{ __('Receiver') }} @endif</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('usd') }}</th>
                            <th>{{ __('Created at') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($charges as $index => $charge)
                            @php
                                if($chargeTabType == 'receiver') {
                                    $userCharges = \App\Helpers\Common::getChargerInfo($charge);
                                } else {
                                    $userCharges = \App\Helpers\Common::getReceiverInfo($charge);
                                }
                                $cName = $userCharges['name'] ?? '-';
                                $cUid = $userCharges['uuid'] ?? '-';
                                $cType = $userCharges['type'] ?? '-';
                                $cImg = $userCharges['image'] ?? asset('images/businessman-icon.jpg');
                            @endphp
                            <tr>
                                <td>{{ @$charge->id ?? 0 }}</td>
                                <td>
                                    <a href="{{ $userCharges['url'] ?? '#' }}" target="_blank" class="am-user-cell">
                                        <img src="{{ getImagePath($cImg) }}" alt="">
                                        <div>
                                            <span class="am-user-name">{{ $cName }}</span>
                                            <span class="am-user-meta">{{ $cUid }}</span>
                                        </div>
                                    </a>
                                </td>
                                <td><span style="background:#f1f5f9;padding:3px 8px;border-radius:4px;font-size:12px;font-weight:600;">{{ $cType }}</span></td>
                                <td><strong>{{ number_format($charge->amount) }}</strong></td>
                                <td><strong>${{ number_format((float)$charge->usd, 2) }}</strong></td>
                                <td><span class="am-user-meta">{{ \Carbon\Carbon::parse($charge->created_at)->format('Y-m-d H:i') }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="am-empty"><i class="fas fa-file-invoice-dollar"></i><p>{{ __('No data available') }}</p></div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if($charges instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="am-pagination">{{ $charges->appends(['tab' => 'charge'])->links('vendor.pagination.bootstrap-4') }}</div>
                @endif
            </div>
        </div>

    {{-- ═══════════ TAB: EMPLOYEES COUNTRY MANAGER ═══════════ --}}
    <div class="am-tab-panel {{ $activeTab === 'users' ? '' : 'am-hidden' }}" id="users-tab">
            <div class="am-card">
                <div class="am-card-header">
                    <h4 class="am-card-title"><i class="fas fa-users-gear"></i> {{ __('Employees Country Manager') }}</h4>
                    <span class="am-count-badge">{{ optional($subAreaManagers)->total() ?? 0 }}</span>
                </div>
                @if($subAreaManagers && $subAreaManagers->count())
                    <div class="table-responsive">
                        <table class="am-table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('user') }}</th>
                                <th>{{ __('Role') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($subAreaManagers as $index => $subSuperAdmin)
                                <tr>
                                    <td>{{ $index + 1 + (($subAreaManagers->currentPage() - 1) * $subAreaManagers->perPage()) }}</td>
                                    <td>
                                        <a href="{{ url($prefix.'/auth-users/' . $subSuperAdmin->id) }}" class="am-user-cell">
                                            <img src="{{ $subSuperAdmin->avatar ? getImagePath($subSuperAdmin->avatar) : $defaultImage }}" alt="">
                                            <div>
                                                <span class="am-user-name">{{ $subSuperAdmin->username ?? '' }}</span>
                                                <span class="am-user-meta">ID: {{ $subSuperAdmin->id }}</span>
                                            </div>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ url($prefix.'/users/' . ($subSuperAdmin->appUser?->id ?? 0)) }}" class="am-user-cell">
                                            <img src="{{ $subSuperAdmin->appUser?->profile?->avatar ? getImagePath($subSuperAdmin->appUser->profile->avatar) : $defaultImage }}" alt="">
                                            <div>
                                                <span class="am-user-name">
                                                    @if(@$subSuperAdmin->appUser->country->flag)
                                                        <img src="{{ getImagePath($subSuperAdmin->appUser->country->flag) }}" alt="" class="am-flag-inline">
                                                    @endif
                                                    {{ $subSuperAdmin->appUser?->name ?? '' }}
                                                </span>
                                                <span class="am-user-meta">ID: {{ $subSuperAdmin->appUser?->uuid ?? 'N/A' }}</span>
                                            </div>
                                        </a>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                            @if (\Encore\Admin\Facades\Admin::user()->can('delete-auth-users') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                                <button class="am-btn am-btn-delete kick-member-btn" data-id="{{ $subSuperAdmin->id }}">
                                                    <i class="fas fa-trash-can"></i> {{ __('delete') }}
                                                </button>
                                            @endif
                                            @if (\Encore\Admin\Facades\Admin::user()->can('edit-auth-users') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                                <button class="am-btn am-btn-edit edit_user_item_model_btn" data-id="{{ $subSuperAdmin->id }}">
                                                    <i class="fas fa-pen"></i> {{ __('edit') }}
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="am-pagination">
                        {{ $subAreaManagers->appends(['tab' => 'sub-super-admin', 'sub_super_admin_page' => $subAreaManagers?->currentPage()])->links('vendor.pagination.default') }}
                    </div>
                @else
                    <div class="am-empty"><i class="fas fa-users-slash"></i><p>{{ __('No users admins found') }}</p></div>
                @endif
            </div>
        </div>

</div>

{{-- ═══════════ EDIT MODAL ═══════════ --}}
<div class="updateProjectModal modal fade" id="item_modal_update" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg mt-6" role="document">
        <div class="modal-content border-0">
            <div class="modal-content position-relative">
                <div class="position-absolute top-0 end-0 mt-2 me-2 z-index-1">
                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ url(request()->segment(1) . '/update-area-manager') }}" id="country_update_form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-0">
                        <div class="p-4">
                            <div class="row flex-evenly">
                                <input type="hidden" name="id" class="item_id">
                                <div class="col-lg-6 mb-3 form-group">
                                    <label class="form-label">{{ __('admin.name') }}</label>
                                    <input type="text" name="name" class="form-control" id="name">
                                </div>
                                <div class="col-lg-6 mb-3 form-group">
                                    <label class="form-label">{{ __('admin.username') }}</label>
                                    <input type="text" name="username" class="form-control" id="username">
                                </div>
                                <div class="col-lg-6 mb-3 form-group">
                                    <label for="user_id" class="form-label">{{ __('admin.users') }}</label>
                                    <select class="form-control" id="user_id" name="user_id">
                                        <option value="">{{ __('admin.selectUser') }}</option>
                                    </select>
                                </div>
                                <div class="col-lg-6 mb-3 form-group">
                                    <label class="form-label">{{ __('admin.password') }}</label>
                                    <input type="password" name="password" class="form-control" placeholder="Leave blank if not changing">
                                </div>
                                <div class="col-lg-6 form-group mb-3">
                                    <label class="form-label">{{ __('image') }}</label>
                                    <input class="form-control" name="image" accept="image/*" type="file"/>
                                    <div class="mt-2">
                                        <img src="" class="w-40" style="width:100px" id="img_edit" alt="">
                                    </div>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
<script>
    const DASHBOARD_PREFIX = "{{ request()->segment(1) }}";

    document.addEventListener("DOMContentLoaded", function () {
        const allTabs = document.querySelectorAll('.am-tab');
        const allPanels = document.querySelectorAll('.am-tab-panel');

        allTabs.forEach(tab => {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-target');

                // Update active tab
                allTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Show/hide panels with animation
                allPanels.forEach(panel => {
                    if (panel.id === targetId) {
                        panel.classList.remove('am-hidden');
                        panel.style.animation = 'none';
                        panel.offsetHeight; // trigger reflow
                        panel.style.animation = '';
                    } else {
                        panel.classList.add('am-hidden');
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
        $('#user_id').select2({
            dropdownParent: $('#item_modal_update'), width: '100%',
            placeholder: 'Select user', allowClear: true, minimumInputLength: 1,
            ajax: {
                url: '/api/search/users-area-manager', dataType: 'json', delay: 250,
                data: function (params) { return { q: params.term || '', page: params.page || 1, selected_id: $('#user_id').val() || null }; },
                processResults: function (data) {
                    return { results: data.data.map(item => ({ id: item.id, text: item.name })), pagination: { more: data.current_page < data.last_page } };
                }
            }
        });

        (function () {
            var preId = $('#user_id').data('selected-id');
            var preText = $('#user_id').data('selected-text');
            if (preId) { $('#user_id').val(null).trigger('change'); var opt = new Option(preText || preId, preId, true, true); $('#user_id').append(opt).trigger('change'); }
        })();

        $(document).on('click', '.edit_user_item_model_btn', function () {
            $('#item_modal_update').modal('show');
            const id = $(this).data('id');
            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                type: 'get', dataType: 'json',
                url: '/' + DASHBOARD_PREFIX + "/show-sub-area-manager/" + id,
                success: function(response) {
                    if (response.status == 404) {
                        Swal.fire({ icon: 'error', title: 'Sorry', text: response.message });
                    } else {
                        const image = "{{ getImagePath('__IMAGE_PATH__') }}".replace('__IMAGE_PATH__', response.item.avatar);
                        $('#name').val(response.item.name);
                        $('#username').val(response.item.username);
                        if (response.item.app_id) {
                            $('#user_id').val(null).trigger('change');
                            var displayText = response.item.app_user_name || null;
                            if (!displayText && response.item.appUser) { var au = response.item.appUser; displayText = (au.name || '') + ' - ' + (au.uuid || ''); }
                            if (!displayText) displayText = response.item.name || response.item.app_id;
                            var selectedUser = new Option(displayText, response.item.app_id, true, true);
                            $('#user_id').append(selectedUser).trigger('change');
                        }
                        $('#img_edit').attr('src', image);
                        $('.item_id').val(response.item.id);
                        $('#item_modal_update').modal('show');
                    }
                }
            });
        });

        $(document).on('click', '.cancel_user_item_model_btn', function () { $('#item_modal_update').modal('hide'); });

        function showLoader() { Swal.fire({ title: 'Loading...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } }); }
        function showSuccess(message, callback = null) { Swal.fire({ icon: 'success', title: message, confirmButtonText: 'OK' }).then(() => { if (callback) callback(); }); }
        function showError(message) { Swal.fire({ icon: 'error', title: message, confirmButtonText: 'OK' }); }
        function confirmAction(message, onConfirm) { Swal.fire({ title: message, icon: 'question', showCancelButton: true, confirmButtonText: 'Yes', cancelButtonText: 'Cancel' }).then(result => { if (result.value) onConfirm(); }); }

        $('.kick-member-btn').click(function () {
            const id = $(this).data('id');
            confirmAction('{{ __("are_you_sure_delete") }}', () => {
                showLoader();
                $.post('/' + DASHBOARD_PREFIX + '/delete-sub-admin/' + id, { _token: '{{ csrf_token() }}' }, function (response) {
                    Swal.close();
                    if (response.status) { showSuccess(response.message, () => { location.reload(); }); }
                    else { showError(response.message); }
                }).fail(function (xhr) { Swal.close(); showError(xhr.responseJSON?.message ?? '{{ __("failed_make_admin") }}'); });
            });
        });

        $('.accept-btn').click(function () {
            const id = $(this).data('id');
            confirmAction('{{ __("are_you_sure_accept") }}', () => {
                showLoader();
                $.post(`/admin/agencies/accept_join/${id}`, { _token: '{{ csrf_token() }}' }, function (response) {
                    Swal.close();
                    if (response.status) { showSuccess(response.message, () => { const url = new URL(window.location.href); url.searchParams.set('tab', 'requests'); window.location.href = url.toString(); }); }
                    else { showError(response.message); }
                }).fail(function (xhr) { Swal.close(); showError(xhr.responseJSON?.message ?? '{{ __("failed_accept_request") }}'); });
            });
        });

        $('.reject-btn').click(function () {
            const id = $(this).data('id');
            confirmAction('{{ __("are_you_sure_reject") }}', () => {
                showLoader();
                $.post(`/admin/agencies/reject_join/${id}`, { _token: '{{ csrf_token() }}' }, function (response) {
                    Swal.close();
                    if (response.status) { showSuccess(response.message, () => { const url = new URL(window.location.href); url.searchParams.set('tab', 'requests'); window.location.href = url.toString(); }); }
                    else { showError(response.message); }
                }).fail(function (xhr) { Swal.close(); showError(xhr.responseJSON?.message ?? '{{ __("failed_reject_request") }}'); });
            });
        });

        $('.make-admin-btn').click(function () {
            const id = $(this).data('id');
            confirmAction('{{ __("are_you_sure_make_admin") }}', () => {
                showLoader();
                $.post(`/admin/agencies/admin/${id}`, { _token: '{{ csrf_token() }}' }, function (response) {
                    Swal.close();
                    if (response.status) { showSuccess(response.message, () => { location.reload(); }); }
                    else { showError(response.message); }
                }).fail(function (xhr) { Swal.close(); showError(xhr.responseJSON?.message ?? '{{ __("failed_make_admin") }}'); });
            });
        });
    });
</script>
