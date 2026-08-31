import 'package:general/src/features/setting/presentation/invitation/bloc/invite_bloc.dart';

import '../../../../../../core/index.dart';

/// "دخل دعوتي" card: shows the accumulated commission balance, a one-time
/// claimable welcome bonus (when present), and the manual "استخراج العملات"
/// action gated by the withdrawal limit.
class GoldIncomeCard extends StatelessWidget {
  const GoldIncomeCard({super.key});

  @override
  Widget build(BuildContext context) {
    return _GoldCard(
      title: StringManager.myInviteIncome.tr(),
      child: BlocBuilder<SendInviteBloc, SendInviteState>(
        bloc: di<SendInviteBloc>(),
        buildWhen: (prev, curr) =>
            prev.getMyEarnInviteRequest != curr.getMyEarnInviteRequest ||
            prev.getMyEarnInviteSuccess != curr.getMyEarnInviteSuccess ||
            prev.extractCoinsRequest != curr.extractCoinsRequest ||
            prev.claimBonusRequest != curr.claimBonusRequest,
        builder: (context, state) {
          final data = state.getMyEarnInviteSuccess;
          final income = data?.claimableIncome ?? 0;
          final bonus = data?.claimableBonus ?? 0;
          final limit = data?.withdrawalLimit ?? 50000;
          final canExtract = income > 0;

          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextWidget(
                StringManager.claimableNow.tr(),
                textAlign: TextAlign.center,
                style: context.bodyMedium
                    .size(13)
                    .colorExt(
                        ColorManager.defaultTextVip.withValues(alpha: 0.75)),
              ),
              8.hBox,
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  CoinIcon(size: 30.h),
                  8.wBox,
                  TextWidget(
                    income.toString(),
                    isTranslate: false,
                    style: context.bodyMedium
                        .size(34)
                        .bold
                        .colorExt(ColorManager.defaultTextVip),
                  ),
                ],
              ),
              12.hBox,
              ButtonWidget(
                height: 50.h,
                radius: 30,
                isLoading: state.extractCoinsRequest.isLoading,
                cLoadingColor: ColorManager.darkGreen,
                backgroundColors:
                    canExtract ? ColorManager.cashOutGradient : null,
                backgroundColor:
                    canExtract ? null : ColorManager.gold.withValues(alpha: 0.4),
                titleColor: ColorManager.darkGreen,
                title: StringManager.extractCoins.tr(),
                onPressed: (canExtract && !state.extractCoinsRequest.isLoading)
                    ? () => di<SendInviteBloc>().add(ExtractInviteCoinsEvent())
                    : null,
              ),
              8.hBox,
              TextWidget(
                "${StringManager.extractUpTo.tr()} $limit ${StringManager.coinsPerRequest.tr()}",
                isTranslate: false,
                textAlign: TextAlign.center,
                style: context.bodyMedium
                    .size(11)
                    .colorExt(ColorManager.defaultTextVip.withValues(alpha: 0.7)),
              ),
              if (!canExtract) ...[
                6.hBox,
                TextWidget(
                  StringManager.noIncomeToExtract.tr(),
                  textAlign: TextAlign.center,
                  style: context.bodyMedium
                      .size(11)
                      .colorExt(
                          ColorManager.defaultTextVip.withValues(alpha: 0.6)),
                ),
              ],
              if (bonus > 0) ...[
                16.hBox,
                Divider(color: ColorManager.gold.withValues(alpha: 0.4)),
                12.hBox,
                _BonusRow(
                  bonus: bonus,
                  isLoading: state.claimBonusRequest.isLoading,
                ),
              ],
            ],
          );
        },
      ),
    );
  }
}

class _BonusRow extends StatelessWidget {
  const _BonusRow({required this.bonus, required this.isLoading});

  final int bonus;
  final bool isLoading;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        TextWidget(
          StringManager.oneTimeBonus.tr(),
          textAlign: TextAlign.center,
          style: context.bodyMedium
              .size(13)
              .colorExt(ColorManager.defaultTextVip),
        ),
        8.hBox,
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.card_giftcard, color: ColorManager.gold3, size: 24.h),
            6.wBox,
            TextWidget(
              bonus.toString(),
              isTranslate: false,
              style: context.bodyMedium
                  .size(24)
                  .bold
                  .colorExt(ColorManager.defaultTextVip),
            ),
          ],
        ),
        12.hBox,
        ButtonWidget(
          height: 46.h,
          radius: 30,
          isLoading: isLoading,
          cLoadingColor: ColorManager.darkGreen,
          backgroundColors: ColorManager.vipBuyButtonGradient,
          titleColor: ColorManager.darkGreen,
          title: StringManager.claimBonus.tr(),
          onPressed: isLoading
              ? null
              : () => di<SendInviteBloc>().add(ClaimInviteBonusEvent()),
        ),
      ],
    );
  }
}

class _GoldCard extends StatelessWidget {
  const _GoldCard({required this.title, required this.child});

  final String title;
  final Widget child;

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
          TextWidget(
            title,
            style: context.bodyMedium
                .size(16)
                .bold
                .colorExt(ColorManager.defaultTextVip),
          ),
          14.hBox,
          child,
        ],
      ),
    );
  }
}
