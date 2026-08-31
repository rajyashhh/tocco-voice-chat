import 'package:general/src/core/index.dart';

/// Theme3 (NEXO) horizontally-scrollable filter chip row driving the parent
/// [TabController] — active chip is a pink filled pill, inactive chips are
/// translucent white. Purely presentational; the controller/index mapping and
/// the tabs it drives are identical to [Theme2HeaderBar]'s TabBar.
class Theme3FilterChips extends StatelessWidget {
  final TabController controller;
  final List<String> labels;

  const Theme3FilterChips({
    super.key,
    required this.controller,
    required this.labels,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 40.h,
      child: AnimatedBuilder(
        animation: controller,
        builder: (context, _) {
          return ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: EdgeInsetsDirectional.only(start: 16.w, end: 16.w),
            itemCount: labels.length,
            separatorBuilder: (_, __) => 8.wBox,
            itemBuilder: (context, index) {
              final isActive = controller.index == index;
              return GestureDetector(
                onTap: () => controller.animateTo(index),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  padding:
                      EdgeInsets.symmetric(horizontal: 18.w, vertical: 8.h),
                  decoration: BoxDecoration(
                    gradient: isActive
                        ? const LinearGradient(
                            colors: ColorManager.theme3CtaGradient,
                          )
                        : null,
                    color: isActive ? null : ColorManager.theme3ChipInactive,
                    borderRadius: BorderRadius.circular(20.r),
                  ),
                  alignment: Alignment.center,
                  child: Text(
                    labels[index],
                    style: TextStyle(
                      color: isActive
                          ? ColorManager.white
                          : ColorManager.theme3TextPrimary,
                      fontSize: 14.sp,
                      fontWeight: isActive ? FontWeight.w600 : FontWeight.w400,
                    ),
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}