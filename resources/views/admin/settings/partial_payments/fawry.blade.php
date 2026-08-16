<div class="col-md-6">
    <div class="form-group">
        <label for="fawry_secret">{{ __('admin.server_secret') }}:</label>
        <input type="text" id="fawry_secret" name="fawry_secret" placeholder="secret"
               value="{{ $settings['fawry_secret'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="fawry_merchant_code">{{ __('admin.merchant_code') }}:</label>
        <input type="text" id="fawry_merchant_code" name="fawry_merchant_code" placeholder="merchant_code"
               value="{{ $settings['fawry_merchant_code'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label
            for="fawry_return_url">{{ __('admin.return_url') }}:</label>
        <input type="text" id="fawry_return_url" name="fawry_return_url" placeholder="return_url"
               value="{{ $settings['fawry_return_url'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="fawry_url">{{ __('admin.fawry_url') }}:</label>
        <input type="text" id="fawry_url" name="fawry_url" placeholder="fawry_url"
               value="{{ $settings['fawry_url'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="fawry_webhook_url">{{ __('admin.webhook_url') }}:</label>
        <div class="copy-container">
            <input type="text" id="fawry_webhook_url" name="fawry_webhook_url" placeholder="fawry_webhook_url"
                   value="{{ url('/api/fawry-callback') }}" class="form-control" readonly>
            <button type="button" class="copy-button" data-copy-target="fawry_webhook_url" title="Copy">📋</button>
        </div>
    </div>
</div>
