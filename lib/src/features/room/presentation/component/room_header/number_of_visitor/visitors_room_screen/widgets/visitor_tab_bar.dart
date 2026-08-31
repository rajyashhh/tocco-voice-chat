import 'package:general/src/features/games/games.dart';

import '../../../../../../../../core/widgets/md_indicator.dart';

class VisitorTabBar extends StatelessWidget {
  final TabController controller;
  final int usersNo;

  const VisitorTabBar(
      {super.key, required this.controller, required this.usersNo});

  @override
  Widget build(BuildContext context) {
    return TabBar(
      tabAlignment: TabAlignment.start,
      controller: controller,
      overlayColor: WidgetStateColor.transparent,
      dividerHeight: 0,
      isScrollable: true,
      splashFactory: NoSplash.splashFactory,
      labelPadding: context.paddingSymmetric(horizontal: 15, vertical: 5),
      indicator: MDIndicator(
        radius: 20.r,
        indicatorSize: MDIndicatorSize.normal,
        indicatorHeight: 3,
        indicatorWidth: 30.w,
        indicatorColor: ColorManager.black,
      ),
      tabs: [
        TextWidget(
          di<RoomStateManager>().isInAudioRoom
              ? StringManager.onlineUser.tr()
              : "${StringManager.onlineUser.tr()} ($usersNo)",
          style: context.bodyLarge.bold.colorExt(ColorManager.black),
        ),
        TextWidget(
          di<RoomStateManager>().isInAudioRoom
              ? StringManager.roomManager.tr()
              : StringManager.liveManager.tr(),
          style: context.bodyLarge.bold.colorExt(ColorManager.black),
        ),
      ],
    );
  }
}
