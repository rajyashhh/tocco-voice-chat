import 'package:general/src/core/database/app_database.dart';
import 'package:general/src/core/database/tables/chat_tables.dart';
import 'package:general/src/core/index.dart';

/// Reply-quote strip above the composer when a message is being replied to.
class GroupReplyBox extends StatelessWidget {
  final Message? replyTo;
  final VoidCallback onClear;

  const GroupReplyBox({super.key, required this.replyTo, required this.onClear});

  static bool _isMedia(Message reply) =>
      reply.type != MessageContentType.text;

  /// A non-blank one-line preview: media bodies are empty, so show a localized
  /// Photo/Voice/Video placeholder instead of a blank line (matches the DM
  /// reply widget).
  static String _previewFor(Message reply) {
    final body = reply.body;
    if (body != null && body.trim().isNotEmpty) return body;
    switch (reply.type) {
      case MessageContentType.image:
        return StringManager.photo;
      case MessageContentType.audio:
        return StringManager.voice;
      case MessageContentType.video:
        return StringManager.video;
      case MessageContentType.cp:
      case MessageContentType.text:
        return '';
    }
  }

  @override
  Widget build(BuildContext context) {
    final reply = replyTo;
    return AnimatedSize(
      duration: const Duration(milliseconds: 250),
      child: reply == null
          ? const SizedBox.shrink()
          : Container(
              width: double.infinity,
              padding: context.paddingAll(8),
              color: ColorManager.surfaceCardColor,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 4.w,
                    height: 38.h,
                    color: ColorManager.blue,
                    margin: context.paddingAll(6),
                  ),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        TextWidget(
                          StringManager.replyingTo.tr(),
                          style: context.bodyMedium.w600
                              .colorExt(ColorManager.blue),
                        ),
                        4.hBox,
                        TextWidget(
                          _previewFor(reply),
                          // Media labels are localized keys; a text body is not.
                          isTranslate: _isMedia(reply),
                          maxLines: 1,
                          style: context.bodyMedium
                              .colorExt(ColorManager.greyTextColor),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close,
                        color: ColorManager.redAccount),
                    onPressed: onClear,
                  ),
                ],
              ),
            ),
    );
  }
}
