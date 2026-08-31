import 'package:general/src/core/index.dart';
import 'package:general/src/features/live_room/presentation/live_room_data.dart';
import 'package:general/src/features/profile/presentation/f_f_f_v/bloc/bloc_get_f_f_f/get_follower_or_following_bloc.dart';
import 'package:general/src/features/profile/presentation/f_f_f_v/bloc/bloc_get_f_f_f/get_follower_or_following_event.dart';
import 'package:general/src/features/profile/presentation/f_f_f_v/bloc/bloc_get_f_f_f/get_follower_or_following_state.dart';
import 'package:general/src/features/room/room.dart';
import 'package:utd_live_room_kit/utd_live_room_kit.dart' as live;

import 'live_pk_matching_dialog.dart';
import 'pk_logo.dart';

/// Mico-style PK sheet for the live host. Replaces the old plain rooms list
/// (LivePkInviteSheet):
///  - header with the PK wordmark centered and a settings gear that opens the
///    challenge-settings section (friend-challenge duration chips 5/15/30/60
///    minutes, default 5 — sent with the invite);
///  - a big pink→blue "Random Battle" button → [live.UTDPkController.
///    requestRandomMatch] with a FIXED 300s duration (product decision, not a
///    user choice). Instant pairing closes the sheet (the kit renders the
///    battle); a queue result closes the sheet and opens
///    [LivePkMatchingDialog];
///  - a "Challenge a friend" section: search-by-ID over the live rooms plus
///    the list of the host's FRIENDS currently live (friends source ∩ live
///    streams from [HomeBloc.stream]); a grey "no friends" line when none.
///
/// `targetRoomName` for [live.UTDPkController.invite] is the other room's
/// [RoomEntity.id] stringified — the same value that room's own live screen
/// passed as `roomId` when it connected.
///
/// The controller + theme are passed EXPLICITLY from the call site (which sits
/// under [live.UTDRoomScope]); the sheet's builder context is ABOVE the scope,
/// so resolving the scope inside it threw and grey-screened (owner 2026-08-08).
class LivePkSheet {
  static Future<void> show(
    BuildContext context,
    live.UTDRoomController controller,
  ) {
    final theme =
        live.UTDRoomScope.maybeOf(context)?.theme ?? const live.UTDRoomTheme();
    return showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _LivePkSheetBody(
        controller: controller,
        theme: theme,
        hostContext: context,
      ),
    );
  }
}

class _LivePkSheetBody extends StatefulWidget {
  final live.UTDRoomController controller;
  final live.UTDRoomTheme theme;

  /// The call-site context (under the room scope, stays mounted after this
  /// sheet pops) — used to open the matching dialog after closing the sheet.
  final BuildContext hostContext;

  const _LivePkSheetBody({
    required this.controller,
    required this.theme,
    required this.hostContext,
  });

  @override
  State<_LivePkSheetBody> createState() => _LivePkSheetBodyState();
}

class _LivePkSheetBodyState extends State<_LivePkSheetBody> {
  static const int _randomBattleSeconds = 300;
  static const List<int> _friendDurationsMin = [5, 15, 30, 60];

  final HomeBloc _homeBloc = di<HomeBloc>();
  final GetFollowerOrFollowingBloc _friendsBloc =
      di<GetFollowerOrFollowingBloc>();
  final TextEditingController _searchCtrl = TextEditingController();

  bool _showSettings = false;
  bool _busy = false;
  int _friendDurationMin = 5;
  String _query = '';

  live.UTDRoomTheme get theme => widget.theme;

  @override
  void initState() {
    super.initState();
    if (_homeBloc.state.stream.isEmpty) {
      _homeBloc.add(const FetchLiveRoomsEvent(isFirstPage: true));
    }
    if (!_friendsBloc.state.getFriendsRequest.isLoaded) {
      _friendsBloc.add(const GetFriendsEvent(loading: true));
    }
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  // ---------------------------------------------------------------------------
  // Actions
  // ---------------------------------------------------------------------------

  Future<void> _startRandomBattle() async {
    if (_busy) return;
    final pk = widget.controller.pkController;
    if (pk == null) {
      // pkController is built in the kit's initApi (before connect); null here
      // means the controller is still initializing. Without this guard the
      // `?.` call silently returned null, which reads as "queued" and opened a
      // dead matching dialog.
      debugPrint('[LivePkSheet] random battle tapped but pkController is null '
          '(controller still initializing)');
      Methods.showToast(
        context,
        message: StringManager.pkPreparing.tr(),
        isError: true,
      );
      return;
    }
    setState(() => _busy = true);
    final hostContext = widget.hostContext;
    try {
      final battle =
          await pk.requestRandomMatch(durationSeconds: _randomBattleSeconds);
      if (!mounted) return;
      // The kit also returns null as a NO-OP when the room isn't connected yet
      // (_homeRoomName == null) — matchState stays idle then. Only a real
      // queue entry flips it to matching; a no-op must not open a dead dialog.
      if (battle == null &&
          pk.matchState.value != live.UTDPkMatchState.matching) {
        debugPrint('[LivePkSheet] requestRandomMatch no-op — room not '
            'connected yet (matchState=${pk.matchState.value})');
        setState(() => _busy = false);
        Methods.showToast(
          context,
          message: StringManager.pkPreparing.tr(),
          isError: true,
        );
        return;
      }
      Navigator.pop(context);
      if (battle == null) {
        // Queued — the matching dialog takes over (opened on the call-site
        // context: this sheet's context is gone after the pop).
        if (hostContext.mounted) {
          await LivePkMatchingDialog.show(
            hostContext,
            widget.controller,
            theme,
          );
        }
      } else {
        // Instant pairing — the kit already shows the battle.
        Methods.showToast(hostContext,
            message: StringManager.pkMatchFound.tr());
      }
    } catch (e, st) {
      // NEVER swallow silently (owner rule): the toast stays friendly, the log
      // carries the real failure. A DioException here is the engine rejecting
      // the match call (404 room not found / 422 not a live_stream room / ...).
      debugPrint('[LivePkSheet] requestRandomMatch failed: $e\n$st');
      if (e is DioException) {
        debugPrint('[LivePkSheet] requestRandomMatch response: '
            'status=${e.response?.statusCode} body=${e.response?.data} '
            'url=${e.requestOptions.uri}');
      }
      if (!mounted) return;
      setState(() => _busy = false);
      Methods.showToast(
        context,
        message: StringManager.someThingWentWrong.tr(),
        isError: true,
      );
    }
  }

  Future<void> _inviteRoom(RoomEntity room) async {
    if (_busy || room.id == null) return;
    final pk = widget.controller.pkController;
    if (pk == null) {
      debugPrint('[LivePkSheet] invite tapped but pkController is null '
          '(controller still initializing)');
      Methods.showToast(
        context,
        message: StringManager.pkPreparing.tr(),
        isError: true,
      );
      return;
    }
    setState(() => _busy = true);
    try {
      final battle = await pk.invite(
        room.id.toString(),
        durationSeconds: _friendDurationMin * 60,
      );
      if (!mounted) return;
      if (battle == null) {
        // Kit no-op: our room isn't connected yet — don't fake a "sent" toast.
        debugPrint('[LivePkSheet] invite no-op — room not connected yet');
        setState(() => _busy = false);
        Methods.showToast(
          context,
          message: StringManager.pkPreparing.tr(),
          isError: true,
        );
        return;
      }
      Navigator.pop(context);
      Methods.showToast(widget.hostContext,
          message: StringManager.pkInviteSent.tr());
    } catch (e, st) {
      debugPrint('[LivePkSheet] invite(${room.id}) failed: $e\n$st');
      if (e is DioException) {
        debugPrint('[LivePkSheet] invite response: '
            'status=${e.response?.statusCode} body=${e.response?.data} '
            'url=${e.requestOptions.uri}');
      }
      if (!mounted) return;
      setState(() => _busy = false);
      Methods.showToast(
        context,
        message: StringManager.someThingWentWrong.tr(),
        isError: true,
      );
    }
  }

  // ---------------------------------------------------------------------------
  // Build
  // ---------------------------------------------------------------------------

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.7,
      decoration: BoxDecoration(
        color: theme.sheetBackground,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      padding: EdgeInsets.fromLTRB(16.w, 12.h, 16.w, 16.h),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: theme.sheetHandle,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          10.hBox,
          _header(),
          14.hBox,
          Expanded(child: _showSettings ? _settingsSection() : _mainSection()),
        ],
      ),
    );
  }

  /// PK wordmark centered; a gear (main) or back arrow (settings) at the edge.
  Widget _header() {
    return SizedBox(
      height: 36.h,
      child: Stack(
        alignment: Alignment.center,
        children: [
          const Center(child: PkLogo(size: 28)),
          Align(
            alignment: AlignmentDirectional.centerEnd,
            child: IconButton(
              onPressed: () => setState(() => _showSettings = !_showSettings),
              icon: Icon(
                _showSettings ? Icons.close : Icons.settings_outlined,
                color: theme.onSurface.withValues(alpha: 0.8),
                size: 22.sp,
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // Settings section — friend-challenge duration (penalty effects deferred by
  // owner decision; the kit exposes no receive-invites preference, so no
  // switches are shown either).
  // ---------------------------------------------------------------------------

  Widget _settingsSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          StringManager.pkChallengeSettings.tr(),
          style: context.bodyLarge.bold.colorExt(theme.onSurface),
        ),
        16.hBox,
        Text(
          StringManager.pkChallengeDuration.tr(),
          style: context.bodyMedium.w600
              .colorExt(theme.onSurface.withValues(alpha: 0.9)),
        ),
        10.hBox,
        Wrap(
          spacing: 10.w,
          children: [
            for (final m in _friendDurationsMin) _durationChip(m),
          ],
        ),
        10.hBox,
        Text(
          StringManager.pkDurationHint.tr(),
          style: context.bodySmall
              .colorExt(theme.onSurface.withValues(alpha: 0.5)),
        ),
      ],
    );
  }

  Widget _durationChip(int minutes) {
    final selected = _friendDurationMin == minutes;
    return GestureDetector(
      onTap: () => setState(() => _friendDurationMin = minutes),
      child: Container(
        padding: EdgeInsets.symmetric(horizontal: 18.w, vertical: 8.h),
        decoration: BoxDecoration(
          gradient: selected ? PkColors.gradient : null,
          color: selected ? null : theme.onSurface.withValues(alpha: 0.08),
          borderRadius: 20.radius,
        ),
        child: Text(
          '$minutes ${StringManager.minute.tr()}',
          style: context.bodyMedium.w600.colorExt(
            selected ? Colors.white : theme.onSurface.withValues(alpha: 0.8),
          ),
        ),
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // Main section — random battle + challenge a friend
  // ---------------------------------------------------------------------------

  Widget _mainSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _randomBattleButton(),
        18.hBox,
        Text(
          StringManager.pkChallengeFriend.tr(),
          style: context.bodyLarge.bold.colorExt(theme.onSurface),
        ),
        10.hBox,
        _searchField(),
        10.hBox,
        Expanded(child: _roomsList()),
      ],
    );
  }

  Widget _randomBattleButton() {
    return GestureDetector(
      onTap: _busy ? null : _startRandomBattle,
      child: Container(
        width: double.infinity,
        height: 52.h,
        decoration: BoxDecoration(
          gradient: PkColors.gradient,
          borderRadius: 26.radius,
          boxShadow: [
            BoxShadow(
              color: PkColors.pink.withValues(alpha: 0.35),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Center(
          child: _busy
              ? SizedBox(
                  width: 22.h,
                  height: 22.h,
                  child: const CircularProgressIndicator(
                    strokeWidth: 2.5,
                    valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                  ),
                )
              : Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.flash_on_rounded,
                        color: Colors.white, size: 20.sp),
                    6.wBox,
                    Text(
                      StringManager.pkRandomBattleStart.tr(),
                      style:
                          context.bodyLarge.bold.colorExt(Colors.white),
                    ),
                  ],
                ),
        ),
      ),
    );
  }

  Widget _searchField() {
    return Container(
      height: 40.h,
      padding: EdgeInsets.symmetric(horizontal: 12.w),
      decoration: BoxDecoration(
        color: theme.onSurface.withValues(alpha: 0.08),
        borderRadius: 20.radius,
      ),
      child: Row(
        children: [
          Icon(Icons.search,
              color: theme.onSurface.withValues(alpha: 0.5), size: 20.sp),
          8.wBox,
          Expanded(
            child: TextField(
              controller: _searchCtrl,
              onChanged: (v) => setState(() => _query = v.trim()),
              style: context.bodyMedium.colorExt(theme.onSurface),
              cursorColor: PkColors.pink,
              decoration: InputDecoration(
                isDense: true,
                border: InputBorder.none,
                hintText: StringManager.pkSearchById.tr(),
                hintStyle: context.bodyMedium
                    .colorExt(theme.onSurface.withValues(alpha: 0.4)),
              ),
            ),
          ),
          if (_query.isNotEmpty)
            GestureDetector(
              onTap: () {
                _searchCtrl.clear();
                setState(() => _query = '');
              },
              child: Icon(Icons.clear,
                  color: theme.onSurface.withValues(alpha: 0.5), size: 18.sp),
            ),
        ],
      ),
    );
  }

  /// Query empty → the host's friends who are live right now.
  /// Query set   → any live room whose owner ID / room ID / name matches.
  Widget _roomsList() {
    final myRoomId = LiveRoomData.instance.roomOrNull?.id;
    return BlocBuilder<HomeBloc, HomeState>(
      bloc: _homeBloc,
      buildWhen: (prev, curr) =>
          prev.stream != curr.stream || prev.reqStateLive != curr.reqStateLive,
      builder: (context, homeState) {
        final liveRooms = homeState.stream
            .where((r) => r.id != null && r.id != myRoomId)
            .toList(growable: false);

        if (_query.isNotEmpty) {
          return _list(_searchRooms(liveRooms));
        }

        return BlocBuilder<GetFollowerOrFollowingBloc,
            GetFollowerOrFollowingState>(
          bloc: _friendsBloc,
          buildWhen: (prev, curr) =>
              prev.getFriends != curr.getFriends ||
              prev.getFriendsRequest != curr.getFriendsRequest,
          builder: (context, friendsState) {
            final friendIds = friendsState.getFriends
                .map((u) => u.id)
                .whereType<int>()
                .toSet();
            final friendRooms = liveRooms
                .where((r) => friendIds.contains(r.ownerId))
                .toList(growable: false);
            return _list(friendRooms);
          },
        );
      },
    );
  }

  List<RoomEntity> _searchRooms(List<RoomEntity> rooms) {
    final q = _query.toLowerCase();
    return rooms
        .where((r) =>
            (r.ownerSpecialId ?? '') == _query ||
            r.id.toString() == _query ||
            (r.roomId ?? '') == _query ||
            (r.ownerName ?? '').toLowerCase().contains(q) ||
            (r.name ?? '').toLowerCase().contains(q))
        .toList(growable: false);
  }

  Widget _list(List<RoomEntity> rooms) {
    if (rooms.isEmpty) {
      // Mico-style grey empty line.
      return Center(
        child: Text(
          _query.isEmpty
              ? StringManager.noFriends.tr()
              : StringManager.noLives.tr(),
          style: context.bodyMedium
              .colorExt(theme.onSurface.withValues(alpha: 0.4)),
        ),
      );
    }
    return ListView.separated(
      itemCount: rooms.length,
      separatorBuilder: (_, __) => 10.hBox,
      itemBuilder: (context, index) => _roomRow(rooms[index]),
    );
  }

  Widget _roomRow(RoomEntity room) {
    return Container(
      padding: context.paddingAll(8),
      decoration: BoxDecoration(
        color: theme.onSurface.withValues(alpha: 0.06),
        borderRadius: 16.radius,
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: 12.radius,
            child: ImageViewWidget(
              url: room.cover ?? '',
              boxFit: BoxFit.cover,
              width: 48.w,
              height: 48.h,
              displayName: room.ownerName ?? room.name ?? '',
              isRoomCover: true,
            ),
          ),
          12.wBox,
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  room.ownerName ?? room.name ?? '',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: context.bodyMedium.w600.colorExt(theme.onSurface),
                ),
                if ((room.ownerSpecialId ?? '').isNotEmpty) ...[
                  2.hBox,
                  Text(
                    'ID: ${room.ownerSpecialId}',
                    maxLines: 1,
                    style: context.bodySmall
                        .colorExt(theme.onSurface.withValues(alpha: 0.5)),
                  ),
                ],
              ],
            ),
          ),
          8.wBox,
          GestureDetector(
            onTap: _busy ? null : () => _inviteRoom(room),
            child: Container(
              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 7.h),
              decoration: BoxDecoration(
                gradient: PkColors.gradient,
                borderRadius: 18.radius,
              ),
              child: Text(
                StringManager.pkChallenge.tr(),
                style: context.bodySmall.w600.colorExt(Colors.white),
              ),
            ),
          ),
        ],
      ),
    );
  }
}