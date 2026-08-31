part of '../medals_page.dart';

class BottomDialogPickPersonal extends StatefulWidget {
  final List<String> pickedAchievements;
  final List<int> pickedRoomIds;

  const BottomDialogPickPersonal({
    super.key,
    required this.pickedAchievements,
    required this.pickedRoomIds,
  });

  @override
  State<BottomDialogPickPersonal> createState() =>
      _BottomDialogPickPersonalState();
}

class _BottomDialogPickPersonalState extends State<BottomDialogPickPersonal>
    with TickerProviderStateMixin {
  final selectionBloc = di<SelectionBloc>();
  late final TabController _controller;

  @override
  void initState() {
    super.initState();
    _controller = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      width: MediaQuery.of(context).size.width,
      height: 450.h,
      decoration: BoxDecoration(
        color: ColorManager.white,
        borderRadius: BorderRadius.only(
          topLeft: 20.radiusCircular,
          topRight: 20.radiusCircular,
        ),
      ),
      child: Column(
        children: [
          MedalsTabBar(
            controller: _controller,
            titles: [
              StringManager.achievementBadge.tr(),
              StringManager.specialBadge.tr(),
            ],
          ),
          10.hBox,
          Expanded(
            child: Stack(
              alignment: Alignment.center,
              children: [
                BlocBuilder<GetBadgesBloc, GetBadgesStates>(
                  bloc: di<GetBadgesBloc>(),
                  buildWhen: (prev, curr) =>
                      prev.myAllBadgeState != curr.myAllBadgeState ||
                      prev.myAllBadge != curr.myAllBadge,
                  builder: (context, state) {
                    List<ImageData> recharge_ = [];
                    List<ImageData> activity_ = [];
                    List<ImageData> data_ = [];
                    if (state.myAllBadgeState.isLoaded) {
                      for (int i = 0; i < state.myAllBadge.length; i++) {
                        if (state.myAllBadge[i].type == 'recharge_target') {
                          recharge_.add(state.myAllBadge[i]);
                        } else if (state.myAllBadge[i].type ==
                            'no achievement') {
                          activity_.add(state.myAllBadge[i]);
                        }
                      }
                      data_ = state.myAllBadge
                          .where((a) =>
                              a.type == 'recharge_target' ||
                              a.type == 'no achievement')
                          .toList();

                      final initialSelectedIds = data_
                          .where((badge) => widget.pickedAchievements
                              .contains(badge.id.toString()))
                          .map((badge) => badge.id)
                          .toList();

                      selectionBloc.add(const ClearSelection());
                      for (final id in initialSelectedIds) {
                        selectionBloc
                            .add(SelectBadge(badgeId: id, context: context));
                      }
                    }
                    return SizedBox(
                      height: 360.h,
                      child: TabBarView(
                        controller: _controller,
                        children: [
                          GridView.builder(
                            padding: EdgeInsets.only(
                                right: 10.w, left: 10, bottom: 70.h),
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 4,
                              mainAxisSpacing: 5,
                              crossAxisSpacing: 5,
                            ),
                            itemCount: recharge_.length,
                            itemBuilder: (context, index) {
                              return InkWell(
                                onTap: () {
                                  selectionBloc.add(SelectBadge(
                                    badgeId: recharge_[index].id,
                                    context: context,
                                  ));
                                },
                                child:
                                    BlocBuilder<SelectionBloc, SelectionState>(
                                  bloc: selectionBloc,
                                  buildWhen: (prev, curr) =>
                                      prev.selectedIds != curr.selectedIds,
                                  builder: (context, selectionState) {
                                    final isSelected = selectionState
                                        .selectedIds
                                        .contains(recharge_[index].id);
                                    return Container(
                                      padding: context.paddingAll(15.0),
                                      decoration: BoxDecoration(
                                        borderRadius: 15.radius,
                                        color: isSelected
                                            ? ColorManager.primary
                                            : ColorManager.transparent,
                                        border: Border.all(
                                            color: ColorManager.white),
                                      ),
                                      child: recharge_[index]
                                                  .image
                                                  .contains(".svga") ||
                                              recharge_[index]
                                                  .image
                                                  .contains(".zz") ||
                                              recharge_[index]
                                                  .image
                                                  .contains(".zzz")
                                          ? CacheSvgaWidget(
                                              url: recharge_[index].image,
                                              height: 50,
                                              width: 50,
                                              radius: 15,
                                            )
                                          : ImageViewWidget(
                                              url: recharge_[index].image,
                                              height: 50,
                                              width: 50,
                                              radius: 15,
                                            ),
                                    );
                                  },
                                ),
                              );
                            },
                          ),
                          GridView.builder(
                            padding: EdgeInsets.only(
                                right: 10.w, left: 10, bottom: 70.h),
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 4,
                              mainAxisSpacing: 5,
                              crossAxisSpacing: 5,
                            ),
                            itemCount: activity_.length,
                            itemBuilder: (context, index) {
                              return InkWell(
                                onTap: () {
                                  selectionBloc.add(SelectBadge(
                                    badgeId: activity_[index].id,
                                    context: context,
                                  ));
                                },
                                child:
                                    BlocBuilder<SelectionBloc, SelectionState>(
                                  bloc: selectionBloc,
                                  buildWhen: (prev, curr) =>
                                      prev.selectedIds != curr.selectedIds,
                                  builder: (context, selectionState) {
                                    final isSelected = selectionState
                                        .selectedIds
                                        .contains(activity_[index].id);
                                    return Container(
                                      padding: context.paddingAll(15.0),
                                      decoration: BoxDecoration(
                                        borderRadius: 15.radius,
                                        color: isSelected
                                            ? ColorManager.primary
                                            : ColorManager.transparent,
                                        border: Border.all(
                                            color: ColorManager.white),
                                      ),
                                      child: activity_[index]
                                                  .image
                                                  .contains(".svga") ||
                                              activity_[index]
                                                  .image
                                                  .contains(".zz") ||
                                              activity_[index]
                                                  .image
                                                  .contains(".zzz")
                                          ? CacheSvgaWidget(
                                              url: activity_[index].image,
                                              height: 40,
                                              width: 40,
                                              radius: 15,
                                            )
                                          : ImageViewWidget(
                                              url: activity_[index].image,
                                              height: 40,
                                              width: 40,
                                              radius: 15,
                                            ),
                                    );
                                  },
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                    );
                  },
                ),
                Positioned(
                  bottom: 5,
                  left: 1,
                  right: 1,
                  child: _BadgesBodybottomDialog(
                    index: 0,
                    pickedRoomIds: widget.pickedRoomIds,
                  ),
                ),
              ],
            ),
          )
        ],
      ),
    );
  }
}
