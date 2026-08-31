import 'package:general/src/features/moment/moment.dart';
import 'package:general/src/features/moment/presentation/view/component/report_moment/problem_type.dart';
import '../../../../../../core/index.dart';

class MomentReportDialog extends StatefulWidget {
  final String momentId;
  const MomentReportDialog({super.key, required this.momentId});
  @override
  State<MomentReportDialog> createState() => _MomentReportDialogState();
}

class _MomentReportDialogState extends State<MomentReportDialog> {
  late TextEditingController detailsController;
  late TextEditingController contactController;

  @override
  void initState() {
    super.initState();
    detailsController = TextEditingController();
    contactController = TextEditingController();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBarWidget(
        title: StringManager.flag.tr(),
        actions: [
          Center(
            child: BlocBuilder<MomentBloc, MomentStates>(
              bloc: di<MomentBloc>(),
              buildWhen: (prev, curr) => false,
              builder: (context, state) {
                return ButtonWidget(
                    width: 90.w,
                    height: 40.h,
                    radius: 20,
                    padding: context.paddingZero(),
                    backgroundColor: ColorManager.primary,
                    onPressed: () {
                      if (detailsController.text.isEmpty) {
                        Methods.showToast(
                            isError: true,
                            context,
                            message: StringManager.pleaseEnterAllData.tr());
                      } else {
                        di<MomentBloc>().add(ReportMomentEvent(
                          context: context,
                          reportMomentParam: ReportMomentParam(
                              description: detailsController.text,
                              momentId: widget.momentId,
                              type: ProblemType.typesArabic[
                                  ProblemType.seletedMomentProblem]),
                        ));
                      }
                    },
                    title: StringManager.send.tr());
              },
            ),
          ),
        ],
      ),
      backgroundColor: ColorManager.scaffoldBg,
      body: Padding(
        padding: context.paddingSymmetric(
          horizontal: 15,
        ),
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              5.hBox,
              TextWidget(StringManager.typeOfProblem.tr(),
                  style: context.bodyLarge.w600),
              20.hBox,
              const ProblemType(),
              40.hBox,
              Text(StringManager.details.tr(), style: context.bodyLarge.w600),
              10.hBox,
              TextInputWidget(
                maxLines: 10,
                minLines: 5,
                StringManager.explainProblem.tr(),
                controller: detailsController,
                fillColor: ColorManager.surfaceCardColor,
                textColor: ColorManager.textPrimary,
                maxLength: 200,
                contentPadding: context.paddingZero(),
                enabledBorder: InputBorder.none,
                focusedBorder: InputBorder.none,
                errorBorder: InputBorder.none,
                focusedErrorBorder: InputBorder.none,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
