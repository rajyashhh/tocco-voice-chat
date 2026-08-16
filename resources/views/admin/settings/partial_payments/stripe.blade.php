<div class="col-md-6">
    <div class="form-group">
        <label for="stripe_test_secret_key">{{ __('admin.test_secret_key') }}:</label>
        <input type="text" id="stripe_test_secret_key" name="stripe_test_secret_key" placeholder="test_secret_key"
               value="{{ $settings['stripe_test_secret_key'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="stripe_success_url">{{ __('admin.success_url') }}:</label>
        <input type="text" id="stripe_success_url" name="stripe_success_url" placeholder="success_url"
               value="{{ $settings['stripe_success_url'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="stripe_cancel_url">{{ __('admin.cancel_url') }}:</label>
        <input type="text" id="stripe_cancel_url" name="stripe_cancel_url" placeholder="cancel_url"
               value="{{ $settings['stripe_cancel_url'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="stripe_currency">{{ __('admin.currency') }}:</label>
        <input type="text" id="stripe_currency" name="stripe_currency" placeholder="currency"
               value="{{ $settings['stripe_currency'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="stripe_webhook_secret">{{ __('admin.webhook_secret') }}:</label>
        <input type="text" id="stripe_webhook_secret" name="stripe_webhook_secret" placeholder="webhook_secret"
               value="{{ $settings['stripe_webhook_secret'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="stripe_webhook_url">{{ __('admin.webhook_url') }}:</label>
        <div class="copy-container">
            <input type="text" id="stripe_webhook_url" name="stripe_webhook_url" placeholder="stripe_webhook_url"
                   value="{{ url('/api/stripe-callback') }}" class="form-control" readonly>
            <button type="button" class="copy-button" data-copy-target="stripe_webhook_url" title="Copy">📋</button>
        </div>
    </div>
</div>
