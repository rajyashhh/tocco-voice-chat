part of 'package:general/src/features/home/presentation/home/view/home_page.dart';

class _TabBarBody extends StatelessWidget {
  const _TabBarBody({required this.controller});

  final TabController controller;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        5.wBox,
        Expanded(
          child: TabBar(
            controller: controller,
            overlayColor: WidgetStateColor.transparent,
            indicatorSize: TabBarIndicatorSize.label,
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            dividerHeight: 0,
            indicator: MDIndicator(
              indicatorColor: ColorManager.headerColor,
              indicatorWidth: 17.0.w,
              indicatorHeight: 4.h,
              radius: 20,
            ),
            labelPadding: context.paddingOnly(end: 10),
            unselectedLabelStyle: context.bodyLarge.w500
                .colorExt(ColorManager.headerColor.withValues(alpha: (0.5)))
                .size(18),
            labelStyle: context.bodyLarge.w600
                .colorExt(ColorManager.headerColor)
                .size(18),
            tabs: [
              if (ConstantsManager.isAudioRoomsEnabled)
                Text(StringManager.me.tr()),
              if (ConstantsManager.isAudioRoomsEnabled)
                Text(StringManager.popular.tr()),
              // Discover now precedes Live (tabs swapped per owner request).
              Text(StringManager.discover.tr()),
              if (ConstantsManager.isShowLive)
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    Text(StringManager.live.tr()),
                    Positioned(
                      top: -5,
                      right: -10,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 4, vertical: 2),
                        decoration: BoxDecoration(
                          color: ColorManager.primary,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          "New",
                          style: context.bodySmall.w600
                              .colorExt(ColorManager.buttonTextColor)
                              .size(8),
                        ),
                      ),
                    ),
                  ],
                ),
            ],
          ),
        ),
        if (ConstantsManager.isAudioRoomsEnabled)
          BlocBuilder<FetchMyRoomDataBloc, FetchMyRoomDataState>(
            bloc: di<FetchMyRoomDataBloc>(),
            buildWhen: (prev, curr) =>
                prev.requestState != curr.requestState,
            builder: (context, state) {
              return state.requestState == RequestState.loaded
                  ? MultiTapCard(
                      onTap: () async {
                        final utdCtrl = RoomData.instance.utdController;
                        if (utdCtrl != null && utdCtrl.minimize.isMinimizing) {
                          final navContext = SafeNavigator.context;
                          if (navContext == null) return;
                          await di<RoomStateManager>().exitRoom(navContext);
                        }

                        if (ConstantsManager.isShowLive) {
                          if (di<RoomStateManager>().isInRoom) {
                            final navContext = SafeNavigator.context;
                            if (navContext == null) return;
                            await di<RoomStateManager>().exitRoom(
                              navContext,
                              callback: () {
                                GoLiveFlow.showStartDialog(
                                    context, state.rooms);
                              },
                            );
                          } else {
                            GoLiveFlow.showStartDialog(context, state.rooms);
                          }
                        } else {
                          Methods.handleRoomEntry(context, state.rooms?.audio);
                        }
                      },
                      child: Image.asset(
                        AssetsManager.icHome,
                        height: 24.5.h,
                        width: 24.5.w,
                        color: ColorManager.headerColor,
                      ),
                    )
                  : const SizedBox();
            },
          ),
        IconButton(
          onPressed: () {
            Navigator.pushNamed(context, Routes.searchScreen);
          },
          icon: Image.asset(
            AssetsManager.icHomeSearch,
            height: 24.5.h,
            width: 24.5.w,
            color: ColorManager.headerColor,
          ),
        ),
      ],
    );
  }
}
