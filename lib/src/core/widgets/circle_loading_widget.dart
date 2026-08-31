import 'package:general/src/core/index.dart';

class CircleLoadingWidget extends StatelessWidget {
  const CircleLoadingWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 16.h),
      child: Center(
        child: SizedBox(
          height: 25.h,
          width: 25.h,
          child: CircularProgressIndicator.adaptive(
            strokeWidth: 2.0,
            valueColor: AlwaysStoppedAnimation<Color>(ColorManager.primary),
          ),
        ),
      ),
    );
  }
}
