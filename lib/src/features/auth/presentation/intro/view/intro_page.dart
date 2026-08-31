import 'dart:io';
import 'dart:math' as math;
import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/presentation/auth_platform/bloc/auth_platform_bloc.dart';
import 'package:general/src/core/widgets/update_dialog.dart';
import 'package:general/src/features/auth/presentation/splash/config_app/config_app_bloc.dart';

class IntroPage extends StatefulWidget {
  final String? error;
  const IntroPage({super.key, this.error});

  @override
  State<IntroPage> createState() => _IntroPageState();
}

class _IntroPageState extends State<IntroPage> with TickerProviderStateMixin {
  late final AnimationController _mainCtrl;
  final ValueNotifier<Offset> _parallaxOffset = ValueNotifier(Offset.zero);

  @override
  void initState() {
    super.initState();

    _mainCtrl =
        AnimationController(vsync: this, duration: const Duration(seconds: 3))
          ..repeat(reverse: true);

    if (widget.error != null && (widget.error ?? '').isNotEmpty) {
      Future.delayed(
        const Duration(milliseconds: 800),
        () {
          if (!mounted) return;
          showDialog(
            context: context,
            builder: (context) => AnimatedDialog(
              // The only flow that lands here with an error is the 505
              // single-session teardown — it's a device sign-out, NOT a ban.
              title: StringManager.loggedInFromAnotherDevice.tr(),
              description: StringManager.loggedInFromAnotherDeviceDesc.tr(),
              isHideConfirm: true,
            ),
          );
        },
      );
    }
  }

  @override
  void dispose() {
    _mainCtrl.dispose();
    _parallaxOffset.dispose();
    super.dispose();
  }

  void _onPointerMove(PointerEvent e, Size size) {
    final nx = ((e.position.dx / size.width) - 0.5).clamp(-0.5, 0.5);
    final ny = ((e.position.dy / size.height) - 0.5).clamp(-0.5, 0.5);
    _parallaxOffset.value = Offset(nx * 18, ny * 18);
  }

  Widget _emojiWidget({
    required String emoji,
    required double fontSize,
    required double offset,
    required double delay,
  }) {
    return AnimatedBuilder(
      animation: _mainCtrl,
      builder: (context, child) {
        final t =
            (math.sin((_mainCtrl.value * math.pi * 2) + delay) * 0.5) + 0.5;
        final dy = offset * t;
        final scale =
            1 + 0.04 * math.sin((_mainCtrl.value * math.pi * 2) + delay);
        return Transform.translate(
          offset: Offset(0, -dy),
          child: Transform.scale(scale: scale, child: child),
        );
      },
      child: TextWidget(
        emoji,
        style: context.bodyMedium.copyWith(
          fontSize: fontSize,
          height: 1,
          color: ColorManager.onDark,
          shadows: const [
            Shadow(blurRadius: 6, color: Colors.black26, offset: Offset(0, 3)),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final baseColor = ColorManager.primary;
    final hsl = HSLColor.fromColor(baseColor);

    final gradient = LinearGradient(
      begin: AlignmentDirectional.topStart,
      end: AlignmentDirectional.bottomEnd,
      colors: [
        hsl.withLightness((hsl.lightness + 0.05).clamp(0.0, 1.0)).toColor(),
        hsl.withLightness((hsl.lightness - 0.25).clamp(0.0, 1.0)).toColor(),
        hsl.withLightness((hsl.lightness - 0.35).clamp(0.0, 1.0)).toColor(),
      ],
      stops: const [0.0, 0.6, 1.0],
    );

    final size = MediaQuery.sizeOf(context);

    return BlocListener<ConfigAppBloc, ConfigAppState>(
      bloc: di<ConfigAppBloc>(),
      listener: (context, state) {
        if (state.requestState.isLoaded) {
          if (state.config?.isLastVersion != true) {
            showDialog(
              context: context,
              barrierDismissible: !(state.config?.isForce ?? false),
              barrierColor: Colors.black.withValues(alpha: 0.5),
              builder: (_) => PopScope(
                canPop: false,
                onPopInvoked: (_) {},
                child: ForceUpdateDialog(
                  description: StringManager.updatDesc.tr(),
                  isOptionalUpdate: !(state.config?.isForce ?? false),
                  onConfirm: () {
                    Methods.openUrl(ConstantsManager.appURL);
                  },
                ),
              ),
            );
          }
        }
      },
      child: Scaffold(
        body: Container(
          decoration: BoxDecoration(gradient: gradient),
          child: Listener(
            onPointerHover: (e) => _onPointerMove(e, size),
            onPointerMove: (e) => _onPointerMove(e, size),
            child: Stack(
              children: [
                ValueListenableBuilder<Offset>(
                  valueListenable: _parallaxOffset,
                  builder: (context, offset, child) {
                    return Transform.translate(offset: offset, child: child);
                  },
                  child: Opacity(
                    opacity: 0.45,
                    child: Stack(
                      children: [
                        Positioned(
                          left: size.width * 0.040,
                          top: size.height * 0.080,
                          child: _emojiWidget(
                            emoji: '🎮',
                            fontSize: 70.h,
                            offset: 25,
                            delay: 0,
                          ),
                        ),
                        Positioned(
                          left: size.width * 0.080,
                          top: size.height * 0.25,
                          child: _emojiWidget(
                            emoji: '😎',
                            fontSize: 45.h,
                            offset: 20,
                            delay: 1.2,
                          ),
                        ),
                        Positioned(
                          right: size.width * 0.080,
                          top: size.height * 0.10,
                          child: _emojiWidget(
                            emoji: '📹',
                            fontSize: 50.h,
                            offset: 18,
                            delay: 2.4,
                          ),
                        ),
                        Positioned(
                          right: size.width * 0.10,
                          top: size.height * 0.45,
                          child: _emojiWidget(
                            emoji: '💬',
                            fontSize: 50.h,
                            offset: 20,
                            delay: 0.9,
                          ),
                        ),
                        Positioned(
                          right: size.width * 0.22,
                          bottom: size.height * 0.25,
                          child: _emojiWidget(
                            emoji: '🏆',
                            fontSize: 60.h,
                            offset: 28,
                            delay: 1.8,
                          ),
                        ),
                        Positioned(
                          left: size.width * 0.070,
                          bottom: size.height * 0.180,
                          child: _emojiWidget(
                            emoji: '▶️',
                            fontSize: 50.h,
                            offset: 20,
                            delay: 0.9,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                Positioned.fill(
                  child: SafeArea(
                    child: Center(
                      child: Column(
                        children: [
                          (size.width / 2.75).hBox,
                          Column(
                            children: [
                              Image.asset(
                                AssetsManager.logo,
                                height: 120.h,
                                width: 120.w,
                              ),
                              5.hBox,
                              TextWidget(
                                StringManager.playStreamConnect,
                                style:
                                    context.bodyMedium.w400.size(14).colorExt(
                                          ColorManager.onDark,
                                        ),
                              ),
                            ],
                          ),
                          40.hBox,
                          Container(
                            padding: context.paddingOnly(
                              start: 20,
                              end: 20,
                              bottom: 20,
                              top: 50,
                            ),
                            margin: context.paddingSymmetric(horizontal: 17.5),
                            decoration: BoxDecoration(
                              color: ColorManager.white.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(28),
                              border: Border.all(
                                color:
                                    ColorManager.white.withValues(alpha: 0.120),
                                width: 1,
                              ),
                              boxShadow: [
                                BoxShadow(
                                  color: ColorManager.white
                                      .withValues(alpha: 0.03),
                                  blurRadius: 18,
                                  spreadRadius: 8,
                                  offset: const Offset(0, -6),
                                ),
                                BoxShadow(
                                  color: ColorManager.black
                                      .withValues(alpha: 0.120),
                                  blurRadius: 40,
                                  offset: const Offset(0, 26),
                                ),
                              ],
                            ),
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                _ghostButton(
                                  icon: AssetsManager.phone,
                                  label: StringManager.signInWithPhone,
                                  onTap: () =>
                                      context.pushNamedRoute(Routes.login),
                                ),
                                15.hBox,
                                BlocBuilder<AuthPlatformBloc,
                                    AuthPlatformState>(
                                  bloc: di<AuthPlatformBloc>(),
                                  buildWhen: (prev, curr) =>
                                      prev.requestStateGoogle !=
                                      curr.requestStateGoogle,
                                  builder: (context, state) {
                                    return _ghostButton(
                                      icon: AssetsManager.google_,
                                      label: StringManager.signInWithGoogle,
                                      size: 30,
                                      isLoading:
                                          state.requestStateGoogle.isLoading,
                                      onTap: () {
                                        di<AuthPlatformBloc>().add(
                                          SignInGoogleEvent(
                                            context: context,
                                            isAddAccount: false,
                                          ),
                                        );
                                      },
                                    );
                                  },
                                ),
                                if (ConstantsManager.devicePlatform ==
                                    StringManager.huawei) ...[
                                  15.hBox,
                                  BlocBuilder<AuthPlatformBloc,
                                      AuthPlatformState>(
                                    bloc: di<AuthPlatformBloc>(),
                                    buildWhen: (prev, curr) =>
                                        prev.requestStateHuawei !=
                                        curr.requestStateHuawei,
                                    builder: (context, state) {
                                      return _ghostButton(
                                        icon: AssetsManager.huawei,
                                        label: StringManager.signInWithHuawei,
                                        isLoading:
                                            state.requestStateHuawei.isLoading,
                                        onTap: () {
                                          di<AuthPlatformBloc>().add(
                                            SignInHuaweiEvent(
                                              context: context,
                                            ),
                                          );
                                        },
                                      );
                                    },
                                  ),
                                ],
                                if (Platform.isIOS) ...[
                                  15.hBox,
                                  BlocBuilder<AuthPlatformBloc,
                                      AuthPlatformState>(
                                    bloc: di<AuthPlatformBloc>(),
                                    buildWhen: (prev, curr) =>
                                        prev.requestStateApple !=
                                        curr.requestStateApple,
                                    builder: (context, state) {
                                      return _ghostButton(
                                        icon: AssetsManager.apple,
                                        label: StringManager.signInWithApple,
                                        isLoading:
                                            state.requestStateApple.isLoading,
                                        onTap: () {
                                          di<AuthPlatformBloc>().add(
                                            SignInAppleEvent(
                                              context: context,
                                            ),
                                          );
                                        },
                                      );
                                    },
                                  ),
                                ],
                                30.hBox,
                                Divider(
                                  color: Colors.white.withValues(alpha: 0.14),
                                  height: 0.0,
                                  thickness: 1.25,
                                ),
                                30.hBox,
                                GestureDetector(
                                  onTap: () =>
                                      context.pushNamedRoute(Routes.privacy),
                                  child: Center(
                                    child: Text.rich(
                                      textAlign: TextAlign.center,
                                      TextSpan(
                                        children: [
                                          TextSpan(
                                            text:
                                                StringManager.bySigningUp.tr(),
                                          ),
                                          TextSpan(
                                            text: StringManager.termsOfService
                                                .tr(),
                                            style: const TextStyle(
                                              decoration:
                                                  TextDecoration.underline,
                                              decorationColor:
                                                  ColorManager.onDark,
                                            ),
                                          ),
                                          const TextSpan(text: ' • '),
                                          TextSpan(
                                            text: StringManager.privacyPolicy_
                                                .tr(),
                                            style: const TextStyle(
                                              decoration:
                                                  TextDecoration.underline,
                                              decorationColor:
                                                  ColorManager.onDark,
                                            ),
                                          ),
                                        ],
                                      ),
                                      style: context.bodyMedium
                                          .size(13)
                                          .colorExt(ColorManager.onDark),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _ghostButton({
    required String icon,
    required String label,
    required VoidCallback onTap,
    double? size,
    bool isLoading = false,
  }) {
    return ButtonWidget(
      shadowColor: ColorManager.transparent,
      elevation: 0,
      radius: 60.r,
      height: 55,
      onPressed: onTap,
      width: ScreenUtil().screenWidth,
      padding: EdgeInsets.zero,
      paddingButton: EdgeInsets.zero,
      isLoading: isLoading,
      cLoadingColor: ColorManager.primary,
      title: SizedBox(
        width: 290.w,
        child: Row(
          children: [
            5.wBox,
            Image.asset(
              height: size?.h ?? 35.h,
              width: size?.w ?? 35.w,
              icon,
            ),
            const Spacer(),
            TextWidget(
              label,
              // The pill is a FIXED white surface on every variant, so its
              // ink is pinned dark — [textPrimary] is white on the dark
              // default (white-on-white label).
              style: context.bodyMedium.w400
                  .colorExt(ColorManager.black2)
                  .copyWith(fontSize: 16.sp),
            ),
            const Spacer(flex: 4),
          ],
        ),
      ),
      backgroundColor: ColorManager.white,
    );
  }
}
