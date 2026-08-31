import '../../../../../../core/index.dart';
import '../../../../../../core/widgets/md_indicator.dart';
import '../widgets/chat_strings.dart';

class TabBarBody extends StatelessWidget {
  const TabBarBody({
    super.key,
    required this.controller,
    this.allCount = 0,
    this.groupsCount = 0,
    this.unreadCount = 0,
  });

  final TabController controller;

  /// Unread badge counts shown next to each tab (0 = no badge).
  final int allCount;
  final int groupsCount;
  final int unreadCount;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      child: TabBar(
        controller: controller,
        overlayColor: WidgetStateColor.transparent,
        indicatorSize: TabBarIndicatorSize.label,
        dividerHeight: 0,
        isScrollable: true,
        tabAlignment: TabAlignment.start,
        splashFactory: NoSplash.splashFactory,
        labelPadding: context.paddingSymmetric(horizontal: 12),
        unselectedLabelStyle: context.bodyLarge
            .colorExt(ColorManager.textPrimary.withValues(alpha: 0.8))
            .w400
            .size(16),
        labelStyle:
            context.bodyLarge.bold.size(16).colorExt(ColorManager.textPrimary),
        indicator: MDIndicator(
          radius: 20.r,
          indicatorSize: MDIndicatorSize.normal,
          indicatorHeight: 4,
          indicatorWidth: 24.w,
          indicatorColor: ColorManager.textPrimary,
        ),
        tabs: [
          _tab(context, StringManager.all.tr(), allCount),
          _tab(context, StringManager.groups.tr(), groupsCount),
          _tab(context, ChatStrings.unreadTab, unreadCount),
        ],
      ),
    );
  }

  Widget _tab(BuildContext context, String label, int count) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        FittedBox(child: Text(label)),
        if (count > 0) ...[
          6.wBox,
          Container(
            padding: context.paddingSymmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
              color: ColorManager.primary,
              borderRadius: 20.radius,
            ),
            child: Text(
              count > 99 ? '99+' : '$count',
              style: context.bodyMedium
                  .size(11)
                  .w600
                  .colorExt(ColorManager.buttonTextColor),
            ),
          ),
        ],
      ],
    );
  }
}
