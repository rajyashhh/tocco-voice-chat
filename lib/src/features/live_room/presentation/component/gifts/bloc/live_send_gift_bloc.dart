import 'package:general/src/core/index.dart';
import 'package:general/src/features/live_room/presentation/live_room_data.dart';
import 'package:general/src/features/room/data/model/most_used_snapshots.dart';
import 'package:general/src/features/room/presentation/gifts/gift.dart';
import 'package:general/src/features/room/room.dart';

/// Live-room gift send bloc — the standalone counterpart of the audio room's
/// [SendGiftBloc]. It reuses the shared REST send use case ([SendGiftUC]) and
/// the shared gift animation engine ([GiftController]), but broadcasts the gift
/// to other participants on the **live** data channel
/// ([LiveRoomData.sendLiveRoomData]) instead of the audio `sendRoomData` (which
/// targets the null audio controller while in a live room and silently drops).
sealed class LiveSendGiftState extends Equatable {
  const LiveSendGiftState();
  @override
  List<Object?> get props => [];
}

class LiveSendGiftInitial extends LiveSendGiftState {
  const LiveSendGiftInitial();
}

class LiveSendGiftLoading extends LiveSendGiftState {
  const LiveSendGiftLoading();
}

class LiveSendGiftSuccess extends LiveSendGiftState {
  final String message;
  const LiveSendGiftSuccess(this.message);
  @override
  List<Object?> get props => [message];
}

class LiveSendGiftError extends LiveSendGiftState {
  final String error;
  const LiveSendGiftError(this.error);
  @override
  List<Object?> get props => [error];
}

class LiveSendGiftEvent extends Equatable {
  final String roomId;
  final String id;
  final String toUid;
  final String num;
  final String typeGift;
  final List<String> userSelected;
  final List<String> userSelectedName;
  final int selectedGiftPrice;
  final int numberOfGifts;
  final int myCoins;
  final GiftsEntity? giftData;

  const LiveSendGiftEvent({
    required this.roomId,
    required this.id,
    required this.toUid,
    required this.num,
    required this.typeGift,
    required this.userSelected,
    required this.userSelectedName,
    required this.selectedGiftPrice,
    required this.numberOfGifts,
    required this.myCoins,
    required this.giftData,
  });

  @override
  List<Object?> get props => [
        roomId,
        id,
        toUid,
        num,
        typeGift,
        userSelected,
        userSelectedName,
        selectedGiftPrice,
        numberOfGifts,
        myCoins,
        giftData,
      ];
}

class LiveSendGiftBloc extends Bloc<LiveSendGiftEvent, LiveSendGiftState> {
  final SendGiftUC _sendGiftUC;

  LiveSendGiftBloc(this._sendGiftUC) : super(const LiveSendGiftInitial()) {
    on<LiveSendGiftEvent>(_onSend);
  }

  Future<void> _onSend(
    LiveSendGiftEvent event,
    Emitter<LiveSendGiftState> emit,
  ) async {
    emit(const LiveSendGiftLoading());

    final isBag = event.typeGift == TypeGift.bag.value;
    final result = await _sendGiftUC(
      GiftParameter(
        roomId: event.roomId,
        id: event.id,
        toUid: event.toUid,
        num: event.num,
        broadcastToRoom: false,
        giftType: isBag ? TypeGift.bag.value : null,
      ),
    );

    result.fold(
      (failure) => emit(
        LiveSendGiftError(NetworkExceptions.getErrorMessage(failure)),
      ),
      (success) {
        emit(LiveSendGiftSuccess(success.message));

        // Personal "Most Used" counter — only after a CONFIRMED send.
        // Bag sends excluded (replaying one from the tab would charge coins).
        final giftData = event.giftData;
        if (giftData?.id != null && !isBag) {
          MostUsedTracker.gift.record(giftData!.id!, giftData.toMostUsedJson());
        }

        if ((event.selectedGiftPrice *
                event.numberOfGifts *
                event.userSelected.length) <=
            event.myCoins) {
          final accumulatedPrice = Methods().convertToAbbreviatedString(
            (event.selectedGiftPrice * int.parse(event.num)) +
                Methods()
                    .convertFromAbbreviatedString(di<GiftBloc>().state.price),
          );

          final mapInformation = {
            "action": "showGifts",
            "messageContent": {
              "message": "showGifts",
              "showGift": event.giftData?.showImg,
              "send_id": MyDataModel.getInstance().id,
              "receiver_id": event.userSelected,
              "gift_price": accumulatedPrice,
              "coins": RoomData.instance.myCoins.value,
              "type": event.giftData?.giftType,
              "giftType": event.typeGift,
              "userGiftTP": event.selectedGiftPrice * int.parse(event.num),
            }
          };

          di<GiftBloc>()
              .add(UpdateRoomGiftsPriceEvent(price: accumulatedPrice));

          // Local animation for the sender (shared engine).
          GiftController().showGifts(
            mapInformation,
            MyDataModel.getInstance().id.toString(),
            GiftController().loadMp4Gift,
            GiftController().loadAnimationGift,
            GiftController().loadAlphaMp4,
            LiveRoomData.instance.room.ownerId.toString(),
          );

          // Broadcast to other participants on the LIVE data channel.
          LiveRoomData.instance.sendLiveRoomData(
            data: mapInformation[messageContent] as Map<String, dynamic>,
          );

          // Per-stage guest counter (المسّات) — sender side (LiveKit never
          // echoes our own data message back).
          LiveRoomData.instance.addGuestStageTouches(
            event.userSelected,
            event.selectedGiftPrice * int.parse(event.num),
          );

          // Gift line in the live comments — same canonical wire the audio
          // chat views localize/parse ("N x ارسل هدية قيمتها P الى Names"),
          // so it lands in the الكل/هدية tabs styled as a gift message.
          final names = event.userSelectedName.join(' , ');
          final totalPrice = event.selectedGiftPrice * int.parse(event.num);
          final me = MyDataModel.getInstance();
          LiveRoomData.instance.chatController?.sendMessage(
            '${event.num} x ارسل هدية قيمتها $totalPrice الى $names',
            userData: {
              "img": me.profile?.image ?? "",
              "bu": me.bubble ?? "",
              "buId": me.bubbleId.toString(),
              "sL": me.level?.senderImage ?? "",
              "rL": me.level?.receiverImage ?? "",
              "v": me.vip1?.img1 ?? "",
              "c": me.vip1?.colorName ?? "",
              "giftImage": event.giftData?.img ?? "",
              "giftName": event.giftData?.name ?? "",
              'type': 'message',
            },
          );
        }
      },
    );
  }
}
