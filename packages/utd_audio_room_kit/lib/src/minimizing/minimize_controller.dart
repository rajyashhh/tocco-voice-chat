import 'package:flutter/material.dart';

import '../controller/utd_room_controller.dart';
import '../models/minimize_config.dart';
import 'mini_overlay_data.dart';
import 'mini_overlay_machine.dart';

class UTDMinimizeController {
  final UTDRoomController _controller;

  UTDMinimizeController(this._controller);

  UTDMiniOverlayMachine get _machine => UTDMiniOverlayMachine.instance;

  bool get isMinimizing => _machine.isMinimizing;

  ValueNotifier<UTDMiniOverlayState> get stateNotifier =>
      _machine.stateNotifier;

  UTDMinimizeConfig? _config;
  UTDMinimizeConfig? get config => _config;

  void configure(UTDMinimizeConfig config) {
    _config = config;
  }

  UTDMinimizeData? _data;
  UTDMinimizeData? get data => _data;

  NavigatorState? _navigator;

  void captureNavigation(BuildContext context) {
    _navigator = Navigator.of(context);
    final route = ModalRoute.of(context);
    _data = UTDMinimizeData(
      controller: _controller,
      routeName: route?.settings.name,
      routeArguments: route?.settings.arguments,
    );
  }

  bool startMinimize(BuildContext context) {
    if (isMinimizing) return false;
    if (!_controller.isConnected) return false;

    captureNavigation(context);
    _machine.changeState(UTDMiniOverlayState.minimizing);
    Navigator.of(context).pop();
    return true;
  }

  bool restore(BuildContext context) {
    if (!isMinimizing || _data == null) return false;

    _machine.changeState(UTDMiniOverlayState.inAudioRoom);

    final name = _data!.routeName;
    final args = _data!.routeArguments;
    if (name == null) return false;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      Navigator.of(context).pushNamed(name, arguments: args);
    });
    return true;
  }

  bool restoreWithNavigator() {
    if (!isMinimizing || _data == null || _navigator == null) return false;

    _machine.changeState(UTDMiniOverlayState.inAudioRoom);

    final nav = _navigator!;
    final name = _data!.routeName;
    final args = _data!.routeArguments;
    if (name == null) return false;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      nav.pushNamed(name, arguments: args);
    });
    return true;
  }

  Future<void> close() async {
    await _controller.leave();
    _machine.changeState(UTDMiniOverlayState.idle);
    _config?.onClose?.call();
    _data = null;
    _navigator = null;
  }

  /// Dismiss the overlay without triggering onClose or leaving the room.
  /// Used internally when the package auto-cleans a previous room.
  void dismiss() {
    _machine.changeState(UTDMiniOverlayState.idle);
    _data = null;
    _navigator = null;
  }
}
