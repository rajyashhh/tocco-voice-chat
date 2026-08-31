part of '../account_setting_page.dart';

class SecurityItem extends StatelessWidget {
  const SecurityItem({
    super.key,
    this.image,
    required this.title,
    required this.bind,
    required this.onTap,
  });

  final String? image;
  final String title;
  final String bind;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onDoubleTap: () {},
      onTap: onTap,
      borderRadius: 12.radius,
      child: Container(
        color: ColorManager.scaffoldBg,
        margin: context.paddingOnly(bottom: 0.5),
        padding: context.paddingSymmetric(horizontal: 14, vertical: 15),
        child: Row(
          children: [
            TextWidget(
              title,
              style: context.bodyMedium
                  .size(16)
                  .colorExt(ColorManager.textPrimary),
            ),
            const Spacer(),
            title == StringManager.changePassword.tr() ||
                    title == StringManager.deleteAccount.tr() ||
                    title == StringManager.changePhoneNumber.tr()
                ? ForwardChevron(
                    color: ColorManager.noName,
                    size: 17.r,
                  )
                : bind.isEmpty
                    ? 1.hBox
                    : TextWidget(
                        style: context.bodyMedium
                            .size(16)
                            .colorExt(bind == StringManager.bind.tr()
                                ? ColorManager.secondaryText
                                : ColorManager.primary)
                            .w600,
                        bind),
          ],
        ),
      ),
    );
  }
}
