<div id="privacyPolicySettings" class="settings-section">
    <div class="section-header-bar">
        <div class="section-header-icon"><i class="fas fa-user-shield"></i></div>
        <div class="section-header-text">
            <h3>{{ __('Privacy Policy') }}</h3>
            <p>{{ __('Shown on the in-app Privacy Policy screen. Plain text or HTML.') }}</p>
        </div>
    </div>

    {{-- Saved to the Settings table via SettingsController@update and served to
         the app by VersionController@settings (privacy_policy_en / privacy_policy_ar). --}}
    <form action="{{ route('admin.app.settings.update') }}" method="POST"
          enctype="multipart/form-data" class="settings-form modern-form">
        @csrf
        <input type="hidden" name="current_tab" value="privacyPolicySettings">

        <div class="form-section-title"><i class="fas fa-user-shield"></i> {{ __('Privacy Policy') }}</div>
        <div class="form row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-language text-muted"></i> {{ __('Privacy Policy (English)') }}</label>
                    <textarea name="privacy_policy_en" rows="12" class="form-control"
                              placeholder="{{ __('Privacy policy...') }}">{{ $settings['privacy_policy_en'] ?? '' }}</textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-language text-muted"></i> {{ __('Privacy Policy (Arabic)') }}</label>
                    <textarea name="privacy_policy_ar" rows="12" class="form-control" dir="rtl"
                              placeholder="{{ __('سياسة الخصوصية...') }}">{{ $settings['privacy_policy_ar'] ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-12 mt-3 text-end">
            <button type="submit" class="btn btn-primary btn-save">
                <i class="fas fa-save"></i> {{ __('save') }}
            </button>
        </div>
    </form>
</div>