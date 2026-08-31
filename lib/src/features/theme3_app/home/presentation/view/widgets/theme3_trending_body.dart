import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/auth.dart';
import 'package:general/src/features/home/presentation/home/bloc/home_manager/home_bloc.dart';
import 'package:general/src/features/home/presentation/home/bloc/get_carousel_manager/get_carousel_bloc.dart';
import 'package:general/src/features/home/presentation/home/view/widgets/carousel_widget.dart';
import 'package:general/src/features/home/presentation/home/view/widgets/top_countries_bar.dart';
import 'package:general/src/features/theme3_app/home/presentation/view/components/theme3_room_card.dart';

/// Theme3 (NEXO) "Popular" chip body — the 2-column room grid with the
/// "POPULAR EVENTS" banner slot on top ([CarouselWidget] bound to
/// [GetCarouselBloc]'s `homeMiddle` source, same as Theme2TrendingBody).
/// Includes the shared [TopCountriesBar] (countries with the most active
/// rooms): tapping a chip switches the grid to the country-filtered rooms,
/// tapping it again (or "Recommended" in the dialog) clears the filter.
class Theme3TrendingBody extends StatelessWidget {
  final HomeBloc bloc;
  const Theme3TrendingBody({super.key, required this.bloc});

  static final _gridDelegate = SliverGridDelegateWithFixedCrossAxisCount(
    crossAxisCount: 2,
    mainAxisSpacing: 10.h,
    crossAxisSpacing: 10.w,
    childAspectRatio: 0.75,
  );

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<CountriesBloc, CountriesState>(
      bloc: di<CountriesBloc>(),
      buildWhen: (prev, curr) => prev.countryEntity != curr.countryEntity,
      builder: (context, countryState) {
        final hasCountryFilter = countryState.countryEntity != null;
        return BlocBuilder<HomeBloc, HomeState>(
          bloc: bloc,
          buildWhen: (prev, curr) => hasCountryFilter
              ? prev.filteredRooms != curr.filteredRooms ||
                  prev.reqStateFilter != curr.reqStateFilter ||
                  prev.isPaginatingFilter != curr.isPaginatingFilter
              : prev.popular != curr.popular ||
                  prev.reqStatePopular != curr.reqStatePopular ||
                  prev.isPaginatingPopular != curr.isPaginatingPopular,
          builder: (context, state) {
            final rooms = hasCountryFilter ? state.filteredRooms : state.popular;
            final reqState =
                hasCountryFilter ? state.reqStateFilter : state.reqStatePopular;
            final isPaginating = hasCountryFilter
                ? state.isPaginatingFilter
                : state.isPaginatingPopular;
            final count = rooms.length;

            return RefreshIndicatorWidget(
              onRefresh: () async {
                if (hasCountryFilter) {
                  final countryId = countryState.countryEntity?.id;
                  bloc.add(FilterPopularRoomsEvent(
                      isLoading: false,
                      countryId: countryId,
                      isFirstPage: true));
                  di<GetCarouselBloc>().add(GetCountryCarouselEvent(
                      isLoading: false, countryId: '$countryId'));
                  return;
                }
                bloc.add(const FetchPopularRoomsEvent(
                    isPopularLoading: false, isFirstPage: true));
                di<GetCarouselBloc>()
                    .add(const GetHomeMiddleCarouselEvent(isLoading: false));
              },
              child: CustomScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                controller: hasCountryFilter
                    ? state.filterScrollCtrl
                    : state.popularScrollCtrl,
                cacheExtent: 250,
                slivers: [
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: EdgeInsets.symmetric(horizontal: 16.w),
                      child: const TopCountriesBar(),
                    ),
                  ),
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: EdgeInsets.symmetric(
                          horizontal: 16.w, vertical: 10.h),
                      child: SizedBox(
                        width: MediaQuery.sizeOf(context).width,
                        child: CarouselWidget(
                          type: "noEvent",
                          source: hasCountryFilter ? 'country' : 'homeMiddle',
                          isNeedLoadingWidget: false,
                        ),
                      ),
                    ),
                  ),

                  if (reqState.isLoading || reqState.isError || count == 0)
                    SliverToBoxAdapter(
                      child: Padding(
                        padding: EdgeInsets.symmetric(horizontal: 10.w),
                        child: HandlingDataWidget(
                          reqState: reqState,
                          title: StringManager.noRooms.tr(),
                          subTitle: StringManager.noRoomsMsg.tr(),
                          onTap: () => hasCountryFilter
                              ? bloc.add(FilterPopularRoomsEvent(
                                  countryId: countryState.countryEntity?.id,
                                  isFirstPage: true))
                              : bloc.add(const FetchPopularRoomsEvent(
                                  isFirstPage: true)),
                          childEmpty: const SizedBox(),
                          child: const SizedBox.shrink(),
                        ),
                      ),
                    ),

                  if (count > 0 && reqState.isLoaded)
                    SliverPadding(
                      padding: EdgeInsets.symmetric(horizontal: 16.w),
                      sliver: SliverGrid.builder(
                        itemCount: count,
                        gridDelegate: _gridDelegate,
                        itemBuilder: (context, index) {
                          final room = rooms[index];
                          return RepaintBoundary(
                            child: Theme3RoomCard(
                              key: ValueKey('theme3_room_${room.id}'),
                              roomEntity: room,
                            ),
                          );
                        },
                      ),
                    ),

                  if (isPaginating)
                    const SliverToBoxAdapter(child: CircleLoadingWidget()),

                  SliverToBoxAdapter(child: 80.hBox),
                ],
              ),
            );
          },
        );
      },
    );
  }
}