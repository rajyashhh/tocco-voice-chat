<div class="col-md-6">
    <div class="form-group">
        <label for="opay_currency">{{ __('admin.currency') }}:</label>
        <input type="text" id="opay_currency" name="opay_currency" placeholder="currency"
               value="{{ $settings['opay_currency'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="opay_secret_key">{{ __('admin.server_secret') }}:</label>
        <input type="text" id="opay_secret_key" name="opay_secret_key" placeholder="server_secret"
               value="{{ $settings['opay_secret_key'] ?? '' }}" class="form-control">
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="opay_public_key">{{ __('admin.public_key') }}:</label>
        <input type="text" id="opay_public_key" name="opay_public_key" placeholder="public_key"
               value="{{ $settings['opay_public_key'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="opay_merchant_id">{{ __('admin.merchant_id') }}:</label>
        <input type="text" id="opay_merchant_id" name="opay_merchant_id" placeholder="merchant_id"
               value="{{ $settings['opay_merchant_id'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="opay_country_code">{{ __('admin.country_code') }}:</label>
        <input type="text" id="opay_country_code" name="country_code" placeholder="server_secret"
               value="{{ $settings['country_code'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="opay_base_url">{{ __('admin.base_url') }}:</label>
        <input type="text" id="opay_base_url" name="opay_base_url" placeholder="base_url"
               value="{{ $settings['opay_base_url'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="opay_webhook_url">{{ __('admin.webhook_url') }}:</label>
        <div class="copy-container">
            <input type="text" id="opay_webhook_url" name="opay_webhook_url" placeholder="opay_webhook_url"
                   value="{{ $settings['opay_webhook_url'] ?? '' }}" class="form-control" readonly>
            <button type="button" class="copy-button" data-copy-target="opay_webhook_url" title="Copy">📋</button>
        </div>
    </div>
</div>
