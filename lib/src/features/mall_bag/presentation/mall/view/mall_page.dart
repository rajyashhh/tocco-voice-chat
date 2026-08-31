import 'dart:io';
import 'package:general/src/core/cache/image_cache_manager.dart';
import 'package:general/src/core/cache/alpha_cache_manager.dart';
import 'package:general/src/core/cache/svga_cache_manager.dart';
import 'package:general/src/core/cache/vap_cache_manager.dart';
import 'package:general/src/core/cache/video_cache_manager.dart';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/mall_bag/mall_bag.dart';
import 'package:general/src/features/mall_bag/presentation/mall/bloc/mall_send_bloc/mall_send_bloc.dart';
import '../../../../../core/widgets/md_indicator.dart';
import '../../../../profile/presentation/coins/bloc/my_store_bloc/my_store_bloc.dart';
import '../../bag/view/widgets/send_bottom_sheet.dart';
import '../../component/custom_model_bottom_sheet.dart';

part '../../component/mall_tab_bar_item.dart';
part 'components/mall_tab_bar.dart';
part 'components/mall_tab_bar_view.dart';
part 'widgets/mall_product_item.dart';

class MallPage extends StatefulWidget {
  final int? index;

  const MallPage({this.index, super.key});

  @override
  State<MallPage> createState() => _MallPageState();
}

class _MallPageState extends State<MallPage> with TickerProviderStateMixin {
  late TabController controller;

  final MallBloc _mallBloc = di<MallBloc>();

  @override
  void initState() {
    super.initState();
    controller =
        TabController(length: 5, vsync: this, initialIndex: widget.index ?? 0);
    di<MallBloc>().add(ChangeAppBarUIMallEvent(index: widget.index ?? 0));

    if (_mallBloc.state.bubbleMallRequest != RequestState.loaded &&
        controller.index == 0) {
      _mallBloc.add(const GetBubbleMallEvent(isLoading: true));
    }
    controller.addListener(
      () {
        di<MallBloc>().add(ChangeAppBarUIMallEvent(index: controller.index));

        if (_mallBloc.state.frameMallRequest != RequestState.loaded &&
            controller.index == 1) {
          _mallBloc.add(const GetFramesMallEvent(isLoading: true));
        }
        if (_mallBloc.state.carMallRequest != RequestState.loaded &&
            controller.index == 2) {
          _mallBloc.add(const GetCarMallEvent(isLoading: true));
        }
        if (_mallBloc.state.bubbleMallRequest != RequestState.loaded &&
            controller.index == 0) {
          _mallBloc.add(const GetBubbleMallEvent(isLoading: true));
        }
        if (_mallBloc.state.emojisMallRequest != RequestState.loaded &&
            controller.index == 3) {
          _mallBloc.add(const GetSpecialIdMallEvent(isLoading: true));
        }
        if (_mallBloc.state.profileFramesMallRequest != RequestState.loaded &&
            controller.index == 4) {
          _mallBloc.add(const GetProfileFramesMallEvent(isLoading: true));
        }
      },
    );
  }

  @override
  void dispose() {
    controller.dispose();
    di<MallBloc>().add(const ChangeAppBarUIMallEvent(index: 0));
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final normalPage = BlocListener<MallSendBloc, MallSendState>(
      bloc: di<MallSendBloc>(),
      listener: (context, state) {
        if (state is SendSuccessState) {
          Methods.showToast(context, message: state.message);
        } else if (state is SendErrorState) {
          Methods.showToast(context, message: state.message, isError: true);
        } else if (state is SendLoadingState) {
          Methods.showToast(context, isLoading: true);
        }
      },
      child: BlocListener<MallBuyBloc, MallBuyState>(
        bloc: di<MallBuyBloc>(),
        listener: (_, state) {
          if (state is BuySuccessState) {
            Methods.showToast(context, message: state.massage);
          } else if (state is BuyErrorState) {
            Methods.showToast(context, message: state.massage, isError: true);
          } else if (state is BuyLoadingState) {
            Methods.showToast(context, isLoading: true);
          }
        },
        child: BlocBuilder<MallBloc, GetDataMallStates>(
          bloc: _mallBloc,
          buildWhen: (prev, curr) =>
              prev.bubbleMallRequest != curr.bubbleMallRequest ||
              prev.bubblesMall != curr.bubblesMall ||
              prev.frameMallRequest != curr.frameMallRequest ||
              prev.framesMall != curr.framesMall ||
              prev.carMallRequest != curr.carMallRequest ||
              prev.carsMall != curr.carsMall ||
              prev.emojisMallRequest != curr.emojisMallRequest ||
              prev.emojisMall != curr.emojisMall ||
              prev.profileFramesMallRequest != curr.profileFramesMallRequest ||
              prev.profileFramesMall != curr.profileFramesMall ||
              prev.selectedItem != curr.selectedItem,
          builder: (context, state) {
            return Scaffold(
              backgroundColor: ColorManager.scaffoldBgAlt,
              appBar: AppBarWidget(
                backgroundColor: ColorManager.scaffoldBg,
                title: StringManager.store.tr(),
                actions: [
                  TextButton(
                    onPressed: () {
                      Navigator.pushNamed(context, Routes.bagScreen);
                    },
                    child: TextWidget(
                      StringManager.mine.tr(),
                      style: context.bodyMedium
                          .size(15)
                          .w500
                          .colorExt(ColorManager.secondaryText),
                    ),
                  )
                ],
              ),
              body: Column(
                children: [
                  _MallTabBar(controller: controller),
                  5.hBox,
                  Expanded(
                    child: TabBarView(
                      controller: controller,
                      children: [
                        HandlingDataWidget(
                          title: StringManager.tittleEmptySpecialId.tr(),
                          subTitle: StringManager.subTittleEmptySpecialId.tr(),
                          reqState: state.bubbleMallRequest,
                          isNeedLoadingWidget: true,
                          onTap: () {
                            _mallBloc.add(const GetBubbleMallEvent());
                          },
                          child: _MallTabBarView(
                            mall: state.bubblesMall,
                            tabType: MallOrBagType.bubble,
                            isLoading: state.bubbleMallRequest.isLoading,
                          ),
                        ),
                        HandlingDataWidget(
                          title: StringManager.tittleEmptyFrame.tr(),
                          subTitle: StringManager.subTittleEmptyFrame.tr(),
                          reqState: state.frameMallRequest,
                          isNeedLoadingWidget: true,
                          onTap: () {
                            _mallBloc.add(const GetFramesMallEvent());
                          },
                          child: _MallTabBarView(
                            mall: state.framesMall,
                            tabType: MallOrBagType.frame,
                            isLoading: state.frameMallRequest.isLoading,
                          ),
                        ),
                        HandlingDataWidget(
                          title: StringManager.tittleEmptyIntro.tr(),
                          subTitle: StringManager.subTittleEmptyIntro.tr(),
                          reqState: state.carMallRequest,
                          isNeedLoadingWidget: true,
                          onTap: () {
                            _mallBloc.add(const GetCarMallEvent());
                          },
                          child: _MallTabBarView(
                            mall: state.carsMall,
                            tabType: MallOrBagType.intro,
                            isLoading: state.carMallRequest.isLoading,
                          ),
                        ),
                        HandlingDataWidget(
                          title: StringManager.tittleEmptySpecialId.tr(),
                          subTitle: StringManager.subTittleEmptySpecialId.tr(),
                          reqState: state.emojisMallRequest,
                          isNeedLoadingWidget: true,
                          onTap: () {
                            _mallBloc.add(const GetSpecialIdMallEvent());
                          },
                          child: _MallTabBarView(
                            mall: state.emojisMall,
                            tabType: MallOrBagType.specialId,
                            isLoading: state.emojisMallRequest.isLoading,
                          ),
                        ),
                        HandlingDataWidget(
                          title: StringManager.tittleEmptyProfileFrame.tr(),
                          subTitle:
                              StringManager.subTittleEmptyProfileFrame.tr(),
                          reqState: state.profileFramesMallRequest,
                          isNeedLoadingWidget: true,
                          onTap: () {
                            _mallBloc.add(const GetProfileFramesMallEvent());
                          },
                          child: _MallTabBarView(
                            mall: state.profileFramesMall,
                            tabType: MallOrBagType.profileFrame,
                            isLoading: state.profileFramesMallRequest.isLoading,
                          ),
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

    return normalPage;
  }
}
