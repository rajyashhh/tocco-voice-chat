<div id="appSettings" class="app-settings settings-section">
    <div class="section-header-bar">
        <div class="section-header-icon"><i class="fas fa-swatchbook"></i></div>
        <div class="section-header-text">
            <h3>{{ __('App Appearance') }}</h3>
            <p>{{ __('Themes and bottom navigation icons') }}</p>
        </div>
    </div>

    <form action="{{ route('admin.app-config.update') }}" method="POST" enctype="multipart/form-data" class="settings-form modern-form">
        @csrf

        {{-- ══════════════════════════════════════════════════════════
             1) THEMES (replaces the old "سمة الجسم" card)
        ══════════════════════════════════════════════════════════ --}}
        <div class="as-section">
            <div class="as-section-header">
                <div class="as-section-icon" style="background: linear-gradient(135deg, #ec4899, #f472b6);">
                    <i class="fas fa-swatchbook"></i>
                </div>
                <div>
                    <h5>{{ __('السمات (Themes)') }}</h5>
                    <span>{{ __('اختر سمة التطبيق. أسفل كل سمة الشاشات التي تتأثر بها (معلومات للمسؤول).') }}</span>
                </div>
            </div>

            {{-- Theme selector (the real switch the app reads) --}}
            <div class="as-fields-grid">
                <div class="as-field-card">
                    <div class="as-field-top">
                        <div class="as-field-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
                            <i class="fas fa-palette"></i>
                        </div>
                        <label class="as-field-label">{{ __('Choose Theme') }}</label>
                    </div>
                    @php
                        $themeVariants = [
                            'default' => __('التصميم 1 / Theme 1'),
                            'theme_1' => __('التصميم 2 / Theme 2'),
                            'theme_2' => __('التصميم 3 / Theme 3'),
                            'theme_3' => __('التصميم 4 / Theme 4'),
                        ];
                        // Normalize a stored legacy value so existing rows still
                        // highlight the right option (the app applies the same map).
                        $variantAliases = ['[REMOVED]' => 'theme_2', 'new_theme' => 'theme_1'];
                        $rawVariant     = data_get($settings, 'app_ui_variant', 'default');
                        $currentVariant = $variantAliases[$rawVariant] ?? $rawVariant;
                    @endphp
                    <div class="as-select-wrap">
                        <select name="app_ui_variant" class="form-control as-select">
                            @foreach($themeVariants as $variantValue => $variantLabel)
                                <option value="{{ $variantValue }}" {{ $currentVariant === $variantValue ? 'selected' : '' }}>{{ $variantLabel }}</option>
                            @endforeach
                        </select>
                        <div class="as-select-chevron"><i class="fas fa-chevron-down"></i></div>
                    </div>
                </div>
            </div>

            {{-- Informational: themes and the screens each affects --}}
            @php
                $themeInfo = [
                    'default' => [
                        'label'   => __('التصميم 1 / Theme 1'),
                        'screens' => [__('الرئيسية (Home)'), __('الملف الشخصي (Profile)'), __('طقم أيقونات الشريط السفلي')],
                    ],
                    'theme_1' => [
                        'label'   => __('التصميم 2 / Theme 2'),
                        'screens' => [__('الرئيسية (Home)'), __('الملف الشخصي (Profile)'), __('طقم أيقونات الشريط السفلي')],
                    ],
                    'theme_2' => [
                        'label'   => __('التصميم 3 / Theme 3'),
                        'screens' => [
                            __('الرئيسية (Theme 3 Home)'),
                            __('الملف الشخصي (Theme 3 Profile)'),
                            __('ملف الزائر (Visitor Profile)'),
                            __('تسجيل الدخول / المقدمة (Login / Intro)'),
                            __('شريط سفلي مخصص (Custom Bottom Nav)'),
                        ],
                    ],
                    'theme_3' => [
                        'label'   => __('التصميم 4 / Theme 4'),
                        'screens' => [
                            __('الرئيسية (Theme 4 Home)'),
                            __('تسجيل الدخول / المقدمة (Login / Intro)'),
                            __('التخطيط والتنقل (Layout / Nav)'),
                            __('الملف الشخصي (Profile)'),
                        ],
                    ],
                ];
            @endphp
            <div class="as-themes-info">
                @foreach($themeInfo as $tKey => $t)
                    <div class="as-theme-block {{ $currentVariant === $tKey ? 'is-active' : '' }}">
                        <div class="as-theme-head">
                            <span class="as-theme-name">{{ $t['label'] }}</span>
                            @if($currentVariant === $tKey)
                                <span class="as-theme-badge">{{ __('مُفعّلة') }}</span>
                            @endif
                        </div>
                        <ul class="as-theme-screens">
                            @foreach($t['screens'] as $screen)
                                <li><i class="fas fa-check"></i> {{ $screen }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             2) BOTTOM NAV — 5 nav-icon uploads (colors are app-fixed)
        ══════════════════════════════════════════════════════════ --}}
        <div class="as-section">
            <div class="as-section-header">
                <div class="as-section-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
                    <i class="fas fa-bars"></i>
                </div>
                <div>
                    <h5>{{ __('الشريط السفلي (Bottom Nav)') }}</h5>
                    <span>{{ __('أيقونات التبويبات الخمسة (Active / Inactive). الألوان مثبّتة داخل التطبيق.') }}</span>
                </div>
            </div>

            {{-- 5 nav tabs × (active + inactive) icon uploads --}}
            @php
                // Five fixed tabs, each with an ACTIVE (selected) and INACTIVE
                // (unselected) icon. The app fetches both per tab and renders the
                // active URL when the tab is selected, inactive otherwise.
                // Back-compat: when no per-state icon was ever uploaded, fall back
                // to the legacy single nav_icon_N for the current-icon preview so
                // existing setups still show their old upload.
                $navIconTabs = [
                    1 => __('الرئيسية / Home'),
                    2 => __('استكشاف / Explore (or Games)'),
                    3 => __('الدردشة / Chat'),
                    4 => __('اللحظات / Moment (or World)'),
                    5 => __('الملف الشخصي / Profile'),
                ];
            @endphp
            <h6 class="as-subhead">{{ __('أيقونات الشريط السفلي (5 تبويبات × حالتين)') }}</h6>
            <small class="as-field-hint" style="margin-bottom:14px;">{{ __('ارفع لكل تبويب أيقونتين: حالة مفعّلة (Active) وحالة غير مفعّلة (Inactive). سيقوم التطبيق بجلبها وتخزينها مؤقتًا (cache).') }}</small>
            <div class="as-fields-grid as-nav-icons-grid">
                @foreach($navIconTabs as $i => $tabLabel)
                    @php
                        $activeKey   = "nav_icon_active_$i";
                        $inactiveKey = "nav_icon_inactive_$i";
                        $activeVal   = data_get($settings, $activeKey)   ?: data_get($settings, "nav_icon_$i");
                        $inactiveVal = data_get($settings, $inactiveKey) ?: data_get($settings, "nav_icon_$i");
                    @endphp
                    <div class="as-field-card">
                        <div class="as-field-top">
                            <div class="as-field-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
                                <i class="fas fa-icons"></i>
                            </div>
                            <label class="as-field-label">{{ $tabLabel }}</label>
                        </div>

                        {{-- Active state --}}
                        <label class="as-field-label" style="font-size:12px;margin-top:6px;">{{ __('مفعّلة (Active)') }}</label>
                        {{-- Clear flag: toggled to 1 by the delete button below so the
                             server stores an empty value and the tab reverts to the
                             app's bundled default icon. --}}
                        <input type="hidden" name="remove_{{ $activeKey }}" value="0" data-nav-clear-flag>
                        <div class="as-file-upload-wrap">
                            <input type="file" name="{{ $activeKey }}" class="as-file-input" accept="image/*">
                            <div class="as-file-upload-placeholder">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>{{ __('Choose image or drag here') }}</span>
                            </div>
                        </div>
                        @if(!empty($activeVal))
                            <div class="as-img-preview" data-nav-preview>
                                <img src="{{ getImagePath($activeVal) }}" alt="">
                                <span>{{ __('Active') }}</span>
                                <button type="button" class="as-img-remove" data-nav-clear-btn="remove_{{ $activeKey }}">
                                    <i class="fas fa-trash-alt"></i> {{ __('حذف / Remove') }}
                                </button>
                            </div>
                        @endif

                        {{-- Inactive state --}}
                        <label class="as-field-label" style="font-size:12px;margin-top:10px;">{{ __('غير مفعّلة (Inactive)') }}</label>
                        <input type="hidden" name="remove_{{ $inactiveKey }}" value="0" data-nav-clear-flag>
                        <div class="as-file-upload-wrap">
                            <input type="file" name="{{ $inactiveKey }}" class="as-file-input" accept="image/*">
                            <div class="as-file-upload-placeholder">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>{{ __('Choose image or drag here') }}</span>
                            </div>
                        </div>
                        @if(!empty($inactiveVal))
                            <div class="as-img-preview" data-nav-preview>
                                <img src="{{ getImagePath($inactiveVal) }}" alt="">
                                <span>{{ __('Inactive') }}</span>
                                <button type="button" class="as-img-remove" data-nav-clear-btn="remove_{{ $inactiveKey }}">
                                    <i class="fas fa-trash-alt"></i> {{ __('حذف / Remove') }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             ACTION BUTTONS
        ══════════════════════════════════════════════════════════ --}}
        <div class="as-actions">
            <button type="submit" class="btn as-btn-save">
                <i class="fas fa-save"></i> {{ __('حفظ') }}
            </button>
        </div>
    </form>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     SCOPED STYLES
═══════════════════════════════════════════════════════════════ --}}
<style>
    /* ── Section Container ─────────────────────────────────────── */
    .as-section {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.3s ease;
    }
    .dark-mode .as-section {
        background: #1e293b;
        border-color: rgba(255,255,255,0.06);
    }

    /* ── Section Header ────────────────────────────────────────── */
    .as-section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark-mode .as-section-header {
        border-bottom-color: rgba(255,255,255,0.06);
    }
    .as-section-icon {
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
    .as-section-header h5 {
        margin: 0 0 2px 0;
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    .dark-mode .as-section-header h5 {
        color: #f1f5f9;
    }
    .as-section-header span {
        font-size: 13px;
        color: #94a3b8;
    }

    /* ── Sub-heading ───────────────────────────────────────────── */
    .as-subhead {
        margin: 22px 0 12px 0;
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        padding-bottom: 6px;
        border-bottom: 1px solid #f1f5f9;
    }
    .as-subhead--first {
        margin-top: 0;
    }
    .dark-mode .as-subhead {
        color: #cbd5e1;
        border-bottom-color: rgba(255,255,255,0.06);
    }

    /* ── Nav-icons grid (compact, 5 tabs) ─────────────────────── */
    /* Specificity bumped (two classes) so it wins over .as-fields-grid 280px
       regardless of source order — this element carries both classes. */
    .as-fields-grid.as-nav-icons-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
    .as-nav-icons-grid .as-img-preview img {
        max-width: 84px !important;
    }

    /* ── Fields Grid ───────────────────────────────────────────── */
    .as-fields-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }

    /* ── Field Card ────────────────────────────────────────────── */
    .as-field-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px;
        transition: all 0.3s ease;
    }
    .dark-mode .as-field-card {
        background: #0f172a;
        border-color: rgba(255,255,255,0.06);
    }
    .as-field-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }
    .dark-mode .as-field-card:hover {
        border-color: rgba(255,255,255,0.12);
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }
    .as-field-top {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
    }
    .as-field-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 12px;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    }
    .as-field-label {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        margin: 0 !important;
        letter-spacing: 0.02em;
    }
    .dark-mode .as-field-label {
        color: #cbd5e1;
    }
    .as-field-hint {
        display: block;
        margin-top: 10px;
        font-size: 12px;
        line-height: 1.5;
        color: #94a3b8;
    }
    .dark-mode .as-field-hint {
        color: #94a3b8;
    }

    /* ── Select Wrap ───────────────────────────────────────────── */
    .as-select-wrap {
        position: relative;
    }
    .as-select {
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
    .dark-mode .as-select {
        background: #1e293b !important;
        border-color: rgba(255,255,255,0.08) !important;
        color: #e2e8f0 !important;
    }
    .as-select:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-soft) !important;
        outline: none !important;
    }
    .as-select:hover {
        border-color: #cbd5e1 !important;
    }
    .dark-mode .as-select:hover {
        border-color: rgba(255,255,255,0.15) !important;
    }
    .as-select-chevron {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
        font-size: 12px;
    }
    .rtl .as-select-chevron {
        right: auto;
        left: 14px;
    }

    /* ── File Upload ───────────────────────────────────────────── */
    .as-file-upload-wrap {
        position: relative;
        margin-bottom: 12px;
    }
    .as-file-input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    .as-file-upload-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 16px 14px;
        border: 2px dashed #cbd5e1;
        border-radius: 10px;
        color: #94a3b8;
        transition: all 0.3s ease;
        background: #f8fafc;
    }
    .dark-mode .as-file-upload-placeholder {
        border-color: rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.02);
    }
    .as-file-upload-wrap:hover .as-file-upload-placeholder {
        border-color: var(--accent);
        color: var(--accent);
        background: var(--accent-soft);
    }
    .as-file-upload-placeholder i {
        font-size: 20px;
    }
    .as-file-upload-placeholder span {
        font-size: 12px;
        font-weight: 500;
        text-align: center;
    }

    /* ── Image Preview ─────────────────────────────────────────── */
    .as-img-preview {
        margin-top: 10px;
        text-align: center;
    }
    .as-img-preview img {
        width: 100% !important;
        max-width: 200px !important;
        height: auto !important;
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .dark-mode .as-img-preview img {
        border-color: rgba(255,255,255,0.08);
    }
    .as-img-preview span {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: #94a3b8;
        font-weight: 500;
    }

    /* ── Remove (clear) nav icon button ────────────────────────── */
    .as-img-remove {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        padding: 6px 14px;
        border: 1px solid #fecaca;
        border-radius: 9px;
        background: #fee2e2;
        color: #dc2626;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .as-img-remove:hover {
        background: #fecaca;
        border-color: #fca5a5;
    }
    .dark-mode .as-img-remove {
        background: rgba(220,38,38,0.15);
        border-color: rgba(220,38,38,0.3);
        color: #f87171;
    }
    .as-img-remove i {
        font-size: 11px;
    }
    .as-img-preview.is-cleared {
        opacity: 0.45;
    }
    .as-img-preview.is-cleared img {
        filter: grayscale(1);
    }

    /* ── Themes Info ───────────────────────────────────────────── */
    .as-themes-info {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 16px;
        margin-top: 18px;
    }
    .as-theme-block {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 18px;
    }
    .dark-mode .as-theme-block {
        background: #0f172a;
        border-color: rgba(255,255,255,0.06);
    }
    .as-theme-block.is-active {
        border-color: var(--accent);
        box-shadow: 0 0 0 2px var(--accent-soft);
    }
    .as-theme-head {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }
    .as-theme-name {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
    }
    .dark-mode .as-theme-name {
        color: #f1f5f9;
    }
    .as-theme-badge {
        font-size: 11px;
        font-weight: 700;
        color: var(--accent-contrast);
        background: linear-gradient(135deg, var(--accent), var(--accent-strong));
        padding: 2px 10px;
        border-radius: 999px;
    }
    .as-theme-screens {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .as-theme-screens li {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12.5px;
        color: #64748b;
        padding: 4px 0;
    }
    .dark-mode .as-theme-screens li {
        color: #94a3b8;
    }
    .as-theme-screens li i {
        color: #10b981;
        font-size: 11px;
    }

    /* ── Action Buttons ────────────────────────────────────────── */
    .as-actions {
        display: flex;
        gap: 12px;
        padding-top: 8px;
    }
    .as-btn-save {
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
    .as-btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px var(--accent-soft);
        background: linear-gradient(135deg, var(--accent-strong), var(--accent)) !important;
    }
    .as-btn-save:active {
        transform: translateY(0);
    }

    /* ── Responsive ────────────────────────────────────────────── */
    @media (max-width: 768px) {
        .as-fields-grid {
            grid-template-columns: 1fr;
        }
        .as-actions {
            flex-direction: column;
        }
        .as-btn-save {
            width: 100% !important;
            justify-content: center;
        }
        .as-section {
            padding: 16px;
        }
    }
</style>

{{-- ═══════════════════════════════════════════════════════════════
     NAV-ICON CLEAR — a delete button next to each uploaded nav-icon
     preview toggles its hidden remove_<key> flag to 1 so the server
     clears the stored icon and the tab reverts to the bundled default.
═══════════════════════════════════════════════════════════════ --}}
<script>
    (function () {
        function run() {
            document.querySelectorAll('#appSettings [data-nav-clear-btn]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var flagName = btn.getAttribute('data-nav-clear-btn');
                    var flag = document.querySelector('#appSettings input[name="' + flagName + '"]');
                    var preview = btn.closest('[data-nav-preview]');
                    if (!flag) return;
                    if (flag.value === '1') {
                        // undo clear
                        flag.value = '0';
                        if (preview) preview.classList.remove('is-cleared');
                        btn.innerHTML = '<i class="fas fa-trash-alt"></i> {{ __('حذف / Remove') }}';
                    } else {
                        flag.value = '1';
                        if (preview) preview.classList.add('is-cleared');
                        btn.innerHTML = '<i class="fas fa-undo"></i> {{ __('تراجع / Undo') }}';
                    }
                });
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    })();
</script>
