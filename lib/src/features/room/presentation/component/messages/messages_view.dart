import 'dart:convert';
import 'package:general/src/core/index.dart';
import 'package:general/src/core/services/bad_words_manager.dart';
import 'package:general/src/core/widgets/vip_container.dart';
import 'package:general/src/features/room/presentation/component/games/free_games/dice_game_body.dart';
import 'package:general/src/features/room/presentation/component/games/free_games/lucky_number_game_body.dart';
import 'package:general/src/features/room/presentation/component/games/free_games/rps_game_body.dart';
import 'package:general/src/features/room/presentation/component/messages/lucky_box_view.dart';
import 'package:general/src/features/room/presentation/component/messages/lucky_box_winner_view.dart';
import 'package:general/src/features/room/presentation/component/messages/lucky_gift_winner_view.dart';
import 'package:general/src/features/room/presentation/gifts/gift.dart';
import 'package:general/src/features/room/room.dart';
import 'package:general/src/features/room/presentation/component/messages/messages_button/input_board.dart';

class MessagesView extends StatefulWidget {
  final UTDChatMessage? message;

  const MessagesView({
    super.key,
    required this.message,
  });

  @override
  State<MessagesView> createState() => _MessagesViewState();
}

class _MessagesViewState extends State<MessagesView> {
  static final Map<int, BubblePadding?> _bubbleCache = {};
  static bool _bubbleFullyParsed = false;

  BubblePadding? findBubblePaddingById(int bubbleId) {
    if (_bubbleCache.containsKey(bubbleId)) {
      return _bubbleCache[bubbleId];
    }

    if (!_bubbleFullyParsed) {
      final cachedJson = HiveManager().getData<String>(
        KeysManager.BUBBLE_PADDING_BOX,
        KeysManager.BUBBLE_PADDING_KEY,
      );
      final parsedMap = _parseBubbleMapFromJson(cachedJson);
      _bubbleCache.addAll(parsedMap.map((k, v) => MapEntry(k, v)));
      _bubbleFullyParsed = true;
    }

    return _bubbleCache[bubbleId];
  }

  static Map<int, BubblePadding> _parseBubbleMapFromJson(String? jsonString) {
    if (jsonString == null || jsonString.isEmpty) return {};
    try {
      final List<dynamic> decoded = jsonDecode(jsonString);
      final list = decoded
          .map((e) => BubblePadding.fromJson(e as Map<String, dynamic>))
          .toList();
      return {for (final element in list) element.id: element};
    } catch (error) {
      Methods.printLog('❌ Failed to decode cache: $error');
      return {};
    }
  }

  @override
  Widget build(BuildContext context) {
    final attrs = widget.message?.userData ?? {};
    return _buildCommentBody(
      context,
      attrs,
      Methods.safeHexColor(attrs["c"]?.toString() ?? "") ?? ColorManager.white,
      padding:
          findBubblePaddingById(int.parse(attrs["buId"]?.toString() ?? "-1"))
              ?.edgeInsets,
      fontSize: ConstantsManager.isVariantBuildA ? 14 : 13,
    );
  }

  static final RegExp _arabicGiftExtractPattern = RegExp(
      r'^(\d+)\s*x\s*ارسل هدية\s*قيمتها\s*(\d+)\s*الى\s*(.+)$',
      caseSensitive: false);
  static final RegExp _englishGiftExtractPattern = RegExp(
      r'^(\d+)\s*x\s*Send a gift\s*its value\s*(\d+)\s*to\s*(.+)$',
      caseSensitive: false);
  final RegExp _arabicGiftCheckPattern = RegExp(r'ارسل هدية\s+قيمتها');
  final RegExp _englishGiftCheckPattern = RegExp(r'Send a gift its value');

  Map<String, dynamic> extractGiftInfo(String text) {
    final normalizedText = text.trim();

    final match = _arabicGiftExtractPattern.firstMatch(normalizedText);
    if (match != null) {
      return {
        'count': int.parse(match.group(1)?.trim() ?? "0"),
        'price': int.parse(match.group(2)?.trim() ?? "0"),
        'name': match.group(3)?.trim(),
        'lang': 'ar',
      };
    }

    final matchEn = _englishGiftExtractPattern.firstMatch(normalizedText);
    if (matchEn != null) {
      return {
        'count': int.parse(matchEn.group(1)?.trim() ?? "0"),
        'price': int.parse(matchEn.group(2)?.trim() ?? "0"),
        'name': matchEn.group(3)?.trim(),
        'lang': 'en',
      };
    }

    return {};
  }

  String formatMessage(UTDChatMessage? message) {
    final rawMessage = message?.text;
    if (rawMessage == null || rawMessage.isEmpty) return '';

    if (rawMessage.contains('BeAdMiN')) {
      final lang = Methods.getLang();
      if (lang == 'en') {
        return rawMessage.replaceAll('BeAdMiN', 'set as administrator');
      } else if (lang == 'ar') {
        return rawMessage.replaceAll('BeAdMiN', ' اصبح مشرفا ');
      } else if (lang == 'tr') {
        return rawMessage.replaceAll('BeAdMiN', 'Bir süpervizör ol');
      } else if (lang == 'ur') {
        return rawMessage.replaceAll('BeAdMiN', 'سپروائزر بنیں۔');
      } else {
        return rawMessage.replaceAll('BeAdMiN', 'पर्यवेक्षक बनें');
      }
    }
    if (rawMessage.contains('removedFromAdmins')) {
      final lang = Methods.getLang();
      if (lang == 'en') {
        return rawMessage.replaceAll(
            'removedFromAdmins', 'removed as administrator');
      } else if (lang == 'ar') {
        return rawMessage.replaceAll(
            'removedFromAdmins', ' محذوف من المشرفين ');
      } else if (lang == 'tr') {
        return rawMessage.replaceAll(
            'removedFromAdmins', 'Yönetici olarak kaldırıldı');
      } else if (lang == 'ur') {
        return rawMessage.replaceAll(
            'removedFromAdmins', 'منتظم کے طور پر ہٹا دیا گیا');
      } else {
        return rawMessage.replaceAll(
            'removedFromAdmins', 'प्रशासक के रूप में हटाया गया');
      }
    }

    if (_arabicGiftCheckPattern.hasMatch(rawMessage) ||
        _englishGiftCheckPattern.hasMatch(rawMessage)) {
      final giftData = extractGiftInfo(rawMessage);
      if (giftData.isEmpty) return rawMessage;

      final lang = Methods.getLang();
      if (lang == 'en') {
        return '${giftData['count']} x Send a gift its value ${giftData['price']} to ${giftData['name']}';
      } else if (lang == 'ar') {
        return '${giftData['count']} x ارسل هدية قيمتها ${giftData['price']} إلى ${giftData['name']}';
      } else if (lang == 'tr') {
        return '${giftData['count']} x ${giftData['name']} kişisine değeri ${giftData['price']} olan bir hediye gönder';
      } else if (lang == 'ur') {
        return '${giftData['count']} x ${giftData['name']} کو ${giftData['price']} مالیت کا تحفہ بھیجیں';
      } else {
        return '${giftData['count']} x ${giftData['name']} को ${giftData['price']} मूल्य का उपहार भेजें';
      }
    }
    return BadWordsManager.instance.filterMessage(rawMessage);
  }

  int estimateLineCountByFixedCharLine(String text) {
    const int charsPerLine = 20;
    final int totalChars = text.length;
    final int lines = (totalChars / charsPerLine).ceil();

    if (totalChars % charsPerLine == 0 && totalChars != 0) {
      return lines + 1;
    }
    if (lines <= 1) return 1;

    return lines;
  }

  Padding _buildCommentBody(
    BuildContext context,
    Map<String, dynamic> value,
    Color getNameColor, {
    EdgeInsets? padding,
    required double fontSize,
  }) {
    return Padding(
      padding: context.paddingAll(5),
      child: widget.message?.text.contains("joinRoom") ?? false
          ? Container(
              padding: context.paddingAll(5),
              decoration: BoxDecoration(
                borderRadius: 4.radius,
                color: const Color(0xFFD9D9D9).withValues(alpha: (0.25)),
              ),
              child: Text.rich(
                _textStyle(
                  "${(widget.message?.senderName ?? "").sanitizedForDisplay} ${StringManager.enterTheRoom.tr()}",
                  mentionStyle: context.bodyMedium
                      .size(fontSize)
                      .colorExt(ColorManager.roomGold),
                  defaultStyle: context.bodyMedium
                      .size(fontSize)
                      .colorExt(ColorManager.white),
                ),
              ),
            )
          : widget.message?.text == StringManager.winInLuckyBoxMessageKey
              ? LuckyBoxWinnerView(
                  name: widget.message?.senderName ?? "",
                  coins: widget.message?.userData['coins']?.toString() ?? "0",
                  fontSize: fontSize,
                )
              : widget.message?.text == StringManager.sendBoxMessageKey
                  ? LuckyBoxView(
                      data: widget.message?.attributes ?? {},
                      name: widget.message?.senderName ?? "",
                      fontSize: fontSize,
                    )
                  : widget.message?.text == "lucky_gift_winner"
                      ? LuckyGiftWinnerView(
                          data: widget.message?.attributes ?? {},
                          fontSize: fontSize,
                        )
                      : Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.start,
                          children: [
                            GestureDetector(
                              onLongPress: () {
                                String name = (widget.message?.senderName ?? "")
                                    .replaceAll(" ", "_");
                                Navigator.of(context).push(
                                  AudioRoomInRoomMessageInputBoard(
                                    mention: "@$name",
                                  ),
                                );
                              },
                              child: CacheImageWidget(
                                url: value['img']?.toString() ?? "",
                                height: 30.h,
                                width: 30.w,
                                shape: BoxShape.circle,
                                // Avatar context: a missing/failed sender image
                                // falls back to their initials, never the logo.
                                displayName: widget.message?.senderName ?? "",
                              ),
                            ),
                            7.5.wBox,
                            Expanded(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.start,
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Column(
                                    mainAxisAlignment: MainAxisAlignment.start,
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      GradientTextVip(
                                        width: 110.w,
                                        isVip: widget.message?.userData["c"] !=
                                                null &&
                                            (widget.message?.userData["c"]
                                                        ?.toString() ??
                                                    '')
                                                .startsWith('#'),
                                        text: (widget.message?.senderName ?? "")
                                            .sanitizedForDisplay,
                                        color: getNameColor,
                                        mainAxisAlignment:
                                            MainAxisAlignment.start,
                                        textAlign: TextAlign.center,
                                        textStyle: context.bodyMedium.w500
                                            .size(fontSize + 1)
                                            .colorExt(getNameColor),
                                      ),
                                      3.hBox,
                                      Row(
                                        mainAxisAlignment:
                                            MainAxisAlignment.start,
                                        crossAxisAlignment:
                                            CrossAxisAlignment.center,
                                        children: [
                                          if (value["v"] != "")
                                            VipContainer(
                                              vip: value["v"] ?? "",
                                              width: 35.w,
                                              height: 16.h,
                                            ),
                                          if (value["v"] != "") 5.wBox,
                                          if (value["rL"] != "")
                                            LevelContainer(
                                              image: value['rL'] ?? "",
                                              isComment: true,
                                              width: 35.w,
                                              height: 25.h,
                                            ),
                                          if (value["sL"] != "") 5.wBox,
                                          if (value["sL"] != "")
                                            LevelContainer(
                                              image: value["sL"] ?? "",
                                              isComment: true,
                                              width: 35.w,
                                              height: 25.h,
                                            ),
                                        ],
                                      ),
                                    ],
                                  ),
                                  3.hBox,
                                  value["bu"] == null || value["bu"] == ""
                                      ? Container(
                                          decoration: BoxDecoration(
                                            borderRadius: 3.radius,
                                            color: const Color(0xFFD9D9D9)
                                                .withValues(alpha: (0.25)),
                                          ),
                                          child: Padding(
                                            padding: context.paddingAll(5),
                                            child: Column(
                                              mainAxisAlignment:
                                                  MainAxisAlignment.start,
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.start,
                                              children: [
                                                _buildMessageBody(
                                                  context,
                                                  fontSize,
                                                ),
                                              ],
                                            ),
                                          ),
                                        )
                                      : ImageViewWidget(
                                          url: value["bu"] ?? "",
                                          width: ScreenUtil().screenWidth,
                                          boxFit: BoxFit.fill,
                                          isBubble: true,
                                          isFromRoom: true,
                                          child: Padding(
                                            padding: padding != null
                                                // Directional: server bubble padding is authored start/end,
                                                // so for Arabic (RTL) it must flip with the text direction —
                                                // absolute left/right made the text sit off the bubble.
                                                ? EdgeInsetsDirectional.only(
                                                    top: padding.top,
                                                    bottom: padding.bottom,
                                                    start: padding.left,
                                                    end: padding.right,
                                                  )
                                                : context.paddingOnly(
                                                    end: 15,
                                                    start: 15,
                                                    bottom: 15,
                                                    top: 20,
                                                  ),
                                            child: Column(
                                              mainAxisAlignment:
                                                  MainAxisAlignment.start,
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.start,
                                              children: [
                                                _buildMessageBody(
                                                  context,
                                                  fontSize,
                                                ),
                                              ],
                                            ),
                                          ),
                                        ),
                                ],
                              ),
                            ),
                          ],
                        ),
    );
  }

  Widget _buildMessageBody(
    BuildContext context,
    double fontSize,
  ) {
    if (widget.message?.text.contains("joinRoom") ?? false) {
      return Padding(
        padding: EdgeInsets.only(left: 8.w, right: 8.w),
        child: Text.rich(
          _textStyle(
            "${(widget.message?.senderName ?? "").sanitizedForDisplay} ${StringManager.enterTheRoom.tr()}",
            mentionStyle: context.bodyMedium
                .size(fontSize)
                .colorExt(ColorManager.roomGold),
            defaultStyle:
                context.bodyMedium.size(fontSize).colorExt(ColorManager.white),
          ),
        ),
      );
    } else if (widget.message?.attributes['gameType'] == "Lucky Number") {
      return Padding(
        padding: context.paddingSymmetric(horizontal: 8.0),
        child: LuckyNumberGameBody(
          key: ValueKey('lucky_${widget.message?.messageID ?? ''}'),
          randomNum: widget.message?.text ?? "",
        ),
      );
    } else if (widget.message?.attributes['gameType'] == "Dice") {
      return DiceGameBody(
        key: ValueKey('dice_${widget.message?.messageID ?? ''}'),
        randomNum: int.parse(widget.message?.text ?? "0"),
      );
    } else if (widget.message?.attributes['gameType'] == "RPS") {
      return RockPaperScissorsGameBody(
        key: ValueKey('rps_${widget.message?.messageID ?? ''}'),
        randomNum: int.parse(widget.message?.text ?? "0"),
      );
    } else {
      return Padding(
        padding: context.paddingSymmetric(horizontal: 8.0),
        child: Text.rich(
          _textStyle(
            formatMessage(widget.message).sanitizedForDisplay,
            mentionStyle: context.bodyMedium
                .size(fontSize)
                .colorExt(ColorManager.roomGold),
            defaultStyle:
                context.bodyMedium.size(fontSize).colorExt(ColorManager.white),
          ),
        ),
      );
    }
  }

  TextSpan _textStyle(
    String text, {
    required TextStyle mentionStyle,
    required TextStyle defaultStyle,
  }) {
    final mentionRegex = RegExp(r'@\w+');
    final spans = <TextSpan>[];

    text.splitMapJoin(
      mentionRegex,
      onMatch: (match) {
        spans.add(TextSpan(
          text: match[0],
          style: mentionStyle,
        ));
        return '';
      },
      onNonMatch: (nonMatch) {
        spans.add(
          TextSpan(
            text: nonMatch,
            style: defaultStyle,
          ),
        );
        return '';
      },
    );

    return TextSpan(children: spans);
  }
}
