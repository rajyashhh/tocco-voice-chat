import 'package:general/src/core/index.dart';
import 'package:general/src/features/room/presentation/gifts/gift.dart';
import '../bloc/levels_bloc/levels_bloc.dart';
import '../bloc/levels_bloc/levels_event.dart';
import '../bloc/levels_bloc/levels_state.dart';

class RoomLevelPage extends StatefulWidget {
  const RoomLevelPage({super.key});

  @override
  State<RoomLevelPage> createState() => _RoomLevelPageState();
}

class _RoomLevelPageState extends State<RoomLevelPage> {
  @override
  void initState() {
    di<LevelBloc>().add(GetUserLevels());
    di<LevelBloc>().add(GetRoomLevelBadges());
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    final normalPage = BlocBuilder<LevelBloc, AllLevelsState>(
      bloc: di<LevelBloc>(),
      buildWhen: (prev, curr) =>
          prev.roomLevelBadgesRequest != curr.roomLevelBadgesRequest ||
          prev.userLevels != curr.userLevels,
      builder: (context, state) {
        return MediaQuery(
          data: MediaQueryData.fromView(View.of(context)),
          child: Scaffold(
            backgroundColor: ColorManager.bgLevel,
            appBar: AppBarWidget(
              title: StringManager.roomLevel.tr(),
              iconColor: ColorManager.white,
              backgroundColor: ColorManager.bgLevel,
              titleStyle: context.bodyLarge.bold.colorExt(ColorManager.onDark),
            ),
            body: HandlingDataWidget(
              titleStyle: context.bodyLarge.colorExt(ColorManager.onDark),
              reqState: state.roomLevelBadgesRequest,
              title: StringManager.noLevels.tr(),
              subTitle: StringManager.noLevelsMsg.tr(),
              onTap: () {
                di<LevelBloc>().add(GetUserLevels());
                di<LevelBloc>().add(GetRoomLevelBadges());
              },
              child: SingleChildScrollView(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    _generateLevelCard(context, state),
                    Padding(
                      padding: context.paddingAll(10),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          TextWidget(
                            StringManager.roomLevelDescription.tr(),
                            style: context.bodyMedium
                                .colorExt(ColorManager.onDark)
                                .size(15)
                                .w600,
                          ),
                          10.hBox,
                          TextWidget(
                            StringManager.roomLevelDescriptionBody.tr(),
                            style: context.bodyMedium
                                .size(15)
                                .colorExt(ColorManager.greyTextColor),
                          ),
                        ],
                      ),
                    ),
                    20.hBox,
                    _buildBadgesTable(context, state),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
    return normalPage;
  }

  Widget _generateLevelCard(BuildContext context, AllLevelsState state) {
    final roomLevel = state.userLevels?.roomLevel;
    final currentLevel = roomLevel?.currentLevel ?? 0;
    final nextLevel = roomLevel?.nextLevel ?? 0;
    final progress = (roomLevel?.progress ?? 0) / 100;
    final currentExp = roomLevel?.currentExp ?? 0;
    final remaining = roomLevel?.remaining ?? 0;

    return Container(
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
        color: ColorManager.green.withValues(alpha: 1),
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
                imageSize: 60.w,
                border: Border.all(color: ColorManager.white, width: 2),
              ),
              10.wBox,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  TextWidget(
                    "${StringManager.room.tr()} ${StringManager.level(currentLevel.toString())}",
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
                    percent: progress.clamp(0.0, 1.0),
                    backgroundColor: ColorManager.black.withValues(alpha: 0.3),
                    progressColor: ColorManager.white,
                  ),
                  SizedBox(
                    width: 250.w,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      mainAxisSize: MainAxisSize.max,
                      children: [
                        TextWidget(
                          StringManager.level(currentLevel.toString()),
                          style: context.bodyMedium
                              .size(15)
                              .colorExt(ColorManager.onDark),
                        ),
                        TextWidget(
                          StringManager.level(nextLevel.toString()),
                          style: context.bodyMedium
                              .size(15)
                              .colorExt(ColorManager.onDark),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
          10.hBox,
          Container(
            height: 80.h,
            alignment: Alignment.center,
            padding: context.paddingAll(10),
            decoration: BoxDecoration(
              color: ColorManager.white.withValues(alpha: 0.2),
              borderRadius: 12.radius,
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                SizedBox(
                  height: 80.h,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      TextWidget(
                        currentExp.toString(),
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
                    color: ColorManager.white.withValues(alpha: 0.6),
                    borderRadius: 5.radius,
                  ),
                ),
                SizedBox(
                  height: 80.h,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      TextWidget(
                        remaining.toString(),
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
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBadgesTable(BuildContext context, AllLevelsState state) {
    final data = state.roomLevelBadges;
    return Container(
      clipBehavior: Clip.hardEdge,
      margin: context.paddingSymmetric(horizontal: 16),
      decoration: BoxDecoration(
        color: const Color(0xFF18183a).withValues(alpha: 0.2),
        borderRadius: 16.radius,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: context.paddingSymmetric(vertical: 16),
            decoration: BoxDecoration(
              color: const Color(0xFF18183a).withValues(alpha: 0.2),
              borderRadius: BorderRadius.only(
                topLeft: 16.radiusCircular,
                topRight: 16.radiusCircular,
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                Text(
                  StringManager.theLevel.tr(),
                  style: context.bodyMedium.w600.colorExt(ColorManager.onDark),
                ),
                Text(
                  StringManager.icLevel.tr(),
                  style: context.bodyMedium.w600.colorExt(ColorManager.onDark),
                ),
              ],
            ),
          ),
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemBuilder: (context, index) {
              var item = index % 2 == 0;
              return Container(
                padding: context.paddingSymmetric(vertical: 12, horizontal: 20),
                decoration: BoxDecoration(
                  color: item
                      ? const Color(0xff252645)
                      : const Color(0xFF18183a).withValues(alpha: 0.2),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    Text(
                      "${data[index].level}",
                      style: context.bodyMedium.copyWith(
                        color: ColorManager.onDark,
                        fontSize: 16,
                      ),
                    ),
                    ImageViewWidget(
                      url: data[index].badge ?? "",
                      width: 70.w,
                      height: 25.h,
                      boxFit: BoxFit.contain,
                    ),
                  ],
                ),
              );
            },
            itemCount: data.length,
          ),
        ],
      ),
    );
  }
}
