import 'package:share_plus/share_plus.dart';
import 'package:general/src/core/services/dynamic_link_handler.dart';
import 'package:general/src/features/setting/presentation/invitation/bloc/invite_bloc.dart';
import 'package:general/src/features/setting/presentation/invitation/view/widgets/gold_commission_card.dart';
import 'package:general/src/features/setting/presentation/invitation/view/widgets/gold_income_card.dart';
import 'package:general/src/features/setting/presentation/invitation/view/widgets/gold_invitations_card.dart';
import 'package:general/src/features/setting/presentation/invitation/view/widgets/gold_rules_card.dart';

import '../../../../../core/index.dart';

class InviteBonusScreen extends StatefulWidget {
  const InviteBonusScreen({super.key});

  @override
  State<InviteBonusScreen> createState() => _InviteBonusScreenState();
}

class _InviteBonusScreenState extends State<InviteBonusScreen> {
  @override
  void initState() {
    super.initState();
    final bloc = di<SendInviteBloc>();
    if (!bloc.state.explainInviteRequest.isLoaded) {
      bloc.add(ExplainInviteEvent());
    }
    if (!bloc.state.getMyEarnInviteRequest.isLoaded) {
      bloc.add(GetMyEarnInviteEvent());
    }
  }

  Future<void> _shareCode() async {
    final code = MyDataModel.getInstance().uuid.toString();
    final link = await DynamicLinkHandler.instance.createDynamicLink(
      code,
      'invitation_code',
    );
    await SharePlus.instance.share(ShareParams(uri: Uri.parse(link)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorManager.inviteGoldBgBottom,
      appBar: AppBarWidget(
        backgroundColor: ColorManager.transparent,
        iconColor: ColorManager.defaultTextVip,
        titleStyle: const TextStyle(color: ColorManager.defaultTextVip),
        title: StringManager.inviteBonus.tr(),
      ),
      extendBodyBehindAppBar: true,
      body: BlocListener<SendInviteBloc, SendInviteState>(
        bloc: di<SendInviteBloc>(),
        listenWhen: (prev, curr) =>
            prev.extractCoinsRequest != curr.extractCoinsRequest ||
            prev.claimBonusRequest != curr.claimBonusRequest ||
            prev.addInviteRequest != curr.addInviteRequest,
        listener: (context, state) {
          if (state.extractCoinsRequest.isLoading ||
              state.claimBonusRequest.isLoading ||
              state.addInviteRequest.isLoading) {
            Methods.showToast(context, isLoading: true);
            return;
          }
          if (state.extractCoinsRequest.isLoaded) {
            Methods.showToast(context, message: state.extractCoinsMessage);
          } else if (state.extractCoinsRequest.isError) {
            Methods.showToast(context,
                isError: true, message: state.extractCoinsMessage);
          } else if (state.claimBonusRequest.isLoaded) {
            Methods.showToast(context, message: state.claimBonusMessage);
          } else if (state.claimBonusRequest.isError) {
            Methods.showToast(context,
                isError: true, message: state.claimBonusMessage);
          } else if (state.addInviteRequest.isLoaded) {
            Methods.showToast(context, message: state.addInviteMessage);
          } else if (state.addInviteRequest.isError) {
            Methods.showToast(context,
                isError: true, message: state.addInviteMessage);
          }
        },
        child: Container(
          width: double.infinity,
          height: double.infinity,
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                ColorManager.inviteGoldBgTop,
                ColorManager.inviteGoldBgBottom,
              ],
            ),
          ),
          child: SafeArea(
            child: SingleChildScrollView(
              padding: context.paddingSymmetric(horizontal: 16.w, vertical: 8.h),
              child: Column(
                children: [
                  8.hBox,
                  GoldCommissionCard(onShare: _shareCode),
                  16.hBox,
                  const GoldIncomeCard(),
                  16.hBox,
                  const GoldInvitationsCard(),
                  16.hBox,
                  const GoldRulesCard(),
                  16.hBox,
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
