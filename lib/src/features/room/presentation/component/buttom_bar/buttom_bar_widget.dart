import 'package:general/src/core/index.dart';
import 'package:general/src/features/room/presentation/component/buttons/basic_tool/chat_button.dart';
import 'package:general/src/features/room/presentation/component/buttons/games_quick_button.dart';
import 'package:general/src/features/room/presentation/component/buttons/pk_button.dart';
import 'package:general/src/features/room/presentation/component/messages/messages_button/input_board_button.dart';
import 'package:general/src/features/room/room.dart';

class ButtomBarWidget extends StatelessWidget {
  const ButtomBarWidget({super.key});

  // Cached getters to avoid repeated calls
  MyDataModel get _myData => MyDataModel.getInstance();
  EnterRoomModel get _room => RoomData.instance.room;
  bool get _isOwner => _myData.id == _room.ownerId;

  bool get _isOnSeat =>
      RoomData.instance.utdController?.seatController
          .isUserOnSeat(_myData.id.toString()) ??
      false;

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<List<SeatState>>(
      valueListenable:
          RoomData.instance.utdController?.seatController.seats ??
              ValueNotifier([]),
      builder: (context, _, __) {
        return Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: ConstantsManager.isTheme1
              ? _buildNewThemeButtons()
              : _buildDefaultThemeButtons(),
        );
      },
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // New Theme Layout
  // ─────────────────────────────────────────────────────────────────────────

  List<Widget> _buildNewThemeButtons() {
    return [
      Row(
        children: [
          const AudioRoomInRoomMessageInputBoardButton(),
          10.wBox,
          const SpeakerButton(),
          if (_isOnSeat) ...[10.wBox, const MicrophoneButton()],
        ],
      ),
      _giftButton,
      Row(
        children: [
          const ChatButton(),
          if (_isOwner) ...[10.wBox, const PkButton()],
          10.wBox,
          if (ConstantsManager.isVariantBuildA || ConstantsManager.isTheme2) ...[
            const EmoijeButton(),
            10.wBox
          ],
          // One-tap games launcher for EVERYONE in all modes.
          if (!ConstantsManager.isVariantBuildA) ...[
            const GamesQuickButton(),
            10.wBox,
          ],
          _basicToolButton(isOnMic: true),
        ],
      ),
    ];
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Default Theme Layout
  // ─────────────────────────────────────────────────────────────────────────

  List<Widget> _buildDefaultThemeButtons() {
    final isOnMic = _isOwner || _isOnSeat;
    return [
      const AudioRoomInRoomMessageInputBoardButton(),
      const ChatButton(),
      if (isOnMic) ...[const EmoijeButton(), const MicrophoneButton()],
      // One-tap games launcher (lucky/rps/dice stacked) — shown for EVERYONE
      // (owner/viewer/on-mic) in all modes; the games were removed from the
      // tools sheet and live only here now.
      if (!ConstantsManager.isVariantBuildA) const GamesQuickButton(),
      _basicToolButton(isOnMic: isOnMic),
      _giftButton,
    ];
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Shared Widgets
  // ─────────────────────────────────────────────────────────────────────────

  Widget _basicToolButton({required bool isOnMic}) => BasicToolButton(
        myDataModel: _myData,
        roomId: _room.id.toString(),
        ownerId: _room.ownerId.toString(),
        isOnMic: isOnMic,
        roomData: _room,
      );

  Widget get _giftButton => GiftButton(
        users: RoomService.instance.getAllUsers(),
        roomData: _room,
        myDataModel: _myData,
      );
}
