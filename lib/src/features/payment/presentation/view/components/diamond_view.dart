import 'package:general/src/core/index.dart';
import '../../../../profile/presentation/coins/bloc/my_store_bloc/my_store_bloc.dart';
import '../../../../profile/presentation/exchange_diamond/bloc/diamond_bloc.dart';
import '../../../../profile/presentation/exchange_diamond/view/exchange_diamond_page.dart';

class DiamondView extends StatelessWidget {
  const DiamondView({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<DiamondBloc, DiamondState>(
      bloc: di<DiamondBloc>(),
      buildWhen: (prev, curr) =>
          prev.reqStateDiamond != curr.reqStateDiamond ||
          prev.diamondData != curr.diamondData,
      builder: (context, state) {
        return Padding(
          padding: context.paddingSymmetric(horizontal: 10),
          child: Column(
            children: [
              110.hBox,
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    StringManager.rechargeDiamond.tr(),
                    style: context.bodyLarge.w600.colorExt(ColorManager.textPrimary),
                  ),
                  InkWell(
                    onTap: () {
                      di<MyStoreBloc>().add(const GetMyStoreEvent());
                      di<DiamondBloc>().add(const GetDiamondDataEvent());
                    },
                    child: Image.asset(
                      AssetsManager.refreshIcon,
                      scale: 2,
                    ),
                  ),
                ],
              ),
              10.hBox,
              HandlingDataWidget(
                reqState: state.reqStateDiamond,
                title: StringManager.noDataYet.tr(),
                subTitle: StringManager.pleaseTryAgine.tr(),
                onTap: () {
                  di<DiamondBloc>().add(const GetDiamondDataEvent());
                },
                child: Expanded(
                  child: ListView.builder(
                    padding: EdgeInsets.zero,
                    shrinkWrap: true,
                    itemBuilder: (BuildContext context, int index) {
                      return Padding(
                        padding: context.paddingSymmetric(
                          vertical: 5,
                        ),
                        child: InkWell(
                          onTap: () {
                            showDialog(
                              context: context,
                              builder: (context) => AnimatedDialog(
                                title: StringManager.confirmation.tr(),
                                description: StringManager.youWillEx(
                                  coin: state.diamondData!.data[index].coin
                                      .toString(),
                                  dimond: state
                                      .diamondData!.data[index].diamonds
                                      .toString(),
                                ),
                                conText: StringManager.confirm.tr(),
                                onTap: () {
                                  di<DiamondBloc>().add(
                                    ExchangeDiamondEvent(
                                      itemId: state.diamondData?.data[index].id
                                              .toString() ??
                                          "",
                                      context: context,
                                    ),
                                  );
                                },
                              ),
                            );
                          },
                          child: DiamondsCardItem(
                            replaceWithGoldItemEntity:
                                state.diamondData!.data[index],
                          ),
                        ),
                      );
                    },
                    itemCount: state.diamondData?.data.length ?? 0,
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
