import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/md_indicator.dart';
import 'package:general/src/features/room/domain/entities/gift_category_entity.dart';
import 'package:general/src/features/room/presentation/gifts/gift.dart';
import 'package:general/src/features/room/presentation/gifts/view/widgets/most_used_gift_view.dart';
import 'package:general/src/features/room/presentation/gifts/view/widgets/search_textfield.dart';
import 'package:general/src/features/room/room.dart';

class GiftRoomBaseDesign extends StatelessWidget {
  final FetchGiftsStates state;
  final List<GiftCategoryEntity> filteredCategories;
  final TabController? giftController;
  final MyDataModel myDataModel;
  final EnterRoomModel roomData;
  final bool isAudioRoom;
  final bool isSingleUser;
  final List<UTDParticipant>? users;
  final String? userId;
  final String? userImage;
  final String? userName;
  final String? momentId;

  const GiftRoomBaseDesign({
    required this.state,
    required this.filteredCategories,
    required this.giftController,
    required this.myDataModel,
    required this.roomData,
    required this.isAudioRoom,
    required this.isSingleUser,
    required this.users,
    required this.userId,
    required this.userImage,
    required this.userName,
    required this.momentId,
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.end,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          height: MediaQuery.of(context).size.height * 0.45,
          width: double.infinity,
          decoration: const BoxDecoration(
            color: ColorManager.black,
          ),
          padding: context.paddingSymmetric(horizontal: 5),
          child: Column(
            children: [
              if (isAudioRoom)
                isSingleUser
                    ? GiftUserOnly(
                        userId: userId ?? "",
                        ownerId: roomData.ownerId.toString(),
                        userImage: userImage ?? "",
                        userName: userName ?? "",
                        selectedBorderColor: ColorManager.roomGold,
                      )
                    : GiftUser(
                        users: users ?? [],
                        ownerId: roomData.ownerId.toString(),
                        containerHeight: 45.h,
                        useNewThemeLayout: false,
                        selectedBorderColor: ColorManager.roomGold,
                        labelTextColor:
                            ColorManager.white.withValues(alpha: 0.4),
                        seatNumberBackgroundColor: ColorManager.roomGold,
                        seatNumberTextColor: ColorManager.white,
                        allButtonBackgroundColor: ColorManager.roomGold,
                        allButtonTextColor: ColorManager.roomButtonText,
                        allButtonBorderRadius: 50,
                      ),
              const SizedBox(),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 5),
                child: TabBar(
                  tabAlignment: TabAlignment.start,
                  isScrollable: true,
                  dividerHeight: 0,
                  indicatorSize: TabBarIndicatorSize.tab,
                  indicator: MDIndicator(
                    indicatorColor: ColorManager.white,
                    indicatorWidth: 18.0.w,
                    indicatorHeight: 4.h,
                    radius: 20.r,
                  ),
                  labelStyle:
                      context.bodyMedium.colorExt(ColorManager.white),
                  unselectedLabelStyle: context.bodyMedium
                      .colorExt(ColorManager.white.withValues(alpha: (0.6))),
                  controller: giftController,
                  padding: EdgeInsets.zero,
                  labelPadding:
                      context.paddingSymmetric(vertical: 5, horizontal: 10),
                  automaticIndicatorColorAdjustment: false,
                  tabs: [
                    Padding(
                      padding: context.paddingOnly(end: 8.0),
                      child: Text(StringManager.mostUsed.tr()),
                    ),
                    ...filteredCategories.map(
                      (category) => Padding(
                        padding: context.paddingOnly(end: 8.0),
                        child: Text(category.title),
                      ),
                    ),
                    Padding(
                      padding: context.paddingOnly(end: 8.0),
                      child: Text(StringManager.bagGift.tr()),
                    ),
                  ],
                ),
              ),
              ValueListenableBuilder(
                valueListenable: GiftTabBarWithSearchState.showBagGiftNotifier,
                builder: (context, showBagGiftNotifier, child) {
                  return ValueListenableBuilder<bool>(
                    valueListenable:
                        GiftTabBarWithSearchState.showSearchNotifier,
                    builder: (context, showSearch, _) {
                      if (showBagGiftNotifier) {
                        return Expanded(
                          child: PageViewGiftWidget(
                            giftType: TypeGift.bag,
                            data: state.bagGifts,
                            state: state.bagReqState,
                            message: state.bagMessage,
                            userCoins:
                                myDataModel.myStore?.coins.toString() ?? '',
                            myData: myDataModel,
                            categoryId: -2, // Bag category
                            gridPaddingTop: 10,
                            cardBackgroundColor: null,
                            selectedBorderColor: ColorManager.roomGold,
                            normalPriceColor: ColorManager.white,
                            selectedPriceColor: ColorManager.orange,
                            enableAnimation: false,
                            showDownloadProgress: true,
                            showMusicIcon: false,
                          ),
                        );
                      }
                      if (showSearch) {
                        return Expanded(
                          child: PageViewGiftWidget(
                            giftType: TypeGift.vip,
                            data: state.searchGifts,
                            state: state.searchReqState,
                            message: state.searchMessage,
                            userCoins:
                                myDataModel.myStore?.coins.toString() ?? '',
                            myData: myDataModel,
                            categoryId: -3, // Search category
                            gridPaddingTop: 10,
                            cardBackgroundColor: null,
                            selectedBorderColor: ColorManager.roomGold,
                            normalPriceColor: ColorManager.white,
                            selectedPriceColor: ColorManager.orange,
                            enableAnimation: false,
                            showDownloadProgress: true,
                            showMusicIcon: false,
                          ),
                        );
                      } else {
                        return Expanded(
                          child: TabBarView(
                            controller: giftController,
                            children: [
                              MostUsedGiftView(
                                myDataModel: myDataModel,
                                includeLucky: isAudioRoom,
                                gridPaddingTop: 10,
                                cardBackgroundColor: null,
                                selectedBorderColor: ColorManager.roomGold,
                                normalPriceColor: ColorManager.white,
                                selectedPriceColor: ColorManager.orange,
                                enableAnimation: false,
                                showDownloadProgress: true,
                                showMusicIcon: false,
                              ),
                              ...filteredCategories.map((category) {
                                final typeGift =
                                    TypeGift.fromString(category.type);
                                return PageViewGiftWidget(
                                  giftType: typeGift,
                                  data: state.getGiftsById(category.id),
                                  state: state.getReqStateById(category.id),
                                  message: state.getMessageById(category.id),
                                  userCoins:
                                      myDataModel.myStore?.coins.toString() ??
                                          '',
                                  myData: myDataModel,
                                  categoryId: category.id,
                                  gridPaddingTop: 10,
                                  cardBackgroundColor: null,
                                  selectedBorderColor: ColorManager.roomGold,
                                  normalPriceColor: ColorManager.white,
                                  selectedPriceColor: ColorManager.orange,
                                  enableAnimation: false,
                                  showDownloadProgress: true,
                                  showMusicIcon: false,
                                );
                              }),
                              PageViewGiftWidget(
                                giftType: TypeGift.bag,
                                data: state.bagGifts,
                                state: state.bagReqState,
                                message: state.bagMessage,
                                userCoins:
                                    myDataModel.myStore?.coins.toString() ?? '',
                                myData: myDataModel,
                                categoryId: -2, // Bag category
                                gridPaddingTop: 10,
                                cardBackgroundColor: null,
                                selectedBorderColor: ColorManager.roomGold,
                                normalPriceColor: ColorManager.white,
                                selectedPriceColor: ColorManager.orange,
                                enableAnimation: false,
                                showDownloadProgress: true,
                                showMusicIcon: false,
                              ),
                            ],
                          ),
                        );
                      }
                    },
                  );
                },
              ),
              GiftBottomBar(
                showData: true,
                roomData: roomData,
                isRoom: isAudioRoom,
                momentId: momentId ?? "",
                sizeFactor: 1.0,
                containerBackgroundColor: ColorManager.transparent,
                containerTopPadding: 0,
                coinImageScale: 4,
                rechargeTextColor: ColorManager.orange,
                selectionColor: ColorManager.roomGold,
                showRechargeArrow: true,
                rechargeArrowColor: ColorManager.orange,
                sendButtonBorderWidth: 1,
                sendButtonBorderColor: ColorManager.roomGold,
                numberDropdownIconColor: const Color(0xFF2196F3),
                sendButtonGradientColors: const [],
                sendButtonBorderRadius: 30,
                dialogBackgroundColor: ColorManager.greyTabBar,
                dialogBorderColor: ColorManager.roomGold.withValues(alpha: 0.2),
                numberFormatPrefix: 'item',
              ),
            ],
          ),
        )
      ],
    );
  }
}
