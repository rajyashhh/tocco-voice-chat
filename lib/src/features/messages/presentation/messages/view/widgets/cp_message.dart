part of 'package:general/src/features/messages/presentation/messages/view/messages_page.dart';

class CpMessage extends StatelessWidget {
  final String messageId;
  final String message;
  final String userId;
  final String createdAt;
  final ReplayEntity? replay;
  final String messageStatus;
  final String image;
  final bool isLastMessage;

  const CpMessage({
    super.key,
    required this.messageId,
    required this.isLastMessage,
    required this.userId,
    required this.message,
    required this.createdAt,
    required this.image,
    this.replay,
    required this.messageStatus,
  });

  static Map<String, dynamic> _decodePayload(String raw) {
    try {
      final decoded = jsonDecode(raw);
      return decoded is Map ? Map<String, dynamic>.from(decoded) : const {};
    } catch (_) {
      return const {};
    }
  }

  @override
  Widget build(BuildContext context) {
    final bool isMe = Methods.isMe(userId);

    // Decode the CP payload ONCE per build (not inside the BlocBuilder builder,
    // which re-ran jsonDecode on every CP-bloc emission) and tolerate a bad
    // payload instead of throwing in build.
    final Map<String, dynamic> decoded = _decodePayload(message);
    final originalStatus = decoded["status"].toString();

    return BlocBuilder<GetCpRelationsBloc, CpRelationsStates>(
      bloc: di<GetCpRelationsBloc>(),
      buildWhen: (prev, curr) =>
          prev.updatedMessageStatuses != curr.updatedMessageStatuses ||
          prev.loadingMessageId != curr.loadingMessageId ||
          prev.loadingStatus != curr.loadingStatus,
      builder: (context, state) {
        final updatedStatus = state.updatedMessageStatuses[messageId];
        final currentStatus = updatedStatus ?? originalStatus;
        final isLoading = state.loadingMessageId == messageId;
        final isAcceptLoading = isLoading && state.loadingStatus == '1';
        final isRejectLoading = isLoading && state.loadingStatus == '2';
        return Card(
          color: ColorManager.transparent,
          elevation: 0,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            mainAxisAlignment:
                isMe ? MainAxisAlignment.end : MainAxisAlignment.start,
            children: [
              // No per-message avatar in 1:1 chats (groups-only affordance).
              10.wBox,
              Column(
                crossAxisAlignment:
                    isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: context.paddingOnly(
                      start: 15,
                      end: 15,
                      top: 11,
                      bottom: isMe ? 11 : 0,
                    ),
                    decoration: Styles.messagesCardStyle(isMe),
                    child: Container(
                      decoration: BoxDecoration(
                        color: ColorManager.textPrimary.withValues(alpha: 0.5),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Padding(
                        padding: context.paddingAll(8.0),
                        child: Row(
                          mainAxisSize: replay != null
                              ? MainAxisSize.max
                              : MainAxisSize.min,
                          mainAxisAlignment: MainAxisAlignment.start,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Column(
                              children: [
                                ConstrainedBox(
                                  constraints: BoxConstraints(
                                    minWidth: ScreenUtil().screenWidth * 0.25,
                                    maxWidth: ScreenUtil().screenWidth * 0.5,
                                  ),
                                  child: Text(
                                    overflow: TextOverflow.clip,
                                    decoded["title"] ?? "",
                                    style: context.bodyMedium.w700
                                        .colorExt(ColorManager.textPrimary),
                                    textAlign: TextAlign.center,
                                  ),
                                ),
                                10.hBox,

                                // Status: Accepted / Refused
                                if (currentStatus != "0")
                                  Text(
                                    currentStatus == "1"
                                        ? StringManager.requestAccepted.tr()
                                        : StringManager.requestDenied.tr(),
                                    style: context.bodyMedium.w700
                                        .colorExt(
                                          currentStatus == "1"
                                              ? ColorManager.secondaryText
                                                  .withValues(alpha: 0.6)
                                              : ColorManager.redAccount,
                                        )
                                        .size(12),
                                  ),

                                // Buttons if not me & status is pending (0)
                                if (!isMe && currentStatus == "0") ...[
                                  10.hBox,
                                  MultiTapCard(
                                    onTap: () {
                                      di<GetCpRelationsBloc>().add(
                                        CpRelationRespondEvents(
                                          cpId: decoded["id"].toString(),
                                          status: '1',
                                          messageId: messageId,
                                        ),
                                      );
                                    },
                                    child: Container(
                                      width: 80.w,
                                      height: 25.h,
                                      margin: context.paddingSymmetric(
                                          vertical: 10),
                                      decoration: BoxDecoration(
                                        borderRadius: 25.radius,
                                        gradient: const LinearGradient(
                                          colors: [
                                            Color(0xFFFFCA5E),
                                            Color(0xFFFFB02E),
                                          ],
                                          begin: Alignment.topLeft,
                                          end: Alignment.bottomRight,
                                        ),
                                      ),
                                      child: Center(
                                        child: isAcceptLoading
                                            ? const LoadingWidget()
                                            : Text(
                                                StringManager.accept.tr(),
                                                style: context.bodyMedium.w600
                                                    .colorExt(
                                                  ColorManager.textPrimary,
                                                ),
                                                textAlign: TextAlign.center,
                                              ),
                                      ),
                                    ),
                                  ),
                                  MultiTapCard(
                                    onTap: () {
                                      di<GetCpRelationsBloc>().add(
                                        CpRelationRespondEvents(
                                          cpId: decoded["id"].toString(),
                                          status: '2',
                                          messageId: messageId,
                                        ),
                                      );
                                    },
                                    child: Container(
                                      width: 80.w,
                                      height: 25.h,
                                      decoration: BoxDecoration(
                                        borderRadius: 25.radius,
                                        gradient: const LinearGradient(
                                          colors: [
                                            Color(0xFFC9C9C9),
                                            Color(0xFF9C9C9C),
                                          ],
                                          begin: Alignment.topLeft,
                                          end: Alignment.bottomRight,
                                        ),
                                      ),
                                      child: Center(
                                        child: isRejectLoading
                                            ? const LoadingWidget()
                                            : Text(
                                                StringManager.refuse.tr(),
                                                style: context.bodyMedium.w600
                                                    .colorExt(
                                                  ColorManager.textPrimary,
                                                ),
                                                textAlign: TextAlign.center,
                                              ),
                                      ),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                            5.wBox,
                            ImageViewWidget(
                              url: decoded["image"] ?? "",
                              height: 100.h,
                              width: 65.w,
                              boxFit: BoxFit.fill,
                              radius: 5.h,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  5.hBox,
                ],
              ),
              // No per-message avatar in 1:1 chats (groups-only affordance).
              10.wBox,
            ],
          ),
        );
      },
    );
  }
}
