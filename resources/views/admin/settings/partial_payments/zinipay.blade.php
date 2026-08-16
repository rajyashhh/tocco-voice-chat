<div class="col-md-6">
    <div class="form-group">
        <label for="zinipay_api_key">{{ __('admin.api_key') }}:</label>
        <input type="text" id="zinipay_api_key" name="zinipay_api_key" placeholder="zinipay_api_key"
               value="{{ $settings['zinipay_api_key'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="zinipay_url">{{ __('admin.url') }}:</label>
        <input type="text" id="zinipay_url" name="zinipay_url" placeholder="zinipay_url"
               value="{{ $settings['zinipay_url'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="zinipay_webhook_url">{{ __('admin.webhook_url') }}:</label>
        <div class="copy-container">
            <input type="text" id="zinipay_webhook_url" name="zinipay_webhook_url" placeholder="zinipay_webhook_url"
                   value="{{ url('/admin/payment-with-method') }}" class="form-control" readonly>
            <button type="button" class="copy-button" data-copy-target="zinipay_webhook_url" title="Copy">📋</button>
        </div>
    </div>
</div>
