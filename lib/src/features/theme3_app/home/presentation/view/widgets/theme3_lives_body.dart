import 'package:general/src/core/index.dart';
import 'package:general/src/features/home/domain/entities/room_entity.dart';
import 'package:general/src/features/home/presentation/home/bloc/home_manager/home_bloc.dart';
import 'package:general/src/features/home/presentation/home/bloc/get_carousel_manager/get_carousel_bloc.dart';
import 'package:general/src/features/home/presentation/home/view/widgets/carousel_widget.dart';
import 'package:general/src/features/theme3_app/home/presentation/view/components/theme3_room_card.dart';

/// Theme3 (NEXO) "Live" chip body — currently-open live rooms. Bound to the
/// same [HomeBloc] live-rooms slice as [Theme2LivesBody]; unlike the other
/// Theme3 chips, tapping a card here ENTERS the live room via
/// [RoomStateManager.navigateToRoom] with `isLive: true`. Also the body reused
/// by the standalone [Theme3LivesPage] so both surfaces show identical
/// content — including the LIVE-placement admin banner via [CarouselWidget].
class Theme3LivesBody extends StatefulWidget {
  final HomeBloc bloc;
  const Theme3LivesBody({super.key, required this.bloc});

  @override
  State<Theme3LivesBody> createState() => _Theme3LivesBodyState();
}

class _Theme3LivesBodyState extends State<Theme3LivesBody>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    // Admin banners assigned to the LIVE placement never rendered here — the
    // tab showed only the rooms grid, so panel "live" banners silently no-oped.
    if (!di<GetCarouselBloc>().state.liveState.isLoaded) {
      di<GetCarouselBloc>().add(const GetLiveCarouselEvent());
    }
  }

  static final _gridDelegate = SliverGridDelegateWithFixedCrossAxisCount(
    crossAxisCount: 2,
    mainAxisSpacing: 10.h,
    crossAxisSpacing: 10.w,
    childAspectRatio: 0.75,
  );

  void _enterLive(BuildContext context, RoomEntity room) {
    if (!di<FetchUserDataBloc>().state.reqState.isLoaded) return;
    di<RoomStateManager>().navigateToRoom(
      RoomEntryRequest(
        context: context,
        roomData: room,
        isLive: true,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final bloc = widget.bloc;

    return BlocBuilder<HomeBloc, HomeState>(
      bloc: bloc,
      buildWhen: (prev, curr) =>
          prev.stream != curr.stream ||
          prev.reqStateLive != curr.reqStateLive ||
          prev.isPaginatingLive != curr.isPaginatingLive,
      builder: (context, state) {
        final rooms = state.stream;
        final count = rooms.length;

        return RefreshIndicatorWidget(
          onRefresh: () async {
            bloc.add(const FetchLiveRoomsEvent(
                isLiveLoading: false, isFirstPage: true));
          },
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            controller: state.streamScrollCtrl,
            cacheExtent: 250,
            slivers: [
              // Admin banners targeted at the LIVE placement (panel display_at
              // = live). Renders nothing when the list is empty.
              SliverToBoxAdapter(
                child: Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: const CarouselWidget(
                    type: "noEvent",
                    source: 'live',
                    isNeedLoadingWidget: false,
                  ),
                ),
              ),

              if (state.reqStateLive.isLoading || state.reqStateLive.isError)
                SliverToBoxAdapter(
                  child: Padding(
                    padding: EdgeInsets.symmetric(horizontal: 10.w),
                    child: HandlingDataWidget(
                      reqState: state.reqStateLive,
                      title: StringManager.noRooms.tr(),
                      subTitle: StringManager.noRoomsMsg.tr(),
                      onTap: () =>
                          bloc.add(const FetchLiveRoomsEvent(isFirstPage: true)),
                      childEmpty: const SizedBox(),
                      child: const SizedBox.shrink(),
                    ),
                  ),
                ),

              if (count == 0 && state.reqStateLive.isLoaded)
                SliverToBoxAdapter(
                  child: Padding(
                    padding: EdgeInsets.symmetric(
                        horizontal: 24.w, vertical: 60.h),
                    child: Column(
                      children: [
                        Icon(Icons.podcasts,
                            size: 56.sp, color: ColorManager.theme3Cta),
                        18.hBox,
                        Text(
                          StringManager.noRooms.tr(),
                          style: TextStyle(
                            color: ColorManager.theme3TextPrimary,
                            fontWeight: FontWeight.w700,
                            fontSize: 17.sp,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        8.hBox,
                        Text(
                          StringManager.noRoomsMsg.tr(),
                          style: TextStyle(
                            color: ColorManager.theme3TextSecondary,
                            fontSize: 13.sp,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                ),

              if (count > 0 && state.reqStateLive.isLoaded)
                SliverPadding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  sliver: SliverGrid.builder(
                    itemCount: count,
                    gridDelegate: _gridDelegate,
                    itemBuilder: (context, index) {
                      final room = rooms[index];
                      return RepaintBoundary(
                        child: Theme3RoomCard(
                          key: ValueKey('theme3_live_${room.id}'),
                          roomEntity: room,
                          onTap: () => _enterLive(context, room),
                        ),
                      );
                    },
                  ),
                ),

              if (state.isPaginatingLive)
                const SliverToBoxAdapter(child: CircleLoadingWidget()),

              SliverToBoxAdapter(child: 80.hBox),
            ],
          ),
        );
      },
    );
  }
}