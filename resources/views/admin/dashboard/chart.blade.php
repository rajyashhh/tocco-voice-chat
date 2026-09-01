{{-- Admin Dashboard --}}
{{-- eslint-disable --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * {
        box-sizing: border-box;
    }

    body {
        background-color: #f8f9fa;
        color: #212529;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        line-height: 1.6;
        margin: 0;
        padding: 0;
    }

    .dashboard-container {
        min-height: 100vh;
        padding: 20px;
    }

    .dashboard-container {
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
        margin-right: auto;
        margin-left: auto;
    }

    .section-header {
        font-size: 2.5rem;
        font-weight: 800;
        text-align: center;
        margin: 60px 0 40px 0;
        position: relative;
        background: var(--gradient-accent);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -0.02em;
    }

    .section-header::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: var(--gradient-accent);
        border-radius: 2px;
        box-shadow: var(--shadow-glow);
    }

    .card {
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .card-header {
        border-bottom: 1px solid #dee2e6;
        padding: 16px 20px;
        font-weight: 600;
        font-size: 1.1rem;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-body {
        padding: 20px;
    }

    .balance-card-premium {
        background: var(--gradient-primary);
        color: white;
        position: relative;
        overflow: hidden;
    }

    .balance-card-premium::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: conic-gradient(from 0deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: rotate 8s linear infinite;
    }

    @keyframes rotate {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .chart-container {
        background: none;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }

    [dir="rtl"] .chart-container {
        direction: ltr;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        border-radius: 6px;
        padding: 10px 20px;
        color: white;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        color: white;
        text-decoration: none;
    }

    .alert-premium {
        background: var(--gradient-danger);
        border: none;
        border-radius: 16px;
        color: white;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-danger);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 0, 128, 0.2);
    }

    .stats-masonry {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
        margin-bottom: 40px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
        margin-bottom: 40px;
    }

    .col-md-1, .col-md-10, .col-md-11, .col-md-12, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-7,
    .col-md-8, .col-md-9 {
        float: none !important;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
    }

    .widget-card {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: box-shadow 0.3s ease;
        width: 100%;
    }

    .widget-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Responsive for masonry grid */
    @media (max-width: 768px) {
        .stats-masonry {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }

    @media (max-width: 480px) {
        .stats-masonry {
            gap: 12px;
        }
    }

    .widget-card-premium {
        background: var(--bg-glass);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--border-light);
        border-radius: 20px;
        padding: 24px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: var(--shadow-soft);
        position: relative;
        overflow: hidden;
    }

    .widget-card-premium::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--gradient-accent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .widget-card-premium:hover::before {
        opacity: 1;
    }

    .widget-card-premium:hover {
        background: var(--bg-glass-hover);
        border-color: var(--border-glow);
        box-shadow: var(--shadow-soft), var(--shadow-glow);
        transform: translateY(-6px) scale(1.02);
    }

    .filter-form-premium {
        background: var(--bg-glass);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--border-light);
        border-radius: 16px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-soft);
    }

    .filter-form-premium input,
    .filter-form-premium button {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--border-light);
        color: var(--text-primary);
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .filter-form-premium input:focus,
    .filter-form-premium button:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
        background: rgba(255, 255, 255, 0.08);
    }

    .filter-form-premium input::placeholder {
        color: var(--text-muted);
    }

    .fade-in-up {
        animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        opacity: 0;
        transform: translateY(30px);
    }

    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stagger-animation {
        animation-delay: calc(var(--stagger) * 0.1s);
    }

    .pulse-glow {
        animation: pulseGlow 2s ease-in-out infinite alternate;
    }

    @keyframes pulseGlow {
        from {
            box-shadow: var(--shadow-soft);
        }
        to {
            box-shadow: var(--shadow-soft), var(--shadow-glow);
        }
    }

    @media (max-width: 768px) {
        .dashboard-container {
            padding: 10px;
        }

        .section-header {
            font-size: 1.5rem;
            margin: 20px 0 15px 0;
        }

        .stats-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .card-body {
            padding: 15px;
        }

        .card-header {
            padding: 12px 16px;
            font-size: 1rem;
        }

        .balance-card .row > div {
            margin-bottom: 15px;
        }

        .tab-content {
            padding: 15px;
            margin: 0 -10px;
        }

        .widget-card {
            margin-bottom: 15px;
        }
    }

    /* Media query for phones 400-500px width */
    @media (max-width: 500px) and (min-width: 400px) {
        .dashboard-container {
            padding: 8px !important;  /* Changed: symmetric padding */
            width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;  /* Added: was missing */
            overflow-x: hidden;
        }

        .section-header {
            font-size: 1.3rem;
            margin: 18px 0 12px 0;
            padding: 0 8px;  /* Changed: symmetric */
        }

        .stats-grid {
            gap: 12px;
            grid-template-columns: 1fr;
            padding: 0;  /* Changed: removed asymmetric padding */
            margin: 0;
        }

        .card-body {
            padding: 12px;
        }

        .card-header {
            padding: 11px 14px;
            font-size: 0.95rem;
        }

        .tab-content {
            padding: 12px 8px !important;  /* Changed: symmetric */
            margin: 0 !important;
            border-radius: 8px;
        }

        .tabs-container {
            padding: 0 !important;
            margin: 0 !important;
        }

        .widget-card {
            margin-bottom: 12px;
            overflow-x: auto;
            margin-left: 0 !important;  /* Changed */
            margin-right: 0 !important;  /* Changed */
        }

        .widget-card-premium {
            padding: 18px;
            border-radius: 16px;
            margin-left: 0 !important;  /* Changed */
            margin-right: 0 !important;  /* Changed */
        }

        .balance-card .row > div {
            margin-bottom: 12px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-left: 0 !important;  /* Changed: was -4px */
            margin-right: 0 !important;  /* Changed: was -4px */
        }

        .col-md-6, .col-lg-3, .col-xl-2, .col-md-12, .col-lg-6, .col-12 {
            width: 100% !important;
            flex: 0 0 100%;
            padding-left: 0 !important;  /* Changed */
            padding-right: 0 !important;  /* Changed */
        }

        table {
            font-size: 0.85rem;
        }

        canvas {
            max-width: 100%;
            height: auto !important;
        }

        .nav-tabs {
            justify-content: flex-start;
            padding: 3px 4px !important;  /* Changed: symmetric */
            margin: 0 !important;  /* Changed */
            border-radius: 8px;
        }

        .stats-masonry {
            padding: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
    }

    @media (max-width: 480px) {
        .dashboard-container {
            padding: 4px !important;  /* Smaller symmetric padding */
            width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            overflow-x: hidden;
        }

        .section-header {
            font-size: 1.2rem;
            margin: 12px 0 8px 0;  /* Smaller margins */
            padding: 0 4px;  /* Smaller symmetric padding */
        }

        .stats-grid {
            gap: 8px;  /* Smaller gap */
            grid-template-columns: 1fr;
            padding: 0;
            margin: 0;
        }

        .card-body {
            padding: 8px;  /* Smaller padding */
        }

        .card-header {
            padding: 8px 10px;  /* Smaller padding */
            font-size: 0.9rem;
        }

        .tab-content {
            padding: 8px 4px !important;  /* Smaller symmetric padding */
            margin: 0 !important;
            border-radius: 8px;
        }

        .tabs-container {
            padding: 0 !important;
            margin: 0 !important;
        }

        .widget-card {
            margin-bottom: 8px;  /* Smaller margin */
            overflow-x: auto;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .widget-card-premium {
            padding: 12px;  /* Smaller padding */
            border-radius: 12px;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .balance-card .row > div {
            margin-bottom: 8px;  /* Smaller margin */
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .col-md-6, .col-lg-3, .col-xl-2, .col-md-12, .col-lg-6, .col-12 {
            width: 100% !important;
            flex: 0 0 100%;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        table {
            font-size: 0.8rem;  /* Smaller font */
        }

        canvas {
            max-width: 100%;
            height: auto !important;
        }

        .nav-tabs {
            justify-content: flex-start;
            padding: 2px 3px !important;  /* Smaller symmetric padding */
            margin: 0 !important;
            border-radius: 8px;
        }

        .stats-masonry {
            padding: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
    }

    /* Responsive for small desktops/tablets 800-1024px */
    @media (max-width: 1024px) and (min-width: 800px) {
        .dashboard-container {
            padding: 12px !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            overflow-x: hidden;
        }

        .section-header {
            font-size: 1.6rem;
            margin: 20px 0 14px 0;
            padding: 0;
        }

        .stats-grid {
            gap: 12px;
            grid-template-columns: 1fr;  /* Single column for better fit */
            padding: 0;
            margin: 0;
        }

        .stats-masonry {
            grid-template-columns: 1fr;  /* Single column */
            gap: 12px;
            padding: 0;
            margin: 0;
        }

        .card {
            margin-bottom: 12px;
        }

        .card-body {
            padding: 14px;
        }

        .card-header {
            padding: 12px 16px;
            font-size: 0.95rem;
        }

        .tab-content {
            padding: 12px !important;
            margin: 0 !important;
            border-radius: 8px;
        }

        .tabs-container {
            padding: 0 !important;
            margin: 0 0 16px 0 !important;
        }

        .widget-card {
            margin-bottom: 12px;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .widget-card-premium {
            padding: 16px;
            border-radius: 14px;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-left: 0 !important;
            margin-right: 0 !important;
            gap: 12px;
        }

        /* Force single column layout for cleaner appearance */
        .col-md-6,
        .col-lg-3,
        .col-xl-2,
        .col-md-12,
        .col-lg-6,
        .col-12,
        .col-lg-4,
        .col-xl-3,
        [class*="col-"] {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        /* Or if you want 2 columns, use this instead:
        .col-lg-6,
        .col-md-6 {
            width: calc(50% - 6px) !important;
            flex: 0 0 calc(50% - 6px) !important;
            max-width: calc(50% - 6px) !important;
        }
        */

        table {
            font-size: 0.85rem;
        }

        .table th,
        .table td {
            padding: 8px 10px;
        }

        canvas {
            max-width: 100%;
            height: auto !important;
        }

        .nav-tabs {
            justify-content: flex-start;
            padding: 4px !important;
            margin: 0 !important;
            border-radius: 8px;
            overflow-x: auto;
        }

        .nav-tabs li {
            margin: 2px 4px;
        }

        .nav-tabs .nav-link {
            font-size: 0.85rem;
            padding: 8px 14px;
            white-space: nowrap;
        }

        /* RTL specific fixes */
        [dir="rtl"] .dashboard-container {
            padding: 12px !important;
        }

        [dir="rtl"] .row {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        [dir="rtl"] [class*="col-"] {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        /* Ensure content fills available width */
        .tab-pane {
            width: 100%;
        }

        .tab-pane > .col-md-12 {
            padding: 0 !important;
        }

        /* Fix for mb-3 and mb-4 classes */
        .mb-3 {
            margin-bottom: 12px !important;
        }

        .mb-4 {
            margin-bottom: 16px !important;
        }
    }

    /* Tab Styles */
    .tabs-container {
        margin-bottom: 30px;
    }

    .nav-tabs {
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 4px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .nav-tabs .nav-link {
        background: transparent;
        border: none;
        color: #6c757d;
        font-weight: 500;
        font-size: 0.95rem;
        padding: 12px 20px;
        margin: 0 2px;
        border-radius: 6px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .nav-tabs .nav-link:hover {
        background-color: var(--primary-color);
        color: #495057;
    }

    .nav-tabs .nav-link.active {
        background-color: var(--primary-color);
        color: white;
        box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
    }

    .tab-content {
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding-top: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

    }

    .tab-pane {
        animation: fadeIn 0.5s ease forwards;
        opacity: 0;
    }

    .nav-tabs {
        display: flex;
        justify-content: center;
        align-items: center;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .nav-tabs::-webkit-scrollbar {
        display: none;
    }

    .nav-tabs li {
        list-style: none;
        margin: 0 10px;
        flex-shrink: 0;
    }

    img {
        display: unset !important;
    }

    /* Responsive adjustments for tabs */
    @media (max-width: 768px) {
        .nav-tabs {
            justify-content: flex-start;
            padding: 4px;
        }

        .nav-tabs li {
            margin: 2px 5px;
        }

        .nav-tabs .nav-link {
            font-size: 0.85rem;
            padding: 8px 12px;
            white-space: nowrap;
        }

        .nav-tabs .nav-link i {
            margin-right: 4px;
        }
    }

    /* Optimized for 400-500px phones */
    @media (max-width: 500px) and (min-width: 400px) {
        .nav-tabs {
            justify-content: flex-start;
            padding: 3px 0;
            margin: 0 4px;
            border-radius: 8px;
        }

        .nav-tabs li {
            margin: 2px 3px;
            flex-shrink: 0;
        }

        .nav-tabs .nav-link {
            font-size: 0.8rem;
            padding: 7px 10px;
            white-space: nowrap;
            margin: 0;
        }

        .nav-tabs .nav-link i {
            margin-right: 3px;
            font-size: 0.75rem;
        }

        .tab-content {
            padding-top: 15px;
            margin: 0;
            border-radius: 0;
        }
    }

    @media (max-width: 480px) {
        .nav-tabs li {
            margin: 2px 2px;
        }

        .nav-tabs .nav-link {
            font-size: 0.8rem;
            padding: 6px 8px;
        }
    }


    .tab-pane.show {
        opacity: 1;
    }

    /* Loading states */
    .loading-shimmer {
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }
        100% {
            background-position: 200% 0;
        }
    }
</style>

@php
    $showBdTab = \App\Models\Bd::query()->exists();
@endphp
<div class="dashboard-container" @if(app()->getLocale() == 'ar') dir="rtl" @endif>
    <div class="tabs-container fade-in-up" style="--stagger: 1">
        <ul class="nav nav-tabs nav-tabs-glass" id="statsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview"
                        type="button" role="tab" aria-controls="overview" aria-selected="true">
                    <i class="fas fa-chart-line"></i> {{ __('Overview') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button"
                        role="tab" aria-controls="users" aria-selected="false">
                    <i class="fas fa-users"></i> {{ __('Users') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#rooms" type="button"
                        role="tab" aria-controls="rooms" aria-selected="false">
                    <i class="fas fa-home"></i> {{ __('Rooms') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="agencies-tab" data-bs-toggle="tab" data-bs-target="#agencies" type="button"
                        role="tab" aria-controls="agencies" aria-selected="false">
                    <i class="fas fa-building"></i> {{ __('Agencies') }}
                </button>
            </li>
            @if($showBdTab)
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bd-tab" data-bs-toggle="tab" data-bs-target="#bd" type="button" role="tab"
                        aria-controls="bd" aria-selected="false">
                    <i class="fas fa-briefcase"></i> {{ __('BD') }}
                </button>
            </li>
            @endif
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="game-tab" data-bs-toggle="tab" data-bs-target="#game" type="button"
                        role="tab" aria-controls="game" aria-selected="false">
                    <i class="fas fa-gamepad"></i> {{ __('Game') }}
                </button>
            </li>
        </ul>

        <div class="tab-content" id="statsTabContent" style="min-height: 1450px;    ">

            <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                <div class="col-md-12">@include('admin.dashboard.widgets.overview_tab')</div>
            </div>

            <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                <div class="col-md-12 mb-4">@include('admin.dashboard.stats')</div>
                <div class="stats-grid">
                    <div class="widget-card">@include('admin.dashboard.widgets.users_chart')</div>
                    <div class="widget-card">@include('admin.dashboard.widgets.top_users_visits_chart')</div>
                    <div class="widget-card">@include('admin.dashboard.widgets.signups_weekly_chart')</div>
                    <div class="widget-card">@include('admin.dashboard.widgets.peak_hours_card')</div>
                    <div class="widget-card">@include('admin.dashboard.widgets.top_followers_table')</div>
                    <div class="widget-card">@include('admin.dashboard.widgets.users_online_chart')</div>
                </div>
            </div>

            <div class="tab-pane fade" id="rooms" role="tabpanel" aria-labelledby="rooms-tab">
                <div class="col-md-12 mb-4">@include('admin.dashboard.widgets.room_tab')</div>
                <div class="row">
                    <div class="col-lg-6 col-12 mb-3"><div class="widget-card">@include('admin.dashboard.widgets.rooms_distribution_chart')</div></div>
                    <div class="col-lg-6 col-12 mb-3"><div class="widget-card">@include('admin.dashboard.widgets.rooms_activity_chart')</div></div>
                    <div class="col-lg-6 col-12 mb-3"><div class="widget-card">@include('admin.dashboard.widgets.top_gifted_rooms_chart')</div></div>
                    <div class="col-lg-6 col-12 mb-3"><div class="widget-card">@include('admin.dashboard.widgets.avg_session_duration_chart')</div></div>
                </div>
            </div>

            <div class="tab-pane fade" id="agencies" role="tabpanel" aria-labelledby="agencies-tab">
                <div class="col-md-12 mb-4">@include('admin.dashboard.widgets.agency_tab')</div>
                <div class="stats-masonry">
                    <div class="widget-card-premium">@include('admin.dashboard.widgets.agencies_targets_chart')</div>
                    <div class="widget-card-premium">@include('admin.dashboard.widgets.top_senders_chart')</div>
                    <div class="widget-card-premium">@include('admin.dashboard.widgets.top_receivers_chart')</div>
                    <div class="widget-card-premium">@include('admin.dashboard.widgets.agencies_compare_chart')</div>
                </div>
            </div>

            @if($showBdTab)
            <div class="tab-pane fade" id="bd" role="tabpanel" aria-labelledby="bd-tab">
                <div class="col-md-12">@include('admin.dashboard.widgets.bd_tab')</div>
            </div>
            @endif

            <div class="tab-pane fade" id="game" role="tabpanel" aria-labelledby="game-tab">
                <div class="col-md-12">@include('admin.dashboard.widgets.game_tab')</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
{{-- jQuery is already loaded globally by Admin::jQuery() in the layout. Loading a
     second copy here overwrites window.jQuery, which breaks $.fn.modal (registered
     by Bootstrap 3 on the original instance).  PJAX's pjax:start handler relies on
     $.fn.modal to hide open modals before navigation; when it is unavailable the
     modal backdrop survives and blocks every click on the page.  — removed 2026-08-21 --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const charts = document.querySelectorAll('canvas');

        const io = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const url = entry.target.dataset.url;
                    if (url) fetch(url)
                        .then(r => r.json())
                        .then(data => {
                            const ctx = entry.target.getContext('2d');
                            new Chart(ctx, {type: 'bar', data: {labels: data.labels, datasets: [{data: data.data}]}});
                        });
                    obs.unobserve(entry.target);
                }
            });
        });
        charts.forEach(c => io.observe(c));

        // Tab persistence functionality
        const tabs = document.querySelectorAll('#statsTabs .nav-link');
        const tabContent = document.getElementById('statsTabContent');

        // Function to activate a tab
        function activateTab(tabId) {
            // Remove active class from all tabs
            tabs.forEach(tab => {
                tab.classList.remove('active');
                tab.setAttribute('aria-selected', 'false');
            });

            // Hide all tab panes
            const panes = tabContent.querySelectorAll('.tab-pane');
            panes.forEach(pane => {
                pane.classList.remove('show', 'active');
            });

            // Activate the selected tab
            const activeTab = document.querySelector(`[data-bs-target="${tabId}"]`);
            if (activeTab) {
                activeTab.classList.add('active');
                activeTab.setAttribute('aria-selected', 'true');
            }

            // Show the selected tab pane
            const activePane = document.querySelector(tabId);
            if (activePane) {
                activePane.classList.add('show', 'active');
            }
        }

        // Load tab from URL hash first, then fallback to localStorage
        const validTabs = ['#overview', '#users', '#rooms', '#agencies', @if($showBdTab) '#bd', @endif '#game'];
        const urlHash = window.location.hash;
        let initialTab = null;

        if (urlHash && validTabs.includes(urlHash)) {
            initialTab = urlHash;
        } else {
            const savedTab = localStorage.getItem('activeDashboardTab');
            if (savedTab && validTabs.includes(savedTab)) {
                initialTab = savedTab;
            }
        }

        if (initialTab) {
            activateTab(initialTab);
            // Update URL hash without scrolling
            history.replaceState(null, null, initialTab);
            // Trigger custom event for chart loading
            $(document).trigger('tabActivated', [initialTab]);
        }

        // Save active tab and update URL when clicked
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.getAttribute('data-bs-target');
                localStorage.setItem('activeDashboardTab', target);
                // Update URL hash so the link is shareable
                history.pushState(null, null, target);
            });
        });

        // Handle browser back/forward navigation between tabs
        window.addEventListener('popstate', function() {
            const hash = window.location.hash;
            if (hash && validTabs.includes(hash)) {
                activateTab(hash);
                initializeTabWidgets(hash);
            }
        });

        // Function to initialize all widgets for a specific tab
        function initializeTabWidgets(tabId) {
            // Use setTimeout to ensure DOM is ready and scripts are loaded
            setTimeout(() => {
                if (tabId === '#overview') {
                    // Initialize overview tab widgets
                    if (typeof initOverviewTab === 'function') {
                        initOverviewTab();
                    }
                    if (typeof loadWalletLogs === 'function') {
                        loadWalletLogs();
                    }
                } else if (tabId === '#users') {
                    // Initialize users tab widgets - only if not already loaded
                    if (typeof loadTopUsers === 'function' && !window.topUsersLoaded) {
                        loadTopUsers();
                    }
                    if (typeof loadWeeklySignups === 'function' && !window.weeklySignupsLoaded) {
                        loadWeeklySignups();
                    }
                    if (typeof loadPeakHours === 'function') {
                        loadPeakHours();
                    }
                    if (typeof loadTopFollowers === 'function' && !window.topFollowersLoaded) {
                        loadTopFollowers();
                    }
                    // Initialize users online chart
                    if (document.getElementById('usersOnlineChart')) {
                        // Trigger the existing DOMContentLoaded logic for users online chart
                        const event = new Event('DOMContentLoaded');
                        document.dispatchEvent(event);
                    }
                } else if (tabId === '#rooms') {
                    // Initialize rooms tab widgets
                    if (typeof loadRoomsDistribution === 'function') {
                        loadRoomsDistribution();
                    }
                    if (typeof loadRoomsActivity === 'function') {
                        loadRoomsActivity();
                    }
                    if (typeof loadTopGiftedRooms === 'function') {
                        loadTopGiftedRooms();
                    }
                    if (typeof loadAvgSessionDuration === 'function') {
                        loadAvgSessionDuration();
                    }
                    if (typeof updateRoomStats === 'function') {
                        updateRoomStats();
                    }
                } else if (tabId === '#agencies') {
                    // Initialize agencies tab widgets
                    if (typeof loadAgenciesTargets === 'function') {
                        loadAgenciesTargets();
                    }
                    if (typeof loadTopSenders === 'function') {
                        loadTopSenders();
                    }
                    if (typeof loadTopReceivers === 'function') {
                        loadTopReceivers();
                    }
                    if (typeof loadAgenciesCompare === 'function') {
                        loadAgenciesCompare();
                    }
                    if (typeof updateAgencyStats === 'function') {
                        updateAgencyStats();
                    }
                } else if (tabId === '#bd') {
                    // Initialize BD tab widgets
                    if (typeof updateBdStats === 'function') {
                        updateBdStats();
                    }
                } else if (tabId === '#game') {
                    // Initialize game tab widgets
                    if (typeof updateGameStats === 'function') {
                        updateGameStats();
                    }
                }
            }, 200); // Increased delay to ensure scripts are loaded
        }

        // Initialize widgets when tab is shown
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function() {
                const target = this.getAttribute('data-bs-target');
                initializeTabWidgets(target);
            });
        });

        // Initialize active tab on page load
        const activeTab = document.querySelector('#statsTabs .nav-link.active');
        if (activeTab) {
            const target = activeTab.getAttribute('data-bs-target');
            setTimeout(() => initializeTabWidgets(target), 500);
        }
    });
</script>
