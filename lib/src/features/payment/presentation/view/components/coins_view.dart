import 'package:general/src/core/widgets/bottom_dialog.dart';
import 'package:general/src/features/agency/presentation/charge_agency/view/component/host_withdrawel_screen/view/host_withdrawel_screen.dart';
import 'package:general/src/features/payment/presentation/bloc/fetch_coins/fetch_coins_event.dart';
import 'package:general/src/features/payment/presentation/view/in_app_purchases.dart';
import 'package:general/src/features/profile/presentation/coins/bloc/my_store_bloc/my_store_bloc.dart';
import 'package:skeletonizer/skeletonizer.dart';

import 'package:general/src/core/index.dart';
import '../../bloc/buy_coins_bloc/buy_coins_bloc.dart';
import '../../bloc/buy_coins_bloc/buy_coins_event.dart';
import '../../bloc/fetch_coins/fetch_coins_bloc.dart';
import '../../bloc/fetch_coins/fetch_coins_state.dart';

class CoinsView extends StatefulWidget {
  const CoinsView({super.key});

  @override
  State<CoinsView> createState() => _CoinsViewState();
}

class _CoinsViewState extends State<CoinsView> {
  @override
  Widget build(BuildContext context) {
    return BlocBuilder<FetchCoinsBloc, FetchCoinsState>(
      bloc: di<FetchCoinsBloc>(),
      buildWhen: (prev, curr) =>
          prev.reqState != curr.reqState || prev.data != curr.data,
      builder: (context, state) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            110.hBox,
            Padding(
              padding: context.paddingSymmetric(horizontal: 18),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  TextWidget(
                    StringManager.recharge.tr(),
                    style: context.bodyMedium.size(15).w600.colorExt(
                        ColorManager.secondaryText),
                  ),
                  InkWell(
                    onTap: () {
                      di<MyStoreBloc>().add(const GetMyStoreEvent());
                      di<FetchCoinsBloc>().add(const FetchCoinsEvent());
                    },
                    child: Image.asset(
                      AssetsManager.refreshIcon,
                      scale: 2,
                      color: ColorManager.walletCard,
                    ),
                  ),
                ],
              ),
            ),
            5.hBox,
            Expanded(
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: context.paddingSymmetric(horizontal: 18, vertical: 10),
                children: [
                  Container(
                    decoration: BoxDecoration(
                      color: ColorManager.surfaceCardColor,
                      borderRadius: 10.radius,
                    ),
                    margin: context.paddingSymmetric(vertical: 5),
                    padding: context.paddingSymmetric(vertical: 8, horizontal: 18),
                    child: GestureDetector(
                      onTap: () {
                        bottomDailog(
                          context: context,
                          widget: const HostWithdrawelScreen(
                            param: CoinsScreenParam(
                              stopTransferButton: true,
                              isFromCoinsScreen: true,
                            ),
                          ),
                        );
                      },
                      child: Row(
                        children: [
                          CoinIcon(
                            width: 40.w,
                            height: 40.h,
                            fallbackAsset: AssetsManager.coinsVip,
                          ),
                          15.wBox,
                          TextWidget(
                            StringManager.shippingFromTheAgency.tr(),
                            style: context.bodyMedium.bold
                                .colorExt(ColorManager.textPrimary),
                          ),
                          const Spacer(),
                          const Icon(
                            Icons.keyboard_arrow_down_outlined,
                            color: Colors.black,
                          ),
                        ],
                      ),
                    ),
                  ),
                  HandlingDataWidget(
                    isNeedLoadingWidget: true,
                    reqState: state.reqState,
                    title: StringManager.noDataYet.tr(),
                    subTitle: StringManager.pleaseTryAgine.tr(),
                    onTap: () {
                      di<FetchCoinsBloc>().add(const FetchCoinsEvent());
                      di<MyStoreBloc>()
                          .add(const GetMyStoreEvent(isLoading: false));
                    },
                    child: Builder(
                      builder: (context) {
                        final filtered = state.data?.where((g) => g.title == 'google_pay').toList() ?? [];
                        return Skeletonizer(
                          enabled: state.reqState == RequestState.loading,
                          child: ListView.builder(
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: state.reqState == RequestState.loading ? 5 : filtered.length,
                            shrinkWrap: true,
                            itemBuilder: (context, ind) {
                              return Container(
                                decoration: BoxDecoration(
                                  color: ColorManager.surfaceCardColor,
                                  borderRadius: 10.radius,
                                ),
                                margin: context.paddingSymmetric(vertical: 5),
                                child: Theme(
                                  data: Theme.of(context).copyWith(dividerColor: ColorManager.surfaceCardColor),
                                  child: state.reqState.isLoading
                                      ? Container(width: 40.w, height: 40.h, color: Colors.grey[300])
                                      : ExpansionTile(
                                          leading: ImageViewWidget(
                                            url: filtered[ind].photo ?? "",
                                            width: 40.w,
                                            height: 40.h,
                                            boxFit: BoxFit.contain,
                                          ),
                                          title: TextWidget(
                                            filtered[ind].title != null
                                                ? StringManager.getGateWay(gateway: filtered[ind].title!)
                                                : "",
                                            style: context.bodyMedium.bold.colorExt(ColorManager.textPrimary),
                                          ),
                                          children: [
                                            Column(
                                              children: List.generate(
                                                filtered[ind].coins?.length ?? 0,
                                                (index) {
                                                  final coin = filtered[ind].coins?[index];
                                                  return Container(
                                                    padding: context.paddingSymmetric(horizontal: 15),
                                                    margin: context.paddingSymmetric(vertical: 10),
                                                    child: Row(
                                                      crossAxisAlignment: CrossAxisAlignment.center,
                                                      mainAxisAlignment: MainAxisAlignment.start,
                                                      children: [
                                                        CoinIcon(size: 26.h, fallbackAsset: AssetsManager.coinsVip),
                                                        10.wBox,
                                                        Expanded(
                                                          child: TextWidget(
                                                            coin?.coin.toString() ?? '',
                                                            style: context.bodyMedium.bold.colorExt(ColorManager.textPrimary).size(18),
                                                          ),
                                                        ),
                                                        ButtonWidget(
                                                          fontWeight: FontWeight.w500,
                                                          fontSize: 16,
                                                          backgroundColor: ColorManager.walletCard,
                                                          onPressed: () async {
                                                            if (filtered[ind].title == "google_pay" && di<PurchaseService>().result != null) {
                                                              di<PurchaseService>().buyProduct(
                                                                di<PurchaseService>().result!.productDetails
                                                                    .where((e) => e.id.toString() == coin?.id.toString()).single,
                                                              );
                                                            } else {
                                                              if (!di<BuyCoinsBloc>().state.reqState.isLoading) {
                                                                di<BuyCoinsBloc>().add(BuyCoinsEvent(
                                                                  context: context,
                                                                  method: filtered[ind].title ?? '',
                                                                  productId: coin?.id.toString() ?? "",
                                                                ));
                                                              }
                                                            }
                                                            Methods.showToast(context, isLoading: true);
                                                          },
                                                          height: 33.h,
                                                          width: 110.w,
                                                          title: coin?.usd != null
                                                              ? "${coin!.usd % 1 == 0 ? coin.usd.toInt() : coin.usd.toString()} \$"
                                                              : '',
                                                        ),
                                                      ],
                                                    ),
                                                  );
                                                },
                                              ),
                                            ),
                                          ],
                                        ),
                                ),
                              );
                            },
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
          ],
        );
      },
    );
  }
}
