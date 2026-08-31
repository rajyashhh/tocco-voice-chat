part of 'package:general/src/features/messages/presentation/messages/view/messages_page.dart';

class _MessageTextWidget extends StatelessWidget {
  const _MessageTextWidget({
    required this.entity,
    required this.params,
    required this.isLastMessage,
  });

  final MessagesEntity entity;
  final MessagesParameter params;
  final bool isLastMessage;

  @override
  Widget build(BuildContext context) {
    final bool isMe = Methods.isMe('${entity.userId}');
    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      mainAxisAlignment: isMe ? MainAxisAlignment.end : MainAxisAlignment.start,
      children: [
        // No per-message avatar in 1:1 chats — the header avatar is enough.
        // (Avatars next to messages are a groups-only affordance.)
        10.wBox,
        Expanded(
          child: Column(
            crossAxisAlignment:
                isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
            children: [
              Stack(
                clipBehavior: Clip.none,
                children: [
                  IntrinsicWidth(
                    child: Container(
                      clipBehavior: Clip.hardEdge,
                      constraints: BoxConstraints(
                        maxWidth: MediaQuery.sizeOf(context).width * 0.75,
                      ),
                      decoration: BoxDecoration(
                        borderRadius: !isMe && isLastMessage
                            ? 10.radius.copyWith(bottomLeft: 0.radiusCircular)
                            : isMe && isLastMessage
                                ? 10
                                    .radius
                                    .copyWith(bottomRight: 0.radiusCircular)
                                : 10.radius,
                        color: isMe
                            ? ColorManager.primary
                            : ColorManager.surfaceCardColor,
                      ),
                      child: Column(
                        crossAxisAlignment: isMe
                            ? CrossAxisAlignment.end
                            : CrossAxisAlignment.end,
                        children: [
                          if (entity.replay != null)
                            _ReplayWidget(
                              entity: entity,
                              isMe: isMe,
                              params: params,
                            ),
                          Container(
                            padding: context.paddingSymmetric(
                                vertical: 7.5, horizontal: 10),
                            decoration: BoxDecoration(
                              borderRadius: entity.replay == null
                                  ? 4.radius
                                  : BorderRadius.only(
                                      bottomLeft: 6.radiusCircular,
                                      bottomRight: 5.radiusCircular,
                                    ),
                              color: isMe
                                  ? ColorManager.primary
                                  : ColorManager.surfaceCardColor,
                            ),
                            child: (entity.message ?? '').length >= 20
                                ? Column(
                                    mainAxisSize: MainAxisSize.min,
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      ExpandableText(
                                        (entity.message ?? "")
                                            .sanitizedForDisplay,
                                        style: context.bodyMedium
                                            .size(16)
                                            .colorExt(isMe
                                                ? ColorManager.buttonTextColor
                                                : ColorManager.textPrimary),
                                        toggleColor: isMe
                                            ? ColorManager.buttonTextColor
                                            : ColorManager.primary,
                                      ),
                                      5.hBox,
                                      Align(
                                        alignment: AlignmentDirectional.centerEnd,
                                        child: _SeenWidget(entity: entity),
                                      ),
                                    ],
                                  )
                                : Row(
                                    mainAxisSize: MainAxisSize.min,
                                    crossAxisAlignment: CrossAxisAlignment.end,
                                    children: [
                                      Text(
                                        (entity.message ?? "")
                                            .sanitizedForDisplay,
                                        style: context.bodyMedium
                                            .size(16)
                                            .colorExt(isMe
                                                ? ColorManager.buttonTextColor
                                                : ColorManager.textPrimary),
                                      ),
                                      5.wBox,
                                      _SeenWidget(entity: entity),
                                    ],
                                  ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  // Tiny "failed → retry" affordance under a red bubble.
                  if (isMe && entity.messageState == MessageState.error) ...[
                    3.hBox,
                    GestureDetector(
                      onTap: () => di<FetchMessagesBloc>().add(
                        RetrySendMessageEvent(
                          failedMessage: entity,
                          peerUserId: params.userId,
                          isNotFriend: params.isNotFriend,
                        ),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.refresh,
                            size: 14.h,
                            color: ColorManager.redAccount,
                          ),
                          3.wBox,
                          TextWidget(
                            StringManager.resendMessage.tr(),
                            isTranslate: false,
                            style: context.bodySmall
                                .colorExt(ColorManager.redAccount)
                                .w600,
                          ),
                        ],
                      ),
                    ),
                  ],
                  entity.reacts == null
                      ? const SizedBox.shrink()
                      : _EmojisWidget(isMe: isMe, entity: entity),
                ],
              ),
            ],
          ),
        ),
        // No per-message avatar in 1:1 chats — the header avatar is enough.
        // (Avatars next to messages are a groups-only affordance.)
        10.wBox,
      ],
    );
  }
}
