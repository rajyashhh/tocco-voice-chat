import 'package:general/src/core/index.dart';
import 'package:general/src/features/reels/domain/entities/reel_comment_entity.dart';

class CommentItem extends StatelessWidget {
  final ReelCommentEntity commentEntity;

  const CommentItem({
    super.key,
    required this.commentEntity,
  });

  @override
  Widget build(BuildContext context) {
    // Shared per-theme text getters: this item renders on the comments sheet
    // (light [surfaceCardColor]), and the theme2* tokens froze to theme_2's
    // fixed white/grey — unreadable here. The getters return the same values
    // the old aliasing produced for default/theme_1/theme_2 and the NEXO
    // indigo/slate under theme_3.
    final Color primaryText = ColorManager.textPrimary;
    final Color secondaryText = ColorManager.secondaryText;
    return Padding(
      padding: context.paddingSymmetric(vertical: 8, horizontal: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          UserImage(
            image: commentEntity.userProfilePic ?? '',
            displayName: commentEntity.userName ?? '',
            imageSize: 35.w,
          ),
          10.wBox, 
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextWidget(
                  commentEntity.userName ?? '',
                  style: context.bodyMedium
                      .colorExt(secondaryText)
                      .size(12),
                ),
                4.hBox,
                TextWidget(
                  commentEntity.comment ?? '',
                  style: context.bodyMedium
                      .colorExt(primaryText)
                      .size(12),
                ),
                4.hBox,
                TextWidget(
                  Methods.formatDate(
                      (commentEntity.commentTime ?? ''),
                      locale: context.locale.languageCode),
                  style: context.bodyMedium
                      .colorExt(secondaryText)
                      .size(12),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
