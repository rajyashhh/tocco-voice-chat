import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/domain/entities/now_room_entity.dart';
import 'package:general/src/features/home/domain/entities/room_entity.dart';

/// Animated "تتبع"/live badge shown on the visitor profile header when the
/// profiled user is currently inside a room. It pulses (scale + glow) to draw
/// attention and, on tap, enters that room reusing the canonical room-enter
/// flow ([RoomStateManager.navigateToRoom]) — the same path a room-list tap
/// uses (see theme2_room_card.dart), so password / PiP / same-room handling
/// all come for free.
class Theme2LiveBadge extends StatefulWidget {
  /// The room the profiled user is currently in. The badge is only built by the
  /// caller when this is non-null with a valid id, so we treat it as present.
  final NowRoomEntity nowRoom;

  const Theme2LiveBadge({super.key, required this.nowRoom});

  @override
  State<Theme2LiveBadge> createState() => _Theme2LiveBadgeState();
}

class _Theme2LiveBadgeState extends State<Theme2LiveBadge>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _scale;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..repeat(reverse: true);
    _scale = Tween<double>(begin: 0.92, end: 1.12).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _enterRoom() {
    final roomId = widget.nowRoom.id;
    if (roomId == null || roomId == 0) return;

    final roomEntity = RoomEntity(
      id: roomId,
      name: widget.nowRoom.roomName,
      cover: widget.nowRoom.roomCover,
      roomBackground: widget.nowRoom.roomBackground,
      mode: widget.nowRoom.mode,
      giftPrice: widget.nowRoom.giftPrice,
      passwordStatus: widget.nowRoom.roomStatus,
    );

    di<RoomStateManager>().navigateToRoom(
      RoomEntryRequest(
        context: context,
        roomData: roomEntity,
        isLive: false,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: _enterRoom,
      child: ScaleTransition(
        scale: _scale,
        child: Container(
          padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 4.h),
          decoration: BoxDecoration(
            color: ColorManager.red,
            borderRadius: BorderRadius.circular(20.r),
            boxShadow: [
              BoxShadow(
                color: ColorManager.red.withValues(alpha: 0.55),
                blurRadius: 8.r,
                spreadRadius: 1.r,
              ),
            ],
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 6.r,
                height: 6.r,
                decoration: const BoxDecoration(
                  color: ColorManager.white,
                  shape: BoxShape.circle,
                ),
              ),
              4.wBox,
              Text(
                StringManager.liveTrackBadge.tr(),
                style: TextStyle(
                  color: ColorManager.onDark,
                  fontSize: 10.sp,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
