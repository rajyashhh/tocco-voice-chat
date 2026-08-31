

import '../../../../../../core/index.dart';

class RelationLevelWidget extends StatelessWidget {
  const RelationLevelWidget({super.key});

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
                AssetsManager.vipBackground4,
                fit: BoxFit.fill,
                width: double.infinity,
              ),
              Align(
                alignment: Alignment.topCenter,
                child: Padding(
                  padding: context.paddingOnly(top: 13),
                  child: Text(
                    StringManager.relationLevel.tr(),
                    style: context.bodyLarge.bold.colorExt(ColorManager.textPrimary),
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
                    Text(
                      StringManager.relationLevelTitle.tr(),
                    style: context.bodySmall.w900.colorExt(ColorManager.textPrimary),
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
                            border: Border.all(
                                color: ColorManager.secondaryColor),
                            gradient:  const LinearGradient(
                              colors: ColorManager.cpBro,
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                            ),
                          ),
                          child: Center(
                            child: Text(
                              "1",
                              style: context.bodySmall.w900.colorExt(ColorManager.textPrimary),
                            ),
                          ),
                        ),
                        15.wBox,
                        Expanded(
                          child: Text(
                            StringManager.relationLevelRule1.tr() ,
                            style: context.bodySmall.w900.colorExt(ColorManager.textPrimary),
                          ),
                        ),
                      ],
                    ),
                    20.hBox,
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          height:30.h,
                          width: 30.w,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(
                                color: ColorManager.cardColor),
                            gradient:  const LinearGradient(
                              colors: ColorManager.cpBro,
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                            ),
                          ),
                          child: Center(
                            child: Text(
                              "2",
                              style: context.bodySmall.w900.colorExt(ColorManager.textPrimary),
                            ),
                          ),
                        ),
                        15.wBox,
                        Expanded(
                          child: Text(
                            StringManager.relationLevelRule2.tr() ,

                            style: context.bodySmall.w900.colorExt(ColorManager.textPrimary),
                          ),
                        ),
                      ],
                    ),
                    20.hBox,
                    Text(
                      StringManager.relationLevelNote.tr() ,
                      style: context.bodySmall.w900.colorExt(ColorManager.textPrimary),
                    ),
                  ],
                ),
              ),
            ),
          ),
          Image.asset(
            AssetsManager.vipBackground(vip:0),
            fit: BoxFit.fill,
            width: double.infinity,
          ),
        ],
      ),
    );
  }
}
