import 'package:general/src/core/index.dart';
import 'package:general/src/features/profile/profile.dart';

part 'components/bill_tab_bar_body.dart';

part 'components/bill_tab_bar_view_body.dart';

part 'widgets/bill_item_row.dart';

class BillPage extends StatelessWidget {
  const BillPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BackgroundImgWidget(
      child: Scaffold(
        backgroundColor: ColorManager.transparent,
        appBar: AppBarWidget(
          backgroundColor: ColorManager.transparent,
          title: StringManager.record.tr(),
          titleStyle:
              context.bodyMedium.size(20).w600.colorExt(ColorManager.textPrimary),
        ),
        body: BlocBuilder<BillBloc, BillState>(
          bloc: di<BillBloc>(),
          buildWhen: (prev, curr) => false,
          builder: (context, state) {
            return Padding(
              padding: context.paddingAll(15),
              child: Column(
                children: [
                  InkWell(
                    onTap: () {
                      Navigator.pushNamed(context, Routes.rechargeRecord);
                    },
                    child: Row(
                      children: [
                        Image.asset(
                          AssetsManager.walletTigerIcon,
                          width: 20,
                        ),
                        12.wBox,
                        TextWidget(
                          StringManager.rechargeRecord.tr(),
                          style: context.bodyMedium.w600.size(16),
                        ),
                      ],
                    ),
                  ),
                  30.hBox,
                  InkWell(
                    onTap: () {
                      Navigator.pushNamed(context, Routes.coinsRecord);
                    },
                    child: Row(
                      children: [
                        CoinIcon(
                          width: 20,
                          height: 20,
                          fallbackAsset: AssetsManager.coinsVip,
                        ),
                        12.wBox,
                        TextWidget(
                          StringManager.coinsRecord.tr(),
                          style: context.bodyMedium.w600.size(16),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}

//Column(
//               children: [
//                 Container(
//                   color: ColorManager.white,
//                   width: ScreenUtil().screenWidth,
//                   child: Column(
//                     children: [
//                       5.hBox,
//                       _BillTabBarBody(controller: billController),
//                       5.hBox,
//                     ],
//                   ),
//                 ),
//                 Expanded(
//                   child: TabBarView(
//                     controller: billController,
//                     children: [
//                       HandlingDataWidget(
//                         reqState: state.billGivingRequest,
//                         title: StringManager.titleEmptyTransaction.tr(),
//                         subTitle: StringManager.subEmptyTransaction.tr(),
//                         onTap: () => di<BillBloc>().add(
//                             const GetBillGivingEvent(
//                                 param: BillParam(type: 'givin'))),
//                         child: BillTabBarViewBody(
//                           billEntityList: state.billGiving ?? [],
//                           type: 1,
//                         ),
//                       ),
//                       HandlingDataWidget(
//                         reqState: state.billReceivedRequest,
//                         title: StringManager.titleEmptyTransaction.tr(),
//                         subTitle: StringManager.subEmptyTransaction1.tr(),
//                         onTap: () => di<BillBloc>().add(
//                             const GetBillReceivedEvent(
//                                 param: BillParam(type: 'receving'))),
//                         child: BillTabBarViewBody(
//                           billEntityList: state.billReceived ?? [],
//                           type: 2,
//                         ),
//                       ),
//                       HandlingDataWidget(
//                         reqState: state.billRechargeRequest,
//                         title: StringManager.titleEmptyTransaction.tr(),
//                         subTitle: StringManager.subEmptyTransaction2.tr(),
//                         onTap: () => di<BillBloc>().add(
//                             const GetBillRechargeEvent(
//                                 param: BillParam(type: 'recharge'))),
//                         child: BillTabBarViewBody(
//                           billEntityList: state.billRecharge ?? [],
//                           type: 3,
//                         ),
//                       ),
//                     ],
//                   ),
//                 ),
//               ],
//             );
