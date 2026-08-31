import 'package:general/src/core/index.dart';

class RowItemWidget extends StatelessWidget {
  const RowItemWidget({super.key, this.widget, this.title});

  final Widget? widget;
  final String? title;
  @override
  Widget build(BuildContext context) {
    return Container(
      height: 65.h,
      padding: context.paddingSymmetric(horizontal: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: 10.radius,
      ),
      child: Row(
        children: [
          TextWidget(
            title ?? '',
            style: context.bodyLarge.colorExt(ColorManager.roomTextPrimary),
          ),
          const Spacer(),
          widget ?? const SizedBox(),
          10.wBox,
          const Icon(
            Icons.arrow_forward_ios_outlined,
            color: ColorManager.grey,
          )
        ],
      ),
    );
  }
}
