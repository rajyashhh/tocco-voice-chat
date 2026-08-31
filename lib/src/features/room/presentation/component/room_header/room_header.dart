import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/on_multiable_tab.dart';
import 'package:general/src/features/room/presentation/component/room_header/exit_room/exit_side_panel_overlay.dart';
import 'package:general/src/features/room/presentation/share/room_share_dialog.dart';
import 'package:general/src/features/room/room.dart';
import 'package:general/src/features/vip/vip.dart';
import 'package:general/src/features/auth/domain/entities/my_data_entity.dart';

class RoomHeader extends StatelessWidget {
  final String? specialIdImage;
  final ImageColorEntity? imageColorEntity;

  const RoomHeader({
    super.key,
    this.specialIdImage,
    this.imageColorEntity,
  });

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder(
        valueListenable: OwnerOfRoom.isEditRoom,
        builder: (context, value, child) {
          return Padding(
            padding: context.paddingOnly(
              start: 8.0,
              end: 8.0,
              bottom: 8.0,
              top: 35.h,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    InkWell(
                      onTap: () {
                        if (HomePage.isConnectToInternet == true) {
                          context.pushNamedRoute(
                            Routes.settingScreenRoom,
                            arguments: RoomData.instance.room,
                          );
                        } else {
                          Methods.showToast(
                            context,
                            message: StringManager.pleaseCheckInternet.tr(),
                            isError: true,
                          );
                        }
                      },
                      child: OwnerOfRoom(
                        roomName:
                            RoomData.instance.roomDataUpdates['room_name'] == ''
                                ? RoomData.instance.room.roomName ?? ''
                                : RoomData.instance
                                        .roomDataUpdates['room_name'] ??
                                    '',
                        roomData: RoomData.instance.room,
                        introRoom:
                            RoomData.instance.roomDataUpdates['room_intro'] ??
                                '',
                        roomImg:
                            RoomData.instance.roomDataUpdates['room_img'] == ''
                                ? RoomData.instance.room.roomCover ?? ""
                                : RoomData
                                        .instance.roomDataUpdates['room_img'] ??
                                    "",
                        imageColorEntity: imageColorEntity,
                        specialIdImage: specialIdImage,
                      ),
                    ),
                    Padding(
                      padding: context.paddingOnly(top: 10),
                      child: Row(
                        children: [
                          MultiTapCard(
                            onTap: () async {
                              if (ConstantsManager.isShareWithFriends == true) {
                                showModalBottomSheet(
                                  context: context,
                                  isScrollControlled: true,
                                  builder: (_) => RoomShareDialog(
                                      roomEntity: RoomData.instance.room),
                                );
                              } else {
                                await shareRoomLink(
                                    context, RoomData.instance.room);
                              }
                            },
                            child: Image.asset(
                              AssetsManager.shareRoom,
                              width: 20.w,
                              color: ColorManager.onDark,
                            ),
                          ),
                          10.wBox,
                          InkWell(
                            child: Image.asset(
                              AssetsManager.exitRoomIcon,
                              width: 26.w,
                              height: 26.w,
                              color: ColorManager.onDark,
                            ),
                            onTap: () {
                              ExitSidePanelOverlay.show(context);
                            },
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                10.hBox,
                Row(
                  mainAxisAlignment: MainAxisAlignment.start,
                  children: [
                    RankWidget(roomEntity: RoomData.instance.room),
                    const Spacer(),
                    StreamBuilder<List<UTDParticipant>>(
                      stream: RoomService.instance.getUserListStream(),
                      builder: (context, snapshot) {
                        final visitors =
                            snapshot.data ?? RoomService.instance.getAllUsers();
                        return NumberOfVisitor(
                          ownerId: '${RoomData.instance.room.ownerId}',
                          vistors: visitors,
                          myDataModel: MyDataModel.getInstance(),
                          roomData: RoomData.instance.room,
                        );
                      },
                    ),
                  ],
                ),
              ],
            ),
          );
        });
  }
}

bool equalsUserLists(
  List<UTDParticipant> prev,
  List<UTDParticipant> next,
) {
  if (prev.length != next.length) return false;
  for (int i = 0; i < prev.length; i++) {
    if (prev[i].id != next[i].id) return false;
  }
  return true;
}
