part of '../problem_reports_screen.dart';

class ProblemType extends StatefulWidget {
  static int selectedProblem = 0;
  final List<String> types;
  final String type;

  const ProblemType({super.key, required this.types, required this.type});

  @override
  State<ProblemType> createState() => _ProblemTypeState();
}

class _ProblemTypeState extends State<ProblemType> {
  @override
  Widget build(BuildContext context) {
    return widget.type == 'type'
        ? SizedBox(
            height: 100.h,
            child: BlocBuilder<MakeProblemReportBloc, MakeProblemReportState>(
              bloc: di<MakeProblemReportBloc>(),
              buildWhen: (prev, curr) => prev.indexTypes != curr.indexTypes,
              builder: (context, state) {
                return GridView.builder(
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2, childAspectRatio: 4.2),
                  padding: EdgeInsets.zero,
                  itemCount: widget.types.length,
                  itemBuilder: (context, index) {
                    return GestureDetector(
                      onTap: () {
                        di<MakeProblemReportBloc>().add(ChangeContactDetails(
                            index: index, type: widget.type));
                      },
                      child: Row(
                        children: [
                          Transform.scale(
                            scale: 0.8,
                            child: Checkbox(
                              activeColor: ColorManager.primary,
                              checkColor: ColorManager.white,
                              value: state.indexTypes == index,
                              onChanged: (value) {
                                di<MakeProblemReportBloc>().add(
                                    ChangeContactDetails(
                                        index: index, type: widget.type));
                              },
                              fillColor: WidgetStateProperty.all<Color>(
                                  state.indexTypes == index
                                      ? ColorManager.primary
                                      : ColorManager.surfaceCardColor),
                              side: BorderSide(
                                  color: ColorManager.grey2
                                      .withValues(alpha: (0.2))),
                            ),
                          ),
                          TextWidget(
                            widget.types[index],
                            style: context.bodyMedium.colorExt(
                                state.indexTypes == index
                                    ? ColorManager.primary
                                    : ColorManager.textPrimary),
                          ),
                        ],
                      ),
                    );
                  },
                );
              },
            ),
          )
        : SizedBox(
            height: 80.h,
            child: BlocBuilder<MakeProblemReportBloc, MakeProblemReportState>(
              bloc: di<MakeProblemReportBloc>(),
              buildWhen: (prev, curr) => prev.indexDetails != curr.indexDetails,
              builder: (context, state) {
                return GridView.builder(
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2, childAspectRatio: 4.2),
                    itemCount: widget.types.length,
                    itemBuilder: (context, index) {
                      return GestureDetector(
                        onTap: () {
                          di<MakeProblemReportBloc>().add(ChangeContactDetails(
                              index: index, type: widget.type));
                        },
                        child: Row(
                          children: [
                            Transform.scale(
                              scale: 0.8,
                              child: Checkbox(
                                activeColor: ColorManager.primary,
                                checkColor: ColorManager.white,
                                value: state.indexDetails == index,
                                onChanged: (value) {
                                  di<MakeProblemReportBloc>().add(
                                      ChangeContactDetails(
                                          index: index, type: widget.type));
                                },
                                fillColor: WidgetStateProperty.all<Color>(
                                    state.indexDetails == index
                                        ? ColorManager.primary
                                        : ColorManager.surfaceCardColor),
                                side: BorderSide(
                                    color: ColorManager.grey2
                                        .withValues(alpha: (0.2))),
                              ),
                            ),
                            TextWidget(
                              widget.types[index],
                              style: context.bodyMedium.colorExt(
                                  state.indexDetails == index
                                      ? ColorManager.primary
                                      : ColorManager.textPrimary),
                            ),
                          ],
                        ),
                      );
                    });
              },
            ),
          );
  }
}
