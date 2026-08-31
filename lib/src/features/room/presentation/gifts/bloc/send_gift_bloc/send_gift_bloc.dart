import 'dart:async';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_my_level/get_my_level_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_my_level/get_my_level_event.dart';
import 'package:general/src/features/room/data/model/most_used_snapshots.dart';
import 'package:general/src/features/room/presentation/gifts/bloc/gift_bloc/gift_bloc.dart';
import 'package:general/src/features/room/presentation/gifts/view/component/normal_gift/gift_bottom_bar.dart';
import 'package:general/src/features/room/room.dart';

part 'send_gift_events.dart';
part 'send_gift_states.dart';

class SendGiftBloc extends Bloc<SendGiftEvents, SendGiftStates> {
  final SendGiftUC _sendGiftUC;

  SendGiftBloc(this._sendGiftUC) : super(const IntialSendGiftStates()) {
    on<SendGiftesEvent>(_sendGiftEvent);
  }

  Future<void> _sendGiftEvent(
    SendGiftesEvent event,
    Emitter<SendGiftStates> emit,
  ) async {
    emit(const LoadingSendGiftStates());
    final result = await _sendGiftUC(
      GiftParameter(
          roomId: event.roomId,
          id: event.id,
          toUid: event.toUid,
          num: event.num,
          broadcastToRoom: event.broadcastToRoom,
          giftType: GiftBottomBar.giftType == TypeGift.bag
              ? TypeGift.bag.value
              : null),
    );

    result.fold(
      (failure) => emit(
        ErrorSendGiftStates(
          error: NetworkExceptions.getErrorMessage(failure),
        ),
      ),
      (success) {
        emit(SuccessSendGiftStates(message: success.message));

        // Personal "Most Used" counter — only after a CONFIRMED send.
        // Bag sends are excluded: replaying a bag item from the Most Used
        // tab would charge coins instead of consuming bag inventory.
        final giftData = event.giftData;
        if (giftData?.id != null && event.typeGift != TypeGift.bag.value) {
          MostUsedTracker.gift.record(giftData!.id!, giftData.toMostUsedJson());
        }

        // Refresh user experience after sending gift
        di<GetMyLevelBloc>().add(const GetMyLevelData());

        if ((event.selectedGiftPrice *
                event.numberOfGifts *
                event.userSelected.length) <=
            event.myCoins) {
          var mapInformation = {
            "action": "showGifts",
            "messageContent": {
              "message": "showGifts",
              "showGift": event.giftData?.showImg,
              "send_id": MyDataModel.getInstance().id,
              "receiver_id": event.userSelected,
              "gift_price": Methods().convertToAbbreviatedString(
                  (event.selectedGiftPrice * int.parse(event.num)) +
                      Methods().convertFromAbbreviatedString(
                          di<GiftBloc>().state.price)),
              "coins": RoomData.instance.myCoins.value,
              "type": event.giftData?.giftType,
              "giftType": event.typeGift,
              "userGiftTP": event.selectedGiftPrice * int.parse(event.num),
            }
          };

          di<GiftBloc>().add(
            UpdateRoomGiftsPriceEvent(
              price: Methods().convertToAbbreviatedString(
                (event.selectedGiftPrice * int.parse(event.num)) +
                    Methods().convertFromAbbreviatedString(
                        di<GiftBloc>().state.price),
              ),
            ),
          );

          GiftController().showGifts(
            mapInformation,
            MyDataModel.getInstance().id.toString(),
            GiftController().loadMp4Gift,
            GiftController().loadAnimationGift,
            GiftController().loadAlphaMp4,
            RoomData.instance.room.ownerId.toString(),
          );

          // LiveKit does not echo the sender's own data message, so the host's
          // gift never returns through GiftMessageHandler. Credit it to the PK
          // bars here so the sender sees the same totals as everyone else.
          addGiftToPK(
            event.userSelected.map((e) => e.toString()).toList(),
            event.selectedGiftPrice * int.parse(event.num),
          );

          sendRoomData(
            data: mapInformation[messageContent] as Map<String, dynamic>,
          );
        }
      },
    );
  }
}