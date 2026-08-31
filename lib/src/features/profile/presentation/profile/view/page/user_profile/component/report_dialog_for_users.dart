import 'dart:io';

import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/dotted_container.dart';
import 'package:general/src/features/auth/presentation/add_information/bloc/add_information_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/feed_back/feed_back_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/feed_back/feed_back_event.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/feed_back/feed_back_state.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/user_report_bloc/user_report_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/user_report_bloc/user_report_event.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/user_report_bloc/user_report_state.dart';

class ReportDialogForUsers extends StatefulWidget {
  final String userId;
  const ReportDialogForUsers({required this.userId, super.key});

  @override
  State<ReportDialogForUsers> createState() => _ReportDialogForUsersState();
}

class _ReportDialogForUsersState extends State<ReportDialogForUsers> {
  final FeedbackBloc _bloc = di<FeedbackBloc>();
  AddInformationBloc addImageBloc = di<AddInformationBloc>();
  final key = GlobalKey<FormState>();
  String? selectedImagePath;

  @override
  void initState() {
    di<UserReportBloc>().add(
      const UserReportRemoveImageEvent(),
    );
    addImageBloc = di<AddInformationBloc>();
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<UserReportBloc, UserReportState>(
      bloc: di<UserReportBloc>(),
      listener: (context, state) {
        if (state.requestStateReport.isLoading) {
        } else if (state.requestStateReport.isLoaded) {
          Methods.showToast(
            context,
            message: state.successReport ?? "",
          );
          state.descController.clear();
          Navigator.pop(context);
          return;
        } else if (state.requestStateReport.isError) {
          Methods.showToast(
            context,
            message: NetworkExceptions.getErrorMessage(state.errorMsgReport!),
          );
          return;
        }
      },
      builder: (context, state) {
        return Scaffold(
          appBar: AppBarWidget(
            title: StringManager.report.tr(),
            actions: [
              ButtonWidget(
                title: StringManager.save.tr(),
                width: 100.w,
                height: 30.h,
                backgroundColor: state.isFormValid
                    ? ColorManager.primary
                    : ColorManager.surfaceCardColor,
                titleColor: ColorManager.grey2,
                onPressed: () {
                  if (key.currentState?.validate() == false) {
                    Methods.showToast(
                      context,
                      message: StringManager.enterYorFullData.tr(),
                    );
                  } else if (state.descController.text.length < 10) {
                    Methods.showToast(
                      context,
                      message: StringManager.yourDescriptionLessThan.tr(),
                    );
                  } else {
                    if (state.isFormValid) {
                      if (key.currentState!.validate()) {
                        key.currentState!.save();
                        di<UserReportBloc>().add(
                          UserReportEvent(
                              reportContent: state.descController.text,
                              typeReport: _bloc.state.selectedProblem,
                              id: widget.userId,
                              image: selectedImagePath == null ||
                                      (selectedImagePath ?? '').isEmpty
                                  ? null
                                  : File(selectedImagePath!)),
                        );
                      }
                    }
                  }
                },
              ),
            ],
          ),
          body: Container(
            height: ScreenUtil().screenHeight,
            color: ColorManager.surfaceCardColor,
            padding: context.paddingSymmetric(horizontal: 15, vertical: 15),
            child: SingleChildScrollView(
              child: Form(
                key: key,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    ProblemTypeBody(bloc: _bloc),
                    20.hBox,
                    Column(
                      children: [
                        Row(
                          children: [
                            TextWidget(
                              StringManager.description.tr(),
                              style: context.bodyMedium.bold,
                            ),
                            TextWidget(
                              "*",
                              style: context.bodyMedium.bold
                                  .colorExt(ColorManager.redAccount),
                            ),
                          ],
                        ),
                        10.hBox,
                        TextInputWidget(
                          onChanged: (value) {
                            String removeDiacritics(String input) {
                              final RegExp diacriticRegex = RegExp(
                                  r'[ؐ-ًؚ-ٰٟۖ-ۭ]');
                              return input.replaceAll(diacriticRegex, '');
                            }

                            final cleaned = removeDiacritics(value!);
                            if (value != cleaned) {
                              state.descController.text = cleaned;
                              state.descController.selection =
                                  TextSelection.fromPosition(
                                TextPosition(offset: cleaned.length),
                              );
                            }
                            di<UserReportBloc>()
                                .add(const UpdateFormValidationEvent());
                          },
                          StringManager.feedbackDescription.tr(),
                          enabledBorder: OutlineInputBorder(
                              borderRadius: 5.radius,
                              borderSide: const BorderSide(
                                  color: ColorManager.transparent)),
                          focusedBorder: OutlineInputBorder(
                              borderRadius: 5.radius,
                              borderSide: const BorderSide(
                                  color: ColorManager.transparent)),
                          border: OutlineInputBorder(
                              borderRadius: 5.radius,
                              borderSide: const BorderSide(
                                  color: ColorManager.transparent)),
                          fillColor: ColorManager.highlightColor,
                          controller: state.descController,
                          maxLines: 10,
                          minLines: 10,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return StringManager.requiredField.tr();
                            }
                            return null;
                          },
                          hintStyle: context.bodyMedium
                              .colorExt(ColorManager.grey2)
                              .size(16)
                              .copyWith(
                                  fontFamily: "NotoNaskhArabic", height: 1.8.h),
                        ),
                      ],
                    ),
                    20.hBox,
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        TextWidget(
                          StringManager.screenshot.tr(),
                          style: context.bodyMedium.bold,
                        ),
                        InkWell(
                          onTap: () {
                            if (selectedImagePath == null ||
                                (selectedImagePath ?? '').isEmpty) {
                              di<UserReportBloc>().add(
                                const UserReportPickImageEvent(
                                    ImageSource.gallery),
                              );
                            }
                          },
                          radius: 10,
                          child: BlocBuilder<UserReportBloc, UserReportState>(
                              bloc: di<UserReportBloc>(),
                              buildWhen: (prev, curr) =>
                                  prev.imagePath != curr.imagePath,
                              builder: (context, state) {
                                selectedImagePath = state.imagePath;
                                return DashedBorderContainer(
                                  width: 80.w,
                                  height: 80.h,
                                  borderRadius: 5,
                                  borderColor: ColorManager.supportBorderColor,
                                  strokeWidth: 2,
                                  child: Container(
                                    width: 80.w,
                                    height: 80.h,
                                    decoration: BoxDecoration(
                                      color: ColorManager.highlightColor,
                                      image: selectedImagePath != null &&
                                              (selectedImagePath ?? '')
                                                  .isNotEmpty
                                          ? DecorationImage(
                                              fit: BoxFit.cover,
                                              image: FileImage(
                                                File(selectedImagePath ?? ''),
                                              ),
                                            )
                                          : null,
                                      borderRadius: 10.radius,
                                    ),
                                    child: (selectedImagePath ?? "") == ""
                                        ? Column(
                                            mainAxisAlignment:
                                                MainAxisAlignment.center,
                                            children: [
                                              Image.asset(
                                                AssetsManager.addGallery,
                                                color: Colors.black,
                                                width: 25.w,
                                              ),
                                            ],
                                          )
                                        : const SizedBox(),
                                  ),
                                );
                              }),
                        ),
                      ],
                    ),
                    20.hBox,
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}

class ProblemTypeBody extends StatelessWidget {
  final FeedbackBloc bloc;

  const ProblemTypeBody({super.key, required this.bloc});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<FeedbackBloc, FeedBackState>(
      bloc: bloc,
      buildWhen: (prev, curr) =>
          prev.problemTypes != curr.problemTypes ||
          prev.selectedProblem != curr.selectedProblem,
      builder: (context, state) {
        return SizedBox(
          height: 100.h,
          child: GridView.builder(
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2, childAspectRatio: 4.2),
            padding: EdgeInsets.zero,
            itemCount: state.problemTypes.length,
            itemBuilder: (context, index) {
              final problemType = bloc.state.problemTypes[index].tr();
              final isSelected = bloc.state.selectedProblem.tr() == problemType;
              return GestureDetector(
                onTap: () {
                  bloc.add(
                    SelectProblemTypeEvent(
                      problemType: problemType,
                    ),
                  );
                },
                child: Row(
                  children: [
                    Transform.scale(
                      scale: 0.8,
                      child: Checkbox(
                        activeColor: ColorManager.primary,
                        checkColor: ColorManager.white,
                        value: isSelected,
                        onChanged: (value) {
                          bloc.add(
                            SelectProblemTypeEvent(
                              problemType: problemType,
                            ),
                          );
                        },
                        fillColor: WidgetStateProperty.all<Color>(isSelected
                            ? ColorManager.primary
                            : ColorManager.surfaceCardColor),
                        side: BorderSide(
                            color: ColorManager.grey2.withValues(alpha: 0.2)),
                      ),
                    ),
                    TextWidget(
                      problemType,
                      style: context.bodyMedium.colorExt(isSelected
                          ? ColorManager.primary
                          : ColorManager.textPrimary),
                    ),
                  ],
                ),
              );
            },
          ),
        );
      },
    );
  }
}
