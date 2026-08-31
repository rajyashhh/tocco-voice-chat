import 'package:general/src/core/index.dart';
class TimeTabs extends StatelessWidget {
  final TabController timeController;
  const TimeTabs({required this.timeController, super.key});

  @override
  Widget build(BuildContext context) {
    Brightness currentBrightness = Theme.of(context).brightness;
    bool isDarkTheme = currentBrightness == Brightness.dark;
    return Container(
      padding: context.paddingSymmetric(horizontal: 7,vertical: 8),
      margin: context.paddingSymmetric(horizontal: 12),
      decoration: BoxDecoration(
          color: isDarkTheme
              ? ColorManager.black.withValues(alpha: (0.5 ))
              : ColorManager.white.withValues(alpha: (0.5 )),
          borderRadius: 20.radius),
      child: TabBar(
          controller: timeController,
          indicatorSize: TabBarIndicatorSize.tab,
          physics: const BouncingScrollPhysics(),
          isScrollable: false,
          indicatorWeight: 0,
          labelColor: ColorManager.primary,
          dividerHeight: 0,
          unselectedLabelColor: Theme.of(context).colorScheme.primary,
          labelStyle: context.bodyLarge,
          unselectedLabelStyle: context.bodyLarge,
          labelPadding: EdgeInsets.zero,
          indicator: BoxDecoration(
            borderRadius: 20.radius,
            color: isDarkTheme ? Colors.black : ColorManager.white,
          ),
          tabs: [
            TextWidget(StringManager.today.tr()),
            TextWidget(StringManager.today.tr()),
            TextWidget(StringManager.weekly.tr()),
            TextWidget(StringManager.monthly.tr()),
          ]),
    );
  }
}
