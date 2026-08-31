part of '../mall_page.dart';

class _MallTabBarView extends StatefulWidget {
  final List<MallEntity> mall;

  final MallOrBagType tabType;
  final bool isLoading;

  const _MallTabBarView({
    required this.mall,
    required this.tabType,
    this.isLoading = false,
  });

  @override
  State<_MallTabBarView> createState() => _MallTabBarViewState();
}

class _MallTabBarViewState extends State<_MallTabBarView> {
  @override
  void initState() {
    di<MallBloc>().add(
      SelectMallItemEvent(
        selectedItem: widget.mall.isEmpty ? const MallEntity() : widget.mall[0],
      ),
    );
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Expanded(
          child: RefreshIndicatorWidget(
            onRefresh: () async {
              if (widget.tabType == MallOrBagType.bubble) {
                di<MallBloc>().add(const GetBubbleMallEvent());
              }
              if (widget.tabType == MallOrBagType.frame) {
                di<MallBloc>().add(const GetFramesMallEvent());
              }
              if (widget.tabType == MallOrBagType.intro) {
                di<MallBloc>().add(const GetCarMallEvent());
              }
              if (widget.tabType == MallOrBagType.specialId) {
                di<MallBloc>().add(const GetSpecialIdMallEvent());
              }
              if (widget.tabType == MallOrBagType.profileFrame) {
                di<MallBloc>().add(const GetProfileFramesMallEvent());
              }
            },
            child: GridView.builder(
              padding: context.paddingSymmetric(horizontal: 7),
              itemCount: widget.mall.length,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                childAspectRatio: .88,
                mainAxisSpacing: 8,
                crossAxisSpacing: 8,
              ),
              itemBuilder: (context, index) {
                return InkWell(
                  onTap: () {
                    di<MallBloc>().add(
                        SelectMallItemEvent(selectedItem: widget.mall[index]));
                  },
                  child: _MallProductItem(
                    data: widget.mall[index],
                    tabType: widget.tabType,
                    isLoading: widget.isLoading,
                    isSelected: (di<MallBloc>().state.selectedItem?.id ==
                        widget.mall[index].id),
                  ),
                );
              },
            ),
          ),
        ),
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
              CoinIcon(
                width: 20.w,
                fallbackAsset: AssetsManager.coinPayment,
              ),
              4.wBox,
              TextWidget(
                (di<MyStoreBloc>().state.myStore?.coins ?? 0).toString(),
                style: context.bodyMedium.w600
                    .colorExt(ColorManager.yellowTextColor),
              ),
              const Spacer(
                flex: 1,
              ),
              Expanded(
                child: MainButton(
                  title: StringManager.send.tr(),
                  height: 30.h,
                  isLoading: false,
                  padding: context.paddingOnly(start: 3.0, end: 3),
                  buttonColor: ColorManager.transparent,
                  borderColor: ColorManager.primary,
                  style: context.bodyMedium.w600.colorExt(ColorManager.primary),
                  onTap: () {
                    MallEntity? selected = di<MallBloc>().state.selectedItem;
                    customModalBottomSheet(
                      context,
                      radius: 0,
                      height: ScreenUtil().screenHeight,
                      child: SendBottomSheet(
                        isMall: true,
                        selectedItemMall: selected,
                      ),
                    );
                  },
                ),
              ),
              5.wBox,
              Expanded(
                child: MainButton(
                  onTap: () {
                    showDialog(
                      context: context,
                      builder: (BuildContext context) {
                        return AnimatedDialog(
                          onTap: () {
                            if (widget.tabType == MallOrBagType.specialId) {
                              di<MallBuyBloc>().add(
                                BuyItemSpecialIdEvent(
                                  idItem: int.parse(
                                      (di<MallBloc>().state.selectedItem)?.id ??
                                          ""),
                                ),
                              );
                            } else {
                              di<MallBuyBloc>().add(
                                BuyItemEvent(
                                    idItem: (di<MallBloc>().state.selectedItem)
                                            ?.id ??
                                        ""),
                              );
                            }
                            Navigator.pop(context);
                          },
                          description:
                              "${StringManager.youWillBuy.tr()} ${(di<MallBloc>().state.selectedItem)?.name} ${StringManager.price.tr()} ${(di<MallBloc>().state.selectedItem)?.price} ${StringManager.coins.tr()}",
                          title: StringManager.purchase.tr(),
                          conText: StringManager.purchase.tr(),
                          color: ColorManager.primary,
                          child: Padding(
                            padding: context.paddingOnly(bottom: 15.0),
                            child: ImageViewWidget(
                              url: di<MallBloc>().state.selectedItem?.image ??
                                  "",
                              width: 90.w,
                              height: 80.h,
                              boxFit: BoxFit.contain,
                            ),
                          ),
                        );
                      },
                    );
                  },
                  height: 30.h,
                  padding: context.paddingOnly(start: 3.0, end: 3),
                  isLoading: false,
                  title: StringManager.purchase.tr(),
                  buttonColor: ColorManager.primary,
                  style: context.bodyMedium.w600
                      .colorExt(ColorManager.buttonTextColor),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
