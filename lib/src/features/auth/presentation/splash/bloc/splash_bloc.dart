import 'dart:async';
import 'package:flutter_native_splash/flutter_native_splash.dart';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/presentation/splash/config_app/config_app_bloc.dart';
import '../../../../home/presentation/banner/banner_bloc/banner_bloc.dart';
import '../../../../home/presentation/banner/banner_bloc/banner_event.dart';

part 'splash_event.dart';
part 'splash_state.dart';

class SplashBloc extends Bloc<SplashEvent, SplashState> {
  SplashBloc() : super(const SplashInitial()) {
    on<SplashNavigationEvent>(_navigationEvent);
  }

  // Events
  void _navigationEvent(
    SplashNavigationEvent event,
    Emitter<SplashState> emit,
  ) async {
    FlutterNativeSplash.remove();
    final token = HiveManager.instance
        .getData<String>(KeysManager.USER_BOX, KeysManager.TOKEN_KEY);

    // if (!ConstantsManager.isVariantBuildB) {
    if (token != null) di<BannerBloc>().add(const GetBannerEvent());
    // }

    final isSkippedOnBoarding = Methods.getOnBoarding();
    final isSkippedLanguage = Methods.getLanguageScreen();

    // Navigation is already gated on config (loaded/empty/error) by
    // _listenToConfigAndNavigate in splash_page.dart, so the old blind 3s
    // delay added nothing but wasted launch time. Navigate immediately.
    //
    // Only the banner branch needs the banner fetch to resolve first. Instead
    // of blocking on an arbitrary delay, wait for the BannerBloc to reach a
    // terminal state with a SHORT cap (600ms). If banners don't resolve in
    // time we default to layout — never block the launch.
    if (token != null && isSkippedLanguage != false && isSkippedOnBoarding != false) {
      await _waitForBanner();
    }

    if (isSkippedLanguage == false) {
      navKey.currentContext?.pushNamedAndRemoveUntil(
        Routes.languageScreen,
        arguments: true,
      );
    } else if (isSkippedOnBoarding == false) {
      navKey.currentContext?.pushNamedAndRemoveUntil(Routes.onBoardingScreen);
    } else {
      token == null
          ? navKey.currentContext?.pushNamedAndRemoveUntil(Routes.intro)
          : di<BannerBloc>().state.bannerEntity != null
              ? navKey.currentContext
                  ?.pushNamedAndRemoveUntil(Routes.bannerScreen)
              : navKey.currentContext
                  ?.pushNamedAndRemoveUntil(Routes.layout);
    }
    if (di<ConfigAppBloc>().state.requestState.isError) {
      final context = SafeNavigator.context;
      if (context == null) return;
      Navigator.pushNamedAndRemoveUntil(
        context,
        Routes.refreshScreen,
        (route) => false,
      );
      return;
    }
  }

  // Wait (max 600ms) for the banner request to reach a terminal state so the
  // bannerScreen-vs-layout decision is correct. Returns immediately if the
  // banner already resolved; falls through (defaulting to layout) on timeout.
  Future<void> _waitForBanner() async {
    final bannerBloc = di<BannerBloc>();
    final state = bannerBloc.state;
    if (state.requestState.isLoaded ||
        state.requestState.isEmpty ||
        state.requestState.isError) {
      return;
    }
    try {
      await bannerBloc.stream
          .firstWhere((s) =>
              s.requestState.isLoaded ||
              s.requestState.isEmpty ||
              s.requestState.isError)
          .timeout(const Duration(milliseconds: 600));
    } catch (_) {
      // Timed out or stream closed — default to layout below.
    }
  }
}
