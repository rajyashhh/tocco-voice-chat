import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/presentation/splash/config_app/config_app_bloc.dart';
import 'package:general/src/features/room/presentation/room_controller.dart';

class RoomBackground extends StatelessWidget {
  const RoomBackground({super.key});
  static ValueNotifier<String> imgBackground = ValueNotifier<String>("");

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<String>(
      valueListenable: RoomBackground.imgBackground,
      builder: (context, edit, _) {
        return ImageViewWidget(
          width: MediaQuery.of(context).size.width,
          height: MediaQuery.of(context).size.height,
          boxFit: BoxFit.cover,
          url: RoomBackground.imgBackground.value == ""
              ? RoomData.instance.room.roomBackground == null ||
                      RoomData.instance.room.roomBackground?.isEmpty == true
                  ? (di<ConfigAppBloc>().state.config?.roomBG ?? "")
                  : (RoomData.instance.room.roomBackground ?? "")
              : RoomBackground.imgBackground.value,
        );
      },
    );
  }
}
