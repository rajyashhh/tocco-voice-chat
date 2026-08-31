part of '../bill_page.dart';

class BillItemRow extends StatelessWidget {
  const BillItemRow({super.key, required this.billEntityList});

  final BillEntity billEntityList;
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: context.paddingSymmetric(horizontal: 0, vertical: 15),
      margin: context.paddingAll(5),
      decoration: ColorManager.cardDecoration(
          borderRadius: 5.radius,
          border: Border.all(width: 1, color: ColorManager.cardBorderColor)),
      child: Row(
        children: [
          Padding(
            padding: EdgeInsetsDirectional.symmetric(horizontal: 9.w),
            child: CoinIcon(
              height: 37.h,
              width: 37.w,
              fallbackAsset: AssetsManager.starCoinsIcon,
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextWidget(
                '${StringManager.successfulOperation.tr()} :${billEntityList.operationNum}',
                style: context.bodyMedium.w500
                    .size(12)
                    .colorExt(ColorManager.textPrimary),
              ),
              12.hBox,
              TextWidget(
                billEntityList.createdAt,
                style: context.bodySmall.colorExt(ColorManager.whiteGrey4),
              ),
            ],
          ),
          const Spacer(),
          TextWidget(
            billEntityList.diamonds.toString(),
            style: context.bodyMedium.w600
                .colorExt(ColorManager.textPrimary)
                .size(13),
                
          ),
          10.wBox
        ],
      ),
    );
  }
}
