import 'package:general/src/core/index.dart';

class ColorManager {
  const ColorManager._();

  static const Color scaffoldBackgroundColor = Color(0xFFF2F0EF);
  static const Color scaffoldBackgroundColor2 = Color(0xFFEDEEF4);
  static const Color darkGreen = Color(0xFF184C27);

  static const Color lightGreen = Color(0xff53E9AB);

  static const Color yellow3 = Color(0xFFFCCC5E);
  static const Color green = Color(0xFF50BB29);
  static const Color lightOrange = Color(0xFFFFB544);
  static const Color orange3 = Color(0xFFFFc438);
  static const Color orangeIndcator = Color(0xFFFDA030);
  static const Color lightBlack = Color(0xFF1F1F1F);
  static Color veryLightBlack = Colors.white.withValues(alpha: 0.15);
  static const Color veryVeryLightBlack = Color(0xFF313131);
  static const Color black12 = Color(0xFF121212);
  static const Color backgroundBottomVip = Color(0xFF101010);
  static const Color fieldColorChat = Color(0xff282828);
  static const Color darkGreenChat = Color(0xff025144);
  static const Color greenWhatsApp = Color(0xff25D366);
  static const Color darkBlackChat = Color(0xff1D282F);
  static const Color lightBlackChat = Color(0xFF909090);
  static const Color yellow = Color(0xFFFFAD38);
  static const Color lightYellow = Color(0xFFFAE280);
  static const Color darkYellow = Color(0xFFE3AA6E);
  static const Color gray = Color(0xffD9D9D9);
  static const Color levelColor = Color(0xFFE8EFFF);

  static const Color profileCardColor = Color(0xFF903EE9);

  static const Color cardColor = Color(0xff69B7FC);
  static const Color timeColor = Color(0xffD1D1CF);
  static const Color divider = Color(0xFFf5f5f5);

  // Derived from the mutable [primary] which is set from the panel AFTER class
  // load. As getters they re-derive on every read so they track the white-label
  // primary instead of freezing the build-time brand default (the white-label
  // bug). Same pattern as [theme2TextPrimary].
  static List<Color> get luckyGiftBannerColors => [
        ColorManager.primary,
        ColorManager.primary.withValues(alpha: 0.8),
        ColorManager.primary.withValues(alpha: 0.6),
        ColorManager.transparent,
      ];

  static List<Color> get senderBallanceBannerColors => [
        ColorManager.primary,
        ColorManager.primary,
        ColorManager.primary.withValues(alpha: 0.8),
        ColorManager.primary.withValues(alpha: 0.6),
        ColorManager.primary.withValues(alpha: 0.4),
      ];

  static const List<Color> saveButtonColors = [
    Color(0xFF4C2A72),
    Color(0xFF903EE9),
  ];
  static const List<Color> hostColors = [
    Color(0xFF4192DA),
    Color(0xFF6CB9FD),
  ];
  static const List<Color> familyColors = [
    Color(0xFF01E3CF),
    Color(0xFF00D4C2),
  ];

  static const List<Color> agencyColors = [
    Color(0xFFFDA874),
    Color(0xFFF9BE9A),
  ];

  static const List<Color> gradientButtonChat = [
    Color(0xFF6DD69E),
    Color(0xFF737373),
  ];

  static const List<Color> yellowGrident = [
    Color(0xFFEEB609),
    Color(0xFFDBA514),
  ];

  static const List<Color> buttonGrident = [
    Color(0xFFF9C87C),
    Color(0xFFFF8904),
  ];

  static const List<Color> buttonGridentMain = [
    Color(0xFF4C2A72),
    Color(0xFF903EE9),
  ];

  static const List<Color> followButtonGrident = [
    Color(0xFF5e2a8c),
    Color(0xFF2f286b),
  ];

  static const List<Color> rechargeGrident = [
    Color(0xFF8A2EFF),
    Color(0xFFCCE0FF),
  ];

  static const List<Color> giveGrident = [
    Color(0xFFFFCA5E),
    Color(0xFFFFB02E),
  ];

  static const List<Color> vipBuyButtonGradient = [
    Color(0xFFFDF1AE),
    Color(0xFFFDD54C),
  ];

  static const List<Color> cashOutGradient = [
    Color(0xFFFE7E07),
    Color(0xFFFFDE67),
  ];

  static const List<Color> pinkColors = [
    Color(0xFFD18FF9),
    Color(0xFFC60077),
  ];

  static const List<Color> notificationColors = [
    Color(0xFFFFF5F5),
    Color(0xFFF6FFF0),
    Color(0xFFFEF7E6),
    Colors.white,
  ];

  static const List<Color> agencypurpleColorList = [
    Color(0xFF8A2EFF),
    Color(0xFFCCE0FF),
  ];

  static const List<Color> maleContainer = [
    Color(0xff47cbf2),
    Color(0xff61eff0),
  ];

  static const List<Color> femaleContainer = [
    Color(0xffee81de),
    Color(0xfff3acec),
  ];

  static const List<Color> cpFriends = [
    Color(0xFF6893FB),
    Color(0xFF9DDAFC),
  ];

  static const List<Color> cpCouple = [
    Color(0xFFF7568C),
    Color(0xFFF99BB8),
  ];

  static const List<Color> cpBro = [
    Color(0xFF6377F7),
    Color(0xFF9AA6FB),
  ];

  static const List<Color> cpBackgroundGradient = [
    Color(0xFFFEBCCA),
    Color(0xFFFF8C9B),
  ];

  static const List<Color> bottomCardCpRank = [
    Color(0xFFffb7dc),
    Color(0xFFff74d5),
  ];

  static const List<Color> bottomCardRankGradient = [
    Color(0xFFFFFDFB),
    Color(0xFFFCE2BA),
  ];

  static const List<Color> backgroundVipBottomWidget = [
    Color(0xff0B390B),
    Color(0xff113052),
    Color(0xff310F4F),
    Color(0xff51183E),
    Color(0xff451112),
  ];

  static const Color defaultTextVip = Color(0xFFFEDC8B);

  // ─── Invite-bonus (gold treasure) screen — theme-INDEPENDENT identity ─────
  // The unified invitation screen keeps its dark-gold look under every
  // app_ui_variant (same precedent as the VIP surfaces above).
  static const Color inviteGoldBgTop = Color(0xFF3A2000);
  static const Color inviteGoldBgBottom = Color(0xFF1B0E00);
  static const List<Color> inviteGoldCardGradient = [
    Color(0xFF4A2D02),
    Color(0xFF2A1900),
  ];

  static const List<Color> vipBackgroundColor = [
    Color(0xff082E0A),
    Color(0xff091A2D),
    Color(0xff1C092D),
    Color(0xff2A061E),
    Color(0xff2C0809),
    Color(0xff000000),
    Color(0xff000000),
    Color(0xff000000),
  ];

  // Getter (not a frozen list): derives from the mutable panel [primary] on
  // every read so it tracks the white-label brand color.
  static List<Color> get joinPurpelColors => [
        primary,
        const Color(0xFFA460D0),
        primary,
      ];
  static List<Color> receiverColors = [
    const Color(0xFF5D78FA),
    const Color(0xFFBD8BFC),
  ];
  static List<Color> roomColors = [
    const Color(0xFFD581FD),
    const Color(0xFFF499F2),
  ];

  static const List<Color> senderColors = [
    Color(0xFFFC8939),
    Color(0xFFFFD73A),
  ];

  static List<Color> backgroudContanerCoins = [
    const Color(0xFFFFE094),
    const Color(0xFFFEF8D4),
  ];

  static List<Color> backgroudContanerAgency = [
    const Color(0xFF5A5A5A).withValues(alpha: 0.82),
    const Color(0xFFBFBFBF),
  ];

  // Getter: depends on the mutable panel [primary] and [secondaryColor], so it
  // must re-derive per read instead of freezing the build-time defaults.
  static List<Color> get mainColorList => [primary, white, secondaryColor];
  static const List<Color> backgroundGradientDiamond = [
    Color(0xff4E51ED),
    Color(0xffAA51E8),
  ];

  //
  static const List<Color> backgroundGradientFamilyPro = [
    Color(0xff9181fe),
    Color(0xffcc68f0),
  ];

  static const List<Color> backgroundOtherUserRank = [
    Color(0xFFF5777B),
    Color(0xFF7791C9),
  ];

  //
  static const List<Color> roomDialogGradient = [
    Color(0xff0E0703),
    Color(0xff2D0F00),
    Color(0xff020100),
  ];

  static const Color textColor2Term = Color(0xFF666666);
  static const Color whiteGrey = Color(0xFFF8F8F9);
  static const Color whiteGrey4 = Color(0xFFBCBBBA);
  static const Color whiteGrey5 = Color(0xFFEBEAEA);
  static const Color greySubtitle = Color(0xFFBCB6B6);
  static const Color black2 = Color(0xFF2B2B2B);

  static const Color white = Color(0xFFFFFFFF);
  static const Color black = Color(0xFF000000);

  static Color get scaffoldBg {
    // [hasBodyThemeDesign] (NOT the raw flag): a stale flat-color body theme
    // must not blank the variant identity — only real image/gradient designs.
    if (hasBodyThemeDesign) return transparent;
    // theme_1's page fill is its light beige and theme_2's is its blue-white
    // off-white, so every scaffold matches its variant's identity app-wide.
    if (ConstantsManager.isTheme1) return theme1Background;
    if (ConstantsManager.isTheme2) return theme2Background;
    if (ConstantsManager.isTheme3) return theme3Background;
    // default (التصميم 1) is the golden-DARK identity: dark navy page.
    return defaultDarkBackground;
  }

  static Color get scaffoldBgSpecial =>
      hasBodyThemeDesign ? transparent : primary;

  static Color get scaffoldBgAlt {
    if (hasBodyThemeDesign) return transparent;
    if (ConstantsManager.isTheme1) return theme1BackgroundAlt;
    if (ConstantsManager.isTheme2) return theme2BackgroundAlt;
    if (ConstantsManager.isTheme3) return offWhite;
    return defaultDarkBackgroundAlt;
  }

  static const Color grey = Color(0xFF707070);
  static const Color transparent = Color(0x00000000);
  static const Color redAccount = Color(0xFFB71C1C);
  static const Color blue = Color(0xFF0091FE);

  static const Color lightDarkText = Color(0xFF3E3D3D);
  static const Color fffvTextgrey = Color(0xFF212024);
  static const Color mainTextgrey = Color(0xFF212024);
  static const Color buttonGrey = Color(0xFFe4e6e5);
  static const Color greyText = Color(0xFF757373);
  static const Color lightGray = Color(0xFFD2E2FC);
  static const Color trailingColor = Color.fromARGB(255, 184, 184, 184);
  static const Color appBarTitlegrey = Color(0xFF4D4D4D);
  static const Color grey2 = Color(0xFFC9C9C9);
  static const Color greyTabBar = Color(0xFF2E2E30);
  static const Color grey3 = Color(0xFFD9D9D9);
  static const Color greyLight = Color(0xFFF1F1F1);
  static const Color tapBarDivider = Color(0xFFE1EDFF);
  static const Color greyDark = Color(0xFF3E3E3E);
  static const Color borderColor = Color(0xFFE6E6E6);
  static const Color textAddInfo = Color(0xFFA4A4A4);
  static const Color pink = Color(0xFFEC008C);
  static const Color itemTexGreyt = Color(0xFF3C3940);

  static const baseColor = Color(0xFFE0E0E0);
  static const highlightColor = Color(0xFFF5F5F5);

  // loading color
  static const Color loadingColor = Color(0xff262E3E);
  static const Color secondaryTextColor = Color(0xffECC8DD);
  static const Color grayTextColor = Color(0xffA6A2A2);
  static const Color bottomNavBarUnselected = Color(0xFFD63B59);
  static const Color bottomNavBarSelected = Color(0xFF003FA6);
  static const Color countryYellow = Color(0xFFFFBF00);
  static const Color searchColor = Color(0xFFCCD8EB);
  static const Color indicatorColor = Color(0xFF2666CF);
  static const Color textTabBar2 = Color(0xFF828181);
  static const Color textTabBar = Color(0xFF323232);
  static const Color notificationChat1 = Color(0xFF2666CF);
  static const Color myColorMessageChat = Color(0xffAB46EA);
  static const Color senderColorMessageChat = Color(0xFFF6F6F6);
  static const Color greenChat1 = Color(0xFF0da886);
  static const Color rankTopGift = Color(0xFF67cfac);
  static const Color sendGift = Color(0xFF33e5ab);
  static const Color redIndicator = Color(0xFFFF0000);
  static const Color redIcons = Color(0xFFFE2B54);

  static const Color blueTabIndicator = Color(0xFF10A7FC);
  static const Color gold = Color(0xFFA8632A);
  static const Color gold3 = Color(0xFFE59E09);

  static const Color orange = Color(0xFFFF9428);
  static const Color orange2 = Color(0xffFFECEA);
  static const Color babyBlue2 = Color(0xFFCCD8EB);
  static const Color mainColor = Color(0xFF1A1A1A);
  static const Color formFieldColor = Color(0xFFF5F9FF);

  static const Color dailyPrizeBackground = Color(0xFF984C00);
  static const Color vipDialogGray = Color(0xFF1F1F1F);
  static const Color colorTextSup = Color(0xFFFFB4CF);
  static const lightGray1 = Color(0xFFf3f3f3);

  static const Color lightRed = Color(0xFFFF8485);
  static const Color agencyYellow = Color(0xFFE3AE6E);
  static const Color hanPurple = Color(0xFF5519FF);
  static const Color blue2 = Color(0xFF0091FE);
  static const Color darkPink = Color(0xFFF40A6D);
  static const Color lightGrey = Color(0xFFF5F5F5);
  static const Color textForgetColor = Color(0xFF454545);

  static const Color checkBoxColor = Color(0xFF29B68C);
  static const Color purchaseBottomColor = Color(0xFFA58DFF);
  static const Color lightGray99 = Color(0xFF999999);

  static const Color grayConnction = Color(0xFF212121);
  static const Color backConnction = Color(0xFF21943d);
  static const Color buleShadw = Color(0xFFC1D9FF);
  static const Color bColor = Color(0xFFEB0000);
  static const Color lightGray2 = Color(0xFFB1B1B1);
  static const Color idGreyColor = Color(0xFFA8A6A6);
  static const Color noName = Color(0xFFC5C5C5);
  static const Color grayMouce = Color(0xFF605D5D);
  static const Color grayMouce4 = Color(0xFFA4A4A5);

  static const Color supportColor = Color(0xFFFBFBFB);
  static const Color supportBorderColor = Color(0xff0bfbfb);
  static const Color greyBorderColor = Color(0xffECECEC);
  static const Color greyColor = Color(0xFFa5a7a4);
  static const Color offWhite = Color(0xFFf9fafa);
  static const Color bgLevel = Color(0xFF0c0c30);
  static const Color levelCard = Color(0xfff76bc8);
  static const Color wealthCard = Color(0xfffeb648);
  static const Color walletCard = Color(0xffffa431);
  static const Color diamondCard = Color(0xff46afff);
  static const Color diamondBottom = Color(0xff47c9fe);
  static const Color grayMain = Color(0xFFbcbcc6);
  static const Color grayLight = Color(0xfff7f7f7);
  static const Color textColorMain = Color(0xFF343434);
  static const Color inactiveColor = Color(0xFF333333);
  static const Color vipCard = Color(0xFFfef5e6);
  static const Color vipTextCard = Color(0xFFf09a37);

  static Color secondaryColor = const Color(0xFF003FA6);

  static Color whiteColor = const Color(0xFFFFFFFF);
  static Color blackColor = const Color(0xFF000000);
  static Color greyTextColor = const Color(0xFF707070);
  static Color yellowTextColor = const Color(0xFFFFAD38);
  static Color userContainer = const Color(0xFF534990);

  static const Color red = Color(0xFFA00539);
  static List<Color> luckyBoxGradient = [
    ColorManager.red,
    ColorManager.red,
    Colors.redAccent.withValues(alpha: 0.6),
    Colors.redAccent.withValues(alpha: 0.6),
  ];

  // ─── Room & Live-room pinned palette (theme-INDEPENDENT) ──────────────────
  // Owner rule: the audio-room and live-room screens NEVER change with the app
  // theme (app_ui_variant). These tokens freeze the golden identity — the exact
  // values of the default/theme_1/theme_2 branch of the shared getters above —
  // so every read inside lib/src/features/room/** and live_room/** renders the
  // same room under ANY theme, including theme_3 (NEXO). Room/live files must
  // use these instead of the theme-branching getters ([primary], [textPrimary],
  // [secondaryText], ...).
  static const Color roomGold = Color(0xFFF0D060); // = default [primary]
  static const Color roomSurface = Color(0xFF05060A); // = default [background]
  static const Color roomHeader = Color(0xFF05060A); // = default [headerColor]
  static const Color roomIcon = Color(0xFF05060A); // = default [iconColor]
  static const Color roomButtonText =
      Color(0xFF14110A); // = default [buttonTextColor]
  static const Color roomCard =
      Color(0xFFFFFFFF); // = default [surfaceCardColor]
  static const Color roomSecondaryText =
      Color(0xFF707070); // = default [secondaryText]

  /// Room text primary — replicates the NON-theme branch of [textPrimary]
  /// exactly (adaptive white/black on the server dark-mode + body-theme flags,
  /// which are NOT theme-variant driven), so rooms render byte-identical to
  /// the current default under any theme.
  static Color get roomTextPrimary =>
      _isDarkMode && isEnabled ? white : black;

  /// Room copy of [luckyGiftBannerColors], derived from [roomGold] instead of
  /// the theme-branching [primary].
  static List<Color> get roomLuckyGiftBannerColors => [
        roomGold,
        roomGold.withValues(alpha: 0.8),
        roomGold.withValues(alpha: 0.6),
        transparent,
      ];

  /// Room copy of [senderBallanceBannerColors].
  static List<Color> get roomSenderBalanceBannerColors => [
        roomGold,
        roomGold,
        roomGold.withValues(alpha: 0.8),
        roomGold.withValues(alpha: 0.6),
        roomGold.withValues(alpha: 0.4),
      ];

  /// Room copy of [mainColorList].
  static List<Color> get roomMainColorList => [roomGold, white, secondaryColor];

  /// Room copy of [bodyBackgroundGradient], pinned over [roomSurface].
  static LinearGradient get roomBodyBackgroundGradient => LinearGradient(
        begin: Alignment.topCenter,
        end: Alignment.bottomCenter,
        colors: [roomSurface, roomSurface.withValues(alpha: 0.85)],
      );
  // ───────────────────────────────────────────────────────────────────────────

  // ─── Theme-pinned identity palette ─────────────────────────────────────────
  // Owner decision (2026-08): colors NO LONGER come from the server/admin
  // panel — each UI variant ships its FIXED palette baked in code. The server
  // still drives ONLY the variant selection (app_ui_variant) and the bottom-nav
  // icons (nav_icons). Four clean branches — one full palette per variant:
  //   default → the golden-DARK identity (owner decision 2026-08: this variant
  //              is fully dark — dark navy pages/surfaces, light ink, golden
  //              accent; no more white pages with a stray blue nav);
  //   theme_1 → orange on beige (theme1* tokens, untouched);
  //   theme_2 → blue on white (theme2* tokens);
  //   theme_3 → NEXO pink (theme3* tokens).
  // Every widget that reads ColorManager picks up the selected variant's
  // palette automatically.
  static bool get _isNexo => ConstantsManager.isTheme3;
  static bool get _isTheme1 => ConstantsManager.isTheme1;
  static bool get _isTheme2 => ConstantsManager.isTheme2;

  // ─── Default (التصميم 1) dark palette — Xena night-violet family ──────────
  // Owner reference (2026-08): the Xena app screenshots. One "violet night"
  // system: near-black violet-navy pages with a subtle vertical gradient,
  // slightly lighter violet surfaces with large radii and no visible borders,
  // pure-white headings over lavender-gray secondary text, and a neon
  // violet→magenta accent for CTAs/active states. Gold stays ONLY on
  // currency/coin elements (and the pinned room/live/VIP surfaces).
  static const Color defaultDarkBackground = Color(0xFF0D0A1A);
  static const Color defaultDarkBackgroundAlt = Color(0xFF141024);
  static const Color defaultDarkSurface = Color(0xFF1E1832);
  static const Color defaultDarkSurfaceAlt = Color(0xFF241C3D);
  static const Color defaultDarkField = Color(0xFF241C3D);
  static const Color defaultDarkTextPrimary = Color(0xFFFFFFFF);
  static const Color defaultDarkTextSecondary = Color(0xFFA9A1C2);

  /// Neon violet accent — the default variant's [primary].
  static const Color defaultAccent = Color(0xFF8B5CF6);

  /// Magenta end of the accent ramp (CTAs, active tab underline).
  static const Color defaultAccentMagenta = Color(0xFFD946EF);

  /// The Xena CTA ramp: neon violet → magenta. Buttons/banners on the default
  /// variant paint this instead of a flat fill.
  static const List<Color> defaultAccentGradient = [
    Color(0xFF8B5CF6),
    Color(0xFFD946EF),
  ];

  /// Page gradient (top → bottom): near-black violet deepening into a faint
  /// violet glow, so pages read as one "violet night" instead of flat black.
  static const List<Color> defaultBgGradient = [
    Color(0xFF0D0A1A),
    Color(0xFF1A1030),
  ];

  /// Hairline border on dark surfaces (replaces the light-theme gray borders
  /// which glow harshly on the dark page).
  static const Color defaultDarkBorder = Color(0x14FFFFFF);

  /// Expressive tile fills for icon grids (profile feature grid & friends):
  /// each icon sits in a rounded tile with its own vivid color on the dark
  /// surface — the Xena signature. Cycled by index.
  static const List<Color> defaultTileColors = [
    Color(0xFF8B5CF6), // violet
    Color(0xFF22C55E), // green
    Color(0xFFEAB308), // yellow
    Color(0xFFF97316), // orange
    Color(0xFFEC4899), // pink
    Color(0xFF06B6D4), // cyan
  ];

  /// Bottom-nav inks for the default variant: near-black violet bar, neon
  /// active glow, muted violet-gray inactive.
  static const Color defaultNavBg = Color(0xFF120E22);
  static const Color defaultNavActive = Color(0xFFA78BFA);
  static const Color defaultNavInactive = Color(0xFF6E6787);

  static Color get primary => _isNexo
      ? theme3Primary
      : _isTheme1
          ? theme1Primary
          : _isTheme2
              ? theme2Primary
              : defaultAccent;
  static Color get background => _isNexo
      ? theme3Background
      : _isTheme1
          ? theme1Background
          : _isTheme2
              ? theme2Background
              : defaultDarkBackground;
  static Color get bottomNavColor => _isNexo
      ? theme3SurfaceDark
      : _isTheme1
          ? theme1NavBg
          : _isTheme2
              ? theme2NavBg
              : defaultNavBg;
  static Color get bottomNavActiveColor => _isNexo
      ? theme3Cta
      : _isTheme1
          ? theme1Primary
          : _isTheme2
              ? theme2Primary
              : defaultNavActive;
  static Color get bottomNavInactiveColor => _isNexo
      ? const Color(0x66FFFFFF)
      : _isTheme1
          ? theme1NavInactive
          : _isTheme2
              ? theme2NavInactive
              : defaultNavInactive;

  /// Ordered bottom-nav icon URLs from the admin panel, one per tab
  /// (Home, Explore/Games, Chat, Moment/World, Profile). Each entry holds an
  /// active (selected) and inactive (unselected) URL. Empty list when the panel
  /// shipped no icons — the nav bar then falls back to the bundled generic set.
  /// Populated from the colors response and the Hive cache on cold start.
  static List<NavIconUrls> navIcons = const [];

  /// The panel URLs for a given nav tab index, or null when the panel shipped no
  /// icon for that tab (caller falls back to the bundled generic asset). A URL
  /// being '' (state unset) is treated as absent for that state.
  static NavIconUrls? navIconAt(int tabIndex) {
    if (tabIndex < 0 || tabIndex >= navIcons.length) return null;
    final icon = navIcons[tabIndex];
    if (icon.active.isEmpty && icon.inactive.isEmpty) return null;
    return icon;
  }

  /// Background descriptor for the bottom-nav region. Owner decision: the nav
  /// background is theme-pinned, never server-driven — always null so both nav
  /// bars render their built-in per-theme fill (the legacy derived gradient for
  /// default/theme_1/theme_2, [theme3SurfaceDark] for theme_3). The getter is
  /// kept (instead of deleting the symbol) so the nav-bar widgets stay
  /// untouched.
  static RegionBackgroundData? get navRegion => null;

  /// Background descriptor for the body region. Theme-pinned like [navRegion]
  /// — always null; the body paints the per-theme [background].
  static RegionBackgroundData? get bodyRegion => null;

  /// The app body's background as a [LinearGradient], for surfaces that want to
  /// blend into the page instead of sitting on a flat card (the "more rooms"
  /// room cards). Theme-pinned: the light variants derive from their flat
  /// [background]; the dark default paints the Xena violet-night ramp.
  static LinearGradient get bodyBackgroundGradient =>
      (_isNexo || _isTheme1 || _isTheme2)
          ? LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [background, background.withValues(alpha: 0.85)],
            )
          : const LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: defaultBgGradient,
            );

  // ─── Gradient direction → alignment (panel/back enum, single source) ───────
  // The panel/back enum is: vertical | horizontal | diagonal_down | diagonal_up
  // | radial. This is THE one place the enum maps to Flutter alignments so the
  // region painter, the body gradient and every per-color token getter all draw
  // along the exact axis the owner saved (fixing the round-trip bug where the
  // saved direction was dropped and everything rendered top->bottom).

  /// The (begin, end) alignment pair for a saved [direction], with [reverse]
  /// swapping the two ends. Unknown directions (and `radial`, which has no
  /// begin/end notion) default to the vertical top->bottom axis — callers that
  /// can render a true radial use [buildRegionGradient] instead.
  static (Alignment begin, Alignment end) gradientAlignment(
    String direction,
    bool reverse,
  ) {
    final Alignment begin;
    final Alignment end;
    switch (direction) {
      case 'horizontal':
        begin = Alignment.centerLeft;
        end = Alignment.centerRight;
        break;
      case 'diagonal_down':
        begin = Alignment.topLeft;
        end = Alignment.bottomRight;
        break;
      case 'diagonal_up':
        begin = Alignment.bottomLeft;
        end = Alignment.topRight;
        break;
      case 'vertical':
      case 'radial':
      default:
        begin = Alignment.topCenter;
        end = Alignment.bottomCenter;
        break;
    }
    return reverse ? (end, begin) : (begin, end);
  }

  /// Build the panel gradient for [colors] along [direction]. Returns a
  /// [RadialGradient] for `radial` (where [reverse] reverses the color order,
  /// since a radial has no begin/end to swap) and a [LinearGradient] otherwise.
  /// Callers pass already-parsed [Color]s (>= 1); a single color is duplicated so
  /// the gradient is always valid.
  static Gradient buildRegionGradient(
    List<Color> colors,
    String direction,
    bool reverse,
  ) {
    final stops = colors.length == 1 ? [colors.first, colors.first] : colors;
    if (direction == 'radial') {
      return RadialGradient(
        colors: reverse ? stops.reversed.toList(growable: false) : stops,
      );
    }
    final (begin, end) = gradientAlignment(direction, reverse);
    return LinearGradient(begin: begin, end: end, colors: stops);
  }

  // ─── Per-color gradient getters (theme-pinned) ─────────────────────────────
  // Owner decision: no server-driven per-color gradients. The light variants
  // return null so their call sites (main_button, button_widget, profile
  // cards, daily prize, ...) take the existing solid-color path. The dark
  // default returns the Xena violet→magenta CTA ramp so every primary
  // button/banner glows instead of sitting flat.
  static Gradient? get primaryGradient => (_isNexo || _isTheme1 || _isTheme2)
      ? null
      : const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: defaultAccentGradient,
        );
  static bool get primaryIsGradient => !(_isNexo || _isTheme1 || _isTheme2);

  static Gradient? get headerGradient => null;
  static bool get headerIsGradient => false;

  static Gradient? get buttonTextGradient => null;
  static bool get buttonTextIsGradient => false;

  static Gradient? get textPrimaryGradient => null;
  static bool get textPrimaryIsGradient => false;

  static Gradient? get textSecondaryGradient => null;
  static bool get textSecondaryIsGradient => false;

  static Gradient? get iconGradient => null;
  static bool get iconIsGradient => false;

  static Gradient? get cardGradient => null;
  static bool get cardIsGradient => false;

  /// A [BoxDecoration] for an ELEVATED card that paints the panel
  /// [cardGradient] when the owner saved a `card_color_grad` gradient and falls
  /// back to the flat [surfaceCardColor] otherwise. Centralizes the
  /// "BoxDecoration rejects both color and gradient" rule (color is null while a
  /// gradient is present) so every card surface renders the panel gradient with
  /// the exact colors/order/direction/reverse the owner saved, or the flat
  /// color (never white) when the token is solid/absent.
  static BoxDecoration cardDecoration({
    BorderRadiusGeometry? borderRadius,
    BoxBorder? border,
    List<BoxShadow>? boxShadow,
    BoxShape shape = BoxShape.rectangle,
  }) {
    final gradient = cardGradient;
    return BoxDecoration(
      color: gradient == null ? surfaceCardColor : null,
      gradient: gradient,
      borderRadius: shape == BoxShape.rectangle ? borderRadius : null,
      border: border,
      boxShadow: boxShadow,
      shape: shape,
    );
  }

  /// A [BoxDecoration] for a PRIMARY accent/CTA surface that paints the panel
  /// [primaryGradient] when the owner saved an `app_primary_color_grad` gradient
  /// (including a `radial`) and falls back to the flat [primary] otherwise.
  /// Same color XOR gradient rule as [cardDecoration].
  static BoxDecoration primaryDecoration({
    BorderRadiusGeometry? borderRadius,
    BoxBorder? border,
    List<BoxShadow>? boxShadow,
    BoxShape shape = BoxShape.rectangle,
  }) {
    final gradient = primaryGradient;
    return BoxDecoration(
      color: gradient == null ? primary : null,
      gradient: gradient,
      borderRadius: shape == BoxShape.rectangle ? borderRadius : null,
      border: border,
      boxShadow: boxShadow,
      shape: shape,
    );
  }

  /// Restore the panel nav-icon URLs from the Hive cache (list of
  /// "active|inactive" strings) so the bar shows the custom icons on cold start
  /// before the colors response returns. No-op when nothing was cached.
  static void restoreCachedNavIcons() {
    final cached = HiveManager()
        .getData<List>(KeysManager.USER_BOX, KeysManager.NAV_ICON_URLS_KEY);
    if (cached == null) return;
    navIcons = cached.map<NavIconUrls>((e) {
      final parts = '$e'.split('|');
      return NavIconUrls(
        active: parts.isNotEmpty ? parts[0] : '',
        inactive: parts.length > 1 ? parts[1] : '',
      );
    }).toList(growable: false);
  }

  /// Header/top-bar accent (tab indicators, header labels). Theme-pinned.
  /// theme_1/theme_2 keep the dark ink here (NOT the accent): it colors tab
  /// labels / indicators sitting on the light page, where dark stays readable.
  /// default is a dark variant, so its header ink is LIGHT.
  static Color get headerColor => hasBodyThemeDesign
      ? (_isDarkMode ? white : black)
      : _isNexo
          ? theme3SurfaceDark
          : _isTheme1
              ? theme1TextPrimary
              : _isTheme2
                  ? theme2TextPrimary
                  : defaultDarkTextPrimary;

  /// Text ON primary-colored buttons. Theme-pinned: white on every variant's
  /// accent — theme_1 orange, theme_2 blue, NEXO pink and the default's neon
  /// violet→magenta ramp.
  static Color get buttonTextColor => white;

  /// Tint for monochrome ICONS (top-bar search/trophy, discover filter arrow,
  /// profile feature-icon assets, search-field icon). Theme-pinned so icons
  /// stay visible on each variant's surfaces: brand dark on the light themes,
  /// light ink on the dark default.
  static Color get iconColor => hasBodyThemeDesign
      ? (_isDarkMode ? white : black)
      : _isNexo
          ? theme3SurfaceDark
          : _isTheme1
              ? theme1TextPrimary
              : _isTheme2
                  ? theme2TextPrimary
                  : defaultDarkTextPrimary;

  /// Surface color for ELEVATED cards (the "more rooms" room cards in the exit
  /// sheet, the profile feature grids & info cards). A tier distinct from
  /// [background] (the page fill): a card painted with the page color would
  /// visually merge into the page. Theme-pinned: white on the light variants
  /// ([theme3Card], [theme2Card] and [theme1Card]), an elevated dark navy on
  /// the dark default. NOTE: separate from the legacy const [cardColor] (a
  /// fixed CP brand blue).
  static Color get surfaceCardColor => hasBodyThemeDesign
      ? (_isDarkMode ? defaultDarkSurface : white)
      : _isNexo
          ? theme3Card
          : _isTheme1
              ? theme1Card
              : _isTheme2
                  ? theme2Card
                  : defaultDarkSurface;

  // ─── Adaptive Text Color ───────────────────────────────────────
  static bool get _isDarkMode =>
      HiveManager().getData<bool>(
        KeysManager.USER_BOX,
        KeysManager.IS_DARK_MODE_ENABLED_KEY,
      ) ??
      false;

  /// Raw panel flag (is_body_theme_enabled). Room-pinned getters keep reading
  /// this legacy flag; PAGE surfaces/inks must use [hasBodyThemeDesign].
  static bool get isEnabled =>
      HiveManager().getData<bool>(
        KeysManager.USER_BOX,
        KeysManager.IS_BODY_THEME_ENABLED_KEY,
      ) ??
      false;

  /// TRUE only when the panel body theme should actually paint the pages: the
  /// flag is on AND the cached background is a real DESIGN (image/gradient).
  ///
  /// The legacy flat-COLOR mode was panel color theming — killed by the
  /// 2026-08 pinned-palette decision (colors ship in code; the panel drives
  /// only the variant + nav icons) — so it no longer overrides the variant
  /// identity. A stale `{type: color, #FFFFFF}` row on the panel was blanking
  /// the dark default into all-white pages under its white ink (owner report
  /// 2026-08-09: register/complete-info text invisible).
  ///
  /// BODY-THEME OVERRIDE RULE: while TRUE, the page background is panel-driven
  /// ([scaffoldBg] goes transparent), so the ink/surface getters follow the
  /// panel's is_dark_mode_enabled flag instead of the variant palette.
  static bool get hasBodyThemeDesign {
    if (!isEnabled) return false;
    final type = HiveManager().getData<String>(
          KeysManager.USER_BOX,
          KeysManager.BODY_THEME_TYPE_KEY,
        ) ??
        '';
    return type == 'image' || type == 'gradient';
  }

  /// Fill for text fields / search boxes that used to hardcode the light
  /// [grayLight]: keeps that exact look on the light variants and switches to
  /// the dark field tone on the dark default so [textPrimary] stays readable.
  static Color get fieldFill => hasBodyThemeDesign
      ? (_isDarkMode ? defaultDarkField : grayLight.withValues(alpha: 0.7))
      : (_isNexo || _isTheme1 || _isTheme2)
          ? grayLight.withValues(alpha: 0.7)
          : defaultDarkField;

  /// Hairline border/divider on cards & rows. Theme-pinned: the legacy light
  /// gray on the light variants (identical look), a faint white hairline on the
  /// dark default so borders don't glow harshly over the violet surfaces.
  static Color get cardBorderColor => hasBodyThemeDesign
      ? (_isDarkMode ? defaultDarkBorder : whiteGrey5)
      : (_isNexo || _isTheme1 || _isTheme2)
          ? whiteGrey5
          : defaultDarkBorder;

  /// Secondary/subtitle text color. Theme-pinned; body-theme adaptive.
  static Color get secondaryText => hasBodyThemeDesign
      ? (_isDarkMode ? defaultDarkTextSecondary : grey)
      : _isNexo
          ? theme3TextSecondary
          : _isTheme1
              ? theme1TextSecondary
              : _isTheme2
                  ? theme2TextSecondary
                  : defaultDarkTextSecondary;

  /// Text that sits on a FIXED dark surface (level cards, dark gradients, media
  /// overlays). Stays white regardless of the theme text colors so it never
  /// becomes invisible on a light palette. Theme-independent by design.
  static const Color onDark = Color(0xFFFFFFFF);

  /// Primary text color — the NEXO deep plum on theme_3, theme_1's warm
  /// dark-brown ink on its beige page, theme_2's cool dark-navy ink on its
  /// white page, and light ink on the dark default (its pages/surfaces are
  /// dark navy, so text is always light there).
  ///
  /// Body-theme override: when the panel body theme is ON the page is the
  /// panel background (light or dark by is_dark_mode_enabled), so the ink
  /// follows that flag — the legacy adaptive white/black — regardless of the
  /// UI variant. Without it the dark default painted its white ink over a
  /// white panel background (invisible text on login/register).
  static Color get textPrimary => hasBodyThemeDesign
      ? (_isDarkMode ? white : black)
      : _isNexo
          ? theme3TextPrimary
          : _isTheme1
              ? theme1TextPrimary
              : _isTheme2
                  ? theme2TextPrimary
                  : defaultDarkTextPrimary;
  /// Dialog/card fill that tracks the ink family ([textPrimary] must stay
  /// readable on it). Was a raw white/black flip that ignored the variant —
  /// a white card under the dark default's white ink. Now simply the elevated
  /// surface of whatever palette is active.
  static Color get backgroundDarkLight => surfaceCardColor;

  // ───────────────────────────────────────────────────────────────
  // ─── Theme1 App Colors — orange on beige ───────────────────────
  // theme_1's identity, extracted from its own shipped screens/assets
  // (the only per-theme_1 color sources in the repo):
  //  - assets/images/bg_mine_wallet_1.webp (the "My wallet" banner on the
  //    theme_1 profile): a red-orange -> amber gradient; its dominant mid
  //    stop ~#FB8856 / right stop ~#FFA040 → brand primary #FF7A2E, a
  //    saturated warm orange between the two.
  //  - the theme_1 profile "check" button ships titleColor:
  //    ColorManager.orange (#FF9428) on white — same warm-orange family.
  //  - assets/images/bg_mine.svga (theme_1 profile header): maroon
  //    arabesque (~#8F2E18) over a golden trim — the ink tone below keeps
  //    that warm-brown character instead of a neutral black.
  // Background is a light warm beige/cream so the white cards
  // ([theme1Card]) still read as elevated on it.
  static const Color theme1Primary = Color(0xFFFF7A2E);
  static const Color theme1Background = Color(0xFFFAF3E8);
  static const Color theme1BackgroundAlt = Color(0xFFF5EDDF);
  static const Color theme1Card = Color(0xFFFFFFFF);

  /// Warm near-black ink for primary text/icons on the beige page —
  /// derived from the maroon of bg_mine.svga darkened to AA contrast.
  static const Color theme1TextPrimary = Color(0xFF3B2313);
  static const Color theme1TextSecondary = Color(0xFF8A7462);

  /// Bottom-nav fill: theme_1's nav is a light bar matching the page (its
  /// icon set is colored line-icons on light), with orange active tint and
  /// warm-grey inactive.
  static const Color theme1NavBg = Color(0xFFFFFBF4);
  static const Color theme1NavInactive = Color(0xFFB09C89);
  // ───────────────────────────────────────────────────────────────
  // ─── Theme2 App Colors — blue on white ─────────────────────────
  // theme_2's identity (owner decision 2026-08): a clean modern blue-indigo
  // on a bright off-white page. Every theme_2 surface derives from this one
  // family — no leftover dark-violet or orange stops.
  static const Color theme2Primary = Color(0xFF3D5AFE);
  static const Color theme2Background = Color(0xFFF7F9FC);
  static const Color theme2BackgroundAlt = Color(0xFFEFF3F9);
  static const Color theme2Card = Color(0xFFFFFFFF);
  static const Color theme2TextPrimary = Color(0xFF1A2233);
  static const Color theme2TextSecondary = Color(0xFF64748B);

  /// Bottom-nav fill: a light bar matching the page, blue active tint and
  /// cool-grey inactive.
  static const Color theme2NavBg = Color(0xFFFFFFFF);
  static const Color theme2NavInactive = Color(0xFF94A0B4);

  static const Color theme2TabInactive = Color(0xFF8A94A6);
  static const Color theme2FilterBg = Color(0xFFEAEEF6);
  static const Color theme2FilterActive = Color(0xFF3D5AFE);
  static const Color theme2AccentLight = Color(0xFF7C93FF);
  static const Color theme2PartyBadge = Color(0xFF3D5AFE);

  /// Semantic live-signal red on the room cards (not a palette surface).
  static const Color theme2SignalBars = Color(0xFFFF4444);

  // Decorative per-category fills for the discover game cards (white text on
  // top); accents, not theme surfaces.
  static const Color theme2GameCardBg1 = Color(0xFF2563EB);
  static const Color theme2GameCardBg2 = Color(0xFFD97706);
  static const Color theme2GameCardBg3 = Color(0xFFEC4899);
  static const Color theme2GameCardBg4 = Color(0xFF059669);

  static const List<Color> theme2TabGradient = [
    Color(0xFF3D5AFE),
    Color(0xFF7C93FF),
  ];
  // ───────────────────────────────────────────────────────────────

  // ─── Reels comments composer — DEFAULT variant legacy surfaces ─────────────
  // The default golden variant shipped the reels comment sheet with this dark
  // bar (must not change a pixel). They used to live under the theme2* names;
  // theme_2 is now blue-on-white, so ONLY the default branch reads these.
  static const Color reelsComposerBg = Color(0xFF141230);
  static const Color reelsComposerField = Color(0xFF1E1A3A);
  static const Color reelsComposerBorder = Color(0xFF2D2755);
  static const Color reelsComposerAccent = Color(0xFF8B5CF6);
  static const Color reelsComposerHint = Color(0xFF9CA3AF);
  // ───────────────────────────────────────────────────────────────

  // ─── Theme3 App Colors — pink, not purple (owner correction 2026-08) ──────
  /// NEXO brand primary — the solid value the shared [primary] getter returns
  /// under theme_3. Pink family throughout: the former violet #8B5CF6 is gone.
  static const Color theme3Primary = Color(0xFFEC4899);
  static const Color theme3Background = Color(0xFFFDF2F8);
  static const Color theme3SurfaceDark = Color(0xFF4A1033);
  static const Color theme3Card = Color(0xFFFFFFFF);
  static const Color theme3CardDark = Color(0x14FFFFFF);
  static const Color theme3Cta = Color(0xFFEC4899);
  static const Color theme3Gold = Color(0xFFF59E0B);
  static const Color theme3ChipInactive = Color(0x2EFFFFFF);

  static const List<Color> theme3PrimaryGradient = [
    Color(0xFFEC4899),
    Color(0xFFF472B6),
  ];
  static const List<Color> theme3CtaGradient = [
    Color(0xFFEC4899),
    Color(0xFFF472B6),
  ];

  // Profile screen chips/banner (Phase 2).
  static const List<Color> theme3WealthGradient = [
    Color(0xFFFB923C),
    Color(0xFFF97316),
  ];
  static const List<Color> theme3VipGoldGradient = [
    Color(0xFFFDE68A),
    Color(0xFFF59E0B),
  ];
  static const List<Color> theme3VipBannerGradient = [
    Color(0xFFF472B6),
    Color(0xFFEC4899),
  ];

  // ─── Theme3 text colors — theme-pinned (owner decision: no panel colors) ───
  // NEXO's fixed text palette: deep plum primary on the light blush
  // background, muted slate secondary.
  static const Color theme3TextPrimary = Color(0xFF3B1026);
  static const Color theme3TextSecondary = Color(0xFF6B7280);
  // ───────────────────────────────────────────────────────────────

  static LinearGradient gradientLinearTabBar(Color base) {
    // theme_1/theme_2 paint white label text ([buttonTextColor]) on this
    // gradient, so the pale-yellow middle stop would make it unreadable — use
    // a solid brand ramp instead (orange for theme_1, blue for theme_2).
    if (_isTheme1) {
      return const LinearGradient(
        colors: [
          Color(0xFFFF7A2E),
          Color(0xFFFF9C4A),
          Color(0xFFFFA95C),
        ],
        begin: AlignmentDirectional.centerEnd,
        end: AlignmentDirectional.centerStart,
      );
    }
    if (_isTheme2) {
      return const LinearGradient(
        colors: [
          Color(0xFF3D5AFE),
          Color(0xFF5C74FF),
          Color(0xFF7C93FF),
        ],
        begin: AlignmentDirectional.centerEnd,
        end: AlignmentDirectional.centerStart,
      );
    }
    const middleYellow = Color(0xFFF7FFBC);

    return LinearGradient(
      colors: [
        base,
        middleYellow,
        base.withValues(alpha: 0.5),
      ],
      begin: AlignmentDirectional.centerEnd,
      end: AlignmentDirectional.centerStart,
    );
  }
}

/// Active/inactive bottom-nav icon URLs for one tab, sourced from the admin
/// panel. A lightweight holder kept here so [ColorManager] stays free of any
/// feature-layer import.
class NavIconUrls {
  final String active;
  final String inactive;

  const NavIconUrls({this.active = '', this.inactive = ''});
}
