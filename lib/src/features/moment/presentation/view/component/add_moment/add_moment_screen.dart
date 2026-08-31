import 'dart:io';
import 'package:general/src/features/moment/moment.dart';
import '../../../../../../core/index.dart';

import 'custom_emoji_picker.dart';

part 'pick_moment_image.dart';

class AddMomentScreen extends StatefulWidget {
  const AddMomentScreen({super.key});

  @override
  State<AddMomentScreen> createState() => _AddMomentScreenState();
}

class _AddMomentScreenState extends State<AddMomentScreen> {
  late TextEditingController createMomentController = TextEditingController();
  @override
  void initState() {
    di<MomentBloc>().add(const RemovePickedImageEvent());
    di<MomentBloc>().add(const ShowEmojiPickerEvent(showEmoji: false));
    createMomentController = TextEditingController();
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<MomentBloc, MomentStates>(
      bloc: di<MomentBloc>(),
      listener: (context, state) {
        if (state.addMomentReqState.isLoading) {
          Methods.showToast(context, isLoading: true);
        } else if (state.addMomentReqState.isLoaded) {
          Methods.showToast(
            context,
            message: state.addMomentMessage,
          );
          Navigator.pop(context);
          return;
        } else if (state.addMomentReqState.isError) {
          Methods.showToast(
            context,
            message: state.addMomentMessage,
          );
          return;
        }
      },
      builder: (context, momentState) {
        return GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: () {
            if (momentState.showEmoji) {
              di<MomentBloc>()
                  .add(const ShowEmojiPickerEvent(showEmoji: false));
            }
            FocusScope.of(context).unfocus();
          },
          child: Scaffold(
            backgroundColor: ColorManager.scaffoldBg,
            appBar: AppBarWidget(
              backgroundColor: ColorManager.scaffoldBg,
              title: StringManager.postUpdates.tr(),
              actions: [
                Container(
                  padding:
                      context.paddingSymmetric(horizontal: 15, vertical: 2),
                  decoration: BoxDecoration(
                      color: momentState.isFormValid
                          ? ColorManager.primary
                          : Colors.grey.withValues(alpha: (0.5)),
                      shape: BoxShape.rectangle,
                      borderRadius: 20.radius),
                  child: InkWell(
                    onTap: momentState.addMomentReqState.isLoading
                        ? null
                        : () async {
                            if (createMomentController.text.isNotEmpty ||
                                momentState.multiImages.isNotEmpty) {
                              di<MomentBloc>().add(
                                AddMomentData(
                                  moment: createMomentController.text,
                                  context: context,
                                  data: FormData.fromMap({
                                    "contacts": createMomentController.text,
                                    if (momentState.image != null)
                                      "img": await MultipartFile.fromFile(
                                          momentState.image!.path,
                                          filename: momentState.image!.path
                                              .split('/')
                                              .last),
                                  }),
                                ),
                              );
                            }
                          },
                    child: TextWidget(StringManager.post.tr(),
                        style: context.bodyMedium
                            .size(15)
                            .colorExt(ColorManager.white)),
                  ),
                ),
                10.wBox
              ],
            ),
            body: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    height: 80,
                    child: _PickMomentImageBody(
                        moment: createMomentController.text),
                  ),
                  15.hBox,
                  TextInputWidget(
                    StringManager.postMomentHint.tr(),
                    maxLines: 20,
                    minLines: 10,
                    contentPadding: context.paddingSymmetric(horizontal: 20),
                    maxLength: 1000,
                    // showMaxLength: true,
                    controller: createMomentController,
                    onChanged: (v) {
                      di<MomentBloc>().add(UpdateValidationEvent(
                          moment: createMomentController.text));
                    },
                    border: InputBorder.none,
                    focusedBorder: InputBorder.none,
                    focusedErrorBorder: InputBorder.none,
                    enabledBorder: InputBorder.none,
                    errorBorder: InputBorder.none,
                    textSize: 16,
                    textColor: ColorManager.textPrimary,
                    fillColor: ColorManager.scaffoldBg,
                    hintStyle: context.bodyMedium
                        .size(16)
                        .colorExt(ColorManager.secondaryText),
                  ),
                  20.hBox,
                ],
              ),
            ),
            bottomSheet: Container(
              decoration: BoxDecoration(
                color: ColorManager.surfaceCardColor,
                border: Border(
                  top: BorderSide(
                    color: ColorManager.grey.withValues(alpha: (0.5)),
                    width: 0.35,
                  ),
                ),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Padding(
                    padding: const EdgeInsets.all(10.0),
                    child: Row(
                      children: [
                        GestureDetector(
                          onTap: () {
                            di<MomentBloc>().add(const PickMultiPicEvent());
                          },
                          child: Row(
                            children: [
                              Container(
                                decoration: BoxDecoration(
                                  color: ColorManager.primary,
                                  shape: BoxShape.circle,
                                ),
                                padding: context.paddingSymmetric(
                                    horizontal: 8, vertical: 8),
                                child: ImageWidget(
                                    height: 10,
                                    width: 12,
                                    boxFit: BoxFit.fill,
                                    image: AssetsManager.addImasges,
                                    color: ColorManager.white),
                              ),
                              2.wBox,
                              TextWidget(
                                StringManager.addPicture.tr(),
                                style: context.bodyMedium
                                    .size(12)
                                    .colorExt(ColorManager.textPrimary),
                              ),
                            ],
                          ),
                        ),
                        const Spacer(),
                        GestureDetector(
                          onTap: () {
                            di<MomentBloc>().add(ShowEmojiPickerEvent(
                                showEmoji: !momentState.showEmoji));
                          },
                          child: Icon(
                            Icons.emoji_emotions,
                            color: ColorManager.grey.withValues(alpha: (0.8)),
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (momentState.showEmoji) ...[
                    10.hBox,
                    CustomEmojiPicker(
                      textController: createMomentController,
                    ),
                  ],
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}
