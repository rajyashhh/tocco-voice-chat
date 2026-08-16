
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

    :root {
        --primary: {{ config('themes.primaryColor', '#6366f1') }};
        --primary-light: {{ config('themes.primaryColor', '#6366f1') }}18;
        --primary-medium: {{ config('themes.primaryColor', '#6366f1') }}30;
    }

    .bd-profile, .bd-profile * { box-sizing: border-box; }

    .bd-profile {
        max-width: 1200px;
        margin: 0 auto;
        padding: 24px;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        color: #1a1a2e !important;
        -webkit-font-smoothing: antialiased;
    }

    .bd-profile, .bd-profile *, .bd-profile a, .bd-profile span, .bd-profile td, .bd-profile th,
    .bd-profile h1, .bd-profile h2, .bd-profile h3, .bd-profile h4, .bd-profile p, .bd-profile label,
    .bd-profile strong, .bd-profile small, .bd-profile div {
        color: #1a1a2e;
    }

    /* ═══════════════════════════════════════
       HERO HEADER
    ═══════════════════════════════════════ */
    .bd-hero {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 25px -3px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
    }

    .bd-hero-banner {
        height: 120px;
        background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 50%, #a855f7 100%);
        position: relative;
    }

    .bd-hero-banner::after {
        content: '';
        position: absolute;
        inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .bd-hero-content {
        padding: 0 32px 28px;
        position: relative;
    }

    .bd-hero-avatar {
        margin-top: -50px;
        display: inline-block;
    }

    .bd-hero-avatar img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        object-fit: cover;
        background: #e2e8f0;
    }

    .bd-hero-info {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-top: 16px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .bd-hero-name {
        font-size: 26px;
        font-weight: 800;
        color: #1a1a2e !important;
        letter-spacing: -0.5px;
        margin-bottom: 8px;
    }

    .bd-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .bd-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #4a5568 !important;
    }

    .bd-meta-item i {
        font-size: 12px;
        color: var(--primary) !important;
        width: 16px;
        text-align: center;
    }

    .bd-meta-item strong {
        color: #1a1a2e !important;
        font-weight: 600;
    }

    .bd-btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #fff;
        color: #4a5568 !important;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }

    .bd-btn-back:hover {
        background: #f8fafc;
        color: #1a1a2e !important;
        border-color: #cbd5e1;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        text-decoration: none;
    }

    /* ═══════════════════════════════════════
       STATS CARDS
    ═══════════════════════════════════════ */
    .bd-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .bd-stat-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }

    .bd-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.08);
    }

    .bd-stat-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .bd-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white !important;
    }

    .bd-stat-icon.profit { background: linear-gradient(135deg, #10b981, #059669); }
    .bd-stat-icon.count { background: linear-gradient(135deg, #3b82f6, #2563eb); }

    .bd-stat-label {
        font-size: 13px;
        font-weight: 600;
        color: #4a5568 !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .bd-stat-value {
        font-size: 28px;
        font-weight: 800;
        color: #1a1a2e !important;
        line-height: 1.2;
    }

    /* ═══════════════════════════════════════
       TABS
    ═══════════════════════════════════════ */
    .bd-tabs-nav {
        display: flex;
        gap: 4px;
        background: #fff;
        border-radius: 16px;
        padding: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
        width: fit-content;
    }

    .bd-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #4a5568 !important;
        text-decoration: none;
        transition: all 0.25s;
        border: none;
        background: transparent;
        cursor: pointer;
        white-space: nowrap;
    }

    .bd-tab-btn:hover {
        color: #1a1a2e !important;
        background: #f1f5f9;
        text-decoration: none;
    }

    .bd-tab-btn.active {
        background: var(--primary) !important;
        color: white !important;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.35);
    }

    .bd-tab-btn.active i { color: white !important; }

    .bd-tab-btn i {
        font-size: 14px;
        color: var(--primary) !important;
    }

    .bd-tab-content { display: none; animation: bdFadeSlide 0.3s ease; }
    .bd-tab-content.active { display: block; }

    @keyframes bdFadeSlide {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ═══════════════════════════════════════
       CONTENT CARDS
    ═══════════════════════════════════════ */
    .bd-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        overflow: hidden;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }

    .bd-card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .bd-card-title {
        font-size: 16px;
        font-weight: 700;
        color: #1a1a2e !important;
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }

    .bd-card-title i {
        color: var(--primary) !important;
        font-size: 16px;
    }

    .bd-count-pill {
        background: var(--primary);
        color: white !important;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
    }

    /* Filter Bar */
    .bd-filter-bar {
        padding: 16px 24px;
        background: #fafbfc;
        border-bottom: 1px solid #e2e8f0;
    }

    .bd-filter-row {
        display: flex;
        align-items: flex-end;
        gap: 14px;
        flex-wrap: wrap;
    }

    .bd-filter-group {
        min-width: 150px;
    }

    .bd-filter-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #4a5568 !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .bd-filter-group select {
        width: 100%;
        height: 38px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0 12px;
        font-size: 13px;
        font-family: inherit;
        color: #1a1a2e !important;
        background: #fff;
        transition: all 0.2s;
    }

    .bd-filter-group select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-light);
    }

    .bd-btn-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: var(--primary);
        color: white !important;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .bd-btn-filter:hover { opacity: 0.9; }

    /* ═══════════════════════════════════════
       TABLE
    ═══════════════════════════════════════ */
    .bd-table {
        width: 100%;
        border-collapse: collapse;
    }

    .bd-table thead th {
        padding: 12px 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #4a5568 !important;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }

    .bd-table tbody td {
        padding: 14px 20px;
        font-size: 14px;
        color: #1a1a2e !important;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .bd-table tbody tr { transition: background 0.15s; }
    .bd-table tbody tr:hover { background: #fafbfe; }
    .bd-table tbody tr:last-child td { border-bottom: none; }

    /* User Cell */
    .bd-user-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .bd-user-cell img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e2e8f0;
        flex-shrink: 0;
    }

    .bd-user-cell-name {
        font-weight: 600;
        font-size: 14px;
        color: #1a1a2e !important;
    }

    .bd-user-cell-id {
        font-size: 11px;
        color: #4a5568 !important;
        margin-top: 1px;
    }

    .bd-user-cell a {
        text-decoration: none;
        color: #1a1a2e !important;
    }

    .bd-user-cell a:hover .bd-user-cell-name {
        color: var(--primary) !important;
    }

    /* Status */
    .bd-status-active {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        background: #ecfdf5;
        color: #065f46 !important;
        border: 1px solid #a7f3d0;
    }

    .bd-status-inactive {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        background: #fef2f2;
        color: #991b1b !important;
        border: 1px solid #fecaca;
    }

    .bd-paid-yes {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        background: #ecfdf5;
        color: #065f46 !important;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #a7f3d0;
    }

    .bd-paid-no {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        background: #fffbeb;
        color: #92400e !important;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #fde68a;
    }

    /* Empty */
    .bd-empty {
        padding: 48px 24px;
        text-align: center;
    }

    .bd-empty i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 12px;
    }

    .bd-empty p {
        font-size: 14px;
        color: #4a5568 !important;
    }

    /* Pagination */
    .bd-pagination {
        padding: 16px 24px;
        border-top: 1px solid #e2e8f0;
    }

    @media (max-width: 768px) {
        .bd-profile { padding: 16px; }
        .bd-hero-content { padding: 0 20px 20px; }
        .bd-hero-banner { height: 90px; }
        .bd-hero-avatar img { width: 80px; height: 80px; }
        .bd-hero-avatar { margin-top: -40px; }
        .bd-hero-name { font-size: 22px; }
        .bd-stats-grid { grid-template-columns: 1fr; }
        .bd-tabs-nav { width: 100%; overflow-x: auto; }
        .bd-tab-btn { flex: 1; justify-content: center; font-size: 13px; padding: 10px 14px; }
        .bd-filter-row { flex-direction: column; }
        .bd-filter-group { width: 100%; }
    }
</style>

<div class="bd-profile">

    {{-- ══════════════ HERO HEADER ══════════════ --}}
    <div class="bd-hero">
        <div class="bd-hero-banner"></div>
        <div class="bd-hero-content">
            <div class="bd-hero-avatar">
                <img src="{{ $bd->display_image }}" alt="{{ $bd->username ?? '' }}">
            </div>
            <div class="bd-hero-info">
                <div>
                    <h1 class="bd-hero-name">{{ $bd->username ?? '' }}</h1>
                    <div class="bd-hero-meta">
                        <div class="bd-meta-item">
                            <i class="fas fa-hashtag"></i>
                            <span>ID: <strong>{{ $bd->id }}</strong></span>
                        </div>
                        <div class="bd-meta-item">
                            <i class="fas fa-user"></i>
                            <span>{{ __("username") }}: <strong>{{ $bd->username ?? '' }}</strong></span>
                        </div>
                    </div>
                </div>
                <a href="{{ url('admin/usersBd') }}" class="bd-btn-back">
                    <i class="fas fa-arrow-left"></i> {{ __("Go Back") }}
                </a>
            </div>
        </div>
    </div>

    {{-- ══════════════ STATS ══════════════ --}}
    <div class="bd-stats-grid">
        <div class="bd-stat-card">
            <div class="bd-stat-header">
                <div class="bd-stat-icon profit"><i class="fas fa-dollar-sign"></i></div>
                <span class="bd-stat-label">{{ __('total proft') }}</span>
            </div>
            <div class="bd-stat-value">{{ truncateAndTrim($bd->total_salary) }}</div>
        </div>
        <div class="bd-stat-card">
            <div class="bd-stat-header">
                <div class="bd-stat-icon count"><i class="fas fa-building"></i></div>
                <span class="bd-stat-label">{{ __('Agency Count') }}</span>
            </div>
            <div class="bd-stat-value">{{ $bd->agencies_count }}</div>
        </div>
    </div>

    @php $activeTab = request('tab', 'agencies'); @endphp

    {{-- ══════════════ TABS NAV ══════════════ --}}
    <div class="bd-tabs-nav">
        <a href="?tab=agencies" class="bd-tab-btn {{ $activeTab === 'agencies' ? 'active' : '' }}" data-target="agencies-tab">
            <i class="fas fa-building"></i> {{ __('agencies') }}
        </a>
        <a href="?tab=transactions" class="bd-tab-btn {{ $activeTab === 'transactions' ? 'active' : '' }}" data-target="transactions-tab">
            <i class="fas fa-exchange-alt"></i> {{ __('transactions') }}
        </a>
        <a href="?tab=target_history" class="bd-tab-btn {{ $activeTab === 'target_history' ? 'active' : '' }}" data-target="target_history-tab">
            <i class="fas fa-history"></i> {{ __('Target History') }}
        </a>
    </div>

    {{-- ══════════════ AGENCIES TAB ══════════════ --}}
    <div class="bd-tab-content {{ $activeTab === 'agencies' ? 'active' : '' }}" id="agencies-tab">
        <div class="bd-card">
            <div class="bd-card-header">
                <h4 class="bd-card-title"><i class="fas fa-building"></i> {{ __('Agencies') }}</h4>
                <span class="bd-count-pill">{{ optional($agencies)->total() ?? 0 }}</span>
            </div>

            @if($agencies && $agencies->count())
                <div class="table-responsive">
                    <table class="bd-table">
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
                                <td style="color:#4a5568; font-weight:600;">{{ $index + 1 + (($agencies->currentPage() - 1) * $agencies->perPage()) }}</td>
                                <td>
                                    <div class="bd-user-cell">
                                        <a href="{{ url('admin/agencies/profile/' . $agency->id) }}">
                                            <img src="{{ getImagePath($agency->img) }}" alt="{{ $agency->name ?? '' }}">
                                        </a>
                                        <div>
                                            <a href="{{ url('admin/agencies/profile/' . $agency->id) }}">
                                                <div class="bd-user-cell-name">{{ $agency->name ?? '' }}</div>
                                            </a>
                                            <div class="bd-user-cell-id">ID: {{ $agency->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="bd-user-cell">
                                        <a href="{{ url('admin/users/' . $agency->owner?->id) }}">
                                            <img src="{{ getImagePath($agency->owner?->profile?->avatar) }}" alt="{{ $agency->owner?->name ?? '' }}">
                                        </a>
                                        <div>
                                            <a href="{{ url('admin/users/' . $agency->owner?->id) }}">
                                                <div class="bd-user-cell-name">{{ $agency->owner?->name ?? '' }}</div>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($agency->status == 1)
                                        <span class="bd-status-active"><i class="fas fa-check-circle"></i> {{ __('Active') }}</span>
                                    @else
                                        <span class="bd-status-inactive"><i class="fas fa-times-circle"></i> {{ __('Inactive') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="bd-pagination">
                    {{ $agencies->appends(['tab' => 'agencies'])->links('vendor.pagination.default') }}
                </div>
            @else
                <div class="bd-empty">
                    <i class="fas fa-building"></i>
                    <p>{{ __('No agencies found') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════ TRANSACTIONS TAB ══════════════ --}}
    <div class="bd-tab-content {{ $activeTab === 'transactions' ? 'active' : '' }}" id="transactions-tab">
        <div class="bd-card">
            <div class="bd-card-header">
                <h4 class="bd-card-title"><i class="fas fa-exchange-alt"></i> {{ __('transactions') }}</h4>
                <span class="bd-count-pill">{{ optional($transactions)->total() ?? 0 }}</span>
            </div>

            @if($transactions && $transactions->count())
                <div class="table-responsive">
                    <table class="bd-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('receiver') }}</th>
                            <th>{{ __('usd') }}</th>
                            <th>{{ __('amount') }}</th>
                            <th>{{ __('Created') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($transactions as $index => $charge)
                            @php
                                $receiverHtml = '';

                                if ($charge->receiveragency) {
                                    $agency = $charge->receiveragency;
                                    if ($agency) {
                                        $cacheKey = "agency_image_{$agency->id}";
                                        $image = \Cache::remember($cacheKey, 3600, function () use ($agency) {
                                            $path = @$agency->img;
                                            $defaultImage = asset("images/icon-agency.jpg");
                                            $url = getImagePath($path) ?? $defaultImage;
                                            if (!isImageExists($url)) $url = $defaultImage;
                                            return handleShowImageWithTypes($agency->id, $url, 40, 40);
                                        });
                                        $name = $agency->name ?? '';
                                        $profileUrl = route('admin.agency.profile', ['id' => $agency->id]);
                                        $receiverHtml = "
                                            <a href='{$profileUrl}' style='text-decoration:none;color:inherit;'>
                                                <div style='display:flex;align-items:center;gap:10px;'>
                                                    {$image}
                                                    <div>
                                                        <div style='font-weight:600;color:#1a1a2e;'>{$name}</div>
                                                        <div style='font-size:11px;color:#4a5568;'>ID: {$agency->id}</div>
                                                    </div>
                                                </div>
                                            </a>
                                        ";
                                    }
                                } else {
                                    $receiverUser = null;
                                    if ($charge->user_id) {
                                        $receiverUser = \App\Models\User::find($charge->user_id);
                                    }

                                    if ($receiverUser) {
                                        $path = $receiverUser->profile?->avatar ?? null;
                                        $defaultImage = asset("images/businessman-icon.jpg");
                                        $url = getImagePath($path) ?? $defaultImage;
                                        if (!isImageExists($url)) $url = $defaultImage;
                                        $image = handleShowImageWithTypes($receiverUser->id, $url, 40, 40);
                                        $showUrl = url("admin/users/{$receiverUser->id}");
                                        $name = e($receiverUser->name ?? '');
                                        $uuid = e($receiverUser->uuid ?? '');
                                        $receiverHtml = "
                                            <a href='{$showUrl}' style='text-decoration:none;color:inherit;'>
                                                <div style='display:flex;align-items:center;gap:10px;'>
                                                    {$image}
                                                    <div>
                                                        <div style='font-weight:600;color:#1a1a2e;'>{$name}</div>
                                                        <div style='font-size:11px;color:#4a5568;'>UUID: {$uuid}</div>
                                                    </div>
                                                </div>
                                            </a>
                                        ";
                                    } else {
                                        $receiverHtml = '<span style="color:#dc2626;">' . __('Unknown') . '</span>';
                                    }
                                }
                            @endphp
                            <tr>
                                <td style="color:#4a5568; font-weight:600;">{{ $transactions->firstItem() + $index }}</td>
                                <td>{!! $receiverHtml !!}</td>
                                <td style="font-weight:600;">{{ number_format($charge->usd ?? 0, 2) }}</td>
                                <td style="font-weight:600;">{{ number_format($charge->amount ?? 0, 2) }}</td>
                                <td style="color:#4a5568;">{{ $charge->created_at }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="bd-pagination">
                    {{ $transactions->appends(['tab' => 'transactions'])->links('vendor.pagination.default') }}
                </div>
            @else
                <div class="bd-empty">
                    <i class="fas fa-exchange-alt"></i>
                    <p>{{ __('No transactions found') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════ TARGET HISTORY TAB ══════════════ --}}
    <div class="bd-tab-content {{ $activeTab === 'target_history' ? 'active' : '' }}" id="target_history-tab">
        <div class="bd-card">
            <div class="bd-card-header">
                <h4 class="bd-card-title"><i class="fas fa-history"></i> {{ __('Target History') }}</h4>
            </div>

            <div class="bd-filter-bar">
                <form method="GET">
                    <input type="hidden" name="tab" value="target_history">
                    <div class="bd-filter-row">
                        <div class="bd-filter-group">
                            <label>{{ __('Month') }}</label>
                            <select name="month">
                                @foreach(range(1,12) as $m)
                                    <option value="{{ $m }}" {{ request('month', now()->month) == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bd-filter-group">
                            <label>{{ __('Year') }}</label>
                            <select name="year">
                                @foreach(range(now()->year-5, now()->year) as $y)
                                    <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="padding-bottom:2px;">
                            <button type="submit" class="bd-btn-filter">
                                <i class="fas fa-filter"></i> {{ __('Filter') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @if($target_history && $target_history->count())
                <div class="table-responsive">
                    <table class="bd-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Agency ID') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Month') }}</th>
                            <th>{{ __('Year') }}</th>
                            <th>{{ __('Is Paid') }}</th>
                            <th>{{ __('Created At') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($target_history as $index => $item)
                            <tr>
                                <td style="color:#4a5568; font-weight:600;">{{ $target_history->firstItem() + $index }}</td>
                                <td style="font-weight:600;">{{ $item->agency_id }}</td>
                                <td style="font-weight:600;">{{ truncateAndTrim($item->amount) }}</td>
                                <td>{{ $item->month }}</td>
                                <td>{{ $item->year }}</td>
                                <td>
                                    @if($item->is_paid)
                                        <span class="bd-paid-yes"><i class="fas fa-check"></i> {{ __('Yes') }}</span>
                                    @else
                                        <span class="bd-paid-no"><i class="fas fa-clock"></i> {{ __('No') }}</span>
                                    @endif
                                </td>
                                <td style="color:#4a5568;">{{ $item->created_at }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="bd-pagination">
                    {{ $target_history->appends(request()->except('page'))->links('vendor.pagination.default') }}
                </div>
            @else
                <div class="bd-empty">
                    <i class="fas fa-calendar-times"></i>
                    <p>{{ __('No target history found') }}</p>
                </div>
            @endif
        </div>
    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedTab = urlParams.get('tab') || 'agencies';
        const allTabs = document.querySelectorAll('.bd-tab-btn');

        allTabs.forEach(tab => {
            const target = tab.getAttribute('data-target');
            const content = document.getElementById(target);
            if (!content) return;

            if (target === selectedTab + '-tab') {
                tab.classList.add('active');
                content.style.display = 'block';
                content.classList.add('active');
            } else {
                tab.classList.remove('active');
                content.style.display = 'none';
                content.classList.remove('active');
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
</script>
