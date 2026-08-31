import 'dart:async';
import 'dart:io';
import 'package:audio_session/audio_session.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:general/src/core/index.dart';
import 'package:general/src/core/realtime/realtime_config.dart';
import 'package:general/src/core/realtime/stream_token_service.dart';
import 'package:general/src/core/widgets/bottom_dialog.dart';
import 'package:general/src/features/auth/domain/entities/my_data_entity.dart';
import 'package:general/src/features/games/domain/entities/game_entity.dart';
import 'package:general/src/features/room/presentation/component/room_header/exit_room/exit_side_panel_overlay.dart';
import 'package:general/src/features/room/presentation/component/seat_config/empty_seat_widget.dart';
import 'package:general/src/features/room/presentation/component/seat_config/locked_seat_widget.dart';
import 'package:general/src/features/room/presentation/component/seat_config/seat_avatar_widget.dart';
import 'package:general/src/features/room/presentation/gifts/bloc/lucky_gift_win_bloc/lucky_gift_win_bloc.dart';
import 'package:general/src/features/room/presentation/gifts/bloc/lucky_gift_win_bloc/lucky_gift_win_event.dart';
import 'package:general/src/features/room/presentation/gifts/gift.dart';
import 'package:general/src/features/room/presentation/lucky_box/widgets/dialog_lucky_box.dart';
import 'package:general/src/features/room/presentation/manager/room_mode/room_mode_cubit.dart';
import 'package:general/src/features/room/presentation/music/bloc/music_room_bloc.dart';
import 'package:general/src/features/room/presentation/room_modes/seat8_mode.dart';
import 'package:general/src/features/room/presentation/room_modes/seat2_mode.dart';
import 'package:general/src/features/room/presentation/room_modes/seat22_mode.dart';
import 'package:general/src/features/room/presentation/room_modes/couples_mode.dart';
import 'package:general/src/features/room/presentation/youtube/view/cinema_mode_layout.dart';
import 'package:general/src/features/room/presentation/youtube/view/youtube_controller.dart';
import 'package:general/src/features/room/room.dart';
import 'package:general/src/features/room/presentation/component/messages/tabbed_messages_view.dart';
import 'package:general/src/features/room/presentation/component/buttom_bar/buttom_bar_widget.dart';
import 'package:general/src/features/room/presentation/component/widgets/seat_options_sheet.dart';

class RoomScreen extends StatefulWidget {
  final MyDataModel userModel;
  final String roomId;
  final String ownerId;
  final bool isHost;
  final bool isLocked;
  final bool? isGame;
  final bool? isExit;
  final bool? fromDynamicLink;
  final GameDataEntity? gameDataEntity;
  final String? specialIdImage;
  final ImageColorEntity? imageColorEntity;

  const RoomScreen({
    super.key,
    required this.userModel,
    required this.isHost,
    required this.isLocked,
    required this.roomId,
    required this.ownerId,
    this.isGame,
    this.isExit,
    this.fromDynamicLink,
    this.gameDataEntity,
    this.specialIdImage,
    this.imageColorEntity,
  });

  @override
  State<RoomScreen> createState() => RoomScreenState();
}

class RoomScreenState extends State<RoomScreen> with TickerProviderStateMixin {
  static Map<String, GlobalKey> seatAvatarKeys = {};
  static Map<int, String> seatAvatarIds = {};
  final giftController = GiftController();

  /// Live subscription to audio-output device changes, so the room re-routes
  /// audio the moment a Bluetooth headset connects/disconnects mid-session.
  StreamSubscription<Set<AudioDevice>>? _audioDevicesSub;

  /// Guards the UTD Stream "service unavailable" notice so it is shown only
  /// once even if onConnectError fires repeatedly during connect/token retries.
  bool _streamNoticeShown = false;

  /// Configure the audio session for Bluetooth and route output to a connected
  /// headset (falling back to the loudspeaker). Delegates the actual routing to
  /// WebRTC's native BT-preferring logic via the kit, and re-applies it whenever
  /// a headset connects/disconnects. Fixes "audio plays from the phone speaker
  /// instead of Bluetooth".
  Future<void> _setupBluetoothAwareAudio(UTDRoomController controller) async {
    try {
      final session = await AudioSession.instance;
      await session.configure(AudioSessionConfiguration(
        avAudioSessionCategory: AVAudioSessionCategory.playAndRecord,
        avAudioSessionCategoryOptions:
            AVAudioSessionCategoryOptions.allowBluetooth |
                AVAudioSessionCategoryOptions.allowBluetoothA2dp,
        avAudioSessionMode: AVAudioSessionMode.voiceChat,
        androidAudioAttributes: const AndroidAudioAttributes(
          contentType: AndroidAudioContentType.speech,
          usage: AndroidAudioUsage.voiceCommunication,
        ),
        androidAudioFocusGainType: AndroidAudioFocusGainType.gainTransient,
        androidWillPauseWhenDucked: true,
      ));
      // Apply BT routing through the KIT (forceHandleAudioRouting on Android),
      // which is the only thing that beats LiveKit's MODE_IN_COMMUNICATION.
      await controller.mediaController.applyBluetoothAudioRouting();
      // …and re-apply whenever a headset connects/disconnects.
      _audioDevicesSub?.cancel();
      _audioDevicesSub = session.devicesStream.listen((_) {
        controller.mediaController.applyBluetoothAudioRouting();
      });
    } catch (_) {}
  }

  @override
  void initState() {
    super.initState();

    loadCachedMicState();

    _deferRtmStart();

    setupRoomAndUser(
      isLocked: widget.isLocked,
      roomId: widget.roomId,
      context: context,
    );
  }

  void _deferRtmStart() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _startRtm();
    });
  }

  void _startRtm() {
    RoomData.instance.initRtm(
      giftController: giftController,
      roomId: widget.roomId,
      userModelId: widget.userModel.id.toString(),
      isHost: widget.isHost,
      tickerProvider: this,
    );
  }

  @override
  void dispose() {
    _audioDevicesSub?.cancel();
    di<GiftBloc>().add(
      const ShowGiftsEvent(
        pathGift: "",
        isShowGift: false,
        giftType: ShowGiftType.svga,
      ),
    );
    di<LuckyGiftWinBloc>().add(const ClearLuckyWinEvent());
    di<GiftBloc>().add(const SetVideoVisibilityEvent(isVisible: false));
    di<MusicRoomBloc>().add(
      SetIndexSongPlayingRoomEvent(di<MusicRoomBloc>().state.nowPlaying),
    );
    if (!di<RoomStateManager>().isMinimized) {
      LuckyBoxVariables.luckyBoxMap['luckyBoxes'].clear();
      di<SetTimerLuckyBox>().remTimeSuperBox = -1;
      DialogLuckyBox.startTime = false;
    }
    super.dispose();
  }

  void _showExitDialog() {
    final dialogContext = SafeNavigator.context;
    if (dialogContext == null) return;
    // Side drawer (the room stays visible behind it) instead of a bottom sheet.
    ExitSidePanelOverlay.show(dialogContext);
  }

  List<UTDRoomMode> _buildRoomModes() {
    Widget Function(UTDParticipant, int) wrapCreator(
      Widget Function(int) seatCreator,
    ) {
      return (user, seatIndex) => seatCreator(seatIndex);
    }

    List<UTDParticipant> extractUsers(List<SeatState> seats) {
      return seats
          .where((s) => s.occupantUserId != null)
          .map((s) => UTDParticipant(id: s.occupantUserId!, name: ''))
          .toList();
    }

    return [
      const UTDRoomMode(
        id: '3',
        seatCount: 9,
        rows: [
          [0],
          [1, 2, 3, 4],
          [5, 6, 7, 8],
        ],
      ),
      const UTDRoomMode(
        id: '2',
        seatCount: 12,
        rows: [
          [0, 1, 2, 3],
          [4, 5, 6, 7],
          [8, 9, 10, 11],
        ],
      ),
      const UTDRoomMode(
        id: '1',
        seatCount: 16,
        rows: [
          [0, 1, 2, 3],
          [4, 5, 6, 7],
          [8, 9, 10, 11],
          [12, 13, 14, 15],
        ],
      ),
      UTDRoomMode(
        id: '5',
        seatCount: 9,
        rows: const [
          [0, 1, 2, 3],
          [4, 5, 6, 7],
          [8],
        ],
        containerBuilder: (seats, seatCreator) {
          final users = extractUsers(seats);
          return CinemaModeLayout(
            allUsers: users,
            audioVideoUsers: users,
            seatWidgetCreator: wrapCreator(seatCreator),
          );
        },
        backgroundBuilder: (context) {
          return Stack(
            children: [
              Container(
                width: double.infinity,
                height: double.infinity,
                decoration: BoxDecoration(
                  image: DecorationImage(
                    image: AssetImage(AssetsManager.cinemaBackground),
                    fit: BoxFit.cover,
                  ),
                ),
              ),
              Container(
                height: MediaQuery.sizeOf(context).height / 2.05,
                decoration: BoxDecoration(
                  image: DecorationImage(
                    image: AssetImage(AssetsManager.cinemaBackgroundTop),
                    fit: BoxFit.fill,
                  ),
                ),
              ),
            ],
          );
        },
      ),
      UTDRoomMode(
        id: '8',
        seatCount: 8,
        rows: const [
          [0, 1],
          [2, 3],
          [4, 5],
          [6, 7],
        ],
        containerBuilder: (seats, seatCreator) {
          final users = extractUsers(seats);
          return Seat8Mode(
            allUsers: users,
            audioVideoUsers: users,
            seatWidgetCreator: wrapCreator(seatCreator),
          );
        },
        backgroundBuilder: (context) {
          return Container(
            width: double.infinity,
            height: double.infinity,
            decoration: BoxDecoration(
              image: DecorationImage(
                image: AssetImage(AssetsManager.seat8ModeBackground),
                fit: BoxFit.cover,
              ),
            ),
          );
        },
      ),
      UTDRoomMode(
        id: '6',
        seatCount: 2,
        rows: const [
          [0, 1],
        ],
        containerBuilder: (seats, seatCreator) {
          final users = extractUsers(seats);
          return Seat2Mode(
            allUsers: users,
            audioVideoUsers: users,
            seatWidgetCreator: wrapCreator(seatCreator),
          );
        },
        backgroundBuilder: (context) {
          return Container(
            width: double.infinity,
            height: double.infinity,
            decoration: BoxDecoration(
              image: DecorationImage(
                image: AssetImage(AssetsManager.seat2ModeBackground),
                fit: BoxFit.cover,
              ),
            ),
          );
        },
      ),
      UTDRoomMode(
        id: '7',
        seatCount: 22,
        rows: const [
          [0],
          [1],
          [2, 3, 4, 5, 6],
          [7, 8, 9, 10, 11],
          [12, 13, 14, 15, 16],
          [17, 18, 19, 20, 21],
        ],
        containerBuilder: (seats, seatCreator) {
          final users = extractUsers(seats);
          return Seat22Mode(
            allUsers: users,
            audioVideoUsers: users,
            seatWidgetCreator: wrapCreator(seatCreator),
          );
        },
      ),
      UTDRoomMode(
        id: '9',
        seatCount: 8,
        // Reference seat size at the design width: couches are laid out 4-across
        // (two couches/row) so the size can't be inferred from `rows` (2-wide).
        // computeSeatSize still scales this gently with the device.
        seatSize: 67,
        rows: const [
          [0, 1],
          [2, 3],
          [4, 5],
          [6, 7],
        ],
        containerBuilder: (seats, seatCreator) {
          final users = extractUsers(seats);
          return CouplesMode(
            allUsers: users,
            audioVideoUsers: users,
            seatWidgetCreator: wrapCreator(seatCreator),
          );
        },
        backgroundBuilder: (context) {
          return Container(
            width: double.infinity,
            height: double.infinity,
            decoration: BoxDecoration(
              image: DecorationImage(
                image: AssetImage(AssetsManager.coupleModeBg),
                fit: BoxFit.cover,
              ),
            ),
          );
        },
      ),
    ];
  }

  @override
  Widget build(BuildContext mainContext) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (didPop) return;
        _showExitDialog();
      },
      child: BlocListener<RoomHandlerBloc, RoomHandlerStates>(
        bloc: di<RoomHandlerBloc>(),
        listener: (context, state) async {
          if (state is EnterRoomSuccesMessageState) {
            RoomBackground.imgBackground.value =
                state.room.roomBackground ?? "";
            if (RoomData.instance.room.mode != state.room.mode) {
              YouTubeController.isCinemaMode.value = state.room.mode == '5';
            }
            RoomData.instance.showCp.value = state.room.cpIndexs ?? [];
            RoomData.instance.isCommentsClosed.value =
                state.room.isCommentsClosed ?? false;
          }
        },
        child: MediaQuery.removePadding(
          context: mainContext,
          removeTop: true,
          removeBottom: true,
          child: UTDAudioRoom(
            // UTD Stream connection credentials: the kit sends these as
            // X-App-Id + publishable app_key to authenticate every room/token
            // call to the UTD Stream service (the kit mints its own token — no
            // server_secret is shipped in the app). REQUIRED — emptying them
            // breaks room entry. Server-driven (admin-editable via
            // /config/settings); the build-time default is only the cold-start
            // fallback (prod by default, test-overridable via dart-define) so an
            // empty/late config can NEVER blank them and break room entry (the
            // bug that broke release 1.0.21) and a TEST build never uses prod.
            appId: RealtimeConfig.utdStreamAppId ??
                RealtimeConfig.defaultUtdStreamAppId,
            appKey: RealtimeConfig.utdStreamAppKey ??
                RealtimeConfig.defaultUtdStreamAppKey,
            // Only hand the kit an EXISTING controller when it is still
            // connected (minimize/PiP restore). A leftover-but-disposed
            // controller from a previous entry is non-null yet not connected —
            // the kit would reuse it (it only auto-creates a fresh one when the
            // passed controller is null) and then connect() does addListener on
            // a disposed ValueNotifier → the red "used after disposed" crash.
            // Passing null forces a fresh UTDRoomController for every new entry.
            controller: (RoomData.instance.utdController?.isConnected ?? false)
                ? RoomData.instance.utdController
                : null,
            // Server-signed tokens (production pattern): our backend verifies
            // the logged-in user and signs the engine mint with the
            // server_secret (the engine forbids identity mints with the
            // publishable app_key). Seat params mirror the kit's own request
            // via resolveTokenSeatParams; on null/failure the kit falls back
            // to its own mint (installs whose project allows app_key minting).
            tokenProvider: () async {
              final isHost = widget.isHost ||
                  MyDataModel.getInstance().id.toString() ==
                      RoomData.instance.room.ownerId.toString();
              final admins = <String>{
                ...(RoomData.instance.room.admins ?? const <String>[])
                    .map((e) => e.toString()),
                ...RoomData.instance.adminsInRoom.keys.map((e) => e.toString()),
              };
              final role = isHost
                  ? 'host'
                  : admins.contains(MyDataModel.getInstance().id.toString())
                      ? 'admin'
                      : 'audience';
              final seatParams = UTDAudioRoom.resolveTokenSeatParams(
                layoutMode: RoomData.instance.room.mode ?? '3',
                isHost: isHost,
                modes: _buildRoomModes(),
              );
              final data = await StreamTokenService.fetch(
                roomName: widget.roomId,
                service: 'rooms',
                role: role,
                roomOwnerId: (widget.ownerId.isNotEmpty && widget.ownerId != '0')
                    ? widget.ownerId
                    : RoomData.instance.room.ownerId.toString(),
                seatCount: seatParams.seatCount,
                seatMode: seatParams.seatMode,
                hostSeat: seatParams.hostSeat,
                modeId: seatParams.modeId,
              );
              return data == null ? null : UTDTokenResponse.fromJson(data);
            },
            userId: MyDataModel.getInstance().id.toString(),
            userName:
                MyDataModel.getInstance().name ??
                MyDataModel.getInstance().id.toString(),
            roomId: widget.roomId,
            roomOwnerId:
                (widget.ownerId.isNotEmpty && widget.ownerId != '0')
                    ? widget.ownerId
                    : RoomData.instance.room.ownerId.toString(),
            // Admins passed to the kit to bake the JOIN-TIME LiveKit role.
            // The kit reads this exactly once in _connect() (which runs BEFORE
            // wireRoleSync seeds adminsInRoom from enter-room), so we must union
            // the enter-room baseline (room.admins, populated at build) with the
            // live map (adminsInRoom, which carries in-session `_role_change`
            // deltas). Using adminsInRoom alone here left it empty at join, so a
            // real admin connected as 'audience' (no moderation perms) — which
            // also made the UI gates hide their own buttons. The app UI gates
            // keep reading the live adminsInRoom; this union only affects the
            // one-shot join-time token. Normalize ids (.toString()).
            adminIds: <String>{
              ...(RoomData.instance.room.admins ?? const <String>[])
                  .map((e) => e.toString()),
              ...RoomData.instance.adminsInRoom.keys.map((e) => e.toString()),
            },
            // Both sources above are usually EMPTY at build: navigation runs
            // before the enter-room response (which carries the admin list).
            // The kit NEVER waits on this resolver — everyone joins instantly
            // with the best-known role; when it resolves and lists this user
            // as an admin, the kit self-upgrades via the engine role endpoint
            // (server-side permissions + `_role_change`), so stored admins
            // still always end with full powers (owner bug 2026-06-12 stays
            // fixed) without gating anyone's join.
            adminIdsResolver: () async =>
                (await RoomData.instance.adminsWhenLoaded)
                    .map((e) => e.toString())
                    .toSet(),
            // Sync probe at token time: enter_room now fires at TAP (before
            // the route push), so its response often lands during the route
            // transition — in that case the join uses an `admin` token
            // directly and no upgrade round-trip is needed.
            adminIdsNow: () => <String>{
              ...(RoomData.instance.room.admins ?? const <String>[])
                  .map((e) => e.toString()),
              ...RoomData.instance.adminsInRoom.keys.map((e) => e.toString()),
            },
            layoutMode: RoomData.instance.room.mode ?? '3',
            onSeatChanged: (seats) {
              // Do NOT clear()+recreate every GlobalKey on each seat change:
              // minting a fresh key detaches it from the rendered seat widget for
              // a frame, so LuckyGiftController.getSeatPosition() reads a null
              // currentContext and the gift animation never targets the seat.
              // Keep stable per-userId keys: reuse existing, add new, drop gone.
              final currentUserIds = <String>{};
              seatAvatarIds.clear();
              for (final seat in seats) {
                final userId = seat.occupantUserId;
                if (userId == null || userId.isEmpty) continue;
                currentUserIds.add(userId);
                seatAvatarKeys.putIfAbsent(userId, () => GlobalKey());
                seatAvatarIds[seat.index] = userId;
              }
              // Remove keys only for users who actually left their seat.
              seatAvatarKeys.removeWhere((uid, _) => !currentUserIds.contains(uid));
            },
            onControllerReady: (controller) {
              // Server-driven engine host (utd_stream_host from /config/settings):
              // when the backend project runs on a non-default engine (e.g. the
              // shared test engine) re-point the kit BEFORE it connects —
              // onControllerReady fires ahead of the widget's _connect(), so the
              // token mint and all in-room ops hit the right host. Empty host =
              // keep the kit's built-in production hosts.
              final streamHost = RealtimeConfig.utdStreamHost;
              if (streamHost != null && streamHost.isNotEmpty) {
                controller.initApi(
                  baseUrl: streamHost,
                  tokenBaseUrl: streamHost,
                  appId: RealtimeConfig.utdStreamAppId ??
                      RealtimeConfig.defaultUtdStreamAppId,
                  appKey: RealtimeConfig.utdStreamAppKey ??
                      RealtimeConfig.defaultUtdStreamAppKey,
                );
              }
              // Request BLUETOOTH_CONNECT on Android 12+ for ALL room members
              // (not just mic users), so listeners also route audio to Bluetooth.
              if (Platform.isAndroid) {
                // Serialized + never throws (central permission requester).
                Methods.requestPermission(Permission.bluetoothConnect);
              }
              // Configure the audio session for Bluetooth (HFP/SCO + A2DP) and
              // route audio to a connected headset, falling back to the
              // loudspeaker. Single source of truth for routing — overrides the
              // kit's join-time speaker decision and tracks headset changes.
              _setupBluetoothAwareAudio(controller);
              controller.navigatorKey = navKey;
              controller.minimize.configure(
                UTDMinimizeConfig(
                  roomImage: EndPoints.getImage(
                    RoomData.instance.room.roomCover,
                  ),
                  onClose: () {
                    final navContext = SafeNavigator.context;
                    if (navContext == null) return;
                    di<RoomStateManager>().exitRoom(navContext);
                  },
                ),
              );
              RoomData.instance.utdController = controller;
              RoomData.instance.wireRoleSync(controller);
              controller.onForceExit = () async {
                final ctx = navKey.currentContext;
                if (ctx == null) return;
                Methods.showToast(
                  ctx,
                  message: StringManager.unableToConnect.tr(),
                  isError: true,
                );
                await di<RoomStateManager>().exitRoom(ctx);
                final popCtx = navKey.currentContext;
                if (popCtx != null) {
                  Navigator.popUntil(
                    popCtx,
                    (route) => route.settings.name == Routes.layout,
                  );
                }
              };
            },
            onConnectError: (error, stackTrace) async {
              // Initial token-generation / connect failure. Room ENTRY is NOT
              // tied to UTD Stream: enter_room (app REST) already ran, so the
              // user STAYS in the room. UTD Stream only powers audio/video and
              // realtime broadcast — those won't work without it, which is
              // acceptable. Browsing, gift and lucky-gift sending (app REST)
              // keep working. The kit's reconnection force-exit timer only ARMS
              // after a SUCCESSFUL connect, so when connect never succeeds,
              // staying here will NOT auto-exit later. Show a small
              // non-blocking notice ONCE — do NOT exitRoom / pop. The genuine
              // teardown paths (onForceExit / ban / live-ended) are untouched.
              if (_streamNoticeShown) return;
              _streamNoticeShown = true;
              final ctx = navKey.currentContext;
              if (ctx == null) return;
              Methods.showToast(
                ctx,
                message: StringManager.liveServiceUnavailable.tr(),
                isError: false,
              );
            },
            config: UTDAudioRoomConfig(
              turnOnMicrophoneWhenJoining: false,
              useSpeakerWhenJoining: true,
              showControlsBar: false,
              emptySeatBuilder:
                  (index, size) => EmptySeat(index: index, size: size),
              lockedSeatBuilder:
                  (index, size) => LockedSeatWidget(index: index, size: size),
              avatarBuilder:
                  (userId, size, attributes, isMuted, seatIndex, userName) =>
                      SeatAvatarWidget(
                        // Register the stable per-userId key HERE, not only in
                        // onSeatChanged: the kit may build the avatar before
                        // onSeatChanged registers the key, leaving key:null so
                        // the GlobalKey never attaches to a live render box and
                        // the lucky-gift fly-to-seat reads a null context. Lazy
                        // putIfAbsent guarantees the key is always attached.
                        key: seatAvatarKeys.putIfAbsent(userId, () => GlobalKey()),
                        userId: userId,
                        attributes: attributes,
                        size: size,
                      ),
              userInRoomAttributes: {
                'fr': MyDataModel.getInstance().frame ?? "",
                'avatar': EndPoints.getImage(
                  MyDataModel.getInstance().profile?.image,
                ),
                'frt': MyDataModel.getInstance().frameType ?? "",
                'cn': MyDataModel.getInstance().vip1?.colorName ?? "",
                // Local user's name so their seat initial is their name letter,
                // not '?' when the avatar URL fails to load.
                'name': MyDataModel.getInstance().name ?? "",
              },
              backgroundWidget: BlocBuilder<RoomOverlayCubit, RoomOverlayState>(
                bloc: di<RoomOverlayCubit>(),
                buildWhen: (prev, curr) => prev.showPk != curr.showPk,
                builder: (context, modeState) {
                  return Stack(
                    fit: StackFit.expand,
                    children: [
                      const BackgroundWidget(isAudioRoom: true),
                      if (modeState.showPk)
                        Positioned.fill(
                          child: Container(
                            decoration: BoxDecoration(
                              image: DecorationImage(
                                image: AssetImage(AssetsManager.pkBackground),
                                fit: BoxFit.cover,
                              ),
                            ),
                          ),
                        ),
                    ],
                  );
                },
              ),
              headerWidget: RoomHeader(
                imageColorEntity: RoomData.instance.room.ownerImageColor,
                specialIdImage: RoomData.instance.room.ownerSpecialId ?? "",
              ),
              messagesWidget: const TabbedMessagesView(),
              controlsBarWidget: const ButtomBarWidget(),
              foregroundWidget: ForegroundWidget(
                imageColorEntity: widget.imageColorEntity,
                specialIdImage: widget.specialIdImage,
                isAudioRoom: true,
                giftButtonCallBack: () {
                  bottomDailog(
                    context: mainContext,
                    barrierColor: ColorManager.transparent,
                    widget: GiftScreen(
                      users: RoomService.instance.getAllUsers(),
                      roomData: RoomData.instance.room,
                      myDataModel: widget.userModel,
                      isSingleUser: false,
                      isAudioRoom: true,
                      userId: null,
                      userImage: null,
                      userName: null,
                    ),
                  );
                },
              ),
              pkWidget: BlocBuilder<RoomOverlayCubit, RoomOverlayState>(
                bloc: di<RoomOverlayCubit>(),
                buildWhen: (prev, curr) => prev.showPk != curr.showPk,
                builder: (context, modeState) {
                  if (!modeState.showPk) return const SizedBox();
                  return PKWidget(
                    scoreBlueTeam: PkController.scoreBlue,
                    scoreRedTem: PkController.scoreRed,
                    isHost:
                        MyDataModel.getInstance().id ==
                        RoomData.instance.room.ownerId,
                    ownerId: RoomData.instance.room.ownerId.toString(),
                    notifyRoom: activePK,
                    roomId: RoomData.instance.room.id.toString(),
                  );
                },
              ),
            ),
            onSeatTap: (index, seat) {
              if (index == 0 && !widget.isHost && seat.isEmpty) {
                return;
              }
              final user =
                  seat.occupantUserId != null
                      ? UTDParticipant(id: seat.occupantUserId!, name: '')
                      : null;
              final navContext = SafeNavigator.context;
              if (navContext == null) return;
              showSeatOptionsSheet(navContext, index, user);
            },
            modes: _buildRoomModes(),
          ),
        ),
      ),
    );
  }
}
