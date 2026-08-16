<div id="aboutUsSettings" class="settings-section">
    <div class="section-header-bar">
        <div class="section-header-icon"><i class="fas fa-file-alt"></i></div>
        <div class="section-header-text">
            <h3>{{ __('About Us') }}</h3>
            <p>{{ __('Shown on the in-app About Us screen. Plain text or HTML.') }}</p>
        </div>
    </div>

    {{-- Saved to the Settings table via SettingsController@update and served to
         the app by VersionController@settings (about_us_en / about_us_ar). --}}
    <form action="{{ route('admin.app.settings.update') }}" method="POST"
          enctype="multipart/form-data" class="settings-form modern-form">
        @csrf
        <input type="hidden" name="current_tab" value="aboutUsSettings">

        <div class="form-section-title"><i class="fas fa-file-alt"></i> {{ __('About Us') }}</div>
        <div class="form row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-language text-muted"></i> {{ __('About Us (English)') }}</label>
                    <textarea name="about_us_en" rows="10" class="form-control"
                              placeholder="{{ __('About this app...') }}">{{ $settings['about_us_en'] ?? '' }}</textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-language text-muted"></i> {{ __('About Us (Arabic)') }}</label>
                    <textarea name="about_us_ar" rows="10" class="form-control" dir="rtl"
                              placeholder="{{ __('عن التطبيق...') }}">{{ $settings['about_us_ar'] ?? '' }}</textarea>
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
