part of 'package:general/src/features/messages/presentation/messages/view/messages_page.dart';

class _SeenWidget extends StatelessWidget {
  const _SeenWidget({required this.entity});
  final MessagesEntity entity;

  @override
  Widget build(BuildContext context) {
    final bool isMe = Methods.isMe('${entity.userId}');
    return Row(
      mainAxisAlignment: MainAxisAlignment.end,
      mainAxisSize: MainAxisSize.min,
      children: [
        TextWidget(
          Methods.utcToLocal(entity.createdAt ?? ""),
          style: context.bodySmall.w500.colorExt(isMe
              ? ColorManager.buttonTextColor.withValues(alpha: (0.7))
              : ColorManager.secondaryText.withValues(alpha: (0.6))),
        ),
        if (isMe) ...[
          5.wBox,
          _statusIcon(entity),
        ],
        5.wBox,
      ],
    );
  }

  // Local-first state takes precedence over the server `status` string so an
  // in-flight bubble shows a clock and a failed send shows the red mark, while
  // delivered/confirmed messages fall back to the receipt derived from
  // `status` (the drift lifecycle collapses sent/delivered/read into `sent`,
  // so the receipt detail lives only in `status`).
  Widget _statusIcon(MessagesEntity e) {
    switch (e.messageState) {
      case MessageState.sending:
        return Icon(
          Icons.access_time,
          size: 14.h,
          color: ColorManager.grey,
        );
      case MessageState.loading:
        return _fromStatus(e.status);
      case MessageState.error:
        return Icon(
          Icons.error_outline,
          size: 16.h,
          color: ColorManager.redAccount,
        );
      case MessageState.sent:
      case MessageState.none:
        return _fromStatus(e.status);
    }
  }

  Widget _fromStatus(String? status) {
    switch (status) {
      case 'seen':
        return Icon(
          Icons.done_all,
          size: 16.h,
          color: ColorManager.blue,
        );
      case 'delivered':
      case 'received':
        return Icon(
          Icons.done_all,
          size: 16.h,
          color: ColorManager.grey,
        );
      case 'sent':
      case 'sended':
        return Icon(
          Icons.done,
          size: 16.h,
          color: ColorManager.grey,
        );
      default:
        return Icon(
          Icons.done,
          size: 16.h,
          color: ColorManager.grey,
        );
    }
  }
}
