import 'package:package_info_plus/package_info_plus.dart';
import 'package:flutter_widget_from_html/flutter_widget_from_html.dart';
import 'package:general/src/core/realtime/realtime_config.dart';
import '../../../../../core/index.dart';

/// Professional "About Us" screen with real version info, animated logo,
/// and bilingual (Arabic/English) app description.
class AboutUsPage extends StatefulWidget {
  const AboutUsPage({super.key});

  @override
  State<AboutUsPage> createState() => _AboutUsPageState();
}

class _AboutUsPageState extends State<AboutUsPage>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;
  String _version = '...';

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      duration: const Duration(milliseconds: 800),
      vsync: this,
    );
    _fadeAnimation = CurvedAnimation(
      parent: _controller,
      curve: Curves.easeIn,
    );
    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.1),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic),
    );
    _controller.forward();
    _loadVersion();
  }

  Future<void> _loadVersion() async {
    final info = await PackageInfo.fromPlatform();
    if (mounted) {
      setState(() {
        _version = '${info.version}+${info.buildNumber}';
      });
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isArabic = context.locale.languageCode == 'ar';
    return Scaffold(
      backgroundColor: ColorManager.scaffoldBgAlt,
      appBar: AppBarWidget(
        backgroundColor: ColorManager.scaffoldBgAlt,
        title: StringManager.aboutUs.tr(),
      ),
      body: FadeTransition(
        opacity: _fadeAnimation,
        child: SlideTransition(
          position: _slideAnimation,
          child: SingleChildScrollView(
            padding: EdgeInsets.symmetric(horizontal: 24.w, vertical: 20.h),
            child: Column(
              children: [
                // Animated logo
                TweenAnimationBuilder<double>(
                  tween: Tween(begin: 0.8, end: 1.0),
                  duration: const Duration(milliseconds: 600),
                  curve: Curves.elasticOut,
                  builder: (context, scale, child) {
                    return Transform.scale(
                      scale: scale,
                      child: child,
                    );
                  },
                  child: Container(
                    padding: EdgeInsets.all(20.w),
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: LinearGradient(
                        colors: [
                          ColorManager.primary.withValues(alpha: 0.1),
                          ColorManager.primary.withValues(alpha: 0.05),
                        ],
                      ),
                    ),
                    child: Image.asset(
                      AssetsManager.logo,
                      width: 120.w,
                      height: 120.w,
                    ),
                  ),
                ),

                16.hBox,

                // App name
                Text(
                  StringManager.appName.tr(),
                  style: TextStyle(
                    fontSize: 28.sp,
                    fontWeight: FontWeight.bold,
                    color: ColorManager.textPrimary,
                  ),
                ),

                8.hBox,

                // Real version from package_info_plus
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 6.h),
                  decoration: BoxDecoration(
                    color: ColorManager.primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(20.r),
                  ),
                  child: Text(
                    'Version $_version',
                    style: TextStyle(
                      fontSize: 14.sp,
                      color: ColorManager.primary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),

                32.hBox,

                // App description — admin-managed (bilingual) content from the
                // panel via /config/settings. No brand/client name is hardcoded;
                // when the admin hasn't filled it yet, a neutral empty state is
                // shown instead.
                if (_aboutUsContent(isArabic).isNotEmpty)
                  _buildInfoCardChild(
                    title: isArabic ? 'عن التطبيق' : 'About the App',
                    icon: Icons.info_outline,
                    child: HtmlWidget(
                      _aboutUsContent(isArabic),
                      textStyle: TextStyle(
                        fontSize: 14.sp,
                        height: 1.6,
                        color: ColorManager.secondaryText,
                      ),
                    ),
                  )
                else
                  _buildEmptyState(isArabic),

                24.hBox,

                // Footer — app name comes from config (StringManager.appName),
                // never a hardcoded brand.
                Text(
                  '© ${DateTime.now().year} ${StringManager.appName.tr()}. ${isArabic ? 'جميع الحقوق محفوظة.' : 'All rights reserved.'}',
                  style: TextStyle(
                    fontSize: 12.sp,
                    color: ColorManager.secondaryText,
                  ),
                ),

                8.hBox,
              ],
            ),
          ),
        ),
      ),
    );
  }

  /// Admin-managed About Us text for the active language, falling back to the
  /// other language if only one is filled. Empty when the admin set neither.
  String _aboutUsContent(bool isArabic) {
    final primary =
        isArabic ? RealtimeConfig.aboutUsAr : RealtimeConfig.aboutUsEn;
    if (primary.isNotEmpty) return primary;
    return isArabic ? RealtimeConfig.aboutUsEn : RealtimeConfig.aboutUsAr;
  }

  /// Info card with an icon header and an arbitrary child body
  /// (e.g. HtmlWidget).
  Widget _buildInfoCardChild({
    required String title,
    required IconData icon,
    required Widget child,
  }) {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.all(20.w),
      decoration: BoxDecoration(
        color: ColorManager.surfaceCardColor,
        borderRadius: BorderRadius.circular(16.r),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: EdgeInsets.all(8.w),
                decoration: BoxDecoration(
                  color: ColorManager.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8.r),
                ),
                child: Icon(
                  icon,
                  color: ColorManager.primary,
                  size: 20.sp,
                ),
              ),
              12.wBox,
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: 16.sp,
                    fontWeight: FontWeight.bold,
                    color: ColorManager.textPrimary,
                  ),
                ),
              ),
            ],
          ),
          12.hBox,
          child,
        ],
      ),
    );
  }

  /// Neutral empty state shown when the admin hasn't filled the About Us
  /// content (both languages empty). Same card styling as the info cards.
  Widget _buildEmptyState(bool isArabic) {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 32.h),
      decoration: BoxDecoration(
        color: ColorManager.surfaceCardColor,
        borderRadius: BorderRadius.circular(16.r),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          Container(
            padding: EdgeInsets.all(14.w),
            decoration: BoxDecoration(
              color: ColorManager.primary.withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(
              Icons.info_outline,
              color: ColorManager.primary,
              size: 28.sp,
            ),
          ),
          14.hBox,
          Text(
            isArabic
                ? 'سيتم إضافة المحتوى قريبًا'
                : 'Content will be added soon',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 14.sp,
              height: 1.6,
              color: ColorManager.secondaryText,
            ),
          ),
        ],
      ),
    );
  }
}
