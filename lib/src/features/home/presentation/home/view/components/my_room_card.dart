import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/on_multiable_tab.dart';
import 'package:general/src/features/home/data/model/my_rooms_model.dart';
import 'package:general/src/features/home/presentation/home/bloc/fetch_my_room_data_manager/fetch_my_room_data_bloc.dart';
import 'package:general/src/features/home/presentation/home/bloc/fetch_my_room_data_manager/fetch_my_room_data_event.dart';
import 'package:general/src/features/home/presentation/home/bloc/fetch_my_room_data_manager/fetch_my_room_data_state.dart';
import 'package:general/src/features/home/presentation/home/view/widgets/visitors_count.dart';
import 'package:general/src/features/room/room.dart';

class MyRoomCard extends StatefulWidget {
  const MyRoomCard({super.key});

  @override
  State<MyRoomCard> createState() => _MyRoomCardState();
}

class _MyRoomCardState extends State<MyRoomCard> {
  @override
  void initState() {
    if (!di<FetchMyRoomDataBloc>().state.requestState.isLoaded) {
      di<FetchMyRoomDataBloc>().add(const FetchMyRoomDataEvent());
    }
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<FetchMyRoomDataBloc, FetchMyRoomDataState>(
      bloc: di<FetchMyRoomDataBloc>(),
      buildWhen: (prev, curr) =>
          prev.requestState != curr.requestState || prev.rooms != curr.rooms,
      builder: (context, state) {
        if (!di<FetchUserDataBloc>().state.reqState.isLoaded) {
          return const SizedBox();
        }

        final audioRoom = state.rooms?.audio;

        if (audioRoom?.id == 0) {
          /// No rooms -> show create room UI
          return _buildCreateRoomCard(context);
        }

        return Column(
          children: [
            if (audioRoom?.id != 0)
              _buildRoomCard(context, audioRoom!, "audio"),
          ],
        );
      },
    );
  }

  Widget _buildRoomCard(BuildContext context, MyRoom room, String type) {
    return MultiTapCard(
      onTap: () {
        di<RoomStateManager>().navigateToRoom(
          RoomEntryRequest(
            context: context,
            roomData: RoomEntity(
              ownerId: MyDataModel.getInstance().id,
              id: room.id,
              name: room.name,
              cover: room.cover,
              roomBackground: room.roomBackground,
              mode: room.mode.toString(),
              uuidOwnerRoom: MyDataModel.getInstance().uuid ?? "",
              giftPrice: room.giftPrice,
            ),
            isLive: false,
          ),
        );
      },
      child: Stack(
        children: [
          Container(
            padding: context.paddingSymmetric(horizontal: 15, vertical: 15),
            margin: context.paddingSymmetric(vertical: 0, horizontal: 10),
            // Theme-pinned surface (was a baked light-green gradient PNG that
            // clashed with every variant's identity — owner report 2026-08).
            decoration: ColorManager.cardDecoration(
              borderRadius: 15.radius,
              border: Border.all(
                color: ColorManager.primary.withValues(alpha: 0.35),
              ),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                UserImage(
                  image: room.cover,
                  displayName: room.name,
                  imageSize: 75.w,
                  borderRadius: 8.radius,
                ),
                10.wBox,
                SizedBox(
                  width: 200.w,
                  child: TextWidget(
                    room.name,
                    maxLines: 1,
                    style: context.bodyMedium
                        .size(14)
                        .w600
                        .colorExt(ColorManager.textPrimary),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const Spacer(),
                Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    VisitorsCount(
                      count: room.visitorsCount.toString(),
                      fontSize: 15.sp,
                      fontWeight: FontWeight.w500,
                      isList: true,
                    ),
                    if (room.roomLevelImage != "")
                      ImageViewWidget(
                        url: room.roomLevelImage,
                        boxFit: BoxFit.cover,
                        width: 30.w,
                        height: 30.h,
                      ),
                  ],
                ),
              ],
            ),
          ),
          Align(
            alignment: AlignmentDirectional.topEnd,
            child: Container(
              padding: context.paddingSymmetric(horizontal: 10),
              margin: context.paddingOnly(top: 4, end: 15),
              decoration: BoxDecoration(
                color: ColorManager.black.withValues(alpha: 0.4),
                borderRadius: BorderRadius.only(
                  topRight: 8.radiusCircular,
                  bottomLeft: 15.radiusCircular,
                ),
              ),
              child: TextWidget(
                type == "audio" ? "Audio" : "Live",
                // On the black overlay chip the label must stay light under
                // every theme (textPrimary is dark ink on the light themes).
                style: context.bodySmall.colorExt(ColorManager.onDark),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCreateRoomCard(BuildContext context) {
    return Container(
      margin: context.paddingSymmetric(vertical: 10, horizontal: 10),
      padding: context.paddingAll(20),
      // Theme-pinned surface (was the same baked light-green gradient PNG).
      decoration: ColorManager.cardDecoration(
        borderRadius: 15.radius,
        border: Border.all(
          color: ColorManager.primary.withValues(alpha: 0.35),
        ),
      ),
      child: Row(
        children: [
          Container(
            padding: context.paddingAll(20),
            decoration: BoxDecoration(
              color: ColorManager.scaffoldBgAlt,
              borderRadius: 8.radius,
            ),
            child: Icon(Icons.add, color: ColorManager.iconColor, size: 30.h),
          ),
          10.wBox,
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Row(
                  children: [
                    TextWidget(
                      StringManager.createYourRoom.tr(),
                      maxLines: 1,
                      style: context.bodyMedium.size(14).w600,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const Spacer(),
                    ButtonWidget(
                      onPressed: () {},
                      title: StringManager.go.tr(),
                      titleColor: ColorManager.onDark,
                      fontSize: 14.sp,
                      isFittedBox: false,
                      paddingButton: context.paddingZero(),
                      width: 50.w,
                      height: 20.h,
                      backgroundColor: ColorManager.primary,
                    ),
                    20.wBox,
                  ],
                ),
                10.hBox,
                Row(
                  children: [
                    Expanded(
                      child: TextWidget(
                        StringManager.enjoyLiveRoom.tr(),
                        maxLines: 1,
                        style: context.bodyMedium.size(12).w400,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
