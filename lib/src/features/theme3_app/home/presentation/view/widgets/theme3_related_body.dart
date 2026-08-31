import 'package:general/src/core/index.dart';
import 'package:general/src/features/home/presentation/home/bloc/home_manager/home_bloc.dart';
import 'package:general/src/features/theme3_app/home/presentation/view/components/theme3_room_card.dart';

/// Theme3 (NEXO) "Related" chip body — the followed-rooms grid. Phase 1
/// keeps this to a single list (no Theme2-style Joined/Following/Recently
/// sub-tabs) reusing [HomeBloc.state.follow]/`reqStateFollow`, which are
/// already fetched/listened to by [Theme3HomePage]'s init/dispose (copied
/// from Theme2HomePage).
class Theme3RelatedBody extends StatelessWidget {
  final HomeBloc bloc;
  const Theme3RelatedBody({super.key, required this.bloc});

  static final _gridDelegate = SliverGridDelegateWithFixedCrossAxisCount(
    crossAxisCount: 2,
    mainAxisSpacing: 10.h,
    crossAxisSpacing: 10.w,
    childAspectRatio: 0.75,
  );

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<HomeBloc, HomeState>(
      bloc: bloc,
      buildWhen: (prev, curr) =>
          prev.follow != curr.follow || prev.reqStateFollow != curr.reqStateFollow,
      builder: (context, state) {
        final rooms = state.follow;

        if (state.reqStateFollow.isLoading) {
          return const Center(child: LoadingWidget());
        }

        if (rooms.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.nights_stay_outlined,
                  color: ColorManager.theme3TextSecondary,
                  size: 60.h,
                ),
                16.hBox,
                Text(
                  StringManager.theme2Empty.tr(),
                  style: TextStyle(
                    color: ColorManager.theme3TextSecondary,
                    fontSize: 16.sp,
                  ),
                ),
              ],
            ),
          );
        }

        return RefreshIndicatorWidget(
          onRefresh: () async =>
              bloc.add(const FetchFollowRoomsEvent(isFollowLoading: false)),
          child: GridView.builder(
            padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 8.h),
            cacheExtent: 250,
            gridDelegate: _gridDelegate,
            itemCount: rooms.length,
            itemBuilder: (context, index) {
              return RepaintBoundary(
                child: Theme3RoomCard(
                  key: ValueKey('theme3_related_${rooms[index].id}'),
                  roomEntity: rooms[index],
                ),
              );
            },
          ),
        );
      },
    );
  }
}