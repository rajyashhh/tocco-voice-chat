part of 'package:general/src/features/profile/presentation/profile/view/profile_screen.dart';

class ListTileBody extends StatelessWidget {
  const ListTileBody({super.key,
    required this.image,
    required this.title,
    required this.onTap,
    this.isVip= false,
    this.isCoin = false,

  });
  final String image, title;
  final VoidCallback onTap;
  final bool isVip;

  /// True when this tile's icon is the CURRENCY coin — renders the unified
  /// panel-driven [CoinIcon] (with [image] as the baked fallback).
  final bool isCoin;
  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Row(
        children: [
          12.wBox,
          Padding(
              padding: context.paddingSymmetric(horizontal: 5),
            child: isCoin
                ? CoinIcon(
                    height: 25.h,
                    width: 34.w,
                    fallbackAsset: image,
                  )
                : ImageWidget(
                    image: image,
                    height: 25.h,
                    width: 34.w,
                  ),
          ),


          TextWidget(
            title,
            style: context.bodyMedium.size(16).w400.colorExt(ColorManager.textPrimary)
          ),
           const Spacer(),
          if (isVip)
            Container(
              padding: context.paddingSymmetric(horizontal: 12, vertical: 4),
              decoration: BoxDecoration(
                color: ColorManager.vipCard,
                borderRadius:20.radius,
              ),
              child: TextWidget(
                StringManager.becomeVip.tr(),
                style: context.bodyMedium.w400.colorExt(ColorManager.vipTextCard)
              ),
            ),
          IconButton(onPressed:onTap , icon: Icon(Icons.navigate_next_sharp,color: ColorManager.iconColor.withValues(alpha: (0.4 )),))

        ],
      ),
    );
  }
}
