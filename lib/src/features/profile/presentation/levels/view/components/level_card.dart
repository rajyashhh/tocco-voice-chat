part of 'package:general/src/features/profile/presentation/levels/view/level_page.dart';

class LevelCard extends StatelessWidget {
  final bool isSender;
  final Color color;
  final String text;
  final String type;

  const LevelCard({
    super.key,
    required this.type,
    required this.isSender,
    required this.color,
    required this.text,
  });

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<LevelBloc, AllLevelsState>(
      bloc: di<LevelBloc>(),
      buildWhen: (prev, curr) =>
          prev.userLevelsRequest != curr.userLevelsRequest ||
          prev.userLevels != curr.userLevels ||
          prev.changeImage != curr.changeImage,
      builder: (context, state) {
        return HandlingDataWidget(
          // Fixed-dark level screen: empty-state text stays light everywhere.
          titleStyle: context.bodyLarge.colorExt(ColorManager.onDark),
          reqState: state.userLevelsRequest,
          title: StringManager.noLevels.tr(),
          subTitle: StringManager.noLevelsMsg.tr(),
          onTap: () {
            di<LevelBloc>().add(GetUserLevels());
          },
          child: Stack(
            children: [
              Container(
                clipBehavior: Clip.none,
                padding: context.paddingAll(8),
                margin: context.paddingAll(25),
                width: 370.w,
                height: 180.h,
                decoration: BoxDecoration(
                  borderRadius: 10.radius,
                  border: Border.all(
                    color: ColorManager.white,
                    width: 1,
                  ),
                  color: color.withValues(alpha: (1)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        UserImage(
                          image: MyDataModel.getInstance().profile?.image ?? '',
                          displayName: MyDataModel.getInstance().name ?? '',
                          borderRadius: 170.radius,
                          imageSize: 55.w,
                          border:
                              Border.all(color: ColorManager.white, width: 2),
                        ),
                        5.wBox,
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              TextWidget(
                                "$text ${StringManager.level(_getCurrentLevel(type, state))}",
                                style: context.bodyMedium.bold
                                    .size(18)
                                    .colorExt(ColorManager.onDark),
                              ),
                              7.hBox,
                              LinearPercentIndicator(
                                barRadius: Radius.circular(10.r),
                                animateFromLastPercent: true,
                                width: 250.w,
                                animation: true,
                                padding: context.paddingZero(),
                                percent: _getPercent(type, state),
                                backgroundColor:
                                    ColorManager.black.withValues(alpha: (0.3)),
                                progressColor: ColorManager.white,
                              ),
                              SizedBox(
                                width: 250.w,
                                child: Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  mainAxisSize: MainAxisSize.max,
                                  children: [
                                    TextWidget(
                                      StringManager.level(
                                          _getCurrentLevel(type, state)),
                                      style: context.bodyMedium
                                          .size(15)
                                          .colorExt(ColorManager.onDark),
                                    ),
                                    TextWidget(
                                      StringManager.level(
                                          _getNextLevel(type, state)),
                                      style: context.bodyMedium
                                          .size(15)
                                          .colorExt(ColorManager.onDark),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    10.hBox,
                    Container(
                      height: 80.h,
                      alignment: Alignment.center,
                      padding: context.paddingAll(10),
                      decoration: BoxDecoration(
                        color: ColorManager.white.withValues(alpha: (0.2)),
                        borderRadius: 12.radius,
                      ),
                      child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            SizedBox(
                              height: 80.h,
                              child: Column(
                                mainAxisSize: MainAxisSize.min,
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceEvenly,
                                children: [
                                  TextWidget(
                                    _getCurrentExp(type, state),
                                    style: context.bodyMedium.bold
                                        .size(15)
                                        .colorExt(ColorManager.onDark),
                                  ),
                                  TextWidget(
                                    StringManager.currentExperienceLevel.tr(),
                                    style: context.bodySmall
                                        .size(12)
                                        .colorExt(ColorManager.onDark),
                                  ),
                                ],
                              ),
                            ),
                            Container(
                              height: 40.h,
                              width: 1.w,
                              decoration: BoxDecoration(
                                color:
                                    ColorManager.white.withValues(alpha: (0.6)),
                                borderRadius: 5.radius,
                              ),
                            ),
                            SizedBox(
                              height: 80.h,
                              child: Column(
                                mainAxisSize: MainAxisSize.min,
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceEvenly,
                                children: [
                                  TextWidget(
                                    _getRemaining(type, state),
                                    style: context.bodyMedium.bold
                                        .size(15)
                                        .colorExt(ColorManager.onDark),
                                  ),
                                  TextWidget(
                                    StringManager.nextlevel.tr(),
                                    style: context.bodySmall
                                        .size(12)
                                        .colorExt(ColorManager.onDark),
                                  ),
                                ],
                              ),
                            ),
                          ]),
                    ),
                  ],
                ),
              ),
              Positioned(
                top: 20,
                right: 30,
                child: Opacity(
                  opacity: 0.1,
                  child: state.changeImage
                      ? CoinIcon(
                          width: 130.w,
                          fit: BoxFit.fill,
                          fallbackAsset: AssetsManager.coinsVip,
                        )
                      : Image.asset(
                          AssetsManager.diamondsIcon,
                          width: 130.w,
                          fit: BoxFit.fill,
                        ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  String _getCurrentLevel(String type, AllLevelsState state) {
    switch (type) {
      case 'sender':
        return state.userLevels?.level?.senderLevel.toString() ?? '';
      case 'reciever':
        return state.userLevels?.level?.reciverLevel.toString() ?? '';
      case 'charge':
        return state.userLevels?.chargeLevel?.currentLevel.toString() ?? '';
      case 'room':
        return state.userLevels?.roomLevel?.currentLevel.toString() ?? '';
      default:
        return '';
    }
  }

  String _getNextLevel(String type, AllLevelsState state) {
    switch (type) {
      case 'sender':
        return state.userLevels?.level?.nextSenderLevel.toString() ?? '';
      case 'reciever':
        return state.userLevels?.level?.nextReciverLevel.toString() ?? '';
      case 'charge':
        return state.userLevels?.chargeLevel?.nextLevel.toString() ?? '';
      case 'room':
        return state.userLevels?.roomLevel?.nextLevel.toString() ?? '';
      default:
        return '';
    }
  }

  double _getPercent(String type, AllLevelsState state) {
    switch (type) {
      case 'sender':
        return (state.userLevels?.level?.senderPer?.toDouble() ?? 0.0) / 100;
      case 'reciever':
        return (state.userLevels?.level?.reciverPer?.toDouble() ?? 0.0) / 100;
      case 'charge':
        return (state.userLevels?.chargeLevel?.progress?.toDouble() ?? 0.0);
      case 'room':
        return (state.userLevels?.roomLevel?.progress?.toDouble() ?? 0.0);
      default:
        return 0.0;
    }
  }

  String _getCurrentExp(String type, AllLevelsState state) {
    switch (type) {
      case 'sender':
        return state.userLevels?.level?.senderNum.toString() ?? '';
      case 'reciever':
        return state.userLevels?.level?.reciverNum.toString() ?? '';
      case 'charge':
        return state.userLevels?.chargeLevel?.currentExp.toString() ?? '';
      case 'room':
        return state.userLevels?.roomLevel?.currentExp.toString() ?? '';
      default:
        return '';
    }
  }

  String _getRemaining(String type, AllLevelsState state) {
    switch (type) {
      case 'sender':
        return state.userLevels?.level?.remSenderLevel.toString() ?? '';
      case 'reciever':
        return state.userLevels?.level?.remReceiverLevel.toString() ?? '';
      case 'charge':
        return state.userLevels?.chargeLevel?.remaining.toString() ?? '';
      case 'room':
        return state.userLevels?.roomLevel?.remaining.toString() ?? '';
      default:
        return '';
    }
  }
}
