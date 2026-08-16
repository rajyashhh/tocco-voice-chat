<div id="workSettings" class="settings-section">
    <div class="section-header-bar">
        <div class="section-header-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
            <i class="fas fa-briefcase"></i>
        </div>
        <div class="section-header-text">
            <h3>{{ __('Work Settings') }}</h3>
            <p>{{ __('Configure experience, charge and work-related settings') }}</p>
        </div>
    </div>

    <div class="box-body">
        @php $chargeTabType = 'Experience'; @endphp

        @php
            use Modules\Vip\Entities\Vip;
            $oldExpData = cache('exp_percentages') ?? [];
            $vipTypeCounts = Vip::getCached()->groupBy('type')->map->count();

            $expTypes = [
                [
                    'key'      => 'exp_sender_percentage',
                    'type'     => 2,
                    'name'     => __('Sender level (wealth)'),
                    'hint'     => __('Every 1 coin the user sends in gifts = how many XP points'),
                    'icon'     => 'fa-gem',
                    'gradient' => 'wealth-gradient',
                ],
                [
                    'key'      => 'exp_received_percentage',
                    'type'     => 1,
                    'name'     => __('Receiver level (attraction)'),
                    'hint'     => __('Every 1 coin worth of gifts the user receives = how many XP points'),
                    'icon'     => 'fa-heart',
                    'gradient' => 'attraction-gradient',
                ],
                [
                    'key'      => 'exp_charge_percentage',
                    'type'     => 5,
                    'name'     => __('Charge level'),
                    'hint'     => __('Every 1 coin the user recharges = how many XP points'),
                    'icon'     => 'fa-bolt',
                    'gradient' => 'charge-gradient',
                ],
                [
                    'key'      => 'exp_cp_percentage',
                    'type'     => 3,
                    'name'     => __('CP level'),
                    'hint'     => __('Every 1 coin of gifts inside the CP relationship = how many XP points'),
                    'icon'     => 'fa-users',
                    'gradient' => 'cp-gradient',
                ],
                [
                    'key'      => 'exp_room_percentage',
                    'type'     => 4,
                    'name'     => __('Room level'),
                    'hint'     => __('Every 1 coin sent inside the room = how many XP points'),
                    'icon'     => 'fa-door-open',
                    'gradient' => 'rooms-gradient',
                ],
            ];
        @endphp

        <!-- ═══════════════ Experience Tab ═══════════════ -->
        <div id="Experience_tab" class="inner-tab-content"
             style="display:{{ $chargeTabType == 'Experience' ? 'block' : 'none' }};">

            {{-- ── Section A: conversion coefficients ── --}}
            <div class="work-section-intro">
                <div class="work-intro-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div>
                    <h4>{{ __('XP conversion rates') }}</h4>
                    <p>{{ __('How many XP points each spent coin generates, per level type') }}</p>
                </div>
            </div>

            <form action="{{ route('admin.ovip-config') }}" method="POST" class="work-card-form settings-form">
                @csrf
                <div class="exp-rates-card work-exp-card">
                    @foreach ($expTypes as $expType)
                        <div class="exp-rate-row">
                            <div class="exp-rate-icon {{ $expType['gradient'] }}">
                                <i class="fas {{ $expType['icon'] }}"></i>
                            </div>
                            <div class="exp-rate-text">
                                <h5>{{ $expType['name'] }}
                                    <span class="exp-rate-count">{{ $vipTypeCounts[$expType['type']] ?? 0 }} {{ __('levels') }}</span>
                                </h5>
                                <p>{{ $expType['hint'] }}</p>
                            </div>
                            <div class="exp-rate-input work-input-wrap">
                                <input type="number" name="{{ $expType['key'] }}"
                                       value="{{ $oldExpData[$expType['key']] ?? '' }}"
                                       placeholder="1" class="form-control work-input"
                                       step="any" min="0" required>
                                <span class="work-input-suffix">XP</span>
                            </div>
                        </div>
                    @endforeach

                    <div class="work-card-footer">
                        <button type="submit" class="btn btn-primary btn-save work-save-btn">
                            <i class="fas fa-save"></i> {{ __('Save conversion rates') }}
                        </button>
                    </div>
                </div>
            </form>

            {{-- ── Section B: curve generator ── --}}
            <div class="work-section-intro" style="margin-top: 28px;">
                <div class="work-intro-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <h4>{{ __('Level curve generator') }}</h4>
                    <p>{{ __('Generate the XP thresholds of a whole level ladder — preview first, then apply') }}</p>
                </div>
            </div>

            <div class="work-exp-card curve-generator-card">
                <form id="curveApplyForm" action="{{ route('admin.level-curve-apply') }}" method="POST"
                      class="work-card-form settings-form">
                    @csrf
                    <div class="work-card-body">
                        <div class="curve-fields-grid">
                            <div class="work-field-group">
                                <label class="work-field-label">
                                    <i class="fas fa-layer-group"></i> {{ __('Level type') }}
                                </label>
                                <select name="level_type" id="curve_level_type" class="form-control work-input" required>
                                    @foreach ($expTypes as $expType)
                                        <option value="{{ $expType['type'] }}">
                                            {{ $expType['name'] }} ({{ $vipTypeCounts[$expType['type']] ?? 0 }} {{ __('levels') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="work-field-group">
                                <label class="work-field-label">
                                    <i class="fas fa-list-ol"></i> {{ __('Number of levels') }}
                                </label>
                                <input type="number" name="levels_count" id="curve_levels_count"
                                       class="form-control work-input" min="1" max="500" required
                                       placeholder="100">
                            </div>

                            <div class="work-field-group">
                                <label class="work-field-label">
                                    <i class="fas fa-flag-checkered"></i> {{ __('XP of first level') }}
                                </label>
                                <input type="number" name="first_exp" id="curve_first_exp"
                                       class="form-control work-input" min="1" required
                                       placeholder="500">
                            </div>

                            <div class="work-field-group">
                                <label class="work-field-label">
                                    <i class="fas fa-arrow-trend-up"></i> {{ __('Growth rate %') }}
                                </label>
                                <input type="number" name="growth_pct" id="curve_growth_pct"
                                       class="form-control work-input" min="0" max="1000" step="any" required
                                       placeholder="15">
                                <small class="work-field-hint"><i class="fas fa-info-circle"></i>
                                    {{ __('Each level needs this % more XP than the previous one (difficulty)') }}</small>
                            </div>

                            <div class="work-field-group">
                                <label class="work-field-label">
                                    <i class="fas fa-mountain"></i> {{ __('Growth rate after midpoint % (optional)') }}
                                </label>
                                <input type="number" name="late_growth_pct" id="curve_late_growth_pct"
                                       class="form-control work-input" min="0" max="1000" step="any"
                                       placeholder="{{ __('Same as growth rate') }}">
                            </div>
                        </div>

                        <div id="curvePreviewError" class="work-alert-banner" style="display:none;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span id="curvePreviewErrorText"></span>
                        </div>

                        <div id="curvePreviewWrap" style="display:none;">
                            <div class="work-calculator-box">
                                <div class="work-calculator-header">
                                    <i class="fas fa-table"></i>
                                    <span>{{ __('Curve preview') }}</span>
                                    <span id="curvePreviewSummary" class="curve-preview-summary"></span>
                                </div>
                                <div class="work-calculator-body curve-preview-body">
                                    <table class="table curve-preview-table">
                                        <thead>
                                        <tr>
                                            <th>{{ __('Level') }}</th>
                                            <th>{{ __('XP threshold (cumulative)') }}</th>
                                            <th>{{ __('XP needed from previous level') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody id="curvePreviewRows"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="work-card-footer curve-actions">
                        <button type="button" id="curvePreviewBtn" class="btn btn-default work-preview-btn">
                            <i class="fas fa-eye"></i> {{ __('Preview curve') }}
                        </button>
                        <button type="submit" id="curveApplyBtn" class="btn btn-primary btn-save work-save-btn"
                                disabled>
                            <i class="fas fa-check"></i> {{ __('Apply to levels') }}
                        </button>
                    </div>
                </form>
            </div>

            <script>
                (function () {
                    const previewBtn = document.getElementById('curvePreviewBtn');
                    const applyBtn = document.getElementById('curveApplyBtn');
                    const form = document.getElementById('curveApplyForm');
                    const wrap = document.getElementById('curvePreviewWrap');
                    const rows = document.getElementById('curvePreviewRows');
                    const summary = document.getElementById('curvePreviewSummary');
                    const errBox = document.getElementById('curvePreviewError');
                    const errText = document.getElementById('curvePreviewErrorText');
                    if (!previewBtn || !form) return;

                    const inputs = ['curve_level_type', 'curve_levels_count', 'curve_first_exp', 'curve_growth_pct', 'curve_late_growth_pct'];

                    // Any parameter change invalidates the previous preview.
                    inputs.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.addEventListener('input', () => { applyBtn.disabled = true; });
                        if (el && el.tagName === 'SELECT') el.addEventListener('change', () => { applyBtn.disabled = true; });
                    });

                    previewBtn.addEventListener('click', function () {
                        errBox.style.display = 'none';
                        const fd = new FormData(form);
                        previewBtn.disabled = true;

                        fetch("{{ route('admin.level-curve-preview') }}", {
                            method: 'POST',
                            headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
                            body: fd
                        }).then(async r => {
                            const data = await r.json();
                            if (!r.ok || !data.curve) {
                                const msg = data.message || Object.values(data.errors || {}).flat().join(' — ') || '{{ __('Preview failed') }}';
                                throw new Error(msg);
                            }
                            rows.innerHTML = '';
                            let prev = 0;
                            const levels = Object.keys(data.curve);
                            levels.forEach(level => {
                                const exp = data.curve[level];
                                const tr = document.createElement('tr');
                                tr.innerHTML = '<td>' + level + '</td>' +
                                    '<td>' + Number(exp).toLocaleString() + '</td>' +
                                    '<td>+' + Number(exp - prev).toLocaleString() + '</td>';
                                rows.appendChild(tr);
                                prev = exp;
                            });
                            summary.textContent = levels.length + ' {{ __('levels') }} — {{ __('last threshold') }}: ' + Number(prev).toLocaleString();
                            wrap.style.display = 'block';
                            applyBtn.disabled = false;
                        }).catch(e => {
                            wrap.style.display = 'none';
                            applyBtn.disabled = true;
                            errText.textContent = e.message;
                            errBox.style.display = 'flex';
                        }).finally(() => {
                            previewBtn.disabled = false;
                        });
                    });

                    form.addEventListener('submit', function (e) {
                        if (applyBtn.disabled) {
                            e.preventDefault();
                            return;
                        }
                        if (!confirm('{{ __('This will overwrite the XP thresholds of the selected level type. Existing level images are kept. Continue?') }}')) {
                            e.preventDefault();
                        }
                    });
                })();
            </script>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════
     WORK SETTINGS — Scoped Styles
     ═══════════════════════════════════════════════════ --}}
<style>
    /* ── Section Intro ── */
    .work-section-intro {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 28px;
        padding: 18px 22px;
        background: var(--white, #fff);
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    }
    .dark-mode .work-section-intro {
        background: var(--dark-secondry-color, #1e293b);
        border-color: rgba(255,255,255,0.06);
    }
    .work-intro-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--accent), var(--accent-strong));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: var(--accent-contrast);
        flex-shrink: 0;
        box-shadow: 0 4px 12px var(--accent-soft);
    }
    .work-section-intro h4 {
        margin: 0 0 3px;
        font-size: 17px;
        font-weight: 700;
        color: var(--text-secondary-color, #1e293b);
    }
    .dark-mode .work-section-intro h4 { color: #f1f5f9; }
    .work-section-intro p {
        margin: 0;
        font-size: 13px;
        color: #94a3b8;
    }

    /* ── Cards Grid ── */
    .work-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 22px;
    }
    .work-cards-grid.work-single-card {
        grid-template-columns: minmax(340px, 460px);
    }

    /* ── Individual Card ── */
    .work-exp-card {
        border-radius: 18px;
        overflow: hidden;
        background: var(--white, #fff);
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark-mode .work-exp-card {
        background: var(--dark-secondry-color, #1e293b);
        border-color: rgba(255,255,255,0.06);
    }
    .work-exp-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 36px rgba(0,0,0,0.1);
        border-color: var(--accent-soft);
    }
    .dark-mode .work-exp-card:hover {
        box-shadow: 0 12px 36px rgba(0,0,0,0.3);
        border-color: var(--accent-soft);
    }

    /* ── Card Form Reset ── */
    .work-card-form {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
    }

    /* ── Card Top / Header with Gradient ── */
    .work-card-top {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 20px 22px;
        position: relative;
        overflow: hidden;
    }
    .work-card-top::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
    }
    .work-card-top::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -10%;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
    }
    .work-card-top h5 {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: #fff;
        flex: 1;
        text-transform: capitalize;
        position: relative;
        z-index: 1;
    }
    .work-card-icon-wrap {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #fff;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }
    .work-card-badge {
        padding: 4px 12px;
        border-radius: 20px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1px;
        position: relative;
        z-index: 1;
    }

    /* Gradient Themes */
    .wealth-gradient    { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .attraction-gradient { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .charge-gradient    { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .rooms-gradient     { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .cp-gradient        { background: linear-gradient(135deg, #10b981, #059669); }
    .diamond-gradient   { background: linear-gradient(135deg, #06b6d4, #0891b2); }

    /* ── Card Body ── */
    .work-card-body {
        padding: 22px;
    }

    /* ── Field Group ── */
    .work-field-group {
        margin-bottom: 18px;
    }
    .work-field-group:last-child {
        margin-bottom: 0;
    }
    .work-field-label {
        display: flex !important;
        align-items: center;
        gap: 7px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary-color, #475569);
        margin-bottom: 8px !important;
        text-transform: capitalize;
    }
    .dark-mode .work-field-label {
        color: #cbd5e1;
    }
    .work-field-label i {
        font-size: 12px;
        color: #94a3b8;
    }
    .work-field-hint {
        display: flex !important;
        align-items: center;
        gap: 5px;
        margin-top: 7px;
        font-size: 12px;
        color: #94a3b8;
    }
    .work-field-hint i {
        font-size: 11px;
    }

    /* ── Input Styling ── */
    .work-input-wrap {
        position: relative;
    }
    .work-input {
        background: #f8fafc !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: 11px 50px 11px 14px !important;
        font-size: 14px;
        transition: all 0.2s ease;
        width: 100% !important;
    }
    .dark-mode .work-input {
        background: rgba(255,255,255,0.04) !important;
        border-color: rgba(255,255,255,0.1) !important;
        color: #f1f5f9 !important;
    }
    .work-input:focus {
        background: #fff !important;
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-soft) !important;
        outline: none !important;
    }
    .dark-mode .work-input:focus {
        background: rgba(255,255,255,0.06) !important;
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-soft) !important;
    }
    .work-input-suffix {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        font-weight: 700;
        color: #94a3b8;
        pointer-events: none;
    }
    .rtl .work-input-suffix {
        right: auto;
        left: 14px;
    }
    .rtl .work-input {
        padding: 11px 14px 11px 50px !important;
    }

    /* ── Result Badge ── */
    .work-result-badge {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: linear-gradient(135deg, var(--accent), var(--accent-strong)) !important;
        color: var(--accent-contrast);
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        box-shadow: 0 2px 8px var(--accent-soft);
    }
    .rtl .work-result-badge {
        right: auto;
        left: 12px;
    }

    /* ── Alert Banner ── */
    .work-alert-banner {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        border-radius: 10px;
        background: #fef3c7;
        border: 1px solid #fcd34d;
        margin-top: 14px;
        font-size: 13px;
        color: #92400e;
    }
    .dark-mode .work-alert-banner {
        background: rgba(251,191,36,0.1);
        border-color: rgba(251,191,36,0.2);
        color: #fbbf24;
    }
    .work-alert-banner > i {
        color: #f59e0b;
        font-size: 15px;
        flex-shrink: 0;
    }
    .work-alert-banner span {
        flex: 1;
        font-weight: 500;
    }
    .work-alert-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 6px;
        background: #f59e0b;
        color: #fff !important;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none !important;
        white-space: nowrap;
        transition: all 0.2s ease;
    }
    .work-alert-link:hover {
        background: #d97706;
        transform: translateX(2px);
    }

    /* ── Card Footer ── */
    .work-card-footer {
        padding: 16px 22px;
        border-top: 1px solid #f1f5f9;
    }
    .dark-mode .work-card-footer {
        border-top-color: rgba(255,255,255,0.06);
    }
    .work-save-btn {
        width: 100% !important;
        padding: 12px 24px !important;
        border-radius: 10px !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: linear-gradient(135deg, var(--accent), var(--accent-strong)) !important;
        border: none !important;
        color: var(--accent-contrast) !important;
        transition: all 0.25s ease !important;
        box-shadow: 0 2px 8px var(--accent-soft) !important;
    }
    .work-save-btn:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px var(--accent-soft) !important;
    }
    .work-save-btn:active {
        transform: translateY(0) !important;
    }

    /* ── Exchange Visual ── */
    .work-exchange-visual {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        padding: 22px;
        margin-bottom: 22px;
        background: linear-gradient(135deg, rgba(6,182,212,0.06), rgba(8,145,178,0.03));
        border-radius: 14px;
        border: 1px dashed rgba(6,182,212,0.2);
    }
    .dark-mode .work-exchange-visual {
        background: linear-gradient(135deg, rgba(6,182,212,0.08), rgba(8,145,178,0.04));
        border-color: rgba(6,182,212,0.15);
    }
    .work-exchange-from,
    .work-exchange-to {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }
    .work-exchange-from i {
        font-size: 28px;
        color: #06b6d4;
    }
    .work-exchange-from span {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary-color, #475569);
    }
    .dark-mode .work-exchange-from span,
    .dark-mode .work-exchange-to small { color: #94a3b8; }
    .work-exchange-arrow {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #06b6d4, #0891b2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        box-shadow: 0 3px 10px rgba(6,182,212,0.3);
    }
    .work-exchange-to span {
        font-size: 32px;
        font-weight: 800;
        color: #0891b2;
        line-height: 1;
    }
    .dark-mode .work-exchange-to span { color: #22d3ee; }
    .work-exchange-to small {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .work-exchange-to i {
        font-size: 28px;
        color: #2563eb;
    }

    /* ── Calculator Box ── */
    .work-calculator-box {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        margin-top: 18px;
    }
    .dark-mode .work-calculator-box {
        border-color: rgba(255,255,255,0.08);
    }
    .work-calculator-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary-color, #475569);
    }
    .dark-mode .work-calculator-header {
        background: rgba(255,255,255,0.03);
        border-bottom-color: rgba(255,255,255,0.06);
        color: #cbd5e1;
    }
    .work-calculator-header i {
        color: var(--accent);
    }
    .work-calculator-body {
        padding: 16px;
    }

    /* ── XP Conversion Rates (Section A) ── */
    .exp-rates-card {
        max-width: 860px;
    }
    .exp-rate-row {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 18px 22px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark-mode .exp-rate-row {
        border-bottom-color: rgba(255,255,255,0.06);
    }
    .exp-rate-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #fff;
        flex-shrink: 0;
    }
    .exp-rate-text {
        flex: 1;
        min-width: 0;
    }
    .exp-rate-text h5 {
        margin: 0 0 4px;
        font-size: 15px;
        font-weight: 700;
        color: var(--text-secondary-color, #1e293b);
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .dark-mode .exp-rate-text h5 { color: #f1f5f9; }
    .exp-rate-count {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 10px;
        border-radius: 12px;
        background: var(--accent-soft);
        color: var(--accent);
    }
    .exp-rate-text p {
        margin: 0;
        font-size: 13px;
        color: #94a3b8;
    }
    .exp-rate-input {
        width: 160px;
        flex-shrink: 0;
    }

    /* ── Curve Generator (Section B) ── */
    .curve-generator-card {
        max-width: 860px;
    }
    .curve-fields-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 16px;
        margin-bottom: 18px;
    }
    .curve-actions {
        display: flex;
        gap: 12px;
    }
    .curve-actions .work-preview-btn {
        flex: 1;
        padding: 12px 24px !important;
        border-radius: 10px !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .curve-actions .work-save-btn {
        flex: 1;
    }
    .curve-actions .work-save-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
    }
    .curve-preview-body {
        max-height: 340px;
        overflow-y: auto;
        padding: 0;
    }
    .curve-preview-table {
        margin: 0;
        width: 100%;
    }
    .curve-preview-table th {
        position: sticky;
        top: 0;
        background: #f8fafc;
        font-size: 12px;
        z-index: 1;
    }
    .dark-mode .curve-preview-table th {
        background: var(--dark-secondry-color, #1e293b);
    }
    .curve-preview-table td {
        font-size: 13px;
    }
    .curve-preview-summary {
        margin-inline-start: auto;
        font-size: 12px;
        color: var(--accent);
        font-weight: 700;
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .exp-rate-row {
            flex-wrap: wrap;
        }
        .exp-rate-input {
            width: 100%;
        }
        .work-cards-grid {
            grid-template-columns: 1fr;
        }
        .work-section-intro {
            padding: 14px 16px;
        }
        .work-exchange-visual {
            padding: 16px;
            gap: 14px;
        }
        .work-exchange-to span {
            font-size: 26px;
        }
    }
</style>
