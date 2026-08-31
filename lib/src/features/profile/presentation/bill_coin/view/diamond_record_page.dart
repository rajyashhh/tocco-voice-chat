import 'package:general/src/core/index.dart';
import '../../../../room/presentation/gifts/gift.dart';
import 'components/dialog_start_end_date.dart';

class DiamondRecord extends StatefulWidget {
  const DiamondRecord({super.key});

  @override
  State<DiamondRecord> createState() => _DiamondRecordState();
}

class _DiamondRecordState extends State<DiamondRecord> {
  bool fetchData = false;

  @override
  void initState() {
    if (!di<BillBloc>().state.billGivingRequest.isLoaded) {
      di<BillBloc>()
          .add(const GetBillGivingEvent(param: BillParam(type: 'givin')));
    }

    di<BillBloc>().add(const AddListenerBillGivingEvent(
        param: BillParam(type: 'givin', isLoading: false)));

    super.initState();
  }

  @override
  void dispose() {
    di<BillBloc>().add(const RemoveListenerBillGivingEvent(
        param: BillParam(type: 'givin', isLoading: false)));
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    String direction = HiveManager().getData<String>(
                KeysManager.USER_BOX, KeysManager.LANG_CODE_KEY) ==
            'ar'
        ? "⬅"
        : "➡";
    return Scaffold(
      appBar: AppBarWidget(
        backgroundColor: ColorManager.scaffoldBg,
        title: StringManager.diamondRecord.tr(),
        iconLasted: IconButton(
          onPressed: () {
            showModalBottomSheet(
                context: context,
                backgroundColor: ColorManager.scaffoldBg,
                shape: RoundedRectangleBorder(
                  borderRadius: 15.radius,
                ),
                builder: (BuildContext context) {
                  return DialogStartEndDate(type: "givin");
                });
          },
          icon: Padding(
            padding: context.paddingSymmetric(horizontal: 10),
            child: Image.asset(
              AssetsManager.filterIcon,
              color: ColorManager.iconColor,
              scale: 5,
            ),
          ),
        ),
      ),
      body: BlocBuilder<BillBloc, BillState>(
        bloc: di<BillBloc>(),
        buildWhen: (prev, curr) =>
            prev.billGivingRequest != curr.billGivingRequest ||
            prev.billGiving != curr.billGiving ||
            prev.givingScrollCtrl != curr.givingScrollCtrl,
        builder: (context, state) {
          return HandlingDataWidget(
            reqState: state.billGivingRequest,
            title: StringManager.titleEmptyTransaction.tr(),
            onTap: () {
              di<BillBloc>().add(
                  const GetBillGivingEvent(param: BillParam(type: 'givin')));
            },
            subTitle: StringManager.subEmptyTransaction1.tr(),
            child: RefreshIndicatorWidget(
              onRefresh: () async {
                di<BillBloc>().add(const GetBillGivingEvent(
                    param: BillParam(
                        type: 'givin', isLoading: false, isRefresh: true)));
              },
              child: ListView.builder(
                  itemCount: state.billGiving.length,
                  controller: state.givingScrollCtrl,
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding:
                      context.paddingSymmetric(horizontal: 10, vertical: 10),
                  itemBuilder: (context, index) {
                    return Container(
                      padding: context.paddingSymmetric(
                          horizontal: 10, vertical: 15),
                      margin: context.paddingAll(5),
                      decoration: BoxDecoration(
                          color: ColorManager.surfaceCardColor,
                          borderRadius: 5.radius,
                          border: Border.all(
                              width: 1, color: ColorManager.cardBorderColor)),
                      child: Row(
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              TextWidget(
                                '${StringManager.reference.tr()} :${state.billGiving[index].operationNum}',
                                style: context.bodyMedium.bold
                                    .size(12)
                                    .colorExt(ColorManager.textPrimary),
                              ),
                              3.hBox,
                              TextWidget(
                                state.billGiving[index].createdAt,
                                style: context.bodySmall
                                    .colorExt(ColorManager.whiteGrey4),
                              ),
                            ],
                          ),
                          const Spacer(),
                          Row(
                            children: [
                              FittedBox(
                                child: TextWidget(
                                  state.billGiving[index].diamonds.toString(),
                                  style: context.bodyMedium.w600.size(14),
                                ),
                              ),
                              5.wBox,
                              Image.asset(
                                AssetsManager.diamondIcon,
                                scale: 4,
                              ),
                              5.wBox,
                              TextWidget(direction),
                              5.wBox,
                              FittedBox(
                                child: TextWidget(
                                  state.billGiving[index].value.toString(),
                                  style: context.bodyMedium.w600.size(14),
                                ),
                              ),
                              5.wBox,
                              CoinIcon(
                                size: 15.0,
                                fallbackAsset: AssetsManager.coinsIcon,
                              ),
                            ],
                          ),
                        ],
                      ),
                    );
                  }),
            ),
          );
        },
      ),
    );
  }
}
