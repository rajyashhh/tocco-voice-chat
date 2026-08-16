<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    body {
        background: var(--gradient-primary);
        min-height: 100vh;
    }

    .page-header {
        padding: 40px 0;
    }

    .page-title {
        font-size: 32px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
    }

    .breadcrumb-custom {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .breadcrumb-custom span {
        font-weight: 600;
    }

    /* Stats Overview */
    .stats-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: #ffffff;
        backdrop-filter: blur(10px);
        border: 1px solid #eaeaea;
        border-radius: 16px;
        padding: 25px 10px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        gap: 8px;
    }

    .dark-mode .stat-card {
        background: var(--dark-secondry-color);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        background: rgba(255, 255, 255, 0.15);
    }

    .stat-label {
        font-size: 14px;
        opacity: 0.8;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
    }

    .stat-change {
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 5px;
        color: #4ade80 !important;
        margin-top: 5px;
    }

    /* Features Grid */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 25px;
    }

    .feature-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        transition: all 0.4s ease;
        position: relative;
    }

    .dark-mode .feature-card {
        background: var(--dark-secondry-color);
    }

    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .feature-header {
        padding: 30px;
        background: var(--gradient-primary);
        color: white;
        position: relative;
        overflow: hidden;
    }

    .feature-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: pulse 3s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.1);
            opacity: 0.8;
        }
    }

    .feature-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        position: relative;
        z-index: 1;
    }

    .feature-name {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 15px;
        position: relative;
        z-index: 1;
    }

    .feature-status {
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 8px;
        position: relative;
        z-index: 1;
    }

    .feature-label {
        font-size: 13px;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }

    .feature-body {
        padding: 25px 30px;
    }

    .feature-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .info-label {
        font-size: 12px;
        font-weight: 500;
    }

    .info-value {
        font-size: 16px;
        font-weight: 700;
    }

    .feature-actions {
        display: flex;
        gap: 12px;
    }

    .btn-feature {
        flex: 1;
        padding: 14px 24px;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: var(--gradient-primary);
        color: var(--text-secondary-color);
    }

    .btn-feature:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--secondary-color);
        text-decoration: none;
    }

    .dark-mode .btn-feature:hover {
        box-shadow: 0 6px 20px var(--dark-secondry-color);
    }

    .feature-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        z-index: 2;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .rtl .feature-badge {
        left: 15px;
        right: auto;
    }

    .feature-update-time {
        position: absolute;
        bottom: 15px;
        right: 15px;
        font-size: 13px;
        z-index: 1;
    }

    /* Modal Styling */
    .modal-backdrop {
        z-index: 1040 !important;
    }

    .modal {
        z-index: 1050 !important;
    }

    .modal-content {
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }

    .modal-header {
        background: var(--gradient-primary);
        border: none;
        padding: 25px 30px;
    }

    .modal-title {
        font-weight: 700;
        font-size: 20px;
    }

    .modal-body {
        padding: 30px;
    }

    .modal-footer {
        border: none;
        padding: 20px 30px;
    }

    .btn-success-custom {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-success-custom:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(17, 153, 142, 0.4);
    }

    .btn-secondary-custom {
        background: #e2e8f0;
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-secondary-custom:hover {
        background: #cbd5e1;
    }

    /* Toast Notifications */
    #toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 99999;
    }

    .alert {
        border-radius: 12px;
        padding: 15px 20px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        animation: slideIn 0.3s ease;
        margin-bottom: 10px;
    }

    .dark-mode #to_wallet_id option {
        background: var(--dark-secondry-color) !important;
    }

    .dark-mode #to_wallet_id option:hover,
    .dark-mode #to_wallet_id option:focus,
    .dark-mode #to_wallet_id option:checked {
        background: var(--primary-color) !important;
        color: #ffffff !important;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .features-grid {
            grid-template-columns: 1fr;
        }

        .stats-overview {
            grid-template-columns: 1fr;
        }
    }

    /* Toggle Switch Styling */
    .feature-toggle-container {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }

    .toggle-label {
        margin-left: 10px;
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

    .feature-description-container {
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .external-content {
        min-height: 200px;
    }

    .loading {
        color: #888;
        font-style: italic;
    }
</style>

<body>
<div class="container-fluid" style="padding: 0;">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="feature-name">
                        {{ __('Agency Feature') }}
                    </div>
                    <div class="feature-status">
                        {{ $hostAgencyStatus ? __('Enabled') : __('Disabled') }}
                    </div>
                    <div class="feature-label">{{ __('Agency') }}</div>
                </div>
                <div class="feature-body">
                    <form id="agencyFeatureForm" class="new-form" action="{{ route('admin.app.settings.update') }}"
                          method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="redirect" value="{{ route('admin.app-feature.index') }}">
                        @php
                            $errorMessage = $errors ? $errors->first('msg') : null;
                        @endphp
                        @if ($errorMessage)
                            <div class="alert alert-danger text-center" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif

                        <div class="feature-toggle-container">
                            <span class="toggle-label">{{ __('Enable Agency Feature') }}</span>
                            <label class="switch">
                                <input type="checkbox" id="agency_toggle" {{ $hostAgencyStatus ? 'checked' : '' }}
                                onchange="document.getElementById('host_agency_value').value = this.checked ? '1' : '0';
                                    document.getElementById('agencyFeatureForm').submit();">
                                <span class="slider round"></span>
                            </label>
                            <input type="hidden" name="host_agency" id="host_agency_value"
                                   value="{{ $hostAgencyStatus ? '1' : '0' }}">
                        </div>

                        <div class="feature-description-container">
                            <h4>{{ __('Feature Description') }}</h4>
                            <div id="feature-description-content" class="external-content">
                                <div class="loading">{{ __('Loading feature description...') }}</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon">
                        <i class="fas fa-film"></i>
                    </div>
                    <div class="feature-name">
                        {{ __('Reel Settings') }}
                    </div>
                    <div class="feature-status">
                        {{ $reelSettings ? __('Enabled') : __('Disabled') }}
                    </div>
                    <div class="feature-label">{{ __('Reel') }}</div>
                </div>
                <div class="feature-body">
                    <form id="reelFeatureForm" class="new-form" action="{{ route('admin.app.settings.update') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @php
                            $errorMessage = $errors ? $errors->first('msg') : null;
                        @endphp
                        @if ($errorMessage)
                            <div class="alert alert-danger text-center" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif

                        <div class="feature-toggle-container">
                            <span class="toggle-label">{{ __('Enable Reel Feature') }}</span>
                            <label class="switch">
                                <input type="checkbox" id="reel_toggle" {{ $reelSettings ? 'checked' : '' }}
                                onchange="document.getElementById('host_reel_value').value = this.checked ? '1' : '0';
                                    document.getElementById('reelFeatureForm').submit();">
                                <span class="slider round"></span>
                            </label>
                            <input type="hidden" name="reel_status" id="host_reel_value"
                                   value="{{ $reelSettings ? '1' : '0' }}">
                        </div>

                        <div class="feature-description-container">
                            <h4>{{ __('Feature Description') }}</h4>
                            <div id="feature-description-content" class="external-content">
                                <div class="loading">{{ __('Loading feature description...') }}</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon">
                        <i class="fab fa-youtube"></i>
                    </div>
                    <div class="feature-name">
                        {{ __('YouTube Settings') }}
                    </div>
                    <div class="feature-status">
                        {{ $youtubeSettings ? __('Enabled') : __('Disabled') }}
                    </div>
                    <div class="feature-label">{{ __('YouTube') }}</div>
                </div>
                <div class="feature-body">
                    <form id="YouTubeFeatureForm" class="new-form" action="{{ route('admin.app.settings.update') }}"
                          method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @php
                            $errorMessage = $errors ? $errors->first('msg') : null;
                        @endphp
                        @if ($errorMessage)
                            <div class="alert alert-danger text-center" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif

                        <div class="feature-toggle-container">
                            <span class="toggle-label">{{ __('Enable youtube Feature') }}</span>
                            <label class="switch">
                                <input type="checkbox" id="youtube_toggle" {{ $youtubeSettings ? 'checked' : '' }}
                                onchange="document.getElementById('host_youtube_value').value = this.checked ? '1' : '0';
                                    document.getElementById('YouTubeFeatureForm').submit();">
                                <span class="slider round"></span>
                            </label>
                            <input type="hidden" name="youtube_status" id="host_youtube_value"
                                   value="{{ $youtubeSettings ? '1' : '0' }}">
                        </div>

                        <div class="feature-description-container">
                            <h4>{{ __('Feature Description') }}</h4>
                            <div id="feature-description-content" class="external-content">
                                <div class="loading">{{ __('Loading feature description...') }}</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon">
                        <i class="fas fa-broadcast-tower"></i>
                    </div>
                    <div class="feature-name">
                        {{ __('Live Settings') }}
                    </div>
                    <div class="feature-status">
                        {{ $liveSettings ? __('Enabled') : __('Disabled') }}
                    </div>
                    <div class="feature-label">{{ __('Live') }}</div>
                </div>
                <div class="feature-body">
                    <form id="liveFeatureForm" class="new-form" action="{{ route('admin.app.settings.update') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @php
                            $errorMessage = $errors ? $errors->first('msg') : null;
                        @endphp
                        @if ($errorMessage)
                            <div class="alert alert-danger text-center" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif

                        <div class="feature-toggle-container">
                            <span class="toggle-label">{{ __('Enable live Feature') }}</span>
                            <label class="switch">
                                <input type="checkbox" id="live_toggle" {{ $liveSettings ? 'checked' : '' }}
                                onchange="document.getElementById('host_live_value').value = this.checked ? '1' : '0';
                                    document.getElementById('liveFeatureForm').submit();">
                                <span class="slider round"></span>
                            </label>
                            <input type="hidden" name="live_status" id="host_live_value"
                                   value="{{ $liveSettings ? '1' : '0' }}">
                        </div>

                        <div class="feature-description-container">
                            <h4>{{ __('Feature Description') }}</h4>
                            <div id="feature-description-content" class="external-content">
                                <div class="loading">{{ __('Loading feature description...') }}</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="feature-name">
                        {{ __('room cup setting') }}
                    </div>
                    <div class="feature-status">
                        {{ $roomCupSetting ? __('Enabled') : __('Disabled') }}
                    </div>
                    <div class="feature-label">{{ __('Room Cup') }}</div>
                </div>
                <div class="feature-body">
                    <form id="roomCupForm" class="new-form" action="{{ route('admin.app.settings.update') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @php
                            $errorMessage = $errors ? $errors->first('msg') : null;
                        @endphp
                        @if ($errorMessage)
                            <div class="alert alert-danger text-center" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif

                        <div class="feature-toggle-container">
                            <span class="toggle-label">{{ __('Enable room cup Feature') }}</span>
                            <label class="switch">
                                <input type="checkbox" id="room_cup_toggle" {{ $roomCupSetting ? 'checked' : '' }}
                                onchange="document.getElementById('room_cup_value').value = this.checked ? '1' : '0';
                                    document.getElementById('roomCupForm').submit();">
                                <span class="slider round"></span>
                            </label>
                            <input type="hidden" name="room_cup_setting" id="room_cup_value"
                                   value="{{ $roomCupSetting ? '1' : '0' }}">
                        </div>

                        <div class="feature-description-container">
                            <h4>{{ __('Feature Description') }}</h4>
                            <div id="feature-description-content" class="external-content">
                                <div class="loading">{{ __('Loading feature description...') }}</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div class="feature-name">
                        {{ __('Moment Status') }}
                    </div>
                    <div class="feature-status">
                        {{ $momentStatus ? __('Enabled') : __('Disabled') }}
                    </div>
                    <div class="feature-label">{{ __('Moment') }}</div>
                </div>
                <div class="feature-body">
                    <form id="momentStatusForm" class="new-form" action="{{ route('admin.app.settings.update') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @php
                            $errorMessage = $errors ? $errors->first('msg') : null;
                        @endphp
                        @if ($errorMessage)
                            <div class="alert alert-danger text-center" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif

                        <div class="feature-toggle-container">
                            <span class="toggle-label">{{ __('Enable moment status Feature') }}</span>
                            <label class="switch">
                                <input type="checkbox" id="moment_status_toggle" {{ $momentStatus ? 'checked' : '' }}
                                onchange="document.getElementById('moment_status_value').value = this.checked ? '1' : '0';
                                    document.getElementById('momentStatusForm').submit();">
                                <span class="slider round"></span>
                            </label>
                            <input type="hidden" name="moment_status" id="moment_status_value"
                                   value="{{ $momentStatus ? '1' : '0' }}">
                        </div>

                        <div class="feature-description-container">
                            <h4>{{ __('Feature Description') }}</h4>
                            <div id="feature-description-content" class="external-content">
                                <div class="loading">{{ __('Loading feature description...') }}</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="feature-name">
                        {{ __('host level') }}
                    </div>
                    <div class="feature-status">
                        {{ $hostLevel ? __('Enabled') : __('Disabled') }}
                    </div>
                    <div class="feature-label">{{ __('Host Level') }}</div>
                </div>
                <div class="feature-body">
                    <form id="hostLevelForm" class="new-form" action="{{ route('admin.app.settings.update') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @php
                            $errorMessage = $errors ? $errors->first('msg') : null;
                        @endphp
                        @if ($errorMessage)
                            <div class="alert alert-danger text-center" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif

                        <div class="feature-toggle-container">
                            <span class="toggle-label">{{ __('Enable host level Feature') }}</span>
                            <label class="switch">
                                <input type="checkbox" id="host_level_toggle" {{ $hostLevel ? 'checked' : '' }}
                                onchange="document.getElementById('host_level_value').value = this.checked ? '1' : '0';
                                    document.getElementById('hostLevelForm').submit();">
                                <span class="slider round"></span>
                            </label>
                            <input type="hidden" name="host_level_enabled" id="host_level_value"
                                   value="{{ $hostLevel ? '1' : '0' }}">
                        </div>

                        <div class="feature-description-container">
                            <h4>{{ __('Feature Description') }}</h4>
                            <div id="feature-description-content" class="external-content">
                                <div class="loading">{{ __('Loading feature description...') }}</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon">
                        <i class="fas fa-share"></i>
                    </div>
                    <div class="feature-name">
                        {{ __('share room') }}
                    </div>
                    <div class="feature-status">
                        {{ $shareRoom ? __('Enabled') : __('Disabled') }}
                    </div>
                    <div class="feature-label">{{ __('share room') }}</div>
                </div>
                <div class="feature-body">
                    <form id="shareRoomForm" class="new-form" action="{{ route('admin.app.settings.update') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @php
                            $errorMessage = $errors ? $errors->first('msg') : null;
                        @endphp
                        @if ($errorMessage)
                            <div class="alert alert-danger text-center" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif

                        <div class="feature-toggle-container">
                            <span class="toggle-label">{{ __('Enable share room Feature') }}</span>
                            <label class="switch">
                                <input type="checkbox" id="share_room_toggle" {{ $shareRoom ? 'checked' : '' }}
                                onchange="document.getElementById('share_room_value').value = this.checked ? '1' : '0';
                                    document.getElementById('shareRoomForm').submit();">
                                <span class="slider round"></span>
                            </label>
                            <input type="hidden" name="share_room_with_friends" id="share_room_value"
                                   value="{{ $shareRoom ? '1' : '0' }}">
                        </div>

                        <div class="feature-description-container">
                            <h4>{{ __('Feature Description') }}</h4>
                            <div id="feature-description-content" class="external-content">
                                <div class="loading">{{ __('Loading feature description...') }}</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>


             <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon">
                        <i class="fas fa-share"></i>
                    </div>
                    <div class="feature-name">
                        {{ __('Room Boom') }}
                    </div>
                    <div class="feature-status">
                        {{ $enableRoomBoom ? __('Enabled') : __('Disabled') }}
                    </div>
                    <div class="feature-label">{{ __('Room Boom') }}</div>
                </div>
                <div class="feature-body">
                    <form id="enableRoomBoomForm" class="new-form" action="{{ route('admin.app.settings.update') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @php
                            $errorMessage = $errors ? $errors->first('msg') : null;
                        @endphp
                        @if ($errorMessage)
                            <div class="alert alert-danger text-center" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif

                        <div class="feature-toggle-container">
                            <span class="toggle-label">{{ __('enable room boom feature') }}</span>
                            <label class="switch">
                                <input type="checkbox" id="enable_room_boom_toggle" {{ $enableRoomBoom ? 'checked' : '' }}
                                onchange="document.getElementById('enable_room_boom_value').value = this.checked ? '1' : '0';
                                    document.getElementById('enableRoomBoomForm').submit();">
                                <span class="slider round"></span>
                            </label>
                            <input type="hidden" name="enable_room_boom" id="enable_room_boom_value"
                                   value="{{ $enableRoomBoom ? '1' : '0' }}">
                        </div>

                        <div class="feature-description-container">
                            <h4>{{ __('Feature Description') }}</h4>
                            <div id="feature-description-content" class="external-content">
                                <div class="loading">{{ __('Loading feature description...') }}</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

             <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon">
                        <i class="fas fa-share"></i>
                    </div>
                    <div class="feature-name">
                        {{ __('Audio Room') }}
                    </div>
                    <div class="feature-status">
                        {{ $audioRoom ? __('Enabled') : __('Disabled') }}
                    </div>
                    <div class="feature-label">{{ __('Audio Room') }}</div>
                </div>
                <div class="feature-body">
                    <form id="enableAudioRoomForm" class="new-form" action="{{ route('admin.app.settings.update') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @php
                            $errorMessage = $errors ? $errors->first('msg') : null;
                        @endphp
                        @if ($errorMessage)
                            <div class="alert alert-danger text-center" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif

                        <div class="feature-toggle-container">
                            <span class="toggle-label">{{ __('enable audio room feature') }}</span>
                            <label class="switch">
                                <input type="checkbox" id="enable_audio_room_toggle" {{ $audioRoom ? 'checked' : '' }}
                                onchange="document.getElementById('enable_audio_room_value').value = this.checked ? '1' : '0';
                                    document.getElementById('enableAudioRoomForm').submit();">
                                <span class="slider round"></span>
                            </label>
                            <input type="hidden" name="audio_room" id="enable_audio_room_value"
                                   value="{{ $audioRoom ? '1' : '0' }}">
                        </div>

                        <div class="feature-description-container">
                            <h4>{{ __('Feature Description') }}</h4>
                            <div id="feature-description-content" class="external-content">
                                <div class="loading">{{ __('Loading feature description...') }}</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="imageModal" class="modal" onclick="closeFullScreen()">
    <span class="close">&times;</span>
    <img class="modal-content" id="fullImage">
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        function getQueryParam(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

        const activeTab = getQueryParam("firsttab") || "AppFeature";
        showSection(activeTab);
    });

    function showSection(sectionId) {
        document.querySelectorAll('.settings-section').forEach(section => {
            section.classList.remove('active');
        });

        const activeSection = document.getElementById(sectionId);
        if (activeSection) {
            activeSection.classList.add('active');
        }

        document.querySelectorAll('.settings-menu button').forEach(button => {
            button.style.backgroundColor = '';
            button.style.color = '';
        });

        const activeButton = document.querySelector(`.settings-menu button[onclick="showSection('${sectionId}')"]`);
        if (activeButton) {
            activeButton.style.setProperty('background-color', 'var(--primary-color)', 'important');
            activeButton.style.setProperty('color', 'var(--text-secondary-color)', 'important');
        }

        const url = new URL(window.location);
        url.searchParams.set("firsttab", sectionId);
        window.history.pushState({}, "", url);
    }

    function openFullScreen(imgElement) {
        var modal = document.getElementById("imageModal");
        var modalImg = document.getElementById("fullImage");
        modal.style.display = "block";
        modalImg.src = imgElement.src;
    }

    function closeFullScreen() {
        document.getElementById("imageModal").style.display = "none";
    }

    document.addEventListener('DOMContentLoaded', function () {
        try {
            var agencyHiddenValue = document.getElementById('host_agency_value')?.value;
            if (document.getElementById('agency_toggle')) {
                document.getElementById('agency_toggle').checked = (agencyHiddenValue === '1');
            }

            var enableRoomBoomHiddenValue = document.getElementById('enable_room_boom_value')?.value;
            if (document.getElementById('enable_room_boom_toggle')) {
                document.getElementById('enable_room_boom_toggle').checked = (enableRoomBoomHiddenValue === '1');
            }

            var reelHiddenValue = document.getElementById('host_reel_value')?.value;
            if (document.getElementById('reel_toggle')) {
                document.getElementById('reel_toggle').checked = (reelHiddenValue === '1');
            }

            var youtubeHiddenValue = document.getElementById('host_youtube_value')?.value;
            if (document.getElementById('youtube_toggle')) {
                document.getElementById('youtube_toggle').checked = (youtubeHiddenValue === '1');
            }

            var liveHiddenValue = document.getElementById('host_live_value')?.value;
            if (document.getElementById('live_toggle')) {
                document.getElementById('live_toggle').checked = (liveHiddenValue === '1');
            }

            var roomCupValue = document.getElementById('room_cup_value')?.value;
            if (document.getElementById('room_cup_toggle')) {
                document.getElementById('room_cup_toggle').checked = (roomCupValue === '1');
            }

            var momentStatusValue = document.getElementById('moment_status_value')?.value;
            if (document.getElementById('moment_status_toggle')) {
                document.getElementById('moment_status_toggle').checked = (momentStatusValue === '1');
            }

            var hostLevelValue = document.getElementById('host_level_value')?.value;
            if (document.getElementById('host_level_toggle')) {
                document.getElementById('host_level_toggle').checked = (hostLevelValue === '1');
            }

            var shareRoomValue = document.getElementById('share_room_value')?.value;
            if (document.getElementById('share_room_toggle')) {
                document.getElementById('share_room_toggle').checked = (shareRoomValue === '1');
            }
        } catch (error) {
            console.error('Error initializing toggles:', error);
        }
    });
</script>
</body>
