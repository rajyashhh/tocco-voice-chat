part of 'package:general/src/features/auth/presentation/add_information/view/add_information_page.dart';

class _FormAddInfoBody extends StatelessWidget {
  const _FormAddInfoBody({required this.state});

  final AddInformationState state;

  @override
  Widget build(BuildContext context) {
    return Form(
      key: state.formKey,
      child: Padding(
        padding: context.paddingSymmetric(horizontal: 20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            5.hBox,
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                TextWidget(
                  StringManager.uploadPicture.tr(),
                  textAlign: TextAlign.center,
                  style: context.bodySmall.w500
                      .colorExt(ColorManager.secondaryText)
                      .size(14),
                ),
                _PickImageBody(
                  state: state,
                ),
              ],
            ),
            10.hBox,
            TextInputWidget(
              label: TextWidget(
                StringManager.fullName.tr(),
                style: context.bodyLarge.colorExt(
                  ColorManager.secondaryText,
                ),
              ),
              border: const UnderlineInputBorder(
                borderSide:
                BorderSide(color: ColorManager.transparent),
              ),
              enabledBorder: UnderlineInputBorder(
                // Theme-aware hairline: faint white on the dark default, the
                // legacy light gray on the light variants (a fixed gray was
                // near-invisible on the dark navy page).
                borderSide: BorderSide(color: ColorManager.cardBorderColor),
              ),
              focusedBorder: const UnderlineInputBorder(
                borderSide: BorderSide(color: Colors.green),
              ),
              errorBorder: InputBorder.none,
              StringManager.fullName.tr(),
              controller: state.name,
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return StringManager.requiredField.tr();
                }
                return null;
              },
            ),
            /*  GenderPickerWidget(
              controller: state.gender,
              onChanged: (int value) => context.read<AddInformationBloc>().add(
                    SelectedGenderEvent(
                      gender: value == 0
                          ? StringManager.male.tr()
                          : StringManager.female.tr(),
                    ),
                  ),
            ),*/
            20.hBox,
            DatePickerWidget(
              controller: state.birthday,
              dateTime: state.birthday.text.isNotEmpty
                  ? DateTime.parse(
                      Methods().convertNumerals(state.birthday.text,
                          toEnglish: true),
                    )
                  : null,
              onDateTimeChanged: (value) => context
                  .read<AddInformationBloc>()
                  .add(SelectedBirthdayEvent(dateTime: value)),
            ),
            30.hBox,
            TextWidget(
              StringManager.gender.tr(),
              style: context.bodySmall.w500
                  .colorExt(ColorManager.secondaryText)
                  .size(14),
            ),
            10.hBox,
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                Expanded(
                  child: GestureDetector(
                    onTap: () => context.read<AddInformationBloc>().add(
                          SelectedGenderEvent(
                            gender: StringManager.male.tr(),
                          ),
                        ),
                    child: state.gender.text == StringManager.male.tr()
                        ? Container(
                            decoration: BoxDecoration(
                                borderRadius: 10.radius,
                                gradient: const LinearGradient(
                                    colors: ColorManager.maleContainer)),
                            padding: context.paddingSymmetric(
                              vertical: 12,
                              horizontal: 13,
                            ),
                            child: Row(
                              children: [
                                Row(
                                  children: [
                                    ImageWidget(
                                        height: 20.h,
                                        width: 20.w,
                                        image: AssetsManager.man),
                                    5.wBox,
                                    TextWidget(
                                      StringManager.male.tr(),
                                      style: context.bodyMedium
                                          .size(15)
                                          .copyWith(color: ColorManager.onDark),
                                    ),
                                  ],
                                ),
                                const Spacer(),
                                CircleAvatar(
                                  radius: 17.r,
                                  backgroundColor: Colors.white,
                                  child: Image.asset(
                                      height: 30.h,
                                      width: 30.w,
                                      AssetsManager.manInfo),
                                ),
                              ],
                            ),
                          )
                        : Container(
                            decoration: BoxDecoration(
                              borderRadius: 10.radius,
                              color: ColorManager.inactiveColor,
                            ),
                            padding: context.paddingSymmetric(
                              vertical: 12,
                              horizontal: 13,
                            ),
                            child: Row(
                              children: [
                                Row(
                                  children: [
                                    ImageWidget(
                                        height: 20.h,
                                        width: 20.w,
                                        image: AssetsManager.man),
                                    5.wBox,
                                    TextWidget(
                                      StringManager.male.tr(),
                                      style: context.bodyMedium
                                          .size(15)
                                          .copyWith(color: ColorManager.onDark),
                                    ),
                                  ],
                                ),
                                const Spacer(),
                                CircleAvatar(
                                  radius: 17.r,
                                  backgroundColor: Colors.white,
                                  child: ColorFiltered(
                                      colorFilter: const ColorFilter.matrix([
                                        0.2126,
                                        0.7152,
                                        0.0722,
                                        0,
                                        0,
                                        0.2126,
                                        0.7152,
                                        0.0722,
                                        0,
                                        0,
                                        0.2126,
                                        0.7152,
                                        0.0722,
                                        0,
                                        0,
                                        0,
                                        0,
                                        0,
                                        1,
                                        0,
                                      ]),
                                      child: Image.asset(
                                          height: 30.h,
                                          width: 30.w,
                                          AssetsManager.manInfo)),
                                ),
                              ],
                            ),
                          ),
                  ),
                ),
                10.wBox,
                Expanded(
                  child: GestureDetector(
                      onTap: () => context.read<AddInformationBloc>().add(
                            SelectedGenderEvent(
                              gender: StringManager.female.tr(),
                            ),
                          ),
                      child: state.gender.text == StringManager.female.tr()
                          ? Container(
                              decoration: BoxDecoration(
                                  borderRadius: 10.radius,
                                  gradient: const LinearGradient(
                                      colors: ColorManager.femaleContainer)),
                              padding: context.paddingSymmetric(
                                vertical: 12,
                                horizontal: 13,
                              ),
                              child: Row(
                                children: [
                                  Row(
                                    children: [
                                      Transform(
                                        alignment: Alignment.center,
                                        transform: Matrix4.identity()
                                          ..scale(-1.0, 1.0), // انعكاس أفقي
                                        child: ImageWidget(
                                            height: 20.h,
                                            width: 20.w,
                                            image:
                                                AssetsManager.femaleIconInfo),
                                      ),
                                      5.wBox,
                                      TextWidget(
                                        StringManager.female.tr(),
                                        style: context.bodyMedium
                                            .size(15)
                                            .copyWith(
                                                color: ColorManager.onDark),
                                      ),
                                    ],
                                  ),
                                  const Spacer(),
                                  CircleAvatar(
                                    radius: 17.r,
                                    backgroundColor: Colors.white,
                                    child: ImageWidget(
                                        height: 30.h,
                                        width: 30.w,
                                        image: AssetsManager.women),
                                  ),
                                ],
                              ),
                            )
                          : Container(
                              decoration: BoxDecoration(
                                color: ColorManager.inactiveColor,
                                borderRadius: 10.radius,
                              ),
                              padding: context.paddingSymmetric(
                                vertical: 12,
                                horizontal: 13,
                              ),
                              child: Row(
                                children: [
                                  Row(
                                    children: [
                                      Transform(
                                        alignment: Alignment.center,
                                        transform: Matrix4.identity()
                                          ..scale(-1.0, 1.0), // انعكاس أفقي
                                        child: ImageWidget(
                                            height: 20.h,
                                            width: 20.w,
                                            image:
                                                AssetsManager.femaleIconInfo),
                                      ),
                                      5.wBox,
                                      TextWidget(
                                        StringManager.female.tr(),
                                        style: context.bodyMedium
                                            .size(15)
                                            .copyWith(
                                                color: ColorManager.onDark),
                                      ),
                                    ],
                                  ),
                                  const Spacer(),
                                  CircleAvatar(
                                    radius: 17.r,
                                    backgroundColor: Colors.white,
                                    child: ColorFiltered(
                                        colorFilter: const ColorFilter.matrix([
                                          0.2126,
                                          0.7152,
                                          0.0722,
                                          0,
                                          0,
                                          0.2126,
                                          0.7152,
                                          0.0722,
                                          0,
                                          0,
                                          0.2126,
                                          0.7152,
                                          0.0722,
                                          0,
                                          0,
                                          0,
                                          0,
                                          0,
                                          1,
                                          0,
                                        ]),
                                        child: Image.asset(
                                            height: 30.h,
                                            width: 30.w,
                                            AssetsManager.women)),
                                  ),
                                ],
                              ),
                            )),
                ),
              ],
            ),
            70.hBox,
            Align(
              alignment: Alignment.center,
              child: ButtonWidget(
                title: StringManager.submit.tr(),
                height: 55.h,
                width: 200.w,
                elevation: 0,
                backgroundColor: ColorManager.primary,
                isLoading: state.requestState.isLoading,
                onPressed: () {
                  if (state.image == null) {
                    Methods.showToast(context,
                        message: StringManager.pickImage.tr(), isError: true);
                  } else if (state.name.text.isEmpty) {
                    Methods.showToast(context,
                        message: StringManager.username.tr(), isError: true);
                  } else if (state.gender.text.isEmpty) {
                    Methods.showToast(context,
                        message: StringManager.selectGender.tr(), isError: true);
                  } else if (state.birthday.text.isEmpty) {
                    Methods.showToast(context,
                        message: StringManager.selectBirthday.tr(), isError: true);
                  } else if (state.birthday.text.isNotEmpty) {
                    if (Methods.isUserUnder18(DateTime.parse(
                      Methods().convertNumerals(state.birthday.text,
                          toEnglish: true),
                    ))) {
                      Methods.showToast(context,
                          message: StringManager.userUnder18.tr(),
                          isError: true);
                    } else {
                      context
                          .read<AddInformationBloc>()
                          .add(AddInformationEvent(context: context));
                    }
                  }
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
