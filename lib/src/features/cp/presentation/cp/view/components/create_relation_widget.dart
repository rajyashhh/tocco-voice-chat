import 'package:general/src/core/index.dart';

class CreateRelationWidget extends StatelessWidget {
  const CreateRelationWidget({super.key});

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
                AssetsManager.giftBanner1,
                fit: BoxFit.fill,
                width: double.infinity,
              ),
              Align(
                alignment: Alignment.topCenter,
                child: Padding(
                  padding: context.paddingOnly(top: 13),
                  child: Text(
                    StringManager.createRelation.tr(),
                    style: context.bodyLarge.w900
                        .colorExt(ColorManager.textPrimary),
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
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                RuleWidget(
                  image: AssetsManager.cpContainer,
                  ruleNo: 1,
                  rule: StringManager.createRelationRule1.tr(),
                ),
                20.hBox,
                RuleWidget(
                  image: AssetsManager.cpRelationRule2,
                  ruleNo: 2,
                  rule: StringManager.createRelationRule2.tr(),
                ),
                20.hBox,
                RuleWidget(
                  image: AssetsManager.cpRelationRule3,
                  ruleNo: 3,
                  rule: StringManager.createRelationRule3.tr(),
                ),
              ],
            ),
          ),
          Image.asset(
            AssetsManager.heartCp,
            fit: BoxFit.fill,
            width: double.infinity,
          ),
        ],
      ),
    );
  }
}

class RuleWidget extends StatelessWidget {
  const RuleWidget(
      {super.key,
      required this.rule,
      required this.image,
      required this.ruleNo});
  final String rule;
  final String image;
  final int ruleNo;

  @override
  Widget build(BuildContext context) {
    return Padding(
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
                    border: Border.all(color: ColorManager.secondaryColor),
                    gradient: const LinearGradient(
                      colors: ColorManager.cpFriends,
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                    ),
                  ),
                  child: Center(
                    child: Text("$ruleNo",
                        style: context.bodySmall.bold
                            .colorExt(ColorManager.textPrimary)),
                  ),
                ),
                15.wBox,
                Expanded(
                  child: Text(rule,
                      style: context.bodySmall.w600
                          .colorExt(ColorManager.secondaryText)),
                ),
              ],
            ),
            20.wBox,
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: Image.asset(
                image,
                width: double.infinity,
                height: 200.h,
                fit: BoxFit.cover,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
