<div class="col-md-6">
    <div class="form-group">
        <label for="utd_base_url">{{ __('admin.base_url') }}:</label>
        <input type="text" id="utd_base_url" name="utd_base_url" placeholder="utd_base_url"
               value="{{ $settings['utd_base_url'] ?? '' }}" class="form-control">
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="utd_api_key">{{ __('admin.api_key') }}:</label>
        <input type="text" id="utd_api_key" name="utd_api_key" placeholder="utd_api_key"
               value="{{ $settings['utd_api_key'] ?? '' }}" class="form-control">
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="utd_project_id">{{ __('admin.project_id') }}:</label>
        <input type="text" id="utd_project_id" name="utd_project_id" placeholder="utd_project_id"
               value="{{ $settings['utd_project_id'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="utd_webhook_url">{{ __('admin.webhook_url') }}:</label>
        <div class="copy-container">
            <input type="text" id="utd_webhook_url" name="utd_webhook_url" placeholder="utd_webhook_url"
                   value="{{ url('/api/utd-callback') }}" class="form-control" required>
            <button type="button" class="copy-button" data-copy-target="utd_webhook_url" title="Copy">📋</button>
        </div>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="utd_webhook_secret">Webhook Secret:</label>
        <input type="text" id="utd_webhook_secret" name="utd_webhook_secret" placeholder="utd_webhook_secret"
               value="{{ $settings['utd_webhook_secret'] ?? '' }}" class="form-control">
    </div>
</div>
