import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/register_dialog.dart';
import 'package:general/src/features/auth/auth.dart';
part 'components/form_auth_body.dart';

class LoginPage extends StatelessWidget {
  const LoginPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBarWidget(
        backgroundColor: ColorManager.scaffoldBgAlt,
      ),
      body: Padding(
        padding: context.paddingSymmetric(horizontal: 20),
        child: const _FormAuthBody(),
      ),
    );
  }
}
