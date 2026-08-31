import 'package:general/src/core/widgets/bottom_dialog.dart';
import 'package:general/src/features/room/presentation/component/buttons/emojie/emojie_widget.dart';
import 'package:general/src/features/room/room.dart';
import '../../../../../../core/index.dart';

class EmoijeButton extends StatelessWidget {
  const EmoijeButton({super.key});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        if (HomePage.isConnectToInternet == true) {
          bottomDailog(
            context: context,
            widget: EmojieWidget(
              userId: MyDataModel.getInstance().id.toString(),
              roomId: RoomData.instance.room.id.toString(),
            ),
          );
        } else {
          Methods.showToast(
            context,
            message: StringManager.pleaseCheckInternet.tr(),
            isError: true,
          );
        }
      },
      child: CircleAvatar(
        radius: 19.r,
        backgroundColor: ConstantsManager.isVariantBuildA
            ? Colors.black.withValues(alpha: .1)
            : Colors.white.withValues(alpha: .1),
        child: Icon(
          Icons.emoji_emotions,
          color: Colors.white,
          size: 25.sp,
        ),
      ),
    );
  }
}
