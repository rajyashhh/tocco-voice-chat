import 'dart:async';
import 'dart:io';
import 'package:general/src/core/realtime/realtime_bootstrap.dart';
import 'package:general/src/features/agency/presentation/charge_agency/view/component/host_withdrawel_screen/bloc/get_setting_manager/get_setting_bloc.dart';
import 'package:general/src/features/agency/presentation/charge_agency/view/component/host_withdrawel_screen/bloc/host_requests_manager/host_requests_bloc.dart';
import 'package:general/src/features/auth/presentation/splash/config_app/config_app_bloc.dart';
import 'package:general/src/features/chats/chats.dart';
import 'package:general/src/features/chats/presentation/chats/view/chats_page.dart';
import 'package:general/src/features/games/presentation/games/view/games_page.dart';
import 'package:general/src/features/moment/presentation/bloc/moment_bloc/moment_bloc.dart';
import 'package:general/src/features/moment/presentation/bloc/moment_bloc/moment_event.dart';
import 'package:general/src/features/moment/presentation/view/moment_page.dart';
import 'package:general/src/features/profile/presentation/coins/bloc/my_store_bloc/my_store_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_user_badges/get_user_badges_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_user_badges/get_user_badges_event.dart';
import 'package:general/src/features/profile/presentation/profile/view/profile_screen.dart';
import 'package:general/reels_viewer/reels_viewer.dart';
import 'package:general/src/features/reels/presentation/main_reels_page/view/main_reels_screen.dart';
import 'package:general/src/features/reels/presentation/main_reels_page/reels_upload_request.dart';
import 'package:general/src/features/layout/view/layout_shell_mixin.dart';

class LayoutPage extends StatefulWidget {
  const LayoutPage({super.key});

  @override
  State<LayoutPage> createState() => _LayoutPageState();
}

class _LayoutPageState extends State<LayoutPage>
    with WidgetsBindingObserver, LayoutShellMixin<LayoutPage> {
  final PageStorageBucket _bucket = PageStorageBucket();

  Widget _buildCurrentPage(int index) {
    switch (index) {
      case 0:
        return const HomePage();
      case 1:
        return ConstantsManager.isReelsVisible
            ? const ReelsScreen()
            : const GamesPage();
      case 2:
        return const ChatsPage();
      case 3:
        return ConstantsManager.isShowMoment
            ? const MomentPage()
            : const ProfilePage();
      case 4:
        return const ProfilePage();
      default:
        return const HomePage();
    }
  }

  @override
  void initState() {
    if (ConstantsManager.homeScreen == 'audio room' ||
        ConstantsManager.homeScreen == 'live') {
      di<LayoutBloc>().add(const ChangeIndexEvent(currentIdex: 0));
    } else if (ConstantsManager.homeScreen == 'chat') {
      di<LayoutBloc>().add(const ChangeIndexEvent(currentIdex: 2));
      if (di<FetchUsersChatBloc>().state.reqState.isIdle) {
        di<FetchUsersChatBloc>().add(const GetChatUsersEvent(userId: ''));
      }
      di<FetchUsersChatBloc>().add(
        const AddChatUsersListenerEvent(userId: ''),
      );
    } else if (ConstantsManager.homeScreen == 'moment' &&
        ConstantsManager.isShowMoment) {
      di<LayoutBloc>().add(const ChangeIndexEvent(currentIdex: 3));
      if (!di<MomentBloc>().state.reqState.isLoaded) {
        di<MomentBloc>().add(const FetchMomentData());
      }
    } else if (ConstantsManager.homeScreen == 'reels' &&
        ConstantsManager.isReelsVisible) {
      di<LayoutBloc>().add(const ChangeIndexEvent(currentIdex: 1));
    } else if (ConstantsManager.homeScreen == 'game' &&
        !ConstantsManager.isReelsVisible) {
      di<LayoutBloc>().add(const ChangeIndexEvent(currentIdex: 1));
    } else {
      di<LayoutBloc>().add(const ChangeIndexEvent(currentIdex: 0));
    }

    if (Platform.isAndroid) {
      ConstantsManager.devicePlatform = StringManager.android;
    } else if (Platform.isIOS) {
      ConstantsManager.devicePlatform = StringManager.ios;
    }

    // Phase 1: Critical data needed immediately
    if (!di<FetchUserDataBloc>().state.reqState.isLoaded) {
      di<FetchUserDataBloc>().add(
        const FetchMyDataEvent(isLoading: false, initRealtime: true),
      );
    }

    // Phase 2: Important but can wait for first frame
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!di<ConfigAppBloc>().state.requestState.isLoaded) {
        di<ConfigAppBloc>().add(
          ConfigAppEvent(
            devicePLATFORM: ConstantsManager.devicePlatform,
            versionApp: ConstantsManager.appVersionCode.toString(),
          ),
        );
      } else {
        checkForUpdate();
      }

      // Stream token is now fetched on-demand when entering a room
      // via _ensureRoomCredentials in RoomStateManager.
      // No need to pre-fetch at layout init.

      // Gift categories and emoji categories moved OFF cold-start — the gift
      // sheet (live_gift_screen / gift_room_page) and the emoji widget self-load
      // them on first open.

      Methods.safeSubscribeToTopic();

      // Realtime / offline-first chat bootstrap (Phase 7 part B). Started here
      // because LayoutPage mounts only after login + DI is complete. Guarded so
      // a not-yet-deployed Centrifugo server (Phase 8) can never break the app —
      // the drift local-first + REST-delta paths stand alone without it. The
      // 1:1 chat keeps running on legacy realtime untouched.
      startRealtimeBootstrap();
    });

    WidgetsBinding.instance.addObserver(this);
    super.initState();
  }

  Future<bool> _onWillPop() async {
    final bloc = di<LayoutBloc>();
    if (bloc.state.currentIndex != 0) {
      bloc.add(const ChangeIndexEvent(currentIdex: 0));
      return false;
    } else {
      final shouldClose = await showDialog<bool>(
        context: context,
        builder: (context) => AnimatedDialog(
          title: StringManager.note.tr(),
          description: StringManager.areYouSureExit.tr(),
          onTap: () async {
            if (di<RoomStateManager>().isInRoom) {
              final navContext = SafeNavigator.context;
              if (navContext == null) {
                Navigator.of(context).pop(true);
                return;
              }
              await di<RoomStateManager>().exitRoom(
                navContext,
                callback: () {
                  Navigator.of(context).pop(true);
                },
              );
            } else {
              Navigator.of(context).pop(true);
            }
          },
        ),
      );
      return shouldClose ?? false;
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<ConfigAppBloc, ConfigAppState>(
        bloc: di<ConfigAppBloc>(),
        listener: (context, state) {
          // A fresh app-check may carry a new UI variant selected from the
          // admin panel. The variant is consumed only at route-generation /
          // first-build time, so re-navigate to rebuild the correct shell.
          reactToVariantChange();
          checkForUpdate();
        },
        child: BlocListener<HostRequestsBloc, HostRequestsState>(
            bloc: di<HostRequestsBloc>(),
            listener: (context, state) =>
                handleHostRequestsState(state, context),
            child: BlocBuilder<LayoutBloc, LayoutState>(
              bloc: di<LayoutBloc>(),
              buildWhen: (prev, curr) => prev.currentIndex != curr.currentIndex,
              builder: (context, state) {
                return BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
                    bloc: di<FetchUserDataBloc>(),
                    buildWhen: (prev, curr) =>
                        prev.userEntity?.isGameAvailable !=
                        curr.userEntity?.isGameAvailable,
                    builder: (context, userState) {
                      final isGameAvailable =
                          userState.userEntity?.isGameAvailable ?? false;
                      return PopScope(
                        canPop: false,
                        onPopInvokedWithResult: (didPop, result) async {
                          if (didPop) return;
                          bool isShouldPop = await _onWillPop();
                          if (isShouldPop) {
                            SystemNavigator.pop();
                          }
                        },
                        child: Scaffold(
                          body: PageStorage(
                            bucket: _bucket,
                            child: _buildCurrentPage(state.currentIndex),
                          ),
                          bottomNavigationBar: Container(
                            height: 80.h,
                            width: ScreenUtil().screenWidth,
                            decoration: const BoxDecoration(
                              border: Border(
                                top: BorderSide(
                                    width: 0.2, color: ColorManager.grey),
                              ),
                            ),
                            // Panel-driven nav region background when present;
                            // otherwise the legacy single-color derived gradient
                            // so existing clients look identical.
                            child: RegionBackground(
                              descriptor: ColorManager.navRegion,
                              fallback: (_) => DecoratedBox(
                                decoration: BoxDecoration(
                                  gradient: GradientHelper
                                      .buildGradientFromBottomColor(
                                    ColorManager.bottomNavColor,
                                  ),
                                ),
                              ),
                              child: _BottomNavigationBar(
                                index: state.currentIndex,
                                isGameAvailable: isGameAvailable,
                              ),
                            ),
                          ),
                        ),
                      );
                    });
              },
            ),
          ),
        );
  }
}

class _BottomNavigationBar extends StatelessWidget {
  const _BottomNavigationBar({
    required this.index,
    required this.isGameAvailable,
  });

  final int index;
  final bool isGameAvailable;

  /// Maps the LayoutBloc index to the visible nav bar selected index.
  int get _displayIndex {
    if (isGameAvailable || ConstantsManager.isReelsVisible) return index;
    if (index <= 1) return 0; // games slot hidden, fallback to home
    return index - 1;
  }

  /// Maps a nav bar tap index back to the LayoutBloc index.
  int _toBlocIndex(int navIndex) {
    if (isGameAvailable || ConstantsManager.isReelsVisible) return navIndex;
    if (navIndex == 0) return 0;
    return navIndex + 1; // skip the hidden games slot (index 1)
  }

  /// Bottom-nav tab icon, driven by the admin panel.
  ///
  /// Each tab's active/inactive icon comes from a panel URL (fetched + cached on
  /// device). When the panel shipped no URL for the requested state the bundled
  /// generic [fallbackIcon] is rendered (tinted active/inactive) so the bar is
  /// never blank.
  Widget _buildIcon({
    required int tabIndex,
    required String fallbackIcon,
    required bool isActive,
    Color? inactiveColor,
    Color? activeColor,
  }) {
    return NavBarIcon(
      tabIndex: tabIndex,
      isSelected: isActive,
      fallbackAsset: fallbackIcon,
      size: 28.5.h,
      activeColor: activeColor,
      inactiveColor: inactiveColor,
    );
  }

  @override
  Widget build(BuildContext context) {
    return BottomNavigationBar(
      items: [
        // Home tab (panel nav-icon index 0)
        BottomNavigationBarItem(
          icon: _buildIcon(
            tabIndex: 0,
            fallbackIcon: AssetsManager.icHome,
            isActive: false,
          ),
          activeIcon: _buildIcon(
            tabIndex: 0,
            fallbackIcon: AssetsManager.icHome,
            isActive: true,
          ),
          label: StringManager.home.tr(),
        ),
        // Reels/Games tab (panel nav-icon index 1). TikTok style (feature
        // parity with the theme_2/theme_3 shells): while the user is ALREADY
        // on the Reels tab, the item morphs into a "+" that opens the reel
        // upload flow.
        if (isGameAvailable || ConstantsManager.isReelsVisible)
          BottomNavigationBarItem(
            icon: _buildIcon(
              tabIndex: 1,
              fallbackIcon: ConstantsManager.isReelsVisible
                  ? AssetsManager.icReel
                  : AssetsManager.icGame,
              isActive: false,
            ),
            activeIcon: ConstantsManager.isReelsVisible
                ? Container(
                    width: 42.w,
                    height: 30.h,
                    decoration: BoxDecoration(
                      color: ColorManager.bottomNavActiveColor,
                      borderRadius: BorderRadius.circular(8.r),
                    ),
                    child: Icon(Icons.add,
                        color: ColorManager.bottomNavColor, size: 22.sp),
                  )
                : _buildIcon(
                    tabIndex: 1,
                    fallbackIcon: AssetsManager.icGame,
                    isActive: true,
                  ),
            label: ConstantsManager.isReelsVisible
                ? StringManager.reels.tr()
                : StringManager.games.tr(),
          ),
        // Chat tab (panel nav-icon index 2)
        BottomNavigationBarItem(
          icon: _buildIcon(
            tabIndex: 2,
            fallbackIcon: AssetsManager.icBubble,
            isActive: false,
          ),
          activeIcon: _buildIcon(
            tabIndex: 2,
            fallbackIcon: AssetsManager.icBubble,
            isActive: true,
          ),
          label: StringManager.chat.tr(),
        ),
        // Moment tab (conditional, panel nav-icon index 3)
        if (ConstantsManager.isShowMoment)
          BottomNavigationBarItem(
            icon: _buildIcon(
              tabIndex: 3,
              fallbackIcon: AssetsManager.icWorld,
              isActive: false,
            ),
            activeIcon: _buildIcon(
              tabIndex: 3,
              fallbackIcon: AssetsManager.icWorld,
              isActive: true,
            ),
            label: StringManager.moment.tr(),
          ),
        // Profile tab (panel nav-icon index 4)
        BottomNavigationBarItem(
          icon: _buildIcon(
            tabIndex: 4,
            fallbackIcon: AssetsManager.icProfile,
            isActive: false,
          ),
          activeIcon: _buildIcon(
            tabIndex: 4,
            fallbackIcon: AssetsManager.icProfile,
            isActive: true,
          ),
          label: StringManager.mine.tr(),
        ),
      ],
      backgroundColor: ColorManager.transparent,
      currentIndex: _displayIndex,
      type: BottomNavigationBarType.fixed,
      onTap: (navIndex) {
        final index = _toBlocIndex(navIndex);
        // Already ON the Reels tab and its item was tapped again → the item
        // is showing the "+", so open the upload flow instead of a no-op
        // (same behaviour as the theme_2/theme_3 nav bars).
        if (index == 1 &&
            this.index == 1 &&
            ConstantsManager.isReelsVisible) {
          reelsUploadRequest.value++;
          return;
        }
        // Pause reels video when leaving the reels tab
        if (this.index == 1 && index != 1 && ConstantsManager.isReelsVisible) {
          if (di.isRegistered<GetReelsBloc>()) {
            di<GetReelsBloc>().add(const PauseAllControllersEvent());
          }
        }

        if (index == 1) {
          if (ConstantsManager.isReelsVisible) {
            if (this.index != 1 && di.isRegistered<GetReelsBloc>()) {
              di<GetReelsBloc>().add(const ResumeCurrentControllerEvent());
            }
          }
        }
        if (index == 2) {
          if (di<FetchUsersChatBloc>().state.reqState.isIdle) {
            di<FetchUsersChatBloc>().add(const GetChatUsersEvent(userId: ''));
          }
          di<FetchUsersChatBloc>().add(
            const AddChatUsersListenerEvent(userId: ''),
          );
        } else if (index == 3) {
          if (!di<MomentBloc>().state.reqState.isLoaded &&
              ConstantsManager.isShowMoment) {
            di<MomentBloc>().add(const FetchMomentData());
          }
        } else if (index == 4) {
          di<GetUserBadgesBloc>().add(
            GetUserBadgesData(id: MyDataModel.getInstance().id ?? 0),
          );
          if (!di<MyStoreBloc>().state.reqState.isLoaded) {
            di<MyStoreBloc>().add(const GetMyStoreEvent(isLoading: false));
          }
          if (!di<GetSettingBloc>().state.state.isLoaded) {
            di<GetSettingBloc>().add(const GetSettingsEvent());
          }
        }
        di<LayoutBloc>().add(ChangeIndexEvent(currentIdex: index));
        HomePage.isFirstTime = false;
      },
      elevation: 0,
      selectedLabelStyle: context.bodyMedium
          .size(11)
          .w500
          .colorExt(ColorManager.bottomNavActiveColor),
      showSelectedLabels:
          !ConstantsManager.isTheme1 || ConstantsManager.isTheme2,
      showUnselectedLabels:
          !ConstantsManager.isTheme1 || ConstantsManager.isTheme2,
      selectedItemColor: ColorManager.bottomNavActiveColor,
      unselectedItemColor: index == 1 && ConstantsManager.isReelsVisible
          ? ColorManager.white
          : ColorManager.bottomNavInactiveColor
        ..withValues(alpha: (0.5)),
      unselectedLabelStyle: context.bodyMedium
          .size(11)
          .w500
          .colorExt(ColorManager.bottomNavInactiveColor),
    );
  }
}
