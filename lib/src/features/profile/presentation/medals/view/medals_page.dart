import 'package:general/src/core/widgets/cache/cache_vap_widget.dart';
import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/bottom_dialog.dart';
import 'package:general/src/core/widgets/show_svga.dart';
import 'package:general/src/features/profile/data/model/badges_model.dart';
import 'package:general/src/features/profile/domain/entities/get_user_badges_entity.dart';
import 'package:general/src/features/profile/presentation/medals/bloc/badge_bloc/badges_bloc.dart';
import 'package:general/src/features/profile/presentation/medals/bloc/pick_badge_bloc/pick_my_badges_bloc.dart';
import 'package:general/src/features/profile/presentation/medals/bloc/pick_badge_bloc/pick_my_badges_event.dart';
import 'package:general/src/features/profile/presentation/medals/bloc/pick_badge_bloc/pick_my_badges_state.dart';
import 'package:general/src/features/profile/presentation/medals/bloc/select_badge_bloc/select_badge_bloc.dart';
import 'package:general/src/features/profile/presentation/medals/bloc/select_badge_bloc/select_badge_event.dart';
import 'package:general/src/features/profile/presentation/medals/bloc/select_badge_bloc/select_badge_state.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_badge/user_badges_bloc.dart';

part 'components/badges_body.dart';

part 'components/badges_body_bottom_dialog.dart';

part 'components/dialog_medal_pick_personal.dart';

part 'components/dialog_medal_pick_room.dart';

part 'components/header_body.dart';

part 'components/medals_tab_bar.dart';

part 'components/tab_bar_dialog.dart';

part 'components/tab_bar_view.dart';

part 'widgets/badge_container_body.dart';

part 'widgets/badge_items.dart';

part 'widgets/container_item_pick.dart';

class MedalsPage extends StatefulWidget {
  const MedalsPage({super.key});

  @override
  State<MedalsPage> createState() => _MedalsPageState();
}

class _MedalsPageState extends State<MedalsPage> with TickerProviderStateMixin {
  final GetBadgesBloc _badgesBloc = di<GetBadgesBloc>();
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    di<GetBadgesBloc>().add(const ChangeMyMedalsAppBarUIEvent(index: 0));

    _tabController = TabController(length: 4, vsync: this);
    _tabController.addListener(_onTabChanged);

    if (!di<GetBadgesBloc>().state.myAllBadgeState.isLoaded) {
      di<GetBadgesBloc>()
          .add(GetMyAllBadges(id: MyDataModel.getInstance().id.toString()));
    }
    if (!di<UserBadgesBloc>().state.myStates.isLoaded) {
      context.read<UserBadgesBloc>().add(const GetMyBadges());
    }
    if (_badgesBloc.state.rechargeBadgeRequest != RequestState.loaded) {
      _badgesBloc.add(const RechargeEvent());
    }
  }

  void _onTabChanged() {
    final index = _tabController.index;
    final wearGroup = index >= 2 ? 1 : 0;
    if (_badgesBloc.state.myMedalsTabBarIndex != wearGroup) {
      _badgesBloc.add(ChangeMyMedalsAppBarUIEvent(index: wearGroup));
    }
    if (index == 1 &&
        _badgesBloc.state.activityBadgeRequest != RequestState.loaded) {
      _badgesBloc.add(const ActivityEvent());
    } else if (index == 2 &&
        _badgesBloc.state.roomBadgeRequest != RequestState.loaded) {
      _badgesBloc.add(const RoomEvent());
    } else if (index == 3 &&
        _badgesBloc.state.giftBadgeRequest != RequestState.loaded) {
      _badgesBloc.add(const GiftEvent());
    }
  }

  @override
  void dispose() {
    _tabController.removeListener(_onTabChanged);
    _tabController.dispose();
    super.dispose();
  }

  // tabs: achievement (1) , special (4) , room (2) , gift (3)
  @override
  Widget build(BuildContext context) {
    return BlocListener<PickMyBadgesBloc, BasePickMyBadgesState>(
      bloc: di<PickMyBadgesBloc>(),
      listener: (context, state) {
        if (state is PickMyBadgesSucssesState) {
          context.read<UserBadgesBloc>().add(
                const GetMyBadges(),
              );
        }
      },
      child: Scaffold(
        body: BlocBuilder<GetBadgesBloc, GetBadgesStates>(
          bloc: _badgesBloc,
          buildWhen: (prev, curr) =>
              prev.myMedalsTabBarIndex != curr.myMedalsTabBarIndex ||
              prev.rechargeBadgeRequest != curr.rechargeBadgeRequest ||
              prev.roomBadgeRequest != curr.roomBadgeRequest ||
              prev.activityBadgeRequest != curr.activityBadgeRequest ||
              prev.giftBadgeRequest != curr.giftBadgeRequest ||
              prev.myAllBadgeState != curr.myAllBadgeState,
          builder: (context, state) {
            return Column(
              children: [
                _HeaderBody(
                  index: state.myMedalsTabBarIndex,
                ),
                Expanded(
                  child: Container(
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: ColorManager.scaffoldBg,
                      borderRadius: BorderRadius.only(
                        topLeft: 25.radiusCircular,
                        topRight: 25.radiusCircular,
                      ),
                    ),
                    child: Column(
                      children: [
                        8.hBox,
                        _MainMedalsTabBar(controller: _tabController),
                        Expanded(
                          child: TabBarView(
                            controller: _tabController,
                            children: [
                              RefreshIndicatorWidget(
                                onRefresh: () async {
                                  _badgesBloc
                                      .add(const RechargeEvent(isLoading: false));
                                },
                                child: MedalsTabBarView(
                                  type: '1',
                                  badges: state.rechargeBadge,
                                  reqState: state.rechargeBadgeRequest,
                                  error: state.rechargeBadgeMessage,
                                ),
                              ),
                              RefreshIndicatorWidget(
                                onRefresh: () async {
                                  _badgesBloc
                                      .add(const ActivityEvent(isLoading: false));
                                },
                                child: MedalsTabBarView(
                                  type: '4',
                                  badges: state.activityBadge,
                                  reqState: state.activityBadgeRequest,
                                  error: state.activityBadgeMessage,
                                ),
                              ),
                              RefreshIndicatorWidget(
                                onRefresh: () async {
                                  _badgesBloc
                                      .add(const RoomEvent(isLoading: false));
                                },
                                child: MedalsTabBarView(
                                  type: '2',
                                  badges: state.roomBadge,
                                  reqState: state.roomBadgeRequest,
                                  error: state.roomBadgeMessage,
                                ),
                              ),
                              RefreshIndicatorWidget(
                                onRefresh: () async {
                                  _badgesBloc
                                      .add(const GiftEvent(isLoading: false));
                                },
                                child: MedalsTabBarView(
                                  type: '3',
                                  badges: state.giftBadge,
                                  reqState: state.giftBadgeRequest,
                                  error: state.giftBadgeMessage,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _MainMedalsTabBar extends StatelessWidget {
  const _MainMedalsTabBar({required this.controller});

  final TabController controller;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 46.h,
      child: TabBar(
        controller: controller,
        isScrollable: true,
        tabAlignment: TabAlignment.center,
        automaticIndicatorColorAdjustment: false,
        dividerHeight: 0,
        indicatorSize: TabBarIndicatorSize.label,
        indicatorWeight: 3,
        indicatorColor: ColorManager.primary,
        labelPadding: context.paddingSymmetric(horizontal: 12),
        padding: EdgeInsets.zero,
        labelStyle: context.bodyMedium.w600.colorExt(ColorManager.primary),
        unselectedLabelStyle:
            context.bodyMedium.w400.colorExt(ColorManager.secondaryText),
        tabs: [
          Tab(text: StringManager.achievementBadge.tr()),
          Tab(text: StringManager.specialBadge.tr()),
          Tab(text: StringManager.roomBadge.tr()),
          Tab(text: StringManager.giftBadge.tr()),
        ],
      ),
    );
  }
}
