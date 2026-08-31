import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/auth.dart';

class CountryWidget extends StatelessWidget {
  final CountryEntity? countryEntity;
  final bool isShowFlag;
  final bool isSelected;
  final VoidCallback onTap;

  const CountryWidget({
    super.key,
    this.countryEntity,
    required this.onTap,
    this.isShowFlag = true,
    required this.isSelected,
  });

  @override
  Widget build(BuildContext context) {
    final name = Methods.getLang() == 'ar'
        ? (countryEntity?.name ?? countryEntity?.nameEn ?? '')
        : (countryEntity?.nameEn ?? countryEntity?.name ?? '');

    return Container(
      margin: context.paddingSymmetric(horizontal: 5),
      decoration: BoxDecoration(
        borderRadius: 4.radius,
        color:
            isSelected ? ColorManager.primary : ColorManager.surfaceCardColor,
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: 30.radius,
        child: Padding(
          padding: context.paddingSymmetric(horizontal: 9.0, vertical: 5),
          child: countryEntity == null
              ? TextWidget(
                  StringManager.all.tr(),
                  style: context.bodyMedium.colorExt(
                    isSelected
                        ? ColorManager.buttonTextColor
                        : ColorManager.textPrimary,
                  ),
                )
              : Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    CountryFlagWidget(
                      iso: countryEntity?.iso,
                      fallbackUrl: countryEntity?.photo,
                      height: 15.h,
                      width: 22.h,
                      boxFit: BoxFit.cover,
                    ),
                    if (name.isNotEmpty) ...[
                      5.wBox,
                      TextWidget(
                        name,
                        isTranslate: false,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: context.bodySmall.size(11).colorExt(
                              isSelected
                                  ? ColorManager.whiteColor
                                  : ColorManager.textPrimary,
                            ),
                      ),
                    ],
                  ],
                ),
        ),
      ),
    );
  }
}