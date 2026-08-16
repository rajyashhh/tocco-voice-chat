<style>
    .ssa-card {
        background: #0d6efd;
        padding: 20px;
        color: #fff;
        font-size: 20px;
        text-align: center;
        max-width: 500px;
        margin: 32px auto;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    .ssa-modal {
        display: none;
        position: fixed;
        top: 20%;
        left: 50%;
        transform: translate(-50%, -20%);
        background: #fff;
        color: #000;
        border-radius: 16px;
        z-index: 9999;
        width: 460px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    }
    .ssa-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9998;
    }
    .ssa-modal .form-group { margin-bottom: 15px; }
    .ssa-modal input { width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
    .ssa-actions { text-align: right; margin-top: 18px; }
    .ssa-actions .btn { margin-left: 8px; }
    #ssaResults { position: absolute; z-index: 10000; width: 90%; display: none; background: #fff; border: 1px solid #ddd; max-height: 220px; overflow-y: auto; }
    #ssaResults .item { padding: 10px 12px; cursor: pointer; color: #000; }
    #ssaResults .item:hover { background: #f1f1f1; }
</style>

<div class="ssa-card">
    <button onclick="ssaOpen()" class="btn btn-info btn-sm">{{ __('Charge') }}</button>
    <strong>{{ __('Coins Balance') }}:</strong> {{ number_format($coins) }} 💎
</div>

<div id="ssaModal" class="ssa-modal">
    <h5>{{ __('Charge') }} — {{ __('Shipping agency') }}</h5>
    <form id="ssaForm" method="POST" action="{{ route('shippingAdmin.wallet.charge') }}">
        @csrf
        <input type="hidden" name="operation_uuid" id="ssa_uuid">

        <div class="form-group" style="position: relative;">
            <label for="ssa_search">{{ __('receiver') }}</label>
            <input type="text" id="ssa_search" placeholder="{{ __('Search') }}" oninput="ssaSearch()" autocomplete="off">
            <input type="hidden" name="target_id" id="ssa_target_id" required>
            <div id="ssaResults"></div>
        </div>

        <div class="form-group">
            <label for="ssa_amount">{{ __('enter_amount') }}</label>
            <input type="number" name="amount" id="ssa_amount" required step="1" min="1">
        </div>

        <div class="ssa-actions">
            <button type="submit" class="btn btn-success">{{ __('confirm_charge') }}</button>
            <button type="button" class="btn btn-secondary" onclick="ssaClose()">{{ __('Cancel') }}</button>
        </div>
    </form>
</div>

<div id="ssaOverlay" class="ssa-overlay" onclick="ssaClose()"></div>

<script>
    function ssaUuid() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        return 'ssa-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }
    function ssaOpen() {
        // A fresh idempotency key per modal open; a double-submit of the same
        // form reuses it, so a retry is a no-op server-side.
        document.getElementById('ssa_uuid').value = ssaUuid();
        document.getElementById('ssaModal').style.display = 'block';
        document.getElementById('ssaOverlay').style.display = 'block';
    }
    function ssaClose() {
        document.getElementById('ssaModal').style.display = 'none';
        document.getElementById('ssaOverlay').style.display = 'none';
    }
    let ssaTimer;
    function ssaSearch() {
        clearTimeout(ssaTimer);
        const q = document.getElementById('ssa_search').value;
        const box = document.getElementById('ssaResults');
        if (!q) { box.style.display = 'none'; return; }
        ssaTimer = setTimeout(() => {
            fetch('/api/search/agencies?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(res => {
                    const data = res.data || [];
                    box.innerHTML = '';
                    if (!data.length) { box.style.display = 'none'; return; }
                    data.forEach(item => {
                        const d = document.createElement('div');
                        d.className = 'item';
                        d.textContent = item.name ? `${item.name} (ID: ${item.id})` : `ID: ${item.id}`;
                        d.onclick = () => {
                            document.getElementById('ssa_search').value = d.textContent;
                            document.getElementById('ssa_target_id').value = item.id;
                            box.style.display = 'none';
                        };
                        box.appendChild(d);
                    });
                    box.style.display = 'block';
                });
        }, 300);
    }
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#ssaResults') && e.target.id !== 'ssa_search') {
            document.getElementById('ssaResults').style.display = 'none';
        }
    });
</script>