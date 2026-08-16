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
        height: 1000px;
        background-color:var(--box-background-color);
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
        padding: 25px 9px 14px 16px;
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

.transferForm .actions {
    margin: 10px 73px 4px 3px;
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
    background-color:var(--box-background-color);


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
#chargeModal::-webkit-scrollbar {
    width: 8px;
}
#chargeModal::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
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
{{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> --}}
<!-- Select2 JS -->
{{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}

<!-- <div class="card bg-primary" style="
   background: var(--secondary-color);
padding: 20px; color: ; font-size: 20px; text-align: center; width: 500px; margin: 42px auto;">

    <div style="margin-top: 15px;">
    <span class="icon_trans"> <i class="fas fa-exchange-alt"></i></span>

        <button onclick="openChargeModal()" class="btn btn-light btn-sm">
            {{ __('charge_wallet') }}
        </button>
        <strong>{{ $translated }}: </strong> {{ $finalSalary }} 🪙

    </div>
</div> -->


<div class="card-visa">
    <div class="card-content">
        <span class="icon_trans"><i class="fas fa-exchange-alt"></i></span>
        @if (\Encore\Admin\Facades\Admin::user()->can('add-switch-coin-recharge') || \Encore\Admin\Facades\Admin::user()->can('*'))
        <button onclick="openChargeModal()" class="btn btn-info btn-sm">
            {{ __('Charge') }}
        </button>
        @endif
        <strong>{{ $translated }}: </strong> {{ $finalSalary }} 🪙
    </div>
</div>

<div id="chargeModal" class="transferModal large">
      <div class="modal-header" style="background-color: var(--primary-color); color: var(--text-secondary-color);">
          <h5 class="modal-title" id="modalDescriptionTitle"></h5>
          <button type="button" class="close" data-dismiss="modal" onclick="closeChargeModal()" style="color: var(--text-secondary-color);">&times;</button>
      </div>
    <form id="chargeForm" class="transferForm" method="POST" action="{{ route('areaManager.wallet.charge') }}">
    @csrf

    <div class="form-group">
        <label for="target_type">{{ __('select_target') }}</label>
        <select id="target_type" name="target_type" class="form-control" required onchange="toggleTargetFields()">
            <option value="">{{ __('select') }}</option>
            @if (auth('admin')->user()->type == "area-manager")
            <option value="subAreaManager">{{ __('sub area manager') }}</option>
            <option value="superAdmin">{{ __('super admin') }}</option>
            @elseif(auth('admin')->user()->type == "sub_area_manager")
                <option value="superAdmin">{{ __('super admin') }}</option>
            @endif
            <option value="agency">{{ __('Shipping agency') }}</option>
        </select>
    </div>

    <div id="target_fields" style="display: none;">
        <div class="form-group position-relative">
            <label for="target_id_search">{{ __('receiver') }}</label>
            <select id="target_id" name="target_id" class="form-control" style="width: 100%;" required></select>
        </div>

        <div class="form-group">
            <label for="charge_type">{{ __('Charge by') }}</label>
            <select id="charge_type" name="charge_type" class="form-control" required onchange="updateChargeInputLabel()">
                <option value="dollar">{{ __('Dollar') }}💲</option>
                <option value="coins">{{ __('Coins') }}🪙</option>
            </select>
        </div>

        @php
                $rate = App\Helpers\Common::getCoinsValue('shipping_coins');
                $areaManagerRate = App\Helpers\Common::getCoinsValue('zones_coins');
                $superAdminRate = App\Helpers\Common::getCoinsValue('super_admin_coins');
            @endphp

            <div class="form-group">
                <label for="amount">{{ __('enter_amount') }} 💲</label>
                <input type="number"
                    name="amount"
                    id="amount"
                    class="form-control"
                    required
                    step="0.01"
                    min="0.01"
                    oninput="updateConvertedAmount()">

                <small class="form-text" style="color: #6c757d; font-style: italic;">
                    {{ __('Now charge by dollar') }}
                </small>

                <small id="convertedAmount"
                    class="form-text"
                    style="color: #007bff; font-weight: bold; display: none;">
                </small>
            </div>

        <div class="text-right mt-1 actions">
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
<script src="/vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>



<script>


    const RATES = {
        agency: {{ $rate ?? 1 }},
        subAreaManager: {{ $areaManagerRate ?? 1 }},
        superAdmin: {{ $superAdminRate ?? 1 }}
    };

    function updateConvertedAmount() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const targetType = document.getElementById('target_type').value;
        const chargeType = document.getElementById('charge_type').value;
        const rate = RATES[targetType] || 1; // fallback rate
        const output = document.getElementById('convertedAmount');

        if (amount > 0 && targetType) {
            output.style.display = 'block';

            if (chargeType === 'dollar') {
                const result = amount * rate;
                output.textContent = `= ${result.toFixed(2)} 🪙 (${rate} coins per $1)`;
            } else {
                const result = amount / rate;
                output.textContent = `≈ ${result.toFixed(2)} 💲 (1 coin = ${(1 / rate).toFixed(4)} $)`;
            }
        } else {
            output.style.display = 'none';
        }
    }

    function updateChargeInputLabel() {
        const type = document.getElementById('charge_type').value;
        const amountLabel = document.querySelector('label[for="amount"]');
        const hint = document.querySelector('.form-text');
        document.getElementById('convertedAmount').style.display = 'none';

        if (type === 'dollar') {
            amountLabel.innerHTML = "{{ __('enter_amount') }} 💲";
            hint.textContent = "{{ __('Now charge by dollar') }}";
        } else {
            amountLabel.innerHTML = "{{ __('enter_amount') }} 🪙";
            hint.textContent = "{{ __('Now charge by coins') }}";
        }

        document.getElementById('amount').value = '';
    }

    function openChargeModal() {
        document.getElementById('chargeModal').style.display = 'block';
        document.getElementById('chargeOverlay').style.display = 'block';

        initSelect2();
    }

    function closeChargeModal() {
        document.getElementById('chargeModal').style.display = 'none';
        document.getElementById('chargeOverlay').style.display = 'none';
    }

    function toggleTargetFields() {
    const type = document.getElementById('target_type').value;
    const fields = document.getElementById('target_fields');
    fields.style.display = type ? 'block' : 'none';

        updateConvertedAmount();

        if (type) {
        const $select = $('#target_id');
        if ($select.data('select2')) {
            $select.select2('destroy');
        }
        initSelect2(type);
    }
}

const AUTH_COUNTRY_ID = "{{ Auth::user()->country_id }}";

function initSelect2(targetType = null) {
    if (!targetType) {
        targetType = document.getElementById('target_type').value;
    }

    let ajaxUrl = '';
    let placeholderText = '';

    if (targetType === 'subAreaManager') {
        ajaxUrl = '/areaManager/sub-area-managers';
        placeholderText = '{{ __("Select sub area manager") }}';
    } else if (targetType === 'superAdmin') {
        ajaxUrl = '/api/search/users-superadmin2';
        placeholderText = '{{ __("Select super admin") }}';
    } else if (targetType === 'agency') {
        ajaxUrl = '/api/search/superadmin-agencies';
        placeholderText = '{{ __("Select agency") }}';
    }

    const $select = $('#target_id');

    $select.select2({
        dropdownParent: $('#chargeModal'),
        placeholder: placeholderText,
        allowClear: true,

        ajax: {
            url: ajaxUrl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    page: params.page || 1,
                    country_id: AUTH_COUNTRY_ID
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                const items = Array.isArray(data)
                            ? (data[0]?.data || [])
                            : (data.data || []);

                return {
                    results: items.map(item => {
                        let displayName = targetType === 'subAreaManager'
                            ? (item.username || item.name || `ID: ${item.id}`)
                            : (item.name || item.username || `ID: ${item.id}`);

                        if (item.status) {
                            displayName += ` (${item.status})`;
                        }

                        return {
                            id: item.id,
                            text: displayName
                        };
                    }),

                    pagination: {
                        more: (params.page * 10) < (data.total || 0)
                    }
                };
            },
            cache: true
        }
    });
}

</script>
