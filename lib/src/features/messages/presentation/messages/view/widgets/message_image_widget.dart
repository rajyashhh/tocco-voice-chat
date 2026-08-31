part of 'package:general/src/features/messages/presentation/messages/view/messages_page.dart';

class _MessageImageWidget extends StatefulWidget {
  const _MessageImageWidget({
    required this.entity,
    required this.params,
    required this.isLastMessage,
  });

  final MessagesEntity entity;
  final MessagesParameter params;
  final bool isLastMessage;

  @override
  State<_MessageImageWidget> createState() => MessageImageWidgetState();
}

class MessageImageWidgetState extends State<_MessageImageWidget> {
  static ValueNotifier<Map<int, double>> imageProgressNotifier =
      ValueNotifier({});

  @override
  Widget build(BuildContext context) {
    final bool isMe = Methods.isMe('${widget.entity.userId}');
    return GestureDetector(
      onTap: () {
        bottomDailog(
          context: context,
          widget: Scaffold(
            backgroundColor: ColorManager.black,
            appBar: const AppBarWidget(
              title: '',
            ),
            body: InteractiveViewer(
              child: Container(
                height: MediaQuery.sizeOf(context).height,
                width: MediaQuery.sizeOf(context).width,
                padding: context.paddingAll(10),
                child: widget.entity.albums?.isLocal ?? false
                    ? Image.file(
                        File('${widget.entity.albums?.file}'),
                        fit: BoxFit.contain,
                      )
                    : ImageViewWidget(
                        url:
                            EndPoints.getImage('${widget.entity.albums?.file}'),
                        height: MediaQuery.sizeOf(context).height,
                        width: MediaQuery.sizeOf(context).width,
                        boxFit: BoxFit.contain,
                      ),
              ),
            ),
          ),
        );
      },
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        mainAxisAlignment:
            isMe ? MainAxisAlignment.end : MainAxisAlignment.start,
        children: [
          // No per-message avatar in 1:1 chats (groups-only affordance).
          10.wBox,
          Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                clipBehavior: Clip.hardEdge,
                decoration: BoxDecoration(
                  color: isMe ? ColorManager.primary : ColorManager.surfaceCardColor,
                  borderRadius: !isMe && widget.isLastMessage
                      ? 10.radius.copyWith(bottomLeft: 0.radiusCircular)
                      : isMe && widget.isLastMessage
                          ? 10.radius.copyWith(bottomRight: 0.radiusCircular)
                          : 10.radius,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    if (widget.entity.albums != null) ...[
                      if (widget.entity.albums?.isLocal == true)
                        Image.file(
                          File(widget.entity.albums?.file ?? ''),
                          height: 150.h,
                          width: ScreenUtil().screenWidth / 1.80,
                          fit: BoxFit.cover,
                        )
                      else
                        // LazyMediaImage honors MediaAutoDownloadPrefs — if
                        // auto-download is off (or current network doesn't
                        // match the policy), shows a tap-to-load placeholder
                        // instead of fetching automatically (WhatsApp-style).
                        LazyMediaImage(
                          height: 150.h,
                          width: ScreenUtil().screenWidth / 1.80,
                          url: widget.entity.albums?.file ?? '',
                          boxFit: BoxFit.cover,
                        ),
                    ],
                    10.hBox,
                    _SeenWidget(entity: widget.entity),
                    10.hBox,
                  ],
                ),
              ),

              Positioned(
                bottom: 5,
                right: isMe ? null : 5,
                left: isMe ? 5 : null,
                child: ValueListenableBuilder<Map<int, double>>(
                  valueListenable:
                      MessageImageWidgetState.imageProgressNotifier,
                  builder: (context, progressMap, _) {
                    // Key the progress on THIS bubble's own id so the ring shows
                    // on the correct message (not a single shared static id).
                    final id = widget.entity.id ?? -1;
                    final progress = progressMap[id] ?? 1.0;

                    final shouldShowLoader = progressMap.containsKey(id) &&
                        progress < 1.0 &&
                        progress > 0.0;

                    if (shouldShowLoader) {
                      return Container(
                        height: 26,
                        width: 26,
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.6),
                          shape: BoxShape.circle,
                        ),
                        child: CircularProgressIndicator(
                          value: progress,
                          strokeWidth: 2.5,
                          backgroundColor: Colors.transparent,
                          color: Colors.white,
                        ),
                      );
                    }
                    return const SizedBox.shrink();
                  },
                ),
              ),

              // ✅ Small progress indicator
//               Positioned(
//                 bottom: 5,
//                 right: !isMe ? 5 : null,
//                 left: !isMe ? null : 5,
//                 child: ValueListenableBuilder<Map<int, double>>(
//                   valueListenable:
//                       MessageImageWidgetState.imageProgressNotifier,
//                   builder: (context, progressMap, _) {
//                     final progress = progressMap[imageMessageId] ?? 1.0;
//
//                     Methods.printLog('progressMap keys: ${progressMap} ');
//                     Methods.printLog('widget.entity.id: ${imageMessageId} ');
// //make good condition to make for the item in the map only
//                     if (progress >= 1.0&& imageMessageId!=widget.entity.id) {
//                       return const SizedBox.shrink();
//                     }
//
//                     return Container(
//                       height: 26,
//                       width: 26,
//                       padding: const EdgeInsets.all(4),
//                       decoration: BoxDecoration(
//                         color: Colors.black.withValues(alpha:0.6),
//                         shape: BoxShape.circle,
//                       ),
//                       child: CircularProgressIndicator(
//                         value: progress,
//                         strokeWidth: 2.5,
//                         backgroundColor: Colors.transparent,
//                         color: Colors.white,
//                       ),
//                     );
//                   },
//                 ),
//               ),

              if (widget.entity.reacts != null)
                _EmojisWidget(isMe: isMe, entity: widget.entity),
            ],
          ),
          // No per-message avatar in 1:1 chats (groups-only affordance).
          10.wBox,
        ],
      ),
    );
  }
}
