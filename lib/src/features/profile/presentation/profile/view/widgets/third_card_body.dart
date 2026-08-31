import 'package:general/src/core/index.dart';
import 'package:general/src/features/profile/presentation/profile/view/profile_screen.dart';

import '../../../../../home/home.dart';
import '../../../../../home/presentation/daily_prize/view/daily_prize_dialog.dart';

class ThirdCardBody extends StatelessWidget {
  const ThirdCardBody({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
      bloc: di<FetchUserDataBloc>(),
      buildWhen: (prev, curr) => prev.userEntity != curr.userEntity,
      builder: (context, state) {
        return ProfileCard(
          child: Column(
            children: [
              ListTileBody(
                image: AssetsManager.gift,
                title: StringManager.checkIn.tr(),
                onTap: () {
                  {
                    if (di<DailyPrizesBloc>()
                        .state
                        .requestStateGetPrize
                        .isLoaded) {
                      showDialog(
                        context: context,
                        builder: (_) => const Dialog(
                          backgroundColor: ColorManager.transparent,
                          insetPadding: EdgeInsets.symmetric(
                              horizontal: 20.0, vertical: 0),
                          child: DailyPrizeDialog(
                            isNeedCompleteInfoDialog: false,
                          ),
                        ),
                      );
                    } else {
                      di<DailyPrizesBloc>()
                          .add(GetDailyPrizesEvent(context: context));
                      Methods.showToast(context,
                          message: StringManager.noGift.tr());
                    }
                  }
                },
              ),
              3.hBox,
              BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
                bloc: di<FetchUserDataBloc>(),
                buildWhen: (prev, curr) => prev.userEntity?.showInvitationCode != curr.userEntity?.showInvitationCode,
                builder: (context, state) {
                  if (state.userEntity?.showInvitationCode != null &&
                      state.userEntity?.showInvitationCode == true) {
                    return Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        ListTileBody(
                            image: AssetsManager.invitation,
                            title: StringManager.invitation.tr(),
                            onTap: () {
                              Navigator.pushNamed(context, Routes.inviteBonus);
                            }),
                        3.hBox,
                      ],
                    );
                  } else {
                    return const SizedBox.shrink();
                  }
                },
              ),
              ListTileBody(
                image: AssetsManager.levelUpIcon,
                title: StringManager.myLevel.tr(),
                onTap: () {
                  Navigator.pushNamed(context, Routes.levelScreen);
                },
              ),
              ListTileBody(
                image: AssetsManager.badgeIcon,
                title: StringManager.badge.tr(),
                onTap: () {
                  Navigator.pushNamed(context, Routes.medalsScreen);
                },
              ),
              if ((ConstantsManager.isHostAgencyVisible)) ...[
                3.hBox,
                ListTileBody(
                  title: StringManager.agencyCenter.tr(),
                  image: AssetsManager.agencyIcon,
                  onTap: () {
                    if ((StringManager.userType[2]! ||
                        StringManager.userType[1]!)) {
                      Navigator.pushNamed(context, Routes.newAgencyScreen);
                    } else {
                      Navigator.pushNamed(
                          context, Routes.searchForAgencyScreen);
                    }
                  },
                ),
              ],
              3.hBox,

              (StringManager.userType[3]! || StringManager.userType[6]!)
                  ? ListTileBody(
                      image: AssetsManager.coin,
                      isCoin: true,
                      title: StringManager.chargeAgency.tr(),
                      onTap: () {
                        Navigator.pushNamed(context, Routes.chargeAgencyScreen);
                      })
                  : const SizedBox(),

           
              ListTileBody(
                  image: AssetsManager.family,
                  title: StringManager.family.tr(),
                  onTap: () {
                    Navigator.pushNamed(context, Routes.familyRankPage);
                  }),
              3.hBox,

             
              ListTileBody(
                image: AssetsManager.chatWithUser,
                title: StringManager.feedBack.tr(),
                onTap: () =>
                    Navigator.pushNamed(context, Routes.problemReportsScreen),
              ),
              // 3.hBox,
              // ListTileBody(
              //   image: AssetsManager.vipPrivilege,
              //   title: StringManager.vipPrivilege.tr(),
              //   onTap: () {
              //     navKey.currentContext!.pushNamedRoute(Routes.privacyScreen);
              //   },
              // ),
              3.hBox,

              ListTileBody(
                image: AssetsManager.settings,
                title: StringManager.settings.tr(),
                onTap: () =>
                    Navigator.pushNamed(context, Routes.settingsScreen),
              ),
              5.hBox,
            ],
          ),
        );
      },
    );
  }
}
