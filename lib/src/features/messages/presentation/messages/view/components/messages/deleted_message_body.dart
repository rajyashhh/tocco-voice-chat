import 'package:general/src/core/index.dart';

class TheDeletedMessage extends StatelessWidget {
  final String userId, image;
  final String createdAt;
  final bool isLastMessage;

  const TheDeletedMessage({
    super.key,
    required this.createdAt,
    required this.userId,
    required this.image,
    required this.isLastMessage,
  });

  @override
  Widget build(BuildContext context) {
    final bool isMe = Methods.isMe(userId);
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      mainAxisAlignment: isMe ? MainAxisAlignment.end : MainAxisAlignment.start,
      children: [
        if (!isMe)
          ImageViewWidget(
            url: image,
            height: 45,
            width: 45,
            radius: 25,
          ),
        /* if (!isMe && isLastMessage) */ 10.wBox,
        IntrinsicWidth(
          child: Card(
            shape: RoundedRectangleBorder(borderRadius: 4.radius),
            elevation: 0.0,
            child: Container(
              padding: context.paddingSymmetric(
                vertical: 5.5,
                horizontal: 10,
              ),
              decoration: BoxDecoration(
                borderRadius: 4.radius,
                color: isMe ? ColorManager.primary : ColorManager.textPrimary,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Row(
                    mainAxisAlignment:
                        isMe ? MainAxisAlignment.end : MainAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.do_disturb,
                        color: isMe
                            ? ColorManager.textPrimary
                            : ColorManager.black,
                        size: 20.h,
                      ),
                      10.wBox,
                      FittedBox(
                        child: TextWidget(
                          isMe
                              ? StringManager.deletedThisMessage.tr()
                              : StringManager.messageWasDeleted.tr(),
                          style: context.bodyMedium.colorExt(
                            isMe
                                ? ColorManager.textPrimary
                                : ColorManager.black,
                          ),
                        ),
                      ),
                    ],
                  ),
                  5.hBox,
                  TextWidget(
                    createdAt,
                    style: context.bodyMedium.w400
                        .colorExt(ColorManager.timeColor),
                  ),
                ],
              ),
            ),
          ),
        ),
        /* if (isMe && isLastMessage) */ 10.wBox,
        // if (isMe && isLastMessage)
        //   CircleAvatar(
        //     radius: 22.5.r,
        //     child: ImageViewWidget(
        //       url: MyDataModel.getInstance().profile?.image ?? '',
        //       height: 45,
        //       width: 45,
        //       radius: 25,
        //     ),
        //   ),
      ],
    );
  }
}
