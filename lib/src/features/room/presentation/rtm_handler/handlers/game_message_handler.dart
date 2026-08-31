import 'package:general/src/core/index.dart';
import 'package:general/src/features/room/presentation/super_bomb/boom_winner_handler.dart';
import 'package:general/src/features/room/room.dart';

import '../room_message_processor.dart';

/// Handles game-related RTM messages: lucky boxes and super boom events.
class GameMessageHandler {
  const GameMessageHandler();

  void handle(CategorizedMessage msg, BuildContext? context) {
    final result = msg.payload;

    switch (msg.messageType) {
      case showLuckyBoxKey:
        showLuckyBox(result);
        break;

      case hideLuckyBoxKey:
        hideLuckyBox(result);
        break;

      case winnerLuckyBoxKey:
        if (context != null) {
          pickFromLuckyBox(result, context);
        }
        break;

      case "roomBoomStarted":
        SuperBoomController.roomBoomLevel.value = 0;
        SuperBoomController.isSuperBoomVisible.value = false;
        Future.delayed(const Duration(milliseconds: 500), () {
          SuperBoomController.roomBoomLevel.value = int.parse(
              result[messageContent]["roomBoomLevel"].toString());
          SuperBoomController.isSuperBoomVisible.value = true;
        });
        break;

      // بانر الصندوق الخارق القادم عبر UTD Stream (نسخة داخل الغرفة).
      // نفس مسار العرض خارج الغرفة: enqueueLuckyBoxBanner بعد إعادة التهيئة.
      case "bannerSuperBox":
        final data = result[messageContent];
        enqueueLuckyBoxBanner({
          "coins": data['coins'],
          "ownerBoxUId": data['boxUId'],
          "ownerBoxName": data['sender']?['s_name'] ?? '',
          "ownerBoxImage": data['sender']?['s_image'] ?? '',
          "ownerRoomId": data['room']?['uuid'] ?? '',
          "room": data['room'] ?? {},
          "ownerBoxSL": data['sender']?['s_sender_level'] ?? 0,
          "ownerBoxRL": data['sender']?['s_receiver_level'] ?? 0,
          "ownerBoxAL": data['ownerBoxAL'] ?? 0,
          "room_type": data['room']['room_type'] ?? 0,
        });
        break;

      case "roomBoomEnded":
        if ((result[messageContent]["video"]?.toString() != null) &&
            (result[messageContent]["video"]?.toString() != "")) {
          SuperBoomController.superBoomVideo.value =
              result[messageContent]['video'];
        }
        if (result[messageContent]["winners"] != null) {
          BoomWinnerHandler.show(result[messageContent]);
        }
        break;

      // بانر نهاية البوم القادم عبر UTD Stream (نسخة داخل الغرفة).
      // نفس مسار العرض خارج الغرفة: SuperBoomController.addBomb.
      case "end_room_boom":
        SuperBoomController.addBomb(result[messageContent]["endData"]);
        break;
    }
  }
}
