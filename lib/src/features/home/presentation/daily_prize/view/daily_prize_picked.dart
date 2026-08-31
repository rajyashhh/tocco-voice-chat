import 'package:general/src/core/index.dart';

class DailyPrizePicked extends StatelessWidget {
  final String image;
  final String name;

  const DailyPrizePicked({
    super.key,
    required this.image,
    required this.name,
  });

  @override
  Widget build(BuildContext context) {
    // [primary] already resolves per theme (theme_3's primary IS its pink CTA
    // now), so no per-theme special case is needed.
    final highlightColor = ColorManager.primary;

    return Container(
      width: ScreenUtil().screenWidth,
      decoration: ColorManager.cardDecoration(
        borderRadius: 20.radius,
      ),
      child: Container(
        width: ScreenUtil().screenWidth,
        decoration: ColorManager.cardDecoration(
          borderRadius: 20.radius,
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            15.hBox,
            Padding(
              padding: context.paddingSymmetric(horizontal: 20),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  SizedBox(
                    width: 200.w,
                    child: TextWidget(
                      StringManager.youGetYourPrizeSuccess.tr(),
                      style: context.bodyLarge.bold.colorExt(highlightColor),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ],
              ),
            ),
            10.hBox,
            (image.contains(".svga") || image.contains(".zz"))
                ? CacheSvgaWidget(
                    isStopErrorAndLoadingFrame: false,
                    url: image,
                    height: 100.h,
                    width: 110.w,
                    boxFit: BoxFit.contain,
                  )
                : ImageViewWidget(
                    url: image,
                    height: 100.h,
                    width: 110.w,
                    boxFit: BoxFit.contain,
                  ),
            15.hBox,
            Center(
              child: TextWidget(
                name,
                style: context.bodyMedium.colorExt(ColorManager.textPrimary),
              ),
            ),
            15.hBox,
            InkWell(
              onTap: () {
                Navigator.pop(context);
                Methods().showCompleteInfoDialog(
                    context, di<FetchUserDataBloc>().state);
              },
              child: Container(
                height: 45.h,
                width: 200.w,
                padding: context.paddingAll(7.5),
                decoration: BoxDecoration(
                  borderRadius: 30.radius,
                  color: highlightColor,
                ),
                child: Center(
                  child: TextWidget(
                    StringManager.confirm.tr(),
                    style: context.bodyMedium
                        .colorExt(ColorManager.buttonTextColor),
                  ),
                ),
              ),
            ),
            15.hBox,
          ],
        ),
      ),
    );
  }
}
