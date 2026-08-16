@php
    $translated = trans('current_balance');
@endphp
<style>
    .transferModal{
        width: 600px;
        height: 294px;
        background-color;:var(--box-background-color)
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
        margin: 66px 79px 4px 3px;

    }
    .icon_trans{
        font-size: 15px;
        margin: 0px 11px;
    }

</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="card bg-successs" style="
    background: var(--secondary-color);
padding: 20px; font-size: 20px; text-align: center; width: 469px; margin: 42px auto; height: auto;">
    <strong style="color: white;">{{ $translated }}: </strong>
    <span style="color: white;">{{ $finalSalary }} 💰</span>

    <div style="margin-top: 15px;">

        <button onclick="openTransferModal()" class="btn btn-light btn-sm">
            {{ __('transfer_to_wallet') }}
        </button>
       <span class="icon_trans"> <i class="fas fa-exchange-alt"></i></span>

    </div>
</div>

<div id="transferModal" class="transferModal" style="display: none; position: fixed; top: 30%; left: 50%; transform: translate(-50%, -30%);
    background: white; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,.5); z-index: 9999;">
                 <div class="modal-header" style="background-color: var(--primary-color); color: var(--text-secondary-color);">
                    <h5 class="modal-title" id="modalDescriptionTitle"></h5>
                    <button type="button" class="close" data-dismiss="modal" style="color: var(--text-secondary-color);">&times;</button>
                </div>
   
   <form id="transferForm" class="transferForm" method="POST" action="{{ route('bd.salary.transfer') }}">
        @csrf
        <input type="hidden" name="bd_id" value="{{ Auth::id() }}">

           <div class="form-group">
                <label for="amount">
                    {{ __('enter_amount') }}
                   
                </label>
                <input type="number" name="amount" id="amount" class="form-control amount-input" required max="{{ $finalSalary }}" step="0.01">
                <span 
                        data-toggle="tooltip" 
                        title="{{ __('bd_wallet_notice') }}"
                        style="    cursor: pointer;
                        color: #007bff;
                        position: absolute;
                        top: 121px;
                        left: 77px;
                        border: 1px solid;
                        border-radius: 13px;"
                             >
                        &#x2757;
                    </span>
            </div>


        <div class="text-right mt-3 actions">
            <button type="submit" class="btn btn-success">{{ __('confirm_transfer') }}</button>
            <button type="button" class="btn btn-secondary" onclick="closeTransferModal()">{{ __('Cancel') }}</button>
        </div>
    </form>
</div>

{{-- Overlay --}}
<div id="modalOverlay" onclick="closeTransferModal()" style="display: none; position: fixed; top: 0; left: 0; width: 100%;
    height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9998;"></div>

{{-- JS --}}
<script>

    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
    function openTransferModal() {
        document.getElementById('transferModal').style.display = 'block';
        document.getElementById('modalOverlay').style.display = 'block';
    }

    function closeTransferModal() {
        document.getElementById('transferModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    }
</script>
