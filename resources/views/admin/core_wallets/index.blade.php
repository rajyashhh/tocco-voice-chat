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

    /* Wallet Cards */
    .wallets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 25px;
    }

    .wallet-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        transition: all 0.4s ease;
        position: relative;
    }

    .dark-mode .wallet-card {
        background: var(--dark-secondry-color);
    }

    .wallet-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .wallet-header {
        padding: 30px;
        background: var(--gradient-primary);
        color: white;
        position: relative;
        overflow: hidden;
    }

    .wallet-header::before {
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

    .wallet-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        position: relative;
        z-index: 1;
    }

    .wallet-name {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 15px;
        position: relative;
        z-index: 1;
    }

    .wallet-balance {
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 8px;
        position: relative;
        z-index: 1;
    }

    .wallet-label {
        font-size: 13px;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }

    .wallet-body {
        padding: 25px 30px;
    }

    .wallet-info {
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

    .wallet-actions {
        display: flex;
        gap: 12px;
    }

    .btn-wallet {
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

    .btn-wallet:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--secondary-color);
        text-decoration: none;
    }

    .dark-mode .btn-wallet:hover {
        box-shadow: 0 6px 20px var(--dark-secondry-color);
    }

    .wallet-badge {
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

    .rtl .wallet-badge {
        left: 15px;
        right: auto;
    }

    .wallet-update-time {
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
        .wallets-grid {
            grid-template-columns: 1fr;
        }

        .stats-overview {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid" style="padding: 0;">
    <!-- Page Header -->
{{--    <div class="page-header">--}}
{{--        <div class="container">--}}
{{--            <div class="breadcrumb-custom">--}}
{{--                {{ __('Core Wallets') }} / <span>{{ __('Home') }}</span>--}}
{{--            </div>--}}
{{--            <h1 class="page-title">--}}
{{--                💼 {{ __('Application wallet') }}--}}
{{--            </h1>--}}
{{--        </div>--}}
{{--    </div>--}}

    <div class="container">
        <!-- Stats Overview -->
        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-label">{{ __('Total Balance') }}</div>
                <div class="stat-value">{{ formatLargeNumber($coreWallets->sum('coins')) }}</div>
                <div class="stat-change">↑ {{ __('Average of all wallets') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ __('Active Wallets Count') }}</div>
                <div class="stat-value">{{ $coreWallets->count() }}</div>
                <div class="stat-change">↑ {{ __('100% Active') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ __('Average Balance') }}</div>
                <div class="stat-value">{{ formatLargeNumber($coreWallets->avg('coins')) }}</div>
                <div class="stat-change">{{ __('Per wallet') }}</div>
            </div>
        </div>

        <!-- Wallets Grid -->
        <div class="wallets-grid">
            @foreach ($coreWallets as $wallet)
                @php
                    $icon = $icons[$wallet->name] ?? 'fa-solid fa-wallet';
                @endphp
                <div class="wallet-card">
                    <div class="wallet-header {{ $wallet->is_negative ? 'negative' : '' }}">
                        @if($loop->first)
                            <div class="wallet-badge">{{ __('Main Wallet') }}</div>
                        @endif
                        <div class="wallet-icon">
                            {{ $icon }}
                        </div>
                        <div class="wallet-name">
                            {{ __(ucfirst(str_replace('_', ' ', $wallet->name))) }}
                        </div>
                        <div class="wallet-balance">
                            {{ number_format($wallet->coins) }}
                        </div>
                        <div class="wallet-label">{{ __('Coins') }}</div>
                        <div class="wallet-update-time">
                            {{ $wallet->update_for_human }}
                        </div>
                    </div>
                    <div class="wallet-body">
                        <div class="wallet-info">
                            <div class="info-item">
                                <span class="info-label">{{ __('Last Update') }}</span>
                                <span class="info-value">{{ $wallet->update_for_human }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">{{ __('Status') }}</span>
                                <span class="info-value"
                                      style="color: {{ $wallet->is_negative ? '#ef4444' : '#22c55e' }} !important;">
                                    ● {{ $wallet->is_negative ? __('Negative') : __('Active') }}
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">{{ __('Type') }}</span>
                                <span class="info-value">{{ __('Core') }}</span>
                            </div>
                        </div>
                        @if($canTransfer)
                            <div class="wallet-actions">
                                <button type="button"
                                        class="btn-wallet"
                                        data-toggle="modal"
                                        data-target="#transferModal"
                                        onclick="prepareTransferModal({{ $wallet->id }}, '{{ __(ucfirst(str_replace('_', ' ', $wallet->name))) }}')">
                                    <i class="fas fa-arrow-right-arrow-left"></i> {{ __('Transfer') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Transfer Modal -->
<div class="modal fade" id="transferModal" tabindex="-1" role="dialog" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="walletTransferForm" method="POST" action="{{ route('admin.wallet.transfer.submit') }}">
            @csrf
            <input type="hidden" name="from_wallet_id" id="from_wallet_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transferModalLabel">
                        {{ __('Transfer from') }} <span id="walletName"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="to_wallet_id" class="form-label font-weight-bold">{{ __('To Wallet') }}</label>
                        <select class="form-control" name="to_wallet_id" id="to_wallet_id" required>
                            @foreach ($coreWallets as $wallet)
                                <option value="{{ $wallet->id }}">
                                    {{ __(ucfirst(str_replace('_', ' ', $wallet->name))) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label font-weight-bold">{{ __('Amount') }}</label>
                        <input type="number" name="amount" class="form-control" min="1" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> {{ __('Execute Transfer') }}
                    </button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                        <i class="fas fa-times"></i> {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container"></div>

<!-- JavaScript -->
<script>
    // Translation strings for JavaScript
    const translations = {
        transferSuccess: @json(__('Transfer completed successfully')),
        transferFailed: @json(__('Transfer failed')),
        serverError: @json(__('Server connection error'))
    };

    // Toast notification function
    function showToast(message, type = 'success') {
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `alert alert-${type}`;
        toast.innerText = message;
        toast.style.marginBottom = '10px';
        document.getElementById('toast-container').appendChild(toast);

        setTimeout(() => {
            document.getElementById(toastId)?.remove();
        }, 3000);
    }

    // Prepare transfer modal
    function prepareTransferModal(fromId, fromName) {
        document.getElementById('from_wallet_id').value = fromId;
        document.getElementById('walletName').innerText = fromName;

        const select = document.getElementById('to_wallet_id');
        for (let option of select.options) {
            option.style.display = option.value == fromId.toString() ? 'none' : 'block';
        }
    }

    // Submit wallet transfer
    async function submitWalletTransfer(event) {
        event.preventDefault();

        const form = document.getElementById('walletTransferForm');
        const from_wallet_id = document.getElementById('from_wallet_id').value;
        const to_wallet_id = document.getElementById('to_wallet_id').value;
        const amount = form.amount.value;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    from_wallet_id,
                    to_wallet_id,
                    amount
                })
            });

            const data = await response.json();

            if (response.ok && data.status === 1) {
                showToast(data.message || translations.transferSuccess + ' ✅', 'success');
                $('#transferModal').modal('hide');
                form.reset();
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast(data.message || translations.transferFailed + ' ❌', 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast(translations.serverError + ' ❌', 'danger');
        }
    }

    // Event listener
    document.getElementById('walletTransferForm').addEventListener('submit', submitWalletTransfer);
</script>
