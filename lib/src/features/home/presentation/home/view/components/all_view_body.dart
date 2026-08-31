part of 'package:general/src/features/home/presentation/home/view/home_page.dart';

class _AllViewBody extends StatefulWidget {
  const _AllViewBody({required this.bloc});

  final HomeBloc bloc;

  @override
  State<_AllViewBody> createState() => _AllViewBodyState();
}

class _AllViewBodyState extends State<_AllViewBody>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  // Cache grid delegates
  static const _gridDelegate = SliverGridDelegateWithFixedCrossAxisCount(
    crossAxisCount: 2,
    mainAxisSpacing: 12,
    crossAxisSpacing: 12,
    childAspectRatio: 0.9,
  );

  static const _listGridDelegate = SliverGridDelegateWithFixedCrossAxisCount(
    crossAxisCount: 2,
    mainAxisSpacing: 5,
    crossAxisSpacing: 5,
    childAspectRatio: 0.9,
  );

  @override
  Widget build(BuildContext context) {
    super.build(context); // Required for AutomaticKeepAliveClientMixin

    // Country selection drives an in-place filter: when a country is selected
    // the body shows the filtered rooms + country banners, otherwise it shows
    // the popular rooms + home banners (same layout, switched data source).
    return BlocBuilder<CountriesBloc, CountriesState>(
      bloc: di<CountriesBloc>(),
      buildWhen: (prev, curr) => prev.countryEntity != curr.countryEntity,
      builder: (context, countryState) {
        final hasCountryFilter = countryState.countryEntity != null;
        return _buildBody(context, hasCountryFilter, countryState);
      },
    );
  }

  Widget _buildBody(
    BuildContext context,
    bool hasCountryFilter,
    CountriesState countryState,
  ) {
    return BlocBuilder<HomeBloc, HomeState>(
      bloc: widget.bloc,
      buildWhen: (previous, current) =>
          previous.popular != current.popular ||
          previous.reqStatePopular != current.reqStatePopular ||
          previous.isPaginatingPopular != current.isPaginatingPopular ||
          previous.filteredRooms != current.filteredRooms ||
          previous.reqStateFilter != current.reqStateFilter ||
          previous.isPaginatingFilter != current.isPaginatingFilter,
      builder: (context, homeState) {
        final popular =
            hasCountryFilter ? homeState.filteredRooms : homeState.popular;
        final isPaginating = hasCountryFilter
            ? homeState.isPaginatingFilter
            : homeState.isPaginatingPopular;
        final scrollCtrl = hasCountryFilter
            ? homeState.filterScrollCtrl
            : homeState.popularScrollCtrl;
        final middleCarouselSource =
            hasCountryFilter ? 'country' : 'homeMiddle';
        final total = popular.length;

        // Pre-calculate top rooms count
        final topCount = ConstantsManager.isShowGridView ? 4 : 3;
        final topThreeRooms =
            total <= topCount ? popular : popular.take(topCount).toList();

        // Calculate item count for the rooms list (rooms after top 3 + 1 for carousel)
        final roomsItemCount = total <= topCount ? 0 : (total - topCount);

        return RefreshIndicatorWidget(
          onRefresh: () async {
            if (hasCountryFilter) {
              final countryId = countryState.countryEntity?.id;
              widget.bloc.add(FilterPopularRoomsEvent(
                isLoading: false,
                isFirstPage: true,
                countryId: countryId,
              ));
              di<GetCarouselBloc>().add(GetCountryCarouselEvent(
                isLoading: false,
                countryId: '$countryId',
              ));
              return;
            }
            di<CountriesBloc>()
                .add(const FetchCountryCategoriesEvent());
            di<GetCarouselBloc>()
                .add(const GetHomeTopCarouselEvent(isLoading: false));
            di<GetCarouselBloc>()
                .add(const GetHomeMiddleCarouselEvent(isLoading: false));
            di<CountriesBloc>().add(const UnSelectedCountryHotEvent());
            widget.bloc.add(
              const FetchPopularRoomsEvent(
                isPopularLoading: true,
                isFirstPage: true,
              ),
            );
          },
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            controller: scrollCtrl,
            slivers: [
              SliverToBoxAdapter(
                child: 3.hBox,
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: context.paddingSymmetric(horizontal: 15),
                  child: const CarouselWidget(
                    type: "event",
                    source: 'homeTop',
                  ),
                ),
              ),
              const SliverToBoxAdapter(
                child: SRRCarouselWidget(),
              ),

              ConstantsManager.isShowGridView
                  ? BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
                      bloc: di<FetchUserDataBloc>(),
                      buildWhen: (previous, current) =>
                          previous.reqState != current.reqState,
                      builder: (context, userState) {
                        final isLoaded =
                            userState.reqState == RequestState.loaded;
                        return SliverPadding(
                          padding: context.paddingOnly(
                            bottom: 10.h,
                            end: 8.w,
                            top: 10.h,
                            start: 8.w,
                          ),
                          sliver: SliverGrid.builder(
                            itemCount: topThreeRooms.length,
                            gridDelegate: _gridDelegate,
                            itemBuilder: (context, index) => RepaintBoundary(
                              child: MultiTapCard(
                                onTap: () {
                                  if (isLoaded) {
                                    di<RoomStateManager>().navigateToRoom(
                                      RoomEntryRequest(
                                        context: context,
                                        roomData: topThreeRooms[index],
                                        isLive: false,
                                      ),
                                    );
                                  }
                                },
                                child: NewRoomCardWidget(
                                  roomEntity: topThreeRooms[index],
                                  isShowSVGA: true,
                                ),
                              ),
                            ),
                          ),
                        );
                      },
                    )
                  : SliverToBoxAdapter(
                      child: TopThreeRoomBody(rooms: topThreeRooms),
                    ),
              if (Platform.isIOS) SliverToBoxAdapter(child: 10.hBox),
              SliverAppBar(
                toolbarHeight: 30.h,
                collapsedHeight: 35.h,
                expandedHeight: 30.h,
                backgroundColor: ColorManager.transparent,
                flexibleSpace: const FlexibleSpaceBar(
                  background: CountriesBody(),
                ),
                pinned: false,
                floating: false,
                primary: false,
              ),
              SliverToBoxAdapter(
                child: 10.hBox,
              ),
              // Middle carousel - always shown
              SliverToBoxAdapter(
                child: Padding(
                  padding: context.paddingSymmetric(horizontal: 15),
                  child: CarouselWidget(
                    type: "noEvent",
                    source: middleCarouselSource,
                  ),
                ),
              ),
              SliverToBoxAdapter(
                child: 10.hBox,
              ),
              // Use SliverList instead of ListView inside SliverToBoxAdapter for better performance
              if (roomsItemCount > 0)
                ConstantsManager.isShowGridView
                    ? BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
                        bloc: di<FetchUserDataBloc>(),
                        buildWhen: (previous, current) =>
                            previous.reqState != current.reqState,
                        builder: (context, userState) {
                          final isLoaded =
                              userState.reqState == RequestState.loaded;
                          return SliverPadding(
                            padding: context.paddingOnly(
                              bottom: 70.h,
                              end: 10.w,
                              start: 10.w,
                            ),
                            sliver: SliverGrid.builder(
                              itemCount: roomsItemCount,
                              gridDelegate: _listGridDelegate,
                              itemBuilder: (context, index) {
                                final roomIndex = index + topCount;
                                if (roomIndex >= total) {
                                  return const SizedBox.shrink();
                                }

                                final room = popular[roomIndex];
                                return RepaintBoundary(
                                  child: MultiTapCard(
                                    onTap: () {
                                      if (isLoaded) {
                                        di<RoomStateManager>().navigateToRoom(
                                          RoomEntryRequest(
                                            context: context,
                                            roomData: room,
                                            isLive: false,
                                          ),
                                        );
                                      }
                                    },
                                    child: NewRoomCardWidget(
                                      roomEntity: room,
                                      isShowSVGA: false,
                                    ),
                                  ),
                                );
                              },
                            ),
                          );
                        },
                      )
                    : BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
                        bloc: di<FetchUserDataBloc>(),
                        buildWhen: (previous, current) =>
                            previous.reqState != current.reqState,
                        builder: (context, userState) {
                          final isLoaded =
                              userState.reqState == RequestState.loaded;
                          return SliverPadding(
                            padding: context.paddingOnly(bottom: 70),
                            sliver: SliverList.separated(
                              itemCount: roomsItemCount,
                              separatorBuilder: (context, index) => 10.hBox,
                              itemBuilder: (context, index) {
                                final roomIndex = index + topCount;
                                if (roomIndex >= total) {
                                  return const SizedBox.shrink();
                                }

                                final room = popular[roomIndex];
                                return RepaintBoundary(
                                  child: MultiTapCard(
                                    onTap: () {
                                      if (isLoaded) {
                                        di<RoomStateManager>().navigateToRoom(
                                          RoomEntryRequest(
                                            context: context,
                                            roomData: room,
                                            isLive: room.streamType == "live",
                                          ),
                                        );
                                      }
                                    },
                                    child: CardLiveWidget(
                                      roomEntity: room,
                                      index: roomIndex,
                                    ),
                                  ),
                                );
                              },
                            ),
                          );
                        },
                      )
              else
                SliverPadding(
                  padding: context.paddingOnly(bottom: 70),
                  sliver: const SliverToBoxAdapter(child: SizedBox.shrink()),
                ),
              if (isPaginating)
                const SliverToBoxAdapter(
                  child: CircleLoadingWidget(),
                ),
            ],
          ),
        );
      },
    );
  }
}
