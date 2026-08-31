import '../../../../../../core/index.dart';

class TapBarBody extends StatelessWidget {
  const TapBarBody({super.key, required this.controller});

  final TabController controller;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: context.paddingSymmetric(horizontal: 30),
      decoration: BoxDecoration(
        borderRadius: 30.radius,
        border: Border.all(color: ColorManager.gold),
      ),
      child: TabBar(
        controller: controller,
        indicator: BoxDecoration(
          borderRadius: 20.radius,
          image:  DecorationImage(
            fit: BoxFit.fill,
            image: AssetImage(
              AssetsManager.buyItem,
            ),
          ),
          color: ColorManager.transparent,
        ),
        indicatorPadding: EdgeInsets.zero,
        indicatorSize: TabBarIndicatorSize.tab,
        labelPadding: EdgeInsets.zero,
        padding: EdgeInsets.zero,
        labelColor: ColorManager.blueTabIndicator,
        dividerHeight: 0,
        unselectedLabelColor: ColorManager.whiteColor,
        labelStyle: context.bodyMedium.bold.size(11).colorExt(ColorManager.cardColor),
        tabs: [
          Tab(
            text: StringManager.relationshipRules.tr(),
          ),
          Tab(
            text: StringManager.relationshipPrivileges.tr(),
          ),
          Tab(
            text: StringManager.specialFriend.tr(),
          ),
        ],
      ),
    );
  }
}
