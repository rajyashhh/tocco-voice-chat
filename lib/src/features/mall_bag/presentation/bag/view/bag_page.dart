import 'dart:io';
import 'package:general/src/core/cache/image_cache_manager.dart';
import 'package:general/src/core/cache/alpha_cache_manager.dart';
import 'package:general/src/core/cache/svga_cache_manager.dart';
import 'package:general/src/core/cache/vap_cache_manager.dart';
import 'package:general/src/core/cache/video_cache_manager.dart';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/mall_bag/mall_bag.dart';
import 'package:general/src/features/mall_bag/presentation/bag/view/widgets/send_bottom_sheet.dart';
import 'package:skeletonizer/skeletonizer.dart';

import '../../../../../core/widgets/md_indicator.dart';
import '../../component/custom_model_bottom_sheet.dart';

part '../view/components/bag_tab_bar.dart';

part '../view/components/bag_tab_bar_view.dart';

part '../view/widgets/bag_product_item.dart';

class BagPage extends StatefulWidget {
  const BagPage({super.key});

  @override
  State<BagPage> createState() => _BagPageState();
}

class _BagPageState extends State<BagPage> with TickerProviderStateMixin {
  late TabController controller;
  late ValueNotifier<int> valueListenable;
  final MyBagBloc _bloc = di<MyBagBloc>();

  @override
  void initState() {
    super.initState();
    valueListenable = ValueNotifier<int>(0);
    controller = TabController(length: 5, vsync: this);
    if (_bloc.state.bubbleBagRequest != RequestState.loaded &&
        controller.index == 0) {
      _bloc.add(const GetBubbleBackPackMyBagEvent(isLoading: true));
    }
    controller.addListener(
      () {
        di<MyBagBloc>().add(ChangeAppBarUIEvent(index: controller.index));

        if (_bloc.state.frameBagRequest != RequestState.loaded &&
            controller.index == 1) {
          _bloc.add(const GetFramesMyBagEvent(isLoading: true));
        }
        if (_bloc.state.carBagRequest != RequestState.loaded &&
            controller.index == 2) {
          _bloc.add(const GetEntrieMyBagEvent(isLoading: true));
        }
        if (_bloc.state.bubbleBagRequest != RequestState.loaded &&
            controller.index == 0) {
          _bloc.add(const GetBubbleBackPackMyBagEvent(isLoading: true));
        }
        if (_bloc.state.emojisBagRequest != RequestState.loaded &&
            controller.index == 3) {
          _bloc.add(const GetSpecialIdMyBagEvent(isLoading: true));
        }
        if (_bloc.state.profileFrameRequest != RequestState.loaded &&
            controller.index == 4) {
          _bloc.add(const GetProfileFrameMyBagEvent(isLoading: true));
        }

        valueListenable.value++;
      },
    );
  }

  @override
  void dispose() {
    controller.dispose();
    valueListenable.value = 0;
    di<MyBagBloc>().add(const ChangeAppBarUIEvent(index: 0));
    di<MyBagBloc>().add(const SelectBagItemEvent(selectedItem: null));

    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final normalPage = BlocBuilder<UseUnUseBloc, UseUnUseState>(
      bloc: di<UseUnUseBloc>(),
      buildWhen: (prev, curr) => false,
      builder: (context, state) {
        return BlocBuilder<MyBagBloc, MyBagState>(
          bloc: _bloc,
          buildWhen: (prev, curr) =>
              prev.bubbleBagRequest != curr.bubbleBagRequest ||
              prev.bubblesBag != curr.bubblesBag ||
              prev.selectedItemBubble != curr.selectedItemBubble ||
              prev.frameBagRequest != curr.frameBagRequest ||
              prev.framesBag != curr.framesBag ||
              prev.selectedItemFrame != curr.selectedItemFrame ||
              prev.carBagRequest != curr.carBagRequest ||
              prev.carsBag != curr.carsBag ||
              prev.selectedItemIntro != curr.selectedItemIntro ||
              prev.emojisBagRequest != curr.emojisBagRequest ||
              prev.emojisBag != curr.emojisBag ||
              prev.selectedItemSpecialId != curr.selectedItemSpecialId ||
              prev.profileFrameRequest != curr.profileFrameRequest ||
              prev.profileFrames != curr.profileFrames ||
              prev.selectedItemProfileFrame != curr.selectedItemProfileFrame,
          builder: (context, state) {
            return Scaffold(
              backgroundColor: ColorManager.scaffoldBgAlt,
              appBar: AppBarWidget(
                backgroundColor: ColorManager.scaffoldBg,
                title: StringManager.mine.tr(),
              ),
              body: Column(
                children: [
                  BagTabBar(controller: controller),
                  5.hBox,
                  Expanded(
                    child: TabBarView(
                      controller: controller,
                      children: [
                        HandlingDataWidget(
                          isNeedLoadingWidget: true,
                          reqState: state.bubbleBagRequest,
                          title: StringManager.titleEmptyBubbles.tr(),
                          subTitle: StringManager.subEmptyBubbles.tr(),
                          onTap: () =>
                              _bloc.add(const GetBubbleBackPackMyBagEvent()),
                          child: BagTabBarView(
                            bagList: state.bubblesBag,
                            selectedItem: state.selectedItemBubble,
                            tabType: MallOrBagType.bubble,
                            isLoading: state.bubbleBagRequest.isLoading,
                          ),
                        ),
                        HandlingDataWidget(
                          isNeedLoadingWidget: true,
                          reqState: state.frameBagRequest,
                          title: StringManager.titleEmptyFrame.tr(),
                          subTitle: StringManager.subEmptyFrame.tr(),
                          onTap: () => _bloc.add(const GetFramesMyBagEvent()),
                          child: BagTabBarView(
                            bagList: state.framesBag,
                            tabType: MallOrBagType.frame,
                            selectedItem: state.selectedItemFrame,
                            isLoading: state.frameBagRequest.isLoading,
                          ),
                        ),
                        HandlingDataWidget(
                          isNeedLoadingWidget: true,
                          reqState: state.carBagRequest,
                          title: StringManager.titleEmptyCar.tr(),
                          subTitle: StringManager.subEmptyCar.tr(),
                          onTap: () => _bloc.add(const GetEntrieMyBagEvent()),
                          child: BagTabBarView(
                            bagList: state.carsBag,
                            tabType: MallOrBagType.intro,
                            selectedItem: state.selectedItemIntro,
                            isLoading: state.carBagRequest.isLoading,
                          ),
                        ),
                        HandlingDataWidget(
                          isNeedLoadingWidget: true,
                          reqState: state.emojisBagRequest,
                          title: StringManager.tittleEmptyProfileFrame.tr(),
                          subTitle:
                              StringManager.subTittleEmptyProfileFrame.tr(),
                          onTap: () =>
                              _bloc.add(const GetSpecialIdMyBagEvent()),
                          child: BagTabBarView(
                            bagList: state.emojisBag,
                            selectedItem: state.selectedItemSpecialId,
                            tabType: MallOrBagType.specialId,
                            isLoading: state.emojisBagRequest.isLoading,
                          ),
                        ),
                        HandlingDataWidget(
                          isNeedLoadingWidget: true,
                          reqState: state.profileFrameRequest,
                          title: StringManager.tittleEmptyProfileFrame.tr(),
                          subTitle:
                              StringManager.subTittleEmptyProfileFrame.tr(),
                          onTap: () =>
                              _bloc.add(const GetSpecialIdMyBagEvent()),
                          child: BagTabBarView(
                            bagList: state.profileFrames,
                            selectedItem: state.selectedItemProfileFrame,
                            tabType: MallOrBagType.profileFrame,
                            isLoading: state.profileFrameRequest.isLoading,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );

    return normalPage;
  }
}
