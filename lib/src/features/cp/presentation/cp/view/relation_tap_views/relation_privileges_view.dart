import 'package:general/src/core/index.dart';

class RelationPrivilegesView extends StatelessWidget {
  const RelationPrivilegesView({super.key});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: context.paddingSymmetric(vertical: 10),
      child: const Column(
        children: [
          CreatePrivilegesWidget(),
        ],
      ),
    );
  }
}

class CreatePrivilegesWidget extends StatelessWidget {
  const CreatePrivilegesWidget({super.key});

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
                AssetsManager.vipBackground6,
                fit: BoxFit.fill,
                width: double.infinity,
              ),
              Align(
                alignment: Alignment.topCenter,
                child: Padding(
                  padding: context.paddingOnly(top: 13),
                  child: Text(
                    StringManager.relationPrivileges.tr(),
                    style: context.bodyLarge.bold
                  ),
                ),
              ),
            ],
          ),
          Container(
            decoration: const BoxDecoration(
              color: Color(0xffF5E2FF),
              border: Border(
                left: BorderSide(width: 3, color: ColorManager.gold),
                right: BorderSide(width: 3, color: ColorManager.gold),
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
                        AssetsManager.cpBackGround,
                      ),
                       LevelWidget(
                        level: 1,
                        title:StringManager.relationPrivilegesL1.tr() ,
                        imagePath: AssetsManager.cpBackGround,
                      ),
                       LevelWidget(
                        level: 2,
                        title: StringManager.relationPrivilegesL2.tr() ,
                         imagePath: AssetsManager.cpBackGround,
                      ),
                       LevelWidget(
                        level: 3,
                        title: StringManager.relationPrivilegesL3.tr() ,
                         imagePath: AssetsManager.cpBackGround,
                      ),
                       LevelWidget(
                        level: 4,
                        title: StringManager.relationPrivilegesL4.tr() ,
                         imagePath: AssetsManager.cpBackGround,
                      ),
                       LevelWidget(
                        level: 6,
                        title: StringManager.relationPrivilegesL6.tr() ,
                         imagePath: AssetsManager.cpBackGround,
                      ),
                       LevelWidget(
                        level: 7,
                        title: StringManager.relationPrivilegesL7.tr() ,
                         imagePath: AssetsManager.cpBackGround,
                      ),
                       LevelWidget(
                        level: 8,
                        title: StringManager.relationPrivilegesL8.tr() ,
                         imagePath: AssetsManager.cpBackGround,
                      ),
                    ],
                  ),
                )),
          ),
          Image.asset(
            AssetsManager.cpBackGround,
            fit: BoxFit.fill,
            width: double.infinity,
          ),
        ],
      ),
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
              height: 55.w,
              color: level > 1
                  ? ColorManager.darkBlackChat
                  : ColorManager.transparent,
            ),
            Container(
              width: 50.w,
              height: 50.h,
              decoration: BoxDecoration(
                  //color: Colors.purple,
                  shape: BoxShape.circle,
                  border: Border.all(color: ColorManager.divider),
                  gradient: const LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: ColorManager.cpFriends,
                  )),
              child: Center(
                child: Text(
                  "LVL $level",
                  style: context.bodyMedium.w900,
                ),
              ),
            ),
            Container(
              width: 3.w,
              height: 55.h,
              color: level < 8
                  ? ColorManager.formFieldColor
                  : ColorManager.transparent,
            ),
          ],
        ),
        10.wBox,
        Expanded(
          child: Container(
            decoration: BoxDecoration(
              borderRadius: 12.radius,
              color: ColorManager.fieldColorChat,
            ),
            child: Padding(
              padding: context.paddingSymmetric(vertical: 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Text(
                    title,
                    style: context.bodyMedium.bold
                  ),
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
