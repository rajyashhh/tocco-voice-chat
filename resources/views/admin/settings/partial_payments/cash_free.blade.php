<div class="col-md-6">
    <div class="form-group">
        <label for="cashfree_mode">{{ __('admin.mode') }}:</label>
        <select id="cashfree_mode" name="cashfree_mode" class="form-control" required>
            <option value="test" {{ ($settings['cashfree_mode'] ?? 'test') == 'test' ? 'selected' : '' }}>{{ __('admin.test') }}</option>
            <option value="live" {{ ($settings['cashfree_mode'] ?? '') == 'live' ? 'selected' : '' }}>{{ __('admin.live') }}</option>
        </select>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="cashfree_currency">{{ __('admin.currency') }}:</label>
        <input type="text" id="cashfree_currency" name="cashfree_currency" placeholder="currency"
               value="{{ $settings['cashfree_currency'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="opay_secret_key">{{ __('admin.app_id') }}:</label>
        <input type="text" id="cashfree_app_id" name="cashfree_app_id" placeholder="cashfree_app_id"
               value="{{ $settings['cashfree_app_id'] ?? '' }}" class="form-control">
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="opay_country_code">{{ __('admin.secret_key') }}:</label>
        <input type="text" id="cashfree_secret_key" name="cashfree_secret_key" placeholder="cashfree_secret_key"
               value="{{ $settings['cashfree_secret_key'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="opay_base_url">{{ __('admin.base_url') }}:</label>
        <input type="text" id="cashfree_base_url" name="cashfree_base_url" placeholder="base_url"
               value="{{ $settings['cashfree_base_url'] ?? '' }}" class="form-control" required>
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
