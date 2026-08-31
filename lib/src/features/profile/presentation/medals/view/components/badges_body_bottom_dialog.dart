part of '../medals_page.dart';

class _BadgesBodybottomDialog extends StatelessWidget {

  final List<int> pickedRoomIds;
  const _BadgesBodybottomDialog({required this.index,required this.pickedRoomIds});

  final int index;
  @override
  Widget build(BuildContext context) {
    return Container(
      width: ScreenUtil().screenWidth,

      margin: context.paddingSymmetric(horizontal: 5),
      decoration:   BoxDecoration(
        color: ColorManager.primary,
        borderRadius: 15.radius,
      ),
      child: BlocBuilder<UserBadgesBloc, UserBadgesState>(
        bloc: di<UserBadgesBloc>(),
        buildWhen: (prev, curr) => prev.myBadge != curr.myBadge,
        builder: (context, state) {
          return Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              10.wBox,
              index == 0
                  ? _BadgeContainerBody(
                badgeImages: state.myBadge!
                    .where((badge) => badge.type != 2)
                    .map((badge) => badge.image)
                    .toList(),
                badgeIds: state.myBadge!
                    .where((badge) => badge.type != 2)
                    .map((badge) => badge.id.toString())
                    .toList(),
              )
                  : _BadgeContainerBody(
                badgeImages: state.myBadge!
                    .where((badge) => badge.type == 2)
                    .map((badge) => badge.image)
                    .toList(),
                badgeIds: state.myBadge!
                    .where((badge) => badge.type == 2)
                    .map((badge) => badge.id.toString())
                    .toList(),
              ),
              const Spacer(),
              ButtonWidget(
                width: 70.w,
                elevation: 0.0,
                height: 30.w,
                fontSize: 14,
                fontWeight: FontWeight.w500,
                onPressed: () {
                  final selectedIds = di<SelectionBloc>().state.selectedIds;

                  if (selectedIds.length < 4) {
                    Navigator.pop(context);
                    di<PickMyBadgesBloc>().add(
                      PickMyBadgesEvent(ids: selectedIds+pickedRoomIds),
                    );
                  }else{
                    Methods.showToast(context,message: StringManager.pleaseSelect3Items.tr(),isError: true);
                  }
                },
                paddingButton: context.paddingZero(),
                isFittedBox: false,
                title: StringManager.save.tr(),
                backgroundColor: ColorManager.scaffoldBg,
                titleColor: ColorManager.primary,
              ),
              10.wBox,
            ],
          );
        },
      ),
    );
  }
}
