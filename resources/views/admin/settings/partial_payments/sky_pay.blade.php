<div class="col-md-6">
    <div class="form-group">
        <label for="paysky_base_url">{{ __('admin.base_url') }}:</label>
        <input type="text" id="paysky_base_url" name="paysky_base_url" placeholder="base_url"
               value="{{ $settings['paysky_base_url'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paysky_merchant_id">{{ __('admin.merchant_id') }}:</label>
        <input type="text" id="paysky_merchant_id" name="paysky_merchant_id" placeholder="merchant_id"
               value="{{ $settings['paysky_merchant_id'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paysky_terminal_id">{{ __('admin.terminal_id') }}:</label>
        <input type="text" id="paysky_terminal_id" name="paysky_terminal_id" placeholder="terminal_id"
               value="{{ $settings['paysky_terminal_id'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paysky_api_key">{{ __('admin.api_key') }}:</label>
        <input type="text" id="paysky_api_key" name="paysky_api_key" placeholder="api_key"
               value="{{ $settings['paysky_api_key'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="paysky_webhook_url">{{ __('admin.webhook_url') }}:</label>
        <div class="copy-container">
            <input type="text" id="paysky_webhook_url" name="paysky_webhook_url" placeholder="paysky_webhook_url"
                   value="{{ $settings['paysky_webhook_url'] ?? '' }}" class="form-control" readonly>
            <button type="button" class="copy-button" data-copy-target="paysky_webhook_url" title="Copy">📋</button>
        </div>
    </div>
</div>
