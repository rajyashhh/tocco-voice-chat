<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.4/js/bootstrap-switch.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/css/bootstrap-colorpicker.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/js/bootstrap-colorpicker.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="..." crossorigin="anonymous" />

@php
    $settings = App\Models\Setting::pluck('value', 'key')->toArray();

    $soundLibrary = App\Helpers\Common::getConfig('sound_library');
    $videoLibrary = App\Helpers\Common::getConfig('video_library');
    $liveLibrary = App\Helpers\Common::getConfig('live_library');
    $is_auto_preview = App\Helpers\Common::getConfig('is_auto_preview');

    // UTD-STREAM
    $utd_stream_app_id = App\Helpers\Common::getConfig('utd_stream_app_id');
    $utd_stream_server_secret = App\Helpers\Common::getConfig('utd_stream_server_secret');
    $utd_stream_callback_secret = App\Helpers\Common::getConfig('utd_stream_callback_secret');
    $utd_stream_app_key = App\Helpers\Common::getConfig('utd_stream_app_key');

    // Third Party
    $centrifugo_ws = App\Helpers\Common::getConfig('centrifugo_ws');
    $realtime_banners_users = App\Helpers\Common::getConfig('realtime_banners_users');
    $realtime_chat_users = App\Helpers\Common::getConfig('realtime_chat_users');

    // Server-side active tab. The initial section is rendered visible directly
    // from HTML so the page never opens blank if JS is delayed/fails under
    // pjax navigation. JS only takes over for clicks and pjax re-entry.
    $validTabs = [
        'brandSettings', 'landPageSettings', 'workSettings', 'mobileLinks',
        'timeSettings', 'appSettings', 'appVersionsSettings', 'paymentCredentialSettings',
        'aboutUsSettings', 'privacyPolicySettings',
    ];
    $activeTab = request()->get('tab', 'brandSettings');
    if (!in_array($activeTab, $validTabs, true)) {
        $activeTab = 'brandSettings';
    }
    // App links moved into the Landing Page section: old saved ?tab=mobileLinks
    // deep links land on their new home instead of a blank page.
    if ($activeTab === 'mobileLinks') {
        $activeTab = 'landPageSettings';
    }
@endphp

@include('admin.settings.css.settings_css')

{{-- Server-side reveal of the active section: guarantees the page never opens
     blank even if JS is delayed or fails under pjax. This <style> is REMOVED by
     initSettingsTabs() as soon as JS takes over, so the class-based toggle
     (.settings-section.active) becomes the single source of truth and two
     sections can never show at once. Its id lets the script find & drop it. --}}
<style id="settings-initial-reveal">#{{ $activeTab }}.settings-section { display: block; }</style>

<div class="settings-page">
<div class="settings-sidebar">
    <div class="sb-brand">
        <div class="sb-brand-icon"><i class="fas fa-sliders-h"></i></div>
        <div class="sb-brand-text">{{ __('Settings') }}</div>
    </div>
    <div class="settings-menu">
        <button onclick="showSection('brandSettings')" class="{{ $activeTab === 'brandSettings' ? 'active' : '' }}"><i class="fas fa-crown"></i> {{ __('Brand settings') }}</button>
        <button onclick="showSection('landPageSettings')" class="{{ $activeTab === 'landPageSettings' ? 'active' : '' }}"><i class="fas fa-globe"></i> {{ __('land settings') }}</button>
        <button onclick="showSection('workSettings')" class="{{ $activeTab === 'workSettings' ? 'active' : '' }}"><i class="fas fa-briefcase"></i> {{ __('Work') }}</button>
        <button onclick="showSection('timeSettings')" class="{{ $activeTab === 'timeSettings' ? 'active' : '' }}"><i class="fas fa-clock"></i> {{ __('Timing settings') }}</button>
        <button onclick="showSection('appSettings')" class="{{ $activeTab === 'appSettings' ? 'active' : '' }}"><i class="fas fa-swatchbook"></i> {{ __('App Appearance') }}</button>
        <button onclick="showSection('appVersionsSettings')" class="{{ $activeTab === 'appVersionsSettings' ? 'active' : '' }}"><i class="fas fa-sync-alt"></i> {{ __('App Updates') }}</button>
        <button onclick="showSection('paymentCredentialSettings')" class="{{ $activeTab === 'paymentCredentialSettings' ? 'active' : '' }}"><i class="fas fa-credit-card"></i> {{ __('Payment') }}</button>
        <button onclick="showSection('aboutUsSettings')" class="{{ $activeTab === 'aboutUsSettings' ? 'active' : '' }}"><i class="fas fa-file-alt"></i> {{ __('About Us') }}</button>
        <button onclick="showSection('privacyPolicySettings')" class="{{ $activeTab === 'privacyPolicySettings' ? 'active' : '' }}"><i class="fas fa-user-shield"></i> {{ __('Privacy Policy') }}</button>
    </div>
</div>

<div class="all-page" style="    width: 100%;">
    <div class="settings-content">
        @include('admin.settings.brand')

        @include('admin.settings.land_page')
        @include('admin.settings.time')
        @include('admin.settings.payment_credential')
        @include('admin.settings.work')
        @include('admin.settings.app')
        @include('admin.settings.app_versions')
        @include('admin.settings.about_us')
        @include('admin.settings.privacy_policy')

        <div id="imageModal" class="modal" onclick="closeFullScreen()">
            <span class="close">&times;</span>
            <img class="modal-content" id="fullImage">
        </div>
    </div>
</div>
</div>

@include('admin.settings.js.settings_js')
