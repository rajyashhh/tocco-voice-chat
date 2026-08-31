import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/body_theme_background.dart';
import 'package:general/src/features/home/data/model/my_rooms_model.dart';
import 'package:general/src/features/home/home.dart';

/// TikTok-style go-live entry.
///
/// The header camera icon opens a SMALL chooser dialog (audio room / live
/// broadcast) instead of the old "start a show" page. Picking the broadcast
/// goes straight to the camera (the live room screen in host preview): no
/// create-room form — a live "room" still exists backend/admin-side, but it is
/// created silently here with a default title the host can edit on the pre-live
/// screen.
class GoLiveFlow {
  GoLiveFlow._();

  static bool _creating = false;

  /// The small audio/live chooser. [rooms] is the user's owned shows
  /// (from the home header's FetchMyRoomDataBloc state).
  ///
  /// Shown as a POPOVER anchored to the tapped camera icon ([context] is the
  /// icon's build context): it scales/fades out of the icon itself instead of
  /// sliding a bottom sheet up from the far edge of the screen.
  static Future<void> showStartDialog(
    BuildContext context,
    MyRoomsModel? rooms,
  ) {
    final ar = Methods.getLang() == 'ar';

    // Anchor geometry (global coordinates of the camera icon).
    final screen = MediaQuery.of(context).size;
    Offset anchor = Offset(16.w, kToolbarHeight + 24.h);
    Size anchorSize = Size.zero;
    final box = context.findRenderObject() as RenderBox?;
    if (box != null && box.hasSize) {
      anchor = box.localToGlobal(Offset.zero);
      anchorSize = box.size;
    }

    final popoverWidth = 250.w;
    final left = (anchor.dx + anchorSize.width / 2 - popoverWidth / 2)
        .clamp(8.0, screen.width - popoverWidth - 8.0);
    final top = anchor.dy + anchorSize.height + 8.h;
    // Transform origin: the icon's center, expressed in the popover's space —
    // so the card visibly grows out of the icon.
    final originX =
        ((anchor.dx + anchorSize.width / 2 - left) / popoverWidth) * 2 - 1;

    return showGeneralDialog<void>(
      context: context,
      barrierDismissible: true,
      barrierLabel: '',
      barrierColor: Colors.black26,
      transitionDuration: const Duration(milliseconds: 200),
      transitionBuilder: (ctx, animation, _, child) {
        final curved =
            CurvedAnimation(parent: animation, curve: Curves.easeOutBack);
        return FadeTransition(
          opacity: animation,
          child: ScaleTransition(
            scale: curved,
            alignment: Alignment(originX, -1),
            child: child,
          ),
        );
      },
      pageBuilder: (dialogCtx, _, __) => Stack(
        children: [
          Positioned(
            left: left,
            top: top,
            width: popoverWidth,
            child: Material(
              color: Colors.transparent,
              child: Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(18.r),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.35),
                      blurRadius: 18,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                // Same body theme background as the home (admin
                // color/gradient/image) instead of a hardcoded surface; the
                // choices sit on top of it. Clipped to the popup's rounded
                // corners so the theme respects the card shape.
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(18.r),
                  child: Stack(
                    children: [
                      const Positioned.fill(
              child: BodyThemeBackground(
                  fallbackColor: ColorManager.roomGold)),
                      Padding(
                        padding: EdgeInsets.all(12.w),
                        child: Row(
                          children: [
                            if (ConstantsManager.isAudioRoomsEnabled)
                              Expanded(
                                child: _ChoiceCard(
                                  icon: Icons.mic_rounded,
                                  // Owns an audio room -> "My Room"; else
                                  // "Audio Room".
                                  label: rooms?.audio != null
                                      ? StringManager.myRoom.tr()
                                      : StringManager.audioRoom.tr(),
                                  onTap: () {
                                    Navigator.pop(dialogCtx);
                                    Methods.handleRoomEntry(
                                        context, rooms?.audio);
                                  },
                                ),
                              ),
                            if (ConstantsManager.isAudioRoomsEnabled &&
                                ConstantsManager.isShowLive)
                              10.wBox,
                            if (ConstantsManager.isShowLive)
                              Expanded(
                                child: _ChoiceCard(
                                  icon: Icons.videocam_rounded,
                                  // Always reads "Go Live" regardless of
                                  // ownership; tapping enters/creates the live.
                                  label: ar ? 'بث مباشر' : 'Go Live',
                                  gradient: true,
                                  onTap: () {
                                    Navigator.pop(dialogCtx);
                                    startLive(context, rooms?.live);
                                  },
                                ),
                              ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Enters the host's live show. With no existing live, the backing room is
  /// created silently (type=live, default title = the host's name) — the user
  /// never sees a create-room form.
  static Future<void> startLive(BuildContext context, MyRoom? live) async {
    if (live != null) {
      _enterLive(context, id: live.id, name: live.name, cover: live.cover,
          background: live.roomBackground, mode: live.mode.toString(),
          giftPrice: live.giftPrice);
      return;
    }
    if (_creating) return;
    _creating = true;
    final me = MyDataModel.getInstance();
    final defaultTitle = (me.name?.trim().isNotEmpty ?? false)
        ? (Methods.getLang() == 'ar' ? 'بث ${me.name}' : "${me.name}'s live")
        : (Methods.getLang() == 'ar' ? 'بث مباشر' : 'Live');
    try {
      final result = await di<CreateRoomUC>().call(
        CreateRoomParameter(
          roomName: defaultTitle,
          roomIntero: '',
          roomPassword: '',
          type: 'live',
        ),
      );
      result.fold(
        (failure) {
          if (context.mounted) {
            Methods.showToast(
              context,
              isError: true,
              message: NetworkExceptions.getErrorMessage(failure),
            );
          }
        },
        (success) {
          final room = success.data;
          if (room == null || !context.mounted) return;
          _enterLive(context, id: room.id, name: room.name, cover: room.cover,
              background: room.roomBackground, mode: room.mode.toString(),
              giftPrice: room.giftPrice);
        },
      );
    } finally {
      _creating = false;
    }
  }

  static void _enterLive(
    BuildContext context, {
    required int? id,
    String? name,
    String? cover,
    String? background,
    String? mode,
    String? giftPrice,
  }) {
    final me = MyDataModel.getInstance();
    di<RoomStateManager>().navigateToRoom(
      RoomEntryRequest(
        context: context,
        roomData: RoomEntity(
          ownerId: me.id,
          id: id,
          name: name,
          cover: cover,
          roomBackground: background,
          mode: mode,
          uuidOwnerRoom: me.uuid ?? "",
          giftPrice: giftPrice,
          ownerSpecialId: me.specialIdImage,
          ownerImageColor: me.imageColorEntity,
          streamType: "live",
          isLive: true,
        ),
        isLive: true,
      ),
    );
  }
}

class _ChoiceCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool gradient;
  final VoidCallback onTap;

  const _ChoiceCard({
    required this.icon,
    required this.label,
    required this.onTap,
    this.gradient = false,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16.r),
      child: Container(
        padding: EdgeInsets.symmetric(vertical: 18.h),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16.r),
          // Panel-driven colors: the gradient card uses the admin primary
          // (with a darker shade for depth); the plain card uses a subtle
          // primary-tinted surface + border. No hardcoded theme2 purple.
          gradient: gradient
              ? LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    ColorManager.roomGold,
                    Color.lerp(
                            ColorManager.roomGold, Colors.black, 0.30) ??
                        ColorManager.roomGold,
                  ],
                )
              : null,
          color: gradient
              ? null
              : ColorManager.roomGold.withValues(alpha: 0.10),
          border: gradient
              ? null
              : Border.all(
                  color: ColorManager.roomGold.withValues(alpha: 0.35),
                ),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Gradient card is filled with the admin primary, so its content
            // uses the panel's on-primary text color (same pairing as
            // MainButton); the plain card follows the panel primary text.
            Icon(
              icon,
              color: gradient
                  ? ColorManager.roomButtonText
                  : ColorManager.roomTextPrimary,
              size: 32.sp,
            ),
            8.hBox,
            TextWidget(
              label,
              style: context.bodyMedium.w600.colorExt(
                gradient
                    ? ColorManager.roomButtonText
                    : ColorManager.roomTextPrimary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
