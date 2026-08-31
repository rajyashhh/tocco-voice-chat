import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/auth.dart';
import 'package:general/src/features/home/presentation/daily_prize/bloc/daily_prizes_bloc.dart';
import 'package:general/src/features/home/presentation/daily_prize/view/daily_prize_dialog.dart';
import 'package:general/src/features/profile/presentation/coins/bloc/my_store_bloc/my_store_bloc.dart';

/// Theme3 (NEXO) profile mid-section: the two gradient chips ("Wealth Level" /
/// "VIP Club"), the 4-tile quick grid (Tasks/Store/My Items/Invite Friends),
/// and the pink coins banner row — per the design brief's light profile
/// screen. Every destination/action here is copied unchanged from
/// [Theme2MenuGrid]'s matching items (check-in/tasks, store, bag, invite) —
/// visuals only.
class Theme3QuickGrid extends StatelessWidget {
  final MyDataEntity data;

  const Theme3QuickGrid({super.key, required this.data});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        _WealthVipChipsRow(),
        16.hBox,
        _QuickTilesRow(data: data),
        16.hBox,
        _CoinsBanner(),
      ],
    );
  }
}

class _WealthVipChipsRow extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(horizontal: 16.w),
      child: Row(
        children: [
          Expanded(
            child: _GradientChip(
              icon: Icons.diamond_outlined,
              label: StringManager.wealth.tr(),
              gradient: ColorManager.theme3WealthGradient,
              onTap: () =>
                  navKey.currentContext?.pushNamedRoute(Routes.levelScreen),
            ),
          ),
          10.wBox,
          Expanded(
            child: _GradientChip(
              icon: Icons.workspace_premium_outlined,
              label: StringManager.vip.tr(),
              gradient: ColorManager.theme3VipGoldGradient,
              onTap: () =>
                  navKey.currentContext?.pushNamedRoute(Routes.vipScreen),
            ),
          ),
        ],
      ),
    );
  }
}

class _GradientChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final List<Color> gradient;
  final VoidCallback onTap;

  const _GradientChip({
    required this.icon,
    required this.label,
    required this.gradient,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16.r),
      child: Container(
        padding: EdgeInsets.symmetric(horizontal: 14.w, vertical: 12.h),
        decoration: BoxDecoration(
          gradient: LinearGradient(colors: gradient),
          borderRadius: BorderRadius.circular(16.r),
        ),
        child: Row(
          children: [
            Icon(icon, color: ColorManager.white, size: 20.sp),
            8.wBox,
            Expanded(
              child: Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: ColorManager.white,
                  fontSize: 13.sp,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _QuickTilesRow extends StatelessWidget {
  final MyDataEntity data;

  const _QuickTilesRow({required this.data});

  @override
  Widget build(BuildContext context) {
    final tiles = <_TileData>[
      _TileData(
        icon: Icons.assignment_outlined,
        color: ColorManager.blue,
        label: StringManager.tasks.tr(),
        onTap: () {
          final context = SafeNavigator.context;
          if (context == null) return;
          if (di<DailyPrizesBloc>().state.requestStateGetPrize.isLoaded) {
            showDialog(
              context: context,
              builder: (_) => const Dialog(
                backgroundColor: ColorManager.transparent,
                insetPadding:
                    EdgeInsets.symmetric(horizontal: 20.0, vertical: 0),
                child: DailyPrizeDialog(isNeedCompleteInfoDialog: false),
              ),
            );
          } else {
            di<DailyPrizesBloc>().add(GetDailyPrizesEvent(context: context));
            Methods.safeShowToast(message: StringManager.noGift.tr());
          }
        },
      ),
      _TileData(
        icon: Icons.storefront_outlined,
        color: ColorManager.orange,
        label: StringManager.store.tr(),
        onTap: () => navKey.currentContext?.pushNamedRoute(Routes.mallScreen),
      ),
      _TileData(
        icon: Icons.checkroom_outlined,
        color: ColorManager.green,
        label: StringManager.myBag.tr(),
        onTap: () => navKey.currentContext?.pushNamedRoute(Routes.bagScreen),
      ),
      if (data.showInvitationCode == true)
        _TileData(
          icon: Icons.person_add_alt_outlined,
          color: ColorManager.theme3Cta,
          label: StringManager.inviteFriendsTitle.tr(),
          onTap: () =>
              navKey.currentContext?.pushNamedRoute(Routes.inviteBonus),
        ),
    ];

    return Container(
      margin: EdgeInsets.symmetric(horizontal: 16.w),
      padding: EdgeInsets.symmetric(vertical: 14.h),
      decoration: BoxDecoration(
        color: ColorManager.theme3Card,
        borderRadius: BorderRadius.circular(16.r),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [for (final tile in tiles) _QuickTile(data: tile)],
      ),
    );
  }
}

class _TileData {
  final IconData icon;
  final Color color;
  final String label;
  final VoidCallback onTap;

  const _TileData({
    required this.icon,
    required this.color,
    required this.label,
    required this.onTap,
  });
}

class _QuickTile extends StatelessWidget {
  final _TileData data;

  const _QuickTile({required this.data});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: data.onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            height: 44.h,
            width: 44.w,
            decoration: BoxDecoration(
              color: data.color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12.r),
            ),
            child: Icon(data.icon, color: data.color, size: 22.sp),
          ),
          6.hBox,
          Text(
            data.label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: ColorManager.theme3TextSecondary,
              fontSize: 11.sp,
            ),
          ),
        ],
      ),
    );
  }
}

class _CoinsBanner extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocBuilder<MyStoreBloc, MyStoreState>(
      bloc: di<MyStoreBloc>(),
      buildWhen: (prev, curr) => prev.myStore?.coins != curr.myStore?.coins,
      builder: (context, storeState) {
        return InkWell(
          onTap: () => context.pushNamedRoute(Routes.coinsPage),
          borderRadius: BorderRadius.circular(16.r),
          child: Container(
            margin: EdgeInsets.symmetric(horizontal: 16.w),
            padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: ColorManager.theme3VipBannerGradient,
              ),
              borderRadius: BorderRadius.circular(16.r),
            ),
            child: Row(
              children: [
                CoinIcon(size: 26.sp),
                10.wBox,
                Expanded(
                  child: Text(
                    StringManager.coins.tr(),
                    style: TextStyle(
                      color: ColorManager.white,
                      fontSize: 14.sp,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                Text(
                  '${storeState.myStore?.coins ?? 0}',
                  style: TextStyle(
                    color: ColorManager.white,
                    fontSize: 15.sp,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                6.wBox,
                Icon(Icons.arrow_forward_ios, color: ColorManager.white, size: 12.sp),
              ],
            ),
          ),
        );
      },
    );
  }
}
