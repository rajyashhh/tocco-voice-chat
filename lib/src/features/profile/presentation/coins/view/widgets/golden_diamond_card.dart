part of'../coins_page.dart';

class GoldenDiamondCard extends StatelessWidget {
  final bool isCoins;

  const GoldenDiamondCard({
    required this.isCoins,
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Container(
        height: 100.h,
        padding:context.paddingSymmetric(horizontal: 12),
        decoration: BoxDecoration(
          borderRadius: 18.radius,
          image: DecorationImage(
            fit: BoxFit.cover,
            image: AssetImage(
              isCoins ? AssetsManager.goldCardBackground : AssetsManager.diamondCardBackground,
            ),
          ),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  isCoins ? StringManager.coins.tr() : StringManager.diamonds.tr(),
                  style: context.bodyLarge.w800.colorExt(ColorManager.textPrimary),
                ),
                25.hBox,
                BlocBuilder<MyStoreBloc, MyStoreState>(
                  bloc: di<MyStoreBloc>(),
                  buildWhen: (prev, curr) => prev.myStore != curr.myStore,
                  builder: (context, state) {
                    return TextWidget(
                      '${isCoins?state.myStore?.coins:state.myStore?.diamonds}',
                      style: context.bodyLarge.w700.size(25).colorExt(ColorManager.textPrimary),
                    );
                  },
                ),
              ],
            ),
            isCoins == true
                ? CoinIcon(
              width: 55.w,
              height: 55.h,
              fallbackAsset: AssetsManager.iconsCoine,
            )
                : Image.asset(
              AssetsManager.diamondIcon,
              width: 55.w,
              height: 55.h,
            ),
          ],
        ),
      ),
    );
  }
}
