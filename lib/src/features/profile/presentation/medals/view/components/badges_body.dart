part of '../medals_page.dart';

class _BadgesBody extends StatelessWidget {
  const _BadgesBody({required this.index});

  final int index;
  @override
  Widget build(BuildContext context) {
    return BlocBuilder<UserBadgesBloc, UserBadgesState>(
      buildWhen: (prev, curr) => prev.myBadge != curr.myBadge,
      builder: (context, state) {
        return Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            10.wBox,
            index == 0
                ? _BadgeContainerBody(
                    badgeImages: state.myBadge!
                        .where((badge) => badge.type != '2')
                        .map((badge) => badge.image)
                        .toList(),
                    badgeIds: state.myBadge!
                        .where((badge) => badge.type != '2')
                        .map((badge) => badge.id.toString())
                        .toList(),
                  )
                : _BadgeContainerBody(
                    badgeImages: state.myBadge!
                        .where((badge) => badge.type == '2')
                        .map((badge) => badge.image)
                        .toList(),
                    badgeIds: state.myBadge!
                        .where((badge) => badge.type == '2')
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
                if (index == 0) {
                  bottomDailog(
                    context: context,
                    widget: BottomDialogPickPersonal(
                      pickedRoomIds: state.myBadge
                              ?.where((badge) => badge.type == 2)
                              .map((badge) => badge.id)
                              .toList() ??
                          [],
                      pickedAchievements: state.myBadge
                              ?.where((badge) => badge.type != 2)
                              .map((badge) => badge.id.toString())
                              .toList() ??
                          [],
                      /* isPicked: true, */
                    ),
                  );
                } else {
                  bottomDailog(
                    context: context,
                    widget: BottomDialogPickRoom(
                      pickedPersonalIds: state.myBadge
                              ?.where((badge) => badge.type != 2)
                              .map((badge) => badge.id)
                              .toList() ??
                          [],
                      pickedAchievements: state.myBadge
                              ?.where((badge) => badge.type == 2)
                              .map((badge) => badge.id.toString())
                              .toList() ??
                          [],
                      /* isPicked: true, */
                    ),
                  );
                }
              },
              paddingButton: context.paddingZero(),
              isFittedBox: false,
              title: StringManager.wear.tr(),
              backgroundColor: ColorManager.scaffoldBg,
              titleColor: ColorManager.primary,
            ),
            10.wBox,
          ],
        );
      },
    );
  }
}
