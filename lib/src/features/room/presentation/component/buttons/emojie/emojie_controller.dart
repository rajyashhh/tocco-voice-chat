import 'package:general/src/core/index.dart';

class EmojieController {
  static const String showEmojie = "showEmojie";
  static ValueNotifier<Map<String, EmojieData>> emojies = ValueNotifier({});
  static ValueNotifier<int> updateEmojie = ValueNotifier(0);

  static void reset() {
    emojies.value = {};
    updateEmojie.value = 0;
  }

  Future<void> showingEmojie({
    required String userId,
    required EmojieData emojieData,
    required int timeEmojie,
  }) async {
    if (EmojieController.emojies.value[userId] != null) {
      Future.delayed(const Duration(milliseconds: 1500), () {
        EmojieController.emojies.value.remove(userId);
        EmojieController.emojies.value.putIfAbsent(userId, () => emojieData);
        EmojieController.updateEmojie.value =
            DateTime.now().millisecondsSinceEpoch.toInt() +
                (MyDataModel.getInstance().id ?? -1);

        Future.delayed(const Duration(milliseconds: 1500), () {
          EmojieController.emojies.value.remove(userId);
          EmojieController.updateEmojie.value =
              DateTime.now().millisecondsSinceEpoch.toInt() +
                  (MyDataModel.getInstance().id ?? -1);
        });
      });
    } else {
      EmojieController.emojies.value.remove(userId);
      EmojieController.emojies.value.putIfAbsent(userId, () => emojieData);
      EmojieController.updateEmojie.value =
          DateTime.now().millisecondsSinceEpoch.toInt() +
              (MyDataModel.getInstance().id ?? -1);

      Future.delayed(const Duration(milliseconds: 1500), () {
        EmojieController.emojies.value.remove(userId);
        EmojieController.updateEmojie.value =
            DateTime.now().millisecondsSinceEpoch.toInt() +
                (MyDataModel.getInstance().id ?? -1);
      });
    }
  }
}

class EmojieData {
  final String emojie;
  final int emojieId;
  final int length;
  final String type;

  const EmojieData( {
    required this.emojie,
    required this.emojieId,
    required this.length,
    required this.type,
  });
}
