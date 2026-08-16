<div class="col-md-6">
    <div class="form-group">
        <label for="apple_team_id">{{ __('admin.apple_team_id') }}:</label>
        <input type="text" id="apple_team_id" name="apple_team_id" placeholder="apple_team_id"
               value="{{ $settings['apple_team_id'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="apple_key_id">{{ __('admin.app_id') }}:</label>
        <input type="text" id="apple_key_id" name="apple_key_id" placeholder="apple_key_id"
               value="{{ $settings['apple_key_id'] ?? '' }}" class="form-control">
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="apple_client_id">{{ __('admin.apple_client_id') }}:</label>
        <input type="text" id="apple_client_id" name="apple_client_id" placeholder="apple_client_id"
               value="{{ $settings['apple_client_id'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="apple_redirect_uri">{{ __('admin.apple_service_file') }}:</label>
        <input type="file" id="apple_service_file" name="apple_service_file" placeholder="apple_service_file"
               class="form-control">
        <input type="text" id="apple_service_file" disabled name="apple_service_file" placeholder="apple_service_file"
               value="{{ $settings['apple_service_file'] ?? '' }}" class="form-control">
    </div>
</div>
