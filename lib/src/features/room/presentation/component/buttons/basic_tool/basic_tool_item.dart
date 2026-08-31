part of 'basic_tool_dialog.dart';

class BasicToolItem extends StatelessWidget {
  const BasicToolItem({
    super.key,
    required this.image,
    required this.title,
    this.height,
    this.width,
    this.color,
    this.icon,
    this.titleColor,
  });
  final String image;
  final String title;
  final double? height;
  final double? width;
  final IconData? icon;
  final Color? color, titleColor;
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: context.paddingAll(2.r),
      margin: context.paddingOnly(top: 3.h),
      width: 90.w,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: context.paddingAll(9),
            decoration: BoxDecoration(
                color: ColorManager.roomIcon.withValues(alpha: 0.08),
                borderRadius: 30.radius),
            child: icon != null
                ? Icon(
                    icon,
                    size: 33.sp,
                    color: color ?? ColorManager.roomIcon,
                  )
                : Image.asset(
                    image,
                    width: width ?? 33.w,
                    height: height ?? 33.h,
                    color: color ?? ColorManager.roomIcon,
                  ),
          ),
          4.hBox,
          Text(
            title,
            style: context.bodySmall
                .colorExt(titleColor ?? ColorManager.roomTextPrimary)
                .copyWith(fontSize: 12.sp),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
