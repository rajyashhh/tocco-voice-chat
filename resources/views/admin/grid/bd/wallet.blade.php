@php
    $translated = trans('current_balance');
@endphp
<style>
    .wallet-card {
        padding: 20px;
        font-size: 20px;
        text-align: center;
        width: 100%;
        max-width: 500px;
        margin: 40px auto;
        border-radius: 10px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        position: relative;
    }

    .wallet-card-actions {
        margin-top: 15px;
    }

    .wallet-button {
        padding: 8px 16px;
        background-color: white;
        border: none;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }

    .wallet-button:hover {
        background-color: #e2e6ea;
    }

    .wallet-modal {
        display: none;
        position: fixed;
        top: 15%;
        left: 50%;
        transform: translate(-50%, 0);
        background: white;
        padding: 30px;
        border-radius: 12px;
        z-index: 9999;
        width: 90%;
        max-width: 450px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .wallet-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background-color: rgba(0,0,0,0.5);
        z-index: 9998;
    }

    .wallet-modal-content .form-group {
        margin-bottom: 15px;
    }

    .wallet-modal-content input,
    .wallet-modal-content select {
        width: 100%;
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    .wallet-modal-actions {
        text-align: right;
        margin-top: 20px;
    }

    .wallet-modal-actions .btn {
        margin-left: 10px;
        padding: 8px 14px;
        border-radius: 6px;
    }

    .transferModal{
        width: 600px;
        height: 332px;
        background-color;:var(--box-background-color);
        display: none;
        position: fixed;
        top: 20%;
        left: 50%;
        transform: translate(-50%, -20%);
        background: white;
        border-radius: 36px;
        z-index: 9999;
        width: 520px;
        overflow: hidden;
        height: 394px;

    }
    .transferForm{
        height: 100%;
        padding: 70px 9px 14px 16px;
    }
    .transferForm .form-group{
        width: 70%;
        margin: auto;
    }
    .transferForm .form-group .amount-input
    {
        margin: 20px 0px 4px 3px;

    }

    .transferForm .actions{
        margin: 42px 73px 4px 3px;


    }
    .transferForm .actions button{
        margin-left: 4px;
    }
    .icon_trans{
        font-size: 15px;
        margin: 0px 11px;
    }

    .card-content{
            color: black !important;
        }
    .card-visa {
        background:  #0d6efd; /* اللون الثانوي الافتراضي */
        padding: 20px;
        color: #fff; /* نص أبيض */
        font-size: 20px;
        text-align: center;
        width: 500px;
        margin: 42px auto;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        position: relative;
        overflow: hidden;
    }

    /* الأيقونة */
    .card-visa .icon_trans {
        font-size: 28px;
        margin-right: 10px;
        vertical-align: middle;
    }

    /* محتوى البطاقة */
    .card-visa .card-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .card-visa .btn {
        cursor: pointer;
    }



    /* === General Modal Styling === */

/* === Button Styling === */
.actions button {
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    margin-left: 10px;
}

.btn-success {
    background-color: #28a745;
    border: none;
    color: #fff;
}

.btn-secondary {
    background-color: #6c757d;
    border: none;
    color: #fff;
}

/* === Search Result List Styling === */
#searchResults {
    position: absolute;
    border: 1px solid #ddd;
    border-top: none;
    max-height: 240px;
    overflow-y: auto;
    width: 67%;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    border-radius: 0 0 10px 10px;
    z-index: 10000;
    background-color;:var(--box-background-color);


}

#searchResults .list-group-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid ;
    transition: background 0.2s;
}
#searchResults .list-group-item2 {
    padding: 10px 15px;
    cursor: pointer;
    border: none !important;
    transition: background 0.2s;
    color: black !important;

    background-color;:var(--box-background-color) !important;

}
#searchResults .list-group-item:last-child {
    border-bottom: none;
}

#searchResults .list-group-item:hover {
    /* background-color: #f1f1f1; */
}

#target_id_search {
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #ccc;
    width: 100%;
    font-size: 14px;
}

</style>
<link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600&display=swap" rel="stylesheet">

<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> -->
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- <div class="card bg-primary" style="
   background: var(--secondary-color);
padding: 20px; color: ; font-size: 20px; text-align: center; width: 500px; margin: 42px auto;">

    <div style="margin-top: 15px;">
    <span class="icon_trans"> <i class="fas fa-exchange-alt"></i></span>

        <button onclick="openChargeModal()" class="btn btn-info btn-sm">
            {{ __('charge_wallet') }}
        </button>
        <strong>{{ $translated }}: </strong> {{ $finalSalary }} 💰

    </div>
</div> -->


<div class="card-visa">
    <div class="card-content">
        <span class="icon_trans"><i class="fas fa-exchange-alt"></i></span>
        <button onclick="openChargeModal()" class="btn btn-info btn-sm">
            {{ __('Charge') }}
        </button>
        <strong>{{ $translated }}: </strong> {{ $finalSalary }} 💰
    </div>
</div>



{{-- Charge Modal --}}

<div id="chargeModal" class="transferModal" style="

    ">
      <div class="modal-header" style="background-color: var(--primary-color); color: var(--text-secondary-color);">
                    <h5 class="modal-title" id="modalDescriptionTitle"></h5>
                    <button type="button" class="close" data-dismiss="modal" onclick="closeChargeModal()" style="color: var(--text-secondary-color);">&times;</button>
                </div>
                <form id="chargeForm" class="transferForm" method="POST" action="{{ route('bd.wallet.charge') }}">
    @csrf

    <div class="form-group">
        <label for="target_type">{{ __('select_target') }}</label>
        <select id="target_type" name="target_type" class="form-control" required onchange="toggleTargetFields()">
            <option value="">{{ __('select') }}</option>
            <option value="user">{{ __('user') }}</option>
            <option value="agency">{{ __('Shipping agency') }}</option>
        </select>
    </div>

    <div id="target_fields" style="display: none;">
        <div class="form-group position-relative">
            <label for="target_id_search">{{ __('receiver') }}</label>
            <input type="text" id="target_id_search" class="form-control" placeholder="{{ __('Search') }}" oninput="searchTarget()" autocomplete="off">
            <input type="hidden" name="target_id" id="target_id" required>

            <div id="searchResults" class="list-group" style="
             background-color;:var(--box-background-color);
            position: absolute; z-index: 9999; width: 67%; display: none;"></div>
        </div>

        <div class="form-group">
            <label for="amount">{{ __('enter_amount') }}</label>
            <input type="number" name="amount" id="amount" class="form-control" required step="0.01" min="0.01">
        </div>

        <div class="text-right mt-3 actions">
            <button type="submit" class="btn btn-success">{{ __('confirm_charge') }}</button>
            <button type="button" class="btn btn-secondary" onclick="closeChargeModal()">{{ __('Cancel') }}</button>
        </div>
    </div>
</form>

</div>

{{-- Overlay --}}
<div id="chargeOverlay" onclick="closeChargeModal()" style="display: none; position: fixed; top: 0; left: 0;
    width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9998;"></div>

{{-- JS --}}
<script>
    function openChargeModal() {
        document.getElementById('chargeModal').style.display = 'block';
        document.getElementById('chargeOverlay').style.display = 'block';
    }

    function closeChargeModal() {
        document.getElementById('chargeModal').style.display = 'none';
        document.getElementById('chargeOverlay').style.display = 'none';
    }

    function toggleTargetFields() {
        const type = document.getElementById('target_type').value;
        const fields = document.getElementById('target_fields');
        fields.style.display = type ? 'block' : 'none';
    }

    let searchTimeout;

    function toggleTargetFields() {
        const type = document.getElementById('target_type').value;
        const fields = document.getElementById('target_fields');
        const inputSearch = document.getElementById('target_id_search');
        const hiddenInput = document.getElementById('target_id');

        fields.style.display = type ? 'block' : 'none';

        // Reset input values
        inputSearch.value = '';
        hiddenInput.value = '';
        document.getElementById('searchResults').style.display = 'none';
    }

    function searchTarget() {
        clearTimeout(searchTimeout);

        const query = document.getElementById('target_id_search').value;
        const targetType = document.getElementById('target_type').value;
        const resultsDiv = document.getElementById('searchResults');

        if (!query || !targetType) {
            resultsDiv.style.display = 'none';
            return;
        }

        let url = targetType === 'user' ? '/api/search/users2' : '/api/search/agencies';

        searchTimeout = setTimeout(() => {
            fetch(`${url}?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(response => {
                    const data = response.data;

                    console.log(data);

                    resultsDiv.innerHTML = '';
                    if (!data.length) {
                        resultsDiv.style.display = 'none';
                        return;
                    }

                    data.forEach(item => {

                        const div = document.createElement('div');
                        div.className = 'list-group-item list-group-item-action list-group-item2';
                        div.textContent = item.name ? `${item.name} (ID: ${item.id})` : `ID: ${item.id}`;
                        div.onclick = () => selectTarget(item);
                        resultsDiv.appendChild(div);
                    });

                    resultsDiv.style.display = 'block';
                });
        }, 300);
    }

    function selectTarget(item) {
        document.getElementById('target_id_search').value = item.name ? `${item.name} (ID: ${item.id})` : `ID: ${item.id}`;
        document.getElementById('target_id').value = item.id;
        document.getElementById('searchResults').style.display = 'none';
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#searchResults') && e.target.id !== 'target_id_search') {
            document.getElementById('searchResults').style.display = 'none';
        }
    });
</script>
