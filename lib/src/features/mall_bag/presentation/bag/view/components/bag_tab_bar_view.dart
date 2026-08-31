part of '../bag_page.dart';

class BagTabBarView extends StatelessWidget {
  final List<MyBagEntity> bagList;
  final MyBagEntity? selectedItem;
  final bool isLoading;
  final MallOrBagType tabType;

  const BagTabBarView({
    required this.bagList,
    required this.selectedItem,
    required this.tabType,
    super.key,
    this.isLoading = false,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Expanded(
          child: RefreshIndicatorWidget(
            onRefresh: () async {
              if (tabType == MallOrBagType.bubble) {
                di<MyBagBloc>().add(const GetBubbleBackPackMyBagEvent());
              }
              if (tabType == MallOrBagType.frame) {
                di<MyBagBloc>().add(const GetFramesMyBagEvent());
              }
              if (tabType == MallOrBagType.intro) {
                di<MyBagBloc>().add(const GetEntrieMyBagEvent());
              }
              if (tabType == MallOrBagType.specialId) {
                di<MyBagBloc>().add(const GetSpecialIdMyBagEvent());
              }
              if (tabType == MallOrBagType.profileFrame) {
                di<MyBagBloc>().add(const GetProfileFrameMyBagEvent());
              }
            },
            child: GridView.builder(
              padding: context.paddingSymmetric(horizontal: 7),
              itemCount: bagList.isEmpty ? 10 : bagList.length,
              shrinkWrap: true,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                childAspectRatio: .85,
                crossAxisSpacing: 8,
                mainAxisSpacing: 8,
              ),
              itemBuilder: (context, index) {
                return Skeletonizer(
                  enabled: isLoading,
                  child: BagProductItem(
                    data: bagList[index],
                    tabType: tabType,
                  ),
                );
              },
            ),
          ),
        ),
        if (selectedItem != null || bagList.isEmpty) ...{
          Container(
            width: ScreenUtil().screenWidth,
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
            decoration: BoxDecoration(
              borderRadius: 5.radius,
              color: ColorManager.white,
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                4.wBox,
                const Spacer(flex: 1),
                5.wBox,
                BlocBuilder<MyBagBloc, MyBagState>(
                  bloc: di<MyBagBloc>(),
                  buildWhen: (prev, curr) => false,
                  builder: (context, state) {
                    int index = bagList.indexWhere(
                      (element) {
                        return element.id == selectedItem?.id;
                      },
                    );

                    index = (index == -1) ? 0 : index;
                    return Expanded(
                      child: Row(
                        children: [
                          if (bagList[index].using == 0)
                            Expanded(
                              child: MainButton(
                                title: StringManager.send.tr(),
                                height: 30.h,
                                isLoading: false,
                                padding: context.paddingZero(),
                                buttonColor: ColorManager.transparent,
                                borderColor: ColorManager.primary,
                                style: context.bodyMedium.w600
                                    .colorExt(ColorManager.primary),
                                onTap: () {
                                  Methods.printLog(
                                      'selectedItem ${selectedItem?.id.toString()}');

                                  customModalBottomSheet(
                                    context,
                                    radius: 0,
                                    height: ScreenUtil().screenHeight,
                                    child: SendBottomSheet(
                                      selectedItem: selectedItem,
                                      isMall: false,
                                    ),
                                  );
                                },
                              ),
                            ),
                          10.wBox,
                          Expanded(
                            child: MainButton(
                              onTap: () {
                                showDialog(
                                  context: context,
                                  builder: (_) => AnimatedDialog(
                                    title: StringManager.mine.tr(),
                                    conText: (bagList[index].isUsed)
                                        ? StringManager.unUse
                                        : StringManager.use,
                                    onTap: () {
                                      final bagItem = bagList[index];
                                      final isUsed = bagItem.isUsed;

                                      final eventType = tabType ==
                                              MallOrBagType.bubble
                                          ? 0
                                          : tabType == MallOrBagType.frame
                                              ? 1
                                              : tabType == MallOrBagType.intro
                                                  ? 2
                                                  : tabType ==
                                                          MallOrBagType
                                                              .specialId
                                                      ? 3
                                                      : 4;

                                      final typeCode = tabType ==
                                              MallOrBagType.frame
                                          ? '1'
                                          : tabType == MallOrBagType.bubble
                                              ? '2'
                                              : tabType == MallOrBagType.intro
                                                  ? '3'
                                                  : tabType ==
                                                          MallOrBagType
                                                              .profileFrame
                                                      ? '28'
                                                      : '4';

                                      if (tabType == MallOrBagType.specialId) {
                                        di<MyBagBloc>().add(
                                          SelectBagItemEvent(
                                            selectedItem:
                                                isUsed ? null : bagItem,
                                            type: eventType,
                                          ),
                                        );

                                        di<UseUnUseBloc>().add(
                                          UseUnUseSpecialIdEvent(
                                            param: UseUnUseBagItemParam(
                                              itemId: bagItem.id.toString(),
                                              isUsed: !isUsed,
                                            ),
                                            tabType: tabType,
                                          ),
                                        );
                                      } else {
                                        if (isUsed) {
                                          di<UseUnUseBloc>().add(
                                            UnUseEvent(
                                              param: UseUnUseBagItemParam(
                                                itemId: bagItem.id.toString(),
                                                type: typeCode,
                                              ),
                                              tabType: tabType,
                                            ),
                                          );
                                          di<MyBagBloc>().add(
                                            SelectBagItemEvent(
                                              selectedItem: null,
                                              type: eventType,
                                            ),
                                          );
                                        } else {
                                          di<UseUnUseBloc>().add(
                                            UseEvent(
                                              param: UseUnUseBagItemParam(
                                                itemId: bagItem.id.toString(),
                                                type: typeCode,
                                              ),
                                              tabType: tabType,
                                            ),
                                          );
                                          di<MyBagBloc>().add(
                                            SelectBagItemEvent(
                                              selectedItem: bagItem,
                                              type: eventType,
                                            ),
                                          );
                                        }
                                      }

                                      Navigator.pop(context);
                                    },
                                    child: ImageViewWidget(
                                      url: bagList[index].image ?? '',
                                      width: 90.w,
                                      height: 80.h,
                                      boxFit: BoxFit.contain,
                                    ),
                                  ),
                                );
                              },
                              height: 30.h,
                              padding: context.paddingZero(),
                              isLoading: false,
                              title: (bagList[index].isUsed)
                                  ? StringManager.unUse.tr()
                                  : StringManager.use.tr(),
                              buttonColor: ColorManager.primary,
                              style: context.bodyMedium.w600
                                  .colorExt(ColorManager.textPrimary),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
        }
      ],
    );
  }
}
