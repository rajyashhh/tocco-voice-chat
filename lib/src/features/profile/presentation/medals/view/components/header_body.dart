part of '../medals_page.dart';

class _HeaderBody extends StatelessWidget {
  const _HeaderBody({required this.index});

  final int index;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: ColorManager.scaffoldBgSpecial,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AppBarWidget(
            backgroundColor: ColorManager.transparent,
            title: index == 1
                ? StringManager.myRoomBadge.tr()
                : StringManager.myBadge.tr(),
            titleStyle:
                context.bodyLarge.bold.colorExt(ColorManager.textPrimary),
            iconColor: ColorManager.white,
          ),
          _BadgesBody(
            index: index,
          ),
          15.hBox,
        ],
      ),
    );
  }
}
