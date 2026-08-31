part of '../medals_page.dart';

class MedalsTabBar extends StatelessWidget {
  final List<String> titles;
  final TabController controller;
  final bool? isScrollable;

  const MedalsTabBar({
    super.key,
    required this.titles,
    required this.controller,
    this.isScrollable = false,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 50.h,
      child: TabBar(
        labelPadding: context.paddingSymmetric(
          horizontal: 10,
        ),
        controller: controller,
        tabAlignment: isScrollable! ? TabAlignment.start : null,
        automaticIndicatorColorAdjustment: false,
        indicatorWeight: 1,
        indicatorColor: ColorManager.transparent,
        dividerHeight: 0,
        isScrollable: isScrollable ?? false,
        unselectedLabelStyle: context.bodyLarge.w400.colorExt(ColorManager.secondaryText),
        padding: EdgeInsets.zero,
        labelStyle: context.bodyLarge.w600.colorExt(ColorManager.textPrimary),
        tabs: [
          for (int i = 0; i < titles.length; i++) Tab(text: titles[i]),
        ],
      ),
    );
  }
}
