import 'dart:convert';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/room/presentation/gifts/gift.dart';
import 'package:general/src/features/room/room.dart';

class BackgroundWidget extends StatelessWidget {
  final bool isAudioRoom;

  const BackgroundWidget({
    super.key,
    this.isAudioRoom = false,
  });

  @override
  Widget build(BuildContext context) {
    return MultiBlocListener(
      listeners: [
        BlocListener<SendGiftBloc, SendGiftStates>(
          bloc: di<SendGiftBloc>(),
          listener: (context, state) {
            if (state is ErrorSendGiftStates) {
              Methods.showToast(
                context,
                isError: true,
                message: state.error,
              );
            } else if (state is SuccessSendGiftStates) {
              RoomData.instance.chatController?.sendMessage(
                state.message,
                userData: {
                  "img": MyDataModel.getInstance().profile?.image ?? "",
                  "bu": MyDataModel.getInstance().bubble ?? "",
                  "buId": MyDataModel.getInstance().bubbleId.toString(),
                  "sL": MyDataModel.getInstance().level?.senderImage ?? "",
                  "rL": MyDataModel.getInstance().level?.receiverImage ?? "",
                  "v": MyDataModel.getInstance().vip1?.img1 ?? "",
                  "c": MyDataModel.getInstance().vip1?.colorName ?? "",
                  'type': 'gift',
                },
              );
              if (GiftBottomBar.giftType == TypeGift.bag) {
                Methods.printLog("Fetch Bag Gift After Send Gift");
                di<FetchGiftBloc>().add(const FetchBagGiftEvent());
              }
            }
          },
        ),
        BlocListener<OnRoomBloc, OnRoomStates>(
          bloc: di<OnRoomBloc>(),
          listener: (context, state) async {
            if (state is RemovePassRoomSucssesState) {
              Methods.showToast(context, message: state.message);
            }
            // A rejected special-message (yallow banner) send used to fail
            // 100% silently — surface the backend's error to the sender.
            if (state is SendYallowBannerErrorState) {
              Methods.showToast(
                context,
                isError: true,
                message: state.message,
              );
            }
            if (state is LockCommentsSuccessState) {
              Map<String, dynamic> messagePayload = {
                "messageContent": {
                  "message": "isCommentsClosed",
                  "value": true,
                }
              };
              String map = jsonEncode(messagePayload);
              sendRoomData(data: jsonDecode(map));
            }

            if (state is UnLockCommentsSuccessState) {
              Map<String, dynamic> messagePayload = {
                "messageContent": {
                  "message": "isCommentsClosed",
                  "value": false,
                }
              };
              String map = jsonEncode(messagePayload);
              sendRoomData(data: jsonDecode(map));
            }
          },
        ),
        BlocListener<UpdateRoomBloc, UpdateRoomStates>(
          bloc: di<UpdateRoomBloc>(),
          listener: (context, state) {
            if (state.requestState == RequestState.loaded) {
              RoomBackground.imgBackground.value =
                  state.data?.roomBackground ?? "";
              Methods.showToast(
                context,
                message: StringManager.done.tr(),
              );
            } else if (state.requestState == RequestState.error) {
              Methods.showToast(
                context,
                message: state.error,
                isError: true,
              );
            }
          },
        ),
      ],
      child: const RoomBackground(),
    );
  }
}
