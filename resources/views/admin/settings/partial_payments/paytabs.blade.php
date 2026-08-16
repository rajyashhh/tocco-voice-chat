<div class="col-md-6">
    <div class="form-group">
        <label for="profile_id">{{ __('admin.profile_id') }}:</label>
        <input type="text" id="paytabs_profile_id" name="paytabs_profile_id" placeholder="paytabs_profile_id"
               value="{{ $settings['paytabs_profile_id'] ?? '' }}" class="form-control">
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paytabs_server_key">{{ __('admin.server_key') }}:</label>
        <input type="text" id="paytabs_server_key" name="paytabs_server_key" placeholder="paytabs_server_key"
               value="{{ $settings['paytabs_server_key'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paytabs_base_url">{{ __('admin.base_url') }}:</label>
        <input type="text" id="paytabs_base_url" name="paytabs_base_url" placeholder="paytabs_base_url"
               value="{{ $settings['paytabs_base_url'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="payment_address">{{ __('admin.payment_address') }}:</label>
        <input type="text" id="paytabs_payment_address" name="paytabs_payment_address"
               placeholder="paytabs_payment_address"
               value="{{ $settings['paytabs_payment_address'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paytabs_webhook_url">{{ __('admin.webhook_url') }}:</label>
        <div class="copy-container">
            <input type="text" id="paytabs_webhook_url" name="paytabs_webhook_url" placeholder="paytabs_webhook_url"
                   value="{{ $settings['paytabs_webhook_url'] ?? '' }}" class="form-control" readonly>
            <button type="button" class="copy-button" data-copy-target="paytabs_webhook_url" title="Copy">📋</button>
        </div>
    </div>
</div>
