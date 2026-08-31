import 'package:general/src/features/setting/presentation/invitation/bloc/invite_bloc.dart';

import '../../../../../../core/index.dart';

/// "دعوتي" card: invitation totals plus a shortcut to the full invitees list.
class GoldInvitationsCard extends StatelessWidget {
  const GoldInvitationsCard({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.symmetric(horizontal: 18.w, vertical: 18.h),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: ColorManager.inviteGoldCardGradient,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20.r),
        border: Border.all(color: ColorManager.gold3.withValues(alpha: 0.6)),
      ),
      child: BlocBuilder<SendInviteBloc, SendInviteState>(
        bloc: di<SendInviteBloc>(),
        buildWhen: (prev, curr) =>
            prev.getMyEarnInviteRequest != curr.getMyEarnInviteRequest ||
            prev.getMyEarnInviteSuccess != curr.getMyEarnInviteSuccess,
        builder: (context, state) {
          final data = state.getMyEarnInviteSuccess;
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextWidget(
                StringManager.myInvitations.tr(),
                style: context.bodyMedium
                    .size(16)
                    .bold
                    .colorExt(ColorManager.defaultTextVip),
              ),
              16.hBox,
              Row(
                children: [
                  _Stat(
                    label: StringManager.totalInvited.tr(),
                    value: data?.totalInvited ?? 0,
                  ),
                  _Stat(
                    label: StringManager.totalEarned.tr(),
                    value: data?.totalEarned ?? 0,
                  ),
                ],
              ),
              12.hBox,
              Row(
                children: [
                  _Stat(
                    label: StringManager.dayInvited.tr(),
                    value: data?.dayInvited ?? 0,
                  ),
                  _Stat(
                    label: StringManager.dayEarned.tr(),
                    value: data?.dayEarned ?? 0,
                  ),
                ],
              ),
              16.hBox,
              ButtonWidget(
                height: 48.h,
                radius: 30,
                backgroundColors: ColorManager.vipBuyButtonGradient,
                titleColor: ColorManager.darkGreen,
                title: StringManager.watch.tr(),
                onPressed: () =>
                    Navigator.pushNamed(context, Routes.inviteUser),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.label, required this.value});

  final String label;
  final int value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          TextWidget(
            value.toString(),
            isTranslate: false,
            style: context.bodyMedium
                .size(22)
                .bold
                .colorExt(ColorManager.defaultTextVip),
          ),
          4.hBox,
          TextWidget(
            label,
            textAlign: TextAlign.center,
            style: context.bodyMedium
                .size(12)
                .colorExt(ColorManager.defaultTextVip.withValues(alpha: 0.75)),
          ),
        ],
      ),
    );
  }
}
