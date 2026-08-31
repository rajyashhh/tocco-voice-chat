import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:google_sign_in/google_sign_in.dart';

import 'package:general/src/core/services/firebase_config_store.dart';
import 'package:general/src/core/utils/cache/hive_manager.dart';
import 'package:general/src/core/utils/cache/keys_manager.dart';

/// Single construction point for [GoogleSignIn] (white-label).
///
/// The Google OAuth SERVER (web) client id — the `aud` of the id_token the
/// backend verifies against the panel's `google_client_id` — is resolved at
/// RUNTIME from the panel instead of being baked into the APK. Resolution
/// order (first non-empty wins):
///
///  1. `google_client_id` from the authed `/config/settings` payload
///     (applied via [applyFromSettings] each fetch — instant panel flip).
///  2. `googleClientId` from the pre-auth `/firebase-config` payload
///     (covers the login screen of a fresh install, where no auth token
///     exists yet — [FirebaseConfigStore] fetches/persists it at cold start).
///  3. The last panel value persisted to Hive (covers the post-logout login
///     screen before any fresh fetch).
///  4. EMPTY -> [GoogleSignIn] is built WITHOUT `serverClientId`, i.e. the
///     bundled google-services.json / Info.plist identity — the exact
///     pre-change behavior, so a clone whose admin left the panel field
///     blank keeps working unchanged.
///
/// No client id is ever hardcoded here.
class GoogleSignInFactory {
  GoogleSignInFactory._();

  /// Panel value from the CURRENT session's `/config/settings` fetch.
  static String _panelServerClientId = '';

  /// Apply the `google_client_id` field of a `/config/settings` response.
  /// Empty/absent values are ignored so a late/blank config can never wipe an
  /// already-applied id (same rule as every other white-label settings field).
  static void applyFromSettings(dynamic id) {
    if (id is! String) return;
    final trimmed = id.trim();
    if (trimmed.isEmpty) return;
    _panelServerClientId = trimmed;
    HiveManager().saveData(
      KeysManager.USER_BOX,
      KeysManager.GOOGLE_SERVER_CLIENT_ID_KEY,
      trimmed,
    );
  }

  /// The panel-driven server client id, or '' when the panel field is blank
  /// everywhere (then the bundled identity is used).
  static String get effectiveServerClientId {
    if (_panelServerClientId.isNotEmpty) return _panelServerClientId;
    final preAuth = FirebaseConfigStore.instance.googleServerClientId;
    if (preAuth.isNotEmpty) return preAuth;
    final cached = HiveManager().getData<String>(
      KeysManager.USER_BOX,
      KeysManager.GOOGLE_SERVER_CLIENT_ID_KEY,
    );
    return cached?.trim() ?? '';
  }

  /// Build the [GoogleSignIn] instance every sign-in/sign-out call site uses.
  /// On web `serverClientId` is unsupported by the plugin (asserts), so the
  /// panel id is only applied on mobile — web keeps its meta-tag client id.
  static GoogleSignIn create() {
    final id = kIsWeb ? '' : effectiveServerClientId;
    return GoogleSignIn(serverClientId: id.isEmpty ? null : id);
  }
}
