part of 'package:general/src/features/profile/presentation/profile/view/profile_screen.dart';

class _ProfileBody extends StatelessWidget {
  const _ProfileBody({required this.data});

  final MyDataEntity data;

  @override
  Widget build(BuildContext context) {
    final user = MyDataModel.getInstance().convertMyDataEntityToUserEntity(data);
    return Padding(
      padding: context.paddingSymmetric(horizontal: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          12.hBox,
          // ── Hero card: avatar + identity + stats on one elevated surface ──
          // Uses the per-theme [surfaceCardColor] so text (theme [textPrimary])
          // stays readable on every variant — light ink on the light themes,
          // white on the dark default — while a subtle primary glow gives the
          // dark identity its richness.
          Container(
            width: double.infinity,
            clipBehavior: Clip.antiAlias,
            decoration: ColorManager.cardDecoration(
              borderRadius: 20.radius,
              border: Border.all(color: ColorManager.cardBorderColor),
            ),
            child: Column(
              children: [
                // faint accent band behind the avatar row for depth
                Container(
                  padding: context.paddingOnly(
                    start: 14,
                    end: 14,
                    top: 16,
                    bottom: 14,
                  ),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: [
                        ColorManager.primary.withValues(alpha: 0.14),
                        ColorManager.transparent,
                      ],
                    ),
                  ),
                  child: UserInfoRow(user_: user),
                ),
                Divider(
                  height: 1,
                  thickness: 1,
                  color: ColorManager.cardBorderColor,
                ),
                Padding(
                  padding: context.paddingSymmetric(horizontal: 8, vertical: 14),
                  child: FFLFBody(
                    isMyProfile: true,
                    horizontalP: 0.w,
                    data: user,
                  ),
                ),
              ],
            ),
          ),
          16.hBox,
          const SecondCard(),
          14.hBox,
          const ThirdCardBody(),
          14.hBox,
        ],
      ),
    );
  }
}
