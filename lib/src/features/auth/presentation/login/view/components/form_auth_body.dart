part of 'package:general/src/features/auth/presentation/login/view/login_page.dart';

class _FormAuthBody extends StatelessWidget {
  const _FormAuthBody();

  void _openCountryPicker(BuildContext context) {
    showCountryPicker(
      context: context,
      showSearch: true,
      countryListTheme: CountryListThemeData(
        textStyle: TextStyle(color: ColorManager.textPrimary),
        searchTextStyle: TextStyle(color: ColorManager.textPrimary),
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(30),
          topRight: Radius.circular(30),
        ),
        padding: const EdgeInsets.only(top: 10),
      ),
      showPhoneCode: true,
      onSelect: (Country country) {
        context.read<LoginBloc>().add(ChangeCountryEvent(country));
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<LoginBloc, LoginState>(
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
              // Fill exactly the VIEWPORT (not a fixed 0.86 of the raw screen
              // height, which overflowed past the app bar + safe area and
              // clipped the agreement line at the bottom). The column still
              // stretches so the Spacer pins the agreement to the bottom, and
              // it scrolls when the keyboard shrinks the viewport.
              child: LayoutBuilder(
                builder: (context, constraints) => SingleChildScrollView(
                  child: ConstrainedBox(
                    constraints: BoxConstraints(minHeight: constraints.maxHeight),
                    child: IntrinsicHeight(
                      child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      10.hBox,
                      Image.asset(
                        fit: BoxFit.contain,
                        AssetsManager.logo,
                        width: MediaQuery.of(context).size.width * 0.230,
                      ),
                      10.hBox,
                      TextWidget(
                        StringManager.hiTemp.tr(),
                        style: context.bodyLarge.size(25).w600,
                      ),
                      5.hBox,
                      TextWidget(
                        StringManager.loginTel.tr(),
                        style: context.bodyLarge
                            .size(16)
                            .colorExt(ColorManager.textPrimary),
                      ),
                      40.hBox,
                      GestureDetector(
                        onTap: () => _openCountryPicker(context),
                        child: Container(
                          decoration: BoxDecoration(
                            border: Border(
                              bottom: BorderSide(
                                // Theme-aware hairline: readable on the dark
                                // default AND the light variants.
                                color: ColorManager.cardBorderColor,
                              ),
                            ),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  TextWidget(
                                    StringManager.codeCountry.tr(),
                                    style: context.bodyLarge
                                        .size(13)
                                        .colorExt(ColorManager.textPrimary),
                                  ),
                                  TextWidget(
                                    " * ",
                                    style: context.bodyLarge
                                        .size(10)
                                        .colorExt(ColorManager.bColor),
                                  ),
                                ],
                              ),
                              5.hBox,
                              Row(
                                children: [
                                  state.selectedCountry != null
                                      ? TextWidget(
                                          " ${state.selectedCountry?.name}   (+${state.selectedCountry?.phoneCode})",
                                          style: context.bodyMedium.size(16),
                                        )
                                      : TextWidget(
                                          " ${StringManager.pleaseInputCountryName.tr()}",
                                          style: context.bodyLarge
                                              .size(16)
                                              .copyWith(
                                                color: ColorManager.textPrimary
                                                    .withValues(alpha: 0.45),
                                              ),
                                        ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                      30.hBox,
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              TextWidget(
                                StringManager.phone.tr(),
                                style: context.bodyLarge
                                    .size(13)
                                    .colorExt(ColorManager.textPrimary),
                              ),
                              TextWidget(
                                " * ",
                                style: context.bodyLarge
                                    .size(10)
                                    .colorExt(ColorManager.bColor),
                              ),
                            ],
                          ),
                          TextInputWidget(
                            StringManager.pleaseInterYourPhone.tr(),
                            hintStyle: context.bodyMedium.size(16).copyWith(
                                  color: ColorManager.textPrimary.withValues(
                                    alpha: 0.45,
                                  ),
                                ),
                            textColor: ColorManager.textPrimary,
                            contentPadding: EdgeInsets.zero,
                            keyboardType: TextInputType.phone,
                            onChanged: (value) {
                              state.isFoundAccount ?? false
                                  ? context
                                      .read<LoginBloc>()
                                      .add(const UpdateFormValidationEvent())
                                  : context.read<LoginBloc>().add(
                                        const UpdateFormValidationEventPhoneFound(),
                                      );
                            },
                            border: UnderlineInputBorder(
                              borderSide: BorderSide(
                                color: ColorManager.cardBorderColor,
                              ),
                            ),
                            enabledBorder: UnderlineInputBorder(
                              borderSide: BorderSide(
                                color: ColorManager.cardBorderColor,
                              ),
                            ),
                            focusedBorder: const UnderlineInputBorder(
                              borderSide: BorderSide(color: Colors.green),
                            ),
                            errorBorder: const UnderlineInputBorder(
                              borderSide: BorderSide(color: Colors.red),
                            ),
                            controller: state.phoneControllerNew,
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return StringManager.requiredField.tr();
                              }
                              if (!Methods().isValidPhoneNumber(
                                  state.selectedCountry?.countryCode ?? "SA",
                                  value)) {
                                return StringManager.phoneValidator.tr();
                              }
                              return null;
                            },
                          ),
                        ],
                      ),
                      20.hBox,
                      if (state.isFoundAccount == true)
                        Column(
                          children: [
                            Row(
                              children: [
                                TextWidget(
                                  StringManager.password.tr(),
                                  style: context.bodyLarge
                                      .size(13)
                                      .colorExt(ColorManager.textPrimary),
                                ),
                                TextWidget(
                                  " * ",
                                  style: context.bodyLarge
                                      .size(10)
                                      .colorExt(ColorManager.bColor),
                                ),
                              ],
                            ),
                            TextInputWidget(
                              border: const UnderlineInputBorder(
                                borderSide:
                                    BorderSide(color: ColorManager.transparent),
                              ),
                              errorBorder: const UnderlineInputBorder(
                                borderSide: BorderSide(color: Colors.red),
                              ),
                              title: StringManager.password.tr(),
                              StringManager.password.tr(),
                              hintStyle: context.bodyMedium.size(16).copyWith(
                                    color: ColorManager.textPrimary.withValues(
                                      alpha: 0.45,
                                    ),
                                  ),
                              controller: state.passwordController,
                              suffixIcon: state.suffixIcon,
                              suffixIconConstraints: const BoxConstraints(
                                  minWidth: 20,
                                  maxWidth: 20,
                                  minHeight: 20,
                                  maxHeight: 20),
                              suffixColor: ColorManager.grey,
                              isPassword: state.isPassword,
                              contentPadding: EdgeInsets.zero,
                              onChanged: (value) {
                                state.isFoundAccount ?? false
                                    ? context
                                        .read<LoginBloc>()
                                        .add(const UpdateFormValidationEvent())
                                    : context.read<LoginBloc>().add(
                                          const UpdateFormValidationEventPhoneFound(),
                                        );
                              },
                              enabledBorder: UnderlineInputBorder(
                                borderSide: BorderSide(
                                  color: ColorManager.cardBorderColor,
                                ),
                              ),
                              focusedBorder: const UnderlineInputBorder(
                                borderSide: BorderSide(color: Colors.green),
                              ),
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return StringManager.requiredField.tr();
                                }
                                return null;
                              },
                              onPressed: () => context
                                  .read<LoginBloc>()
                                  .add(const TogglePasswordEvent()),
                            ),
                          ],
                        ),
                      TextButtonWidget(
                        onTap: () =>
                            context.pushNamedRoute(Routes.recoverPassword),
                        content: TextWidget(
                          StringManager.recoverPassword_.tr(),
                          style: context.bodyLarge
                              .colorExt(ColorManager.textPrimary)
                              .size(15)
                              .w500,
                        ),
                      ),
                      50.hBox,
                      state.isFoundAccount ?? false
                          ? Align(
                              alignment: Alignment.center,
                              child: InkWell(
                                onDoubleTap: () {},
                                child: ButtonWidget(
                                  title: StringManager.login.tr(),
                                  height: 40.h,
                                  width: 200.w,
                                  padding: EdgeInsets.zero,
                                  paddingButton: EdgeInsets.zero,
                                  radius: 30.r,
                                  backgroundColor: state.isFormValid ?? false
                                      ? ColorManager.primary
                                      : Colors.grey.shade400,
                                  isLoading: state.requestState.isLoading,
                                  onPressed: () {
                                    state.isFormValid != false
                                        ? context.read<LoginBloc>().add(
                                              LoginWithPhoneEvent(
                                                context: context,
                                                isLogin: true,
                                              ),
                                            )
                                        : () {};
                                  },
                                ),
                              ),
                            )
                          : Align(
                              alignment: Alignment.center,
                              child: InkWell(
                                onDoubleTap: () {},
                                child: ButtonWidget(
                                  title: StringManager.next.tr(),
                                  height: 40.h,
                                  width: 200.w,
                                  padding: EdgeInsets.zero,
                                  paddingButton: EdgeInsets.zero,
                                  radius: 30.r,
                                  backgroundColor:
                                      state.isFormValidPhoneFound ?? false
                                          ? ColorManager.primary
                                          : Colors.grey.shade400,
                                  isLoading:
                                      state.requestStateCheckPhone.isLoading,
                                  onPressed: () {
                                    state.isFormValidPhoneFound != false
                                        ? context.read<LoginBloc>().add(
                                              const CheckPhoneEvent(),
                                            )
                                        : () {};
                                  },
                                ),
                              ),
                            ),
                      const Spacer(),
                      Align(
                        child: InkWell(
                          onTap: () {
                            context.pushNamedRoute(Routes.privacy);
                          },
                          child: Text.rich(
                            textAlign: TextAlign.center,
                            TextSpan(
                              text: StringManager.agreeLogin.tr(),
                              style: context.bodyLarge
                                  .size(13)
                                  .colorExt(ColorManager.secondaryText),
                              children: [
                                TextSpan(
                                  text: StringManager.userAgreementLogin.tr(),
                                  style: context.bodyLarge
                                      .size(13)
                                      .colorExt(
                                        ColorManager.primary,
                                      )
                                      .w600,
                                ),
                                TextSpan(
                                  text: StringManager.andLogin.tr(),
                                  style: context.bodyLarge
                                      .size(13)
                                      .colorExt(ColorManager.secondaryText),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      10.hBox,
                    ],
                      ),
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }
}
