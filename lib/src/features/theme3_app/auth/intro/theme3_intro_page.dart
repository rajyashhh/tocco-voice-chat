import 'dart:io';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/presentation/auth_platform/bloc/auth_platform_bloc.dart';
import 'package:general/src/core/widgets/update_dialog.dart';
import 'package:general/src/features/auth/presentation/splash/config_app/config_app_bloc.dart';

/// Theme3 (NEXO) Intro Page — light lavender background, centered brand logo,
/// pink gradient CTA pill for Google sign-in, circular secondary buttons for
/// phone/Apple/Huawei. Same bloc wiring as [Theme2IntroPage] — visuals only.
class Theme3IntroPage extends StatefulWidget {
  final String? error;
  const Theme3IntroPage({super.key, this.error});

  @override
  State<Theme3IntroPage> createState() => _Theme3IntroPageState();
}

class _Theme3IntroPageState extends State<Theme3IntroPage> {
  @override
  void initState() {
    super.initState();

    if (widget.error != null && (widget.error ?? '').isNotEmpty) {
      Future.delayed(
        const Duration(milliseconds: 800),
        () {
          if (mounted) {
            showDialog(
              context: context,
              builder: (context) => AnimatedDialog(
                // 505 single-session teardown — a device sign-out, NOT a ban.
                title: StringManager.loggedInFromAnotherDevice.tr(),
                description: StringManager.loggedInFromAnotherDeviceDesc.tr(),
                isHideConfirm: true,
              ),
            );
          }
        },
      );
    }
  }

  @override
  Widget build(BuildContext context) {
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
        backgroundColor: ColorManager.theme3Background,
        body: SafeArea(
          child: Column(
            children: [
              16.hBox,

              // ─── Logo + App Name ─────────────
              Expanded(
                flex: 3,
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(28.r),
                      child: Image.asset(
                        AssetsManager.logo,
                        height: 110.h,
                        width: 110.w,
                        fit: BoxFit.cover,
                      ),
                    ),
                    16.hBox,
                    TextWidget(
                      ConstantsManager.appDisplayName,
                      style: context.bodyMedium.bold
                          .size(28)
                          .colorExt(ColorManager.theme3TextPrimary),
                    ),
                    8.hBox,
                    TextWidget(
                      StringManager.playStreamConnect,
                      style: context.bodyMedium.w400.size(16).colorExt(
                            ColorManager.theme3TextSecondary,
                          ),
                    ),
                  ],
                ),
              ),

              // ─── Auth Buttons ────────────────
              Expanded(
                flex: 2,
                child: Padding(
                  padding: EdgeInsets.symmetric(horizontal: 32.w),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      // Google button (primary gradient CTA pill)
                      BlocBuilder<AuthPlatformBloc, AuthPlatformState>(
                        bloc: di<AuthPlatformBloc>(),
                        buildWhen: (prev, curr) =>
                            prev.requestStateGoogle != curr.requestStateGoogle,
                        builder: (context, state) {
                          return _ctaButton(
                            icon: AssetsManager.google_,
                            label: StringManager.signInWithGoogle,
                            isLoading: state.requestStateGoogle.isLoading,
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
                      30.hBox,

                      // Bottom icon row: Phone + Apple + Huawei
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          _circleAuthButton(
                            icon: AssetsManager.phone,
                            onTap: () => context.pushNamedRoute(Routes.login),
                          ),
                          if (Platform.isIOS) ...[
                            24.wBox,
                            BlocBuilder<AuthPlatformBloc, AuthPlatformState>(
                              bloc: di<AuthPlatformBloc>(),
                              buildWhen: (prev, curr) =>
                                  prev.requestStateApple !=
                                  curr.requestStateApple,
                              builder: (context, state) {
                                return _circleAuthButton(
                                  icon: AssetsManager.apple,
                                  isLoading: state.requestStateApple.isLoading,
                                  onTap: () {
                                    di<AuthPlatformBloc>().add(
                                      SignInAppleEvent(context: context),
                                    );
                                  },
                                );
                              },
                            ),
                          ],
                          if (ConstantsManager.devicePlatform ==
                              StringManager.huawei) ...[
                            24.wBox,
                            BlocBuilder<AuthPlatformBloc, AuthPlatformState>(
                              bloc: di<AuthPlatformBloc>(),
                              buildWhen: (prev, curr) =>
                                  prev.requestStateHuawei !=
                                  curr.requestStateHuawei,
                              builder: (context, state) {
                                return _circleAuthButton(
                                  icon: AssetsManager.huawei,
                                  isLoading:
                                      state.requestStateHuawei.isLoading,
                                  onTap: () {
                                    di<AuthPlatformBloc>().add(
                                      SignInHuaweiEvent(context: context),
                                    );
                                  },
                                );
                              },
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
              ),

              // ─── Terms & Privacy ─────────────
              Padding(
                padding: EdgeInsets.only(bottom: 16.h),
                child: GestureDetector(
                  onTap: () => context.pushNamedRoute(Routes.privacy),
                  child: Text.rich(
                    textAlign: TextAlign.center,
                    TextSpan(
                      children: [
                        TextSpan(text: StringManager.bySigningUp.tr()),
                        TextSpan(
                          text: StringManager.termsOfService.tr(),
                          style: const TextStyle(
                            decoration: TextDecoration.underline,
                            decorationColor: ColorManager.theme3TextPrimary,
                          ),
                        ),
                        const TextSpan(text: ' • '),
                        TextSpan(
                          text: StringManager.privacyPolicy_.tr(),
                          style: const TextStyle(
                            decoration: TextDecoration.underline,
                            decorationColor: ColorManager.theme3TextPrimary,
                          ),
                        ),
                      ],
                    ),
                    style: context.bodyMedium.size(12).colorExt(
                          ColorManager.theme3TextSecondary,
                        ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _ctaButton({
    required String icon,
    required String label,
    required VoidCallback onTap,
    bool isLoading = false,
  }) {
    return GestureDetector(
      onTap: isLoading ? null : onTap,
      child: Container(
        height: 56.h,
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            colors: ColorManager.theme3CtaGradient,
            begin: AlignmentDirectional.centerStart,
            end: AlignmentDirectional.centerEnd,
          ),
          borderRadius: BorderRadius.circular(30.r),
          boxShadow: [
            BoxShadow(
              color: ColorManager.theme3Cta.withValues(alpha: 0.35),
              blurRadius: 16,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: isLoading
            ? Center(
                child: SizedBox(
                  height: 22.h,
                  width: 22.w,
                  child: const CircularProgressIndicator(
                    strokeWidth: 2,
                    color: ColorManager.white,
                  ),
                ),
              )
            : Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  ColoredBox(
                    color: ColorManager.transparent,
                    child: Container(
                      height: 26.h,
                      width: 26.w,
                      decoration: const BoxDecoration(
                        color: ColorManager.white,
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Image.asset(icon, height: 16.h, width: 16.w),
                      ),
                    ),
                  ),
                  10.wBox,
                  TextWidget(
                    label,
                    style: context.bodyMedium.w600
                        .size(15)
                        .colorExt(ColorManager.white),
                  ),
                ],
              ),
      ),
    );
  }

  Widget _circleAuthButton({
    required String icon,
    required VoidCallback onTap,
    bool isLoading = false,
  }) {
    return GestureDetector(
      onTap: isLoading ? null : onTap,
      child: Container(
        height: 56.h,
        width: 56.w,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: ColorManager.theme3Card,
          border: Border.all(
            color: ColorManager.theme3TextSecondary.withValues(alpha: 0.15),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.06),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: isLoading
            ? Center(
                child: SizedBox(
                  height: 20.h,
                  width: 20.w,
                  child: const CircularProgressIndicator(
                    strokeWidth: 2,
                    color: ColorManager.theme3Cta,
                  ),
                ),
              )
            : Center(
                child: Image.asset(icon, height: 28.h, width: 28.w),
              ),
      ),
    );
  }
}
