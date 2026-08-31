import 'package:general/src/core/index.dart';

class IndicatorRowWidget extends StatelessWidget {
  const IndicatorRowWidget({
    super.key,
    this.onTap,
    required this.title,
  });

  final VoidCallback? onTap;
  final String title;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Row(
        children: [
          TextWidget(title,
              style: context.bodyMedium
                  .size(13)
                  .w600
                  .colorExt(ColorManager.textPrimary)),
          const Spacer(),
          if (onTap != null) ...[
            ForwardChevron(
              size: 15.w,
              color: ColorManager.textPrimary,
            ),
          ],
        ],
      ),
    );
  }
}
