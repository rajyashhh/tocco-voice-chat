import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/body_theme_background.dart';
import 'package:general/src/core/widgets/on_multiable_tab.dart';
import 'package:general/src/features/room/presentation/gifts/bloc/gift_bloc/gift_bloc.dart';
import 'package:general/src/features/room/presentation/gifts/controller/lucky_gift_service.dart';
import 'package:general/src/features/room/presentation/manager/alpha_gift_manager/alpha_gift_manager_bloc.dart';
import 'package:general/src/features/room/presentation/manager/alpha_gift_manager/alpha_gift_manager_event.dart';
import 'package:general/src/features/room/room.dart';
import 'package:general/src/features/live_room/presentation/live_room_data.dart';

/// Exit panel shown when the user taps EXIT inside an audio room or a live
/// stream (viewers only — the live host gets a confirmation dialog instead).
///
/// Instead of leaving immediately it presents a vertical, single-column
/// scrollable list the user can switch to — "المزيد من الغرف" inside an audio
/// room, "المزيد من البثوث المباشرة" inside a live stream — plus two circular
/// actions: احتفظ (minimize the current room via the kit, audio only) and
/// خروج (clean exit).
///
/// It is the content of a partial-width side drawer (see
/// `ExitSidePanelOverlay`) so the audio room stays visible behind it; this
/// widget only renders the panel surface, the list and the actions.
///
/// Tapping a row switches rooms (leave current + enter selected) by
/// reusing the canonical [RoomStateManager.navigateToRoom] flow.
class ExitPanel extends StatefulWidget {
  const ExitPanel({super.key});

  @override
  State<ExitPanel> createState() => _ExitPanelState();
}

class _ExitPanelState extends State<ExitPanel> {
  final HomeBloc _homeBloc = di<HomeBloc>();

  /// Inside a live stream the panel lists other live streams, not audio rooms.
  final bool _isLive = di<RoomStateManager>().isInVideoRoom;

  @override
  void initState() {
    super.initState();
    // Reuse the home lists. Only fetch when the slice hasn't been loaded yet
    // so returning to the panel is instant and we don't duplicate home calls.
    if (_isLive) {
      if (_homeBloc.state.stream.isEmpty) {
        _homeBloc.add(const FetchLiveRoomsEvent(isFirstPage: true));
      }
    } else if (_homeBloc.state.popular.isEmpty) {
      _homeBloc.add(const FetchPopularRoomsEvent(isFirstPage: true));
    }
  }

  void _onRoomTap(RoomEntity room) {
    // Tapping the room/live the user is already in just closes the panel —
    // the list intentionally shows it (same order as the home list) so the
    // user can see their room's rank.
    if (room.id == RoomData.instance.room.id) {
      Navigator.pop(context);
      return;
    }
    if (!di<FetchUserDataBloc>().state.reqState.isLoaded) return;
    // Close the panel first, then run the canonical switch flow which leaves
    // the current room and enters the selected one. Live rooms route to the
    // live screen via RoomEntryRequest.isLiveRoom (streamType-aware).
    Navigator.pop(context);
    di<RoomStateManager>().navigateToRoom(
      RoomEntryRequest(
        context: context,
        roomData: room,
        isLive: _isLive || room.streamType == "live",
      ),
    );
  }

  void _onKeep() {
    if (PkController.isPK.value == true) {
      Methods.showToast(context,
          message: StringManager.cantSave.tr(), isError: true);
      return;
    }
    if (RoomData.instance.room.mode == '5') {
      Methods.showToast(
        context,
        message: StringManager.youCantSaveRoomBecauseCinemaMode.tr(),
        isError: true,
      );
      return;
    }
    // Clear in-room gift/animation overlays before minimizing so a minimized
    // room doesn't keep replaying effects (same teardown as the old dialog).
    ShowEntroWidget.showEntro.value = null;
    di<GiftBloc>().add(
      const ShowGiftsEvent(
        isShowGift: false,
        pathGift: '',
        giftType: ShowGiftType.svga,
      ),
    );
    di<AlphaGiftManagerBloc>().add(const EndAlphaGift());
    GiftController().normalGiftsToShow.clear();
    di<LuckyGiftAnaimationManagerBloc>()
        .add(const InitLuckyGiftAnaimationManagerEvent());
    LuckyGiftService.instance.endAllLuckyGift();
    SuperBoomController.superBoomVideo.value = "";
    SuperBoomController.superBoomVideoType.value = "";
    Navigator.pop(context);
    // Minimize via the active room's controller: the live (video) room has its
    // own kit/controller, the audio room another. Both expose the same minimize
    // API (startMinimize → pops the screen, mounts the floating mini-window).
    if (_isLive) {
      LiveRoomData.instance.liveController?.minimize.startMinimize(context);
    } else {
      RoomData.instance.utdController?.minimize.startMinimize(context);
    }
  }

  Future<void> _onExit() async {
    if (HomePage.isConnectToInternet != true) {
      showDialog(
        context: context,
        builder: (_) => AnimatedDialog(
          titleColor: ColorManager.roomTextPrimary,
          descriptionColor: ColorManager.roomSecondaryText,
          cancelTextColor: ColorManager.roomTextPrimary,
          title: StringManager.noConnection.tr(),
          description: StringManager.internetConnection.tr(),
          needPopScope: false,
          isHideConfirm: true,
          cancelText: StringManager.cancel.tr(),
          isUpdateDialog: true,
          onTapCancel: () => Navigator.pop(context),
        ),
      );
      return;
    }
    ShowEntroWidget.showEntro.value = null;
    SuperBoomController.superBoomVideo.value = "";
    SuperBoomController.superBoomVideoType.value = "";
    final navContext = SafeNavigator.context;
    if (navContext == null) return;
    // Pop back to where the user came from (lives page when the live was
    // opened from it, else layout/first); the SDK's leave() inside exitRoom()
    // handles popping the room route, so we don't pop the room screen manually.
    Navigator.popUntil(
      navContext,
      (route) =>
          route.settings.name == Routes.livesPage ||
          route.settings.name == Routes.layout ||
          route.isFirst,
    );
    await di<RoomStateManager>().exitRoom(navContext);
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Material(
        color: ColorManager.transparent,
        // Rounded on the inner edge (the side facing the room/scrim); the start
        // edge hugs the screen edge so the drawer reads as a side panel.
        borderRadius: BorderRadius.horizontal(left: Radius.circular(24.r)),
        clipBehavior: Clip.antiAlias,
        // This panel shows the HOME room list, so it wears the same body theme
        // background as the home (admin color/gradient/image), not a hardcoded
        // surface. The list/actions sit on top of it.
        child: Stack(
          children: [
            const Positioned.fill(
              child: BodyThemeBackground(
                  fallbackColor: ColorManager.roomGold)),
            SafeArea(
              left: false,
              child: Padding(
                padding: context.paddingOnly(top: 24, bottom: 16),
                child: Column(
                  children: [
                    Text(
                      (_isLive
                              ? StringManager.moreLives
                              : StringManager.moreRooms)
                          .tr(),
                      style: context.bodyLarge
                          .size(18)
                          .w700
                          .colorExt(ColorManager.roomTextPrimary),
                    ),
                    12.hBox,
                    Expanded(child: _buildRoomsList()),
                    12.hBox,
                    _buildActions(),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRoomsList() {
    return BlocBuilder<HomeBloc, HomeState>(
      bloc: _homeBloc,
      buildWhen: (prev, curr) => _isLive
          ? (prev.stream != curr.stream ||
              prev.reqStateLive != curr.reqStateLive)
          : (prev.popular != curr.popular ||
              prev.reqStatePopular != curr.reqStatePopular),
      builder: (context, state) {
        final reqState = _isLive ? state.reqStateLive : state.reqStatePopular;
        // Full home list, current room INCLUDED, same order as outside — the
        // user wants to see where their room ranks (owner report 2026-06-11).
        final rooms = (_isLive ? state.stream : state.popular)
            .where((r) => r.id != null)
            .toList(growable: false);

        if (rooms.isEmpty) {
          final emptyTitle =
              (_isLive ? StringManager.noLives : StringManager.noRooms).tr();
          return Padding(
            padding: context.paddingSymmetric(horizontal: 12, vertical: 20),
            child: HandlingDataWidget(
              accentColor: ColorManager.roomGold,
              reqState: reqState,
              title: emptyTitle,
              subTitle:
                  (_isLive ? StringManager.noLivesMsg : StringManager.noRoomsMsg)
                      .tr(),
              onTap: () => _homeBloc.add(_isLive
                  ? const FetchLiveRoomsEvent(isFirstPage: true)
                  : const FetchPopularRoomsEvent(isFirstPage: true)),
              // Loaded but no other open rooms/lives besides the current one.
              child: Padding(
                padding: context.paddingSymmetric(vertical: 30),
                child: Text(
                  emptyTitle,
                  style: context.bodyMedium
                      .colorExt(ColorManager.roomSecondaryText),
                ),
              ),
            ),
          );
        }

        return ListView.separated(
          padding: context.paddingSymmetric(horizontal: 12),
          physics: const AlwaysScrollableScrollPhysics(),
          itemCount: rooms.length,
          separatorBuilder: (_, __) => 10.hBox,
          itemBuilder: (context, index) {
            final room = rooms[index];
            return RepaintBoundary(
              child: MultiTapCard(
                onTap: () => _onRoomTap(room),
                child: _RoomSideCard(
                  key: ValueKey('exit_panel_room_${room.id}'),
                  room: room,
                  isLive: _isLive,
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildActions() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
      children: [
        // Minimize (تصغير): shrinks the current room — audio or live — into a
        // draggable floating mini-window while staying connected to the stream.
        _CircleAction(
          icon: AssetsManager.miniRoomIcon,
          label: StringManager.minimize.tr(),
          onTap: _onKeep,
        ),
        _CircleAction(
          icon: AssetsManager.exitRoomIcon,
          label: StringManager.exit.tr(),
          onTap: _onExit,
        ),
      ],
    );
  }
}

class _CircleAction extends StatelessWidget {
  final String icon;
  final String label;
  final VoidCallback onTap;

  const _CircleAction({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return MultiTapCard(
      onTap: onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            padding: context.paddingAll(15),
            decoration: const BoxDecoration(
              color: ColorManager.roomGold,
              shape: BoxShape.circle,
            ),
            child: Image.asset(
              icon,
              height: 32.h,
              width: 32.w,
              color: ColorManager.roomButtonText,
            ),
          ),
          8.hBox,
          Text(
            label,
            style: context.bodyMedium
                .size(15)
                .w600
                .colorExt(ColorManager.roomTextPrimary),
          ),
        ],
      ),
    );
  }
}

/// Wide horizontal row for one open room/live inside the side list: cover
/// thumbnail on the start edge + name, country flag, listeners/gifts counts
/// and a party/live tag. The parent [MultiTapCard] owns the tap
/// (pop-then-switch), so this is a presentation-only widget.
class _RoomSideCard extends StatelessWidget {
  final RoomEntity room;
  final bool isLive;

  const _RoomSideCard({required this.room, this.isLive = false, super.key});

  @override
  Widget build(BuildContext context) {
    final flag = room.country?.flag ?? '';
    final gifts = room.giftPrice ?? '';

    return Container(
      padding: context.paddingAll(8),
      decoration: BoxDecoration(
        // Blend the room card into the app body instead of a flat white card:
        // reuse the SAME panel body gradient (`regions.body`) the scaffold paints.
        gradient: ColorManager.roomBodyBackgroundGradient,
        borderRadius: 16.radius,
        border: Border.all(color: ColorManager.roomGold),
      ),
      child: Row(
        children: [
          // Cover thumbnail.
          ClipRRect(
            borderRadius: 12.radius,
            child: ImageViewWidget(
              url: room.cover ?? '',
              boxFit: BoxFit.cover,
              width: 72.w,
              height: 72.h,
              displayName: room.name ?? '',
              isRoomCover: true,
            ),
          ),
          12.wBox,
          // Name + flag, then listeners + gifts.
          Expanded(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        room.name ?? '',
                        style: context.bodyLarge
                            .size(15)
                            .w600
                            .colorExt(ColorManager.roomTextPrimary),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    if (flag.isNotEmpty) ...[
                      6.wBox,
                      ImageViewWidget(url: flag, height: 16, width: 16),
                    ],
                  ],
                ),
                8.hBox,
                Row(
                  children: [
                    Icon(
                      Icons.headset,
                      size: 14.sp,
                      color: ColorManager.roomSecondaryText,
                    ),
                    3.wBox,
                    Text(
                      '${room.visitorsCount ?? 0}',
                      style: context.bodySmall
                          .size(12)
                          .colorExt(ColorManager.roomSecondaryText),
                    ),
                    if (gifts.isNotEmpty) ...[
                      12.wBox,
                      Image.asset(
                        AssetsManager.diamondsIcon,
                        height: 14.h,
                        width: 14.w,
                      ),
                      3.wBox,
                      Text(
                        gifts,
                        style: context.bodySmall
                            .size(12)
                            .colorExt(ColorManager.roomSecondaryText),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
          8.wBox,
          // Party tag ("حفلة") for audio rooms, live tag ("لايف") for streams.
          Container(
            padding: context.paddingSymmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: ColorManager.roomGold,
              borderRadius: 12.radius,
            ),
            child: Text(
              (isLive ? StringManager.live : StringManager.party).tr(),
              style: context.bodySmall
                  .size(11)
                  .w600
                  .colorExt(ColorManager.roomButtonText),
            ),
          ),
        ],
      ),
    );
  }
}
