import '../../../../core/index.dart';

PopupMenuItem popUpItemWidget({
  required String title,
  bool showNotificationNum = false,
  IconData? icon, // Make the icon nullable
  VoidCallback? onTap,
  BuildContext? context,
  String? numOfRequests,
}) {
  return PopupMenuItem(
    onTap: onTap,
    child: Row(
      children: [
        if (icon != null)
          Stack(
            clipBehavior: Clip.none,
            children: [
              Icon(
                icon,
                size: 20.w,
                color: ColorManager.white,
              ),
              if (showNotificationNum && numOfRequests != 0.toString())
                Positioned(
                  top: -8,
                  right: -3,
                  child: Container(
                    margin: context?.paddingOnly(bottom: 8, start: 7),
                    padding: context?.paddingAll(3),
                    decoration: const BoxDecoration(
                      color: ColorManager.redAccount,
                      shape: BoxShape.circle,
                    ),
                    child: Center(
                      child: TextWidget(
                        numOfRequests ?? "",
                        style: context?.bodySmall
                            .colorExt(ColorManager.textPrimary)
                            .size(9),
                      ),
                    ),
                  ),
                )
            ],
          ),
        if (icon != null) 5.wBox, // Add spacing only if the icon exists
        Text(
          title,
          style: context!.bodyMedium.colorExt(ColorManager.textPrimary),
        ),
      ],
    ),
  );
}
