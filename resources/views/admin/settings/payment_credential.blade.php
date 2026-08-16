<div id="paymentCredentialSettings" class="settings-section">
    <div class="section-header-bar">
        <div class="section-header-icon"><i class="fas fa-credit-card"></i></div>
        <div class="section-header-text">
            <h3>{{ __('Payment') }}</h3>
            <p>{{ __('Configure payment gateways and credential settings') }}</p>
        </div>
    </div>

    <div class="py-payments-grid">
        @foreach ($paymentCoins as $coin)
            <form class="py-form-reset" action="{{ route('admin.app.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="current_tab" value="">
                <div class="py-gateway-card">
                    {{-- Card Header --}}
                    <div class="py-gateway-header">
                        <div class="py-gateway-avatar">
                            <img src="{{ $coin->photo ? getImagePath($coin->photo) : asset('images/dollar.jpg') }}"
                                 alt="{{ $coin->title }}">
                        </div>
                        <div class="py-gateway-title">
                            <h5>{{ __('admin.' . $coin->title) }}</h5>
                            <span>{{ __('Payment Gateway') }}</span>
                        </div>
                        <div class="py-gateway-toggle">
                            <input type="hidden" name="is_{{ $coin->type }}_active" value="0">
                            <input type="hidden" name="payment_getaway_id" value="{{ $coin->id }}">
                            <input type="checkbox" id="{{ $coin->type }}Radio"
                                   class="custom-payment-radio libraryRealTime"
                                   name="is_{{ $coin->type }}_active" value="1"
                                {{ $coin->status == 1 && @$settings['is_' . $coin->type . '_active'] == '1' ? 'checked' : '' }}>
                            <label for="{{ $coin->type }}Radio" class="switch"></label>
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="py-gateway-body">
                        <div class="row">
                            {{-- One dynamic include per gateway. The partial name equals the
                                 gateway type, except 'strip' whose partial is 'stripe'. --}}
                            @php $partial = $coin->type === 'strip' ? 'stripe' : $coin->type; @endphp
                            @includeIf('admin.settings.partial_payments.' . $partial)
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="py-gateway-footer">
                        <button type="submit" class="btn py-btn-save">
                            <i class="fas fa-save"></i> {{ __('save') }}
                        </button>
                    </div>
                </div>
            </form>
        @endforeach
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     SCOPED STYLES
═══════════════════════════════════════════════════════════════ --}}
<style>
    /* ── Form Reset ────────────────────────────────────────────── */
    .py-form-reset {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: auto !important;
    }

    /* ── Payments Grid ─────────────────────────────────────────── */
    .py-payments-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 20px;
        align-items: start;
    }

    /* ── Gateway Card ──────────────────────────────────────────── */
    .py-gateway-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    .dark-mode .py-gateway-card {
        background: #1e293b;
        border-color: rgba(255,255,255,0.06);
    }
    .py-gateway-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .dark-mode .py-gateway-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    }

    /* ── Gateway Header ────────────────────────────────────────── */
    .py-gateway-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 20px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark-mode .py-gateway-header {
        border-bottom-color: rgba(255,255,255,0.06);
    }
    .py-gateway-avatar {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        overflow: hidden;
        flex-shrink: 0;
        border: 2px solid #e2e8f0;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .dark-mode .py-gateway-avatar {
        border-color: rgba(255,255,255,0.08);
        background: #0f172a;
    }
    .py-gateway-avatar img {
        width: 100% !important;
        height: 100% !important;
        object-fit: contain !important;
        display: block !important;
        border-radius: 0 !important;
    }
    .py-gateway-title {
        flex: 1;
        min-width: 0;
    }
    .py-gateway-title h5 {
        margin: 0 0 2px 0;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        text-transform: capitalize;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dark-mode .py-gateway-title h5 {
        color: #f1f5f9;
    }
    .py-gateway-title span {
        font-size: 12px;
        color: #94a3b8;
    }
    .py-gateway-toggle {
        flex-shrink: 0;
    }

    /* ── Gateway Body ──────────────────────────────────────────── */
    .py-gateway-body {
        padding: 20px;
        flex: 1;
    }
    .py-gateway-body .form-group {
        margin-bottom: 14px;
    }
    .py-gateway-body label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 6px;
    }
    .dark-mode .py-gateway-body label {
        color: #94a3b8;
    }
    .py-gateway-body .form-control {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: 10px 14px !important;
        font-size: 13px !important;
        font-weight: 500;
        transition: all 0.3s ease;
        background: #f8fafc !important;
    }
    .dark-mode .py-gateway-body .form-control {
        background: #0f172a !important;
        border-color: rgba(255,255,255,0.08) !important;
        color: #e2e8f0 !important;
    }
    .py-gateway-body .form-control:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-soft) !important;
        outline: none;
    }
    .py-gateway-body select.form-control {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 36px !important;
        cursor: pointer;
    }

    /* ── Gateway Footer ────────────────────────────────────────── */
    .py-gateway-footer {
        padding: 14px 20px;
        border-top: 1px solid #f1f5f9;
    }
    .dark-mode .py-gateway-footer {
        border-top-color: rgba(255,255,255,0.06);
    }
    .py-btn-save {
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
    .py-btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px var(--accent-soft);
        background: linear-gradient(135deg, var(--accent-strong), var(--accent)) !important;
    }
    .py-btn-save:active {
        transform: translateY(0);
    }

    /* ── Responsive ────────────────────────────────────────────── */
    @media (max-width: 992px) {
        .py-payments-grid {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 480px) {
        .py-gateway-header {
            padding: 16px;
        }
        .py-gateway-body {
            padding: 16px;
        }
        .py-gateway-avatar {
            width: 44px;
            height: 44px;
        }
    }
</style>
