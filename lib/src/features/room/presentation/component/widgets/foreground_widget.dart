import 'dart:async';
import 'package:carousel_slider/carousel_slider.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter_svga/flutter_svga.dart';
import 'package:general/src/core/index.dart';

import 'package:general/src/core/widgets/bottom_dialog.dart';
import 'package:general/src/core/widgets/cache/cache_alpha_widget.dart';
import 'package:general/src/core/widgets/cache/cache_vap_widget.dart';
import 'package:general/src/core/widgets/cache/cache_video_widget.dart';
import 'package:general/src/features/auth/domain/entities/my_data_entity.dart';
import 'package:general/src/features/home/domain/entities/carousel_entity.dart';
import 'package:general/src/features/home/presentation/home/bloc/get_carousel_manager/get_carousel_bloc.dart';
import 'package:general/src/features/home/presentation/home/view/components/carousel_page_view.dart';
import 'package:general/src/features/room/presentation/component/pk/counter_time_pk_widget.dart';
import 'package:general/src/features/room/presentation/gifts/controller/lucky_gift_service.dart';
import 'package:general/src/features/room/presentation/gifts/view/component/normal_gift/gift_bottom_bar.dart';
import 'package:general/src/features/room/presentation/gifts/bloc/gift_bloc/gift_bloc.dart';
import 'package:general/src/features/room/presentation/gifts/view/component/gift_banners/lucky_gift_win.dart';
import 'package:general/src/features/room/presentation/gifts/bloc/lucky_gift_win_bloc/lucky_gift_win_bloc.dart';
import 'package:general/src/features/room/presentation/gifts/bloc/lucky_gift_win_bloc/lucky_gift_win_state.dart';
import 'package:general/src/features/room/presentation/gifts/view/component/gift_banners/sender_balance_banner.dart';
import 'package:general/src/features/room/presentation/gifts/view/widgets/show_normal_gift.dart';
import 'package:general/src/features/room/presentation/lucky_box/widgets/lucky_box_icon.dart';
import 'package:general/src/features/room/presentation/manager/alpha_gift_manager/alpha_gift_manager_bloc.dart';
import 'package:general/src/features/room/presentation/manager/alpha_gift_manager/alpha_gift_manager_state.dart';
import 'package:general/src/features/room/presentation/manager/clear_mode_manager/clear_mode_bloc.dart';
import 'package:general/src/features/room/presentation/manager/clear_mode_manager/clear_mode_state.dart';
import 'package:general/src/features/room/presentation/manager/manager_pk/pk_bloc.dart';
import 'package:general/src/features/room/presentation/manager/manager_pk/pk_states.dart';
import 'package:general/src/features/room/presentation/music/view/component/music_widget.dart';
import 'package:general/src/features/room/presentation/super_bomb/view/widgets/super_bomb_widget.dart';
import 'package:general/src/features/room/presentation/youtube/view/youtube_controller.dart';
import 'package:general/src/features/room/room.dart';
import 'package:general/src/features/room/presentation/component/widgets/cp_overlay_widget.dart';

class ForegroundWidget extends StatefulWidget {
  final VoidCallback? giftButtonCallBack;
  final String? specialIdImage;
  final ImageColorEntity? imageColorEntity;
  final bool isAudioRoom;

  const ForegroundWidget({
    super.key,
    this.giftButtonCallBack,
    this.specialIdImage,
    this.imageColorEntity,
    this.isAudioRoom = false,
  });

  static ValueNotifier<bool> openGames = ValueNotifier<bool>(false);

  @override
  State<ForegroundWidget> createState() => _ForegroundWidgetState();
}

class _ForegroundWidgetState extends State<ForegroundWidget>
    with TickerProviderStateMixin {
  EnterRoomSuccesMessageState? _cachedRoomState;
  bool _isAlreadyHandledMismatch = false;
  VoidCallback? _connectionStateListener;

  // Incremented when RoomData.instance.room changes so only the inner Stack
  // rebuilds instead of the full _ForegroundWidgetState build tree.
  late final ValueNotifier<int> _roomRevision;

  // Stable, full-screen anchor for the lucky-gift fly-to-seat animation.
  // Seat positions are resolved in GLOBAL screen coords; the flying candy must
  // be Positioned inside a layer whose origin is known and stable. We attach
  // this key to a Positioned.fill host so its RenderBox always spans the whole
  // overlay (origin == overlay origin) regardless of where the UTD kit mounts
  // the foreground widget. The animation converts global → this box's local
  // space, so the candy lands exactly on the mic seat.
  final GlobalKey _luckyOverlayKey = GlobalKey();

  @override
  void initState() {
    super.initState();
    _roomRevision = ValueNotifier<int>(0);

    // The in-room banner box has its own placement (in_room) — fetch it here
    // because no home tab loads this list.
    di<GetCarouselBloc>().add(const GetInRoomCarouselEvent(isLoading: false));

    // Listen to UTD Kit connection state changes (replaces the legacy engine's room-state stream)
    _attachConnectionListener(RoomData.instance.utdController);

    // utdController may be set after this widget builds (via addPostFrameCallback
    // in onControllerReady). Listen to the notifier so we can attach the
    // connection listener as soon as the controller becomes available.
    RoomData.instance.utdControllerNotifier.addListener(_onUtdControllerChanged);

    // Check if BLoC already emitted success state before this widget was built
    // (e.g. locked room flow where password was already verified).
    final currentState = di<RoomHandlerBloc>().state;
    if (currentState is EnterRoomSuccesMessageState) {
      _cachedRoomState = currentState;
      // If the engine is already connected, this is a restore from minimize —
      // skip onEnterRoomSuccess to avoid re-triggering enter_room, take_seat,
      // and extra-data API calls.
      final isRestoreFromMinimize =
          RoomData.instance.utdController?.isConnected ?? false;
      if (isRestoreFromMinimize) {
        _isAlreadyHandledMismatch = true;
      } else {
        _handleConnectionStateChange();
      }
    }

    RoomData.instance.rtmHandler?.updateTickerProvider(this);
  }

  void _onUtdControllerChanged() {
    final controller = RoomData.instance.utdController;
    if (controller != null) {
      _attachConnectionListener(controller);
      _handleConnectionStateChange();
    }
  }

  void _attachConnectionListener(UTDRoomController? controller) {
    if (controller == null || _connectionStateListener != null) return;
    _connectionStateListener = _handleConnectionStateChange;
    controller.connectionState.addListener(_connectionStateListener!);
  }

  void _handleConnectionStateChange() {
    final utdController = RoomData.instance.utdController;
    if (utdController == null) return;

    final isConnected = utdController.isConnected;
    Methods.printLog('UTD connection state changed: isConnected=$isConnected');

    if (_cachedRoomState != null && isConnected && !_isAlreadyHandledMismatch) {
      // With LiveKit, room identity is managed by the token — no room ID mismatch
      // possible like with the legacy engine. Proceed directly to success handling.
      _isAlreadyHandledMismatch = true;
      loadSecondaryData(
        roomId: RoomData.instance.room.id.toString(),
        userCoins: '${MyDataModel.getInstance().myStore?.coinsNew}',
      );
      onEnterRoomSuccess();

      // Remove listener after successful connection
      if (_connectionStateListener != null) {
        utdController.connectionState.removeListener(_connectionStateListener!);
        _connectionStateListener = null;
      }
    }
  }

  @override
  void dispose() {
    _roomRevision.dispose();
    RoomData.instance.utdControllerNotifier.removeListener(_onUtdControllerChanged);
    if (_connectionStateListener != null) {
      RoomData.instance.utdController?.connectionState
          .removeListener(_connectionStateListener!);
      _connectionStateListener = null;
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MultiBlocListener(
      listeners: [
        BlocListener<RoomHandlerBloc, RoomHandlerStates>(
          bloc: di<RoomHandlerBloc>(),
          listener: (context, state) async {
            if (state is EnterRoomSuccesMessageState) {
              _cachedRoomState = null;
              _cachedRoomState = state;
              _isAlreadyHandledMismatch = false;
              _handleConnectionStateChange();

              RoomBackground.imgBackground.value =
                  state.room.roomBackground ?? "";
              YouTubeController.isCinemaMode.value =
                  state.room.mode.toString() == '5';
              di<GiftBloc>().add(
                  UpdateRoomGiftsPriceEvent(price: '${state.room.giftPrice}'));
              Methods.setDataRooms(
                data: {
                  "name": state.room.roomName ?? "",
                  "ownerId": state.room.ownerId,
                  "id": state.room.id,
                  "cover": state.room.roomCover,
                  "roomIntro": state.room.roomIntro,
                  "roomRule": state.room.roomRule,
                  "background": state.room.roomBackground,
                  "mode": state.room.mode,
                  "uuid": state.room.uuidOwnerRoom,
                  "giftPrice": state.room.giftPrice,
                  "ownerSpecialId": state.room.ownerSpecialId,
                  'ownerIdColors': state.room.ownerImageColor?.color
                },
                id: state.room.id.toString(),
              );

              LockRoomDialog.roomIsLoked = state.room.roomPassStatus ?? false;
              RoomData.instance.isRoomLocked.value =
                  state.room.roomPassStatus ?? false;
              RoomData.instance.room = state.room;
              _roomRevision.value++;

              // Admin membership is seeded from the engine (RoomData.wireRoleSync),
              // not from the app-backend `room.admins` list.
              if (RoomData.instance.room.showPk == 1) {
                showPK(this);
                Methods.printLog(
                  "showPK called with room data: ${RoomData.instance.room.showPk}",
                );
              }
              if (RoomData.instance.room.isPK == 1) {
                PkController.isPK.value = true;
                PkController.showPK.value = true;
                PkController.timeMinutePK =
                    RoomData.instance.room.pkModel?.timeMPk ?? 0;
                PkController.timeSecondPK =
                    RoomData.instance.room.pkModel?.timeSPk ?? 0;
                PkController.scoreTeam1 =
                    RoomData.instance.room.pkModel?.team1Score ?? 0;
                PkController.scoreTeam2 =
                    RoomData.instance.room.pkModel?.team2Score ?? 0;
                PkController.precantgeTeam1 = RoomData
                        .instance.room.pkModel?.percentageTeam1
                        ?.toDouble() ??
                    0;
                PkController.precantgeTeam2 = RoomData
                        .instance.room.pkModel?.percentageTeam2
                        ?.toDouble() ??
                    0;
                PKWidget.pkId =
                    RoomData.instance.room.pkModel?.pkId.toString() ?? '';
                PKWidget.isStartPK.value = true;
                PkController.updatePKNotifier.value =
                    PkController.updatePKNotifier.value + 1;

                di<SetTimerPK>().start(
                  context,
                  RoomData.instance.room.ownerId.toString(),
                  RoomData.instance.room.id.toString(),
                );
              }
            } else if (state is EnterRoomErrorMessageState) {
              di<RoomStateManager>().onRoomExited();

              final navContext = SafeNavigator.context;
              if (navContext == null) return;

              if (state.errorMessage
                  .toString()
                  .contains("The room is locked please enter the password")) {
                context.popRoute();
                di<RoomStateManager>().navigateToRoom(
                  RoomEntryRequest(
                    context: navContext,
                    roomData: RoomEntity(
                      name: RoomData.instance.room.roomName,
                      ownerId: RoomData.instance.room.ownerId,
                      id: RoomData.instance.room.id,
                      roomBackground: RoomData.instance.room.roomCover,
                      passwordStatus: true,
                      mode: RoomData.instance.room.mode,
                      uuidOwnerRoom: RoomData.instance.room.uuidOwnerRoom,
                    ),
                    isLive: false,
                  ),
                );
              } else {
                // Unmount the room screen (and the UTD kit widget + its
                // connectionState listeners) BEFORE exitRoom disposes the
                // controller. Otherwise the kit/listeners touch a disposed
                // ValueNotifier<UTDConnectionState> → the red "used after
                // disposed" crash on a denied entry.
                Navigator.of(navContext).popUntil(
                    (route) => route.settings.name == Routes.layout);
                await di<RoomStateManager>().exitRoom(
                  navContext,
                  callback: () async {
                    await Future.delayed(const Duration(milliseconds: 300));

                    final dialogContext = SafeNavigator.context;
                    if (dialogContext == null) return;
                    showDialog(
                      context: dialogContext,
                      builder: (_) {
                        return AnimatedDialog(
                          titleColor: ColorManager.roomTextPrimary,
                          descriptionColor: ColorManager.roomSecondaryText,
                          confirmTitleColor: ColorManager.roomButtonText,
                          color: ColorManager.roomGold,
                          cancelTextColor: ColorManager.roomTextPrimary,
                          title: StringManager.unExpectedError.tr(),
                          description: state.errorMessage,
                          needPopScope: true,
                          conText: StringManager.exit.tr(),
                          isHideConfirm: false,
                          isUpdateDialog: false,
                          onTap: () {
                            SafeNavigator.pop();
                          },
                        );
                      },
                    );
                  },
                );
              }
            }
          },
        ),
        BlocListener<PrivacyBloc, PrivacyState>(
          bloc: di<PrivacyBloc>(),
          listener: (context, state) {
            if (state is SuccessState) {
              Methods.showToast(context, message: state.massege);
            } else if (state is ErrorState) {
              Methods.showToast(context, message: state.massege, isError: true);
            } else if (state is LoadingState) {
              Methods.showToast(context, isLoading: true);
            }
          },
        ),
        BlocListener<ClearModeBloc, ClearModeState>(
          bloc: di<ClearModeBloc>(),
          listener: (context, state) {
            if (state is ClearModeLoadingState) {
              Methods.showToast(context, isLoading: true);
            } else if (state is ClearModeSuccessState) {
              Methods.showToast(context, message: StringManager.success.tr());
              di<FetchUserDataBloc>()
                  .add(const FetchMyDataEvent(isLoading: false));
            } else if (state is ClearModeErrorState) {
              Methods.showToast(context, message: state.message, isError: true);
            }
          },
        ),
        BlocListener<PKBloc, PKStates>(
          bloc: di<PKBloc>(),
          listener: (context, state) {
            // The host opens/closes PK over REST; the backend's data-channel
            // broadcast is not echoed back to the initiator, so the host's own
            // seats never react to it. Reflect the host's PK toggle locally
            // here (audience members still update via the RTM PK handler).
            if (state is ShowStateSuccess) {
              showPK(this);
            } else if (state is HidePKStateSuccess) {
              hidePK();
            }
          },
        ),
      ],
      child: ValueListenableBuilder<int>(
        valueListenable: _roomRevision,
        builder: (context, _, __) => Stack(
          children: [
            if (widget.isAudioRoom &&
                RoomData.instance.utdController != null) ...[
              ValueListenableBuilder<UTDRoomMode>(
                valueListenable:
                    RoomData.instance.utdController!.currentMode,
                builder: (context, mode, _) {
                  final rows =
                      RoomData.cpGridRowsForMode(mode.id);
                  if (rows == null) return const SizedBox();
                  final seatSize = mode.computeSeatSize(
                      MediaQuery.of(context).size.width);
                  return CpOverlayWidget(
                    rows: rows,
                    seatSize: seatSize,
                    topOffset: 0,
                  );
                },
              ),
            ],
            ValueListenableBuilder<bool>(
              valueListenable: PkController.showPK,
              builder: (context, showPK, _) {
                final controller = PkController.animationControllerRedTeam;
                if (!showPK || controller == null) {
                  return const SizedBox();
                }
                return Positioned(
                  top: showPK ? 265.h : 235.h,
                  bottom: 415.h,
                  right: 140.w,
                  child: RepaintBoundary(
                    child: SVGAImage(controller),
                  ),
                );
              },
            ),
            ValueListenableBuilder<bool>(
              valueListenable: PkController.showPK,
              builder: (context, showPK, _) {
                final controller = PkController.animationControllerBlueTeam;
                if (!showPK || controller == null) {
                  return const SizedBox();
                }
                return Positioned(
                  top: showPK ? 265.h : 235.h,
                  bottom: 415.h,
                  left: 140.w,
                  child: RepaintBoundary(
                    child: SVGAImage(controller),
                  ),
                );
              },
            ),
            Positioned(
              bottom: 65.h,
              right:
                  Directionality.of(context) == TextDirection.rtl ? null : 5.w,
              left:
                  Directionality.of(context) == TextDirection.rtl ? 5.w : null,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  ValueListenableBuilder<bool>(
                    valueListenable: SuperBoomController.isSuperBoomVisible,
                    builder: (context, isShow, child) {
                      return isShow && ConstantsManager.isShowRoomBoom
                          ? const RepaintBoundary(
                              child: SuperBombWidget(),
                            )
                          : const SizedBox();
                    },
                  ),
                  15.hBox,
                  if (GamesAccess.canPlay)
                    GestureDetector(
                      onTap: () {
                        if (!GamesAccess.canPlay) {
                          Methods.showToast(
                            context,
                            message:
                                StringManager.gamesNotAvailableForYou.tr(),
                            isError: true,
                          );
                          return;
                        }
                        if (HomePage.isConnectToInternet == true) {
                          final navContext = SafeNavigator.context;
                          if (navContext == null) return;
                          bottomDailog(
                            context: navContext,
                            widget: GamesView(
                              roomId: RoomData.instance.room.id.toString(),
                            ),
                          );
                        } else {
                          Methods.showToast(
                            context,
                            message: StringManager.pleaseCheckInternet.tr(),
                            isError: true,
                          );
                        }
                      },
                      child: CircleAvatar(
                        backgroundColor: ColorManager.blue,
                        radius: 20.w,
                        child: Image.asset(
                          AssetsManager.gameIconHome,
                          color: ColorManager.whiteColor,
                          width: 30.w,
                        ),
                      ),
                    ),
                  15.hBox,
                  BlocBuilder<GetCarouselBloc, GetCarouselState>(
                    bloc: di<GetCarouselBloc>(),
                    buildWhen: (prev, curr) =>
                        prev.inRoomCarousels != curr.inRoomCarousels,
                    builder: (context, state) {
                      final List<CarouselEntity> sliders = state
                          .inRoomCarousels
                          .where((c) => c.type == 'event')
                          .toList();
                      return SizedBox(
                        height: 75,
                        width: 75,
                        child: RepaintBoundary(
                          child: ClipRRect(
                            borderRadius: 5.radius,
                            child: Stack(
                              children: [
                                CarouselSlider(
                                  items: List.generate(
                                    sliders.length,
                                    (index) => Padding(
                                      padding: context.paddingSymmetric(
                                          horizontal: 0),
                                      child: GestureDetector(
                                        onTap: () async {
                                          switch (sliders[index].type) {
                                            case 'normal':
                                              break;
                                            case 'room':
                                              di<RoomStateManager>()
                                                  .navigateToRoom(
                                                RoomEntryRequest(
                                                  context: context,
                                                  roomData: RoomEntity(
                                                    passwordStatus:
                                                        sliders[index]
                                                            .myRoomData
                                                            ?.passwordStatus,
                                                    ownerId:
                                                        sliders[index].ownerId,
                                                    id: sliders[index]
                                                            .myRoomData
                                                            ?.id ??
                                                        0,
                                                    name: sliders[index]
                                                            .myRoomData
                                                            ?.name ??
                                                        "",
                                                    cover: sliders[index]
                                                            .myRoomData
                                                            ?.cover ??
                                                        "",
                                                    roomBackground:
                                                        sliders[index]
                                                                .myRoomData
                                                                ?.background ??
                                                            "",
                                                    mode: sliders[index]
                                                            .myRoomData
                                                            ?.toString() ??
                                                        '',
                                                    uuidOwnerRoom:
                                                        sliders[index]
                                                                .myRoomData
                                                                ?.ownerUuid ??
                                                            "",
                                                    giftPrice: sliders[index]
                                                            .myRoomData
                                                            ?.giftPrice ??
                                                        "",
                                                  ),
                                                  isLive: false,
                                                ),
                                              );
                                              break;
                                            case 'link':
                                              final url =
                                                  sliders[index].url ?? "";
                                              if (url.contains("wa.me") ||
                                                  url.contains(
                                                      "whatsapp.com")) {
                                                Methods()
                                                    .whatsAppLink(context, url);
                                              } else {
                                                if (context.mounted) {
                                                  Navigator.pushNamed(
                                                    context,
                                                    Routes.webViewEvents,
                                                    arguments: {
                                                      'url': url,
                                                      'type': 'events',
                                                    },
                                                  );
                                                }
                                              }
                                              break;
                                            case 'event':
                                              String token =
                                                  Methods.getUserToken();
                                              String lang = HiveManager()
                                                      .getData<String>(
                                                          KeysManager.USER_BOX,
                                                          KeysManager
                                                              .LANG_CODE_KEY) ??
                                                  "en";
                                              String? baseUrl =
                                                  EndPoints.baseURL;
                                              String? bucketName =
                                                  EndPoints.storageURL;

                                              if (context.mounted &&
                                                  sliders[index].url != null &&
                                                  sliders[index]
                                                      .url!
                                                      .isNotEmpty) {
                                                Uri originalUri = Uri.parse(
                                                    sliders[index].url!);
                                                Map<String, String?>
                                                    updatedParams = Map.from(
                                                        originalUri
                                                            .queryParameters);

                                                updatedParams.putIfAbsent(
                                                    'token', () => token);
                                                updatedParams.putIfAbsent(
                                                    'lang', () => lang);
                                                updatedParams.putIfAbsent(
                                                    'base_url', () => baseUrl);
                                                updatedParams.putIfAbsent(
                                                    'bucket_name',
                                                    () => bucketName);

                                                Uri finalUri =
                                                    originalUri.replace(
                                                        queryParameters:
                                                            updatedParams);
                                                String finalUrl =
                                                    finalUri.toString();

                                                Navigator.pushNamed(
                                                  context,
                                                  Routes.webViewEvents,
                                                  arguments: {
                                                    'url': finalUrl,
                                                    'type': 'events',
                                                  },
                                                );
                                              }
                                          }
                                        },
                                        child: sliders[index].type == 'event'
                                            ? EventSliderItem(
                                                image: sliders[index].img,
                                                avatar: sliders[index].avatar,
                                                avatar2:
                                                    sliders[index].cpAvatar2,
                                                name: sliders[index].cpName,
                                                name2: sliders[index].cpName2,
                                                eventType:
                                                    sliders[index].eventType,
                                              )
                                            : ImageViewWidget(
                                                url: sliders[index].img,
                                                boxFit: BoxFit.fill,
                                                width: ScreenUtil().screenWidth,
                                              ),
                                      ),
                                    ),
                                  ),
                                  options: CarouselOptions(
                                    initialPage: 0,
                                    reverse: false,
                                    autoPlay: true,
                                    viewportFraction: 1,
                                    enableInfiniteScroll: true,
                                    height: 110.h,
                                    autoPlayInterval:
                                        const Duration(seconds: 5),
                                    autoPlayAnimationDuration:
                                        const Duration(milliseconds: 600),
                                    autoPlayCurve: Curves.easeInOut,
                                    enlargeCenterPage: false,
                                    onPageChanged: (index, reason) {
                                      di<GetCarouselBloc>().add(
                                          ChangeCarsouleIndex(
                                              index: index, type: 'homeTop'));
                                    },
                                  ),
                                ),
                                Positioned(
                                  left: 15,
                                  bottom: 2,
                                  child: Padding(
                                    padding:
                                        context.paddingSymmetric(vertical: 8),
                                    child: BlocBuilder<GetCarouselBloc,
                                        GetCarouselState>(
                                      bloc: di<GetCarouselBloc>(),
                                      buildWhen: (prev, curr) =>
                                          prev.topHomeIndex !=
                                          curr.topHomeIndex,
                                      builder: (context, state) {
                                        int currentIndex = state.topHomeIndex;

                                        return Row(
                                          mainAxisAlignment:
                                              MainAxisAlignment.center,
                                          children: List.generate(
                                              sliders.length, (index) {
                                            bool isActive =
                                                index == currentIndex;
                                            return AnimatedContainer(
                                              duration: const Duration(
                                                  milliseconds: 100),
                                              margin:
                                                  const EdgeInsets.symmetric(
                                                      horizontal: 4),
                                              width: isActive ? 6 : 4,
                                              height: 4,
                                              decoration: BoxDecoration(
                                                color: isActive
                                                    ? Colors.white
                                                    : Colors.grey,
                                                borderRadius:
                                                    BorderRadius.circular(4),
                                              ),
                                            );
                                          }),
                                        );
                                      },
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                  15.hBox,
                  ValueListenableBuilder(
                    valueListenable: LuckyBoxVariables.notifierLuckyBox,
                    builder: (context, _, child) {
                      if (LuckyBoxVariables.luckyBoxMap['luckyBoxes'] != [] &&
                          LuckyBoxVariables.luckyBoxMap['luckyBoxes'] != null) {
                        return LuckyBoxIcon(
                          giftButtonCallBack: widget.giftButtonCallBack,
                          roomId: '${RoomData.instance.room.id}',
                        );
                      } else {
                        return const SizedBox();
                      }
                    },
                  ),
                ],
              ),
            ),
            const _GiftDisplayStack(),
            if (di<RoomStateManager>().isInAudioRoom)
              MusicWidget(room: RoomData.instance.room),
            BlocConsumer<LuckyGiftBannerBloc, LuckyGiftBannerState>(
              bloc: di<LuckyGiftBannerBloc>(),
              buildWhen: (previous, current) {
                if (current is SendLuckyGiftLoadingState) return false;
                return true;
              },
              listener: (context, state) {
                if (state is SendLuckyGiftSucssesState) {
                  RoomData.instance.myCoins.value = state.data.userCoins ?? "";
                  di<GiftBloc>().add(
                    UpdateRoomGiftsPriceEvent(
                      price: state.data.giftPrice.toString(),
                    ),
                  );
                  // The SENDER already animated optimistically per-tap (see
                  // lucky_candy._showOptimisticTapAnimation), so the response is
                  // NOT a trigger for the sender's own animation anymore — that
                  // is what batched into a burst after the network replied. The
                  // response only carries the authoritative win data
                  // (giftNum/totalWin/totalPk) every OTHER client needs, so we
                  // forward it over RTM without touching local seats.
                  final data = state.data;
                  final indexes = data.position ?? const <int>[];
                  final receiversId = data.receiversId ?? const <String>[];
                  di<LuckyGiftAnaimationManagerBloc>().add(
                    RebroadcastLuckyGiftEvent(
                      giftPrice: data.giftPrice,
                      index: indexes,
                      ids: receiversId,
                      image: data.giftImage ?? "",
                      reciverName: data.receiverName ?? "",
                      senderName: data.senderName ?? "",
                      senderImage: data.senderImg ?? "",
                      giftNum: data.giftNum ?? 0,
                      giftPriceT: data.giftPriceT ?? 0,
                      totalWin: data.totalWin ?? 0,
                      totalPk: data.totalPk ?? 0,
                    ),
                  );
                  return;
                } else if (state is SendLuckyGiftErrorStateState) {
                  Methods.showToast(
                    context,
                    message: state.error,
                    isError: true,
                  );
                  return;
                }
              },
              builder: (context, state) {
                if (state is SendLuckyGiftSucssesState) {
                  return Stack(
                    children: [
                      Positioned(
                        top: 450.h,
                        left: 20.w,
                        child: LuckGiftBannerWidget(
                          reciverName: state.data.receiverName ?? "",
                          giftNum: state.giftNum,
                          giftImage: state.data.giftImage ?? "",
                          totalWin: state.totalWin,
                          onHideComplete: () {
                            LuckyGiftController.instance.tempLuckyGiftData
                                .clear();
                            if (GiftBottomBar.typeCandy.value !=
                                TypeCandy.luckyCandy) {
                              LuckyGiftService.instance.endAllLuckyGift();
                            }
                          },
                        ),
                      ),
                      Positioned(
                        top: 450.h + 60.h,
                        right: 0,
                        child: SenderBalanceBanner(
                          giftPrice: state.data.userCoins ?? "",
                        ),
                      ),
                    ],
                  );
                } else if (state is SendLuckyGiftLoadingState) {
                  if (state.data == null) {
                    return const SizedBox();
                  } else {
                    return Stack(
                      children: [
                        Positioned(
                          top: 450.h,
                          left: 20.w,
                          child: LuckGiftBannerWidget(
                            reciverName: state.data?.receiverName ?? "",
                            giftNum: state.giftNum ?? 0,
                            giftImage: state.data?.giftImage ?? "",
                            totalWin: state.totalWin ?? 0,
                            onHideComplete: () {
                              LuckyGiftController.instance.tempLuckyGiftData
                                  .clear();
                              if (GiftBottomBar.typeCandy.value !=
                                  TypeCandy.luckyCandy) {
                                LuckyGiftService.instance.endAllLuckyGift();
                              }
                            },
                          ),
                        ),
                        Positioned(
                          top: 450.h + 60.h,
                          right: 0,
                          child: SenderBalanceBanner(
                            giftPrice: state.data?.userCoins ?? "",
                          ),
                        ),
                      ],
                    );
                  }
                } else {
                  return const SizedBox();
                }
              },
            ),
            BlocBuilder<LuckyGiftBannerForReciverBloc,
                LuckyGiftBannerForReciverState>(
              bloc: di<LuckyGiftBannerForReciverBloc>(),
              buildWhen: (previous, current) => previous != current,
              builder: (context, state) {
                if (state is LuckyGiftBannerForReciverSucssesState) {
                  return Positioned(
                    top: 510.h,
                    left: 20,
                    child: LuckGiftBannerWidget(
                      senderImg: state.senderImg,
                      senderName: state.senderName,
                      reciverName: state.receiverName,
                      giftNum: state.giftNum,
                      giftImage: state.giftImage,
                      totalWin: state.totalWin,
                      onHideComplete: () {
                        di<LuckyGiftBannerForReciverBloc>()
                            .add(const EndLuckyGiftBannerForReciverEvent());
                      },
                    ),
                  );
                } else {
                  return const SizedBox();
                }
              },
            ),
            BlocBuilder<LuckyGiftAnaimationManagerBloc,
                LuckyGiftAnaimationManagerState>(
              bloc: di<LuckyGiftAnaimationManagerBloc>(),
              buildWhen: (prev, curr) =>
                  prev.runtimeType != curr.runtimeType ||
                  (prev is LuckyGiftAnimationSucssesState &&
                      curr is LuckyGiftAnimationSucssesState &&
                      prev.data != curr.data),
              builder: (context, state) {
                if (state is LuckyGiftAnimationSucssesState) {
                  // Positioned.fill forces this host Stack to span the entire
                  // overlay, giving _luckyOverlayKey a stable full-screen
                  // RenderBox. LuckyOverlayAnchor exposes that key to every
                  // LuckyGiftSeatAnimation below so they convert global seat
                  // coords into THIS box's local space (no fragile per-item
                  // context, no zero-size collapse).
                  return Positioned.fill(
                    child: LuckyOverlayAnchor(
                      overlayKey: _luckyOverlayKey,
                      child: RepaintBoundary(
                        child: Stack(
                          key: _luckyOverlayKey,
                          fit: StackFit.expand,
                          children: [...state.data!],
                        ),
                      ),
                    ),
                  );
                } else {
                  return const SizedBox();
                }
              },
            ),
            ValueListenableBuilder<Map<String, dynamic>?>(
              valueListenable: ShowEntroWidget.showEntro,
              builder: (context, data, _) {
                if (data != null &&
                    data['wappelImage'] != null &&
                    data['wappelImage'] != "") {
                  return Positioned(
                    bottom: 300.h,
                    child: RepaintBoundary(
                      child: ShowEntroWidget(
                        userIntroData: data,
                      ),
                    ),
                  );
                } else {
                  return const SizedBox();
                }
              },
            ),
            const _SuperBoomVideoOverlay(),
            const _LuckyGiftWinOverlay(),
          ],
        ),
      ),
    );
  }
}

class _GiftDisplayStack extends StatelessWidget {
  const _GiftDisplayStack();

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Align(
          alignment: Alignment.bottomCenter,
          child: BlocBuilder<GiftBloc, GiftState>(
            bloc: di<GiftBloc>(),
            buildWhen: (prev, curr) =>
                prev.isShowGift != curr.isShowGift ||
                prev.giftType != curr.giftType,
            builder: (context, state) {
              if (state.isShowGift && state.giftType == ShowGiftType.image) {
                return RepaintBoundary(
                  child: ShowNormalGift(state: state),
                );
              }
              return const SizedBox();
            },
          ),
        ),
        Align(
          alignment: Alignment.bottomCenter,
          child: BlocBuilder<GiftBloc, GiftState>(
            bloc: di<GiftBloc>(),
            buildWhen: (prev, curr) =>
                prev.isShowGift != curr.isShowGift ||
                prev.giftType != curr.giftType ||
                prev.isFamousGift != curr.isFamousGift ||
                prev.gift != curr.gift,
            builder: (context, state) {
              if (state.isShowGift && state.giftType == ShowGiftType.mp4) {
                return RepaintBoundary(
                  child: state.isFamousGift == true
                      ? _FamousGiftMinimizeToggle(
                          child: (isMinimize) => IgnorePointer(
                            child: CacheVideoWidget(
                              height: isMinimize
                                  ? (ScreenUtil().screenWidth / 1.15).h
                                  : ScreenUtil().screenHeight,
                              width: isMinimize
                                  ? (ScreenUtil().screenWidth / 1.15).w
                                  : ScreenUtil().screenWidth,
                              videoUrl: EndPoints.getImage(state.gift),
                              isShowGift: true,
                            ),
                          ),
                        )
                      : Stack(
                          children: [
                            SizedBox(
                                height:
                                    MediaQuery.of(context).size.height * 0.105),
                            Align(
                              alignment: Alignment.bottomCenter,
                              child: SizedBox(
                                height:
                                    MediaQuery.of(context).size.height * 0.730,
                                width: MediaQuery.of(context).size.width,
                                child: CacheVideoWidget(
                                  videoUrl: EndPoints.getImage(state.gift),
                                  isShowGift: true,
                                ),
                              ),
                            ),
                          ],
                        ),
                );
              }
              return const SizedBox();
            },
          ),
        ),
        Align(
          alignment: Alignment.bottomCenter,
          child: BlocBuilder<AlphaGiftManagerBloc, AlphaGiftManagerState>(
            bloc: di<AlphaGiftManagerBloc>(),
            buildWhen: (prev, curr) =>
                prev.runtimeType != curr.runtimeType ||
                (prev is AlphaGiftManagerShowGift &&
                    curr is AlphaGiftManagerShowGift &&
                    (prev.isFamousGift != curr.isFamousGift ||
                        prev.giftPath != curr.giftPath)),
            builder: (context, state) {
              if (state is AlphaGiftManagerShowGift) {
                return RepaintBoundary(
                  child: state.isFamousGift == true
                      ? _FamousGiftMinimizeToggle(
                          child: (isMinimize) => CacheAlphaWidget(
                            height: isMinimize
                                ? (ScreenUtil().screenWidth / 1.15).h
                                : ScreenUtil().screenHeight,
                            width: isMinimize
                                ? (ScreenUtil().screenWidth / 1.15).w
                                : ScreenUtil().screenWidth,
                            url: EndPoints.getImage(state.giftPath),
                          ),
                        )
                      : Stack(
                          children: [
                            SizedBox(
                                height:
                                    MediaQuery.of(context).size.height * 0.105),
                            Align(
                              alignment: Alignment.bottomCenter,
                              child: CacheAlphaWidget(
                                url: EndPoints.getImage(state.giftPath),
                              ),
                            ),
                          ],
                        ),
                );
              }
              return const SizedBox();
            },
          ),
        ),
        Align(
          alignment: Alignment.bottomCenter,
          child: BlocBuilder<GiftBloc, GiftState>(
            bloc: di<GiftBloc>(),
            buildWhen: (prev, curr) =>
                prev.isShowGift != curr.isShowGift ||
                prev.giftType != curr.giftType ||
                prev.isFamousGift != curr.isFamousGift ||
                prev.gift != curr.gift,
            builder: (context, state) {
              if (state.isShowGift == true &&
                  state.giftType == ShowGiftType.svga) {
                return RepaintBoundary(
                  child: state.isFamousGift == true
                      ? _FamousGiftMinimizeToggle(
                          child: (isMinimize) => CacheSvgaWidget(
                            url: EndPoints.getImage(state.gift),
                            height: isMinimize
                                ? (ScreenUtil().screenWidth / 1.15).h
                                : ScreenUtil().screenHeight,
                            width: isMinimize
                                ? (ScreenUtil().screenWidth / 1.15).w
                                : ScreenUtil().screenWidth,
                            isShowGift: true,
                          ),
                        )
                      : CacheSvgaWidget(
                          url: EndPoints.getImage(state.gift),
                          isShowGift: true,
                        ),
                );
              }
              return const SizedBox();
            },
          ),
        ),
        BlocBuilder<GiftBloc, GiftState>(
          bloc: di<GiftBloc>(),
          buildWhen: (prev, curr) =>
              prev.isShowGift != curr.isShowGift ||
              prev.gift != curr.gift ||
              prev.giftType != curr.giftType ||
              prev.isFamousGift != curr.isFamousGift ||
              prev.isShowIntroFullScreen != curr.isShowIntroFullScreen,
          builder: (context, state) {
            if (state.isShowGift == true &&
                state.gift != "" &&
                state.giftType == ShowGiftType.vap) {
              return RepaintBoundary(
                child: state.isFamousGift == true
                    ? _FamousGiftMinimizeToggle(
                        child: (isMinimize) => IgnorePointer(
                          child: CachedVapWidget(
                            url: EndPoints.getImage(state.gift),
                            height: isMinimize
                                ? (ScreenUtil().screenWidth / 1.15).h
                                : ScreenUtil().screenHeight,
                            width: isMinimize
                                ? (ScreenUtil().screenWidth / 1.15).w
                                : ScreenUtil().screenWidth,
                          ),
                        ),
                      )
                    : IgnorePointer(
                        child: Stack(
                          children: [
                            SizedBox(
                                height:
                                    MediaQuery.of(context).size.height * 0.105),
                            SizedBox(
                              height: state.isShowIntroFullScreen
                                  ? MediaQuery.of(context).size.height
                                  : MediaQuery.of(context).size.height * 0.730,
                              width: MediaQuery.of(context).size.width,
                              child: CachedVapWidget(
                                url: EndPoints.getImage(state.gift),
                                height: ScreenUtil().screenHeight,
                                width: ScreenUtil().screenWidth,
                              ),
                            ),
                          ],
                        ),
                      ),
              );
            }
            return const SizedBox();
          },
        ),
      ],
    );
  }
}

class _FamousGiftMinimizeToggle extends StatelessWidget {
  final Widget Function(bool isMinimize) child;
  const _FamousGiftMinimizeToggle({required this.child});

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<bool>(
      valueListenable: RoomData.instance.minimizeGiftFamous,
      builder: (context, isMinimize, _) {
        return Stack(
          children: [
            child(isMinimize),
            Positioned(
              top: 50.h,
              right: 20.h,
              child: InkWell(
                onTap: () async {
                  bool newValue = !RoomData.instance.minimizeGiftFamous.value;
                  await HiveManager().saveData<bool>(
                    KeysManager.ROOMS_BOX,
                    KeysManager.MINIMIZE_GIFT_KEY,
                    newValue,
                  );
                  RoomData.instance.minimizeGiftFamous.value = newValue;
                },
                child: Icon(
                  isMinimize
                      ? CupertinoIcons.fullscreen
                      : CupertinoIcons.fullscreen_exit,
                  color: Colors.white,
                  size: 30.h,
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

class _SuperBoomVideoOverlay extends StatelessWidget {
  const _SuperBoomVideoOverlay();

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<String>(
      valueListenable: SuperBoomController.superBoomVideo,
      builder: (context, videoUrl, _) {
        return ValueListenableBuilder<String>(
          valueListenable: SuperBoomController.superBoomVideoType,
          builder: (context, videoType, _) {
            if (videoUrl.isEmpty) return const SizedBox();

            switch (videoType.toLowerCase()) {
              case 'alpha':
                return RepaintBoundary(child: CacheAlphaWidget(url: videoUrl));
              case 'mp4':
                return RepaintBoundary(
                  child: CacheVideoWidget(
                    videoUrl: videoUrl,
                    isShowGift: true,
                  ),
                );
              case 'vap':
                return RepaintBoundary(
                  child: CachedVapWidget(
                    url: videoUrl,
                    height: ScreenUtil().screenHeight,
                    width: ScreenUtil().screenWidth,
                  ),
                );
              case 'svga':
                return RepaintBoundary(
                  child: CacheSvgaWidget(
                    url: videoUrl,
                    isShowGift: true,
                  ),
                );
              default:
                return CacheAlphaWidget(url: videoUrl);
            }
          },
        );
      },
    );
  }
}

class _LuckyGiftWinOverlay extends StatelessWidget {
  const _LuckyGiftWinOverlay();

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<LuckyGiftWinBloc, LuckyGiftWinState>(
      bloc: di<LuckyGiftWinBloc>(),
      buildWhen: (prev, curr) =>
          prev.runtimeType != curr.runtimeType ||
          (prev is LuckyGiftWinQueueUpdated &&
              curr is LuckyGiftWinQueueUpdated &&
              prev.queue != curr.queue),
      builder: (context, state) {
        if (state is LuckyGiftWinQueueUpdated && state.queue.isNotEmpty) {
          final item = state.queue.first;
          return Positioned(
            top: 300.h,
            left: 110.w,
            right: 110.w,
            child: LuckyGiftWin(winTimes: item['winTimes']),
          );
        }
        return const SizedBox();
      },
    );
  }
}
