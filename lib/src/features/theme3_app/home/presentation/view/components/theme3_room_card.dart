import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/on_multiable_tab.dart';
import 'package:general/src/features/home/domain/entities/room_entity.dart';
import 'package:general/src/features/home/presentation/home/view/widgets/lock_pk_icon.dart';

/// Theme3 (NEXO) room card — full-bleed cover photo, rounded-xl, country flag
/// chip top-start, viewers chip top-end, type badge bottom-start. Navigation
/// and tap-guard logic are byte-equivalent to [Theme2RoomCard] — visuals only.
class Theme3RoomCard extends StatelessWidget {
  final RoomEntity roomEntity;

  /// Overrides the default tap behaviour. When null, falls back to the
  /// audio-only default (ignores live rooms, enters audio rooms). The Live tab
  /// passes its own handler here to enter the live room.
  final VoidCallback? onTap;

  const Theme3RoomCard({
    required this.roomEntity,
    this.onTap,
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    // 2-col grid: 10.w padding each side + 10.w crossAxisSpacing between columns.
    // Logical tile width drives the cover's ResizeImage decode target so the
    // cover decodes at ~tile size instead of full network resolution.
    final coverDecodeWidth = (ScreenUtil().screenWidth - 30.w) / 2;
    return MultiTapCard(
      onTap: () {
        if (onTap != null) {
          onTap!();
          return;
        }
        // Video live is removed: live rooms are not enterable. Ignore the tap.
        if (roomEntity.streamType == "live") return;
        if (di<FetchUserDataBloc>().state.reqState.isLoaded) {
          di<RoomStateManager>().navigateToRoom(
            RoomEntryRequest(
              context: context,
              roomData: roomEntity,
              isLive: false,
            ),
          );
        }
      },
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Room Cover Image
          Expanded(
            child: ClipRRect(
              borderRadius: 16.radius,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  // Cover — falls back to the host's avatar when the room has
                  // no cover, and to name initials as the last resort. Never
                  // the black app-logo placeholder.
                  ImageViewWidget(
                    url: (roomEntity.cover ?? '').isNotEmpty
                        ? roomEntity.cover!
                        : roomEntity.ownerImage ?? '',
                    boxFit: BoxFit.cover,
                    width: coverDecodeWidth,
                    displayName: roomEntity.name ?? '',
                  ),

                  // Top-start: ROOM LEVEL badge (owner decision 2026-08-08).
                  // This corner used to hold the country-flag chip, which
                  // rendered as a grey person placeholder whenever the flag
                  // image failed — the owner wants the room's level here
                  // instead, matching Theme2RoomCard's top-start level badge.
                  // Level 0 / missing / failed badge → default first-level
                  // pill, NEVER a grey placeholder.
                  PositionedDirectional(
                    top: 8.h,
                    start: 8.w,
                    child: _RoomLevelBadge(
                      levelImage: roomEntity.roomLevelImage,
                    ),
                  ),

                  // Top-end: viewers chip
                  PositionedDirectional(
                    top: 8.h,
                    end: 8.w,
                    child: Container(
                      padding: EdgeInsets.symmetric(
                          horizontal: 7.w, vertical: 3.h),
                      decoration: BoxDecoration(
                        color: ColorManager.black.withValues(alpha: 0.45),
                        borderRadius: 12.radius,
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.remove_red_eye_outlined,
                            color: ColorManager.white,
                            size: 12.sp,
                          ),
                          3.wBox,
                          Text(
                            '${roomEntity.visitorsCount ?? 0}',
                            // Sits on a FIXED dark cover overlay — stays light
                            // regardless of the admin text palette.
                            style: TextStyle(
                              color: ColorManager.white,
                              fontSize: 11.sp,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  // Lock/PK icons
                  PositionedDirectional(
                    top: 34.h,
                    end: 8.w,
                    child: LockAndPkIcon(
                      isPK: roomEntity.isPK ?? false,
                      isPassword: roomEntity.passwordStatus ?? false,
                      isHasLuckyBox: roomEntity.hasLuckyBox ?? false,
                    ),
                  ),

                  // Bottom gradient (keeps the type badge legible)
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    child: Container(
                      height: 44.h,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.only(
                          bottomLeft: Radius.circular(16.r),
                          bottomRight: Radius.circular(16.r),
                        ),
                        gradient: LinearGradient(
                          begin: Alignment.bottomCenter,
                          end: Alignment.topCenter,
                          colors: [
                            ColorManager.black.withValues(alpha: 0.55),
                            ColorManager.transparent,
                          ],
                        ),
                      ),
                    ),
                  ),

                  // Bottom-start: type badge pill
                  PositionedDirectional(
                    bottom: 8.h,
                    start: 8.w,
                    child: _TypeBadge(streamType: roomEntity.streamType),
                  ),
                ],
              ),
            ),
          ),

          6.hBox,

          // Room name
          Padding(
            padding: EdgeInsetsDirectional.only(start: 2.w, end: 2.w),
            child: Text(
              roomEntity.name ?? '',
              style: TextStyle(
                color: ColorManager.theme3TextPrimary,
                fontSize: 12.sp,
                fontWeight: FontWeight.w600,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),

          // Room intro / description
          if (roomEntity.roomIntro != null && roomEntity.roomIntro!.isNotEmpty)
            Padding(
              padding: EdgeInsetsDirectional.only(start: 2.w, end: 2.w),
              child: Text(
                roomEntity.roomIntro!,
                style: TextStyle(
                  color: ColorManager.theme3TextSecondary,
                  fontSize: 10.sp,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
        ],
      ),
    );
  }
}

/// Room LEVEL badge for the card's top-start corner. Renders the server's
/// level-badge image (same source [Theme2RoomCard] shows: `room_level_image`)
/// when the room has one; a level-0 room (empty url) or a failed download
/// falls back to the default first-level pill below — never the grey
/// person placeholder.
class _RoomLevelBadge extends StatefulWidget {
  final String? levelImage;

  const _RoomLevelBadge({this.levelImage});

  @override
  State<_RoomLevelBadge> createState() => _RoomLevelBadgeState();
}

class _RoomLevelBadgeState extends State<_RoomLevelBadge> {
  bool _failed = false;

  @override
  void didUpdateWidget(covariant _RoomLevelBadge oldWidget) {
    super.didUpdateWidget(oldWidget);
    // Card recycled for another room: give the new url a fresh attempt.
    if (oldWidget.levelImage != widget.levelImage) _failed = false;
  }

  @override
  Widget build(BuildContext context) {
    final url = widget.levelImage ?? '';
    if (url.isNotEmpty && !_failed) {
      return ImageViewWidget(
        url: url,
        boxFit: BoxFit.contain,
        width: 34.w,
        height: 22.h,
        // No shimmer while loading and no placeholder frame on failure —
        // isGift collapses the error frame to nothing, and detectError swaps
        // this subtree to the default pill on the next frame.
        isStopLoadingAndError: false,
        isGift: true,
        detectError: () {
          if (mounted) setState(() => _failed = true);
        },
      );
    }

    // Default first-level pill (level 0 / no badge / download failed).
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 7.w, vertical: 3.h),
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: ColorManager.theme3CtaGradient),
        borderRadius: 12.radius,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.workspace_premium_rounded,
            color: ColorManager.white,
            size: 11.sp,
          ),
          2.wBox,
          Text(
            'Lv.1',
            // Sits on the FIXED pink CTA gradient — stays light regardless of
            // the admin text palette.
            style: TextStyle(
              color: ColorManager.white,
              fontSize: 10.sp,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _TypeBadge extends StatelessWidget {
  final String? streamType;

  const _TypeBadge({this.streamType});

  @override
  Widget build(BuildContext context) {
    final isLive = streamType == "live";
    final label = isLive
        ? StringManager.live.tr()
        : StringManager.party.tr();

    return Container(
      padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 3.h),
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: ColorManager.theme3CtaGradient),
        borderRadius: 12.radius,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            isLive ? Icons.videocam_rounded : Icons.mic_rounded,
            color: ColorManager.white,
            size: 11.sp,
          ),
          3.wBox,
          Text(
            label,
            style: TextStyle(
              color: ColorManager.white,
              fontSize: 10.sp,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}