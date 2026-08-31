import 'package:general/src/core/index.dart';
import 'package:general/src/features/messages/messages.dart';

void showDeleteDialog(
  BuildContext context, {
  required VoidCallback onDeleteForEveryone,
  required VoidCallback onDeleteForMe,
}) {
  showDialog(
    context: context,
    builder: (context) {
      return AlertDialog(
        // Theme card surface — the dialog text uses the theme text styles, so
        // a fixed light fill broke readability on the dark default.
        backgroundColor: ColorManager.surfaceCardColor,
        shape: RoundedRectangleBorder(
          borderRadius: 15.radius,
        ),
        content: SizedBox(
          width: MediaQuery.sizeOf(context).width * 0.90,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Align(
                alignment: AlignmentDirectional.topStart,
                child: TextWidget(
                  StringManager.deleteMessage.tr(),
                  style: context.bodyLarge,
                ),
              ),
              30.hBox,
              di<ToggleAppBarBloc>().state.messageSelectionMap.values.any(
                      (message) =>
                          int.parse('${message.senderId}') !=
                          MyDataModel.getInstance().id)
                  ? const SizedBox.shrink() : TextButtonWidget(
                      onTap: onDeleteForEveryone,
                      content: TextWidget(
                        StringManager.removeForAll.tr(),
                        style: context.bodyMedium,
                      ),
                    )
                  ,
              5.hBox,
              TextButtonWidget(
                onTap: onDeleteForMe,
                content: TextWidget(
                  StringManager.removeForMe.tr(),
                  style: context.bodyMedium,
                ),
              ),
              5.hBox,
              TextButtonWidget(
                onTap: () {
                  di<ToggleAppBarBloc>().add(const InitAppBarEvent());
                  context.popRoute();
                },
                content: TextWidget(
                  StringManager.cancel.tr(),
                  style: context.bodyMedium,
                ),
              ),
            ],
          ),
        ),
      );
    },
  );
}
