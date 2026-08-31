import 'package:general/src/core/index.dart';
import 'package:general/src/features/reels/presentation/main_reels_page/reels_upload_request.dart';

/// Theme3 (NEXO) Bottom Navigation Bar — floating glass pill bar spanning
/// (almost) the full width, with a center-raised pink circular action button
/// that sits ABOVE the pill (outside its clip) so it never gets cropped.
/// Reuses the same panel-driven [NavBarIcon]/[RegionBackground] plumbing as
/// [Theme2BottomNavBar]; only the shell styling (glass pill + raised center
/// button) differs.
class Theme3BottomNavBar extends StatelessWidget {
  final int currentIndex;
  final ValueChanged<int> onTap;

  const Theme3BottomNavBar({
    super.key,
    required this.currentIndex,
    required this.onTap,
  });

  static const double _barHeight = 66;
  static const double _centerSize = 54;

  /// Index mapping, kept consistent with Theme3LayoutPage._pageBuilders:
  ///   [Home, (Moment if isShowMoment), (Reels if isReelsVisible), Chat, Profile]
  /// In RTL the first child renders rightmost, so Chat (right after Reels) shows
  /// to the LEFT of the centered Reels.
  int get _momentIndex => ConstantsManager.isShowMoment ? 1 : -1;
  int get _reelsIndex {
    if (!ConstantsManager.isReelsVisible) return -1;
    return ConstantsManager.isShowMoment ? 2 : 1;
  }

  int get _chatIndex {
    var index = 1;
    if (ConstantsManager.isShowMoment) index++;
    if (ConstantsManager.isReelsVisible) index++;
    return index;
  }

  int get _profileIndex => _chatIndex + 1;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(12.w, 0, 12.w, 12.h),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: _barHeight.h + 18.h,
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Positioned(
                left: 0,
                right: 0,
                bottom: 0,
                child: Container(
                  height: _barHeight.h,
                  // Shadow only — no fill/border here. The rounded fill lives
                  // one level down, inside the ClipRRect, so the shadow (which
                  // must paint OUTSIDE the clipped shape) is never itself
                  // clipped.
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(32.r),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.25),
                        blurRadius: 20,
                        offset: const Offset(0, 8),
                      ),
                    ],
                  ),
                  // A single ClipRRect owns the pill's rounded shape; both the
                  // fill (panel region OR the plain theme3SurfaceDark fallback)
                  // and the icon row are clipped to it together. Previously the
                  // fill lived OUTSIDE the clip (as this Container's `color`)
                  // while only the icon row was clipped inside — since a
                  // [RegionBackground] solid/gradient fill paints a
                  // sharp-cornered rectangle, that produced two visibly
                  // overlapping layers (a rounded strip behind a square one)
                  // whenever the admin panel had a nav region configured.
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(32.r),
                    child: RegionBackground(
                      descriptor: ColorManager.navRegion,
                      fallback: (_) =>
                          Container(color: ColorManager.theme3SurfaceDark),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          // Home (panel nav-icon index 0)
                          _buildNavItem(
                            context: context,
                            tabIndex: 0,
                            fallbackIcon: AssetsManager.icHome,
                            isSelected: currentIndex == 0,
                            onTap: () => onTap(0),
                          ),
                          // Moment (if enabled) - placed before Reels (panel nav-icon index 3)
                          if (ConstantsManager.isShowMoment)
                            _buildNavItem(
                              context: context,
                              tabIndex: 3,
                              fallbackIcon: AssetsManager.icWorld,
                              isSelected: currentIndex == _momentIndex,
                              onTap: () => onTap(_momentIndex),
                            ),
                          // Reserved center slot: keeps the other icons evenly
                          // spread; the actual raised button renders above,
                          // outside this ClipRRect, so it is never cropped.
                          if (ConstantsManager.isReelsVisible)
                            const Expanded(child: SizedBox.shrink()),
                          // Chat (immediately after Reels -> left of Reels in RTL)
                          // (panel nav-icon index 2)
                          _buildNavItem(
                            context: context,
                            tabIndex: 2,
                            fallbackIcon: AssetsManager.icBubble,
                            isSelected: currentIndex == _chatIndex,
                            onTap: () => onTap(_chatIndex),
                          ),
                          // Profile (panel nav-icon index 4)
                          _buildNavItem(
                            context: context,
                            tabIndex: 4,
                            fallbackIcon: AssetsManager.icProfile,
                            isSelected: currentIndex == _profileIndex,
                            onTap: () => onTap(_profileIndex),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
              if (ConstantsManager.isReelsVisible)
                Positioned(
                  top: 0,
                  left: 0,
                  right: 0,
                  child: Center(
                    child: _buildRaisedCenterItem(
                      context: context,
                      isUpload: currentIndex == _reelsIndex,
                      onTap: () => onTap(_reelsIndex),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  /// The raised, pink-gradient circular center action (Reels slot). Morphs
  /// into a TikTok-style "+" (upload) when the Reels tab is already active.
  /// Sits outside the pill's [ClipRRect] so it is never cropped.
  Widget _buildRaisedCenterItem({
    required BuildContext context,
    required bool isUpload,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: isUpload ? () => reelsUploadRequest.value++ : onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        height: _centerSize.h,
        width: _centerSize.w,
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            colors: ColorManager.theme3CtaGradient,
            begin: AlignmentDirectional.topStart,
            end: AlignmentDirectional.bottomEnd,
          ),
          shape: BoxShape.circle,
          border: Border.all(color: ColorManager.theme3Background, width: 3),
          boxShadow: [
            BoxShadow(
              color: ColorManager.theme3Cta.withValues(alpha: 0.45),
              blurRadius: 14,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: Icon(
          isUpload ? Icons.add : Icons.play_arrow_rounded,
          color: ColorManager.white,
          size: 26.sp,
        ),
      ),
    );
  }

  Widget _buildNavItem({
    required BuildContext context,
    required int tabIndex,
    required String fallbackIcon,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        behavior: HitTestBehavior.opaque,
        child: Center(
          child: NavBarIcon(
            tabIndex: tabIndex,
            isSelected: isSelected,
            fallbackAsset: fallbackIcon,
            size: 24.h,
            activeColor: ColorManager.theme3Cta,
            inactiveColor: ColorManager.white.withValues(alpha: 0.4),
          ),
        ),
      ),
    );
  }
}
