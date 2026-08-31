import 'package:general/src/core/index.dart';
import 'package:general/src/features/payment/presentation/bloc/buy_coins_bloc/buy_coins_bloc.dart';
import 'package:general/src/features/payment/presentation/bloc/buy_coins_bloc/buy_coins_event.dart';
import 'package:general/src/features/payment/presentation/bloc/buy_coins_bloc/buy_coins_state.dart';
import 'package:general/src/features/payment/presentation/bloc/fetch_coins/fetch_coins_bloc.dart';
import 'package:general/src/features/payment/presentation/bloc/fetch_coins/fetch_coins_event.dart';
import 'package:general/src/features/profile/presentation/coins/bloc/my_store_bloc/my_store_bloc.dart';
import '../../../profile/presentation/exchange_diamond/bloc/diamond_bloc.dart';
import '../components/coins_tab_bar.dart';
import 'components/coins_tab_bar_view.dart';

part 'components/coins_tab_bar.dart';

class CoinsScreen extends StatefulWidget {
  final int? index;

  const CoinsScreen({super.key, this.index});

  @override
  State<CoinsScreen> createState() => CoinsScreenState();
}

class CoinsScreenState extends State<CoinsScreen>
    with TickerProviderStateMixin {
  final FetchCoinsBloc _goldCoinBloc = di<FetchCoinsBloc>();
  late final TabController _controller;

  int value = di<MyStoreBloc>().state.myStore?.coins ?? 0;

  @override
  void initState() {
    super.initState();
    di<BuyCoinsBloc>().add(
        const ChangeValueEvent(value: 0, changeText: true, controllerIndex: 0));

    di<MyStoreBloc>().add(const GetMyStoreEvent());
    if (!di<DiamondBloc>().state.reqStateDiamond.isLoaded) {
      di<DiamondBloc>().add(const GetDiamondDataEvent(isLoading: true));
    }
    di<MyStoreBloc>().state.myStore;
    _controller = TabController(
      length:
          (!(StringManager.userType[1]! || StringManager.userType[2]!)) ? 3 : 2,
      vsync: this,
      initialIndex: widget.index ?? 0,
    );

    if (!_goldCoinBloc.state.reqState.isLoaded) {
      _goldCoinBloc.add(const FetchCoinsEvent());
    }

    _controller.addListener(() {
      if (_controller.indexIsChanging) return;

      final value = (_controller.index == 0)
          ? di<MyStoreBloc>().state.myStore?.coins ?? 0
          : (_controller.index == 1)
              ? di<MyStoreBloc>().state.myStore?.diamonds ?? 0
              : di<MyStoreBloc>().state.myStore?.userUsd ?? 0;

      di<BuyCoinsBloc>().add(ChangeValueEvent(
          value: int.tryParse(value.toString()), changeText: true, controllerIndex: _controller.index));
    });
  }

  @override
  Widget build(BuildContext context) {
    final normalPage = BlocBuilder<BuyCoinsBloc, BuyCoinsState>(
      bloc: di<BuyCoinsBloc>(),
      buildWhen: (prev, curr) => prev.controllerIndex != curr.controllerIndex,
      builder: (context, state) {
        return Scaffold(
          backgroundColor: ColorManager.scaffoldBg,
          resizeToAvoidBottomInset: false,
          appBar: AppBarWidget(
            iconColor: ColorManager.white,
            title: StringManager.rechargeCoins.tr(),
            titleStyle: context.bodyMedium
                .size(18)
                .w600
                .colorExt(ColorManager.textPrimary),
            backgroundColor: (state.controllerIndex == 0)
                ? ColorManager.walletCard
                : (!(StringManager.userType[1]! || StringManager.userType[2]!))
                    ? ColorManager.diamondCard
                    : ColorManager.primary,
          ),
          body: DefaultTabController(
            length: _controller.length,
            child: Column(
              children: [
                CoinsTabBar(
                  controller: _controller,
                  color: (state.controllerIndex == 0)
                      ? ColorManager.walletCard
                      : (!(StringManager.userType[1]! ||
                              StringManager.userType[2]!))
                          ? ColorManager.diamondCard
                          : ColorManager.primary,
                  txt: state.controllerIndex == 0
                      ? StringManager.coins.tr()
                      : (!(StringManager.userType[1]! ||
                              StringManager.userType[2]!))
                          ? StringManager.diamond.tr()
                          : StringManager.profits.tr(),
                ),
                Expanded(
                  child: RefreshIndicatorWidget(
                    onRefresh: () async {
                      _goldCoinBloc.add(const FetchCoinsEvent());
                      di<MyStoreBloc>().add(const GetMyStoreEvent());
                    },
                    child: CoinsTabBarView(
                      controller: _controller,
                    ),
                  ),
                )
              ],
            ),
          ),
        );
      },
    );

    return normalPage;
  }
}
