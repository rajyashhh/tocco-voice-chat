import 'package:general/src/core/index.dart';

class RelationRecoveryWidget extends StatelessWidget {
  const RelationRecoveryWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        border: Border.all(color: ColorManager.transparent),
      ),
      child: Column(
        children: [
          Stack(
            children: [
              Image.asset(
                AssetsManager.bannerRoom1,
                fit: BoxFit.fill,
                width: double.infinity,
              ),
              Align(
                alignment: Alignment.topCenter,
                child: Padding(
                  padding: context.paddingOnly(top: 13),
                  child: Text(
                    StringManager.relationLevel.tr(),
                    style: context.bodyLarge.bold,
                  ),
                ),
              ),
            ],
          ),
          Container(
            decoration: const BoxDecoration(
              color: Color(0xffF5E2FF),
              border: Border(
                left: BorderSide(width: 3, color: ColorManager.gold),
                right: BorderSide(width: 3, color: ColorManager.gold),
              ),
            ),
            child: Padding(
              padding: context.paddingAll(16),
              child: SizedBox(
                width: ScreenUtil().screenWidth * 0.85,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          height: 30.h,
                          width: 30.w,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(color: ColorManager.borderColor),
                            gradient: const LinearGradient(
                              colors: ColorManager.cpCouple,
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                            ),
                          ),
                          child: Center(
                            child: Text(
                              "1",
                              style: context.bodyMedium.bold
                                  .colorExt(ColorManager.textPrimary),
                            ),
                          ),
                        ),
                        15.wBox,
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                StringManager.relationRecoveryTitleR1.tr(),
                                style: context.bodyMedium.w900
                                    .colorExt(ColorManager.textPrimary),
                              ),
                              const SizedBox(
                                height: 5,
                              ),
                              Text(
                                StringManager.relationRecoveryR1.tr(),
                                style: context.bodyMedium.w900
                                    .colorExt(ColorManager.textPrimary),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    20.hBox,
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          height: 30.h,
                          width: 30.w,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border:
                                Border.all(color: ColorManager.colorTextSup),
                            gradient: const LinearGradient(
                              colors: ColorManager.cpBro,
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                            ),
                          ),
                          child: Center(
                            child: Text(
                              "2",
                              style: context.bodySmall.w900
                                  .colorExt(ColorManager.textPrimary),
                            ),
                          ),
                        ),
                        15.wBox,
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                StringManager.relationRecoveryTitleR2.tr(),
                                style: context.bodySmall.w900
                                    .colorExt(ColorManager.textPrimary),
                              ),
                              5.hBox,
                              Text(
                                StringManager.relationRecoveryR2.tr(),
                                style: context.bodySmall.w900
                                    .colorExt(ColorManager.textPrimary),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    20.hBox,
                    ClipRRect(
                      borderRadius: 8.radius,
                      child: CoinIcon(
                        width: double.infinity,
                        height: 200.h,
                        fit: BoxFit.contain,
                        fallbackAsset: AssetsManager.coinsVip,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          Image.asset(
            AssetsManager.vip,
            fit: BoxFit.fill,
            width: double.infinity,
          ),
        ],
      ),
    );
  }
}
