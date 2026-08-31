part of '../medals_page.dart';

class BottomDialogPickRoom extends StatefulWidget {
  final List<String> pickedAchievements;
  final List<int> pickedPersonalIds;
  const BottomDialogPickRoom({
    super.key,
    required this.pickedAchievements,
    required this.pickedPersonalIds,
  });

  @override
  State<BottomDialogPickRoom> createState() => _BottomDialogPickRoomState();
}

class _BottomDialogPickRoomState extends State<BottomDialogPickRoom>
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
          10.hBox,
          MedalsTabBar(
            controller: _controller,
            titles: [
              StringManager.roomBadge.tr(),
              StringManager.giftBadge.tr(),
            ],
          ),
          10.hBox,
          BlocBuilder<GetBadgesBloc, GetBadgesStates>(
            bloc: di<GetBadgesBloc>(),
            buildWhen: (prev, curr) => prev.myAllBadgeState != curr.myAllBadgeState || prev.myAllBadge != curr.myAllBadge,
            builder: (context, state) {
              List<ImageData> data_ = [];
              if (state.myAllBadgeState.isLoaded) {
                data_ = state.myAllBadge
                    .where((a) => a.type == 'room_target')
                    .toList();

                final initialSelectedIds = data_
                    .where((badge) =>
                        widget.pickedAchievements.contains(badge.id.toString()))
                    .map((badge) => badge.id)
                    .toList();

                selectionBloc.add(const ClearSelection());
                for (final id in initialSelectedIds) {
                  selectionBloc.add(SelectBadge(badgeId: id, context: context));
                }
              }
              return HandlingDataWidget(
                reqState: state.myAllBadgeState,
                title: StringManager.noAchievements.tr(),
                subTitle: StringManager.noAchievementSubtitle.tr(),
                child: Expanded(
                  child: TabBarView(
                    controller: _controller,
                    children: [
                      GridView.builder(
                        padding:
                            EdgeInsets.symmetric(horizontal: 10.w, vertical: 5),
                        gridDelegate:
                            const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 4,
                          mainAxisSpacing: 5,
                          crossAxisSpacing: 5,
                        ),
                        itemCount: data_.length,
                        itemBuilder: (context, index) {
                          return InkWell(
                            onTap: () {
                              selectionBloc.add(SelectBadge(
                                badgeId: data_[index].id,
                                context: context,
                              ));
                            },
                            child: BlocBuilder<SelectionBloc, SelectionState>(
                              bloc: selectionBloc,
                              buildWhen: (prev, curr) => prev.selectedIds != curr.selectedIds,
                              builder: (context, selectionState) {
                                final isSelected = selectionState.selectedIds
                                    .contains(data_[index].id);
                                return Container(
                                  padding: context.paddingAll(15.0),
                                  decoration: BoxDecoration(
                                    borderRadius: 15.radius,
                                    color: isSelected
                                        ? ColorManager.primary
                                        : ColorManager.transparent,
                                    border:
                                        Border.all(color: ColorManager.white),
                                  ),
                                  child: ImageViewWidget(
                                    url: data_[index].image,
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
                      const SizedBox(),
                    ],
                  ),
                ),
              );
            },
          ),
          10.hBox,
          _BadgesBodybottomDialog(
            index: 1,
            pickedRoomIds: widget.pickedPersonalIds,
          ),
          10.hBox,
        ],
      ),
    );
  }
}
