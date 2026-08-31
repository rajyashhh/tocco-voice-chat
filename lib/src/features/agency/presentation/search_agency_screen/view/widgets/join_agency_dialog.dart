import 'package:general/src/core/index.dart';
import 'package:general/src/features/agency/agency.dart';

/// Collects the applicant's WhatsApp number before dispatching a join request.
///
/// The backend persists this number (`whatsapp`) on the join request so the
/// agency owner / BD can contact the applicant. It is optional server-side, so
/// an empty field still submits (null) — matching the backend `nullable` rule.
Future<void> showJoinAgencyDialog(
  BuildContext context, {
  required String agencyId,
}) {
  final controller = TextEditingController();
  return showDialog(
    context: context,
    builder: (dialogContext) => AnimatedDialog(
      title: StringManager.whatsapp.tr(),
      isUpdateDialog: true,
      conText: StringManager.join.tr(),
      child: Padding(
        padding: dialogContext.paddingSymmetric(vertical: 10),
        child: TextInputWidget(
          StringManager.whatsapp.tr(),
          controller: controller,
          keyboardType: TextInputType.phone,
          fillColor: ColorManager.scaffoldBg,
          enabledBorder: OutlineInputBorder(
            borderRadius: 10.radius,
            borderSide: BorderSide(color: ColorManager.grey.withValues(alpha: 0.3)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: 10.radius,
            borderSide: BorderSide(color: ColorManager.primary),
          ),
        ),
      ),
      onTap: () {
        final number = controller.text.trim();
        Navigator.pop(dialogContext);
        di<JoinToAgenciesBloc>().add(
          JoinToAgencyEvent(
            agencyId: agencyId,
            context: context,
            whatsAppNum: number.isEmpty ? null : number,
          ),
        );
      },
    ),
  );
}
