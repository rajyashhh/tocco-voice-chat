import 'package:general/src/core/index.dart';
import 'package:general/src/core/realtime/realtime_config.dart';
import 'package:flutter_widget_from_html/flutter_widget_from_html.dart';

class PrivacyPolicyPage extends StatelessWidget {
  const PrivacyPolicyPage({super.key});

  /// Admin-managed privacy content for the active language, falling back to the
  /// other language if only one is filled. Single source: the admin panel via
  /// /config/settings (RealtimeConfig). No brand/client text is hardcoded.
  String _content(bool isArabic) {
    final primary = isArabic
        ? RealtimeConfig.privacyPolicyAr
        : RealtimeConfig.privacyPolicyEn;
    if (primary.isNotEmpty) return primary;
    return isArabic
        ? RealtimeConfig.privacyPolicyEn
        : RealtimeConfig.privacyPolicyAr;
  }

  @override
  Widget build(BuildContext context) {
    final isArabic =
        HiveManager().getData(KeysManager.USER_BOX, KeysManager.LANG_CODE_KEY) ==
            'ar';
    final content = _content(isArabic);
    return Scaffold(
      backgroundColor: ColorManager.scaffoldBgAlt,
      appBar: AppBarWidget(
        backgroundColor: ColorManager.scaffoldBgAlt,
        title: StringManager.privacyPolicy.tr(),
      ),
      body: Container(
        width: ScreenUtil().screenWidth,
        height: ScreenUtil().screenHeight,
        decoration: const BoxDecoration(),
        padding: context.paddingSymmetric(horizontal: 20),
        child: content.isEmpty
            ? Center(
                child: Text(
                  StringManager.noDataYet.tr(),
                  style: context.bodyLarge.colorExt(ColorManager.textPrimary),
                ),
              )
            : SingleChildScrollView(
                child: Column(
                  children: [
                    5.hBox,
                    HtmlWidget(
                      content,
                      textStyle:
                          context.bodyLarge.colorExt(ColorManager.textPrimary),
                    ),
                    5.hBox,
                  ],
                ),
              ),
      ),
    );
  }
}
