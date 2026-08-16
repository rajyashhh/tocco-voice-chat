<div id="landPageSettings" class="settings-section">
    <div class="section-header-bar">
        <div class="section-header-icon"><i class="fas fa-globe"></i></div>
        <div class="section-header-text">
            <h3>{{ __('Landing Page Settings') }}</h3>
            <p>{{ __('Configure your landing page content, statistics and social media links') }}</p>
        </div>
    </div>

    <div class="settings-container">
        <div class="tabs-topbar" role="tablist" aria-orientation="horizontal">
            <button class="tab-btn active" data-target="#general" type="button" role="tab"
                    aria-controls="general" aria-selected="true">
                <i class="fas fa-cog"></i> {{ __('General Settings') }}
            </button>
            <button class="tab-btn" data-target="#stats" type="button" role="tab" aria-controls="stats"
                    aria-selected="false">
                <i class="fas fa-chart-bar"></i> {{ __('Statistics Settings') }}
            </button>
            <button class="tab-btn" data-target="#social" type="button" role="tab" aria-controls="social"
                    aria-selected="false">
                <i class="fas fa-share-alt"></i> {{ __('Social Media Settings') }}
            </button>
            <button class="tab-btn" data-target="#appLinks" type="button" role="tab" aria-controls="appLinks"
                    aria-selected="false">
                <i class="fas fa-mobile-alt"></i> {{ __('App Links') }}
            </button>
        </div>

        <div class="tab-content">
            <form action="{{ route('admin.app.settings.update') }}" method="POST" enctype="multipart/form-data"
                  id="landingSettingsForm" class="settings-form lp-form-reset">
                @csrf
                <input type="hidden" name="current_tab" value="">
                <input type="hidden" name="inner_tab_type_hash" value="">

                {{-- General Settings --}}
                <div class="tab-pane show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <div class="lp-section-header">
                        <div class="lp-section-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div>
                            <h5>{{ __('General Settings') }}</h5>
                            <span>{{ __('Basic landing page configuration') }}</span>
                        </div>
                    </div>
                    <div class="lp-input-row">
                        <div class="lp-input-group">
                            <label><i class="fas fa-info-circle"></i> {{ __('About Us Link') }}</label>
                            <input type="url" name="about_us_link"
                                   value="{{ $settings['about_us_link'] ?? '' }}" class="form-control lp-input"
                                   placeholder="{{ __('https://example.com/about') }}">
                        </div>
                        <div class="lp-input-group">
                            <label><i class="fas fa-images"></i> {{ __('Gallery App Link') }}</label>
                            <input type="url" name="gallery_app_link"
                                   value="{{ $settings['gallery_app_link'] ?? '' }}" class="form-control lp-input"
                                   placeholder="{{ __('https://example.com/gallery') }}">
                        </div>
                    </div>
                </div>

                {{-- Statistics Settings --}}
                <div class="tab-pane" id="stats" role="tabpanel" aria-labelledby="stats-tab">
                    <div class="lp-section-header">
                        <div class="lp-section-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div>
                            <h5>{{ __('Statistics Settings') }}</h5>
                            <span>{{ __('Display numbers on your landing page') }}</span>
                        </div>
                    </div>
                    <div class="lp-stats-grid">
                        <div class="lp-stat-card">
                            <div class="lp-stat-icon" style="background: var(--accent-soft); color: var(--accent);">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="lp-input-group">
                                <label>{{ __('Number of Users') }}</label>
                                <input type="number" name="landing_users_count"
                                       value="{{ $settings['landing_users_count'] ?? '' }}" class="form-control lp-input"
                                       min="0" placeholder="0">
                            </div>
                        </div>
                        <div class="lp-stat-card">
                            <div class="lp-stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                                <i class="fas fa-globe-americas"></i>
                            </div>
                            <div class="lp-input-group">
                                <label>{{ __('Number of Countries') }}</label>
                                <input type="number" name="landing_countries_count"
                                       value="{{ $settings['landing_countries_count'] ?? '' }}" class="form-control lp-input"
                                       min="0" placeholder="0">
                            </div>
                        </div>
                        <div class="lp-stat-card">
                            <div class="lp-stat-icon" style="background: rgba(239,68,68,0.1); color: #ef4444;">
                                <i class="fas fa-broadcast-tower"></i>
                            </div>
                            <div class="lp-input-group">
                                <label>{{ __('Number of Live Streams') }}</label>
                                <input type="number" name="landing_live_count"
                                       value="{{ $settings['landing_live_count'] ?? '' }}" class="form-control lp-input"
                                       min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Social Media Settings --}}
                <div class="tab-pane" id="social" role="tabpanel" aria-labelledby="social-tab">
                    <div class="lp-section-header">
                        <div class="lp-section-icon" style="background: linear-gradient(135deg, #ec4899, #f472b6);">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <div>
                            <h5>{{ __('Social Media Settings') }}</h5>
                            <span>{{ __('Connect your social media accounts') }}</span>
                        </div>
                    </div>
                    <div class="lp-social-list">
                        <div class="lp-social-item">
                            <div class="lp-social-icon" style="background: #1877f2;">
                                <i class="fab fa-facebook-f"></i>
                            </div>
                            <div class="lp-input-group" style="flex:1;">
                                <label>{{ __('Facebook Link') }}</label>
                                <input type="url" name="facebook_link"
                                       value="{{ $settings['facebook_link'] ?? '' }}" class="form-control lp-input"
                                       placeholder="https://facebook.com/...">
                            </div>
                        </div>
                        <div class="lp-social-item">
                            <div class="lp-social-icon" style="background: #1da1f2;">
                                <i class="fab fa-twitter"></i>
                            </div>
                            <div class="lp-input-group" style="flex:1;">
                                <label>{{ __('Twitter Link') }}</label>
                                <input type="url" name="twitter_link"
                                       value="{{ $settings['twitter_link'] ?? '' }}" class="form-control lp-input"
                                       placeholder="https://twitter.com/...">
                            </div>
                        </div>
                        <div class="lp-social-item">
                            <div class="lp-social-icon" style="background: #25d366;">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div class="lp-input-group" style="flex:1;">
                                <label>{{ __('WhatsApp Link') }}</label>
                                <input type="url" name="whatsapp_link"
                                       value="{{ $settings['whatsapp_link'] ?? '' }}" class="form-control lp-input"
                                       placeholder="https://wa.me/...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- App Links (moved from the old standalone Mobile Links section) --}}
                <div class="tab-pane" id="appLinks" role="tabpanel" aria-labelledby="appLinks-tab">
                    <div class="lp-section-header">
                        <div class="lp-section-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div>
                            <h5>{{ __('App Links') }}</h5>
                            <span>{{ __('Configure download links for different platforms') }}</span>
                        </div>
                    </div>

                    <div class="mlinks-grid">

                        {{-- ── Android Card ── --}}
                        <div class="mlinks-card">
                            <div class="mlinks-card-header android-gradient">
                                <div class="mlinks-card-decoration"></div>
                                <div class="mlinks-card-icon-wrap">
                                    <i class="fab fa-android"></i>
                                </div>
                                <div class="mlinks-card-title">
                                    <h5>{{ __('Android') }}</h5>
                                    <span>Google Play Store</span>
                                </div>
                                <div class="mlinks-card-badge">
                                    <i class="fab fa-google-play"></i>
                                </div>
                            </div>
                            <div class="mlinks-card-body">
                                <label class="mlinks-label">
                                    <i class="fas fa-link"></i> {{ __('Store URL') }}
                                </label>
                                <div class="mlinks-input-wrap">
                                    <span class="mlinks-input-prefix">
                                        <i class="fas fa-globe"></i>
                                    </span>
                                    <input type="url" name="android_link"
                                           value="{{ $settings['android_link'] ?? '' }}"
                                           placeholder="{{ __('https://play.google.com/store/apps/...') }}"
                                           class="form-control mlinks-input">
                                    @if(!empty($settings['android_link']))
                                        <a href="{{ $settings['android_link'] }}" target="_blank" class="mlinks-input-action"
                                           title="{{ __('Open link') }}">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @endif
                                </div>
                                <div class="mlinks-status">
                                    @if(!empty($settings['android_link']))
                                        <span class="mlinks-status-dot active"></span>
                                        <span class="mlinks-status-text">{{ __('Link configured') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ── iOS Card ── --}}
                        <div class="mlinks-card">
                            <div class="mlinks-card-header ios-gradient">
                                <div class="mlinks-card-decoration"></div>
                                <div class="mlinks-card-icon-wrap">
                                    <i class="fab fa-apple"></i>
                                </div>
                                <div class="mlinks-card-title">
                                    <h5>{{ __('iOS') }}</h5>
                                    <span>App Store</span>
                                </div>
                                <div class="mlinks-card-badge">
                                    <i class="fab fa-app-store"></i>
                                </div>
                            </div>
                            <div class="mlinks-card-body">
                                <label class="mlinks-label">
                                    <i class="fas fa-link"></i> {{ __('Store URL') }}
                                </label>
                                <div class="mlinks-input-wrap">
                                    <span class="mlinks-input-prefix">
                                        <i class="fas fa-globe"></i>
                                    </span>
                                    <input type="url" name="ios_link"
                                           value="{{ $settings['ios_link'] ?? '' }}"
                                           placeholder="{{ __('https://apps.apple.com/app/...') }}"
                                           class="form-control mlinks-input">
                                    @if(!empty($settings['ios_link']))
                                        <a href="{{ $settings['ios_link'] }}" target="_blank" class="mlinks-input-action"
                                           title="{{ __('Open link') }}">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @endif
                                </div>
                                <div class="mlinks-status">
                                    @if(!empty($settings['ios_link']))
                                        <span class="mlinks-status-dot active"></span>
                                        <span class="mlinks-status-text">{{ __('Link configured') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ── Huawei Card ── --}}
                        <div class="mlinks-card">
                            <div class="mlinks-card-header huawei-gradient">
                                <div class="mlinks-card-decoration"></div>
                                <div class="mlinks-card-icon-wrap">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="mlinks-card-title">
                                    <h5>{{ __('Huawei') }}</h5>
                                    <span>AppGallery</span>
                                </div>
                                <div class="mlinks-card-badge">
                                    <i class="fas fa-store"></i>
                                </div>
                            </div>
                            <div class="mlinks-card-body">
                                <label class="mlinks-label">
                                    <i class="fas fa-link"></i> {{ __('Store URL') }}
                                </label>
                                <div class="mlinks-input-wrap">
                                    <span class="mlinks-input-prefix">
                                        <i class="fas fa-globe"></i>
                                    </span>
                                    <input type="url" name="huawei_link"
                                           value="{{ $settings['huawei_link'] ?? '' }}"
                                           placeholder="{{ __('https://appgallery.huawei.com/app/...') }}"
                                           class="form-control mlinks-input">
                                    @if(!empty($settings['huawei_link']))
                                        <a href="{{ $settings['huawei_link'] }}" target="_blank" class="mlinks-input-action"
                                           title="{{ __('Open link') }}">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @endif
                                </div>
                                <div class="mlinks-status">
                                    @if(!empty($settings['huawei_link']))
                                        <span class="mlinks-status-dot active"></span>
                                        <span class="mlinks-status-text">{{ __('Link configured') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="lp-footer">
                    <button type="submit" class="btn lp-btn-save">
                        <i class="fas fa-save"></i> {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* ── Horizontal Tabs (replaces the old vertical .tabs-sidebar — the third
       nested menu on mobile). Scoped to this section only; third_party keeps
       its own .tp-subtabs treatment. ── */
    #landPageSettings .settings-container {
        flex-direction: column;
    }
    .tabs-topbar {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding-bottom: 12px;
        margin-bottom: 6px;
        border-bottom: 1px solid #eee;
        overflow-x: auto;
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
    }
    .dark-mode .tabs-topbar {
        border-bottom-color: rgba(255,255,255,0.06);
    }
    .tabs-topbar .tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        flex-shrink: 0;
        border-radius: 999px;
        padding: 9px 18px;
        text-align: center;
        width: auto !important;
    }
    #landPageSettings .tab-content {
        width: 100%;
    }

    /* ── App Links cards (moved from the old standalone Mobile Links section) ── */
    .mlinks-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 22px;
    }
    .mlinks-card {
        border-radius: 18px;
        overflow: hidden;
        background: var(--white, #fff);
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark-mode .mlinks-card {
        background: var(--dark-secondry-color, #1e293b);
        border-color: rgba(255,255,255,0.06);
    }
    .mlinks-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 36px rgba(0,0,0,0.1);
    }
    .dark-mode .mlinks-card:hover {
        box-shadow: 0 12px 36px rgba(0,0,0,0.3);
    }
    .mlinks-card-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 20px 22px;
        position: relative;
        overflow: hidden;
    }
    .mlinks-card-decoration {
        position: absolute;
        top: -40%;
        right: -15%;
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
        pointer-events: none;
    }
    .mlinks-card-header::after {
        content: '';
        position: absolute;
        bottom: -25%;
        left: -8%;
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
        pointer-events: none;
    }
    .mlinks-card-icon-wrap {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #fff;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }
    .mlinks-card-title {
        flex: 1;
        position: relative;
        z-index: 1;
    }
    .mlinks-card-title h5 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #fff;
    }
    .mlinks-card-title span {
        font-size: 12px;
        color: rgba(255,255,255,0.7);
        font-weight: 500;
    }
    .mlinks-card-badge {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: rgba(255,255,255,0.8);
        position: relative;
        z-index: 1;
    }

    /* Gradient Themes */
    .android-gradient { background: linear-gradient(135deg, #34d399, #059669); }
    .ios-gradient      { background: linear-gradient(135deg, #64748b, #334155); }
    .huawei-gradient   { background: linear-gradient(135deg, #ef4444, #b91c1c); }

    .mlinks-card-body {
        padding: 22px;
    }
    .mlinks-label {
        display: flex !important;
        align-items: center;
        gap: 7px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary-color, #475569);
        margin-bottom: 10px !important;
    }
    .dark-mode .mlinks-label {
        color: #cbd5e1;
    }
    .mlinks-label i {
        font-size: 12px;
        color: #94a3b8;
    }
    .mlinks-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .mlinks-input-prefix {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 14px;
        pointer-events: none;
        z-index: 2;
    }
    .rtl .mlinks-input-prefix {
        left: auto;
        right: 14px;
    }
    .mlinks-input {
        background: #f8fafc !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: 12px 44px 12px 40px !important;
        font-size: 13px !important;
        transition: all 0.2s ease;
        width: 100% !important;
        direction: ltr;
    }
    .rtl .mlinks-input {
        padding: 12px 40px 12px 44px !important;
    }
    .dark-mode .mlinks-input {
        background: rgba(255,255,255,0.04) !important;
        border-color: rgba(255,255,255,0.1) !important;
        color: #f1f5f9 !important;
    }
    .mlinks-input:focus {
        background: #fff !important;
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 0 3px rgba(139,92,246,0.1) !important;
        outline: none !important;
    }
    .dark-mode .mlinks-input:focus {
        background: rgba(255,255,255,0.06) !important;
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 0 3px rgba(139,92,246,0.15) !important;
    }
    .mlinks-input-action {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(139,92,246,0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8b5cf6;
        font-size: 13px;
        text-decoration: none;
        transition: all 0.2s ease;
        z-index: 2;
    }
    .rtl .mlinks-input-action {
        right: auto;
        left: 10px;
    }
    .mlinks-input-action:hover {
        background: #8b5cf6;
        color: #fff;
        transform: translateY(-50%) scale(1.05);
    }
    .mlinks-status {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
        padding: 8px 12px;
        border-radius: 8px;
        background: #f8fafc;
    }
    .dark-mode .mlinks-status {
        background: rgba(255,255,255,0.03);
    }
    .mlinks-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .mlinks-status-dot.active {
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
    }
    .mlinks-status-text {
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
    }
    .dark-mode .mlinks-status-text {
        color: #94a3b8;
    }
    @media (max-width: 768px) {
        .mlinks-grid {
            grid-template-columns: 1fr;
        }
    }

    .lp-form-reset {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* ── Section Header ────────────────────────────────────────── */
    .lp-section-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark-mode .lp-section-header { border-bottom-color: rgba(255,255,255,0.06); }
    .lp-section-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .lp-section-header h5 {
        margin: 0 0 2px 0;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
    }
    .dark-mode .lp-section-header h5 { color: #f1f5f9; }
    .lp-section-header span {
        font-size: 13px;
        color: #94a3b8;
    }

    /* ── Input Group ───────────────────────────────────────────── */
    .lp-input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .lp-input-group label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
    }
    .dark-mode .lp-input-group label { color: #94a3b8; }
    .lp-input-group label i { font-size: 11px; opacity: 0.7; }
    .lp-input {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: 10px 14px !important;
        font-size: 13px !important;
        font-weight: 500;
        transition: all 0.3s ease;
        background: #f8fafc !important;
    }
    .dark-mode .lp-input {
        background: #0f172a !important;
        border-color: rgba(255,255,255,0.08) !important;
        color: #e2e8f0 !important;
    }
    .lp-input:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-soft) !important;
        outline: none;
    }
    .lp-input-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    /* ── Stats Grid ────────────────────────────────────────────── */
    .lp-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .lp-stat-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 14px;
        padding: 24px 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        text-align: center;
        transition: all 0.3s ease;
    }
    .dark-mode .lp-stat-card {
        background: #0f172a;
        border-color: rgba(255,255,255,0.06);
    }
    .lp-stat-card:hover {
        border-color: #cbd5e1;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .lp-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .lp-stat-card .lp-input-group { width: 100%; }
    .lp-stat-card .lp-input { text-align: center; }

    /* ── Social List ───────────────────────────────────────────── */
    .lp-social-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .lp-social-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        transition: all 0.3s ease;
    }
    .dark-mode .lp-social-item {
        background: #0f172a;
        border-color: rgba(255,255,255,0.06);
    }
    .lp-social-item:hover { border-color: #cbd5e1; }
    .lp-social-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 18px;
        flex-shrink: 0;
        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    }

    /* ── Footer ────────────────────────────────────────────────── */
    .lp-footer {
        display: flex;
        justify-content: flex-end;
        padding-top: 20px;
        margin-top: 10px;
        border-top: 1px solid #f1f5f9;
    }
    .dark-mode .lp-footer { border-top-color: rgba(255,255,255,0.06); }
    .lp-btn-save {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        padding: 10px 28px !important;
        background: linear-gradient(135deg, var(--accent), var(--accent-strong)) !important;
        color: var(--accent-contrast) !important;
        border: none !important;
        border-radius: 10px !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 3px 10px var(--accent-soft);
        width: auto !important;
    }
    .lp-btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px var(--accent-soft);
        background: linear-gradient(135deg, var(--accent-strong), var(--accent)) !important;
    }

    /* ── Responsive ────────────────────────────────────────────── */
    @media (max-width: 992px) {
        .lp-stats-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
        .lp-input-row { grid-template-columns: 1fr; }
        .lp-stats-grid { grid-template-columns: 1fr; }
        .lp-social-item { flex-direction: column; align-items: stretch; }
    }
</style>
