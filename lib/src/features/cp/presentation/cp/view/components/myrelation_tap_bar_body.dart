
import '../../../../../../core/index.dart';

class MyRelationTapBarBody extends StatelessWidget {
  const MyRelationTapBarBody({super.key, required this.controller});

  final TabController controller;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: context.paddingSymmetric(horizontal: 30),
      decoration: BoxDecoration(
        color: ColorManager.veryLightBlack,
        borderRadius: 30.radius,
        border: Border.all(color: ColorManager.gold),
      ),
      child: ValueListenableBuilder<int>(
        valueListenable: CoinIcon.revision,
        builder: (context, _, __) => TabBar(
        controller: controller,
        indicator: BoxDecoration(
          borderRadius: 20.radius,
          image: DecorationImage(
            fit: BoxFit.fill,
            image: CoinIcon.imageProvider(),
          ),
          color: ColorManager.transparent,
        ),
        indicatorPadding: EdgeInsets.zero,
        indicatorSize: TabBarIndicatorSize.tab,
        labelPadding: EdgeInsets.zero,
        padding: EdgeInsets.zero,
        labelColor: ColorManager.whiteColor,
        dividerHeight: 0,
        unselectedLabelColor: ColorManager.whiteColor,
        labelStyle: context.bodySmall.bold,
        tabs: [
          Tab(
            text: StringManager.cP.tr(),
          ),
          Tab(
            text: StringManager.specialFriend.tr(),
          ),
        ],
        ),
      ),
    );
  }
}
