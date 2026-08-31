import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/auth.dart';

/// Theme3 (NEXO) profile menu list: the remaining shortcut items
/// (family/badge/agency/feedback) plus block list/about us/settings — a
/// single flat list, per the design brief's "menu list" description. Coins
/// and invite-friends live in [Theme3QuickGrid] instead (banner/tile) to
/// avoid duplicating them here. Every destination/condition is copied
/// unchanged from [Theme2MenuGrid] + [Theme2SettingsSection] — visuals only.
class Theme3MenuList extends StatelessWidget {
  final MyDataEntity data;

  const Theme3MenuList({super.key, required this.data});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(horizontal: 16.w),
      decoration: BoxDecoration(
        color: ColorManager.theme3Card,
        borderRadius: BorderRadius.circular(16.r),
      ),
      child: Column(
        children: [
          _MenuRow(
            icon: Icons.groups_outlined,
            iconColor: ColorManager.theme3Cta,
            title: StringManager.family.tr(),
            onTap: () => navKey.currentContext?.pushNamedRoute(Routes.familyRankPage),
          ),
          _divider(),
          _MenuRow(
            icon: Icons.emoji_events_outlined,
            iconColor: ColorManager.theme3Gold,
            title: StringManager.badge.tr(),
            onTap: () => navKey.currentContext?.pushNamedRoute(Routes.medalsScreen),
          ),
          if (ConstantsManager.isHostAgencyVisible) ...[
            _divider(),
            _MenuRow(
              icon: Icons.apartment_outlined,
              iconColor: ColorManager.theme3Cta,
              title: StringManager.agency1.tr(),
              onTap: () {
                if (StringManager.userType[2]! || StringManager.userType[1]!) {
                  navKey.currentContext?.pushNamedRoute(Routes.newAgencyScreen);
                } else {
                  navKey.currentContext?.pushNamedRoute(Routes.searchForAgencyScreen);
                }
              },
            ),
          ],
          if (StringManager.userType[3]! || StringManager.userType[6]!) ...[
            _divider(),
            _MenuRow(
              icon: Icons.apartment_outlined,
              iconColor: ColorManager.theme3Cta,
              title: StringManager.chargeAgency.tr(),
              onTap: () =>
                  navKey.currentContext?.pushNamedRoute(Routes.chargeAgencyScreen),
            ),
          ],
          _divider(),
          _MenuRow(
            icon: Icons.support_agent_outlined,
            iconColor: ColorManager.theme3Cta,
            title: StringManager.feedBack.tr(),
            onTap: () =>
                navKey.currentContext?.pushNamedRoute(Routes.problemReportsScreen),
          ),
          _divider(),
          _MenuRow(
            icon: Icons.lock_outline,
            iconColor: ColorManager.theme3TextSecondary,
            title: StringManager.blockList.tr(),
            onTap: () => context.pushNamedRoute(Routes.blockListScreen),
          ),
          _divider(),
          _MenuRow(
            icon: Icons.info_outline,
            iconColor: ColorManager.theme3TextSecondary,
            title: StringManager.aboutUs.tr(),
            onTap: () => context.pushNamedRoute(Routes.aboutUsPage),
          ),
          _divider(),
          _MenuRow(
            icon: Icons.settings_outlined,
            iconColor: ColorManager.theme3TextSecondary,
            title: StringManager.settings.tr(),
            onTap: () => context.pushNamedRoute(Routes.settingsScreen),
          ),
        ],
      ),
    );
  }

  Widget _divider() => Divider(
        color: ColorManager.theme3TextSecondary.withValues(alpha: 0.15),
        height: 1,
        indent: 56.w,
      );
}

class _MenuRow extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String title;
  final VoidCallback onTap;

  const _MenuRow({
    required this.icon,
    required this.iconColor,
    required this.title,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
        child: Row(
          children: [
            Icon(icon, size: 22.sp, color: iconColor),
            12.wBox,
            Expanded(
              child: Text(
                title,
                style: TextStyle(
                  color: ColorManager.theme3TextPrimary,
                  fontSize: 15.sp,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
            Icon(
              Icons.arrow_forward_ios,
              size: 14.sp,
              color: ColorManager.theme3TextSecondary,
            ),
          ],
        ),
      ),
    );
  }
}
