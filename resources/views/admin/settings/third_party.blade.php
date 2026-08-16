<div id="thirdPartySettings" class="settings-section">

    {{-- ══════════════════════════════════════════════════════════
         SECTION HEADER
    ══════════════════════════════════════════════════════════ --}}
    <div class="section-header-bar">
        <div class="section-header-icon"><i class="fas fa-satellite-dish"></i></div>
        <div class="section-header-text">
            <h3>{{ __('Third Party Settings') }}</h3>
            <p>{{ __('Every external service on its own tab: Realtime, notifications, phone login (Firebase / Twilio SMS), Google login, Crashlytics, storage, YouTube, UTD Stream and games.') }}</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         SUB-TABS — one tab per service.
    ══════════════════════════════════════════════════════════ --}}
    <div class="inner-settings-menu tp-subtabs">
        <button type="button" class="tab-btn active" onclick="showThirdPartySub('tpRealtime')">
            <i class="fas fa-broadcast-tower"></i> {{ __('Realtime Server') }}
        </button>
        <button type="button" class="tab-btn" onclick="showThirdPartySub('tpFirebaseFcm')">
            <i class="fas fa-bell"></i> {{ __('Firebase Notifications (FCM)') }}
        </button>
        <button type="button" class="tab-btn" onclick="showThirdPartySub('tpLogin')">
            <i class="fas fa-sms"></i> {{ __('Phone Login — Firebase') }}
        </button>
        <button type="button" class="tab-btn" onclick="showThirdPartySub('tpTwilioSms')">
            <i class="fas fa-mobile-alt"></i> {{ __('Phone Login — Twilio SMS') }}
        </button>
        <button type="button" class="tab-btn" onclick="showThirdPartySub('tpGoogleLogin')">
            <i class="fab fa-google"></i> {{ __('Google Login') }}
        </button>
        <button type="button" class="tab-btn" onclick="showThirdPartySub('tpFirebaseApp')">
            <i class="fas fa-bug"></i> {{ __('Firebase App (Crashlytics)') }}
        </button>
        <button type="button" class="tab-btn" onclick="showThirdPartySub('tpStorage')">
            <i class="fas fa-hdd"></i> {{ __('Storage') }}
        </button>
        <button type="button" class="tab-btn" onclick="showThirdPartySub('tpYoutube')">
            <i class="fab fa-youtube"></i> {{ __('YouTube') }}
        </button>
        <button type="button" class="tab-btn" onclick="showThirdPartySub('tpUtdStream')">
            <i class="fas fa-video"></i> {{ __('UTD Stream') }}
        </button>
        <button type="button" class="tab-btn" onclick="showThirdPartySub('tpGames')">
            <i class="fas fa-gamepad"></i> {{ __('Games') }}
        </button>
    </div>

    {{-- ─────────────────────── Realtime Server (Centrifugo) ─────────────────────── --}}
    <div id="tpRealtime" class="tp-sub-pane">
    <div class="rt-providers-grid">

        {{-- ══════════════════════════════════════════════════════════
             CENTRIFUGO — Realtime Server
             api_url is plain text (endpoint, not a secret). The three secrets
             (api_key / hmac / proxy) follow the Firebase/GCS blank-keeps-current
             pattern: never rendered, a green badge shows when set, blank submit
             keeps the current value. Read DB-first by setupRealtimeEndpoint().
        ══════════════════════════════════════════════════════════ --}}
        @php
            $cf_api_url        = App\Helpers\Common::getSettingValue('centrifugo_api_url') ?: '';
            $cf_ws             = App\Helpers\Common::getSettingValue('centrifugo_ws') ?: ($centrifugo_ws ?? '');
            $cf_api_key_set    = !empty(App\Helpers\Common::getSettingValue('centrifugo_api_key'));
            $cf_hmac_set       = !empty(App\Helpers\Common::getSettingValue('centrifugo_hmac_secret'));
            $cf_proxy_set      = !empty(App\Helpers\Common::getSettingValue('centrifugo_proxy_secret'));
        @endphp
        <form class="no-background-form" action="{{ route('admin.update-agora-zego') }}" method="POST">
            @csrf
            <input type="hidden" name="current_tab" value="thirdPartySettings">
            <div class="rt-provider-card">
                <div class="rt-provider-header" style="background: linear-gradient(135deg, #0f766e, #14b8a6);">
                    <div class="rt-provider-logo">
                        <i class="fas fa-broadcast-tower"></i>
                    </div>
                    <div class="rt-provider-title">
                        <h5>Centrifugo — {{ __('Realtime Server') }}</h5>
                        <span>{{ $cf_ws ?: __('not configured') }}</span>
                    </div>
                </div>
                <div class="rt-provider-body">

                    {{-- WebSocket URL --}}
                    <div class="rt-input-group">
                        <label><i class="fas fa-link"></i> {{ __('WebSocket URL') }}</label>
                        <input type="text" name="centrifugo_ws"
                               value="{{ $cf_ws }}"
                               class="form-control rt-input"
                               placeholder="wss://your-realtime-server/connection/websocket">
                        <small class="rt-hint">{{ __('The WebSocket URL sent to Flutter clients. Change only when the server address changes.') }}</small>
                    </div>

                    {{-- HTTP API URL (plain — endpoint, not a secret) --}}
                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fas fa-server"></i> {{ __('HTTP API URL') }}</label>
                        <input type="text" name="centrifugo_api_url"
                               value="{{ $cf_api_url }}"
                               class="form-control rt-input"
                               placeholder="https://your-realtime-server/api">
                        <small class="rt-hint">{{ __('The Centrifugo HTTP API base URL the backend publishes to. Leave blank to use the server .env default.') }}</small>
                    </div>

                    {{-- API Key (secret — blank keeps current) --}}
                    <div class="rt-input-group" style="margin-top:10px;">
                        <label>
                            <i class="fas fa-key"></i> {{ __('API Key') }}
                            @if($cf_api_key_set)
                                <span class="badge badge-success" style="margin-inline-start:8px;font-size:12px;padding:4px 10px;">
                                    <i class="fas fa-check-circle"></i> {{ __('Configured') }}
                                </span>
                            @endif
                        </label>
                        <input type="text" name="centrifugo_api_key" autocomplete="off"
                               class="form-control rt-input"
                               style="font-family:monospace;"
                               placeholder="••••••••••••••••">
                        <small class="rt-hint">{{ __('X-API-Key the backend sends on publish/broadcast. Stored in the database. Leave blank to keep the current value.') }}</small>
                    </div>

                    {{-- HMAC Secret (secret — blank keeps current) --}}
                    <div class="rt-input-group" style="margin-top:10px;">
                        <label>
                            <i class="fas fa-signature"></i> {{ __('Token HMAC Secret') }}
                            @if($cf_hmac_set)
                                <span class="badge badge-success" style="margin-inline-start:8px;font-size:12px;padding:4px 10px;">
                                    <i class="fas fa-check-circle"></i> {{ __('Configured') }}
                                </span>
                            @endif
                        </label>
                        <input type="text" name="centrifugo_hmac_secret" autocomplete="off"
                               class="form-control rt-input"
                               style="font-family:monospace;"
                               placeholder="••••••••••••••••">
                        <small class="rt-hint">{{ __('HS256 secret that signs connection + subscription JWTs (must match the node). Stored in the database. Leave blank to keep the current value.') }}</small>
                    </div>

                    {{-- Proxy Secret (secret — blank keeps current) --}}
                    <div class="rt-input-group" style="margin-top:10px;">
                        <label>
                            <i class="fas fa-shield-alt"></i> {{ __('Subscribe-Proxy Secret') }}
                            @if($cf_proxy_set)
                                <span class="badge badge-success" style="margin-inline-start:8px;font-size:12px;padding:4px 10px;">
                                    <i class="fas fa-check-circle"></i> {{ __('Configured') }}
                                </span>
                            @endif
                        </label>
                        <input type="text" name="centrifugo_proxy_secret" autocomplete="off"
                               class="form-control rt-input"
                               style="font-family:monospace;"
                               placeholder="••••••••••••••••">
                        <small class="rt-hint">{{ __('Shared secret the node sends on its subscribe-proxy call so the backend can verify the caller. Stored in the database. Leave blank to keep the current value.') }}</small>
                    </div>

                    {{-- Banner rollout --}}
                    <div class="rt-input-row" style="margin-top:12px;">
                        <div class="rt-input-group">
                            <label><i class="fas fa-flag"></i> {{ __('Banner Rollout') }}</label>
                            <select name="realtime_banners_users" class="form-control rt-input">
                                <option value="all"   {{ ($realtime_banners_users ?? 'all') === 'all'  ? 'selected' : '' }}>all — {{ __('Everyone') }}</option>
                                <option value="none"  {{ ($realtime_banners_users ?? '') === 'none'    ? 'selected' : '' }}>none — {{ __('Nobody (Pusher fallback)') }}</option>
                                <option value="test"  {{ ($realtime_banners_users ?? '') === 'test'    ? 'selected' : '' }}>test — {{ __('Test accounts only') }}</option>
                            </select>
                            <small class="rt-hint">{{ __('Who receives gift/game banners via Centrifugo') }}</small>
                        </div>

                        {{-- Chat rollout --}}
                        <div class="rt-input-group">
                            <label><i class="fas fa-comments"></i> {{ __('Chat Rollout') }}</label>
                            <select name="realtime_chat_users" class="form-control rt-input">
                                <option value="all"   {{ ($realtime_chat_users ?? 'all') === 'all'  ? 'selected' : '' }}>all — {{ __('Everyone') }}</option>
                                <option value="none"  {{ ($realtime_chat_users ?? '') === 'none'    ? 'selected' : '' }}>none — {{ __('Nobody') }}</option>
                                <option value="test"  {{ ($realtime_chat_users ?? '') === 'test'    ? 'selected' : '' }}>test — {{ __('Test accounts only') }}</option>
                            </select>
                            <small class="rt-hint">{{ __('Who uses Centrifugo for chat messages') }}</small>
                        </div>
                    </div>

                </div>
                <div class="rt-provider-footer">
                    <button type="submit" class="btn rt-btn-save">
                        <i class="fas fa-save"></i> {{ __('save') }}
                    </button>
                </div>
            </div>
        </form>

    </div>{{-- /.rt-providers-grid --}}
    </div>{{-- /#tpRealtime --}}

    {{-- ─────────────────────── Firebase Notifications (FCM) ─────────────────────── --}}
    {{-- The FCM form (firebase_project_name + firebase_service_account_json)
         moved to the dedicated Notification Settings page
         (NotificationSettingsController — the single save surface for these
         keys). This pane is only a pointer so nobody looks for it here. --}}
    <div id="tpFirebaseFcm" class="tp-sub-pane" style="display:none;">
    <div class="rt-providers-grid">
        <div class="rt-provider-card">
            <div class="rt-provider-header" style="background: linear-gradient(135deg, #7c3aed, #a855f7);">
                <div class="rt-provider-logo">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="rt-provider-title">
                    <h5>{{ __('Firebase Notifications (FCM)') }}</h5>
                    <span>{{ __('Firebase notification settings moved to the Notification Settings page.') }}</span>
                </div>
            </div>
            <div class="rt-provider-body">
                <div class="rt-input-group">
                    <small class="rt-hint" style="display:block;">
                        <i class="fas fa-info-circle"></i>
                        {{ __('The Firebase project name and Service Account JSON are managed there in one place, together with the rest of the push-notification keys.') }}
                    </small>
                </div>
            </div>
            <div class="rt-provider-footer">
                <a href="{{ url('admin/notification-settings') }}" class="btn rt-btn-save">
                    <i class="fas fa-bell"></i> {{ __('Open Notification Settings') }}
                </a>
            </div>
        </div>
        </div>{{-- /.rt-providers-grid --}}
    </div>{{-- /#tpFirebaseFcm --}}

    {{-- ─────────────────────── Login / Phone Auth ─────────────────────── --}}
    <div id="tpLogin" class="tp-sub-pane" style="display:none;">
    <div class="rt-providers-grid">
        {{-- ══════════════════════════════════════════════════════════
             FIREBASE SMS / PHONE AUTH — Editable from panel
             (firebase_phone_auth_enabled / firebase_web_api_key /
              firebase_auth_domain stored in the SETTINGS table)
        ══════════════════════════════════════════════════════════ --}}
        @php
            $fb_phone_enabled = App\Helpers\Common::getSettingValue('firebase_phone_auth_enabled');
            $fb_web_api_key   = App\Helpers\Common::getSettingValue('firebase_web_api_key') ?: '';
            $fb_auth_domain   = App\Helpers\Common::getSettingValue('firebase_auth_domain') ?: '';
        @endphp
        <form class="no-background-form" action="{{ route('admin.update-agora-zego') }}" method="POST">
            @csrf
            <input type="hidden" name="current_tab" value="thirdPartySettings">
            <div class="rt-provider-card">
                <div class="rt-provider-header" style="background: linear-gradient(135deg, #be123c, #fb7185);">
                    <div class="rt-provider-logo">
                        <i class="fas fa-sms"></i>
                    </div>
                    <div class="rt-provider-title">
                        <h5>{{ __('Phone Login — Firebase') }}</h5>
                        <span>{{ __('Account creation / sign-in with a phone number via Firebase SMS — values come from the Firebase console (Project settings).') }}</span>
                    </div>
                </div>
                <div class="rt-provider-body">

                    <div class="rt-input-group">
                        <label><i class="fas fa-toggle-on"></i> {{ __('Phone Auth Enabled') }}</label>
                        <select name="firebase_phone_auth_enabled" class="form-control rt-input">
                            <option value="0" {{ ((string) $fb_phone_enabled === '1') ? '' : 'selected' }}>{{ __('Disabled') }}</option>
                            <option value="1" {{ ((string) $fb_phone_enabled === '1') ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                        </select>
                        <small class="rt-hint">{{ __('Turn Firebase phone (SMS) sign-in on or off for the app.') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fas fa-key"></i> {{ __('Web API Key') }}</label>
                        <input type="text" name="firebase_web_api_key" class="form-control rt-input"
                               value="{{ $fb_web_api_key }}"
                               placeholder="AIza...">
                        <small class="rt-hint">{{ __('Firebase Web API key (from the Firebase console — Project settings).') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fas fa-globe"></i> {{ __('Auth Domain') }}</label>
                        <input type="text" name="firebase_auth_domain" class="form-control rt-input"
                               value="{{ $fb_auth_domain }}"
                               placeholder="your-project.firebaseapp.com">
                        <small class="rt-hint">{{ __('Firebase auth domain (usually your-project.firebaseapp.com).') }}</small>
                    </div>

                </div>
                <div class="rt-provider-footer">
                    <button type="submit" class="btn rt-btn-save">
                        <i class="fas fa-save"></i> {{ __('save') }}
                    </button>
                </div>
            </div>
        </form>

        </div>{{-- /.rt-providers-grid --}}
    </div>{{-- /#tpLogin --}}

    {{-- ─────────────────────── Phone Login — Twilio SMS ─────────────────────── --}}
    <div id="tpTwilioSms" class="tp-sub-pane" style="display:none;">
    <div class="rt-providers-grid">
        {{-- ══════════════════════════════════════════════════════════
             TWILIO SMS OTP — server-generated codes (codes table) delivered
             via the Twilio REST API. SID + Auth Token are secrets (blank
             submit keeps current); the sender number is a plain identifier.
             The provider selector writes phone_otp_provider — the single
             switch OtpProviderService reads for register / forgot-password /
             change-phone / bind. It lives HERE (not in the Firebase tab)
             because it spans all three methods, and this tab is the home of
             the "OTP methods beyond Firebase" surface.
        ══════════════════════════════════════════════════════════ --}}
        @php
            $tw_sid_set   = !empty(App\Helpers\Common::getSettingValue('twilio_account_sid'));
            $tw_token_set = !empty(App\Helpers\Common::getSettingValue('twilio_auth_token'));
            $tw_from      = App\Helpers\Common::getSettingValue('twilio_from_number') ?: '';
            $otp_provider = App\Helpers\Common::getSettingValue('phone_otp_provider') ?: 'firebase';
        @endphp
        <form class="no-background-form" action="{{ route('admin.update-agora-zego') }}" method="POST">
            @csrf
            <input type="hidden" name="current_tab" value="thirdPartySettings">
            <div class="rt-provider-card">
                <div class="rt-provider-header" style="background: linear-gradient(135deg, #dc2626, #f87171);">
                    <div class="rt-provider-logo">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="rt-provider-title">
                        <h5>{{ __('Phone Login — Twilio SMS') }}</h5>
                        <span>{{ __('The OTP code is generated on OUR server (visible on the Codes page) and delivered by Twilio SMS — credentials from the Twilio console.') }}</span>
                    </div>
                </div>
                <div class="rt-provider-body">

                    {{-- Active OTP provider (writes phone_otp_provider) --}}
                    <div class="rt-input-group">
                        <label><i class="fas fa-exchange-alt"></i> {{ __('Active Phone OTP Provider') }}</label>
                        <select name="phone_otp_provider" class="form-control rt-input">
                            <option value="firebase" {{ $otp_provider === 'firebase' ? 'selected' : '' }}>Firebase — {{ __('code verified by Google (default)') }}</option>
                            <option value="twilio"   {{ $otp_provider === 'twilio'   ? 'selected' : '' }}>Twilio SMS — {{ __('server code, delivered by SMS') }}</option>
                            <option value="whatsapp" {{ $otp_provider === 'whatsapp' ? 'selected' : '' }}>WhatsApp — {{ __('server code, delivered by WhatsApp') }}</option>
                        </select>
                        <small class="rt-hint">{{ __('Which service verifies phone numbers at sign-up / password reset / phone change. With Twilio or WhatsApp the code is generated on the server and always appears on the Codes page — even if delivery fails, you can read it there and give it to the user yourself.') }}</small>
                    </div>

                    {{-- Account SID (secret — blank keeps current) --}}
                    <div class="rt-input-group" style="margin-top:10px;">
                        <label>
                            <i class="fas fa-id-badge"></i> {{ __('Account SID') }}
                            @if($tw_sid_set)
                                <span class="badge badge-success" style="margin-inline-start:8px;font-size:12px;padding:4px 10px;">
                                    <i class="fas fa-check-circle"></i> {{ __('Configured') }}
                                </span>
                            @endif
                        </label>
                        <input type="text" name="twilio_account_sid" autocomplete="off"
                               class="form-control rt-input"
                               style="font-family:monospace;"
                               placeholder="••••••••••••••••">
                        <small class="rt-hint">{{ __('Twilio Account SID (starts with AC — from the Twilio console dashboard). Stored in the database. Leave blank to keep the current value.') }}</small>
                    </div>

                    {{-- Auth Token (secret — blank keeps current) --}}
                    <div class="rt-input-group" style="margin-top:10px;">
                        <label>
                            <i class="fas fa-key"></i> {{ __('Auth Token') }}
                            @if($tw_token_set)
                                <span class="badge badge-success" style="margin-inline-start:8px;font-size:12px;padding:4px 10px;">
                                    <i class="fas fa-check-circle"></i> {{ __('Configured') }}
                                </span>
                            @endif
                        </label>
                        <input type="text" name="twilio_auth_token" autocomplete="off"
                               class="form-control rt-input"
                               style="font-family:monospace;"
                               placeholder="••••••••••••••••">
                        <small class="rt-hint">{{ __('Twilio Auth Token (from the Twilio console dashboard). Stored in the database. Leave blank to keep the current value.') }}</small>
                    </div>

                    {{-- Sender number (plain) --}}
                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fas fa-hashtag"></i> {{ __('Sender Number') }}</label>
                        <input type="text" name="twilio_from_number" class="form-control rt-input"
                               value="{{ $tw_from }}"
                               placeholder="+15005550006">
                        <small class="rt-hint">{{ __('The Twilio phone number that sends the SMS, in international format with the plus sign (a number you bought or verified in the Twilio console).') }}</small>
                    </div>

                    <div style="margin-top:16px;padding-top:12px;border-top:1px dashed rgba(128,128,128,.35);">
                        <small class="rt-hint" style="display:block;">
                            <i class="fas fa-info-circle"></i>
                            {{ __('Even with no Twilio credentials you can select Twilio SMS: the code is still generated and shown on the Codes page, only the automatic SMS delivery is skipped.') }}
                        </small>
                    </div>

                </div>
                <div class="rt-provider-footer">
                    <button type="submit" class="btn rt-btn-save">
                        <i class="fas fa-save"></i> {{ __('save') }}
                    </button>
                </div>
            </div>
        </form>

        </div>{{-- /.rt-providers-grid --}}
    </div>{{-- /#tpTwilioSms --}}

    {{-- ─────────────────────── Google Login ─────────────────────── --}}
    <div id="tpGoogleLogin" class="tp-sub-pane" style="display:none;">
    <div class="rt-providers-grid">
        {{-- ══════════════════════════════════════════════════════════
             GOOGLE LOGIN — OAuth client id (Sign-in audience).
             Plain identifier stored in the SETTINGS table (google_client_id,
             seeded by WhiteLabelSettingsSeeder). Read DB-first by
             AuthService via Common::whiteLabel('google_client_id',
             'app.google_client_id') to verify the id_token audience.
        ══════════════════════════════════════════════════════════ --}}
        @php
            $google_client_id = App\Helpers\Common::getSettingValue('google_client_id') ?: '';
        @endphp
        <form class="no-background-form" action="{{ route('admin.update-agora-zego') }}" method="POST">
            @csrf
            <input type="hidden" name="current_tab" value="thirdPartySettings">
            <div class="rt-provider-card">
                <div class="rt-provider-header" style="background: linear-gradient(135deg, #ea580c, #f59e0b);">
                    <div class="rt-provider-logo">
                        <i class="fab fa-google"></i>
                    </div>
                    <div class="rt-provider-title">
                        <h5>{{ __('Google Login') }}</h5>
                        <span>{{ __('Sign in with Google — the backend verifies Google tokens against this OAuth client id.') }}</span>
                    </div>
                </div>
                <div class="rt-provider-body">

                    <div class="rt-input-group">
                        <label><i class="fas fa-id-badge"></i> {{ __('Google OAuth Client ID') }}</label>
                        <input type="text" name="google_client_id" class="form-control rt-input"
                               value="{{ $google_client_id }}"
                               placeholder="000000000000-xxxxxxxxxxxxxxxx.apps.googleusercontent.com">
                        <small class="rt-hint">{{ __('The WEB OAuth client id from Google Cloud Console → APIs & Services → Credentials (the same client_type 3 entry inside google-services.json). The backend checks that Google sign-in tokens were issued for this id. Leave blank to use the server .env value.') }}</small>
                    </div>

                    <div style="margin-top:12px;">
                        <small class="rt-hint" style="display:block;">
                            <i class="fas fa-info-circle"></i>
                            {{ __('Google sign-in works through Firebase and the google-services.json file inside the app even when this field is empty — fill it only to enable an extra server-side verification layer.') }}
                        </small>
                    </div>

                    <div style="margin-top:16px;padding-top:12px;border-top:1px dashed rgba(128,128,128,.35);">
                        <small class="rt-hint" style="display:block;">
                            <i class="fas fa-info-circle"></i>
                            {{ __('App-side setup: Google sign-in is enabled from the Firebase console (Authentication → Sign-in method → Google) and requires your SHA-1 / SHA-256 fingerprints on the Android app plus an updated google-services.json baked into the app build.') }}
                        </small>
                    </div>

                </div>
                <div class="rt-provider-footer">
                    <button type="submit" class="btn rt-btn-save">
                        <i class="fas fa-save"></i> {{ __('save') }}
                    </button>
                </div>
            </div>
        </form>

        </div>{{-- /.rt-providers-grid --}}
    </div>{{-- /#tpGoogleLogin --}}

    {{-- ─────────────────────── Firebase App Config ─────────────────────── --}}
    <div id="tpFirebaseApp" class="tp-sub-pane" style="display:none;">
    <div class="rt-providers-grid">
        {{-- ══════════════════════════════════════════════════════════
             FIREBASE APP CONFIG — Crashlytics / Auth / Firestore
             Per-platform client identity (plain identifiers, not secrets)
             stored in the SETTINGS table and served by /firebase-config so a
             white-label clone configures its own Firebase from the panel.
        ══════════════════════════════════════════════════════════ --}}
        @php
            $fb_and_api_key = App\Helpers\Common::getSettingValue('firebase_app_android_api_key') ?: '';
            $fb_and_app_id  = App\Helpers\Common::getSettingValue('firebase_app_android_app_id') ?: '';
            $fb_and_sender  = App\Helpers\Common::getSettingValue('firebase_app_android_sender_id') ?: '';
            $fb_and_project = App\Helpers\Common::getSettingValue('firebase_app_android_project_id') ?: '';
            $fb_and_bucket  = App\Helpers\Common::getSettingValue('firebase_app_android_bucket') ?: '';
            $fb_ios_api_key = App\Helpers\Common::getSettingValue('firebase_app_ios_api_key') ?: '';
            $fb_ios_app_id  = App\Helpers\Common::getSettingValue('firebase_app_ios_app_id') ?: '';
            $fb_ios_sender  = App\Helpers\Common::getSettingValue('firebase_app_ios_sender_id') ?: '';
            $fb_ios_project = App\Helpers\Common::getSettingValue('firebase_app_ios_project_id') ?: '';
            $fb_ios_bucket  = App\Helpers\Common::getSettingValue('firebase_app_ios_bucket') ?: '';
            $fb_web_app_api = App\Helpers\Common::getSettingValue('firebase_app_web_api_key') ?: '';
            $fb_web_app_id  = App\Helpers\Common::getSettingValue('firebase_app_web_app_id') ?: '';
            $fb_web_sender  = App\Helpers\Common::getSettingValue('firebase_app_web_sender_id') ?: '';
            $fb_web_project = App\Helpers\Common::getSettingValue('firebase_app_web_project_id') ?: '';
            $fb_web_bucket  = App\Helpers\Common::getSettingValue('firebase_app_web_bucket') ?: '';
            $fb_web_measure = App\Helpers\Common::getSettingValue('firebase_app_web_measurement_id') ?: '';
            $fb_database_uri = App\Helpers\Common::getSettingValue('firebase_database_uri') ?: '';
        @endphp
        <form class="no-background-form" action="{{ route('admin.update-agora-zego') }}" method="POST">
            @csrf
            <input type="hidden" name="current_tab" value="thirdPartySettings">
            <div class="rt-provider-card">
                <div class="rt-provider-header" style="background: linear-gradient(135deg, #3730a3, #4f46e5);">
                    <div class="rt-provider-logo">
                        <i class="fas fa-bug"></i>
                    </div>
                    <div class="rt-provider-title">
                        <h5>{{ __('Firebase App Config (Crashlytics / Auth / Firestore)') }}</h5>
                        <span>{{ __('Per-platform app identity (Android / iOS / Web) — copied from google-services.json and the Firebase console; powers Crashlytics, Auth and Firestore in the app.') }}</span>
                    </div>
                </div>
                <div class="rt-provider-body">

                    {{-- ANDROID --}}
                    <div class="rt-input-group">
                        <label><i class="fab fa-android"></i> {{ __('Android — API Key') }}</label>
                        <input type="text" name="firebase_app_android_api_key" class="form-control rt-input"
                               value="{{ $fb_and_api_key }}" placeholder="AIza...">
                        <small class="rt-hint">{{ __('Android app API key (Firebase console — Project settings — Your apps). Leave blank to fall back to the Web API key.') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fab fa-android"></i> {{ __('Android — App ID') }}</label>
                        <input type="text" name="firebase_app_android_app_id" class="form-control rt-input"
                               value="{{ $fb_and_app_id }}" placeholder="1:000000000000:android:xxxxxxxxxxxx">
                        <small class="rt-hint">{{ __('Android Firebase application id.') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fab fa-android"></i> {{ __('Android — Messaging Sender ID') }}</label>
                        <input type="text" name="firebase_app_android_sender_id" class="form-control rt-input"
                               value="{{ $fb_and_sender }}" placeholder="000000000000">
                        <small class="rt-hint">{{ __('Sender id / project number for the Android app.') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fab fa-android"></i> {{ __('Android — Project ID') }}</label>
                        <input type="text" name="firebase_app_android_project_id" class="form-control rt-input"
                               value="{{ $fb_and_project }}" placeholder="your-firebase-project-id">
                        <small class="rt-hint">{{ __('Firebase project id for the Android app.') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fab fa-android"></i> {{ __('Android — Storage Bucket') }}</label>
                        <input type="text" name="firebase_app_android_bucket" class="form-control rt-input"
                               value="{{ $fb_and_bucket }}" placeholder="your-project.appspot.com">
                        <small class="rt-hint">{{ __('Firebase storage bucket for the Android app.') }}</small>
                    </div>

                    <hr style="margin:18px 0; border-color: rgba(0,0,0,0.08);">

                    {{-- iOS --}}
                    <div class="rt-input-group">
                        <label><i class="fab fa-apple"></i> {{ __('iOS — API Key') }}</label>
                        <input type="text" name="firebase_app_ios_api_key" class="form-control rt-input"
                               value="{{ $fb_ios_api_key }}" placeholder="AIza...">
                        <small class="rt-hint">{{ __('iOS app API key (Firebase console — Project settings — Your apps). Leave blank to fall back to the Web API key.') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fab fa-apple"></i> {{ __('iOS — App ID') }}</label>
                        <input type="text" name="firebase_app_ios_app_id" class="form-control rt-input"
                               value="{{ $fb_ios_app_id }}" placeholder="1:000000000000:ios:xxxxxxxxxxxx">
                        <small class="rt-hint">{{ __('iOS Firebase application id.') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fab fa-apple"></i> {{ __('iOS — Messaging Sender ID') }}</label>
                        <input type="text" name="firebase_app_ios_sender_id" class="form-control rt-input"
                               value="{{ $fb_ios_sender }}" placeholder="000000000000">
                        <small class="rt-hint">{{ __('Sender id / project number for the iOS app.') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fab fa-apple"></i> {{ __('iOS — Project ID') }}</label>
                        <input type="text" name="firebase_app_ios_project_id" class="form-control rt-input"
                               value="{{ $fb_ios_project }}" placeholder="your-firebase-project-id">
                        <small class="rt-hint">{{ __('Firebase project id for the iOS app.') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fab fa-apple"></i> {{ __('iOS — Storage Bucket') }}</label>
                        <input type="text" name="firebase_app_ios_bucket" class="form-control rt-input"
                               value="{{ $fb_ios_bucket }}" placeholder="your-project.appspot.com">
                        <small class="rt-hint">{{ __('Firebase storage bucket for the iOS app.') }}</small>
                    </div>

                    <hr style="margin:18px 0; border-color: rgba(0,0,0,0.08);">

                    {{-- WEB --}}
                    <div class="rt-input-group">
                        <label><i class="fas fa-globe"></i> {{ __('Web — API Key') }}</label>
                        <input type="text" name="firebase_app_web_api_key" class="form-control rt-input"
                               value="{{ $fb_web_app_api }}" placeholder="AIza...">
                        <small class="rt-hint">{{ __('Web app API key. Leave blank to fall back to the Web API key above.') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fas fa-globe"></i> {{ __('Web — App ID') }}</label>
                        <input type="text" name="firebase_app_web_app_id" class="form-control rt-input"
                               value="{{ $fb_web_app_id }}" placeholder="1:000000000000:web:xxxxxxxxxxxx">
                        <small class="rt-hint">{{ __('Web Firebase application id (required for the web build to initialize Firebase).') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fas fa-globe"></i> {{ __('Web — Messaging Sender ID') }}</label>
                        <input type="text" name="firebase_app_web_sender_id" class="form-control rt-input"
                               value="{{ $fb_web_sender }}" placeholder="000000000000">
                        <small class="rt-hint">{{ __('Leave blank to reuse the Android sender id (same Firebase project).') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fas fa-globe"></i> {{ __('Web — Project ID') }}</label>
                        <input type="text" name="firebase_app_web_project_id" class="form-control rt-input"
                               value="{{ $fb_web_project }}" placeholder="your-firebase-project-id">
                        <small class="rt-hint">{{ __('Leave blank to reuse the Android project id (same Firebase project).') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fas fa-globe"></i> {{ __('Web — Storage Bucket') }}</label>
                        <input type="text" name="firebase_app_web_bucket" class="form-control rt-input"
                               value="{{ $fb_web_bucket }}" placeholder="your-project.appspot.com">
                        <small class="rt-hint">{{ __('Leave blank to reuse the Android storage bucket.') }}</small>
                    </div>

                    <div class="rt-input-group" style="margin-top:10px;">
                        <label><i class="fas fa-globe"></i> {{ __('Web — Measurement ID') }}</label>
                        <input type="text" name="firebase_app_web_measurement_id" class="form-control rt-input"
                               value="{{ $fb_web_measure }}" placeholder="G-XXXXXXXXXX">
                        <small class="rt-hint">{{ __('Optional Google Analytics measurement id for the web app.') }}</small>
                    </div>

                    <hr style="margin:18px 0; border-color: rgba(0,0,0,0.08);">

                    {{-- REALTIME DATABASE --}}
                    <div class="rt-input-group">
                        <label><i class="fas fa-database"></i> {{ __('Realtime Database URL') }}</label>
                        <input type="text" name="firebase_database_uri" class="form-control rt-input"
                               value="{{ $fb_database_uri }}" placeholder="https://your-project-default-rtdb.firebaseio.com">
                        <small class="rt-hint">{{ __('Firebase Realtime Database URL — sent to every platform (any feature using Realtime Database needs this).') }}</small>
                    </div>

                </div>
                <div class="rt-provider-footer">
                    <button type="submit" class="btn rt-btn-save">
                        <i class="fas fa-save"></i> {{ __('save') }}
                    </button>
                </div>
            </div>
        </form>

        </div>{{-- /.rt-providers-grid --}}
    </div>{{-- /#tpFirebaseApp --}}

    {{-- ─────────────────────── Storage (GCS) ─────────────────────── --}}
    <div id="tpStorage" class="tp-sub-pane" style="display:none;">
    <div class="rt-providers-grid">
        {{-- ══════════════════════════════════════════════════════════
             STORAGE — Editable configuration
        ══════════════════════════════════════════════════════════ --}}
        @php
            $gcs_bucket  = App\Helpers\Common::getSettingValue('gcs_bucket') ?: '';
            $gcs_project = App\Helpers\Common::getSettingValue('gcs_project_id') ?: '';
            $storage_url = App\Helpers\Common::getSettingValue('storage_url') ?: ($gcs_bucket ? 'https://storage.googleapis.com/' . $gcs_bucket : '');
            $gcs_sa_set  = !empty(App\Helpers\Common::getSettingValue('gcs_service_account_json'));
        @endphp
        <form class="no-background-form" action="{{ route('admin.update-agora-zego') }}" method="POST">
            @csrf
            <input type="hidden" name="current_tab" value="thirdPartySettings">
            <div class="rt-provider-card">
                <div class="rt-provider-header" style="background: linear-gradient(135deg, #1d4ed8, #3b82f6);">
                    <div class="rt-provider-logo">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="rt-provider-title">
                        <h5>{{ __('Storage') }}</h5>
                        <span>{{ __('File & media storage — any provider (AWS S3, Google Cloud, DigitalOcean, …)') }}</span>
                    </div>
                </div>
                <div class="rt-provider-body">

                    {{-- Provider-agnostic: the ONE field every deployment needs.
                         The app builds all media URLs from this base, whatever
                         the provider behind it is. --}}
                    <div class="rt-input-group">
                        <label><i class="fas fa-link"></i> {{ __('Storage URL') }}</label>
                        <input type="text" name="storage_url" class="form-control rt-input"
                               value="{{ $storage_url }}"
                               placeholder="https://your-bucket.s3.amazonaws.com">
                        <small class="rt-hint">{{ __('Public base URL of your bucket — works with any provider: AWS S3 (https://bucket.s3.amazonaws.com), Google Cloud (https://storage.googleapis.com/bucket), or any public object storage. This is what the app uses to load media.') }}</small>
                    </div>

                    {{-- GCS-only extras, clearly optional so an S3/other client
                         never thinks they are required. --}}
                    <div style="margin-top:16px;padding-top:12px;border-top:1px dashed rgba(128,128,128,.35);">
                        <small class="rt-hint" style="display:block;margin-bottom:8px;">
                            <i class="fab fa-google"></i>
                            {{ __('Google Cloud Storage only (optional) — fill these ONLY if your bucket is on GCS. AWS S3 / other providers: leave empty, the Storage URL above is enough.') }}
                        </small>

                        <div class="rt-input-group">
                            <label><i class="fas fa-bucket"></i> {{ __('GCS Bucket') }}</label>
                            <input type="text" name="gcs_bucket" class="form-control rt-input"
                                   value="{{ $gcs_bucket }}"
                                   placeholder="your-gcs-bucket">
                        </div>

                        <div class="rt-input-group" style="margin-top:10px;">
                            <label><i class="fas fa-project-diagram"></i> {{ __('GCS Project ID') }}</label>
                            <input type="text" name="gcs_project_id" class="form-control rt-input"
                                   value="{{ $gcs_project }}"
                                   placeholder="your-gcp-project">
                        </div>

                        <div class="rt-input-group" style="margin-top:10px;">
                            <label>
                                <i class="fas fa-file-code"></i> {{ __('GCS Service Account JSON') }}
                                @if($gcs_sa_set)
                                    <span class="badge badge-success" style="margin-inline-start:8px;font-size:12px;padding:4px 10px;">
                                        <i class="fas fa-check-circle"></i> {{ __('Configured') }}
                                    </span>
                                @endif
                            </label>
                            <textarea name="gcs_service_account_json" class="form-control rt-input" rows="6"
                                      style="min-height:130px;font-family:monospace;"
                                      placeholder='{ "type": "service_account", "project_id": "...", "private_key": "...", ... }'></textarea>
                            <small class="rt-hint">{{ __('Paste the GCS service account JSON, then Save. Stored in the database. Leave blank to keep the current value.') }}</small>
                        </div>
                    </div>

                </div>
                <div class="rt-provider-footer">
                    <button type="submit" class="btn rt-btn-save">
                        <i class="fas fa-save"></i> {{ __('save') }}
                    </button>
                </div>
            </div>
        </form>

        </div>{{-- /.rt-providers-grid --}}
    </div>{{-- /#tpStorage --}}

    {{-- ─────────────────────── YouTube ─────────────────────── --}}
    <div id="tpYoutube" class="tp-sub-pane" style="display:none;">
    <div class="rt-providers-grid">
        {{-- ══════════════════════════════════════════════════════════
             YOUTUBE — Data API v3 key (secret — blank keeps current)
             Stored in the SETTINGS table (youtube_api_key), served to the
             app in the app-check payload next to youtube_status so a clone
             configures its own key from the panel.
        ══════════════════════════════════════════════════════════ --}}
        @php $yt_key_set = !empty(App\Helpers\Common::getSettingValue('youtube_api_key')); @endphp
        <form class="no-background-form" action="{{ route('admin.update-agora-zego') }}" method="POST">
            @csrf
            <input type="hidden" name="current_tab" value="thirdPartySettings">
            <div class="rt-provider-card">
                <div class="rt-provider-header" style="background: linear-gradient(135deg, #b91c1c, #ef4444);">
                    <div class="rt-provider-logo">
                        <i class="fab fa-youtube"></i>
                    </div>
                    <div class="rt-provider-title">
                        <h5>YouTube</h5>
                        <span>{{ __('YouTube playback inside rooms — needs a YouTube Data API v3 key from Google Cloud Console.') }}</span>
                    </div>
                </div>
                <div class="rt-provider-body">

                    <div class="rt-input-group">
                        <label>
                            <i class="fas fa-key"></i> {{ __('API Key') }}
                            @if($yt_key_set)
                                <span class="badge badge-success" style="margin-inline-start:8px;font-size:12px;padding:4px 10px;">
                                    <i class="fas fa-check-circle"></i> {{ __('Configured') }}
                                </span>
                            @endif
                        </label>
                        <input type="text" name="youtube_api_key" autocomplete="off"
                               class="form-control rt-input"
                               style="font-family:monospace;"
                               placeholder="••••••••••••••••">
                        <small class="rt-hint">{{ __('YouTube Data API v3 key — from Google Cloud Console. Stored in the database. Leave blank to keep the current value.') }}</small>
                    </div>

                </div>
                <div class="rt-provider-footer">
                    <button type="submit" class="btn rt-btn-save">
                        <i class="fas fa-save"></i> {{ __('save') }}
                    </button>
                </div>
            </div>
        </form>

    </div>{{-- /.rt-providers-grid --}}
    </div>{{-- /#tpYoutube --}}

    {{-- ─────────────────────── UTD Stream ─────────────────────── --}}
    <div id="tpUtdStream" class="tp-sub-pane" style="display:none;">
        @include('admin.settings.utd_stream')
    </div>

    {{-- ══════════════════════════════════════════════════════════
         GAMES (relocated under Third Party)
    ══════════════════════════════════════════════════════════ --}}
    <div id="tpGames" class="tp-sub-pane" style="display:none;">
        @include('admin.settings.game')
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     SUB-TAB LOGIC + STYLES
═══════════════════════════════════════════════════════════════ --}}
<style>
    .tp-subtabs { flex-wrap: wrap; }
    /* The included UTD Stream / Games partials are wrapped in their own
       `.settings-section` (hidden by default). Force them visible inside
       their sub-pane so the inner tab can control them. */
    #tpUtdStream #utdStream,
    #tpGames #gamesSettings {
        display: block !important;
    }
</style>
<script>
    window.showThirdPartySub = function (paneId, push) {
        document.querySelectorAll('#thirdPartySettings .tp-sub-pane').forEach(function (pane) {
            pane.style.display = 'none';
        });
        var pane = document.getElementById(paneId);
        if (pane) pane.style.display = 'block';

        document.querySelectorAll('#thirdPartySettings .tp-subtabs .tab-btn').forEach(function (btn) {
            btn.classList.remove('active');
        });
        var activeBtn = document.querySelector('#thirdPartySettings .tp-subtabs .tab-btn[onclick*="\'' + paneId + '\'"]');
        if (activeBtn) activeBtn.classList.add('active');

        // Remember the open service in the URL so a save (which redirects back to
        // fullUrl) and a refresh reopen the same tab instead of the first one.
        if (push !== false) {
            var url = new URL(window.location);
            url.searchParams.set('svc', paneId);
            window.history.replaceState({}, '', url);
        }
    };

    (function initThirdPartySub() {
        function open() {
            var svc = new URLSearchParams(window.location.search).get('svc');
            if (svc && document.getElementById(svc)) {
                showThirdPartySub(svc, false);
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', open);
        } else {
            open();
        }
        $(document).on('pjax:complete', open);
    })();
</script>
