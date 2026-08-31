import 'dart:convert';
import 'dart:math';
import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/bottom_dialog.dart';
import 'package:general/src/features/room/presentation/banned_users/view/banned_users_page.dart';
import 'package:general/src/features/room/presentation/charisma/bloc/charisma_bloc.dart';
import 'package:general/src/features/room/presentation/component/buttons/basic_tool/choose_mode_room.dart';
import 'package:general/src/features/room/presentation/component/buttons/basic_tool/room_effect_dailog.dart';
import 'package:general/src/features/room/presentation/component/buttons/emojie/emojie_widget.dart';
import 'package:general/src/features/room/presentation/lucky_box/lucky_box.dart';
import 'package:general/src/features/room/presentation/manager/manager_pk/pk_bloc.dart';
import 'package:general/src/features/room/presentation/manager/manager_pk/pk_events.dart';
import 'package:general/src/features/room/presentation/music/bloc/music_room_bloc.dart';
import 'package:general/src/features/room/presentation/music/view/component/music_widget.dart';
import 'package:general/src/features/room/presentation/music/view/music_server_page.dart';
import 'package:general/src/features/room/presentation/theme/view/theme_dialog.dart';
import 'package:general/src/features/room/presentation/youtube/bloc/youtube/youtube_bloc.dart';
import 'package:general/src/features/room/presentation/youtube/bloc/youtube/youtube_event.dart';
import 'package:general/src/features/room/presentation/youtube/view/privacy_dialog.dart';
import 'package:general/src/features/room/room.dart';
import 'package:permission_handler/permission_handler.dart';

import '../../../manager/check_admin_owner_manager/check_admin_owner_bloc.dart';

part 'basic_tool_item.dart';

class ToolConfig {
  final String title;
  final String image;
  final double? height;
  final IconData? icon;
  final Color? color;
  final VoidCallback? onTap;
  final bool Function(BuildContext, BasicToolDialog) isVisible;
  final Widget? additionalWidget;

  ToolConfig({
    required this.title,
    required this.image,
    this.height,
    this.color,
    this.icon,
    this.onTap,
    required this.isVisible,
    this.additionalWidget,
  });
}

class BasicToolDialog extends StatefulWidget {
  final String ownerId;
  final String userId;
  final bool isAdmin;
  final bool isOnMic;
  final EnterRoomModel roomData;

  const BasicToolDialog({
    super.key,
    required this.roomData,
    required this.ownerId,
    required this.userId,
    required this.isOnMic,
    required this.isAdmin,
  });

  @override
  State<BasicToolDialog> createState() => _BasicToolDialogState();
}

class _BasicToolDialogState extends State<BasicToolDialog> {
  late bool isAudio;

  @override
  void initState() {
    super.initState();
    isAudio = di<RoomStateManager>().isInAudioRoom;

    if (widget.ownerId == widget.userId) {
      di<MangerGetVipPrevBloc>().add(const GetVipPrevEvent());
    }
  }

  List<ToolConfig> _getAudioToolConfigs() => [
        ToolConfig(
          title: LockRoomDialog.roomIsLoked
              ? StringManager.unlockRoom.tr()
              : StringManager.lockRoom.tr(),
          image: AssetsManager.lockRoomIcon,
          isVisible: (context, widget) => widget.ownerId == widget.userId,
          onTap: () {
            Navigator.pop(context);
            if (LockRoomDialog.roomIsLoked) {
              showDialog(
                context: context,
                builder: (context) => AnimatedDialog(
                  titleColor: ColorManager.roomTextPrimary,
                  descriptionColor: ColorManager.roomSecondaryText,
                  confirmTitleColor: ColorManager.roomButtonText,
                  color: ColorManager.roomGold,
                  cancelTextColor: ColorManager.roomTextPrimary,
                  title: StringManager.unLockRoom.tr(),
                  description: StringManager.unLockRoomMsg.tr(),
                  onTap: () {
                    di<OnRoomBloc>().add(RemovePassRoomEvent(
                        roomId: widget.roomData.id.toString()));
                    LockRoomDialog.roomIsLoked = false;
                    RoomData.instance.isRoomLocked.value = false;
                    sendRoomData(data: {
                      "messageContent": {
                        "message": "roomPassword",
                        "value": false
                      }
                    });
                    Navigator.pop(context);
                  },
                ),
              );
            } else {
              bottomDailog(
                context: context,
                color: ColorManager.transparent,
                widget: const LockRoomDialog(),
              );
            }
          },
        ),
        //////////////////////////////////////////////////////////////////
        ToolConfig(
          title: StringManager.emojis.tr(),
          image: "",
          icon: Icons.emoji_emotions,
          color: ColorManager.roomIcon,
          height: 35.h,
          isVisible: (context, widget) =>
              (RoomData.instance.utdController?.seatController
                      .isUserOnSeat(widget.userId.toString()) ??
                  false) &&
              ConstantsManager.isTheme1 &&
              !ConstantsManager.isTheme2,
          onTap: () {
            Navigator.pop(context);
            if (HomePage.isConnectToInternet == true) {
              bottomDailog(
                context: context,
                widget: EmojieWidget(
                  userId: widget.userId,
                  roomId: widget.roomData.id.toString(),
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
        ),
        ToolConfig(
          title: StringManager.theme.tr(),
          image: AssetsManager.themeIcon,
          height: 35.h,
          isVisible: (context, widget) => widget.ownerId == widget.userId,
          onTap: () {
            Navigator.pop(context);
            bottomDailog(
              context: context,
              widget: ThemePage(ownerId: widget.ownerId),
            );
          },
        ),
        ToolConfig(
          title: StringManager.luckyBox.tr(),
          image: AssetsManager.luckyBox,
          height: 35.h,
          isVisible: (context, widget) => true,
          onTap: () {
            Navigator.pop(context);
            di<LuckyBoxBloc>().add(GetLuckyBoxesEvent());
            bottomDailog(
              context: context,
              widget: LuckyBox(
                roomData: widget.roomData,
              ),
            );
          },
        ),
        ToolConfig(
          // Mic mode now also hosts Cinema + PK as selectable "modes".
          title: StringManager.micMode.tr(),
          image: AssetsManager.micMode,
          isVisible: (context, widget) => widget.ownerId == widget.userId,
          onTap: () {
            Navigator.pop(context);
            final ctx = navKey.currentContext;
            if (ctx == null) return;
            final roomId = widget.roomData.id.toString();
            bottomDailog(
              context: ctx,
              widget: ChooseModeRoom(
                roomId: roomId,
                modeRoom: widget.roomData.mode.toString(),
                onCinemaTap: ConstantsManager.isShowCinemaMode
                    ? () => _toggleCinema(roomId)
                    : null,
                onPkTap:
                    !ConstantsManager.isTheme1 ? () => _startPk(roomId) : null,
              ),
            );
          },
        ),
        ToolConfig(
          title: StringManager.effect.tr(),
          image: AssetsManager.effect,
          isVisible: (context, widget) => true,
          onTap: () {
            Navigator.pop(context);
            bottomDailog(
              context: context,
              widget: const RoomEffectDailog(),
            );
          },
        ),
        ToolConfig(
          title: StringManager.music.tr(),
          image: AssetsManager.musicIcon,
          isVisible: (context, widget) =>
              widget.ownerId == widget.userId || widget.isAdmin,
          onTap: () {
            di<CheckAdminOwnerBloc>().add(CheckAdminOwnerEvent(
              context: context,
              params: CheckAdminOwnerParam(
                roomId: widget.roomData.id.toString(),
                type: 'music',
              ),
              callback: () async {
                await Methods.requestPermission(Permission.audio);
                // `context` here is the State.context getter: after the async
                // gap the dialog is often already popped/disposed, and the
                // getter itself throws (`_element!`) BEFORE `.mounted` can be
                // read — the live "Null check operator" fatal. State.mounted
                // is the unmount-safe check.
                if (!mounted) return;

                if (isAudio) {
                  // Audio room: shared, synced music. Open the server-backed
                  // upload/list page; playback is broadcast to all users and
                  // controlled only by whoever starts it.
                  context.popRoute();
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const MusicServerPage(),
                    ),
                  );
                  return;
                }

                // Live (video) room: existing local-music behavior.
                di<MusicRoomBloc>().add(GetMusicRoomListEvent(callback: () {
                  // Same unmount-safe guard: State.context throws when the
                  // dialog State is already disposed by the time this lands.
                  if (!mounted) return;
                  context.popRoute();
                  if (di<MusicRoomBloc>().state.musicesInRoom.isEmpty) {
                    Navigator.pushNamed(
                      context,
                      Routes.musicPage,
                      arguments: widget.ownerId,
                    );
                  } else {
                    bottomDailog(
                      context: context,
                      widget: const MusicDialog(),
                    );
                  }
                }));
              },
            ));
          },
        ),
        ToolConfig(
          title: StringManager.cleanComments.tr(),
          image: AssetsManager.clearChat,
          height: 35.h,
          isVisible: (context, widget) =>
              widget.ownerId == widget.userId || widget.isAdmin,
          onTap: () {
            Navigator.pop(context);
            di<CheckAdminOwnerBloc>().add(CheckAdminOwnerEvent(
              context: context,
              params: CheckAdminOwnerParam(
                roomId: widget.roomData.id.toString(),
                type: 'cleat_chat',
              ),
              callback: () {
                final ctx = navKey.currentState?.context;
                if (ctx == null) return;
                showDialog(
                  context: ctx,
                  builder: (context) => AnimatedDialog(
                    titleColor: ColorManager.roomTextPrimary,
                    descriptionColor: ColorManager.roomSecondaryText,
                    confirmTitleColor: ColorManager.roomButtonText,
                    color: ColorManager.roomGold,
                    cancelTextColor: ColorManager.roomTextPrimary,
                    title: StringManager.cleanComments.tr(),
                    description: StringManager.areYouSureDeleteChat.tr(),
                    conText: StringManager.confirm.tr(),
                    onTap: () {
                      RoomData.instance.chatController?.clearMessages();
                      final mapInformation = {
                        "messageContent": {"message": "removeChat"}
                      };
                      final map = jsonEncode(mapInformation);
                      sendRoomData(data: jsonDecode(map));
                      Navigator.pop(context);
                    },
                  ),
                );
              },
            ));
          },
        ),
        ToolConfig(
          title: StringManager.adminsTool.tr(),
          image: AssetsManager.admins,
          isVisible: (context, widget) =>
              widget.ownerId == widget.userId || widget.isAdmin,
          onTap: () {
            Navigator.pop(context);
            bottomDailog(
              context: context,
              widget: MediaQuery(
                data: MediaQueryData.fromView(View.of(context)),
                child: AdminsRoomPage(
                  isRoomManager: false,
                  ownerId: widget.ownerId,
                ),
              ),
            );
          },
        ),
        ToolConfig(
          title: StringManager.hideRoom.tr(),
          image: AssetsManager.hideRoom,
          isVisible: (context, widget) => widget.ownerId == widget.userId,
          onTap: () {
            final state = di<MangerGetVipPrevBloc>().state;
            if (state.requestState == RequestState.loaded) {
              final reslt =
                  state.data.firstWhere((element) => element.key == "room");
              if (reslt.isAllowToUser != true) {
                showDialog(
                  context: context,
                  builder: (context) => AnimatedDialog(
                    titleColor: ColorManager.roomTextPrimary,
                    descriptionColor: ColorManager.roomSecondaryText,
                    confirmTitleColor: ColorManager.roomButtonText,
                    color: ColorManager.roomGold,
                    cancelTextColor: ColorManager.roomTextPrimary,
                    onTap: () {
                      context.popRoute();
                      context.pushNamedRoute(Routes.vipScreen);
                    },
                    description: StringManager.thisFeatureisNotAvailableForYou(
                      vip: reslt.mine.toString(),
                    ).tr(),
                    title: reslt.titleAr ?? '',
                  ),
                );
              } else {
                di<PrivacyBloc>().add(
                  reslt.isActive == false
                      ? ActivePrivacy(type: "room")
                      : DisposePrivacy(type: "room"),
                );
                Navigator.pop(context);
              }
            }
          },
        ),
        ToolConfig(
          title: StringManager.bannedUsers.tr(),
          image: AssetsManager.userBlock,
          isVisible: (context, widget) =>
              widget.ownerId == widget.userId || widget.isAdmin,
          onTap: () {
            context.popRoute();
            bottomDailog(
              context: context,
              widget: MediaQuery(
                data: MediaQueryData.fromView(View.of(context)),
                child: const BannedUsersPage(),
              ),
            );
          },
        ),
        // Charisma (moved here from the room-info page). Owner-only toggle, with
        // the same PK/cinema guards as before.
        ToolConfig(
          title: StringManager.charisma.tr(),
          image: '',
          icon: Icons.auto_awesome,
          color: ColorManager.roomIcon,
          isVisible: (context, widget) => widget.ownerId == widget.userId,
          onTap: () {
            context.popRoute();
            if (PkController.isPK.value || PkController.showPK.value) {
              Methods.showToast(context,
                  message: StringManager.closePkFirst.tr(), isError: true);
            } else if (RoomData.instance.room.mode == '5' ||
                RoomData.instance.runningCinemaMode == true) {
              Methods.showToast(context,
                  message: StringManager.closeCinemaMode.tr(), isError: true);
            } else {
              di<CharismaBloc>().add(
                StartCharismaEvent(roomId: widget.roomData.id.toString()),
              );
              RoomData.instance.runningCinemaMode = false;
            }
          },
        ),
        ToolConfig(
          title: StringManager.resetCharisma.tr(),
          image: '',
          icon: Icons.refresh,
          color: ColorManager.roomIcon,
          isVisible: (context, widget) => widget.ownerId == widget.userId,
          onTap: () {
            context.popRoute();
            di<CharismaBloc>().add(
              ResetCharismaEvent(
                roomId: widget.roomData.id.toString(),
                ownerId: widget.ownerId,
                context: context,
              ),
            );
          },
        ),
        ToolConfig(
          title: RoomData.instance.isCommentsClosed.value
              ? StringManager.unlockComments.tr()
              : StringManager.lockComments.tr(),
          image: AssetsManager.closeComments,
          height: 25.h,
          color: ColorManager.roomIcon,
          isVisible: (context, widget) =>
              widget.ownerId == widget.userId || widget.isAdmin,
          onTap: () {
            if (RoomData.instance.isCommentsClosed.value) {
              RoomData.instance.isCommentsClosed.value = false;
              di<OnRoomBloc>().add(
                UnLockCommentsEvent(
                  roomId: widget.roomData.id.toString(),
                ),
              );
            } else {
              RoomData.instance.isCommentsClosed.value = true;
              di<OnRoomBloc>().add(
                LockCommentsEvent(
                  roomId: widget.roomData.id.toString(),
                ),
              );
            }
          },
        ),
      ];

  // Cinema toggle, invoked from the mic-mode selector (the tools sheet is
  // already closed, so use the global navigator context — State.context is dead).
  void _toggleCinema(String roomId) {
    if (RoomData.instance.room.mode == '5') {
      RoomData.instance.runningCinemaMode = false;
      final controller = RoomData.instance.utdController;
      if (controller != null) {
        final mode = controller.resolveMode('3');
        controller.seatController.setupSeats(
          identity: MyDataModel.getInstance().id.toString(),
          seatCount: mode.seatCount,
          seatMode: controller.seatController.seatMode.value,
          modeId: '3',
        );
      }
      di<YoutubeBloc>().add(const InitialViewYoutubeVideoEvent());
      di<YoutubeBloc>().add(const DisposeViewYoutubeVideoEvent());
    } else {
      final ctx = navKey.currentContext;
      if (ctx == null) return;
      if (PkController.showPK.value || PkController.isPK.value) {
        Methods.showToast(ctx,
            message: StringManager.closePkFirst.tr(), isError: true);
      } else if (RoomData.instance.isCharismaVisible.value) {
        Methods.showToast(ctx, message: StringManager.closeCharisma.tr());
      } else {
        showDialog(
          context: ctx,
          builder: (_) => PrivacyDialog(roomId: roomId),
        );
      }
    }
  }

  // PK start, invoked from the mic-mode selector.
  void _startPk(String roomId) {
    final ctx = navKey.currentContext;
    if (ctx == null) return;
    if (RoomData.instance.room.mode != '3') {
      Methods.showToast(ctx, message: StringManager.cantOpenPk.tr());
    } else if (RoomData.instance.isCharismaVisible.value) {
      Methods.showToast(ctx,
          message: StringManager.closeCharisma.tr(), isError: true);
    } else if (PKWidget.isStartPK.value) {
      Methods.showToast(ctx, message: StringManager.cantClosePk.tr());
    } else {
      activePK();
      di<PKBloc>().add(ShowPKEvent(roomId: roomId));
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasSpeaker = di<RoomStateManager>().isInAudioRoom &&
        !ConstantsManager.isTheme1;

    final tools = _getAudioToolConfigs()
        .where((tool) => tool.isVisible(context, widget))
        .toList();
    Widget gamesWidget = BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
      bloc: di<FetchUserDataBloc>(),
      buildWhen: (prev, curr) =>
          prev.userEntity?.isGameAvailable != curr.userEntity?.isGameAvailable,
      builder: (context, state) {
        if (state.userEntity?.isGameAvailable == true) {
          return GestureDetector(
            onTap: () {
              if (!GamesAccess.canPlay) {
                Methods.showToast(
                  context,
                  message: StringManager.gamesNotAvailableForYou.tr(),
                  isError: true,
                );
                return;
              }
              if (HomePage.isConnectToInternet == true) {
                Navigator.pop(context);
                final ctx = navKey.currentState?.context;
                if (ctx == null) return;
                bottomDailog(
                  context: ctx,
                  widget: GamesView(
                    roomId: widget.roomData.id.toString(),
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
            child: BasicToolItem(
              title: StringManager.games.tr(),
              image: AssetsManager.gameIconHome,
              color: ColorManager.roomIcon,
            ),
          );
        } else {
          return const SizedBox.shrink();
        }
      },
    );
    return Container(
      // Size to content (scrolls if taller) instead of a fixed height that
      // clipped rows — so every tool is reachable.
      constraints: BoxConstraints(
        maxHeight: MediaQuery.sizeOf(context).height * 0.62,
      ),
      padding: context.paddingSymmetric(horizontal: 10),
      decoration: BoxDecoration(
        // Themed surface (was a flat translucent black) so the tools sheet
        // matches the admin-panel theme.
        color: ColorManager.roomCard,
        borderRadius: BorderRadius.only(
          topRight: Radius.circular(15.r),
          topLeft: Radius.circular(15.r),
        ),
      ),
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Title
            3.hBox,
            Padding(
              padding: context.paddingOnly(top: 8, start: 10),
              child: Text(
                StringManager.basicTools.tr(),
                style: context.bodyMedium.bold
                    .colorExt(ColorManager.roomTextPrimary)
                    .size(18),
              ),
            ),
            5.hBox,
            // Dynamic grid of tools
            BlocBuilder<MangerGetVipPrevBloc, MangerGetVipPrevState>(
              bloc: di<MangerGetVipPrevBloc>(),
              buildWhen: (prev, curr) =>
                  prev.requestState != curr.requestState ||
                  prev.data != curr.data,
              builder: (context, state) {
                return GridView.builder(
                  shrinkWrap: true,
                  padding: context.paddingZero(),
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 4,
                    crossAxisSpacing: 5,
                    mainAxisSpacing: 5,
                    childAspectRatio: 1,
                  ),
                  itemCount: tools.length + 1,
                  // +1 for SpeakerButton
                  itemBuilder: (context, index) {
                    if (index == 0 &&
                        hasSpeaker &&
                        !ConstantsManager.isTheme1) {
                      return const SpeakerButton();
                    } else if (index == tools.length + (hasSpeaker ? 1 : 0)) {
                      return gamesWidget;
                    }
                    final tool = tools[index - (hasSpeaker ? 1 : 0)];

                    return ValueListenableBuilder(
                      valueListenable: tool.title.contains(StringManager.pk)
                          ? PKWidget.isStartPK
                          : tool.title.contains(StringManager.comments)
                              ? RoomData.instance.isCommentsClosed
                              : ValueNotifier<bool>(false),
                      builder: (context, value, _) => InkWell(
                        onTap: tool.onTap,
                        child: Stack(
                          alignment: AlignmentDirectional.bottomEnd,
                          children: [
                            BasicToolItem(
                              title: tool.title
                                      .contains(StringManager.hideRoom.tr())
                                  ? (state.requestState ==
                                              RequestState.loaded &&
                                          state.data.any((e) => e.key == "room")
                                      ? (state.data
                                                  .firstWhere(
                                                      (e) => e.key == "room")
                                                  .isActive ??
                                              false)
                                          ? StringManager.showRoom.tr()
                                          : StringManager.hideRoom.tr()
                                      : StringManager.hideRoom.tr())
                                  : tool.title,
                              image: tool.image,
                              icon: tool.icon,
                              height: tool.height,
                              color: tool.color,
                            ),
                            if (tool.additionalWidget != null)
                              tool.additionalWidget!,
                          ],
                        ),
                      ),
                    );
                  },
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}
