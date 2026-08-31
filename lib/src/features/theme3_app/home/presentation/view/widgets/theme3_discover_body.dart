import 'package:general/src/features/home/presentation/home/bloc/home_manager/home_bloc.dart';
import 'package:general/src/features/home/presentation/home/bloc/get_carousel_manager/get_carousel_bloc.dart';
import 'package:general/src/features/home/presentation/home/view/widgets/carousel_widget.dart';
import 'package:general/src/features/games/games.dart';
import 'package:general/src/features/games/presentation/games/view/widgets/games_grid_widget.dart';

/// Theme3 (NEXO) "Discover" chip body — games grid + discover banners.
/// Structurally identical to [Theme2DiscoverBody] (same blocs/events); the
/// only visual change is the section title color.
class Theme3DiscoverBody extends StatelessWidget {
  final HomeBloc bloc;

  const Theme3DiscoverBody({super.key, required this.bloc});

  @override
  Widget build(BuildContext context) {
    return RefreshIndicatorWidget(
      onRefresh: () async {
        di<GetCarouselBloc>().add(
          const GetDiscoverCarouselEvent(isLoading: false),
        );

        di<ExploreBloc>().add(
          const FetchGamesEvent(
            isGamesLoading: false,
            type: 'outer',
          ),
        );
      },
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        controller: bloc.state.globalScrollCtrl,
        cacheExtent: 250,
        slivers: [
          SliverToBoxAdapter(
            child: BlocConsumer<FetchUserDataBloc, FetchUserDataState>(
              bloc: di<FetchUserDataBloc>(),
              listenWhen: (prev, curr) =>
                  prev.userEntity?.isGameAvailable !=
                  curr.userEntity?.isGameAvailable,
              listener: (context, userState) {
                if ((userState.userEntity?.isGameAvailable ?? false) &&
                    !di<ExploreBloc>().state.outerReqStateGames.isLoaded) {
                  di<ExploreBloc>().add(const FetchGamesEvent(type: 'outer'));
                }
              },
              buildWhen: (prev, curr) =>
                  prev.userEntity?.isGameAvailable !=
                  curr.userEntity?.isGameAvailable,
              builder: (context, userState) {
                if (!(userState.userEntity?.isGameAvailable ?? false)) {
                  return const SizedBox.shrink();
                }
                return BlocBuilder<ExploreBloc, ExploreState>(
                  bloc: di<ExploreBloc>(),
                  buildWhen: (prev, curr) =>
                      prev.outerReqStateGames != curr.outerReqStateGames ||
                      prev.outerGames != curr.outerGames,
                  builder: (context, state) {
                    final hasGames =
                        (state.outerGames?.fullGames?.isNotEmpty ?? false);
                    if (!hasGames) return const SizedBox.shrink();
                    return Padding(
                      padding: EdgeInsets.symmetric(
                          horizontal: 16.w, vertical: 5.h),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          GestureDetector(
                            behavior: HitTestBehavior.opaque,
                            onTap: () =>
                                context.pushNamedRoute(Routes.gamesPage),
                            child: Text(
                              StringManager.recommendedGames.tr(),
                              style: TextStyle(
                                color: ColorManager.theme3TextPrimary,
                                fontWeight: FontWeight.bold,
                                fontSize: 15.sp,
                              ),
                            ),
                          ),
                          8.hBox,
                          GamesGridWidget(
                            bloc: di<ExploreBloc>(),
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                          ),
                        ],
                      ),
                    );
                  },
                );
              },
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 5.h),
              child: const DiscoverCarouselList(),
            ),
          ),
          SliverToBoxAdapter(child: 85.hBox),
        ],
      ),
    );
  }
}