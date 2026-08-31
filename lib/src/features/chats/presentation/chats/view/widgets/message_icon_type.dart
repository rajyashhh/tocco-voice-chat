import 'package:flutter/cupertino.dart';
import 'package:general/src/core/index.dart';

class MessageIconType extends StatelessWidget {
  const MessageIconType({
    super.key,
    required this.type,
    required this.message,
  });
  final String type;
  final String message;

  @override
  Widget build(BuildContext context) {
    switch (type) {
      case 'img':
        return Row(
          children: [
            Icon(
              CupertinoIcons.photo,
              size: 16.h,
              color: ColorManager.secondaryText,
            ),
            2.5.wBox,
            TextWidget(
              StringManager.photo.tr(),
              style: context.bodyMedium.colorExt(
                ColorManager.secondaryText,
              ),
            ),
          ],
        );
      case 'voice':
        return Row(
          children: [
            Icon(
              Icons.mic,
              size: 18.h,
              color: ColorManager.secondaryText,
            ),
            TextWidget(
              StringManager.record.tr(),
              style: context.bodyLarge
                  .colorExt(ColorManager.secondaryText),
            ),
          ],
        );
      case 'video':
        return Row(
          children: [
            Icon(
              Icons.video_camera_back,
              size: 18.h,
              color: ColorManager.secondaryText,
            ),
            TextWidget(
              StringManager.video.tr(),
              style: context.bodyLarge
                  .colorExt(ColorManager.secondaryText),
            ),
          ],
        );
      case 'file':
        return Row(
          children: [
            Icon(
              Icons.file_present,
              size: 18.h,
              color: ColorManager.secondaryText,
            ),
            TextWidget(
              StringManager.file.tr(),
              style: context.bodyLarge
                  .colorExt(ColorManager.secondaryText),
            ),
          ],
        );
        case 'CP':
        return Row(
          children: [
            Icon(
              Icons.perm_identity_sharp,
              size: 18.h,
              color: ColorManager.secondaryText,
            ),
            TextWidget(
              StringManager.cP.tr(),
              style: context.bodyLarge
                  .colorExt(ColorManager.secondaryText),
            ),
          ],
        );
      default:
        return TextWidget(
          message,
          maxLines: 1,
          style:
              context.bodySmall.colorExt(ColorManager.lightBlackChat),
          overflow: TextOverflow.ellipsis,
        );
    }
  }
}
