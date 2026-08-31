import 'dart:convert';
import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/bottom_dialog.dart';
import 'package:general/src/core/widgets/cache/cache_alpha_widget.dart';
import 'package:general/src/core/widgets/cache/cache_vap_widget.dart';
import 'package:general/src/core/widgets/cache/cache_video_widget.dart';
import 'package:general/src/core/widgets/gender_widget.dart';
import 'package:general/src/core/widgets/id_with_copy.dart';
import 'package:general/src/core/widgets/vip_container.dart';
import 'package:general/src/features/auth/data/model/vip_frames_model.dart';
import 'package:general/src/features/auth/domain/entities/user_entity.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_user_badges/get_user_badges_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_user_badges/get_user_badges_event.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_user_badges/get_user_badges_state.dart';
import 'package:general/src/features/room/data/model/user_in_room_model.dart';
import 'package:general/src/features/profile/presentation/f_f_f_v/bloc/bloc_make_follow_unfollow/follow_bloc.dart';
import 'package:general/src/features/profile/domain/profile_use_case/follow_unfollow_use_case.dart';
import 'package:general/src/features/room/presentation/component/profile/widgets/follow_button.dart';
import 'package:general/src/features/room/presentation/gifts/gift.dart';
import 'package:general/src/features/room/presentation/component/messages/messages_button/input_board.dart';

import '../../../room.dart';
import 'bloc/extra_data_profile_bloc.dart';
import 'component/profile_permission_menu.dart';
import 'component/second_card_body.dart';

class ProfileBody extends StatefulWidget {
  final EnterRoomModel roomData;
  final UserInRoomModel userData;
  final bool isMyProfile;
  final String userId;

  const ProfileBody({
    super.key,
    required this.roomData,
    required this.userData,
    required this.isMyProfile,
    required this.userId,
  });

  @override
  State<ProfileBody> createState() => _ProfileBodyState();
}

class _ProfileBodyState extends State<ProfileBody> {
  String vipImage = '';
  String vipType = '';
  String vipId = '';
  bool? halfImageProfile;
  ValueNotifier<Color?> backgroundColor =
      ValueNotifier<Color?>(ColorManager.black);
  late final GetUserBadgesBloc getUserBadgesBloc;
  late final FollowBloc _followBloc;
  late final FetchExtraDataBloc _fetchExtraDataBloc;

  @override
  void initState() {
    super.initState();
    _resolveVipFrame();
    _fetchExtraDataBloc = di<FetchExtraDataBloc>();
    _fetchExtraDataBloc.add(FetchExtraDataProfile(userId: widget.userId));
    getUserBadgesBloc = di<GetUserBadgesBloc>();
    getUserBadgesBloc.add(GetUserBadgesData(id: int.tryParse(widget.userId) ?? 0));
    _followBloc = FollowBloc(
      makeFollowUseCase: di<MakeFollowUseCase>(),
      makeUnFollowUseCase: di<MakeUnFollowUseCase>(),
    );
  }

  @override
  void dispose() {
    _followBloc.close();
    super.dispose();
  }

  void _resolveVipFrame() {
    final jsonData = HiveManager().getData(
      KeysManager.FRAMES_BOX,
      KeysManager.VIP_FRAMES_KEY,
    );
    if (jsonData == null) return;
    final List decoded;
    try {
      final result = jsonDecode(jsonData);
      if (result is! List) return;
      decoded = result;
    } catch (_) {
      return;
    }
    for (var item in decoded) {
      final vipFrame = VipFramesModel.fromJson(item);
      if (vipFrame.id.toString() ==
          (widget.userData.profileFrameId ?? '0').toString()) {
        vipImage = vipFrame.image ?? '';
        halfImageProfile = vipFrame.isHalfFrame ?? false;
        vipType = vipFrame.imageType ?? '';
        vipId = '${vipFrame.id ?? '0'}';
        break;
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasProfileFrame = (widget.userData.profileFrameId != null &&
        widget.userData.profileFrameId != "" &&
        widget.userData.profileFrameId != "0");
    final screenWidth = ScreenUtil().screenWidth;
    bool isOnSeat = RoomData.instance.utdController?.seatController
            .isUserOnSeat(widget.userData.id.toString()) ??
        false;
    backgroundColor.value = vipImage.isEmpty
        ? ColorManager.whiteGrey
        : halfImageProfile == true
            ? ColorManager.whiteGrey
            : hasProfileFrame
                ? ColorManager.transparent
                : ColorManager.whiteGrey;

    Widget buildProfileFrame({required bool ignorePointer}) {
      // No VIP frame for this user: render nothing. Rendering an empty-url image
      // here would fire detectError and flip backgroundColor to black, turning
      // the profile sheet's button black-on-black. Keep the light default.
      if (vipImage.isEmpty) return const SizedBox.shrink();
      final Widget frameWidget = Methods().isSvgaFile(vipImage)
          ? CacheSvgaWidget(
              url: vipImage,
              boxFit: BoxFit.fill,
              width: screenWidth,
              detectError: () {
                backgroundColor.value = ColorManager.black;
              },
            )
          : vipType == 'mp4'
              ? CacheVideoWidget(
                  videoUrl: vipImage,
                  width: screenWidth,
                  isReels: true,
                  detectError: () {
                    backgroundColor.value = ColorManager.black;
                  },
                )
              : vipType == 'vap'
                  ? CachedVapWidget(
                      isLoop: true,
                      url: vipImage,
                      width: screenWidth,
                      detectError: () {
                        backgroundColor.value = ColorManager.black;
                      },
                    )
                  : vipType == 'alpha'
                      ? CacheAlphaWidget(
                          url: vipImage,
                          width: screenWidth,
                          isLoop: true,
                          detectError: () {
                            backgroundColor.value = ColorManager.black;
                          },
                        )
                      : ImageViewWidget(
                          url: vipImage,
                          boxFit: BoxFit.cover,
                          width: screenWidth,
                          heightLoading: 100.h,
                          widthLoading: screenWidth,
                          isRoomProfile: true,
                          isStopForProfileRoom: false,
                          isStopLoadingAndError: false,
                          detectError: () {
                            Methods.printLog('ProfileFrame detectError ');
                            backgroundColor.value = ColorManager.black;
                          },
                        );

      return Positioned(
        top: ignorePointer ? -150.h : -85.h,
        left: 0,
        right: 0,
        bottom: 0,
        child: ignorePointer ? IgnorePointer(child: frameWidget) : frameWidget,
      );
    }

    return Stack(
      clipBehavior: Clip.none,
      children: [
        if (hasProfileFrame && halfImageProfile == false)
          buildProfileFrame(ignorePointer: true),
        ValueListenableBuilder(
            valueListenable: backgroundColor,
            builder: (context, value, child) {
              return Container(
                decoration: BoxDecoration(
                  color: value,
                  borderRadius:
                      BorderRadius.vertical(top: Radius.circular(10.r)),
                ),
                child: Padding(
                  padding: context.paddingOnly(
                    start: 0,
                    top: backgroundColor.value != ColorManager.black
                        ? halfImageProfile == true
                            ? 35
                            : (hasProfileFrame && halfImageProfile == false)
                                ? 120
                                : 35
                        : 35,
                    end: 0,
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      InkWell(
                        onTap: () {
                          final int? targetId = widget.userData.id;
                          if (targetId == null || targetId == 0) return;
                          context.popRoute();
                          Methods().userProfileNavigator(
                            context: context,
                            user_: const UserEntity(),
                            comesFromRoom: true,
                            userId: targetId.toString(),
                          );
                        },
                        child: UserImage(
                          image: widget.userData.image ?? '',
                          displayName: widget.userData.name ?? '',
                          imageSize: 80,
                          frame: widget.userData.frame,
                          frameType: widget.userData.frameType,
                          frameSize: 125,
                        ),
                      ),
                      10.hBox,
                      GradientTextVip(
                        width: 150.w,
                        isVip: widget.userData.vipColorName != null &&
                            (widget.userData.vipColorName ?? '')
                                .startsWith('#'),
                        text: widget.userData.name ?? '',
                        color: Methods.safeHexColor(
                                widget.userData.vipColorName) ??
                            ColorManager.blackColor,
                        mainAxisAlignment: MainAxisAlignment.start,
                        textAlign: TextAlign.center,
                        textStyle: context.bodyMedium.w500.size(18).colorExt(
                            Methods.safeHexColor(
                                    widget.userData.vipColorName) ??
                                ColorManager.blackColor),
                      ),
                      5.hBox,
                      Row(
                        children: [
                          const Spacer(),
                          if (widget.userData.isCountryHidden != true)
                            CountryFlagWidget(
                              iso: widget.userData.countryIso,
                              fallbackUrl: widget.userData.countryImage,
                              height: 20.h,
                              width: 25.w,
                              boxFit: BoxFit.cover,
                            ),
                          5.wBox,
                          GenderWidget(
                            age: widget.userData.age ?? 0,
                            gender: widget.userData.gender ?? 0,
                          ),
                          5.wBox,
                          IdWithCopyIcon(
                            userId: widget.userData.uuid ?? '',
                            isNeedCopyIcon: true,
                            isSpecial: (widget.userData.specialId != null &&
                                (widget.userData.specialId ?? '') != ''),
                            specialImg: widget.userData.idImage ?? '',
                            color: widget.userData.imageColorEntity?.color,
                            img: widget.userData.imageColorEntity?.image,
                            mainAxisAlignment: MainAxisAlignment.start,
                            idColor: ColorManager.greyTextColor,
                            idStyle: context.bodyMedium.bold
                                .colorExt(
                                    widget.userData.imageColorEntity?.color !=
                                                null &&
                                            widget.userData.imageColorEntity
                                                    ?.color?.isNotEmpty ==
                                                true
                                        ? Color(int.tryParse(widget
                                            .userData.imageColorEntity!.color!
                                            .replaceAll('#', '0xff')) ?? 0xFF000000)
                                        : ColorManager.black)
                                .copyWith(height: 0.1, fontSize: 11.sp),
                          ),
                          const Spacer(),
                        ],
                      ),
                      3.hBox,
                      BlocBuilder<GetUserBadgesBloc, GetUserBadgesState>(
                        bloc: getUserBadgesBloc,
                        buildWhen: (prev, curr) =>
                            prev.userBadge != curr.userBadge,
                        builder: (context, state) {
                          final userBadgesData = [
                            ...(state.userBadge?.top ?? []),
                            ...(state.userBadge?.regular ?? []),
                          ];
                          return SizedBox(
                            width: MediaQuery.sizeOf(context).width * 0.8,
                            child: Wrap(
                              spacing: 5.w,
                              runSpacing: 3.h,
                              alignment: WrapAlignment.center,
                              crossAxisAlignment: WrapCrossAlignment.center,
                              children: [
                                // VIP Container
                                if (widget.userData.vipImage != "")
                                  VipContainer(
                                    vip: widget.userData.vipImage ?? "",
                                    height: 20.h,
                                    width: 38.w,
                                  ),

                                // Sender Level Container
                                if (widget.userData.senderLevelImage != "")
                                  LevelContainer(
                                    image:
                                        widget.userData.senderLevelImage ?? "",
                                  ),

                                // Receiver Level Container
                                if (widget.userData.receiverLevelImage != "")
                                  LevelContainer(
                                    image: widget.userData.receiverLevelImage ??
                                        "",
                                  ),

                                // User badges from BlocBuilder
                                if (userBadgesData.isNotEmpty)
                                  ...userBadgesData.map(
                                    (badgeData) {
                                      return badgeData.imageType == "svga"
                                          ? CacheSvgaWidget(
                                              url: EndPoints.getImage(
                                                  badgeData.image),
                                              boxFit: BoxFit.fill,
                                              height: 22.5.h,
                                              width: 70.h,
                                            )
                                          : ImageViewWidget(
                                              url: EndPoints.getImage(
                                                  badgeData.image),
                                              height: 22.5.h,
                                              width: 70.w,
                                              boxFit: BoxFit.fill,
                                            );
                                    },
                                  ),
                              ],
                            ),
                          );
                        },
                      ),
                      5.hBox,
                      Padding(
                        padding: context.paddingSymmetric(horizontal: 20),
                        child: Column(
                          children: [
                            SecondCardRoomProfileBody(
                              userData: widget.userData,
                            ),
                            10.hBox,
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                if (widget.userData.id !=
                                    MyDataModel.getInstance().id)
                                  BlocBuilder<FetchExtraDataBloc,
                                      FetchExtraDataStates>(
                                    bloc: _fetchExtraDataBloc,
                                    builder: (context, state) {
                                      if (state.extraProfileData != null) {
                                        return FollowButton(
                                          userData: widget.userData,
                                          initialIsFollow: state
                                                  .extraProfileData?.isFollow ??
                                              false,
                                          followBloc: _followBloc,
                                        );
                                      } else if (state.requestState.isError ||
                                          state.requestState ==
                                              RequestState.offline) {
                                        return const SizedBox();
                                      } else {
                                        return SizedBox(
                                          width: 100.w,
                                          height: 35.h,
                                          child: const Center(
                                            child: SizedBox(
                                              width: 20,
                                              height: 20,
                                              child: CircularProgressIndicator(
                                                strokeWidth: 2,
                                                color: ColorManager.black,
                                              ),
                                            ),
                                          ),
                                        );
                                      }
                                    },
                                  ),
                                if (widget.userData.id !=
                                    MyDataModel.getInstance().id)
                                  10.wBox,
                                // Mention "@" — quick, always-visible action next
                                // to send-gift (for other users in an audio room),
                                // instead of being buried in the 3-dots menu.
                                if (widget.userData.id !=
                                        MyDataModel.getInstance().id &&
                                    di<RoomStateManager>().isInAudioRoom) ...[
                                  ButtonWidget(
                                    onPressed: () {
                                      final name = widget.userData.name
                                              ?.replaceAll(" ", "_") ??
                                          "";
                                      final nav = Navigator.of(context);
                                      nav.pop();
                                      nav.push(
                                        AudioRoomInRoomMessageInputBoard(
                                          mention: "@$name",
                                        ),
                                      );
                                    },
                                    width: 44.w,
                                    backgroundColor:
                                        ColorManager.roomCard,
                                    paddingButton: context.paddingAll(8),
                                    height: 35.h,
                                    title: TextWidget(
                                      "@",
                                      style: context.bodyLarge.bold
                                          .colorExt(ColorManager.roomTextPrimary),
                                    ),
                                  ),
                                  8.wBox,
                                ],
                                ButtonWidget(
                                  onPressed: () {
                                    Navigator.pop(context);
                                    bottomDailog(
                                      context: context,
                                      barrierColor: ColorManager.transparent,
                                      widget: GiftScreen(
                                        roomData: widget.roomData,
                                        userId: widget.userData.id.toString(),
                                        myDataModel: MyDataModel.getInstance(),
                                        userImage: widget.userData.image ?? '',
                                        userName: widget.userData.name ?? '',
                                        users: null,
                                        isSingleUser: true,
                                        isAudioRoom: true,
                                      ),
                                    );
                                  },
                                  width: 100.w,
                                  // Card color from the admin-panel theme (was a
                                  // hardcoded purple).
                                  backgroundColor: ColorManager.roomCard,
                                  paddingButton: context.paddingAll(8),
                                  height: 35.h,
                                  title: Row(
                                    children: [
                                      Image.asset(
                                        AssetsManager.sendGiftRoom,
                                        height: 25.h,
                                      ),
                                      10.wBox,
                                      TextWidget(
                                        StringManager.sendGift.tr(),
                                        style: context.bodyMedium.bold
                                            .colorExt(ColorManager.roomTextPrimary),
                                      ),
                                    ],
                                  ),
                                ),
                                (((widget.roomData.ownerId ==
                                                    MyDataModel.getInstance()
                                                        .id &&
                                                widget.userData.id !=
                                                    MyDataModel.getInstance()
                                                        .id) ||
                                            (widget.userData.id ==
                                                MyDataModel.getInstance().id) ||
                                            ((RoomData.instance.adminsInRoom
                                                        .containsKey(
                                                      MyDataModel.getInstance()
                                                          .id
                                                          .toString(),
                                                    ) &&
                                                    (RoomData.instance
                                                            .adminsInRoom
                                                            .containsKey(
                                                          widget.userData.id
                                                              .toString(),
                                                        )) ==
                                                        false) &&
                                                widget.userData.id !=
                                                    widget.roomData.ownerId)) &&
                                        isOnSeat)
                                    ? Row(
                                        children: [
                                          10.wBox,
                                          ButtonWidget(
                                            onPressed: () async {
                                              if (widget.userData.id ==
                                                  MyDataModel.getInstance()
                                                      .id) {
                                                final controller = RoomData.instance.utdController;
                                                if (controller != null) {
                                                  // Best-effort leave; leaveSeat
                                                  // returns false on rejection
                                                  // (no longer throws).
                                                  await controller.seatController.leaveSeat(
                                                    MyDataModel.getInstance().id.toString(),
                                                  );
                                                }
                                                Navigator.pop(context);
                                              } else {
                                                try {
                                                  final controller = RoomData.instance.utdController;
                                                  if (controller != null) {
                                                    final userId = widget.userData.id.toString();
                                                    final seatIndex = controller.seatController.getSeatIndexByUserId(userId);
                                                    if (seatIndex >= 0) {
                                                      await controller.seatController.kickFromSeat(seatIndex, identity: MyDataModel.getInstance().id.toString());
                                                    }
                                                  }
                                                  kickUserfromMic(
                                                    userId: widget.userData.id.toString(),
                                                  );
                                                  if (context.mounted) context.popRoute();
                                                } catch (e) {
                                                  if (context.mounted) {
                                                    Methods.showToast(context, isError: true, message: e.toString());
                                                  }
                                                }
                                              }
                                            },
                                            width: 100.w,
                                            height: 35.h,
                                            borderColor: ColorManager.grey
                                                .withValues(alpha: 0.4),
                                            paddingButton:
                                                context.paddingAll(9),
                                            backgroundColor:
                                                ColorManager.black,
                                            title: Row(
                                              children: [
                                                Image.asset(
                                                  AssetsManager.downArrow,
                                                  height: 25.h,
                                                ),
                                                10.wBox,
                                                TextWidget(
                                                  StringManager.leaveSeat
                                                      .tr(),
                                                  style: context
                                                      .bodyMedium.bold
                                                      .colorExt(ColorManager
                                                          .black),
                                                ),
                                              ],
                                            ),
                                          )
                                        ],
                                      )
                                    : const SizedBox(),
                              ],
                            ),
                            10.hBox,
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }),
        if (MyDataModel.getInstance().id != widget.userData.id)
          Positioned(
            right: 15,
            top: (hasProfileFrame && halfImageProfile == false) ? 105.h : 15.h,
            child: ProfilePermissionMenu(
              roomData: widget.roomData,
              halfImageProfile: halfImageProfile,
              userData: widget.userData,
            ),
          ),
        if (hasProfileFrame && halfImageProfile == true)
          buildProfileFrame(ignorePointer: false),
      ],
    );
  }
}
