import 'package:flutter/material.dart';

class SuperBoomController {
  static ValueNotifier<bool> isSuperBoomVisible = ValueNotifier(false);
  static ValueNotifier<int> roomBoomLevel = ValueNotifier(0);

  static ValueNotifier<String> superBoomVideo = ValueNotifier("");
  static ValueNotifier<String> superBoomVideoType = ValueNotifier("");

  static ValueNotifier<List<Map<String, dynamic>>> activeBombs =
      ValueNotifier([]);
  static ValueNotifier<Map<String, dynamic>?> currentBomb = ValueNotifier(null);

  static void showNextBomb() {
    if (activeBombs.value.isNotEmpty) {
      currentBomb.value = activeBombs.value.first;
    } else {
      currentBomb.value = null;
    }
    currentBomb.notifyListeners();
  }

  static void addBomb(Map<String, dynamic> bomb) {
    activeBombs.value = [...activeBombs.value, bomb];
    if (currentBomb.value == null) {
      showNextBomb();
    }
  }

  static void removeCurrentBomb() {
    currentBomb.value = null;
    Future.delayed(const Duration(milliseconds: 500), () {
      if (activeBombs.value.isNotEmpty) {
        activeBombs.value = List.from(activeBombs.value)..removeAt(0);
      }
      showNextBomb();
    });
  }

  static void reset() {
    isSuperBoomVisible.value = false;
    roomBoomLevel.value = 0;
    superBoomVideo.value = "";
    superBoomVideoType.value = "";
    activeBombs.value = [];
    currentBomb.value = null;
  }
}
