
import 'package:general/src/core/index.dart';
import 'package:general/src/features/cp/presentation/cp/view/components/create_relation_widget.dart';
import 'package:general/src/features/cp/presentation/cp/view/components/relation_level_widget.dart';
import 'package:general/src/features/cp/presentation/cp/view/components/relation_recovery_widget.dart';

class RelationRulesView extends StatelessWidget {


  const RelationRulesView({super.key});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: context.paddingSymmetric(vertical: 10),
      child:  const Column(
        children: [
        
        CreateRelationWidget(),
          SizedBox(
            height: 20,
          ),
          RelationLevelWidget(),
          SizedBox(
            height: 20,
          ),
          RelationRecoveryWidget(),

      
        ],
      ),
    );
  }
}

