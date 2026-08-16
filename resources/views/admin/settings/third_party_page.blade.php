<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.4/js/bootstrap-switch.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="..." crossorigin="anonymous" />

@php
    // The Third Party partial reads its own service values via
    // Common::getSettingValue(); only these fallbacks are consumed with `?? ''`.
    $centrifugo_ws          = App\Helpers\Common::getConfig('centrifugo_ws');
    $realtime_banners_users = App\Helpers\Common::getConfig('realtime_banners_users');
    $realtime_chat_users    = App\Helpers\Common::getConfig('realtime_chat_users');
@endphp

@include('admin.settings.css.settings_css')

{{-- Standalone page: no sidebar of the 10 settings tabs. The third_party
     partial is self-contained and drives its own service tabs. --}}
<div class="settings-page">
    <div class="all-page" style="width: 100%;">
        <div class="settings-content" style="width: 100%;">
            {{-- The partial's wrapper is `.settings-section` (hidden by default);
                 reveal it since this page renders only that one section. --}}
            <style>#thirdPartySettings.settings-section { display: block; }</style>
            @include('admin.settings.third_party')

            <div id="imageModal" class="modal" onclick="closeFullScreen()">
                <span class="close">&times;</span>
                <img class="modal-content" id="fullImage">
            </div>
        </div>
    </div>
</div>

@include('admin.settings.js.settings_js')
