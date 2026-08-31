import 'package:general/src/core/index.dart';
import 'package:general/src/features/cp/presentation/cp/view/components/tap_bar_body.dart';
import 'package:general/src/features/cp/presentation/cp/view/cp_my_relations.dart';
import 'package:general/src/features/cp/presentation/cp/view/relation_tap_views/relation_special_friends_view.dart';

import 'relation_tap_views/relation_privileges_view.dart';
import 'relation_tap_views/relation_rules_view.dart';

class CpPage extends StatefulWidget {
  const CpPage({super.key});

  @override
  State<CpPage> createState() => _CpPageState();
}

class _CpPageState extends State<CpPage> with TickerProviderStateMixin {
  TabController? tabController;

  @override
  void initState() {
    tabController = TabController(length: 3, vsync: this);
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        top: true,
        bottom: false,
        child: Stack(
        children: [
          SingleChildScrollView(
            child: Column(children: [
              Stack(children: [
                Image.asset(
                  AssetsManager.cpBackground,
                ),
                Positioned(
                  bottom: 40,
                  left: 50,
                  right: 50,
                  child: Image.asset(
                    AssetsManager.report,
                  ),
                ),
              ]),
              Column(
                children: [
                  TapBarBody(
                    controller: tabController!,
                  ),
                  SizedBox(
                    height:ScreenUtil().screenHeight * 2.8,
                    width: ScreenUtil().screenWidth,
                    child: TabBarView(
                      controller: tabController,
                      children: const [
                        RelationRulesView(),
                        RelationPrivilegesView(),
                        RelationSpecialFriendsView(),
                      ],
                    ),
                  ),
                ],
              )
            ]),
          ),

          /// buttton to navigate to my relations
          Align(
            alignment: Alignment.bottomCenter,
            child: InkWell(
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => const CpMyRelations(),
                    ),
                  );
                },
                child: Container(
                  height: 100,
                  width: ScreenUtil().screenWidth * 0.8,
                  decoration:  BoxDecoration(
                      image: DecorationImage(
                          image: AssetImage(AssetsManager.diamondIcon),
                          fit: BoxFit.fill)),
                  child: Center(
                    child: Text(
                      StringManager.myRelations.tr(),
                      textAlign: TextAlign.center,
                      style: context.bodyLarge.w900,
                    ),
                  ),
                )),
          ),
        ],
      ),
      ),
    );
  }
}
