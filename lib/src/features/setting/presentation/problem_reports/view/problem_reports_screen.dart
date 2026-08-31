import 'dart:io';
import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/dotted_container.dart';
import 'package:general/src/features/setting/setting.dart';
import '../bloc/get_questions_bloc/get_questions_bloc.dart';
import '../bloc/get_questions_bloc/get_questions_state.dart';

part 'components/problem_type_body.dart';
part 'components/support_tab_bar.dart';
part 'components/faq_component.dart';
part 'components/email_support.dart';

class ProblemReportsScreen extends StatefulWidget {
  const ProblemReportsScreen({super.key});

  @override
  State<ProblemReportsScreen> createState() => _ProblemReportsScreenState();
}

class _ProblemReportsScreenState extends State<ProblemReportsScreen> {
  late TextEditingController controller;
  late TextEditingController contactController;
  final _formKey = GlobalKey<FormState>();
  String? selectedImagePath;

  @override
  void initState() {
    final bloc = di<MakeProblemReportBloc>();
    bloc.state.contactController.clear();
    bloc.state.descController.clear();

    bloc.add(const MakeProblemReportRemoveImageEvent());

    bloc.add(const ChangeContactDetails(index: 0, type: 'type'));
    bloc.add(const ChangeContactDetails(index: 0, type: 'details'));

    // Initialize local controllers
    controller = TextEditingController();
    contactController = TextEditingController();
    super.initState();
  }

  @override
  void dispose() {
    final bloc = di<MakeProblemReportBloc>();
    bloc.state.contactController.clear();
    bloc.state.descController.clear();

    bloc.add(const MakeProblemReportRemoveImageEvent());

    controller.dispose();
    contactController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final normalPage =
        BlocConsumer<MakeProblemReportBloc, MakeProblemReportState>(
      bloc: di<MakeProblemReportBloc>(),
      listener: (context, state) {
        if (state.reqState.isLoaded) {
          Methods.showToast(context, message: state.message ?? '');
        } else if (state.reqState.isError) {
          Methods.showToast(
            context,
            message: state.message ?? '',
            isError: true,
          );
        }
      },
      builder: (context, state) {
        // No ScreenBackground here: it painted the fixed dark-violet splash
        // image under every theme (owner-flagged anomaly). The page now sits
        // on the per-theme scaffold fill like every other settings screen.
        return Scaffold(
            backgroundColor: ColorManager.scaffoldBg,
            appBar: AppBarWidget(
              backgroundColor: ColorManager.scaffoldBg,
              title: StringManager.feedBack.tr(),
              actions: [
                ButtonWidget(
                  title: StringManager.save.tr(),
                  width: 100.w,
                  height: 30.h,
                  titleColor: ColorManager.onDark,
                  onPressed: () {
                    if (_formKey.currentState?.validate() == false) {
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
                      di<MakeProblemReportBloc>().add(
                        MakeProblemReportEvent(
                          context: context,
                          makeProblemReportParam: MakeProblemReportParam(
                            description: state.descController.text,
                            userId: MyDataModel.getInstance().id.toString(),
                            contact: state.contactController.text,
                            image: selectedImagePath == null ||
                                    selectedImagePath?.isEmpty == true
                                ? null
                                : File(selectedImagePath ?? ""),
                          ),
                        ),
                      );
                    }
                  },
                ),
              ],
            ),
            body: SingleChildScrollView(
              padding: context.paddingSymmetric(horizontal: 15.w),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    TextWidget(
                      StringManager.selectFeedback.tr(),
                      style: context.bodyMedium.w600
                          .size(16)
                          .colorExt(ColorManager.textPrimary),
                    ),
                    15.hBox,
                    ProblemType(
                      types: [
                        StringManager.suggestion.tr(),
                        StringManager.bug.tr(),
                        StringManager.help.tr(),
                        StringManager.other.tr(),
                      ],
                      type: 'type',
                    ),
                    Column(
                      children: [
                        Row(
                          children: [
                            TextWidget(
                              StringManager.contactDetails.tr(),
                              style: context.bodyMedium.bold
                                  .colorExt(ColorManager.textPrimary),
                            ),
                            TextWidget(
                              "*",
                              style: context.bodyMedium.bold
                                  .colorExt(ColorManager.redAccount),
                            ),
                          ],
                        ),
                        10.hBox,
                        ProblemType(
                          types: [
                            StringManager.whatsappDetails.tr(),
                            StringManager.email.tr(),
                            StringManager.phone.tr(),
                          ],
                          type: 'details',
                        ),
                        10.hBox,
                        Align(
                          alignment: Alignment.topLeft,
                          child: TextWidget(
                            state.indexDetails == 0
                                ? StringManager.whatsapp.tr()
                                : state.indexDetails == 1
                                    ? StringManager.email.tr()
                                    : StringManager.phone.tr(),
                            style: context.bodyLarge.w500.colorExt(
                              ColorManager.textPrimary,
                            ),
                          ),
                        ),
                        10.hBox,
                        TextInputWidget(
                          "",
                          onChanged: (value) {
                            di<MakeProblemReportBloc>()
                                .add(const UpdateFormValidationEvent());
                          },
                          keyboardType: state.indexDetails == 0
                              ? TextInputType.phone
                              : state.indexDetails == 1
                                  ? TextInputType.text
                                  : TextInputType.phone,
                          controller: state.contactController,
                          inputFormatters: state.indexDetails == 1
                              ? null
                              : [
                                  FilteringTextInputFormatter.allow(
                                      RegExp(r'^\+?\d*')),
                                ],
                          enabledBorder: OutlineInputBorder(
                            borderRadius: 5.radius,
                            borderSide: const BorderSide(
                              color: ColorManager.transparent,
                            ),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: 5.radius,
                            borderSide: const BorderSide(
                              color: ColorManager.transparent,
                            ),
                          ),
                          border: OutlineInputBorder(
                              borderRadius: 5.radius,
                              borderSide: const BorderSide(
                                  color: ColorManager.transparent)),
                          fillColor: ColorManager.highlightColor,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return StringManager.requiredField.tr();
                            }

                            if (state.indexDetails == 1) {
                              final emailRegex = RegExp(
                                  r"^(?!.*\.\.)([a-zA-Z0-9_.+-])+@([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$");

                              if (!emailRegex.hasMatch(value)) {
                                return StringManager.invalidEmail.tr();
                              }

                              final commonDomains = [
                                'gmail.com',
                                'hotmail.com',
                                'yahoo.com'
                              ];
                              final emailParts = value.split('@');
                              if (emailParts.length == 2) {
                                final domain = emailParts[1].toLowerCase();
                                if (!commonDomains.contains(domain)) {
                                  return StringManager.invalidEmail.tr();
                                }
                              }
                            }

                            return null;
                          },
                          hintStyle: context.bodyMedium
                              .colorExt(ColorManager.textPrimary),
                        ),
                        7.hBox,
                        Align(
                          alignment: Alignment.centerRight,
                          child: TextWidget(
                            DateFormat('yyyy/MM/dd hh:mm a')
                                .format(DateTime.now()),
                            style: context.bodyMedium.bold.colorExt(
                              ColorManager.textPrimary.withValues(
                                alpha: (0.3),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    20.hBox,
                    Column(
                      children: [
                        Row(
                          children: [
                            TextWidget(
                              StringManager.description.tr(),
                              style: context.bodyMedium.bold
                                  .colorExt(ColorManager.textPrimary),
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
                          contentPadding: context.paddingSymmetric(
                              vertical: 7, horizontal: 10),
                          StringManager.feedbackDescription.tr(),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: 5.radius,
                            borderSide: const BorderSide(
                              color: ColorManager.transparent,
                            ),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: 5.radius,
                            borderSide: const BorderSide(
                              color: ColorManager.transparent,
                            ),
                          ),
                          border: OutlineInputBorder(
                            borderRadius: 5.radius,
                            borderSide: const BorderSide(
                              color: ColorManager.transparent,
                            ),
                          ),
                          fillColor: ColorManager.highlightColor,
                          controller: state.descController,
                          maxLines: 10,
                          minLines: 10,
                          maxLength: 500,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return StringManager.requiredField.tr();
                            }
                            return null;
                          },
                          hintStyle: context.bodyMedium
                              .colorExt(ColorManager.secondaryText)
                              .size(16),
                        ),
                      ],
                    ),
                    20.hBox,
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        TextWidget(
                          StringManager.screenshot.tr(),
                          style: context.bodyMedium.bold
                              .colorExt(ColorManager.textPrimary),
                        ),
                        InkWell(
                          onTap: () {
                            if (selectedImagePath == null ||
                                selectedImagePath!.isEmpty) {
                              di<MakeProblemReportBloc>().add(
                                const MakeProblemReportPickImageEvent(
                                  ImageSource.gallery,
                                ),
                              );
                            }
                          },
                          radius: 10,
                          child: BlocBuilder<MakeProblemReportBloc,
                              MakeProblemReportState>(
                            bloc: di<MakeProblemReportBloc>(),
                            buildWhen: (prev, curr) =>
                                prev.imagePath != curr.imagePath,
                            builder: (context, state) {
                              selectedImagePath = state.imagePath;

                              return DashedBorderContainer(
                                width: 80.w,
                                height: 80.h,
                                borderRadius: 10.r,
                                borderColor: ColorManager.primary,
                                strokeWidth: 2,
                                child: Container(
                                  width: 80.w,
                                  height: 80.h,
                                  decoration: BoxDecoration(
                                    color: ColorManager.highlightColor,
                                    image: selectedImagePath != null &&
                                            selectedImagePath!.isNotEmpty
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
                            },
                          ),
                        ),
                      ],
                    ),
                    20.hBox,
                  ],
                ),
              ),
            ),
          );
      },
    );
    return normalPage;
  }
}
