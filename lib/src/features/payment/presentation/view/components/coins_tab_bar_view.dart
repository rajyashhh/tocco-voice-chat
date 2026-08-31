import 'package:general/src/features/payment/presentation/view/components/dollars_view.dart';

import 'package:general/src/core/index.dart';
import 'coins_view.dart';
import 'diamond_view.dart';

class CoinsTabBarView extends StatelessWidget {
  final TabController controller;

  const CoinsTabBarView({
    required this.controller,
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return TabBarView(
      controller: controller,
      clipBehavior: Clip.none,
      physics: const NeverScrollableScrollPhysics(), // Disable swipe

      children: [
        const CoinsView(),
        if (!(StringManager.userType[1]! || StringManager.userType[2]!))
          const DiamondView(),
        // Profits tab: the working profit-transfer path (transfer to a user or
        // shipping agent via ChargeToBloc). The three "coming soon" placeholder
        // sub-tabs (self / agent withdrawal, internal sale) were removed.
        const DollarsView(),
      ],
    );
  }
}
