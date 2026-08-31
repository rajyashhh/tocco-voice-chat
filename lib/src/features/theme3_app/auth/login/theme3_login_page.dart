import 'dart:io';

import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/register_dialog.dart';
import 'package:general/src/features/auth/auth.dart';
import 'package:general/src/features/auth/presentation/auth_platform/bloc/auth_platform_bloc.dart';

/// Theme3 (NEXO) Login Page — light lavender background, white rounded-2xl
/// card, pink gradient submit pill. Same [LoginBloc]/[ChangeCountryEvent]
/// wiring as [Theme2LoginPage] — visuals only.
class Theme3LoginPage extends StatelessWidget {
  const Theme3LoginPage({super.key});

  void _openCountryPicker(BuildContext context) {
    showCountryPicker(
      context: context,
      showSearch: true,
      countryListTheme: const CountryListThemeData(
        textStyle: TextStyle(color: ColorManager.theme3TextPrimary),
        searchTextStyle: TextStyle(color: ColorManager.theme3TextPrimary),
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(30),
          topRight: Radius.circular(30),
        ),
        padding: EdgeInsets.only(top: 10),
      ),
      showPhoneCode: true,
      onSelect: (Country country) {
        context.read<LoginBloc>().add(ChangeCountryEvent(country));
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorManager.theme3Background,
      body: SafeArea(
        child: BlocConsumer<LoginBloc, LoginState>(
          listener: (context, state) {
            if (state.showRegisterDialog) {
              showDialog(
                context: context,
                builder: (_) => RegisterAnimatedDialog(
                  phoneNumber: state.phoneControllerNew,
                  countryName: state.selectedCountry?.phoneCode ?? "",
                  countryCode: state.selectedCountry?.countryCode ?? "",
                ),
              ).then((_) {
                context.read<LoginBloc>().add(const ResetLoginStateEvent());
              });
            }
          },
          builder: (context, state) {
            if (state.selectedCountry == null) {
              final defaultCountry = Country.tryParse('SA');
              if (defaultCountry != null) {
                WidgetsBinding.instance.addPostFrameCallback((_) {
                  if (context.mounted) {
                    context.read<LoginBloc>().add(
                          ChangeCountryEvent(defaultCountry),
                        );
                  }
                });
              }
            }
            return BlocBuilder<LoginBloc, LoginState>(
              buildWhen: (prev, curr) =>
                  prev.selectedCountry != curr.selectedCountry ||
                  prev.isFoundAccount != curr.isFoundAccount ||
                  prev.isFormValid != curr.isFormValid ||
                  prev.isFormValidPhoneFound != curr.isFormValidPhoneFound ||
                  prev.requestState != curr.requestState ||
                  prev.requestStateCheckPhone != curr.requestStateCheckPhone ||
                  prev.isPassword != curr.isPassword ||
                  prev.suffixIcon != curr.suffixIcon,
              builder: (context, state) {
                return Form(
                  key: state.formKey,
                  child: SingleChildScrollView(
                    padding: EdgeInsets.symmetric(horizontal: 20.w),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        // Back arrow (top right)
                        Align(
                          alignment: Alignment.topRight,
                          child: Padding(
                            padding: EdgeInsets.only(top: 8.h),
                            child: IconButton(
                              onPressed: () => Navigator.maybePop(context),
                              icon: Icon(
                                Icons.arrow_forward_ios,
                                color: ColorManager.theme3TextSecondary,
                                size: 20.sp,
                              ),
                            ),
                          ),
                        ),
                        16.hBox,

                        // White rounded-2xl card
                        Container(
                          padding: EdgeInsets.symmetric(
                              horizontal: 20.w, vertical: 28.h),
                          decoration: BoxDecoration(
                            color: ColorManager.theme3Card,
                            borderRadius: BorderRadius.circular(24.r),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.06),
                                blurRadius: 20,
                                offset: const Offset(0, 8),
                              ),
                            ],
                          ),
                          child: Column(
                            children: [
                              // App Logo
                              ClipRRect(
                                borderRadius: BorderRadius.circular(20.r),
                                child: Image.asset(
                                  AssetsManager.logo,
                                  height: 90.h,
                                  width: 90.w,
                                  fit: BoxFit.cover,
                                ),
                              ),
                              28.hBox,

                              // Phone ID field
                              _Theme3InputField(
                                hint: StringManager.pleaseInterYourPhone.tr(),
                                controller: state.phoneControllerNew,
                                keyboardType: TextInputType.phone,
                                prefix: GestureDetector(
                                  onTap: () => _openCountryPicker(context),
                                  child: Container(
                                    padding: EdgeInsets.only(
                                        left: 12.w, right: 8.w),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Text(
                                          state.selectedCountry?.flagEmoji ??
                                              '🇸🇦',
                                          style: TextStyle(fontSize: 18.sp),
                                        ),
                                        4.wBox,
                                        Text(
                                          '+${state.selectedCountry?.phoneCode ?? '966'}',
                                          style: TextStyle(
                                            color:
                                                ColorManager.theme3TextPrimary,
                                            fontSize: 14.sp,
                                            fontWeight: FontWeight.w500,
                                          ),
                                        ),
                                        Icon(
                                          Icons.keyboard_arrow_down_rounded,
                                          color: ColorManager
                                              .theme3TextSecondary,
                                          size: 18.sp,
                                        ),
                                        8.wBox,
                                        Container(
                                          height: 24.h,
                                          width: 1,
                                          color: Colors.grey
                                              .withValues(alpha: 0.3),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                                onChanged: (value) {
                                  state.isFoundAccount ?? false
                                      ? context.read<LoginBloc>().add(
                                            const UpdateFormValidationEvent(),
                                          )
                                      : context.read<LoginBloc>().add(
                                            const UpdateFormValidationEventPhoneFound(),
                                          );
                                },
                                validator: (value) {
                                  if (value == null || value.isEmpty) {
                                    return StringManager.requiredField.tr();
                                  }
                                  if (!Methods().isValidPhoneNumber(
                                    state.selectedCountry?.countryCode ??
                                        "SA",
                                    value,
                                  )) {
                                    return StringManager.phoneValidator.tr();
                                  }
                                  return null;
                                },
                              ),
                              16.hBox,

                              // Password field (only visible after phone is verified)
                              if (state.isFoundAccount ?? false) ...[
                                _Theme3InputField(
                                  hint: StringManager.password.tr(),
                                  controller: state.passwordController,
                                  isPassword: state.isPassword,
                                  suffixIcon: state.suffixIcon,
                                  onChanged: (value) {
                                    context.read<LoginBloc>().add(
                                          const UpdateFormValidationEvent(),
                                        );
                                  },
                                  validator: (value) {
                                    if (value == null || value.isEmpty) {
                                      return StringManager.requiredField.tr();
                                    }
                                    return null;
                                  },
                                  onSuffixTap: () =>
                                      context.read<LoginBloc>().add(
                                            const TogglePasswordEvent(),
                                          ),
                                ),
                                16.hBox,
                              ],
                              24.hBox,

                              // Login button — pink gradient submit pill
                              SizedBox(
                                width: double.infinity,
                                height: 52.h,
                                child: DecoratedBox(
                                  decoration: BoxDecoration(
                                    gradient: const LinearGradient(
                                      colors: ColorManager.theme3CtaGradient,
                                      begin: AlignmentDirectional.centerStart,
                                      end: AlignmentDirectional.centerEnd,
                                    ),
                                    borderRadius:
                                        BorderRadius.circular(28.r),
                                  ),
                                  child: ElevatedButton(
                                    onPressed: () {
                                      if (state.isFoundAccount ?? false) {
                                        if (state.isFormValid != false) {
                                          context.read<LoginBloc>().add(
                                                LoginWithPhoneEvent(
                                                  context: context,
                                                  isLogin: true,
                                                ),
                                              );
                                        }
                                      } else {
                                        if (state.isFormValidPhoneFound !=
                                            false) {
                                          context.read<LoginBloc>().add(
                                                const CheckPhoneEvent(),
                                              );
                                        }
                                      }
                                    },
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor:
                                          ColorManager.transparent,
                                      shadowColor: ColorManager.transparent,
                                      foregroundColor: ColorManager.white,
                                      elevation: 0,
                                      shape: RoundedRectangleBorder(
                                        borderRadius:
                                            BorderRadius.circular(28.r),
                                      ),
                                    ),
                                    child: state.requestState.isLoading ||
                                            state.requestStateCheckPhone
                                                .isLoading
                                        ? SizedBox(
                                            height: 20.h,
                                            width: 20.w,
                                            child:
                                                const CircularProgressIndicator(
                                              strokeWidth: 2,
                                              color: ColorManager.white,
                                            ),
                                          )
                                        : Text(
                                            state.isFoundAccount ?? false
                                                ? StringManager.login.tr()
                                                : StringManager.next.tr(),
                                            style: TextStyle(
                                              fontSize: 16.sp,
                                              fontWeight: FontWeight.w600,
                                              color: ColorManager.white,
                                            ),
                                          ),
                                  ),
                                ),
                              ),
                              32.hBox,

                              // Social login icons row
                              Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  _SocialCircleButton(
                                    child: Image.asset(
                                      AssetsManager.google_,
                                      height: 22.h,
                                      width: 22.w,
                                    ),
                                    onTap: () {
                                      di<AuthPlatformBloc>().add(
                                        SignInGoogleEvent(context: context),
                                      );
                                    },
                                  ),
                                  if (Platform.isIOS) ...[
                                    20.wBox,
                                    _SocialCircleButton(
                                      child: Image.asset(
                                        AssetsManager.appleIcon,
                                        height: 22.h,
                                        width: 22.w,
                                        color: ColorManager.black,
                                      ),
                                      onTap: () {
                                        di<AuthPlatformBloc>().add(
                                          SignInAppleEvent(context: context),
                                        );
                                      },
                                    ),
                                  ],
                                  if (ConstantsManager.devicePlatform ==
                                      StringManager.huawei) ...[
                                    20.wBox,
                                    _SocialCircleButton(
                                      child: Image.asset(
                                        AssetsManager.huawei,
                                        height: 22.h,
                                        width: 22.w,
                                      ),
                                      onTap: () {
                                        di<AuthPlatformBloc>().add(
                                          SignInHuaweiEvent(context: context),
                                        );
                                      },
                                    ),
                                  ],
                                ],
                              ),
                            ],
                          ),
                        ),
                        16.hBox,
                      ],
                    ),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }
}

/// Rounded input field - Theme3 style (light lavender fill).
class _Theme3InputField extends StatelessWidget {
  final String hint;
  final TextEditingController? controller;
  final TextInputType? keyboardType;
  final bool isPassword;
  final IconData? suffixIcon;
  final Widget? prefix;
  final ValueChanged<String>? onChanged;
  final FormFieldValidator<String>? validator;
  final VoidCallback? onSuffixTap;

  const _Theme3InputField({
    required this.hint,
    this.controller,
    this.keyboardType,
    this.isPassword = false,
    this.suffixIcon,
    this.prefix,
    this.onChanged,
    this.validator,
    this.onSuffixTap,
  });

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      obscureText: isPassword,
      onChanged: onChanged,
      validator: validator,
      style: TextStyle(
          color: ColorManager.theme3TextPrimary, fontSize: 15.sp),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: TextStyle(
          color: ColorManager.theme3TextSecondary.withValues(alpha: 0.6),
          fontSize: 14.sp,
        ),
        filled: true,
        fillColor: ColorManager.theme3Background,
        contentPadding:
            EdgeInsets.symmetric(horizontal: 20.w, vertical: 16.h),
        prefixIcon: prefix,
        prefixIconConstraints:
            const BoxConstraints(minHeight: 0, minWidth: 0),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14.r),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14.r),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14.r),
          borderSide: BorderSide(
            color: ColorManager.theme3Cta.withValues(alpha: 0.5),
            width: 1.5,
          ),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14.r),
          borderSide: const BorderSide(color: Colors.red, width: 1),
        ),
        suffixIcon: suffixIcon != null
            ? GestureDetector(
                onTap: onSuffixTap,
                child: Icon(
                  suffixIcon,
                  color: ColorManager.theme3TextSecondary,
                  size: 20.sp,
                ),
              )
            : null,
      ),
    );
  }
}

/// Circular social login button - Theme3 style.
class _SocialCircleButton extends StatelessWidget {
  final Widget? child;
  final VoidCallback onTap;

  const _SocialCircleButton({required this.child, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 52.h,
        width: 52.w,
        decoration: const BoxDecoration(
          shape: BoxShape.circle,
          color: ColorManager.theme3Background,
        ),
        child: Center(child: child),
      ),
    );
  }
}