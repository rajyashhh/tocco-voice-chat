import 'package:general/src/features/auth/domain/entities/colors_entity.dart';
import 'package:general/src/features/auth/domain/use_cases/fetch_colors_uc.dart';
import 'package:general/src/core/index.dart';

part 'colors_event.dart';
part 'colors_state.dart';

class ColorsBloc extends Bloc<ColorsEvent, ColorsState> {
  final FetchColorsUc _fetchColorsUc;

  ColorsBloc(this._fetchColorsUc) : super(const ColorsState()) {
    on<FetchColorsEvent>(_fetchColorsEvent);
  }

  void _fetchColorsEvent(
    FetchColorsEvent event,
    Emitter<ColorsState> emit,
  ) async {
    emit(state.copyWith(reqState: RequestState.loading));
    final result = await _fetchColorsUc(forceRefresh: event.forceRefresh);

    await result.fold(
      (left) async {
        emit(
          state.copyWith(
            reqState: RequestState.error,
            message: NetworkExceptions.getErrorMessage(left),
          ),
        );
      },
      (right) async {
        emit(
          state.copyWith(
            reqState: RequestState.loaded,
            message: right.message,
            colors: right.data,
          ),
        );
        await handleColors();
      },
    );
  }

  /// Awaitable fetch+apply used by the cold-start config flow so the FIRST
  /// launch after a panel color change applies the new colors into
  /// [ColorManager] BEFORE the splash navigates — otherwise the fresh tree
  /// builds against the old cached colors and only picks up the new ones on a
  /// later launch (the multi enter/exit bug). Returns once colors are applied.
  ///
  /// [forceRefresh] bypasses the dio HTTP cache so the GET hits the server and
  /// overwrites the stale cached entry. The config flow passes `true` only when
  /// app-check signalled a panel color change (isColorUpdated); when unchanged
  /// the colors are served from cache and this fetch is never called.
  Future<void> fetchAndApplyColors({bool forceRefresh = false}) async {
    final result = await _fetchColorsUc(forceRefresh: forceRefresh);
    await result.fold(
      (left) async {
        // Surface the fetch failure instead of swallowing it. The cached colors
        // (already applied at the top of the config flow) remain the visible
        // fallback, but the error is logged + emitted so it is never hidden
        // (owner rule: no silent fallback masking problems).
        final message = NetworkExceptions.getErrorMessage(left);
        Methods.printLog(
          '❌ fetchAndApplyColors failed: $message — keeping cached colors',
          name: 'ColorsBloc',
        );
        emit(
          state.copyWith(
            reqState: RequestState.error,
            message: message,
          ),
        );
      },
      (right) async {
        emit(
          state.copyWith(
            reqState: RequestState.loaded,
            message: right.message,
            colors: right.data,
          ),
        );
        await handleColors();
      },
    );
  }

  Future<void> handleColors() async {
    if (state.colors == null) {
      return;
    }

    if (state.colors?.bottomNav == null) {
      ConstantsManager.isSvgaNavBar = true;
    } else {
      ConstantsManager.isSvgaNavBar = false;
    }

    await HiveManager().saveData(
      KeysManager.USER_BOX,
      KeysManager.BOTTOM_NAV_ICONS_KEY,
      ConstantsManager.isSvgaNavBar,
    );

    final background = state.colors?.background?.value;
    final backgroundType = state.colors?.background?.type;

    if ((background ?? "").isNotEmpty) {
      await HiveManager().saveData(
        KeysManager.USER_BOX,
        KeysManager.BACKGROUND_KEY,
        background,
      );
      await HiveManager().saveData(
        KeysManager.USER_BOX,
        KeysManager.BACKGROUND_TYPE_KEY,
        backgroundType,
      );
    }

    // Owner decision (2026-08): the color fields of colors/v2 are IGNORED —
    // every theme ships its fixed palette baked into ColorManager. Only the
    // nav icons (and the isSvgaNavBar flag above) remain server-driven here.

    // Bottom-nav icons from the panel: 5 tabs × {active, inactive} URLs. Apply
    // them to ColorManager (consumed by both nav bars) and cache the raw URL
    // list so a cold start renders the custom icons before this fetch returns.
    final navIcons = state.colors?.navIcons ?? const [];
    ColorManager.navIcons = navIcons
        .map((e) => NavIconUrls(active: e.active, inactive: e.inactive))
        .toList(growable: false);
    await HiveManager().saveData(
      KeysManager.USER_BOX,
      KeysManager.NAV_ICON_URLS_KEY,
      navIcons.map((e) => '${e.active}|${e.inactive}').toList(growable: false),
    );

    Methods().saveCurrentUtcTimeToCache(TypesCache.color);

    // Signal any already-mounted widgets that read ColorManager statics to
    // rebuild with the freshly fetched colors (e.g. a mid-session refetch
    // triggered from the layout). On cold start the tree is built AFTER this
    // completes, so the new values are already in place.
    ConstantsManager.colorsNotifier.value++;
  }

  void applyCachedColors() {
    ConstantsManager.isSvgaNavBar = HiveManager().getData<bool>(
            KeysManager.USER_BOX, KeysManager.BOTTOM_NAV_ICONS_KEY) ??
        true;

    ColorManager.restoreCachedNavIcons();

    // Migration cleanup: purge the legacy server-driven color entries from
    // Hive so no stale panel color ever lingers on devices that cached one
    // before colors became theme-pinned in code (owner decision 2026-08).
    _purgeLegacyColorCache();
  }

  /// Deletes every deprecated server-color Hive key (flat colors, `{key}_grad`
  /// gradient tokens and region backgrounds). Fire-and-forget: runs once per
  /// launch from [applyCachedColors]; deleting an absent key is a no-op.
  void _purgeLegacyColorCache() {
    const legacyColorKeys = [
      KeysManager.PRIMARY_COLOR_KEY,
      KeysManager.BOTTOM_NAV_COLOR_KEY,
      KeysManager.ACTIVE_COLOR_KEY,
      KeysManager.INACTIVE_COLOR_KEY,
      KeysManager.HEADER_COLOR_KEY,
      KeysManager.BUTTON_TEXT_COLOR_KEY,
      KeysManager.BUTTON_COLOR_KEY,
      KeysManager.TEXT_PRIMARY_COLOR_KEY,
      KeysManager.TEXT_SECONDARY_COLOR_KEY,
      KeysManager.ICON_COLOR_KEY,
      KeysManager.CARD_COLOR_KEY,
      KeysManager.PRIMARY_GRAD_KEY,
      KeysManager.HEADER_GRAD_KEY,
      KeysManager.BUTTON_TEXT_GRAD_KEY,
      KeysManager.TEXT_PRIMARY_GRAD_KEY,
      KeysManager.TEXT_SECONDARY_GRAD_KEY,
      KeysManager.ICON_GRAD_KEY,
      KeysManager.CARD_GRAD_KEY,
      KeysManager.NAV_REGION_KEY,
      KeysManager.BODY_REGION_KEY,
    ];
    for (final key in legacyColorKeys) {
      HiveManager().deleteData(KeysManager.USER_BOX, key);
    }
  }
}
