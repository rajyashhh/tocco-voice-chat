import 'package:general/src/core/index.dart';
import 'package:general/src/features/cp/cp.dart';
import 'package:general/src/features/cp/presentation/cp/view/MY_relation_tap_views/myrelation_cp_view.dart';
import 'package:general/src/features/cp/presentation/cp/view/MY_relation_tap_views/myrelation_special_friends_view.dart';
import 'package:general/src/features/cp/presentation/cp/view/components/myrelation_tap_bar_body.dart';

class CpMyRelations extends StatefulWidget {
  const CpMyRelations({super.key});

  @override
  State<CpMyRelations> createState() => _CpMyRelationsState();
}

class _CpMyRelationsState extends State<CpMyRelations> with TickerProviderStateMixin {
  TabController? tabController;

  @override
  void initState() {
    tabController = TabController(length: 2, vsync: this);
    BlocProvider.of<CpProfileBloc>(context).add(GetCpProfileEvents(userId: MyDataModel.getInstance().id.toString()));
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorManager.blueTabIndicator,
      body: SingleChildScrollView(
        child: Column(
          children: [
          Stack(
            children: [
            Image.asset(
              AssetsManager.cpBackground,
            ),
          ],
          ),
          Container(
            color: ColorManager.gold3,
            child: Column(
              children: [
                MyRelationTapBarBody(
                  controller: tabController!,
                ),
                SizedBox(
                  height:ScreenUtil().screenHeight * 2,
                  width: ScreenUtil().screenWidth,
                  child: TabBarView(
                    controller: tabController,
                    children: const [
                      MyRelationCpView(),
                      MyRelationSpecialFriendsView(),
                    ],
                  ),
                ),
              ],
            ),
          )
        ]),
      ),
    );
  }
}
