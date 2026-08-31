part of '../edit_profile_screen.dart';

class _RowEditInfo extends StatelessWidget {
  const _RowEditInfo({
    required this.title,
    this.subtitle,
    this.trailing,
    this.onTap,
    this.isFlagIcon,
    this.hasTrailing,
    this.isNeedTranslation,
  });

  final VoidCallback? onTap;
  final String? subtitle, trailing, title;
  final bool? hasTrailing, isFlagIcon, isNeedTranslation;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: ColorManager.scaffoldBg,
          borderRadius: 10.radius,
        ),
        padding: context.paddingSymmetric(vertical: 20, horizontal: 10),
        child: Row(
          children: [
            Expanded(
              child: TextWidget(
                subtitle!,
                style: context.bodyMedium
                    .size(16)
                    .colorExt(ColorManager.textPrimary),
              ),
            ),
            isFlagIcon ?? false
                // title carries the ISO code (preferred) or a legacy flag URL.
                ? CountryFlagWidget(
                    iso: (title ?? '').length <= 3 ? title : null,
                    fallbackUrl: (title ?? '').length > 3 ? title : null,
                    height: 30,
                    width: 30,
                  )
                : isNeedTranslation == true
                    ? ConstrainedBox(
                        constraints: BoxConstraints(maxWidth: 220.w),
                        child: TextWidget(
                          title!,
                          style: context.bodyMedium
                              .copyWith(overflow: TextOverflow.ellipsis)
                              .size(16)
                              .colorExt(ColorManager.textPrimary),
                        ),
                      )
                    : ConstrainedBox(
                        constraints: BoxConstraints(maxWidth: 220.w),
                        child: Text(
                          title!,
                          style: context.bodyMedium
                              .copyWith(overflow: TextOverflow.ellipsis)
                              .size(16)
                              .colorExt(ColorManager.textPrimary),
                        ),
                      ),
            10.wBox,
            hasTrailing == true
                ? Icon(
                    Icons.arrow_forward_ios,
                    color: ColorManager.secondaryText,
                    size: 16.h,
                  )
                : 15.wBox,
          ],
        ),
      ),
    );
  }
}
