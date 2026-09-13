<div class="modal fade" id="mainAdminFundModal" tabindex="-1" role="dialog" aria-labelledby="mainAdminFundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 10px; overflow: hidden; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e88e5 0%, #1565c0 100%); color: #fff; padding: 15px 20px;">
                <h4 class="modal-title" id="mainAdminFundModalLabel" style="font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class="fa fa-money"></i> {{ __('Fund Agency Coins') }}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 0.8; font-size: 24px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="mainAdminFundForm" onsubmit="return false;">
                <div class="modal-body" style="padding: 20px;">
                    <input type="hidden" id="fund_agency_id" name="agency_id" value="">
                    <input type="hidden" id="fund_operation_uuid" name="operation_uuid" value="">

                    <div id="fund_error_alert" class="alert alert-danger" style="display: none; border-radius: 6px; font-size: 13px;"></div>

                    <div class="form-group">
                        <label for="fund_agency_name" style="font-weight: 600; font-size: 13px;">{{ __('Agency') }}</label>
                        <input type="text" id="fund_agency_name" class="form-control" readonly style="background-color: #f8f9fa; cursor: not-allowed; font-weight: 600;">
                    </div>

                    <div class="form-group">
                        <label for="fund_agency_current_coins" style="font-weight: 600; font-size: 13px;">{{ __('Current coin balance') }}</label>
                        <div class="input-group">
                            <input type="text" id="fund_agency_current_coins" class="form-control" readonly style="background-color: #f8f9fa; cursor: not-allowed; font-weight: 600;">
                            <span class="input-group-addon" style="background-color: #eee;">🪙</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fund_amount" style="font-weight: 600; font-size: 13px;">
                            {{ __('Amount') }} <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" id="fund_amount" name="amount" class="form-control" min="1" step="1" placeholder="{{ __('Enter coin amount') }}" required style="border-radius: 4px;">
                            <span class="input-group-addon" style="background-color: #eee;">🪙</span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="fund_reason" style="font-weight: 600; font-size: 13px;">{{ __('Reason') }} ({{ __('Optional') }})</label>
                        <textarea id="fund_reason" name="reason" class="form-control" rows="2" placeholder="{{ __('Enter optional reason / reference') }}" style="border-radius: 4px; resize: vertical;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef; padding: 12px 20px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 4px; padding: 6px 16px;">
                        {{ __('admin.cancel') }}
                    </button>
                    <button type="button" id="fund_submit_btn" class="btn btn-success" style="border-radius: 4px; padding: 6px 20px; font-weight: 600;">
                        <i class="fa fa-check"></i> {{ __('admin.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="mainAdminRemoveModal" tabindex="-1" role="dialog" aria-labelledby="mainAdminRemoveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 10px; overflow: hidden; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #e53935 0%, #c62828 100%); color: #fff; padding: 15px 20px;">
                <h4 class="modal-title" id="mainAdminRemoveModalLabel" style="font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class="fa fa-minus-circle"></i> {{ __('Remove Agency Coins') }}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 0.8; font-size: 24px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="mainAdminRemoveForm" onsubmit="return false;">
                <div class="modal-body" style="padding: 20px;">
                    <input type="hidden" id="remove_agency_id" name="agency_id" value="">
                    <input type="hidden" id="remove_operation_uuid" name="operation_uuid" value="">
                    <input type="hidden" id="remove_agency_coins_raw" value="0">

                    <div id="remove_error_alert" class="alert alert-danger" style="display: none; border-radius: 6px; font-size: 13px;"></div>

                    <div class="form-group">
                        <label for="remove_agency_name" style="font-weight: 600; font-size: 13px;">{{ __('Agency') }}</label>
                        <input type="text" id="remove_agency_name" class="form-control" readonly style="background-color: #f8f9fa; cursor: not-allowed; font-weight: 600;">
                    </div>

                    <div class="form-group">
                        <label for="remove_agency_current_coins" style="font-weight: 600; font-size: 13px;">{{ __('Current coin balance') }}</label>
                        <div class="input-group">
                            <input type="text" id="remove_agency_current_coins" class="form-control" readonly style="background-color: #f8f9fa; cursor: not-allowed; font-weight: 600;">
                            <span class="input-group-addon" style="background-color: #eee;">🪙</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="remove_amount" style="font-weight: 600; font-size: 13px;">
                            {{ __('Amount to remove') }} <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" id="remove_amount" name="amount" class="form-control" min="1" step="1" placeholder="{{ __('Enter coin amount to remove') }}" required style="border-radius: 4px;">
                            <span class="input-group-addon" style="background-color: #eee;">🪙</span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="remove_reason" style="font-weight: 600; font-size: 13px;">{{ __('Reason') }} ({{ __('Optional') }})</label>
                        <textarea id="remove_reason" name="reason" class="form-control" rows="2" placeholder="{{ __('Enter optional reason / reference') }}" style="border-radius: 4px; resize: vertical;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef; padding: 12px 20px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 4px; padding: 6px 16px;">
                        {{ __('admin.cancel') }}
                    </button>
                    <button type="button" id="remove_submit_btn" class="btn btn-danger" style="border-radius: 4px; padding: 6px 20px; font-weight: 600;">
                        <i class="fa fa-minus-circle"></i> {{ __('Remove Coins') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {
    function generateUUID() {
        if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
            return crypto.randomUUID();
        }
        return 'main-' + Date.now() + '-' + Math.random().toString(36).substring(2, 11);
    }

    // --- FUND COINS ---
    $(document).off('click', '.fund-agency-coins-btn').on('click', '.fund-agency-coins-btn', function (e) {
        e.preventDefault();
        var agencyId = $(this).data('id');
        var agencyName = $(this).data('name');
        var currentCoins = $(this).data('coins');

        $('#fund_agency_id').val(agencyId);
        $('#fund_agency_name').val(agencyName);
        $('#fund_agency_current_coins').val(Number(currentCoins).toLocaleString());
        $('#fund_operation_uuid').val(generateUUID());
        $('#fund_amount').val('');
        $('#fund_reason').val('');
        $('#fund_error_alert').hide().text('');

        var $submitBtn = $('#fund_submit_btn');
        $submitBtn.prop('disabled', false).html('<i class="fa fa-check"></i> ' + '{{ __("admin.submit") }}');

        $('#mainAdminFundModal').modal('show');
        setTimeout(function () {
            $('#fund_amount').focus();
        }, 500);
    });

    $(document).off('click', '#fund_submit_btn').on('click', '#fund_submit_btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var agencyId = $('#fund_agency_id').val();
        var operationUuid = $('#fund_operation_uuid').val();
        var amount = parseInt($('#fund_amount').val(), 10);
        var reason = $('#fund_reason').val();

        if (!amount || isNaN(amount) || amount <= 0) {
            $('#fund_error_alert').text('{{ __("Amount must be greater than 0") }}').show();
            $('#fund_amount').focus();
            return;
        }

        $('#fund_error_alert').hide().text('');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + '{{ __("admin.loading") }}');

        var token = (typeof LA !== 'undefined' && LA.token) ? LA.token : $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: '{{ admin_url("charge-agencies/fund") }}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: token,
                agency_id: agencyId,
                amount: amount,
                operation_uuid: operationUuid,
                reason: reason
            },
            success: function (res) {
                $('#mainAdminFundModal').modal('hide');
                if (typeof toastr !== 'undefined') {
                    toastr.success(res.message || '{{ __("Charged successfully") }}');
                } else if (typeof swal !== 'undefined') {
                    swal('{{ __("admin.successful") }}', res.message || '{{ __("Charged successfully") }}', 'success');
                } else {
                    alert(res.message || '{{ __("Charged successfully") }}');
                }

                if ($.pjax) {
                    $.pjax.reload('#pjax-container');
                } else {
                    window.location.reload();
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fa fa-check"></i> ' + '{{ __("admin.submit") }}');
                var msg = '{{ __("An error occurred during transaction") }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed.message) msg = parsed.message;
                    } catch (e) {}
                }
                $('#fund_error_alert').text(msg).show();
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                }
            }
        });
    });

    // --- REMOVE COINS ---
    $(document).off('click', '.remove-agency-coins-btn').on('click', '.remove-agency-coins-btn', function (e) {
        e.preventDefault();
        var agencyId = $(this).data('id');
        var agencyName = $(this).data('name');
        var currentCoins = $(this).data('coins');

        $('#remove_agency_id').val(agencyId);
        $('#remove_agency_name').val(agencyName);
        $('#remove_agency_current_coins').val(Number(currentCoins).toLocaleString());
        $('#remove_agency_coins_raw').val(currentCoins);
        $('#remove_operation_uuid').val(generateUUID());
        $('#remove_amount').val('');
        $('#remove_reason').val('');
        $('#remove_error_alert').hide().text('');

        var $submitBtn = $('#remove_submit_btn');
        $submitBtn.prop('disabled', false).html('<i class="fa fa-minus-circle"></i> ' + '{{ __("Remove Coins") }}');

        $('#mainAdminRemoveModal').modal('show');
        setTimeout(function () {
            $('#remove_amount').focus();
        }, 500);
    });

    $(document).off('click', '#remove_submit_btn').on('click', '#remove_submit_btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var agencyId = $('#remove_agency_id').val();
        var operationUuid = $('#remove_operation_uuid').val();
        var amount = parseInt($('#remove_amount').val(), 10);
        var reason = $('#remove_reason').val();
        var maxCoins = parseInt($('#remove_agency_coins_raw').val(), 10) || 0;

        if (!amount || isNaN(amount) || amount <= 0) {
            $('#remove_error_alert').text('{{ __("Amount must be greater than 0") }}').show();
            $('#remove_amount').focus();
            return;
        }

        if (amount > maxCoins) {
            $('#remove_error_alert').text('{{ __("balance not enough") }}').show();
            $('#remove_amount').focus();
            return;
        }

        $('#remove_error_alert').hide().text('');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + '{{ __("admin.loading") }}');

        var token = (typeof LA !== 'undefined' && LA.token) ? LA.token : $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: '{{ admin_url("charge-agencies/remove") }}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: token,
                agency_id: agencyId,
                amount: amount,
                operation_uuid: operationUuid,
                reason: reason
            },
            success: function (res) {
                $('#mainAdminRemoveModal').modal('hide');
                if (typeof toastr !== 'undefined') {
                    toastr.success(res.message || '{{ __("Coins removed successfully") }}');
                } else if (typeof swal !== 'undefined') {
                    swal('{{ __("admin.successful") }}', res.message || '{{ __("Coins removed successfully") }}', 'success');
                } else {
                    alert(res.message || '{{ __("Coins removed successfully") }}');
                }

                if ($.pjax) {
                    $.pjax.reload('#pjax-container');
                } else {
                    window.location.reload();
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fa fa-minus-circle"></i> ' + '{{ __("Remove Coins") }}');
                var msg = '{{ __("An error occurred during transaction") }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed.message) msg = parsed.message;
                    } catch (e) {}
                }
                $('#remove_error_alert').text(msg).show();
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                }
            }
        });
    });
});
</script>
