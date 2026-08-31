import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/add_or_delete_block/add_or_delete_block_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/add_or_delete_block/add_or_delete_block_event.dart';
import 'package:general/src/features/auth/domain/entities/user_entity.dart';
import 'package:general/src/features/cp/presentation/cp_store/bloc/cp_profile_bloc/cp_profile_bloc.dart';
import 'package:general/src/features/moment/moment.dart';
import 'package:general/src/features/moment/presentation/view/widgets/moment_item.dart';
import 'package:general/src/features/profile/presentation/medals/bloc/badge_bloc/badges_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_badge/user_badges_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_user_badges/get_user_badges_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_user_badges/get_user_badges_event.dart';
import 'package:general/src/features/reels/presentation/main_reels_page/view/play_myreels_view.dart';
import 'package:general/reels_viewer/reels_viewer.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/get_user_intro_bloc/get_user_intro_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/get_user_intro_bloc/get_user_intro_event.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/get_user_rooms/get_user_rooms_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/get_user_rooms/get_user_rooms_event.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/get_user_support/get_user_support_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/get_user_support/get_user_support_event.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/gift_history_bloc/gift_history_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/gift_history_bloc/gift_history_event.dart';
import 'package:general/src/features/profile/presentation/profile/view/widgets/f_f_f_body.dart';
import 'package:general/src/features/messages/presentation/messages/blocs/user_online/user_online_bloc.dart';
import 'package:general/src/features/theme2_app/user_profile/presentation/widgets/theme2_visitor_profile_header.dart';
import 'package:general/src/features/theme2_app/user_profile/presentation/widgets/theme2_visitor_action_bar.dart';
import 'package:general/src/features/theme2_app/user_profile/presentation/widgets/theme2_visitor_relation_tab.dart';
import 'package:general/src/features/theme2_app/user_profile/presentation/widgets/theme2_visitor_honor_tab.dart';

class Theme2VisitorProfilePage extends StatefulWidget {
  final UserEntity? userData;
  final String? userId;
  final bool? comesFromRoom;

  const Theme2VisitorProfilePage({
    super.key,
    this.userData,
    this.userId,
    this.comesFromRoom,
  });

  /// The user id of the profile currently open on screen (empty when none).
  /// Single source of truth used by deep-link handling to avoid re-opening the
  /// same profile. (Relocated here from the retired UserProfileScreen.)
  static String currentUserId = '';

  @override
  State<Theme2VisitorProfilePage> createState() =>
      _Theme2VisitorProfilePageState();
}

class _Theme2VisitorProfilePageState extends State<Theme2VisitorProfilePage>
    with TickerProviderStateMixin {
  bool isMyProfile = true;
  late final TabController _tabController;
  late final GiftHistoryBloc giftHistoryBloc;
  late final FetchUserDataBloc fetchUserDataBloc;
  late final UserBadgesBloc userBadgesBloc;
  late final GetSupporterBloc getSupporterBloc;
  late final GetUserIntroBloc getUserIntroBloc;
  late final MomentBloc momentBloc;
  late final CpProfileBloc cpProfileBloc;
  late final GetBadgesBloc getBadgesBloc;
  late final GetUserRoomsBloc getUserRoomsBloc;
  late final GetUserBadgesBloc getUserBadgesBloc;
  late final GetReelsBloc getReelsBloc;

  /// Page-scoped presence bloc for the visited profile's header. A fresh
  /// instance (NOT di<UserOnlineBloc>(), which the chat header owns) so opening a
  /// peer profile from a chat never repoints/stops the chat's presence watch.
  late final UserOnlineBloc onlineBloc;

  /// Profile user id resolved in initState; used by the lazy per-tab loaders.
  String? _profileUserId;

  /// Guards so each tab's network data is fetched only once on first selection.
  bool _postsTabLoaded = false;
  bool _reelsTabLoaded = false;

  /// Set when the DI lookups in initState failed (container reset race). build()
  /// renders an empty scaffold and the screen pops on the next frame.
  bool _initFailed = false;

  @override
  void initState() {
    super.initState();
    // CR-3 guard: a DI container reset (logout / switch account) mid-navigation
    // makes these di<>() lookups throw GetIt "Object/factory not registered"
    // (throwIfNot Bad state). Wrap them so a half-initialized screen pops safely
    // instead of crashing.
    try {
      giftHistoryBloc = di<GiftHistoryBloc>();
      fetchUserDataBloc = di<FetchUserDataBloc>();
      userBadgesBloc = di<UserBadgesBloc>();
      getUserIntroBloc = di<GetUserIntroBloc>();
      getSupporterBloc = di<GetSupporterBloc>();
      getBadgesBloc = di<GetBadgesBloc>();
      momentBloc = di<MomentBloc>();
      cpProfileBloc = di<CpProfileBloc>();
      getUserRoomsBloc = di<GetUserRoomsBloc>();
      getUserBadgesBloc = di<GetUserBadgesBloc>();
      getReelsBloc = di<GetReelsBloc>();
      onlineBloc = UserOnlineBloc(di());
    } catch (_) {
      _initFailed = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) Navigator.of(context).maybePop();
      });
      // Tab controller still needs to exist for dispose()/build() safety.
      _tabController = TabController(length: 3, vsync: this);
      return;
    }

    // 3 tabs: عام | اللحظات (posts) | الريلز (videos)
    _tabController = TabController(length: 3, vsync: this);
    _tabController.addListener(_onTabChanged);

    if (widget.userId == null) {
      isMyProfile = true;
      final ownId = widget.userData?.id ?? MyDataModel.getInstance().id;
      _profileUserId = (ownId != null && ownId != 0) ? '$ownId' : null;
    } else {
      isMyProfile = false;
      Theme2VisitorProfilePage.currentUserId = widget.userId ?? '';
      _profileUserId = '${widget.userId}';
    }

    // Defer all network dispatch until after the first frame so the page
    // paints (and the route transition runs) before HTTP work starts.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      momentBloc.add(const ResetMyMomentsEvent());

      final id = _profileUserId;
      if (id == null) return;

      if (!isMyProfile) {
        fetchUserDataBloc
            .add(FetchUserEvent(userId: id, isVisit: true));
        getUserRoomsBloc.add(GetUserRooms(id: int.tryParse(id) ?? 0));
        // Keep the header's "آخر ظهور / متصل" label live from the unified
        // presence source (GET /user-status/{id}) via this page's OWN bloc.
        onlineBloc.add(UserOnlineEvent(userId: id));
      }
      // Load all عام tab data immediately (it's the first visible tab).
      _fetchGeneralTabData(id);
    });
  }

  /// Load all data needed for the عام (general) tab + header.
  void _showReportDialog(BuildContext context, String userId) {
    // Open the reporting flow: navigate to the user profile with report intent.
    // The existing UserReportBloc + ReportUserUseCase handle the actual submission.
    Methods().userProfileNavigator(
      context: context,
      userId: userId,
    );
  }

  void _showMoreOptions(BuildContext context, UserEntity? user) {
    final userId = '${user?.id ?? 0}';
    showDialog(
      context: context,
      builder: (_) => AnimatedDialog(
        isHideConfirm: true,
        isUpdateDialog: false,
        titleDivider: false,
        showIcon: false,
        radius: 5,
        horizontalPadding: 50,
        contentPadding: context.paddingZero(),
        child: Column(
          children: [
            InkWell(
              onTap: () {
                Navigator.pop(context);
                _showReportDialog(context, userId);
              },
              child: SizedBox(
                width: double.infinity,
                height: 50.h,
                child: Center(
                  child: TextWidget(
                    StringManager.report.tr(),
                    style: context.bodyMedium.size(16).colorExt(ColorManager.textPrimary),
                  ),
                ),
              ),
            ),
            const Divider(height: 0.5, color: ColorManager.grey2, thickness: 0.5),
            InkWell(
              onTap: () {
                if (user?.hasAntiBan ?? false) {
                  Methods.showToast(context,
                      message: StringManager.userHasAntiBan.tr(), isError: true);
                  return;
                }
                di<AddOrRemoveBlock>().add(AddBlockListEvent(context, userId: userId));
                Navigator.pop(context);
              },
              child: SizedBox(
                width: double.infinity,
                height: 50.h,
                child: Center(
                  child: TextWidget(
                    StringManager.addToBlacklist.tr(),
                    style: context.bodyMedium.size(16).colorExt(ColorManager.textPrimary),
                  ),
                ),
              ),
            ),
            const Divider(height: 0.5, color: ColorManager.grey2, thickness: 0.5),
            InkWell(
              onTap: () => Navigator.pop(context),
              child: SizedBox(
                width: double.infinity,
                height: 50.h,
                child: Center(
                  child: TextWidget(
                    StringManager.cancel.tr(),
                    style: context.bodyMedium.size(16).colorExt(ColorManager.secondaryText),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _fetchGeneralTabData(String id) {
    getUserBadgesBloc.add(GetUserBadgesData(id: int.tryParse(id) ?? 0));
    cpProfileBloc.add(GetCpProfileEvents(userId: id));
    getSupporterBloc.add(GetUserSupporterEvent(userId: id));
    userBadgesBloc.add(GetUserBadges(id: id));
    getBadgesBloc.add(GetMyAllBadges(id: id));
    giftHistoryBloc.add(GetGiftHistory(id: id));
    getUserIntroBloc.add(GetUserIntro(id: id));
  }

  void _onTabChanged() {
    if (_tabController.indexIsChanging) return;
    final id = _profileUserId;
    if (id == null) return;

    switch (_tabController.index) {
      case 1: // اللحظات — Posts/Moments only (lazy load)
        if (!_postsTabLoaded) {
          _postsTabLoaded = true;
          momentBloc.add(FetchMyMomentData(type: '1', userId: id));
        }
        break;
      case 2: // الريلز — Videos/Reels only (lazy load)
        if (!_reelsTabLoaded) {
          _reelsTabLoaded = true;
          getReelsBloc.add(GetMyReels(userId: id));
        }
        break;
    }
  }

  @override
  void dispose() {
    _tabController.removeListener(_onTabChanged);
    _tabController.dispose();
    Theme2VisitorProfilePage.currentUserId = "";
    // Close this page's own presence bloc (cancels its poll); the chat's shared
    // di<UserOnlineBloc>() is untouched, so the chat header stays live.
    if (!_initFailed) {
      onlineBloc.close();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // DI lookups failed in initState (container reset race) — render nothing;
    // the post-frame callback pops the route.
    if (_initFailed) {
      return const Scaffold(backgroundColor: Colors.transparent);
    }
    return BackgroundImgWidget(
      child: Scaffold(
        backgroundColor: ColorManager.transparent,
        body: Container(
          decoration: BoxDecoration(
            gradient: ColorManager.bodyBackgroundGradient,
          ),
          child: BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
          bloc: isMyProfile ? di<FetchUserDataBloc>() : fetchUserDataBloc,
          buildWhen: (prev, curr) =>
              prev.reqState != curr.reqState ||
              prev.reqStateUser != curr.reqStateUser ||
              prev.otherUserEntity != curr.otherUserEntity,
          builder: (context, state) {
            final reqState = isMyProfile ? state.reqState : state.reqStateUser;
            final user = isMyProfile ? widget.userData : state.otherUserEntity;
            final userId = isMyProfile
                ? widget.userData?.id.toString() ?? ''
                : widget.userId ?? '';

            return HandlingDataWidget(
              reqState: reqState,
              title: '',
              subTitle: '',
              child: NestedScrollView(
                headerSliverBuilder: (context, innerBoxIsScrolled) => [
                  SliverToBoxAdapter(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Header: Cover + Avatar + Name + badges + ID
                        Stack(
                          children: [
                            Theme2VisitorProfileHeader(
                              user: user,
                              isMyProfile: isMyProfile,
                              getUserBadgesBloc: getUserBadgesBloc,
                              cpProfileBloc: cpProfileBloc,
                              userOnlineBloc: onlineBloc,
                            ),
                            // Back + More options bar
                            Positioned(
                              top: MediaQuery.of(context).padding.top + 8,
                              left: 8.w,
                              right: 8.w,
                              child: Row(
                                children: [
                                  IconButton(
                                    onPressed: () => Navigator.pop(context),
                                    icon: Icon(Icons.arrow_back_ios,
                                        color: ColorManager.white, size: 20.sp),
                                  ),
                                  const Spacer(),
                                  // After the Spacer so it sits at the far end of
                                  // the bar (far-left in the app's RTL layout), as
                                  // required for #1. edit/more are mutually
                                  // exclusive, so only one ever renders here.
                                  if (isMyProfile)
                                    IconButton(
                                      onPressed: () => Navigator.pushNamed(
                                        context,
                                        Routes.editProfile,
                                        arguments: MyDataModel.getInstance(),
                                      ),
                                      icon: Icon(Icons.edit,
                                          color: ColorManager.white,
                                          size: 22.sp),
                                    ),
                                  if (!isMyProfile && user?.id != null && user!.id != 0)
                                    IconButton(
                                      onPressed: () => _showMoreOptions(context, user),
                                      icon: Icon(Icons.more_horiz,
                                          color: ColorManager.white,
                                          size: 24.sp),
                                    ),
                                ],
                              ),
                            ),
                          ],
                        ),

                        // Stats: Following | Followers | Friends | Visitors
                        Container(
                          decoration: BoxDecoration(
                            gradient: ColorManager.bodyBackgroundGradient,
                          ),
                          padding: EdgeInsets.symmetric(
                              horizontal: 16.w, vertical: 12.h),
                          child: FFLFBody(
                            isMyProfile: isMyProfile,
                            data: user,
                            textColor: ColorManager.textPrimary,
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Sticky Tab Bar
                  SliverPersistentHeader(
                    pinned: true,
                    delegate: _Theme2StickyTabBarDelegate(
                      TabBar(
                        controller: _tabController,
                        indicatorColor: ColorManager.primary,
                        indicatorSize: TabBarIndicatorSize.label,
                        indicatorWeight: 3,
                        labelColor: ColorManager.primary,
                        unselectedLabelColor: ColorManager.secondaryText,
                        dividerHeight: 0,
                        labelStyle: TextStyle(
                          fontSize: 15.sp,
                          fontWeight: FontWeight.w700,
                        ),
                        unselectedLabelStyle: TextStyle(
                          fontSize: 14.sp,
                          fontWeight: FontWeight.w400,
                        ),
                        tabs: const [
                          Tab(text: 'عام'),
                          Tab(text: 'اللحظات'),
                          Tab(text: 'الريلز'),
                        ],
                      ),
                    ),
                  ),
                ],
                body: TabBarView(
                  controller: _tabController,
                  children: [
                    // تاب عام — bio + كل المعلومات العامة
                    _Theme2GeneralTab(
                      user: user,
                      userId: userId,
                      getSupporterBloc: getSupporterBloc,
                      cpProfileBloc: cpProfileBloc,
                      userBadgesBloc: userBadgesBloc,
                      getBadgesBloc: getBadgesBloc,
                      getUserIntroBloc: getUserIntroBloc,
                      giftHistoryBloc: giftHistoryBloc,
                    ),
                    // تاب اللحظات — المنشورات فقط
                    _Theme2MomentsTab(
                      momentBloc: momentBloc,
                    ),
                    // تاب الريلز — الفيديوهات فقط
                    _Theme2ReelsTab(
                      getReelsBloc: getReelsBloc,
                      userId: userId,
                    ),
                  ],
                ),
              ),
            );
          },
        ),
        ),
        bottomNavigationBar: isMyProfile
            ? null
            : BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
                bloc: fetchUserDataBloc,
                buildWhen: (prev, curr) =>
                    prev.reqStateUser != curr.reqStateUser ||
                    prev.otherUserEntity != curr.otherUserEntity,
                builder: (context, state) {
                  if (!state.reqStateUser.isLoaded) {
                    return const SizedBox.shrink();
                  }
                  return Theme2VisitorActionBar(user: state.otherUserEntity);
                },
              ),
      ),
    );
  }
}

/// تاب عام — bio + supporters/CP + honors/badges/gifts all in one scroll.
class _Theme2GeneralTab extends StatelessWidget {
  final UserEntity? user;
  final String userId;
  final GetSupporterBloc getSupporterBloc;
  final CpProfileBloc cpProfileBloc;
  final UserBadgesBloc userBadgesBloc;
  final GetBadgesBloc getBadgesBloc;
  final GetUserIntroBloc getUserIntroBloc;
  final GiftHistoryBloc giftHistoryBloc;

  const _Theme2GeneralTab({
    required this.user,
    required this.userId,
    required this.getSupporterBloc,
    required this.cpProfileBloc,
    required this.userBadgesBloc,
    required this.getBadgesBloc,
    required this.getUserIntroBloc,
    required this.giftHistoryBloc,
  });

  @override
  Widget build(BuildContext context) {
    // SingleChildScrollView+Column (not ListView) so the two nested shrink-wrap
    // ListViews (relation + honor) measure their intrinsic height correctly
    // inside the NestedScrollView body. A bare outer ListView here collapsed to
    // zero height (عام showed empty) because a lazy ListView cannot give a
    // bounded height to nested shrink-wrap ListViews.
    return SingleChildScrollView(
      key: const PageStorageKey('theme2_general_tab'),
      padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // الملف الشخصي والبيانات الشخصية
          if ((user?.bio != null && user!.bio!.isNotEmpty) ||
              user?.country?.nameEn != null)
            Container(
            width: double.infinity,
            padding: EdgeInsets.all(16.r),
            margin: EdgeInsets.only(bottom: 12.h),
            decoration: ColorManager.cardDecoration(
              borderRadius: BorderRadius.circular(16.r),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(StringManager.personalProfile.tr(),
                    style: TextStyle(
                        color: ColorManager.secondaryText, fontSize: 13.sp)),
                8.hBox,
                Text(
                  (user?.bio != null && user!.bio!.isNotEmpty)
                      ? user!.bio!
                      : StringManager.noDataYet.tr(),
                  style: TextStyle(
                      color: ColorManager.textPrimary,
                      fontSize: 15.sp,
                      fontWeight: FontWeight.w500),
                  textAlign: TextAlign.right,
                ),
                if (user?.country?.nameEn != null) ...[
                  12.hBox,
                  Text('Personality Tags',
                      style: TextStyle(
                          color: ColorManager.secondaryText, fontSize: 13.sp)),
                  8.hBox,
                  Container(
                    padding: EdgeInsets.symmetric(
                        horizontal: 12.w, vertical: 5.h),
                    decoration: BoxDecoration(
                      color: ColorManager.primary.withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(20.r),
                    ),
                    child: Text(user!.country!.nameEn ?? '',
                        style: TextStyle(
                            color: ColorManager.primary,
                            fontSize: 12.sp,
                            fontWeight: FontWeight.w500)),
                  ),
                ],
              ],
            ),
          ),
        // العلاقات والداعمين
        Theme2VisitorRelationTab(
          user: user,
          getSupporterBloc: getSupporterBloc,
          cpProfileBloc: cpProfileBloc,
        ),
          // الإنجازات والشارات والهدايا والمركبات
          Theme2VisitorHonorTab(
            userBadgesBloc: userBadgesBloc,
            getBadgesBloc: getBadgesBloc,
            getUserIntroBloc: getUserIntroBloc,
            giftHistoryBloc: giftHistoryBloc,
            userId: userId,
          ),
        ],
      ),
    );
  }
}

/// تاب اللحظات — المنشورات فقط (بدون فيديوهات).
class _Theme2MomentsTab extends StatelessWidget {
  final MomentBloc momentBloc;

  const _Theme2MomentsTab({
    required this.momentBloc,
  });

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<MomentBloc, MomentStates>(
      bloc: momentBloc,
      buildWhen: (prev, curr) =>
          prev.myReqState != curr.myReqState ||
          prev.myMoments != curr.myMoments,
      builder: (context, state) {
        if (state.myMoments.isEmpty) {
          return Center(
            child: Padding(
              padding: EdgeInsets.symmetric(vertical: 24.h),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.article_outlined,
                      color: ColorManager.greyText, size: 40.sp),
                  8.hBox,
                  Text(StringManager.noMomentsFound.tr(),
                      style: TextStyle(
                          color: ColorManager.secondaryText, fontSize: 14.sp)),
                ],
              ),
            ),
          );
        }
        return ListView.builder(
          padding: EdgeInsets.all(12.r),
          itemCount: state.myMoments.length,
          itemBuilder: (context, i) {
            final moment = state.myMoments[i];
            return MomentItem(
              type: '1',
              momentBloc: momentBloc,
              momentType: MomentType.myMoment,
              moment: moment,
              currentMomentIndex: i,
              isProfile: true,
            );
          },
        );
      },
    );
  }
}

/// تاب الريلز — فيديوهات المستخدم: شبكة من الصور المصغّرة (subFrame)، والضغط يشغّل.
class _Theme2ReelsTab extends StatelessWidget {
  final GetReelsBloc getReelsBloc;
  final String userId;

  const _Theme2ReelsTab({required this.getReelsBloc, required this.userId});

  void _openReel(BuildContext context, int index) {
    final reelViewerBloc = di<ReelViewerBloc>();
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => PlayMyReelsView(
          getReelsBloc: getReelsBloc,
          reelViewerBloc: reelViewerBloc,
          param: PlayMyReelParam(
            userId: userId,
            index: index,
            getReelsBloc: getReelsBloc,
            reelViewerBloc: reelViewerBloc,
          ),
        ),
      ),
    );
  }

  void _confirmDeleteReel(
      BuildContext context, ReelsEntity reel, int index) {
    showDialog<void>(
      context: context,
      builder: (dialogContext) => AnimatedDialog(
        title: StringManager.delete.tr(),
        conText: StringManager.confirm.tr(),
        cancelText: StringManager.cancel.tr(),
        onTap: () {
          Navigator.pop(dialogContext);
          getReelsBloc.add(DeleteReelEvent(index, reel.id.toString()));
        },
        onTapCancel: () => Navigator.pop(dialogContext),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<GetReelsBloc, GetReelsState>(
      bloc: getReelsBloc,
      buildWhen: (prev, curr) =>
          prev.requestMyReelsState != curr.requestMyReelsState ||
          prev.myReelsList != curr.myReelsList,
      builder: (context, state) {
        if (state.requestMyReelsState == RequestState.loading) {
          return const Center(child: CircularProgressIndicator());
        }
        if (state.myReelsList.isEmpty) {
          return Center(
            child: Padding(
              padding: EdgeInsets.symmetric(vertical: 40.h),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.video_library_outlined,
                      color: ColorManager.greyText, size: 40.sp),
                  8.hBox,
                  Text(
                    StringManager.noDataYet.tr(),
                    style: TextStyle(
                        color: ColorManager.secondaryText, fontSize: 14.sp),
                  ),
                ],
              ),
            ),
          );
        }
        return GridView.builder(
          padding: EdgeInsets.all(4.r),
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 3,
            crossAxisSpacing: 2.w,
            mainAxisSpacing: 2.h,
            childAspectRatio: 9 / 16,
          ),
          itemCount: state.myReelsList.length,
          itemBuilder: (context, index) {
            final reel = state.myReelsList[index];
            final isOwnReel =
                reel.user?.id != null &&
                    reel.user?.id == MyDataModel.getInstance().id;
            return GestureDetector(
              onTap: () => _openReel(context, index),
              onLongPress: isOwnReel
                  ? () => _confirmDeleteReel(context, reel, index)
                  : null,
              child: ClipRRect(
                borderRadius: BorderRadius.circular(4.r),
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    // الصورة المصغّرة من حقل subFrame (الكوفر) لا من رابط الفيديو.
                    ImageViewWidget(
                      url: EndPoints.getImage(reel.subFrame ?? ''),
                      boxFit: BoxFit.cover,
                    ),
                    Positioned(
                      bottom: 4.h,
                      left: 4.w,
                      child: Icon(Icons.play_circle_fill,
                          color: ColorManager.white, size: 18.sp),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }
}

/// Sticky tab bar delegate
class _Theme2StickyTabBarDelegate extends SliverPersistentHeaderDelegate {
  final TabBar tabBar;

  _Theme2StickyTabBarDelegate(this.tabBar);

  @override
  double get minExtent => tabBar.preferredSize.height;

  @override
  double get maxExtent => tabBar.preferredSize.height;

  @override
  Widget build(
      BuildContext context, double shrinkOffset, bool overlapsContent) {
    return Container(
      color: ColorManager.surfaceCardColor,
      child: tabBar,
    );
  }

  @override
  bool shouldRebuild(_Theme2StickyTabBarDelegate oldDelegate) => false;
}
