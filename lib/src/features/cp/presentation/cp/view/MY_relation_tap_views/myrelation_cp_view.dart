import 'package:general/src/core/index.dart';
import 'package:general/src/features/cp/cp.dart';
import 'package:general/src/features/cp/domain/entities/cp_relations_levels_gifts_entity.dart';

class MyRelationCpView extends StatefulWidget {
  const MyRelationCpView({super.key});

  @override
  State<MyRelationCpView> createState() => _MyRelationCpViewState();
}

class _MyRelationCpViewState extends State<MyRelationCpView> {
  @override
  void initState() {
    BlocProvider.of<CpRelationsLevelsBloc>(context)
        .add(GetCpRelationsLevelsEvent());

    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: context.paddingSymmetric(vertical: 10),
      child: const Column(
        children: [
          MyCpWidget(),
          MyRelationCpWidget(),
        ],
      ),
    );
  }
}

class MyRelationCpWidget extends StatelessWidget {
  const MyRelationCpWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<CpRelationsLevelsBloc, CpRelationsLevelsState>(
      buildWhen: (prev, curr) => prev.reqStateLevelsGift != curr.reqStateLevelsGift || prev.levelsGift != curr.levelsGift,
      builder: (context, state) {
        return HandlingDataWidget(
            reqState: state.reqStateLevelsGift,
            title: StringManager.noDataYet.tr(),
            subTitle: StringManager.pleaseTryAgine.tr(),
            child: Container(
              width: double.infinity,
              decoration: BoxDecoration(
                border: Border.all(color: ColorManager.transparent),
              ),
              child: Column(
                children: [
                  Stack(
                    children: [
                      Image.asset(
                        AssetsManager.cpContainer,
                        fit: BoxFit.fill,
                        width: double.infinity,
                      ),
                      Align(
                        alignment: Alignment.topCenter,
                        child: Padding(
                          padding: context.paddingOnly(top: 20),
                          child: Text(
                            StringManager.cpLevelPrivileges.tr(),
                            style: context.bodyLarge.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                  Container(
                    decoration: const BoxDecoration(
                      color: ColorManager.colorTextSup,
                      border: Border(
                        left: BorderSide(
                            width: 3, color: ColorManager.blueTabIndicator),
                        right: BorderSide(width: 3, color: ColorManager.gold3),
                      ),
                    ),
                    child: Padding(
                        padding: context.paddingAll(16),
                        child: SizedBox(
                          width: ScreenUtil().screenWidth * 0.85,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.center,
                            children: [
                              Image.asset(
                                AssetsManager.frameThreeRank,
                              ),
                              ...state.levelsGift!
                                  .map((e) => MyRelationLevelWidget(
                                        level: e.level,
                                        title: e.gifts!.first.title!,
                                        imagePath:
                                            AssetsManager.vipBackground(vip: 0),
                                        levelData: e,
                                      )),
                            ],
                          ),
                        )),
                  ),
                  Image.asset(
                    AssetsManager.cp,
                    fit: BoxFit.fill,
                    width: double.infinity,
                  ),
                ],
              ),
            ));
      },
    );
  }
}

class MyRelationLevelWidget extends StatelessWidget {
  final int level;
  final String title;
  final String imagePath;
  final LevelDataEntity levelData;

  const MyRelationLevelWidget({
    required this.level,
    required this.title,
    required this.imagePath,
    required this.levelData,
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Column(
          children: [
            Container(
              width: 3.w,
              height: 6.h,
              color:
                  level > 1 ? ColorManager.hanPurple : ColorManager.transparent,
            ),
            Container(
              width: 50.w,
              height: 50.h,
              decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(color: ColorManager.highlightColor),
                  gradient: const LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: ColorManager.cpFriends,
                  )),
              child: Stack(children: [
                if (!levelData.have)
                  Image.asset(
                    AssetsManager.cp,
                    width: double.infinity,
                    fit: BoxFit.fill,
                  ),
                Center(
                  child: Text(
                    "LVL $level",
                    style: context.bodyMedium.w700
                        .colorExt(ColorManager.textPrimary),
                  ),
                )
              ]),
            ),
            Container(
              width: 3.w,
              height: 55.h,
              color: level < 8
                  ? ColorManager.darkBlackChat
                  : ColorManager.transparent,
            ),
          ],
        ),
        10.wBox,
        Expanded(
          child: Container(
            decoration: BoxDecoration(
              borderRadius: 12.radius,
              color: ColorManager.gold3,
            ),
            child: Padding(
              padding: context.paddingSymmetric(vertical: 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Text(
                    title,
                    style: context.bodyMedium.bold
                        .colorExt(ColorManager.textPrimary),
                  ),
                  10.hBox,
                  if (levelData.gifts != null &&
                      levelData.gifts?.isNotEmpty == true &&
                      levelData.gifts?.first.images?.isNotEmpty == true)
                    ImageViewWidget(
                      url: levelData.gifts!.first.images![0],
                      width: ScreenUtil().screenWidth * 0.6,
                      height: 100.h,
                      boxFit: BoxFit.fill,
                    )
                  // Image.asset(
                  //   imagePath,
                  //   width: double.infinity,
                  //   fit: BoxFit.fill,
                  // ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class MyCpWidget extends StatelessWidget {
  const MyCpWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        border: Border.all(color: ColorManager.transparent),
      ),
      child: Column(
        children: [
          Stack(
            children: [
              Image.asset(
                AssetsManager.frameThreeRank,
                fit: BoxFit.fill,
                width: double.infinity,
              ),
              Align(
                alignment: Alignment.topCenter,
                child: Padding(
                  padding: context.paddingOnly(top: 20),
                  child: Text(
                    StringManager.myCp.tr(),
                    style: context.bodyLarge.bold
                        .colorExt(ColorManager.textPrimary),
                  ),
                ),
              ),
            ],
          ),
          Container(
            decoration: const BoxDecoration(
              color: ColorManager.cardColor,
              border: Border(
                left: BorderSide(width: 3, color: ColorManager.colorTextSup),
                right: BorderSide(width: 3, color: ColorManager.colorTextSup),
              ),
            ),
            child: Padding(
              padding: context.paddingAll(16),
              child: SizedBox(
                width: ScreenUtil().screenWidth * 0.85,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        BlocBuilder<CpProfileBloc, CpProfileStates>(
                          buildWhen: (prev, curr) => prev.reqStates != curr.reqStates || prev.data != curr.data,
                          builder: (context, state) {
                            return HandlingDataWidget(
                              reqState: state.reqStates,
                              title: StringManager.noDataYet.tr(),
                              subTitle: StringManager.pleaseTryAgine.tr(),
                              child: state.data?.mainCp != null
                                  ? UserImage(
                                      image:
                                          state.data?.mainCp?.user?.image ?? "",
                                      displayName:
                                          state.data?.mainCp?.user?.name ?? '',
                                      imageSize: 70.h,
                                      frameSize: 100.h,
                                    )
                                  : Column(
                                      children: [
                                        Image.asset(
                                          AssetsManager.frameOne,
                                          fit: BoxFit.fill,
                                          width: 60.w,
                                          height: 60.h,
                                        ),
                                        InkWell(
                                          onTap: () {
                                            Navigator.pushNamed(
                                                context, Routes.cpStorePage);
                                          },
                                          child: Padding(
                                            padding: context.paddingSymmetric(
                                                vertical: 10, horizontal: 20),
                                            child: Container(
                                              decoration: BoxDecoration(
                                                borderRadius: 20.radius,
                                                color: ColorManager
                                                    .dailyPrizeBackground,
                                              ),
                                              child: Padding(
                                                padding: context.paddingAll(10),
                                                child: Text(
                                                  StringManager.invite.tr(),
                                                  style: context.bodySmall.bold,
                                                ),
                                              ),
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                            );
                          },
                        ),
                        Padding(
                          padding: context.paddingOnly(top: 10),
                          child: Image.asset(
                            AssetsManager.cp,
                            fit: BoxFit.fill,
                            width: 80.w,
                            height: 80.h,
                          ),
                        ),
                        UserImage(
                          image: MyDataModel.getInstance().profile?.image ?? "",
                          displayName: MyDataModel.getInstance().name ?? '',
                          imageSize: 70.h,
                          frameSize: 100.h,
                          frame: MyDataModel.getInstance().frame,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
          Image.asset(
            AssetsManager.cp,
            fit: BoxFit.fill,
            width: double.infinity,
          ),
        ],
      ),
    );
  }
}
