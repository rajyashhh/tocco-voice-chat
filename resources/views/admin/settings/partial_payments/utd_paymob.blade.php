<div class="col-md-6">
    <div class="form-group">
        <label for="utd_paymob_secret">{{ __('admin.server_secret') }}:</label>
        <input type="text" id="utd_paymob_secret" name="utd_paymob_secret" placeholder="secret"
               value="{{ $settings['utd_paymob_secret'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="utd_paymob_merchant_code">{{ __('admin.merchant_code') }}:</label>
        <input type="text" id="utd_paymob_merchant_code" name="utd_paymob_merchant_code" placeholder="merchant_code"
               value="{{ $settings['utd_paymob_merchant_code'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="utd_paymob_url">{{ __('admin.utd_url') }}:</label>
        <input type="text" id="utd_paymob_url" name="utd_paymob_url" placeholder="utd_paymob_url"
               value="{{ $settings['utd_paymob_url'] ?? '' }}" class="form-control" required>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="utd_paymob_return_url">{{ __('admin.return_url') }}:</label>
        <div class="copy-container">
            <input type="text" id="utd_paymob_return_url" name="utd_paymob_return_url" placeholder="utd_paymob_return_url"
                   value="{{ url('/api/Utd-paymob-callback') }}" class="form-control" readonly>
            <button type="button" class="copy-button" data-copy-target="utd_paymob_return_url" title="Copy">📋</button>
        </div>
    </div>
</div>
