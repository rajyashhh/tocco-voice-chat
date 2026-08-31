import 'package:general/src/core/index.dart';
import 'package:general/src/features/cp/cp.dart';
import 'package:general/src/features/cp/presentation/cp/view/MY_relation_tap_views/myrelation_cp_view.dart';

class MyRelationSpecialFriendsView extends StatefulWidget {
  const MyRelationSpecialFriendsView({super.key});

  @override
  State<MyRelationSpecialFriendsView> createState() =>
      _MyRelationSpecialFriendsViewState();
}

class _MyRelationSpecialFriendsViewState
    extends State<MyRelationSpecialFriendsView> {
  @override
  void initState() {
    BlocProvider.of<CpRelationsLevelsBloc>(context)
        .add(GetCpRelationsSpecialFriendLevelsEvent());

    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: context.paddingSymmetric(vertical: 10),
      child: const Column(
        children: [
          SpecialFriendWidget(),
          MyRelationSpecialFriendWidget(),
        ],
      ),
    );
  }
}

class MyRelationSpecialFriendWidget extends StatelessWidget {
  const MyRelationSpecialFriendWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<CpRelationsLevelsBloc, CpRelationsLevelsState>(
      buildWhen: (prev, curr) => prev.reqStateSpecialFriendsGift != curr.reqStateSpecialFriendsGift || prev.specialFriendsGift != curr.specialFriendsGift,
      builder: (context, state) {
        return HandlingDataWidget(
          reqState: state.reqStateSpecialFriendsGift,
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
                      AssetsManager.heartCp,
                      fit: BoxFit.fill,
                      width: double.infinity,
                    ),
                    Align(
                      alignment: Alignment.topCenter,
                      child: Padding(
                        padding: context.paddingOnly(top: 20),
                        child: Text(
                            StringManager.relationSpecialFriendPrivileges.tr(),
                            style: context.bodyLarge.bold),
                      ),
                    ),
                  ],
                ),
                Container(
                  decoration: const BoxDecoration(
                    color: ColorManager.colorTextSup,
                    border: Border(
                      left: BorderSide(
                          width: 3, color: ColorManager.veryVeryLightBlack),
                      right: BorderSide(
                          width: 3, color: ColorManager.dailyPrizeBackground),
                    ),
                  ),
                  child: Padding(
                      padding: context.paddingAll(16),
                      child: SizedBox(
                        width: ScreenUtil().screenWidth * 0.85,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            ...state.specialFriendsGift!
                                .map((e) => MyRelationLevelWidget(
                                      level: e.level,
                                      title: e.gifts!.first.title!,
                                      imagePath: AssetsManager.acceptIcon,
                                      levelData: e,
                                    )),
                          ],
                        ),
                      )),
                ),
                Image.asset(
                  AssetsManager.vipBackground6,
                  fit: BoxFit.fill,
                  width: double.infinity,
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class LevelWidget extends StatelessWidget {
  final int level;
  final String title;
  final String imagePath;

  const LevelWidget({
    required this.level,
    required this.title,
    required this.imagePath,
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
              height: 55.h,
              color: level > 1
                  ? ColorManager.veryLightBlack
                  : ColorManager.transparent,
            ),
            Container(
              width: 50.w,
              height: 50.h,
              decoration: BoxDecoration(
                  //color: Colors.purple,
                  shape: BoxShape.circle,
                  border: Border.all(color: ColorManager.dailyPrizeBackground),
                  gradient: const LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: ColorManager.cpCouple,
                  )),
              child: Stack(children: [
                Image.asset(
                  AssetsManager.vipBackground6,
                  width: double.infinity,
                  fit: BoxFit.fill,
                ),
                Center(
                  child: Text("LVL $level", style: context.bodyMedium.w900),
                )
              ]),
            ),
            Container(
              width: 3.w,
              height: 55.h,
              color:
                  level < 2 ? ColorManager.divider : ColorManager.transparent,
            ),
          ],
        ),
        10.wBox,
        Expanded(
          child: Container(
            decoration: BoxDecoration(
              borderRadius: 12.radius,
              color: ColorManager.colorTextSup,
            ),
            child: Padding(
              padding: context.paddingSymmetric(vertical: 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Text(title,
                      style: context.bodyMedium.bold
                          .colorExt(ColorManager.textPrimary)),
                  10.hBox,
                  Image.asset(
                    imagePath,
                    width: double.infinity,
                    fit: BoxFit.fill,
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class SpecialFriendWidget extends StatelessWidget {
  const SpecialFriendWidget({super.key});

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
                AssetsManager.dailyPrizeBackground,
                fit: BoxFit.fill,
                width: double.infinity,
              ),
              Align(
                alignment: Alignment.topCenter,
                child: Padding(
                  padding: context.paddingOnly(top: 20),
                  child: Text(
                    StringManager.specialFriend.tr(),
                    style: context.bodyMedium.bold,
                  ),
                ),
              ),
            ],
          ),
          Container(
            decoration: const BoxDecoration(
              color: ColorManager.cardColor,
              border: Border(
                left: BorderSide(width: 3, color: ColorManager.appBarTitlegrey),
                right: BorderSide(width: 3, color: ColorManager.checkBoxColor),
              ),
            ),
            child: Padding(
              padding: context.paddingAll(16),
              child: SizedBox(
                width: ScreenUtil().screenWidth * 0.85,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    BlocBuilder<CpProfileBloc, CpProfileStates>(
                      buildWhen: (prev, curr) => prev.reqStates != curr.reqStates || prev.data != curr.data,
                      builder: (context, state) {
                        final List<RemainingCp>? filteredItems = state
                            .data?.remainingCp
                            ?.where((item) => item.relation?.type == "friend")
                            .toList();
                        return HandlingDataWidget(
                            reqState: state.reqStates,
                            title: StringManager.noDataYet.tr(),
                            subTitle: StringManager.pleaseTryAgine.tr(),
                            child: filteredItems!.isNotEmpty
                                ? GridView.builder(
                                    gridDelegate:
                                        const SliverGridDelegateWithFixedCrossAxisCount(
                                      crossAxisCount: 4,
                                      crossAxisSpacing: 10,
                                      mainAxisSpacing: 10,
                                    ),
                                    physics:
                                        const NeverScrollableScrollPhysics(),
                                    shrinkWrap: true,
                                    itemCount: filteredItems.length,
                                    padding: EdgeInsets.zero,
                                    itemBuilder: (context, index) {
                                      return ImageViewWidget(
                                        url: filteredItems[index].user?.image ??
                                            "",
                                        displayName:
                                            filteredItems[index].user?.name ??
                                                '',
                                        height: 55.h,
                                        width: 55.w,
                                        radius: 60.h,
                                      );
                                    })
                                : Column(
                                    children: [
                                      Padding(
                                        padding: context.paddingAll(16),
                                        child: Text(
                                          StringManager.noSpecialFriends.tr(),
                                          style: context.bodyMedium.bold,
                                        ),
                                      ),
                                      InkWell(
                                        onTap: () {
                                          Navigator.pushNamed(
                                              context, Routes.cpStorePage);
                                        },
                                        child: Container(
                                          decoration: BoxDecoration(
                                            borderRadius: 20.radius,
                                            color: ColorManager.textPrimary,
                                          ),
                                          child: Padding(
                                            padding: context.paddingAll(10),
                                            child: Text(
                                                StringManager.invite.tr(),
                                                style: context.bodySmall.bold),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ));
                      },
                    ),
                  ],
                ),
              ),
            ),
          ),
          Image.asset(
            AssetsManager.sadBear,
            fit: BoxFit.fill,
            width: double.infinity,
          ),
        ],
      ),
    );
  }
}
