import 'dart:async';
import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/bottom_dialog.dart';
import 'package:general/src/features/room/room.dart';

enum MessageTabType { all, chats, games, gifts }

class TabbedMessagesView extends StatefulWidget {
  const TabbedMessagesView({super.key});

  @override
  State<TabbedMessagesView> createState() => _TabbedMessagesViewState();
}

class _TabbedMessagesViewState extends State<TabbedMessagesView> {
  MessageTabType _selectedTab = MessageTabType.all;
  final ScrollController _scrollController = ScrollController();

  final _rebuildNotifier = ValueNotifier<int>(0);
  Timer? _throttleTimer;
  VoidCallback? _messageListener;

  @override
  void initState() {
    super.initState();
    _messageListener = () {
      if (_throttleTimer == null || !_throttleTimer!.isActive) {
        _throttleTimer = Timer(const Duration(milliseconds: 100), () {
          _rebuildNotifier.value++;
          _autoScrollToEnd();
        });
      }
    };
    RoomData.instance.chatController?.messages.addListener(_messageListener!);
  }

  @override
  void dispose() {
    if (_messageListener != null) {
      RoomData.instance.chatController?.messages
          .removeListener(_messageListener!);
    }
    _throttleTimer?.cancel();
    _scrollController.dispose();
    _rebuildNotifier.dispose();
    super.dispose();
  }

  void _autoScrollToEnd() {
    if (!_scrollController.hasClients) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  bool _filterMessage(UTDChatMessage message) {
    switch (_selectedTab) {
      case MessageTabType.all:
        return true;
      case MessageTabType.chats:
        return _isChatMessage(message);
      case MessageTabType.games:
        return _isGameMessage(message);
      case MessageTabType.gifts:
        return _isGiftMessage(message);
    }
  }

  bool _isChatMessage(UTDChatMessage message) {
    return !_isGameMessage(message) && !_isGiftMessage(message);
  }

  bool _isGameMessage(UTDChatMessage message) {
    final gameType = message.userData['gameType'];
    if (gameType != null && gameType.toString().isNotEmpty) {
      return true;
    }
    return false;
  }

  bool _isGiftMessage(UTDChatMessage message) {
    final msgText = message.text;

    if (msgText.contains('Send a gift') || msgText.contains('ارسل هدية')) {
      return true;
    }

    if (msgText == 'lucky_gift_winner') {
      return true;
    }

    if (msgText == StringManager.winInLuckyBoxMessageKey ||
        msgText == StringManager.sendBoxMessageKey) {
      return true;
    }

    if (message.userData['giftImage'] != null ||
        message.userData['giftName'] != null) {
      return true;
    }

    return false;
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        15.hBox,
        // Tab bar
        _buildTabBar(),
        8.hBox,
        // Messages list
        Expanded(
          child: SizedBox(
            width: MediaQuery.sizeOf(context).width * 0.7,
            child: _buildMessagesList(),
          ),
        ),
      ],
    );
  }

  Widget _buildTabBar() {
    return Container(
      height: 32.h,
      margin: EdgeInsets.symmetric(horizontal: 12.w),
      decoration: BoxDecoration(
        color: ColorManager.black.withValues(alpha: 0.3),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _buildTabItem(
            title: StringManager.all.tr(),
            type: MessageTabType.all,
          ),
          _buildTabItem(
            title: StringManager.chat.tr(),
            type: MessageTabType.chats,
          ),
          _buildTabItem(
            title: StringManager.games.tr(),
            type: MessageTabType.games,
          ),
          _buildTabItem(
            title: StringManager.gift.tr(),
            type: MessageTabType.gifts,
          ),
        ],
      ),
    );
  }

  Widget _buildTabItem({
    required String title,
    required MessageTabType type,
  }) {
    final isSelected = _selectedTab == type;
    return GestureDetector(
      onTap: () {
        setState(() {
          _selectedTab = type;
        });
        _autoScrollToEnd();
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 6.h),
        decoration: BoxDecoration(
          color: ColorManager.transparent,
          border: isSelected
              ? Border(
                  bottom: BorderSide(
                    color: Colors.white,
                    width: 2.h,
                  ),
                )
              : null,
        ),
        child: Text(
          title,
          style: context.bodySmall
              .colorExt(
                isSelected ? ColorManager.white : Colors.white70,
              )
              .w500,
        ),
      ),
    );
  }

  Widget _buildMessagesList() {
    return ValueListenableBuilder<int>(
      valueListenable: _rebuildNotifier,
      builder: (context, _, __) {
        final allMessages =
            RoomData.instance.chatController?.messages.value ?? [];
        final filteredMessages = allMessages.where(_filterMessage).toList();

        return ListView.builder(
          reverse: false,
          controller: _scrollController,
          padding: EdgeInsets.zero,
          itemCount: filteredMessages.length + 2,
          itemBuilder: (context, index) {
            if (index == 0) {
              return _buildDefaultMessageBody(
                imagePath: AssetsManager.logo,
                text: RoomData.instance.room.roomRule,
              );
            } else if (index == 1) {
              return _buildDefaultMessageBody(
                imagePath: AssetsManager.roomIntroMessageIcon,
                text:
                    "${StringManager.roomIntro.tr()} \n${RoomData.instance.room.roomIntro ?? ""}",
              );
            } else {
              final message = filteredMessages[index - 2];
              return InkWell(
                onTap: () {
                  final navContext = SafeNavigator.context;
                  if (message.senderUserId != '-1' && navContext != null) {
                    bottomDailog(
                      context: navContext,
                      widget: UserRoomProfile(
                        userId: message.senderUserId,
                        roomData: RoomData.instance.room,
                      ),
                    );
                  }
                },
                child: MessagesView(
                  message: message,
                ),
              );
            }
          },
        );
      },
    );
  }
}

Widget _buildDefaultMessageBody({
  required String imagePath,
  required String text,
}) {
  return Container(
    padding: const EdgeInsets.all(5),
    margin: const EdgeInsets.all(5),
    decoration: BoxDecoration(
      borderRadius: 3.radius,
      color: const Color(0xFFD9D9D9).withValues(alpha: 0.25),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Container(
          width: 37.w,
          height: 37.h,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            image: DecorationImage(image: AssetImage(imagePath)),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            text,
            style: TextStyle(
              color: ColorManager.white,
              fontSize: 12.sp,
            ),
            overflow: TextOverflow.visible,
          ),
        )
      ],
    ),
  );
}
