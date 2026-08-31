import 'package:general/src/features/auth/domain/entities/my_store_entity.dart';
import 'package:general/src/features/payment/presentation/bloc/buy_coins_bloc/buy_coins_bloc.dart';
import 'package:general/src/features/payment/presentation/bloc/buy_coins_bloc/buy_coins_event.dart';
import 'package:general/src/features/payment/presentation/bloc/buy_coins_bloc/buy_coins_state.dart';
import 'package:general/src/features/profile/presentation/coins/bloc/my_store_bloc/my_store_bloc.dart';

import '../../../../../reels_viewer/reels_viewer.dart';

class CoinsTabBar extends StatefulWidget {
  final TabController controller;
  final String txt;
  final Color? color;

  const CoinsTabBar({
    required this.controller,
    super.key,
    required this.txt,
    required this.color,
  });

  @override
  State<CoinsTabBar> createState() => _CoinsTabBarState();
}

class _CoinsTabBarState extends State<CoinsTabBar> {
  @override
  Widget build(BuildContext context) {
    final tabIndex = widget.controller.index;
    final isDiamondEnabled = widget.txt == StringManager.diamond.tr();
    final String currentTabLabel;
    if (tabIndex == 0) {
      currentTabLabel = StringManager.coins.tr();
    } else if (tabIndex == 1 && isDiamondEnabled) {
      currentTabLabel = StringManager.diamond.tr();
    } else {
      currentTabLabel = StringManager.profits.tr();
    }

    return BlocBuilder<BuyCoinsBloc, BuyCoinsState>(
      bloc: di<BuyCoinsBloc>(),
      buildWhen: (prev, curr) => prev.controllerIndex != curr.controllerIndex,
      builder: (context, state) {
        return Stack(
          clipBehavior: Clip.none,
          children: [
            _buildBackgroundIcon(state),
            _buildTabBar(context, state),

            // Conditional tab content
            if (currentTabLabel == StringManager.coins.tr())
              _buildCoinsUI(state)
            else if (currentTabLabel == StringManager.diamond.tr())
              _buildDiamondsUI(state)
            else if (currentTabLabel == StringManager.profits.tr())
              _buildDollarsUI(state),
          ],
        );
      },
    );
  }

  Widget _buildBackgroundIcon(BuyCoinsState state) {
    return Positioned(
      top: -4,
      right: 120,
      child: Opacity(
        opacity: 0.1,
        child: state.controllerIndex == 0
            ? CoinIcon(
                width: 130.w,
                fit: BoxFit.fill,
                fallbackAsset: AssetsManager.coinsVip,
              )
            : Image.asset(
                widget.txt == StringManager.diamond.tr()
                    ? AssetsManager.diamondsIcon
                    : AssetsManager.dollar1,
                width: 130.w,
                fit: BoxFit.fill,
              ),
      ),
    );
  }

  Widget _buildTabBar(BuildContext context, BuyCoinsState state) {
    return Container(
      padding: context.paddingOnly(bottom: 50),
      decoration: BoxDecoration(
        color: widget.color,
        borderRadius: BorderRadius.only(
          bottomRight: Radius.circular(20.r),
          bottomLeft: Radius.circular(20.r),
        ),
      ),
      child: Container(
        padding: context.paddingSymmetric(horizontal: 10, vertical: 5),
        margin: context.paddingAll(15),
        decoration: BoxDecoration(
          color: ColorManager.white.withValues(alpha: (0.2)),
          borderRadius: 30.radius,
        ),
        child: TabBar(
          controller: widget.controller,
          isScrollable: false,
          indicatorSize: TabBarIndicatorSize.tab,
          dividerHeight: 0,
          indicator: BoxDecoration(
            color: ColorManager.white,
            borderRadius: 25.radius,
          ),
          onTap: (value) =>
              di<BuyCoinsBloc>().add(ChangeValueEvent(controllerIndex: value)),
          labelColor: ColorManager.walletCard,
          indicatorColor: ColorManager.transparent,
          labelPadding: context.paddingSymmetric(horizontal: 10, vertical: 5),
          unselectedLabelStyle: context.bodyMedium
              .colorExt(ColorManager.secondaryText)
              .size(16),
          labelStyle: context.bodyMedium.w600.colorExt(widget.color!),
          tabs: [
            TextWidget(StringManager.coins.tr(),
                style:
                    context.bodyMedium.size(14).colorExt(ColorManager.textPrimary)),
            if ((!(StringManager.userType[1]! || StringManager.userType[2]!)))
              TextWidget(StringManager.diamond.tr(),
                  style:
                      context.bodyMedium.size(14).colorExt(ColorManager.textPrimary)),
            TextWidget(StringManager.profits.tr(),
                style:
                    context.bodyMedium.size(14).colorExt(ColorManager.textPrimary)),
          ],
        ),
      ),
    );
  }

  Widget _buildCoinsUI(BuyCoinsState state) {
    return _buildCommonBottomSheet(
      iconPath: AssetsManager.coinsVip,
      isCoin: true,
      valueBuilder: (myStore) => myStore?.coins.toString() ?? '0',
      onTapRoute: Routes.billPage,
    );
  }

  Widget _buildDiamondsUI(BuyCoinsState state) {
    return _buildCommonBottomSheet(
      iconPath: AssetsManager.diamondsIcon,
      valueBuilder: (myStore) => myStore?.diamonds.toString() ?? '0',
      onTapRoute: Routes.diamondRecord,
    );
  }

  Widget _buildDollarsUI(BuyCoinsState state) {
    return _buildCommonBottomSheet(
      iconPath: AssetsManager.dollar1,
      valueBuilder: (myStore) => Methods()
          .convertToAbbreviatedString(myStore?.userUsd ?? 0)
          .toString(),
      onTapRoute: Routes.agencyMemberChargesHistoryScreen,
    );
  }

  Widget _buildCommonBottomSheet({
    required String iconPath,
    required String Function(MyStoreEntity? myStore) valueBuilder,
    required String onTapRoute,
    bool isCoin = false,
  }) {
    return Positioned(
      bottom: -90.h,
      child: Container(
        width: 360.w,
        height: 125.h,
        margin: context.paddingSymmetric(horizontal: 15),
        padding: context.paddingAll(10),
        decoration: BoxDecoration(
          color: ColorManager.surfaceCardColor,
          borderRadius: 10.radius,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Row
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                TextWidget(widget.txt,
                    style: context.bodyMedium
                        .size(16)
                        .w600
                        .colorExt(ColorManager.textPrimary)),
                InkWell(
                  onTap: () => Navigator.pushNamed(context, onTapRoute),
                  child: Container(
                    padding:
                        context.paddingSymmetric(horizontal: 15, vertical: 5),
                    decoration: BoxDecoration(
                      borderRadius: 20.radius,
                      color: ColorManager.grey2.withValues(alpha: (0.1)),
                    ),
                    child: TextWidget(
                      "${StringManager.record.tr()}>",
                      style: context.bodyMedium.size(14).w600.colorExt(
                          ColorManager.secondaryText),
                    ),
                  ),
                ),
              ],
            ),
            8.hBox,
            Row(
              children: [
                isCoin
                    ? CoinIcon(size: 30, fallbackAsset: iconPath)
                    : Image.asset(iconPath, height: 30),
                5.wBox,
                BlocBuilder<MyStoreBloc, MyStoreState>(
                  bloc: di<MyStoreBloc>(),
                  buildWhen: (prev, curr) => prev.myStore != curr.myStore,
                  builder: (context, myStoreState) {
                    return ConstrainedBox(
                      constraints: BoxConstraints(
                        maxWidth: ScreenUtil().screenWidth * 0.6,
                        minWidth: 1,
                      ),
                      child: FittedBox(
                        fit: BoxFit.scaleDown,
                        alignment: AlignmentGeometry.topRight,
                        child: TextWidget(
                          valueBuilder(myStoreState.myStore),
                          style: context.bodyMedium
                              .size(30)
                              .w700
                              .colorExt(ColorManager.textPrimary),
                        ),
                      ),
                    );
                  },
                ),
              ],
            ),
            8.hBox,
          ],
        ),
      ),
    );
  }
}
