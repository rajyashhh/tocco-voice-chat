<div class="col-md-6">
    <div class="form-group">
        <label for="codapay_base_url">{{ __('admin.base_url') }}:</label>
        <input type="text" id="codapay_base_url" name="codapay_base_url" placeholder="codapay_base_url"
               value="{{ $settings['codapay_base_url'] ?? '' }}" class="form-control">
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="codapay_api_key">{{ __('admin.api_key') }}:</label>
        <input type="text" id="codapay_api_key" name="codapay_api_key" placeholder="codapay_api_key"
               value="{{ $settings['codapay_api_key'] ?? '' }}" class="form-control">
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="codapay_project_id">{{ __('admin.project_id') }}:</label>
        <input type="text" id="codapay_project_id" name="codapay_project_id" placeholder="codapay_project_id"
               value="{{ $settings['codapay_project_id'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="codapay_webhook_url">{{ __('admin.webhook_url') }}:</label>
        <div class="copy-container">
            <input type="text" id="codapay_webhook_url" name="codapay_webhook_url" placeholder="codapay_webhook_url"
                   value="{{ url('/api/codapay-callback') }}" class="form-control" required>
            <button type="button" class="copy-button" data-copy-target="codapay_webhook_url" title="Copy">📋</button>
        </div>
    </div>
</div>
