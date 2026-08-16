<div id="brandSettings" class="settings-section">
    <div class="section-header-bar">
        <div class="section-header-icon"><i class="fas fa-palette"></i></div>
        <div class="section-header-text">
            <h3>{{ __('Brand settings') }}</h3>
            <p>{{ __('Manage your application identity, titles and logos') }}</p>
        </div>
    </div>

    <form action="{{ route('admin.app.settings.update') }}" method="POST" enctype="multipart/form-data" class="settings-form modern-form">
        @csrf
        <input type="hidden" name="current_tab" value="">

        <div class="form-section-title"><i class="fas fa-heading"></i> {{ __('Application Titles') }}</div>
        <div class="form row">
            <div class="col-md-6">
                <div class="form-group floating-group">
                    <label><i class="fas fa-language text-muted"></i> {{ __('Application title en:') }}</label>
                    <input type="text" name="app_title_en" value="{{ $settings['app_title_en'] ?? '' }}" class="form-control" placeholder="{{ __('Enter English title') }}" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group floating-group">
                    <label><i class="fas fa-language text-muted"></i> {{ __('Application title ar:') }}</label>
                    <input type="text" name="app_title_ar" value="{{ $settings['app_title_ar'] ?? '' }}" class="form-control" placeholder="{{ __('Enter Arabic title') }}" required>
                </div>
            </div>
        </div>

        <div class="form-divider"></div>

        <div class="form-section-title"><i class="fas fa-image"></i> {{ __('Brand Images') }}</div>
        <div class="form row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-image text-muted"></i> {{ __('Application logo:') }}</label>
                    <input type="file" name="app_logo" class="form-control file-input" onchange="previewImage(event)" accept="image/*">
                    <div class="img-preview-card {{ empty($settings['app_logo']) ? 'hidden' : '' }}">
                        <img id="imagePreview"
                             src="{{ !empty($settings['app_logo']) ? getImagePath($settings['app_logo']) : '' }}"
                             class="mt-2 img-thumbnail"
                             style="{{ !empty($settings['app_logo']) ? '' : 'display:none;' }}"
                             onclick="openFullScreen(this)">
                        <span class="img-label">{{ __('Current Logo') }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-star text-muted"></i> {{ __('Application Fav Icon:') }}</label>
                    <input type="file" name="app_fav_icon" class="form-control file-input" onchange="previewFavIcon(event)" accept="image/*">
                    <div class="img-preview-card {{ empty($settings['app_fav_icon']) ? 'hidden' : '' }}">
                        <img id="favIconPreview"
                             src="{{ !empty($settings['app_fav_icon']) ? getImagePath($settings['app_fav_icon']) : '' }}"
                             class="mt-2 img-thumbnail"
                             style="{{ !empty($settings['app_fav_icon']) ? '' : 'display:none;' }}"
                             onclick="openFullScreen(this)">
                        <span class="img-label">{{ __('Current Favicon') }}</span>
                    </div>
                </div>
            </div>

            {{-- Coin (currency) image — the single image the app shows wherever a
                 coin amount appears (wallet, recharge, rewards, events). Saved to
                 the settings table AND mirrored to the two canonical storage
                 paths every API resource references (coin.png +
                 custom_image/gold_coin_icon.png) by SettingsController@update. --}}
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-coins text-muted"></i> {{ __('Coin Image (currency):') }}</label>
                    <input type="file" name="coin_image" class="form-control file-input" onchange="previewCoinImage(event)" accept="image/*">
                    <div class="img-preview-card {{ empty($settings['coin_image']) ? 'hidden' : '' }}">
                        <img id="coinImagePreview"
                             src="{{ !empty($settings['coin_image']) ? getImagePath($settings['coin_image']) : '' }}"
                             class="mt-2 img-thumbnail"
                             style="{{ !empty($settings['coin_image']) ? '' : 'display:none;' }}"
                             onclick="openFullScreen(this)">
                        <span class="img-label">{{ __('Current Coin Image') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mt-3 text-end">
            <button type="submit" class="btn btn-primary btn-save">
                <i class="fas fa-save"></i> {{ __('save') }}
            </button>
        </div>
    </form>

    {{-- About Us + Privacy Policy were moved out of Brand settings into their own
         dedicated tabs (settings/about_us.blade.php, settings/privacy_policy.blade.php).
         — owner request 2026-08-13. --}}

</div>
