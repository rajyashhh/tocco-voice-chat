<div class="col-md-6">
    <div class="form-group">
        <label for="google_pay_payment_url">{{ __('admin.payment_url') }}:</label>
        <input type="text" id="google_pay_payment_url" name="google_pay_payment_url" placeholder="payment_url"
               value="{{ $settings['google_pay_payment_url'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="google_pay_node_server_name">{{ __('admin.node_server_name') }}:</label>
        <input type="text" id="google_pay_node_server_name" name="google_pay_node_server_name"
               placeholder="node_server_name" value="{{ $settings['google_pay_node_server_name'] ?? '' }}"
               class="form-control" required>
    </div>
</div>
