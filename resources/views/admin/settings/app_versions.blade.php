@php
    // These keys live in storage/app/settings.json (settings() helper), NOT the
    // settings table. Read them the same way the API (VersionController) does.
    $avPlatforms = [
        'android' => [
            'label'    => __('admin.android'),
            'sub'      => 'Google Play Store',
            'icon'     => 'fab fa-android',
            'gradient' => 'linear-gradient(135deg, #34d399, #059669)',
            'route'    => route('admin.android-setting'),
        ],
        'ios' => [
            'label'    => __('admin.ios'),
            'sub'      => 'App Store',
            'icon'     => 'fab fa-apple',
            'gradient' => 'linear-gradient(135deg, #64748b, #334155)',
            'route'    => route('admin.ios-setting'),
        ],
        'huawei' => [
            'label'    => __('admin.huawei'),
            'sub'      => 'AppGallery',
            'icon'     => 'fas fa-mobile-alt',
            'gradient' => 'linear-gradient(135deg, #ef4444, #b91c1c)',
            'route'    => route('admin.huawi-setting'),
        ],
    ];
@endphp

<div id="appVersionsSettings" class="settings-section">
    <div class="section-header-bar">
        <div class="section-header-icon" style="background: linear-gradient(135deg, #0ea5e9, #2563eb);">
            <i class="fas fa-sync-alt"></i>
        </div>
        <div class="section-header-text">
            <h3>{{ __('App Updates') }}</h3>
            <p>{{ __('Minimum version, current version and force update per platform') }}</p>
        </div>
    </div>

    <div class="av-grid">
        @foreach($avPlatforms as $os => $p)
            @php
                $minVersion      = settings()->get($os . '_min_version');
                $currentVersion  = settings()->get($os . '_current_version');
                $updateRequired  = settings()->get($os . '_update_required') == 1;
            @endphp
            <form action="{{ $p['route'] }}" method="POST" class="av-card no-background-form">
                @csrf
                <div class="av-card-header" style="background: {{ $p['gradient'] }};">
                    <div class="av-card-icon-wrap">
                        <i class="{{ $p['icon'] }}"></i>
                    </div>
                    <div class="av-card-title">
                        <h5>{{ $p['label'] }}</h5>
                        <span>{{ $p['sub'] }}</span>
                    </div>
                    @if($updateRequired)
                        <span class="av-force-badge on"><i class="fas fa-lock"></i> {{ __('admin.update_required') }}</span>
                    @endif
                </div>

                <div class="av-card-body">
                    <div class="av-input-group">
                        <label><i class="fas fa-arrow-down"></i> {{ __('admin.minimum_version') }}</label>
                        <input type="number" name="{{ $os }}_min_version" min="0"
                               value="{{ $minVersion }}"
                               class="form-control av-input" placeholder="0">
                        <small class="av-hint">{{ __('Build number (versionCode)') }}</small>
                    </div>

                    <div class="av-input-group">
                        <label><i class="fas fa-code-branch"></i> {{ __('admin.current_version') }}</label>
                        <input type="number" name="{{ $os }}_current_version" min="0"
                               value="{{ $currentVersion }}"
                               class="form-control av-input" placeholder="0">
                        <small class="av-hint">{{ __('Build number (versionCode)') }}</small>
                    </div>

                    <div class="av-toggle-row">
                        <div class="av-toggle-text">
                            <label for="av_{{ $os }}_update_required">{{ __('Force Update') }}</label>
                            <small>{{ __('Off: no user is ever forced to update, whatever their version. On: any user below the minimum version is forced to update.') }}</small>
                        </div>
                        {{-- Hidden 0 + checkbox 1: the controller stores the raw value,
                             isForce() only forces when it equals 1 AND version < min. --}}
                        <input type="hidden" name="{{ $os }}_update_required" value="0">
                        <label class="av-switch">
                            <input type="checkbox" id="av_{{ $os }}_update_required"
                                   name="{{ $os }}_update_required" value="1"
                                   {{ $updateRequired ? 'checked' : '' }}>
                            <span class="av-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="av-card-footer">
                    <button type="submit" class="btn av-save-btn">
                        <i class="fas fa-save"></i> {{ __('Save') }}
                    </button>
                </div>
            </form>
        @endforeach
    </div>
</div>

{{-- ═══════════════════════════════════════════════════
     APP VERSIONS — Scoped Styles
     ═══════════════════════════════════════════════════ --}}
<style>
    .av-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 22px;
    }

    .av-card {
        display: flex;
        flex-direction: column;
        border-radius: 18px;
        overflow: hidden;
        background: var(--white, #fff) !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark-mode .av-card {
        background: var(--dark-secondry-color, #1e293b) !important;
        border-color: rgba(255,255,255,0.06) !important;
    }
    .av-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 36px rgba(0,0,0,0.1) !important;
    }
    .dark-mode .av-card:hover {
        box-shadow: 0 12px 36px rgba(0,0,0,0.3) !important;
    }

    .av-card-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 20px 22px;
        position: relative;
        overflow: hidden;
    }
    .av-card-icon-wrap {
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
    }
    .av-card-title {
        flex: 1;
    }
    .av-card-title h5 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #fff;
    }
    .av-card-title span {
        font-size: 12px;
        color: rgba(255,255,255,0.7);
        font-weight: 500;
    }
    .av-force-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        color: #fff;
        white-space: nowrap;
    }

    .av-card-body {
        padding: 22px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .av-input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .av-input-group label {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary-color, #475569);
        margin: 0 !important;
    }
    .dark-mode .av-input-group label { color: #cbd5e1; }
    .av-input-group label i {
        font-size: 12px;
        color: #94a3b8;
    }
    .av-input {
        background: #f8fafc !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: 12px 14px !important;
        font-size: 13px !important;
        width: 100% !important;
        transition: all 0.2s ease;
    }
    .dark-mode .av-input {
        background: rgba(255,255,255,0.04) !important;
        border-color: rgba(255,255,255,0.1) !important;
        color: #f1f5f9 !important;
    }
    .av-input:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1) !important;
        outline: none !important;
    }
    .av-hint {
        font-size: 11px;
        color: #94a3b8;
    }

    /* ── Force-update toggle ── */
    .av-toggle-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    .dark-mode .av-toggle-row {
        background: rgba(255,255,255,0.03);
        border-color: rgba(255,255,255,0.06);
    }
    .av-toggle-text {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .av-toggle-text label {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-secondary-color, #1e293b);
        margin: 0 !important;
        cursor: pointer;
    }
    .dark-mode .av-toggle-text label { color: #f1f5f9; }
    .av-toggle-text small {
        font-size: 11.5px;
        line-height: 1.5;
        color: #94a3b8;
    }
    .av-switch {
        position: relative;
        display: inline-block;
        width: 46px;
        height: 24px;
        flex-shrink: 0;
        margin: 0 !important;
        cursor: pointer;
    }
    .av-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .av-slider {
        position: absolute;
        inset: 0;
        background: #cbd5e1;
        border-radius: 999px;
        transition: background 0.25s ease;
    }
    .dark-mode .av-slider { background: rgba(255,255,255,0.15); }
    .av-slider::after {
        content: '';
        position: absolute;
        top: 3px;
        inset-inline-start: 3px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #fff;
        transition: transform 0.25s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    }
    .av-switch input:checked + .av-slider {
        background: #10b981;
    }
    .av-switch input:checked + .av-slider::after {
        transform: translateX(22px);
    }
    .rtl .av-switch input:checked + .av-slider::after {
        transform: translateX(-22px);
    }

    .av-card-footer {
        padding: 0 22px 22px;
        display: flex;
        justify-content: flex-end;
    }
    .av-save-btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 28px !important;
        border-radius: 10px !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        background: linear-gradient(135deg, var(--accent), var(--accent-strong)) !important;
        color: var(--accent-contrast) !important;
        border: none !important;
        transition: all 0.25s ease;
        box-shadow: 0 3px 10px var(--accent-soft);
        width: auto !important;
    }
    .av-save-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px var(--accent-soft);
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .av-grid {
            grid-template-columns: 1fr;
        }
        .av-card-footer {
            justify-content: stretch;
        }
        .av-save-btn {
            width: 100% !important;
        }
    }
</style>
