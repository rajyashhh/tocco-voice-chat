import 'package:general/src/features/vip/vip.dart';

class VipBottomBar extends StatelessWidget {
  final String price;
  final String expire;
  final String id;
  final String name;
  final String vipBadge;
  final Color color;

  const VipBottomBar({
    required this.vipBadge,
    required this.name,
    required this.expire,
    required this.id,
    required this.price,
    required this.color,
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: context.paddingOnly(bottom: 15, top: 15, start: 10, end: 10),
      width: MediaQuery.of(context).size.width,
      decoration: const BoxDecoration(
        color: ColorManager.backgroundBottomVip,
      ),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                20.wBox,
                CoinIcon(
                  height: 25.h,
                  width: 25.h,
                ),
                3.wBox,
                TextWidget(
                  '$price${StringManager.coins_.tr()}',
                  style:
                      context.bodyLarge.bold.colorExt(const Color(0xffFEC93A)),
                ),
                TextWidget(
                  " /$expire ${StringManager.day.tr()}",
                  style:
                      context.bodyLarge.bold.colorExt(const Color(0xffFEC93A)),
                ),
              ],
            ),
          ),
          Expanded(
            child: ButtonWidget(
              width: 120.w,
              height: 35.h,
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (_) {
                    return AnimatedDialog(
                      conText: StringManager.buy.tr(),
                      onTap: () {
                        di<BuyVipBloc>().add(BuyVipEvent(
                            type: '0', vipId: id, context: context));
                        Navigator.pop(context);
                      },
                      title: name,
                      child: vipBadge.contains('.svga') ||
                              vipBadge.contains('.zz') ||
                              vipBadge.contains('.zzz')
                          ? CacheSvgaWidget(
                              url: vipBadge,
                              height: 120.h,
                              width: 120.w,
                            )
                          : ImageViewWidget(
                              url: vipBadge,
                              height: 120.h,
                              width: 120.w,
                            ),
                    );
                  },
                );
              },
              beginGradient: AlignmentDirectional.topCenter,
              endGradient: AlignmentDirectional.bottomCenter,
              title: TextWidget(
                StringManager.becomeVip.tr(),
                style:
                    context.bodyMedium.w600.colorExt(ColorManager.textPrimary),
              ),
              backgroundColors: ColorManager.vipBuyButtonGradient,
              isFittedBox: false,
              fontSize: 14,
            ),
          )
        ],
      ),
    );
  }
}
