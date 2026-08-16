<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: {{ config('themes.primaryColor', '#6366f1') }};
            --primary-light: {{ config('themes.primaryColor', '#6366f1') }}18;
            --primary-medium: {{ config('themes.primaryColor', '#6366f1') }}30;
            --secondary: #f8fafc;
            --text-primary: #1a1a2e;
            --text-secondary: #4a5568;
            --bg-card: #ffffff;
            --bg-page: #f1f5f9;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 25px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04);
            --shadow-xl: 0 20px 40px -4px rgba(0,0,0,0.1);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body, .profile-container, .profile-container * {
            color: #1a1a2e;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-page);
            color: #1a1a2e !important;
            -webkit-font-smoothing: antialiased;
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        /* ═══════════════════════════════════════
           HERO HEADER CARD
        ═══════════════════════════════════════ */
        .hero-card {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-bottom: 24px;
            border: 1px solid var(--border);
        }

        .hero-banner {
            height: 120px;
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 50%, #a855f7 100%);
            position: relative;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .hero-content {
            padding: 0 32px 28px;
            position: relative;
        }

        .hero-avatar-wrapper {
            margin-top: -50px;
            position: relative;
            display: inline-block;
        }

        .hero-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid var(--bg-card);
            box-shadow: var(--shadow-md);
            object-fit: cover;
            background: #e2e8f0;
        }

        .hero-info {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-top: 16px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .hero-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .hero-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .hero-meta-item i {
            font-size: 12px;
            color: var(--primary);
            width: 16px;
            text-align: center;
        }

        .hero-meta-item strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        .btn-go-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--bg-card);
            color: var(--text-secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-go-back:hover {
            background: #f8fafc;
            color: var(--text-primary);
            border-color: #cbd5e1;
            box-shadow: var(--shadow-sm);
        }

        /* ═══════════════════════════════════════
           LEVEL SECTION
        ═══════════════════════════════════════ */
        .level-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
        }

        .level-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .level-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .level-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-medium);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .level-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .level-diamonds {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .level-diamonds i {
            color: #f59e0b;
            margin-right: 4px;
        }

        .badge-max-level {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .level-next {
            text-align: right;
        }

        .level-next-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .level-next-remaining {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* Progress Bar */
        .progress-wrapper {
            margin-bottom: 24px;
        }

        .progress-track {
            background: #e2e8f0;
            border-radius: 50px;
            height: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), #a855f7);
            border-radius: 50px;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 4px;
            position: relative;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .progress-labels {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .progress-percent {
            font-weight: 700;
            color: var(--primary);
            font-size: 13px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: var(--radius-md);
            padding: 18px 16px;
            text-align: center;
            border: 1px solid var(--border);
            transition: all 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-medium);
        }

        .stat-card .stat-value {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .stat-card .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 4px;
        }

        /* ═══════════════════════════════════════
           TAB NAVIGATION
        ═══════════════════════════════════════ */
        .tabs-nav {
            display: flex;
            gap: 4px;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 6px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-bottom: 24px;
            width: fit-content;
        }

        .tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.25s;
            border: none;
            background: transparent;
            cursor: pointer;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: var(--text-primary);
            background: #f1f5f9;
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.35);
        }

        .tab-btn.active:hover {
            background: var(--primary);
            color: white;
        }

        .tab-btn i {
            font-size: 14px;
        }

        .tab-content { display: none; animation: fadeSlide 0.3s ease; }
        .tab-content.active { display: block; }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ═══════════════════════════════════════
           CONTENT CARDS
        ═══════════════════════════════════════ */
        .content-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header-title i {
            color: var(--primary);
            font-size: 16px;
        }

        .count-pill {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
        }

        /* Filter Bar */
        .filter-bar {
            padding: 16px 24px;
            background: #fafbfc;
            border-bottom: 1px solid var(--border);
        }

        .filter-row {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            flex-wrap: wrap;
        }

        .filter-group {
            min-width: 160px;
        }

        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            height: 38px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0 12px;
            font-size: 13px;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--bg-card);
            transition: all 0.2s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .filter-actions {
            display: flex;
            gap: 8px;
            padding-bottom: 2px;
        }

        .btn-search {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-search:hover { opacity: 0.9; transform: translateY(-1px); }

        .btn-reset-filter {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--bg-card);
            color: var(--text-secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-reset-filter:hover { background: #f1f5f9; color: var(--text-primary); }

        /* ═══════════════════════════════════════
           DATA TABLE
        ═══════════════════════════════════════ */
        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modern-table thead th {
            padding: 12px 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-secondary);
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }

        .modern-table tbody td {
            padding: 14px 20px;
            font-size: 14px;
            color: var(--text-primary);
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .modern-table tbody tr {
            transition: background 0.15s;
        }

        .modern-table tbody tr:hover {
            background: #fafbfe;
        }

        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* User Cell */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-cell-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            flex-shrink: 0;
        }

        .user-cell-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
        }

        .user-cell-uid {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 1px;
        }

        /* Badges */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }

        .role-badge.owner {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
        }

        .role-badge.admin {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .role-badge.member {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .diamond-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            color: #92400e;
        }

        .diamond-badge i { color: #f59e0b; }

        .remaining-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            color: #991b1b;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-action.admin-toggle {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .btn-action.admin-toggle:hover {
            background: #dbeafe;
            box-shadow: var(--shadow-sm);
        }

        .btn-action.admin-toggle.is-admin {
            background: #fff7ed;
            color: #c2410c;
            border-color: #fed7aa;
        }

        .btn-action.admin-toggle.is-admin:hover {
            background: #ffedd5;
        }

        .btn-action.kick {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-action.kick:hover {
            background: #fee2e2;
            box-shadow: var(--shadow-sm);
        }

        /* Supporters */
        .supporters-stack {
            display: flex;
            align-items: center;
        }

        .supporters-stack img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--bg-card);
            margin-left: -8px;
            transition: transform 0.2s;
        }

        .supporters-stack img:first-child { margin-left: 0; }
        .supporters-stack img:hover { transform: scale(1.15); z-index: 2; }

        /* Empty State */
        .empty-state {
            padding: 48px 24px;
            text-align: center;
        }

        .empty-state i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Pagination */
        .pagination-wrapper {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
        }

        /* ═══════════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════════ */
        @media (max-width: 768px) {
            .profile-container { padding: 16px; }
            .hero-content { padding: 0 20px 20px; }
            .hero-banner { height: 90px; }
            .hero-avatar { width: 80px; height: 80px; }
            .hero-avatar-wrapper { margin-top: -40px; }
            .hero-title { font-size: 22px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .level-card { padding: 18px; }
            .tabs-nav { width: 100%; }
            .tab-btn { flex: 1; justify-content: center; }
            .filter-row { flex-direction: column; }
            .filter-group { width: 100%; }
            .action-group { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="profile-container" id="pjax-container">

    {{-- ══════════════════════════════════
         HERO HEADER
    ══════════════════════════════════ --}}
    <div class="hero-card">
        <div class="hero-banner"></div>
        <div class="hero-content">
            <div class="hero-avatar-wrapper">
                <img src="{{ getImagePath(@$family->image) }}" alt="{{ @$family->name }}" class="hero-avatar">
            </div>
            <div class="hero-info">
                <div>
                    <h1 class="hero-title">{{ @$family->name ?? '' }}</h1>
                    <div class="hero-meta">
                        <div class="hero-meta-item">
                            <i class="fas fa-hashtag"></i>
                            <span>ID: <strong>{{ $family->id }}</strong></span>
                        </div>
                        <div class="hero-meta-item">
                            <i class="fas fa-crown"></i>
                            <span>{{ __('Owner') }}: <strong>{{ @$family->owner->name ?? '' }}</strong> <span style="opacity:0.6">({{ @$family->owner->uuid ?? '' }})</span></span>
                        </div>
                        <div class="hero-meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>{{ $family->created_at }}</span>
                        </div>
                    </div>
                </div>
                <a href="{{ url('admin/families') }}" class="btn-go-back">
                    <i class="fas fa-arrow-left"></i> {{ __('Go Back') }}
                </a>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════
         LEVEL & PROGRESS
    ══════════════════════════════════ --}}
    <div class="level-card">
        <div class="level-top">
            <div class="level-left">
                @if(!empty($familyLevel['level_img']))
                    <img src="{{ getImagePath($familyLevel['level_img']) }}" alt="Level" class="level-icon">
                @endif
                <div>
                    <div class="level-name">{{ $familyLevel['level_name'] ?: __('No Level') }}</div>
                    <div class="level-diamonds">
                        <i class="fas fa-gem"></i> {{ __('Total Diamonds') }}: {{ number_format($familyLevel['family_exp']) }}
                    </div>
                </div>
            </div>
            @if(!$familyLevel['is_last_level'])
                <div class="level-next">
                    <div class="level-next-label">{{ __('Next') }}: {{ $familyLevel['next_name'] }}</div>
                    <div class="level-next-remaining">{{ number_format($familyLevel['rem']) }} {{ __('remaining') }}</div>
                </div>
            @else
                <span class="badge-max-level"><i class="fas fa-trophy"></i> {{ __('Max Level') }}</span>
            @endif
        </div>

        <div class="progress-wrapper">
            <div class="progress-track">
                <div class="progress-fill" style="width: {{ round($familyLevel['per'] * 100) }}%"></div>
            </div>
            <div class="progress-labels">
                <span>{{ number_format($familyLevel['level_exp']) }}</span>
                <span class="progress-percent">{{ round($familyLevel['per'] * 100) }}%</span>
                <span>{{ number_format($familyLevel['next_exp']) }}</span>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ $family->members_count }}</div>
                <div class="stat-label">{{ __('members') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $family->admins_num }}</div>
                <div class="stat-label">{{ __('admin.admin') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $family->num }}</div>
                <div class="stat-label">{{ __('Max Members') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $family->num_admins }}</div>
                <div class="stat-label">{{ __('Max Admins') }}</div>
            </div>
        </div>
    </div>

    @php $activeTab = request('tab', 'members'); @endphp

    {{-- ══════════════════════════════════
         TAB NAVIGATION
    ══════════════════════════════════ --}}
    <div class="tabs-nav">
        <a href="?tab=members" class="tab-btn {{ $activeTab == 'members' ? 'active' : '' }}" data-target="members-tab">
            <i class="fas fa-users"></i> {{ __('members') }}
        </a>
        <a href="?tab=targets" class="tab-btn {{ $activeTab == 'targets' ? 'active' : '' }}" data-target="targets-tab">
            <i class="fas fa-bullseye"></i> {{ __('Targets') }}
        </a>
    </div>

    {{-- ══════════════════════════════════
         MEMBERS TAB
    ══════════════════════════════════ --}}
    <div class="tab-content {{ $activeTab == 'members' ? 'active' : '' }}" id="members-tab">
        <div class="content-card">
            <div class="card-header">
                <div class="card-header-title">
                    <i class="fas fa-users"></i> {{ __('members') }}
                </div>
            </div>

            <div class="filter-bar">
                <form action="{{ url('admin/families/' . $family->id) }}" method="get">
                    <input type="hidden" name="tab" value="members">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>{{ __('type') }}</label>
                            @php $filterType = is_array(request('type')) ? null : request('type'); @endphp
                            <select name="type">
                                <option value="">{{ __('select') }}</option>
                                <option value="2" {{ $filterType === '2' ? 'selected' : '' }}>{{ __('Owner') }}</option>
                                <option value="1" {{ $filterType === '1' ? 'selected' : '' }}>{{ __('admin.admin') }}</option>
                                <option value="0" {{ $filterType === '0' ? 'selected' : '' }}>{{ __('members') }}</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn-search">
                                <i class="fas fa-search"></i> {{ __('Search') }}
                            </button>
                            <a href="{{ url('admin/families/' . $family->id . '?tab=members') }}" class="btn-reset-filter">
                                <i class="fas fa-undo"></i> {{ __('Reset') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('user') }}</th>
                        <th>{{ __('type') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                    </thead>
                    @if($familyMembers && $familyMembers->count())
                        <tbody>
                        @foreach($familyMembers as $index => $familyMember)
                            <tr id="member-row-{{ $familyMember->id }}">
                                <td style="color:var(--text-secondary); font-weight:600;">{{ $familyMembers->firstItem() + $index }}</td>
                                <td>
                                    <div class="user-cell">
                                        <img src="{{ getImagePath(@$familyMember->user->profile->avatar) }}"
                                             alt="{{ @$familyMember->user->name }}" class="user-cell-avatar">
                                        <div>
                                            <div class="user-cell-name">{{ @$familyMember->user->name }}</div>
                                            <div class="user-cell-uid">UID: {{ @$familyMember->user->uuid }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($familyMember->user_type == 2)
                                        <span class="role-badge owner"><i class="fas fa-crown"></i> {{ __('Owner') }}</span>
                                    @elseif($familyMember->user_type == 1)
                                        <span class="role-badge admin"><i class="fas fa-shield-alt"></i> {{ __('admin.admin') }}</span>
                                    @else
                                        <span class="role-badge member"><i class="fas fa-user"></i> {{ __('members') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($familyMember->user_type != 2)
                                        <div class="action-group">
                                            <button class="btn-action admin-toggle {{ $familyMember->user_type == 1 ? 'is-admin' : '' }} btn-toggle-admin"
                                                    data-id="{{ $familyMember->id }}">
                                                <i class="fas {{ $familyMember->user_type == 1 ? 'fa-user-minus' : 'fa-user-shield' }}"></i>
                                                {{ $familyMember->user_type == 1 ? __('Remove Admin') : __('Make Admin') }}
                                            </button>
                                            <button class="btn-action kick btn-kick" data-id="{{ $familyMember->id }}">
                                                <i class="fas fa-times"></i> {{ __('Kick') }}
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    @else
                        <tbody>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="fas fa-users-slash"></i>
                                    <p>{{ __('No members found.') }}</p>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    @endif
                </table>
            </div>

            <div class="pagination-wrapper">
                {{ $familyMembers->appends(['tab' => 'members', 'type' => $filterType])->links('vendor.pagination.default') }}
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════
         TARGETS TAB
    ══════════════════════════════════ --}}
    <div class="tab-content {{ $activeTab == 'targets' ? 'active' : '' }}" id="targets-tab">
        <div class="content-card">
            <div class="card-header">
                <div class="card-header-title">
                    <i class="fas fa-bullseye"></i> {{ __('Targets') }}
                </div>
                <span class="count-pill">{{ $memberTargets->total() }}</span>
            </div>

            <div class="filter-bar">
                <form method="GET" action="{{ url('admin/families/' . $family->id) }}">
                    <input type="hidden" name="tab" value="targets">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>{{ __('Month') }}</label>
                            <select name="month">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>{{ __('Year') }}</label>
                            <select name="year">
                                @for($y = now()->year; $y >= 2020; $y--)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn-search">
                                <i class="fas fa-filter"></i> {{ __('Apply') }}
                            </button>
                            <a href="{{ url('admin/families/' . $family->id . '?tab=targets') }}" class="btn-reset-filter">
                                <i class="fas fa-times"></i> {{ __('Reset') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Diamonds') }}</th>
                        <th>{{ __('Remaining') }}</th>
                        <th>{{ __('Days') }}</th>
                        <th>{{ __('Hours') }}</th>
                        <th>{{ __('Supporters') }}</th>
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

                                $giftLogs = \App\Models\GiftLog::where('receiver_family_id', $family->id)
                                    ->where('receiver_id', $memberTarget->id)
                                    ->whereHas('sender')
                                    ->with('sender.profile')
                                    ->whereYear('created_at', $year)
                                    ->whereMonth('created_at', $month)
                                    ->selectRaw("sum(giftPrice) as exp, sender_id")
                                    ->groupBy('sender_id')
                                    ->orderByRaw("exp desc")
                                    ->limit(3)
                                    ->get()
                                    ->reject(fn($q) => $q->exp == 0);

                                $target = $memberTarget->targets->first();
                            @endphp
                            <tr>
                                <td style="color:var(--text-secondary); font-weight:600;">{{ $memberTargets->firstItem() + $index }}</td>
                                <td>
                                    <div class="user-cell">
                                        <img src="{{ $avatarUrl }}" class="user-cell-avatar" alt="{{ $name }}">
                                        <div>
                                            <div class="user-cell-name">{{ $name }}</div>
                                            <div class="user-cell-uid">{{ $uid }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="diamond-badge"><i class="fas fa-gem"></i> {{ number_format($target->user_diamonds ?? 0) }}</span>
                                </td>
                                <td>
                                    <span class="remaining-badge"><i class="fas fa-arrow-down"></i> {{ number_format($target->next_diamond ?? 0) }}</span>
                                </td>
                                <td style="font-weight:600; font-size:13px;">{{ ($target->user_days ?? 0) . '/' . ($target->target_days ?? 0) }}</td>
                                <td style="font-weight:600; font-size:13px;">{{ ($target->user_hours ?? 0) . '/' . ($target->target_hours ?? 0) }}</td>
                                <td>
                                    <div class="supporters-stack">
                                        @foreach($giftLogs as $supporter)
                                            @php
                                                $sender = $supporter->sender;
                                                $supporterAvatar = $sender->profile->avatar ?? null;
                                                $supporterUrl = getImagePath($supporterAvatar) ?? $defaultImage;
                                                if (!isImageExists($supporterUrl)) { $supporterUrl = $defaultImage; }
                                            @endphp
                                            <img src="{{ $supporterUrl }}" title="{{ $sender->name ?? '' }}" alt="Supporter">
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    @else
                        <tbody>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-bullseye"></i>
                                    <p>{{ __('No target data available') }}</p>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    @endif
                </table>
            </div>

            @if($memberTargets && $memberTargets->count())
                <div class="pagination-wrapper">
                    {{ $memberTargets->appends(['tab' => 'targets', 'month' => $month, 'year' => $year])->links('vendor.pagination.default') }}
                </div>
            @endif
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-pjax@2.0.1/jquery.pjax.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedTab = urlParams.get('tab') || 'members';
        const allTabs = document.querySelectorAll('.tab-btn');

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

    function showLoader() {
        Swal.fire({
            title: '{{ __("Loading...") }}',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }

    function showSuccess(message, callback) {
        Swal.fire({ icon: 'success', title: message, confirmButtonText: 'OK' })
            .then(() => { if (callback) callback(); });
    }

    function showError(message) {
        Swal.fire({ icon: 'error', title: message, confirmButtonText: 'OK' });
    }

    function confirmAction(message, onConfirm) {
        Swal.fire({
            title: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes") }}',
            cancelButtonText: '{{ __("Cancel") }}'
        }).then(result => {
            if (result.isConfirmed) onConfirm();
        });
    }

    function bindActionButtons() {
        $(document).off('click', '.btn-kick').on('click', '.btn-kick', function () {
            const id = $(this).data('id');
            confirmAction('{{ __("Are you sure you want to remove this member?") }}', () => {
                showLoader();
                $.post(`/admin/families/kick/${id}`, {
                    _token: '{{ csrf_token() }}'
                }, function (response) {
                    Swal.close();
                    if (response.status) {
                        showSuccess(response.message, () => { location.reload(); });
                    } else { showError(response.message); }
                }).fail(function (xhr) {
                    Swal.close();
                    showError(xhr.responseJSON?.message ?? '{{ __("Operation failed") }}');
                });
            });
        });

        $(document).off('click', '.btn-toggle-admin').on('click', '.btn-toggle-admin', function () {
            const id = $(this).data('id');
            const isAdmin = $(this).hasClass('is-admin');
            const message = isAdmin
                ? '{{ __("Are you sure you want to remove admin privileges?") }}'
                : '{{ __("Are you sure you want to make this user an admin?") }}';

            confirmAction(message, () => {
                showLoader();
                $.post(`/admin/families/toggle-admin/${id}`, {
                    _token: '{{ csrf_token() }}'
                }, function (response) {
                    Swal.close();
                    if (response.status) {
                        showSuccess(response.message, () => { location.reload(); });
                    } else { showError(response.message); }
                }).fail(function (xhr) {
                    Swal.close();
                    showError(xhr.responseJSON?.message ?? '{{ __("Operation failed") }}');
                });
            });
        });
    }

    $(document).ready(function () { bindActionButtons(); });
</script>

</body>
</html>
