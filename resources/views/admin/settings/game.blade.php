<div id="gamesSettings" class="settings-section">
    <div class="section-header-bar">
        <div class="section-header-icon"><i class="fas fa-gamepad"></i></div>
        <div class="section-header-text">
            <h3>{{ __('Games') }}</h3>
            <p>{{ __('Configure game providers and gaming settings') }}</p>
        </div>
    </div>

    <div class="gm-providers-grid">

        {{-- ══════════════════════════════════════════════════════════
             UTD GAMES — third-party games aggregator on the leader-cc-game
             protocol, with its OWN app_key. The callback middleware accepts the
             active provider's key that matches the request signature.
        ══════════════════════════════════════════════════════════ --}}
        <form class="gm-form-reset" action="{{ url('admin/game-provider-setting') }}" method="POST">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <input type="hidden" name="provider_code" value="utd">
            <input type="hidden" name="provider_name" value="UTD Game">
            <div class="gm-provider-card">
                <div class="gm-provider-header" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    <div class="gm-provider-logo"><i class="fas fa-dice"></i></div>
                    <div class="gm-provider-title">
                        <h5>{{ __('UTD Game') }}</h5>
                        <span>{{ __('Game provider') }}</span>
                    </div>
                    <div class="gm-provider-toggle">
                        <input type="hidden" name="active" value="0">
                        <input type="checkbox" id="utdRadio" class="custom-payment-radio libraryRealTime"
                               name="active" value="1" {{ @$utdSettings?->is_active == 1 ? 'checked' : '' }}>
                        <label for="utdRadio" class="switch"></label>
                    </div>
                </div>
                <div class="gm-provider-body">
                    <div class="gm-input-group">
                        <label><i class="fas fa-key"></i> {{ __('app key (signature secret)') }}</label>
                        <input type="text" name="app_key" placeholder="app_key"
                               value="{{ $utdSettings->app_key ?? '' }}" class="form-control gm-input" required>
                        <small class="gm-input-hint">{{ __('The APP_KEY from UTD (Callback test step). Used for md5(orderedValues + APP_KEY) callback signatures.') }}</small>
                    </div>

                    <div class="gm-input-group">
                        <label><i class="fas fa-link"></i> {{ __('base url (launch URL)') }}</label>
                        <input type="text" name="base_url" placeholder="https://your-backend-domain.com"
                               value="{{ $utdSettings->base_url ?? '' }}" class="form-control gm-input">
                        <small class="gm-input-hint">{{ __('Optional for now — used later for the launch-flag / game launch.') }}</small>
                    </div>

                    <div class="gm-webhooks-section">
                        <div class="gm-webhooks-title"><i class="fas fa-link"></i> {{ __('Our callbacks (give these to UTD)') }}</div>
                        @foreach ([
                            'get_user_info'  => url('api/leader-cc-game/get-user-info'),
                            'change_balance' => url('api/leader-cc-game/change-balance'),
                            'make_up_orders' => url('api/leader-cc-game/make-up-orders'),
                        ] as $key => $endpointUrl)
                            <div class="gm-webhook-item">
                                <div class="gm-webhook-info">
                                    <span class="gm-webhook-name">{{ str_replace('_', ' ', $key) }}</span>
                                    <span class="gm-webhook-method">POST</span>
                                </div>
                                <div class="gm-webhook-url-wrap">
                                    <input type="text" class="form-control gm-input gm-input-readonly" value="{{ $endpointUrl }}" readonly>
                                    <button type="button" class="gm-copy-btn" onclick="gmCopyWebhook(this)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                        <small class="gm-input-hint">{{ __('Callback base URL to enter in UTD = your API host; UTD appends /api/leader-cc-game/... itself.') }}</small>
                    </div>
                </div>
                <div class="gm-provider-footer">
                    <button type="submit" class="btn gm-btn-save"><i class="fas fa-save"></i> {{ __('save') }}</button>
                </div>
            </div>
        </form>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     SCOPED STYLES
═══════════════════════════════════════════════════════════════ --}}
<style>
    .gm-form-reset {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: auto !important;
    }
    .gm-providers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 20px;
        align-items: start;
    }

    /* ── Card ──────────────────────────────────────────────────── */
    .gm-provider-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    .dark-mode .gm-provider-card {
        background: #1e293b;
        border-color: rgba(255,255,255,0.06);
    }
    .gm-provider-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .dark-mode .gm-provider-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    }

    /* ── Header ────────────────────────────────────────────────── */
    .gm-provider-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 20px;
        color: #fff;
    }
    .gm-provider-logo {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        backdrop-filter: blur(10px);
    }
    .gm-provider-title { flex: 1; }
    .gm-provider-title h5 {
        margin: 0 0 2px 0;
        font-size: 16px;
        font-weight: 700;
        color: #fff;
    }
    .gm-provider-title span {
        font-size: 12px;
        opacity: 0.85;
    }
    .gm-provider-toggle { flex-shrink: 0; }

    /* ── Body ──────────────────────────────────────────────────── */
    .gm-provider-body {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .gm-input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .gm-input-group label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
    }
    .dark-mode .gm-input-group label { color: #94a3b8; }
    .gm-input-group label i { font-size: 11px; opacity: 0.7; }
    .gm-input-hint {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 2px;
    }
    .gm-input {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: 10px 14px !important;
        font-size: 13px !important;
        font-weight: 500;
        transition: all 0.3s ease;
        background: #f8fafc !important;
    }
    .dark-mode .gm-input {
        background: #0f172a !important;
        border-color: rgba(255,255,255,0.08) !important;
        color: #e2e8f0 !important;
    }
    .gm-input:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-soft) !important;
        outline: none;
    }
    .gm-input-readonly {
        background: #f1f5f9 !important;
        cursor: not-allowed;
        color: #64748b !important;
        font-size: 12px !important;
    }
    .dark-mode .gm-input-readonly {
        background: rgba(255,255,255,0.04) !important;
        color: #94a3b8 !important;
    }

    /* ── Empty State ───────────────────────────────────────────── */
    .gm-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 30px 20px;
        color: #94a3b8;
    }
    .gm-empty-state i {
        font-size: 28px;
        opacity: 0.5;
    }
    .gm-empty-state span {
        font-size: 13px;
        font-weight: 500;
    }

    /* ── Webhooks ───────────────────────────────────────────────── */
    .gm-webhooks-section {
        margin-top: 6px;
        padding-top: 18px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .dark-mode .gm-webhooks-section { border-top-color: rgba(255,255,255,0.06); }
    .gm-webhooks-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dark-mode .gm-webhooks-title { color: #f1f5f9; }
    .gm-webhooks-title i { color: var(--accent); font-size: 13px; }
    .gm-webhook-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        transition: all 0.3s ease;
    }
    .dark-mode .gm-webhook-item {
        background: #0f172a;
        border-color: rgba(255,255,255,0.06);
    }
    .gm-webhook-item:hover { border-color: #cbd5e1; }
    .dark-mode .gm-webhook-item:hover { border-color: rgba(255,255,255,0.12); }
    .gm-webhook-info {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .gm-webhook-name {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        text-transform: capitalize;
    }
    .dark-mode .gm-webhook-name { color: #e2e8f0; }
    .gm-webhook-method {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
        background: rgba(16,185,129,0.1);
        color: #059669;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .dark-mode .gm-webhook-method {
        background: rgba(16,185,129,0.15);
        color: #34d399;
    }
    .gm-webhook-url-wrap {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .gm-webhook-url-wrap .gm-input { flex: 1; }
    .gm-copy-btn {
        width: 38px !important;
        height: 38px;
        border-radius: 10px !important;
        border: 1px solid #e2e8f0 !important;
        background: #fff !important;
        color: #64748b !important;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        flex-shrink: 0;
        padding: 0 !important;
    }
    .dark-mode .gm-copy-btn {
        background: #1e293b !important;
        border-color: rgba(255,255,255,0.08) !important;
        color: #94a3b8 !important;
    }
    .gm-copy-btn:hover {
        background: var(--accent) !important;
        border-color: var(--accent) !important;
        color: var(--accent-contrast) !important;
    }

    /* ── Footer ────────────────────────────────────────────────── */
    .gm-provider-footer {
        padding: 14px 20px;
        border-top: 1px solid #f1f5f9;
    }
    .dark-mode .gm-provider-footer { border-top-color: rgba(255,255,255,0.06); }
    .gm-btn-save {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        padding: 10px 24px !important;
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
    .gm-btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px var(--accent-soft);
        background: linear-gradient(135deg, var(--accent-strong), var(--accent)) !important;
    }

    /* ── Responsive ────────────────────────────────────────────── */
    @media (max-width: 992px) {
        .gm-providers-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
        .gm-provider-body { padding: 16px; }
        .gm-provider-header { padding: 14px 16px; }
    }
</style>

<script>
    function gmCopyWebhook(btn) {
        const input = btn.closest('.gm-webhook-url-wrap').querySelector('input');
        navigator.clipboard.writeText(input.value).then(() => {
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => { btn.innerHTML = originalHTML; }, 2000);
        }).catch(() => {
            const temp = document.createElement('input');
            temp.value = input.value;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => { btn.innerHTML = originalHTML; }, 2000);
        });
    }
</script>
