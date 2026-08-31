import 'package:general/src/core/constants/constants_manager.dart';
import 'package:general/src/core/constants/end_points.dart';
import 'package:general/src/core/services/google_sign_in_factory.dart';
import 'package:general/src/core/utils/cache/hive_manager.dart';
import 'package:general/src/core/utils/cache/keys_manager.dart';
import 'package:general/src/core/utils/methods.dart';

/// Runtime realtime-transport config, populated from the server's
/// `/config/settings` response (`use_realtime_banners` + `centrifugo_ws`).
///
/// Centrifugo is the SOLE chat transport (legacy realtime has been fully removed from the
/// client), so the chat socket + drift/offline path are always on. Only the
/// outside-room banner transport remains independently server-gated.
class RealtimeConfig {
  /// Whether this client should receive the OUTSIDE-room banners/counters
  /// (gift/lucky-box/super-boom/close-stream + unread + game-status) via Centrifugo.
  /// Set from the server's `use_realtime_banners` on each settings fetch. Gated
  /// independently so the banner routing can be toggled/rolled back from the
  /// admin without an app rebuild. Rides the always-open chat socket.
  static bool useRealtimeBanners = false;

  /// WebSocket endpoint for the centrifuge-dart client — set from admin panel via /config/settings.
  static String wsUrl = '';

  /// UTD Stream room credentials, server-overridable from the admin panel
  /// (set from `/config/settings` on each fetch). Null/empty means the server
  /// block hasn't arrived yet — the room widgets fall back to
  /// [defaultUtdStreamAppId] / [defaultUtdStreamAppKey] so an empty/late
  /// config can NEVER blank the credentials and break room entry (the bug that
  /// broke release 1.0.21).
  static String? utdStreamAppId;

  /// The publishable app_key the new engine kits require (the kit mints its own
  /// token from it — no server_secret is ever shipped in the app). Server-driven
  /// from `/config/settings` (`utd_stream_app_key`, with a legacy
  /// `utd_stream_app_secret` fallback for configs not yet migrated).
  static String? utdStreamAppKey;

  /// Engine host override, server-driven from `/config/settings`
  /// (`utd_stream_host`, no path suffix). When set, the room kits' initApi is
  /// re-pointed at this host for BOTH token minting and in-room operations —
  /// required when the backend project lives on a non-default engine (e.g. the
  /// shared test engine). Null/empty keeps the kits' built-in production hosts.
  static String? utdStreamHost;

  /// UTD Stream credentials come ONLY from the backend (admin panel -> DB ->
  /// `/config/settings` -> applyFromSettings). NO credential is hardcoded in the
  /// source — a clone never ships another app's app_id/key. The build-time
  /// dart-define is an optional TEST-build injection (e.g. Tocco Voice Chat) for the
  /// pre-settings window; its default is EMPTY, so without DB/define the app has
  /// no credentials and will not connect to a room until the admin sets them.
  ///   flutter build ... \
  ///     --dart-define=UTD_STREAM_APP_ID=<test-app-id> \
  ///     --dart-define=UTD_STREAM_APP_KEY=<test-app-key>
  static const String defaultUtdStreamAppId =
      String.fromEnvironment('UTD_STREAM_APP_ID', defaultValue: '');
  static const String defaultUtdStreamAppKey = String.fromEnvironment(
    'UTD_STREAM_APP_KEY',
    defaultValue:
        String.fromEnvironment('UTD_STREAM_APP_SECRET', defaultValue: ''),
  );

  /// Support WhatsApp contact (full international number / wa.me target), set from
  /// the server's `support_whatsapp` on each settings fetch. Empty until the
  /// admin sets it — the UI HIDES the contact button when empty rather than show
  /// a foreign number. No client number is ever hardcoded.
  static String supportWhatsapp = '';

  /// In-app About Us content (bilingual), set from the server's `about_us_ar` /
  /// `about_us_en` on each settings fetch. Empty until the admin fills it from
  /// the panel — the About Us screen renders nothing/brand-neutral rather than a
  /// hardcoded client name. No client/brand text is ever shipped in the source.
  static String aboutUsAr = '';
  static String aboutUsEn = '';

  /// In-app Privacy Policy content (bilingual), set from the server's
  /// `privacy_policy_ar` / `privacy_policy_en` on each settings fetch. This is
  /// the SINGLE source the Privacy screen reads — admin-managed from the panel.
  static String privacyPolicyAr = '';
  static String privacyPolicyEn = '';

  /// True once a panel-driven title (cached or fresh) has been applied.
  /// main()'s unawaited PackageInfo read checks it so the native label can
  /// never race in AFTER the panel title and flip the header back.
  static bool panelTitleApplied = false;

  /// Re-apply the last panel-driven brand title at boot, BEFORE the first
  /// frame (called right after the Hive boxes open in main()). The cached
  /// title is language-stamped and only replayed when it matches the current
  /// app language — a stale other-language title falls through to the native
  /// label instead of flashing the wrong script.
  static void applyCachedAppTitle() {
    final cachedTitle = HiveManager()
        .getData<String>(KeysManager.USER_BOX, KeysManager.APP_TITLE_KEY);
    final cachedLang = HiveManager()
        .getData<String>(KeysManager.USER_BOX, KeysManager.APP_TITLE_LANG_KEY);
    if (cachedTitle != null &&
        cachedTitle.trim().isNotEmpty &&
        cachedLang == Methods.getLang()) {
      ConstantsManager.appDisplayName = cachedTitle.trim();
      panelTitleApplied = true;
    }
  }

  /// Apply the realtime block from the `/config/settings` response.
  static void applyFromSettings(Map<String, dynamic> json) {
    useRealtimeBanners = json['use_realtime_banners'] == true;
    final ws = json['centrifugo_ws'];
    if (ws is String && ws.isNotEmpty) {
      wsUrl = ws;
    }
    final appId = json['utd_stream_app_id'];
    if (appId is String && appId.isNotEmpty) {
      utdStreamAppId = appId;
    }
    final appKey = json['utd_stream_app_key'] ?? json['utd_stream_app_secret'];
    if (appKey is String && appKey.isNotEmpty) {
      utdStreamAppKey = appKey;
    }
    final streamHost = json['utd_stream_host'];
    if (streamHost is String && streamHost.isNotEmpty) {
      utdStreamHost = streamHost;
    }
    // Panel-managed Google OAuth server client id (white-label Sign-In
    // audience). Empty values are ignored inside the factory so a late/blank
    // config never wipes an applied id; blank-everywhere falls back to the
    // bundled google-services.json identity (no existing client breaks).
    GoogleSignInFactory.applyFromSettings(json['google_client_id']);
    // Panel-managed brand name (Brand settings -> Application Titles). The
    // admin's title wins over the native label / build define wherever the
    // app shows its own name. Strict per-language pick — ar gets ONLY
    // app_title_ar, everything else ONLY app_title_en — with the native
    // label as the fallback when the language's own key is blank. No
    // cross-language fallback: that is what made the header flip scripts
    // when one panel field was filled with the other language's text.
    // Persisted to Hive so the next cold start applies it BEFORE the first
    // frame (applyCachedAppTitle) instead of flashing the native label.
    final lang = Methods.getLang();
    final title = json[lang == 'ar' ? 'app_title_ar' : 'app_title_en'];
    if (title is String && title.trim().isNotEmpty) {
      ConstantsManager.appDisplayName = title.trim();
      panelTitleApplied = true;
      HiveManager().saveData(
          KeysManager.USER_BOX, KeysManager.APP_TITLE_KEY, title.trim());
      HiveManager()
          .saveData(KeysManager.USER_BOX, KeysManager.APP_TITLE_LANG_KEY, lang);
    }
    // Per-app object-storage base + privacy-policy override (white-label, set
    // from the admin panel). Empty values are ignored by the EndPoints setters
    // so a late/blank config can never wipe the bootstrap values.
    final storageUrl = json['storage_url'];
    if (storageUrl is String) {
      EndPoints.setStorageBaseUrl(storageUrl);
    }
    final privacyUrl = json['privacy_policy_url'];
    if (privacyUrl is String) {
      EndPoints.setPrivacyPolicyUrl(privacyUrl);
    }
    final supportWa = json['support_whatsapp'];
    if (supportWa is String && supportWa.isNotEmpty) {
      supportWhatsapp = supportWa;
    }
    // In-app About Us + Privacy content (bilingual). Empty values are ignored so
    // a late/blank config never wipes already-applied content; the screens read
    // these admin-managed values directly with no hardcoded brand text.
    final aboutAr = json['about_us_ar'];
    if (aboutAr is String && aboutAr.isNotEmpty) {
      aboutUsAr = aboutAr;
    }
    final aboutEn = json['about_us_en'];
    if (aboutEn is String && aboutEn.isNotEmpty) {
      aboutUsEn = aboutEn;
    }
    final privacyAr = json['privacy_policy_ar'];
    if (privacyAr is String && privacyAr.isNotEmpty) {
      privacyPolicyAr = privacyAr;
    }
    final privacyEn = json['privacy_policy_en'];
    if (privacyEn is String && privacyEn.isNotEmpty) {
      privacyPolicyEn = privacyEn;
    }
  }
}

/// LIVE accessor for the outside-room banner/counter transport switch. Reads the
/// runtime value so flipping the server flag re-routes banners with no rebuild.
bool get kUseRealtimeBanners => RealtimeConfig.useRealtimeBanners;
