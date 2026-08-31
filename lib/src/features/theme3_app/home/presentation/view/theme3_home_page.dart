import 'package:general/src/features/home/home.dart';
import 'package:general/src/features/home/presentation/home/bloc/home_manager/home_bloc.dart';
import 'package:general/src/features/home/presentation/home/bloc/fetch_my_room_data_manager/fetch_my_room_data_bloc.dart';
import 'package:general/src/features/home/presentation/home/bloc/fetch_my_room_data_manager/fetch_my_room_data_event.dart';
import 'package:general/src/features/home/presentation/home/bloc/fetch_top_ranking_manager/fetch_top_ranking_bloc.dart';
import 'package:general/src/features/home/presentation/home/bloc/get_carousel_manager/get_carousel_bloc.dart';
import 'package:general/src/features/home/presentation/daily_prize/bloc/daily_prizes_bloc.dart';
import 'package:general/src/features/games/games.dart';
import 'package:general/src/features/auth/presentation/country/bloc/countries_bloc.dart';
import 'package:general/src/features/theme3_app/home/presentation/view/widgets/theme3_header_bar.dart';
import 'package:general/src/features/theme3_app/home/presentation/view/widgets/theme3_filter_chips.dart';
import 'package:general/src/features/theme3_app/home/presentation/view/widgets/theme3_related_body.dart';
import 'package:general/src/features/theme3_app/home/presentation/view/widgets/theme3_trending_body.dart';
import 'package:general/src/features/theme3_app/home/presentation/view/widgets/theme3_lives_body.dart';
import 'package:general/src/features/theme3_app/home/presentation/view/widgets/theme3_discover_body.dart';

/// Theme3 (NEXO) Home Page — app name + search/bell header, horizontally
/// scrollable filter chips (Related/Popular/[Live]/Discover), 2-column room
/// grid per chip with a "POPULAR EVENTS" banner slot on the Popular chip.
/// Bloc wiring (init/dispose/tab-listener sequencing) is copied unchanged
/// from [Theme2HomePage] — only the chrome (chips instead of a TabBar, no
/// FAB) and card visuals differ.
class Theme3HomePage extends StatefulWidget {
  const Theme3HomePage({super.key});

  @override
  State<Theme3HomePage> createState() => _Theme3HomePageState();
}

class _Theme3HomePageState extends State<Theme3HomePage>
    with TickerProviderStateMixin {
  late final TabController _parentTabController;
  final _bloc = di<HomeBloc>();

  @override
  void initState() {
    super.initState();
    // Honor the panel's default screen: open on the Live chip (index 2, present
    // only when live is enabled) when default_screen == 'live'; otherwise
    // Popular (index 1) stays the default.
    final bool startOnLive =
        ConstantsManager.homeScreen == 'live' && ConstantsManager.isShowLive;
    _parentTabController = TabController(
      length: ConstantsManager.isShowLive ? 4 : 3,
      vsync: this,
      initialIndex: startOnLive ? 2 : 1,
    );

    // Phase 1: Critical data
    if (!_bloc.state.reqStatePopular.isLoaded &&
        ConstantsManager.isAudioRoomsEnabled) {
      _bloc.add(const FetchPopularRoomsEvent(isPopularLoading: true));
    }

    if (!di<GetCarouselBloc>().state.reqStateHomeTop.isLoaded) {
      di<GetCarouselBloc>().add(const GetHomeTopCarouselEvent());
    }

    if (ConstantsManager.isAudioRoomsEnabled) {
      _bloc.add(const PopularAddListenerEvent());
    }

    // The tab-change listener only fires on later switches, so when we start
    // ON the Live chip its data must be fetched here or it renders empty.
    if (startOnLive) {
      if (!_bloc.state.reqStateLive.isLoaded) {
        _bloc.add(const FetchLiveRoomsEvent());
      }
      _bloc.add(const LiveAddListenerEvent());
    }

    // Phase 2: Secondary data
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!di<FetchMyRoomDataBloc>().state.requestState.isLoaded) {
        di<FetchMyRoomDataBloc>().add(const FetchMyRoomDataEvent());
      }
      if (!di<CountriesBloc>().state.categoriesRequestState.isLoaded) {
        di<CountriesBloc>().add(const FetchCountryCategoriesEvent());
      }
      if (!di<DailyPrizesBloc>().state.requestStateGetPrize.isLoaded) {
        di<DailyPrizesBloc>().add(GetDailyPrizesEvent(context: context));
      }

      Future.delayed(const Duration(milliseconds: 500), () {
        if (!di<GetCarouselBloc>().state.reqStateHomeMiddle.isLoaded) {
          di<GetCarouselBloc>().add(const GetHomeMiddleCarouselEvent());
        }
        if (!di<FetchTopUserImageBloc>().state.requestState.isLoaded) {
          di<FetchTopUserImageBloc>().add(const FetchTopUserImageEvent());
        }
      });
    });

    _parentTabController.addListener(_tabListener);
  }

  void _tabListener() {
    // Live is index 2 (only when isShowLive); Discover is always last.
    const int liveIndex = 2;
    final int discoverIndex = ConstantsManager.isShowLive ? 3 : 2;
    final int index = _parentTabController.index;

    if (index == 0) {
      // Related chip
      if (!_bloc.state.reqStateFollow.isLoaded) {
        _bloc.add(const FetchFollowRoomsEvent());
        _bloc.add(const FollowAddListenerEvent());
      }
    } else if (index == discoverIndex) {
      // Discover chip — games + discover banners only.
      if (!di<GetCarouselBloc>().state.discoverState.isLoaded) {
        di<GetCarouselBloc>().add(const GetDiscoverCarouselEvent());
      }
      if (!di<ExploreBloc>().state.outerReqStateGames.isLoaded) {
        di<ExploreBloc>().add(const FetchGamesEvent(type: 'outer'));
      }
    } else if (ConstantsManager.isShowLive && index == liveIndex) {
      // Live chip (only present when ConstantsManager.isShowLive)
      if (!_bloc.state.reqStateLive.isLoaded) {
        _bloc.add(const FetchLiveRoomsEvent());
      }
      _bloc.add(const LiveAddListenerEvent());
    }
  }

  @override
  void dispose() {
    _parentTabController.removeListener(_tabListener);
    _parentTabController.dispose();
    if (ConstantsManager.isAudioRoomsEnabled) {
      _bloc.add(const PopularRemoveListenerEvent());
    }
    _bloc.add(const FollowRemoveListenerEvent());
    _bloc.add(const GlobalRemoveListenerEvent());
    if (ConstantsManager.isShowLive) {
      _bloc.add(const LiveRemoveListenerEvent());
    }
    super.dispose();
  }

  List<String> get _chipLabels => [
        StringManager.theme2Related.tr(),
        StringManager.theme2Trending.tr(),
        if (ConstantsManager.isShowLive) StringManager.live.tr(),
        StringManager.discover.tr(),
      ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorManager.theme3Background,
      // Games entry FAB — feature parity with the default/theme_1/theme_2
      // homes (owner rule: nothing exists in one theme and not the others).
      // Same gate (isGameAvailable) and same destination (Routes.gamesPage),
      // styled with the NEXO pink CTA gradient.
      floatingActionButton: BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
        bloc: di<FetchUserDataBloc>(),
        buildWhen: (prev, curr) =>
            prev.userEntity?.isGameAvailable !=
            curr.userEntity?.isGameAvailable,
        builder: (context, state) {
          if (state.userEntity?.isGameAvailable == true) {
            return GestureDetector(
              onTap: () {
                context.pushNamedRoute(Routes.gamesPage);
              },
              child: Container(
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: LinearGradient(
                    colors: ColorManager.theme3CtaGradient,
                    begin: AlignmentDirectional.topStart,
                    end: AlignmentDirectional.bottomEnd,
                  ),
                ),
                child: CircleAvatar(
                  backgroundColor: ColorManager.transparent,
                  radius: 30.r,
                  child: Image.asset(
                    AssetsManager.gameIconHome,
                    height: 38,
                    width: 38,
                    fit: BoxFit.fill,
                    color: ColorManager.white,
                  ),
                ),
              ),
            );
          } else {
            return const SizedBox.shrink();
          }
        },
      ),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            const Theme3HeaderBar(),
            10.hBox,
            Theme3FilterChips(
              controller: _parentTabController,
              labels: _chipLabels,
            ),
            10.hBox,
            Expanded(
              child: TabBarView(
                controller: _parentTabController,
                children: [
                  // Related
                  Theme3RelatedBody(bloc: _bloc),
                  // Popular (default)
                  Theme3TrendingBody(bloc: _bloc),
                  // Live (only when enabled; swapped before Discover)
                  if (ConstantsManager.isShowLive)
                    Theme3LivesBody(bloc: _bloc),
                  // Discover
                  Theme3DiscoverBody(bloc: _bloc),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}