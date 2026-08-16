<div class="col-md-6">
    <div class="form-group">
        <label for="paypal_base_url">{{ __('admin.base_url') }}:</label>
        <input type="text" id="paypal_base_url" name="paypal_base_url" placeholder="paypal_base_url"
               value="{{ $settings['paypal_base_url'] ?? '' }}" class="form-control">
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paypal_client_id">{{ __('admin.client_id') }}:</label>
        <input type="text" id="paypal_client_id" name="paypal_client_id" placeholder="paypal_client_id"
               value="{{ $settings['paypal_client_id'] ?? '' }}" class="form-control">
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paypal_client_secret">{{ __('admin.client_secret') }}:</label>
        <input type="text" id="paypal_client_secret" name="paypal_client_secret" placeholder="paypal_client_secret"
               value="{{ $settings['paypal_client_secret'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paypal_currency">{{ __('admin.currency') }}:</label>
        <input type="text" id="paypal_currency" name="paypal_currency" placeholder="paypal_currency"
               value="{{ $settings['paypal_currency'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paypal_webhook_id">{{ __('admin.webhook_id') }}:</label>
        <div class="copy-container">
            <input type="text" id="paypal_webhook_id" name="paypal_webhook_id" placeholder="paypal_webhook_id"
                   value="{{ $settings['paypal_webhook_id'] ?? '' }}" class="form-control">
        </div>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paypal_webhook_url">{{ __('admin.webhook_url') }}:</label>
        <div class="copy-container">
            <input type="text" id="paypal_webhook_url" name="paypal_webhook_url" placeholder="paypal_webhook_url"
                   value="{{ url('/api/paypal-callback') }}" class="form-control" required>
            <button type="button" class="copy-button" data-copy-target="paypal_webhook_url" title="Copy">📋</button>
        </div>
    </div>
</div>
