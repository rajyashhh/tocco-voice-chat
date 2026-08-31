import 'package:flutter/cupertino.dart';
import 'package:general/src/core/index.dart';

class DailyPrizeItem extends StatelessWidget {
  const DailyPrizeItem({
    super.key,
    this.day,
    this.image,
    this.takenPrize,
    this.isLastDay,
    this.isThisDay,
    this.size,
  });

  final String? image;
  final bool? takenPrize;
  final bool? isLastDay;
  final bool? isThisDay;
  final int? day;
  final double? size;

  @override
  Widget build(BuildContext context) {
    // [primary] already resolves per theme (theme_3's primary IS its pink CTA
    // now), so no per-theme special case is needed.
    final highlightColor = ColorManager.primary;

    return InkWell(
      borderRadius: 50.radius,
      child: Container(
        height: 100.h,
        margin: context.paddingSymmetric(horizontal: 3.0),
        decoration: ColorManager.cardDecoration(
          border: Border.all(
            color: isThisDay == true
                ? highlightColor
                : ColorManager.transparent,
          ),
          borderRadius: 10.radius,
        ),
        child: Column(
          children: [
            Align(
              alignment: AlignmentDirectional.topStart,
              child: Container(
                padding: context.paddingSymmetric(vertical: 0.6, horizontal: 7),
                decoration: BoxDecoration(
                  color: highlightColor,
                  borderRadius: BorderRadius.only(
                    topLeft: Methods.getLang() == 'ar'
                        ? 0.radiusCircular
                        : 10.radiusCircular,
                    bottomRight: Methods.getLang() == 'ar'
                        ? 0.radiusCircular
                        : 10.radiusCircular,
                    topRight: Methods.getLang() == 'ar'
                        ? 10.radiusCircular
                        : 0.radiusCircular,
                    bottomLeft: Methods.getLang() == 'ar'
                        ? 10.radiusCircular
                        : 0.radiusCircular,
                  ),
                ),
                child: Text(
                  '$day',
                  style: context.bodyMedium.w400.colorExt(ColorManager.onDark),
                ),
              ),
            ),
            (image!.contains(".svga") || image!.contains(".zz"))
                ? CacheSvgaWidget(
                    isStopErrorAndLoadingFrame: false,
                    url: image ?? '',
                    width: 40.w,
                    height: 40.w,
                    boxFit: BoxFit.cover,
                  )
                : ImageViewWidget(
                    url: image ?? "",
                    height: size ?? 40.h,
                    width: size ?? 40.w,
                    boxFit: BoxFit.contain,
                  ),
            10.hBox,
            if (takenPrize == true) ...[
              Icon(
                CupertinoIcons.checkmark,
                color: highlightColor,
                size: 18.h,
              )
            ] else ...[
              18.hBox,
            ]
          ],
        ),
      ),
    );
  }
}
