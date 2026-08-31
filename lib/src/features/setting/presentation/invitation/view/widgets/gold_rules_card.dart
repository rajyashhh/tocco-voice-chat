import 'package:general/src/features/setting/presentation/invitation/bloc/invite_bloc.dart';

import '../../../../../../core/index.dart';

/// "قواعد الدعوة" card: renders the admin-authored rules text (explain-invitation).
class GoldRulesCard extends StatelessWidget {
  const GoldRulesCard({super.key});

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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.menu_book_rounded,
                  color: ColorManager.gold3, size: 20.h),
              8.wBox,
              TextWidget(
                StringManager.invitationRules.tr(),
                style: context.bodyMedium
                    .size(16)
                    .bold
                    .colorExt(ColorManager.defaultTextVip),
              ),
            ],
          ),
          12.hBox,
          BlocBuilder<SendInviteBloc, SendInviteState>(
            bloc: di<SendInviteBloc>(),
            buildWhen: (prev, curr) =>
                prev.explainInviteRequest != curr.explainInviteRequest ||
                prev.explainInviteSuccess != curr.explainInviteSuccess,
            builder: (context, state) {
              return HandlingDataWidget(
                reqState: state.explainInviteRequest,
                title: '',
                subTitle: '',
                child: TextWidget(
                  state.explainInviteSuccess.toString(),
                  isTranslate: false,
                  textAlign: TextAlign.start,
                  overflow: TextOverflow.fade,
                  style: context.bodyMedium
                      .size(14)
                      .w400
                      .colorExt(
                          ColorManager.defaultTextVip.withValues(alpha: 0.9)),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}
