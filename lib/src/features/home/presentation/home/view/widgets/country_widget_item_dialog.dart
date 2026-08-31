import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/auth.dart';

class CountryWidgetItemDialog extends StatelessWidget {
  final CountryEntity? countryEntity;
  final bool isShowFlag;
  final bool isSelected;
  final VoidCallback onTap;

  /// When set, the item renders this plain label instead of a country row
  /// (e.g. the leading "Recommended" entry that clears the country filter).
  final String? label;

  const CountryWidgetItemDialog({
    super.key,
    this.countryEntity,
    required this.onTap,
    this.isShowFlag = true,
    required this.isSelected,
    this.label,
  });

  @override
  Widget build(BuildContext context) {
    // Render directly over the dialog's body gradient: no per-item white card.
    return Material(
      type: MaterialType.transparency,
      child: InkWell(
        onTap: onTap,
        borderRadius: 5.radius,
        child: Padding(
          padding: context.paddingSymmetric(horizontal: 10, vertical: 3),
          child: (label != null || countryEntity == null)
              ? Align(
                  alignment: AlignmentDirectional.centerStart,
                  child: TextWidget(
                    label ?? StringManager.all.tr(),
                    style: context.bodyMedium.colorExt(
                      isSelected
                          ? ColorManager.primary
                          : ColorManager.textPrimary,
                    ),
                  ),
                )
              : Row(
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          SizedBox(
                            width: 80.w,
                            child: TextWidget(
                              (Methods.getLang() == 'ar'
                                      ? countryEntity?.name
                                      : countryEntity?.nameEn) ??
                                  countryEntity?.name ??
                                  '',
                              isTranslate: false,
                              overflow: TextOverflow.ellipsis,
                              style: context.bodySmall
                                  .colorExt(
                                    ColorManager.secondaryText,
                                  )
                                  .size(12)
                                  .bold,
                            ),
                          ),
                          4.hBox,
                          Row(
                            children: List.generate(
                              (countryEntity?.supporters?.length ?? 0)
                                  .clamp(0, 3),
                              (index) {
                                final supporter =
                                    countryEntity!.supporters![index];
                                return GestureDetector(
                                  onTap: () {
                                    Methods().userProfileNavigator(
                                      context: context,
                                      userId:
                                          '${countryEntity!.supporters![index].id}',
                                    );
                                  },
                                  child: Padding(
                                    padding: EdgeInsets.only(right: 6.w),
                                    child: ImageViewWidget(
                                      height: 24.h,
                                      width: 24.h,
                                      url: supporter.avatar ?? '',
                                      displayName: supporter.name ?? '',
                                      boxFit: BoxFit.cover,
                                      border: Border.all(
                                        color: ColorManager.textPrimary,
                                      ),
                                      radius: 20,
                                    ),
                                  ),
                                );
                              },
                            ),
                          ),
                        ],
                      ),
                      const Spacer(),
                      CountryFlagWidget(
                        iso: countryEntity?.iso,
                        fallbackUrl: countryEntity?.photo,
                        height: 15.h,
                        width: 27.5.h,
                        boxFit: BoxFit.cover,
                      ),
                    ],
                  ),
        ),
      ),
    );
  }
}
