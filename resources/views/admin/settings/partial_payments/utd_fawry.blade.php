<div class="col-md-6">
    <div class="form-group">
        <label for="utd_fawry_secret">{{ __('admin.server_secret') }}:</label>
        <input type="text" id="utd_fawry_secret" name="utd_fawry_secret" placeholder="secret"
               value="{{ $settings['utd_fawry_secret'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="utd_fawry_merchant_code">{{ __('admin.merchant_code') }}:</label>
        <input type="text" id="utd_fawry_merchant_code" name="utd_fawry_merchant_code" placeholder="merchant_code"
               value="{{ $settings['utd_fawry_merchant_code'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="utd_url">{{ __('admin.utd_url') }}:</label>
        <input type="text" id="utd_url" name="utd_url" placeholder="utd_url" value="{{ $settings['utd_url'] ?? '' }}"
               class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="utd_fawry_url">{{ __('admin.fawry_url') }}:</label>
        <input type="text" id="utd_fawry_url" name="utd_fawry_url" placeholder="utd_fawry_url"
               value="{{ $settings['utd_fawry_url'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="utd_fawry_return_url">{{ __('admin.return_url') }}:</label>
        <div class="copy-container">
            <input type="text" id="utd_fawry_return_url" name="utd_fawry_return_url" placeholder="utd_fawry_return_url"
                   value="{{ url('/api/utd-fawry-callback') }}" class="form-control" readonly>
            <button type="button" class="copy-button" data-copy-target="utd_fawry_return_url" title="Copy">📋</button>
        </div>
    </div>
</div>
