import 'package:general/src/features/setting/presentation/invitation/bloc/invite_bloc.dart';

import '../../../../../../core/index.dart';

/// Hero card of the gold-treasure invite screen: big commission percentage,
/// the user's own invite code, and the primary "invite now" action.
class GoldCommissionCard extends StatelessWidget {
  const GoldCommissionCard({super.key, required this.onShare});

  final Future<void> Function() onShare;

  @override
  Widget build(BuildContext context) {
    final code = MyDataModel.getInstance().uuid.toString();
    return Container(
      width: double.infinity,
      padding: EdgeInsets.symmetric(horizontal: 18.w, vertical: 22.h),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: ColorManager.giveGrident,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24.r),
        border: Border.all(color: ColorManager.defaultTextVip, width: 1.5),
        boxShadow: [
          BoxShadow(
            color: ColorManager.gold3.withValues(alpha: 0.4),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        children: [
          BlocBuilder<SendInviteBloc, SendInviteState>(
            bloc: di<SendInviteBloc>(),
            buildWhen: (prev, curr) =>
                prev.getMyEarnInviteSuccess != curr.getMyEarnInviteSuccess,
            builder: (context, state) {
              final percent =
                  state.getMyEarnInviteSuccess?.commissionPercent ?? 0;
              return Column(
                children: [
                  TextWidget(
                    StringManager.commissionPercent.tr(),
                    style: context.bodyMedium
                        .size(15)
                        .colorExt(ColorManager.darkGreen),
                  ),
                  6.hBox,
                  TextWidget(
                    "$percent%",
                    isTranslate: false,
                    style: context.bodyMedium
                        .size(48)
                        .bold
                        .colorExt(ColorManager.darkGreen),
                  ),
                ],
              );
            },
          ),
          18.hBox,
          _CodeRow(code: code),
          18.hBox,
          ButtonWidget(
            width: 260.w,
            height: 50.h,
            radius: 30,
            backgroundColors: ColorManager.cashOutGradient,
            titleColor: ColorManager.darkGreen,
            title: StringManager.inviteNow.tr(),
            onPressed: () => onShare(),
          ),
          14.hBox,
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              _ActionIcon(
                icon: Icons.share_outlined,
                label: StringManager.shareInviteCode.tr(),
                onTap: () => onShare(),
              ),
              _ActionIcon(
                icon: Icons.copy_rounded,
                label: StringManager.invitationCode.tr(),
                onTap: () {
                  Clipboard.setData(ClipboardData(text: code));
                  Methods.showToast(
                    context,
                    message: StringManager.theTextHasBeenCopied.tr(),
                  );
                },
              ),
              _ActionIcon(
                icon: Icons.edit_outlined,
                label: StringManager.fillInInvitationCode.tr(),
                onTap: () => _showRedeemDialog(context),
              ),
            ],
          ),
        ],
      ),
    );
  }

  void _showRedeemDialog(BuildContext context) {
    final controller = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16.r),
          ),
          title: Center(
            child: TextWidget(
              StringManager.receiveAward.tr(),
              style:
                  context.bodyMedium.size(16).bold.colorExt(ColorManager.textPrimary),
            ),
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextWidget(
                StringManager.enterInvitationCode.tr(),
                textAlign: TextAlign.center,
                style:
                    context.bodyMedium.size(14).colorExt(ColorManager.textPrimary),
              ),
              12.hBox,
              TextField(
                controller: controller,
                decoration: InputDecoration(
                  hintText: StringManager.enterCode.tr(),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12.r),
                  ),
                ),
              ),
            ],
          ),
          actions: [
            ButtonWidget(
              width: 250.w,
              height: 48.h,
              radius: 12,
              backgroundColor: ColorManager.gold3,
              title: StringManager.submit.tr(),
              onPressed: () {
                final code = controller.text.trim();
                Navigator.pop(ctx);
                if (code.isNotEmpty) {
                  di<SendInviteBloc>().add(AddInviteEvent(code: code));
                } else {
                  Methods.showToast(
                    context,
                    isError: true,
                    message: StringManager.enterValidCode.tr(),
                  );
                }
              },
            ),
          ],
        );
      },
    );
  }
}

class _CodeRow extends StatelessWidget {
  const _CodeRow({required this.code});

  final String code;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
      decoration: BoxDecoration(
        color: ColorManager.darkGreen.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(16.r),
        border: Border.all(color: ColorManager.darkGreen.withValues(alpha: 0.3)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          TextWidget(
            StringManager.invitationCode.tr(),
            style:
                context.bodyMedium.size(13).colorExt(ColorManager.darkGreen),
          ),
          8.wBox,
          Flexible(
            child: TextWidget(
              code,
              isTranslate: false,
              overflow: TextOverflow.ellipsis,
              style: context.bodyMedium
                  .size(18)
                  .bold
                  .colorExt(ColorManager.darkGreen),
            ),
          ),
          6.wBox,
          GestureDetector(
            onTap: () {
              Clipboard.setData(ClipboardData(text: code));
              Methods.showToast(
                context,
                message: StringManager.theTextHasBeenCopied.tr(),
              );
            },
            child: Icon(
              Icons.copy,
              size: 18.h,
              color: ColorManager.darkGreen,
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionIcon extends StatelessWidget {
  const _ActionIcon({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: SizedBox(
        width: 90.w,
        child: Column(
          children: [
            Container(
              width: 46.w,
              height: 46.h,
              decoration: const BoxDecoration(
                color: ColorManager.darkGreen,
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: ColorManager.defaultTextVip, size: 22.h),
            ),
            6.hBox,
            TextWidget(
              label,
              maxLines: 2,
              textAlign: TextAlign.center,
              style:
                  context.bodyMedium.size(11).colorExt(ColorManager.darkGreen),
            ),
          ],
        ),
      ),
    );
  }
}
