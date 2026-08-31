part of 'package:general/src/features/agency/presentation/host_agency/view/component/agency_manager_screen/view/agency_manager_screen.dart';

class FirstTabManagerScreen extends StatefulWidget {
  const FirstTabManagerScreen({super.key});

  @override
  State<FirstTabManagerScreen> createState() => _FirstTabManagerScreenState();
}

class _FirstTabManagerScreenState extends State<FirstTabManagerScreen> {
  @override
  void initState() {
    if (!di<AgencyHostReportBloc>().state.requestState.isLoaded) {
      di<AgencyHostReportBloc>().add(
        AgencyHostReportEvent(
            isFirsLoading: true,
            mounth: '${DateTime.now().month}',
            year: '${DateTime.now().year}'),
      );
    }
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      child: BlocBuilder<AgencyHostReportBloc, AgencyHostReportState>(
        bloc: di<AgencyHostReportBloc>(),
        builder: (context, state) {
          return HandlingDataWidget(
            reqState: state.requestState,
            title: StringManager.noAgencyDataNowTitle.tr(),
            subTitle: StringManager.noAgencyDataNowSubTitle.tr(),
            onTap: () {
              di<AgencyHostReportBloc>().add(
                AgencyHostReportEvent(
                    isFirsLoading: true,
                    mounth: '${DateTime.now().month}',
                    year: '${DateTime.now().year}'),
              );
            },
            child: Column(
              children: [
                Padding(
                  padding: context.paddingSymmetric(
                    horizontal: 15,
                  ),
                  child: Row(
                    children: [
                      DateWidget(
                        isFirstNotifier: true,
                        selectedDate: (value) {
                          firstTabAgencyTimeFilter.value = value;
                          firstTabAgencyTimeFilter.notifyListeners();
                        },
                        onPressed: () {
                          di<AgencyHostReportBloc>().add(AgencyHostReportEvent(
                            mounth:
                                firstTabAgencyTimeFilter.value.split('/')[1],
                            year: firstTabAgencyTimeFilter.value.split('/')[0],
                          ));
                          Navigator.pop(context);
                        },
                        isNeedTitle: false,
                      ),
                    ],
                  ),
                ),
                10.hBox,
                MonthlyData(
                  state: state,
                ),
                const _MyTargetSection(),
                DailyData(
                  isAdmin:
                      (di<InformationAgencyBloc>().state.data?.userStates ??
                              0) ==
                          1,
                  state: state,
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

/// The host's own monthly target and achievement rate. Sourced from the
/// requester-scoped `target` / `rate_percentage` fields of the more-info
/// response (NOT the agency-wide roster in [TabBarViewData], which stays
/// restricted to admins/agents to avoid leaking other members' data and the
/// kick-out action).
class _MyTargetSection extends StatelessWidget {
  const _MyTargetSection();

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<FetchMoreInfoAgencyBloc, FetchMoreInfoAgencyState>(
      bloc: di<FetchMoreInfoAgencyBloc>(),
      buildWhen: (prev, curr) => prev.entity != curr.entity,
      builder: (context, state) {
        final target = state.entity?.target ?? 0;
        final ratePercentage = state.entity?.ratePercentage ?? 0;
        return Padding(
          padding: context.paddingSymmetric(horizontal: 15, vertical: 10),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              MonthlyDataItemWidget(
                title: StringManager.monthlyTarget.tr(),
                value:
                    '${Methods().convertToAbbreviatedString(target)}\$',
                textColor: ColorManager.primary,
                icon: AssetsManager.moneyBag,
                scale: 14,
              ),
              20.wBox,
              MonthlyDataItemWidget(
                title: StringManager.targetRate.tr(),
                value: '${ratePercentage.toStringAsFixed(0)}%',
                textColor: ColorManager.hanPurple,
                icon: AssetsManager.agencydiamond,
              ),
            ],
          ),
        );
      },
    );
  }
}
