part of '../setting_page.dart';

class RowBuilderWidget extends StatelessWidget {
  final String title;
  final Widget lastWidget;
  final Function() onTap;
  const RowBuilderWidget(
      {super.key,
      required this.title,
      required this.onTap,
      required this.lastWidget});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Container(
        width: ScreenUtil().screenWidth,
        padding: context.paddingSymmetric(horizontal: 10.w, vertical: 10.h),
        decoration: BoxDecoration(
          color: ColorManager.scaffoldBg,
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            TextWidget(
              title,
              style: context.bodyLarge.colorExt(ColorManager.roomTextPrimary),
            ),
            lastWidget,
          ],
        ),
      ),
    );
  }
}
