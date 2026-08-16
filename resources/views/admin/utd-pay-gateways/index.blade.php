<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 22px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute; cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc; transition: .4s;
    }
    .slider:before {
        position: absolute; content: "";
        height: 16px; width: 16px;
        left: 3px; bottom: 3px;
        background-color: white; transition: .4s;
    }
    input:checked + .slider { background-color: #2196F3; }
    input:checked + .slider:before { transform: translateX(18px); }
    .slider.round { border-radius: 22px; }
    .slider.round:before { border-radius: 50%; }
</style>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">UTD Payment Gateways</h3>
    </div>
    <div class="box-body">
        @if(empty($gateways))
            <div class="alert alert-warning">
                No payment gateways found. Check your UTD API settings (Base URL & API Key).
            </div>
        @else
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Gateway</th>
                        <th>Live Mode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gateways as $index => $gateway)
                        <tr id="row-{{ $gateway['id'] }}">
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $gateway['name'] }}</strong></td>
                            <td>
                                @if($gateway['liveMode'])
                                    <span class="label label-success">Live</span>
                                @else
                                    <span class="label label-default">Sandbox</span>
                                @endif
                            </td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox"
                                           class="gateway-toggle"
                                           data-id="{{ $gateway['id'] }}"
                                           {{ $gateway['enabled'] ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<script>
$(function () {
    $('.gateway-toggle').on('change', function () {
        var checkbox = $(this);
        var gatewayId = checkbox.data('id');
        var enabled = checkbox.is(':checked');
        var statusLabel = $('.gateway-status-' + gatewayId);

        checkbox.prop('disabled', true);

        $.ajax({
            url: '{{ admin_url("utd-pay-gateways/toggle") }}',
            method: 'POST',
            data: {
                _token: LA.token,
                gateway_id: gatewayId,
                enabled: enabled ? 1 : 0
            },
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    statusLabel
                        .removeClass('label-success label-danger')
                        .addClass(enabled ? 'label-success' : 'label-danger')
                        .text(enabled ? 'Enabled' : 'Disabled');
                } else {
                    toastr.error(res.message);
                    checkbox.prop('checked', !enabled);
                }
            },
            error: function () {
                toastr.error('Failed to update gateway');
                checkbox.prop('checked', !enabled);
            },
            complete: function () {
                checkbox.prop('disabled', false);
            }
        });
    });
});
</script>
