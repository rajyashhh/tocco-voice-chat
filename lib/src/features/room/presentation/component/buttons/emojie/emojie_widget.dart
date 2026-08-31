import 'dart:math';

import 'package:general/src/core/index.dart';
import 'package:general/src/features/room/presentation/component/buttons/emojie/emojie_page_view.dart';
import 'package:general/src/features/room/presentation/component/buttons/emojie/most_used_emojie_view.dart';
import 'package:general/src/features/room/presentation/manager/emojie_manager/emojie_bloc.dart';
import 'package:general/src/features/room/presentation/manager/emojie_manager/emojie_event.dart';
import 'package:general/src/features/room/presentation/manager/emojie_manager/emojie_state.dart';
import 'package:general/src/features/room/room.dart';

class EmojieWidget extends StatefulWidget {
  final String userId;
  final String roomId;

  const EmojieWidget({
    required this.roomId,
    required this.userId,
    super.key,
  });

  @override
  State<EmojieWidget> createState() => _EmojieWidgetState();
}

class _EmojieWidgetState extends State<EmojieWidget>
    with TickerProviderStateMixin {
  late TabController? emojiController;

  @override
  void initState() {
    super.initState();

    // Fetch emoji categories first
    if (!di<EmojieBloc>().state.reqStateEmojisCategory.isLoaded) {
      di<EmojieBloc>().add(const FetchEmojisCategoryEvent());
    }

    _initializeTabController();
  }

  void _initializeTabController() {
    final state = di<EmojieBloc>().state;

    if (state.categories.isEmpty) {
      emojiController = null;
      return;
    }

    // +1 for the leading personal "Most Used" tab.
    final tabLength = state.categories.length + 1;
    emojiController = TabController(length: tabLength, vsync: this);

    // Sheet opens on the Most Used tab, so no category tab is selected yet.
    EmojiePageView.index = -1;

    emojiController?.addListener(() {
      // Category-relative index: the variant-A games row is injected into the
      // FIRST CATEGORY grid (EmojiePageView.index == 0), which now sits at
      // controller index 1 behind the Most Used tab.
      final categoryIndex = (emojiController?.index ?? 0) - 1;
      EmojiePageView.index = categoryIndex;
      final state = di<EmojieBloc>().state;

      if (categoryIndex >= 0 && categoryIndex < state.categories.length) {
        final category = state.categories[categoryIndex];

        // Fetch emojis if not loaded
        if (state.getReqStateById(category.id) != RequestState.loaded) {
          di<EmojieBloc>().add(
            FetchEmojisByCategoryEvent(
              categoryId: category.id,
              typeId: category.id,
            ),
          );
        }
      }
    });

    // Load first tab data
    if (state.categories.isNotEmpty) {
      final firstCategory = state.categories.first;
      if (state.getReqStateById(firstCategory.id) != RequestState.loaded) {
        di<EmojieBloc>().add(
          FetchEmojisByCategoryEvent(
            categoryId: firstCategory.id,
            typeId: firstCategory.id,
          ),
        );
      }
    }
  }

  @override
  void dispose() {
    emojiController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<EmojieBloc, EmojieState>(
      bloc: di<EmojieBloc>(),
      listener: (context, state) {
        // Re-initialize tab controller when categories are loaded
        if (state.reqStateEmojisCategory == RequestState.loaded &&
            (emojiController == null || emojiController!.length == 0)) {
          setState(() {
            _initializeTabController();
          });
        }
      },
      builder: (context, state) {
        if (state.reqStateEmojisCategory == RequestState.loading) {
          return Container(
            height: 342.h,
            decoration: BoxDecoration(
              color: ColorManager.black,
              borderRadius: BorderRadius.only(
                topRight: Radius.circular(15.r),
                topLeft: Radius.circular(15.r),
              ),
            ),
          );
        }

        if (state.reqStateEmojisCategory == RequestState.error) {
          return Container(
            height: 345.h,
            decoration: BoxDecoration(
              color: ColorManager.black,
              borderRadius: BorderRadius.only(
                topRight: Radius.circular(15.r),
                topLeft: Radius.circular(15.r),
              ),
            ),
            child: Center(
              child: ErrorView(
                accentColor: ColorManager.roomGold,
                onTap: () =>
                    di<EmojieBloc>().add(const FetchEmojisCategoryEvent()),
              ),
            ),
          );
        }

        final isOnSeat = (RoomData.instance.utdController?.seatController
                    .isUserOnSeat(widget.userId) ??
                false) ||
            di<RoomStateManager>().isInVideoRoom;

        final showEmojiTabs = state.categories.isNotEmpty &&
            emojiController != null &&
            isOnSeat;
        final showAltGames = ConstantsManager.isVariantBuildA && !isOnSeat;

        // When neither the emoji tabs nor the games row will render, the
        // sheet would otherwise be a bare black box. Show a clean localized
        // empty/unavailable state (light text on the black sheet) instead.
        if (!showEmojiTabs && !showAltGames) {
          return Container(
            height: 345.h,
            decoration: BoxDecoration(
              color: ColorManager.black,
              borderRadius: BorderRadius.only(
                topRight: Radius.circular(15.r),
                topLeft: Radius.circular(15.r),
              ),
            ),
            child: Center(
              child: EmptyView(
                accentColor: ColorManager.roomGold,
                title: StringManager.noEmojis,
                subTitle: StringManager.noEmojisMsg,
                titleStyle: context.bodyMedium.colorExt(ColorManager.white),
                subTitleStyle:
                    context.bodySmall.colorExt(ColorManager.white),
                onTap: () => di<EmojieBloc>()
                    .add(const FetchEmojisCategoryEvent()),
              ),
            ),
          );
        }

        return Container(
          height: showAltGames ? 100.h : 345.h,
          decoration: BoxDecoration(
            color: ColorManager.black,
            borderRadius: BorderRadius.only(
              topRight: Radius.circular(15.r),
              topLeft: Radius.circular(15.r),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (ConstantsManager.isVariantBuildA && !isOnSeat)
                Padding(
                  padding: const EdgeInsets.only(
                    top: 20,
                    left: 20,
                    right: 20,
                  ),
                  child: Row(
                    children: [
                      GestureDetector(
                        onTap: () {
                          Navigator.pop(context);
                          RoomData.instance.chatController?.sendMessage(
                            "${Random().nextInt(9)},${Random().nextInt(9)},${Random().nextInt(9)}",
                            userData: {
                              StringManager.gameType:
                                  StringManager.luckyNumGame,
                              "img":
                                  MyDataModel.getInstance().profile?.image ??
                                      "",
                              "bu": MyDataModel.getInstance().bubble ?? "",
                              "buId": MyDataModel.getInstance()
                                  .bubbleId
                                  .toString(),
                              "sL": MyDataModel.getInstance()
                                      .level
                                      ?.senderImage ??
                                  "",
                              "rL": MyDataModel.getInstance()
                                      .level
                                      ?.receiverImage ??
                                  "",
                              "v": MyDataModel.getInstance().vip1?.img1 ?? "",
                              "c":
                                  MyDataModel.getInstance().vip1?.colorName ??
                                      "",
                              'type': 'games',
                            },
                          );
                        },
                        child: Image.asset(
                          AssetsManager.number12,
                          color: Colors.white,
                          width: 35.w,
                        ),
                      ),
                      20.wBox,
                      GestureDetector(
                        onTap: () {
                          Navigator.pop(context);
                          RoomData.instance.chatController?.sendMessage(
                            "${Random().nextInt(3)}",
                            userData: {
                              StringManager.gameType: StringManager.rps,
                              "img":
                                  MyDataModel.getInstance().profile?.image ??
                                      "",
                              "bu": MyDataModel.getInstance().bubble ?? "",
                              "buId": MyDataModel.getInstance()
                                  .bubbleId
                                  .toString(),
                              "sL": MyDataModel.getInstance()
                                      .level
                                      ?.senderImage ??
                                  "",
                              "rL": MyDataModel.getInstance()
                                      .level
                                      ?.receiverImage ??
                                  "",
                              "v": MyDataModel.getInstance().vip1?.img1 ?? "",
                              "c":
                                  MyDataModel.getInstance().vip1?.colorName ??
                                      "",
                              'type': 'games',
                            },
                          );
                        },
                        child: Image.asset(
                          AssetsManager.rpsGameIcon,
                          color: Colors.white,
                          width: 35.w,
                        ),
                      ),
                      20.wBox,
                      GestureDetector(
                        onTap: () {
                          Navigator.pop(context);
                          RoomData.instance.chatController?.sendMessage(
                            "${Random().nextInt(6)}",
                            userData: {
                              StringManager.gameType: StringManager.diceGame,
                              "img":
                                  MyDataModel.getInstance().profile?.image ??
                                      "",
                              "bu": MyDataModel.getInstance().bubble ?? "",
                              "buId": MyDataModel.getInstance()
                                  .bubbleId
                                  .toString(),
                              "sL": MyDataModel.getInstance()
                                      .level
                                      ?.senderImage ??
                                  "",
                              "rL": MyDataModel.getInstance()
                                      .level
                                      ?.receiverImage ??
                                  "",
                              "v": MyDataModel.getInstance().vip1?.img1 ?? "",
                              "c":
                                  MyDataModel.getInstance().vip1?.colorName ??
                                      "",
                              'type': 'games',
                            },
                          );
                        },
                        child: Image.asset(
                          AssetsManager.dices,
                          color: Colors.white,
                          width: 35.w,
                        ),
                      ),
                    ],
                  ),
                ),

              // Tabs
              if (state.categories.isNotEmpty &&
                  emojiController != null &&
                  isOnSeat)
                Padding(
                  padding: context.paddingOnly(start: 20, top: 20, bottom: 10),
                  child: TabBar(
                    controller: emojiController,
                    isScrollable: true,
                    tabAlignment: TabAlignment.start,
                    dividerHeight: 0,
                    indicatorWeight: 0,
                    indicatorPadding: context.paddingSymmetric(vertical: 2.0),
                    indicatorSize: TabBarIndicatorSize.tab,
                    labelStyle: context.bodyMedium.w600
                        .colorExt(ColorManager.roomButtonText)
                        .size(14),
                    unselectedLabelStyle: context.bodyMedium
                        .colorExt(ColorManager.lightGray2)
                        .size(14),
                    indicator: BoxDecoration(
                      color: ColorManager.roomGold,
                      borderRadius: BorderRadius.horizontal(
                        right: Radius.circular(6.r),
                        left: Radius.circular(6.r),
                      ),
                    ),
                    labelPadding: context.paddingSymmetric(horizontal: 15),
                    tabs: [
                      Tab(text: StringManager.mostUsed.tr()),
                      ...state.categories.map((category) {
                        return Tab(
                            text:
                                Methods.capitalizeFirstLetter(category.title));
                      }),
                    ],
                  ),
                ),

              // Tab Views
              if (state.categories.isNotEmpty &&
                  emojiController != null &&
                  isOnSeat)
                Expanded(
                  child: TabBarView(
                    controller: emojiController,
                    children: [
                      MostUsedEmojieView(userId: widget.userId),
                      ...state.categories.map((category) {
                        return EmojiePageView(
                          numberOfPages: 1,
                          userId: widget.userId,
                          roomId: widget.roomId,
                          categoryId: category.id,
                        );
                      }),
                    ],
                  ),
                ),
            ],
          ),
        );
      },
    );
  }
}
