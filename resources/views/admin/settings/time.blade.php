
<div id="timeSettings" class="settings-section">
    <div class="section-header-bar">
        <div class="section-header-icon"><i class="fas fa-clock"></i></div>
        <div class="section-header-text">
            <h3>{{ __('Timing Settings') }}</h3>
            <p>{{ __('Configure timezone and time-related preferences') }}</p>
        </div>
    </div>

    <form action="{{ route('admin.app.settings.update') }}" method="POST" class="settings-form modern-form">
        @csrf
        <input type="hidden" name="current_tab" value="">

        {{-- ══════════════════════════════════════════════════════════
             REGION & LOCALE
        ══════════════════════════════════════════════════════════ --}}
        <div class="tm-section">
            <div class="tm-section-header">
                <div class="tm-section-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
                    <i class="fas fa-globe-americas"></i>
                </div>
                <div>
                    <h5>{{ __('Region & Locale') }}</h5>
                    <span>{{ __('Set your timezone, country and language preferences') }}</span>
                </div>
            </div>

            <div class="tm-fields-grid">
                {{-- Timezone --}}
                <div class="tm-field-card">
                    <div class="tm-field-icon-bar">
                        <div class="tm-field-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
                            <i class="fas fa-clock"></i>
                        </div>
                        <label class="tm-field-label">{{ __('Time zone') }}</label>
                    </div>
                    <div class="tm-select-wrap">
                        <select name="timezone" class="form-control tm-select" required>
                            @foreach ($timezones as $timezone)
                                <option value="{{ $timezone->name }}"
                                    {{ $timezone->name == ($settings['timezone'] ?? '') ? 'selected' : '' }}>
                                    {{ $timezone->name }} ({{ $timezone->offset }})
                                </option>
                            @endforeach
                        </select>
                        <div class="tm-select-chevron"><i class="fas fa-chevron-down"></i></div>
                    </div>
                </div>

                {{-- Default Country --}}
                <div class="tm-field-card">
                    <div class="tm-field-icon-bar">
                        <div class="tm-field-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
                            <i class="fas fa-flag"></i>
                        </div>
                        <label class="tm-field-label">{{ __('Default Country') }}</label>
                    </div>
                    <div class="tm-select-wrap">
                        <select name="default_country" class="form-control tm-select select2-country" required>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}"
                                    {{ $country->id == ($settings['default_country'] ?? '') ? 'selected' : '' }}>
                                    {{ app()->getLocale() === 'ar' ? $country->name : $country->e_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="tm-select-chevron"><i class="fas fa-chevron-down"></i></div>
                    </div>
                </div>

                {{-- Default Language --}}
                <div class="tm-field-card">
                    <div class="tm-field-icon-bar">
                        <div class="tm-field-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                            <i class="fas fa-language"></i>
                        </div>
                        <label class="tm-field-label">{{ __('Default language') }}</label>
                    </div>
                    <div class="tm-select-wrap">
                        <select name="default_language" class="form-control tm-select select2-language" required>
                            @foreach ($languages as $language)
                                <option value="{{ $language->code }}"
                                    {{ $language->code == ($settings['default_language'] ?? 'en') ? 'selected' : '' }}>
                                    {{ $language->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="tm-select-chevron"><i class="fas fa-chevron-down"></i></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             WEEK CONFIGURATION
        ══════════════════════════════════════════════════════════ --}}
        <div class="tm-section">
            <div class="tm-section-header">
                <div class="tm-section-icon" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div>
                    <h5>{{ __('Week Configuration') }}</h5>
                    <span>{{ __('Define when your business week starts and ends') }}</span>
                </div>
            </div>

            <div class="tm-week-grid">
                {{-- Start of Week --}}
                <div class="tm-week-card">
                    <div class="tm-week-card-header tm-week-start">
                        <div class="tm-week-badge">
                            <i class="fas fa-play"></i>
                        </div>
                        <span>{{ __('Start of week') }}</span>
                    </div>
                    <div class="tm-week-card-body">
                        <div class="tm-select-wrap">
                            <select name="week_start" class="form-control tm-select" required>
                                @foreach ([
                                    'sunday' => __('Sunday'),
                                    'monday' => __('Monday'),
                                    'tuesday' => __('Tuesday'),
                                    'wednesday' => __('Wednesday'),
                                    'thursday' => __('Thursday'),
                                    'friday' => __('Friday'),
                                    'saturday' => __('Saturday'),
                                ] as $key => $day)
                                    <option value="{{ $key }}" {{ ($settings['week_start'] ?? 'monday') == $key ? 'selected' : '' }}>
                                        {{ $day }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="tm-select-chevron"><i class="fas fa-chevron-down"></i></div>
                        </div>
                    </div>
                </div>

                {{-- Connector Arrow --}}
                <div class="tm-week-connector">
                    <div class="tm-connector-line"></div>
                    <i class="fas fa-arrow-right"></i>
                    <div class="tm-connector-line"></div>
                </div>

                {{-- End of Week --}}
                <div class="tm-week-card">
                    <div class="tm-week-card-header tm-week-end">
                        <div class="tm-week-badge">
                            <i class="fas fa-stop"></i>
                        </div>
                        <span>{{ __('End of week') }}</span>
                    </div>
                    <div class="tm-week-card-body">
                        <div class="tm-select-wrap">
                            <select name="week_end" class="form-control tm-select" required>
                                @foreach ([
                                    'saturday' => __('Saturday'),
                                    'sunday' => __('Sunday'),
                                    'monday' => __('Monday'),
                                    'tuesday' => __('Tuesday'),
                                    'wednesday' => __('Wednesday'),
                                    'thursday' => __('Thursday'),
                                    'friday' => __('Friday'),
                                ] as $key => $day)
                                    <option value="{{ $key }}" {{ ($settings['week_end'] ?? 'sunday') == $key ? 'selected' : '' }}>
                                        {{ $day }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="tm-select-chevron"><i class="fas fa-chevron-down"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             SAVE BUTTON
        ══════════════════════════════════════════════════════════ --}}
        <div class="tm-actions">
            <button type="submit" class="btn tm-btn-save">
                <i class="fas fa-save"></i> {{ __('Save') }}
            </button>
        </div>
    </form>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     SCOPED STYLES
═══════════════════════════════════════════════════════════════ --}}
<style>
    /* ── Section Container ─────────────────────────────────────── */
    .tm-section {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.3s ease;
    }
    .dark-mode .tm-section {
        background: #1e293b;
        border-color: rgba(255,255,255,0.06);
    }

    /* ── Section Header ────────────────────────────────────────── */
    .tm-section-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark-mode .tm-section-header {
        border-bottom-color: rgba(255,255,255,0.06);
    }
    .tm-section-icon {
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
    .tm-section-header h5 {
        margin: 0 0 2px 0;
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    .dark-mode .tm-section-header h5 {
        color: #f1f5f9;
    }
    .tm-section-header span {
        font-size: 13px;
        color: #94a3b8;
    }

    /* ── Fields Grid ───────────────────────────────────────────── */
    .tm-fields-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }

    /* ── Field Card ────────────────────────────────────────────── */
    .tm-field-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px;
        transition: all 0.3s ease;
    }
    .dark-mode .tm-field-card {
        background: #0f172a;
        border-color: rgba(255,255,255,0.06);
    }
    .tm-field-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }
    .dark-mode .tm-field-card:hover {
        border-color: rgba(255,255,255,0.12);
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }
    .tm-field-icon-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }
    .tm-field-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        flex-shrink: 0;
        box-shadow: 0 3px 8px rgba(0,0,0,0.12);
    }
    .tm-field-label {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        margin: 0 !important;
        letter-spacing: 0.02em;
    }
    .dark-mode .tm-field-label {
        color: #cbd5e1;
    }

    /* ── Select Wrap ───────────────────────────────────────────── */
    .tm-select-wrap {
        position: relative;
    }
    .tm-select {
        appearance: none !important;
        -webkit-appearance: none !important;
        padding: 12px 40px 12px 16px !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        font-size: 14px !important;
        font-weight: 500;
        background: #fff !important;
        color: #334155 !important;
        cursor: pointer;
        transition: all 0.3s ease;
        height: auto !important;
        line-height: 1.5 !important;
    }
    .dark-mode .tm-select {
        background: #1e293b !important;
        border-color: rgba(255,255,255,0.08) !important;
        color: #e2e8f0 !important;
    }
    .tm-select:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-soft) !important;
        outline: none !important;
    }
    .tm-select:hover {
        border-color: #cbd5e1 !important;
    }
    .dark-mode .tm-select:hover {
        border-color: rgba(255,255,255,0.15) !important;
    }
    .tm-select-chevron {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
        font-size: 12px;
        transition: color 0.3s ease;
    }
    .rtl .tm-select-chevron {
        right: auto;
        left: 14px;
    }

    /* ── Week Configuration Grid ───────────────────────────────── */
    .tm-week-grid {
        display: flex;
        align-items: stretch;
        gap: 0;
    }
    .tm-week-card {
        flex: 1;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .dark-mode .tm-week-card {
        background: #0f172a;
        border-color: rgba(255,255,255,0.06);
    }
    .tm-week-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }
    .dark-mode .tm-week-card:hover {
        border-color: rgba(255,255,255,0.12);
    }
    .tm-week-card-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 18px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }
    .dark-mode .tm-week-card-header {
        color: #cbd5e1;
    }
    .tm-week-start {
        border-bottom: 2px solid #10b981;
        background: linear-gradient(180deg, rgba(16,185,129,0.06) 0%, transparent 100%);
    }
    .dark-mode .tm-week-start {
        background: linear-gradient(180deg, rgba(16,185,129,0.1) 0%, transparent 100%);
    }
    .tm-week-end {
        border-bottom: 2px solid #ef4444;
        background: linear-gradient(180deg, rgba(239,68,68,0.06) 0%, transparent 100%);
    }
    .dark-mode .tm-week-end {
        background: linear-gradient(180deg, rgba(239,68,68,0.1) 0%, transparent 100%);
    }
    .tm-week-badge {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        color: #fff;
    }
    .tm-week-start .tm-week-badge {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 3px 8px rgba(16,185,129,0.3);
    }
    .tm-week-end .tm-week-badge {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 3px 8px rgba(239,68,68,0.3);
    }
    .tm-week-card-body {
        padding: 18px;
    }

    /* ── Week Connector ────────────────────────────────────────── */
    .tm-week-connector {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 12px;
        color: #cbd5e1;
        flex-shrink: 0;
    }
    .dark-mode .tm-week-connector {
        color: rgba(255,255,255,0.15);
    }
    .tm-week-connector i {
        font-size: 16px;
        color: #94a3b8;
    }
    .tm-connector-line {
        width: 20px;
        height: 2px;
        background: #e2e8f0;
        border-radius: 1px;
    }
    .dark-mode .tm-connector-line {
        background: rgba(255,255,255,0.08);
    }

    /* ── Action Buttons ────────────────────────────────────────── */
    .tm-actions {
        display: flex;
        gap: 12px;
        padding-top: 8px;
    }
    .tm-btn-save {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        padding: 12px 32px !important;
        background: linear-gradient(135deg, var(--accent), var(--accent-strong)) !important;
        color: var(--accent-contrast) !important;
        border: none !important;
        border-radius: 12px !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 14px var(--accent-soft);
        width: auto !important;
    }
    .tm-btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px var(--accent-soft);
        background: linear-gradient(135deg, var(--accent-strong), var(--accent)) !important;
    }
    .tm-btn-save:active {
        transform: translateY(0);
    }

    /* ── Responsive ────────────────────────────────────────────── */
    @media (max-width: 768px) {
        .tm-fields-grid {
            grid-template-columns: 1fr;
        }
        .tm-week-grid {
            flex-direction: column;
            gap: 12px;
        }
        .tm-week-connector {
            justify-content: center;
            padding: 4px 0;
        }
        .tm-week-connector i {
            transform: rotate(90deg);
        }
        .tm-connector-line {
            width: 12px;
        }
        .tm-actions {
            flex-direction: column;
        }
        .tm-btn-save {
            width: 100% !important;
            justify-content: center;
        }
        .tm-section {
            padding: 16px;
        }
    }
</style>
