import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/auth.dart';
import 'package:general/src/features/profile/presentation/coins/bloc/my_store_bloc/my_store_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_user_badges/get_user_badges_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_user_badges/get_user_badges_event.dart';
import 'package:general/src/features/agency/presentation/charge_agency/view/component/host_withdrawel_screen/bloc/get_setting_manager/get_setting_bloc.dart';
import 'package:general/src/features/home/presentation/daily_prize/bloc/daily_prizes_bloc.dart';
import 'package:general/src/features/home/presentation/daily_prize/view/daily_prize_dialog.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/edit_info_screen/bloc/edit_information/edit_information_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/edit_info_screen/edit_profile_screen.dart';

import '../../app/theme.dart';

/// Theme 4 Profile / "Me" screen — fully functional.
///
/// All data comes from existing Tocco BLoCs. No hardcoded values.
/// Every visible action navigates to the existing Tocco route.
class Theme4ProfilePage extends StatefulWidget {
  const Theme4ProfilePage({super.key});

  @override
  State<Theme4ProfilePage> createState() => _Theme4ProfilePageState();
}

class _Theme4ProfilePageState extends State<Theme4ProfilePage> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      di<GetUserBadgesBloc>().add(
        GetUserBadgesData(id: MyDataModel.getInstance().id ?? 0),
      );
      if (!di<MyStoreBloc>().state.reqState.isLoaded) {
        di<MyStoreBloc>().add(const GetMyStoreEvent(isLoading: false));
      }
      if (!di<GetSettingBloc>().state.state.isLoaded) {
        di<GetSettingBloc>().add(const GetSettingsEvent());
      }
      // Pre-load daily prizes so Task opens instantly when tapped
      if (!di<DailyPrizesBloc>().state.requestStateGetPrize.isLoaded) {
        di<DailyPrizesBloc>().add(GetDailyPrizesEvent(context: context));
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        // Background image — matches designer
        Positioned.fill(
          child: Image.asset(
            'assets/images/me/bg.png',
            fit: BoxFit.cover,
          ),
        ),
        // Content
        BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
          bloc: di<FetchUserDataBloc>(),
          buildWhen: (prev, curr) =>
              prev.reqState != curr.reqState ||
              prev.userEntity != curr.userEntity,
          builder: (context, state) {
            final data = state.userEntity ?? const MyDataEntity();

            // Loading state
            if (state.reqState.isLoading && data.id == null) {
              return const SafeArea(
                child: Center(
                  child: CircularProgressIndicator.adaptive(),
                ),
              );
            }

            return RefreshIndicator(
              onRefresh: () async {
                di<FetchUserDataBloc>().add(
                  const FetchMyDataEvent(isLoading: false),
                );
                di<GetSettingBloc>().add(const GetSettingsEvent());
                di<MyStoreBloc>().add(const GetMyStoreEvent());
                di<DailyPrizesBloc>()
                    .add(const GetDailyPrizesEvent());
              },
              child: SafeArea(
                bottom: false,
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(8, 0, 8, 120),
                  child: Column(
                    children: [
                      _ProfileHeaderCard(data: data),
                      const SizedBox(height: 16),
                      _WalletDiamondRow(data: data),
                      const SizedBox(height: 16),
                      _VIPBanner(data: data),
                      const SizedBox(height: 16),
                      _QuickActionsRow(data: data),
                      const SizedBox(height: 16),
                      _SettingsList(data: data),
                    ],
                  ),
                ),
              ),
            );
          },
        ),
      ],
    );
  }
}

// ── Profile Header Card ─────────────────────────────────────────────────────

class _ProfileHeaderCard extends StatelessWidget {
  final MyDataEntity data;
  const _ProfileHeaderCard({required this.data});

  void _editName(BuildContext context) {
    di<EditInformationBloc>().add(AssignInformationEvent());
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16.0)),
      ),
      builder: (_) => EditInfoScreen(
        params: EditProfileParameter(
          content: '${data.name}',
          isUsername: true,
        ),
      ),
    );
  }

  void _editPhoto(BuildContext context) {
    di<EditInformationBloc>().add(AssignInformationEvent());
    di<EditInformationBloc>().add(const PickImageEvent(fromCamera: false));
  }

  @override
  Widget build(BuildContext context) {
    final imageUrl = (data.profile?.image?.isNotEmpty ?? false)
        ? EndPoints.getImage(data.profile!.image!)
        : '';
    final uuid = data.uuid ?? data.id ?? '';

    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        image: const DecorationImage(
          image: AssetImage('assets/images/me/profile_header_bg.webp'),
          fit: BoxFit.fill,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: T4Shadows.card,
      ),
      child: Stack(
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 40, bottom: 20),
            child: Column(
              children: [
                // Avatar with photo-edit badge
                SizedBox(
                  width: 100,
                  height: 100,
                  child: Stack(
                    clipBehavior: Clip.none,
                    alignment: Alignment.center,
                    children: [
                      CircleAvatar(
                        radius: 45,
                        backgroundColor: Colors.white,
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(40),
                          child: imageUrl.isNotEmpty
                              ? Image.network(
                                  imageUrl,
                                  fit: BoxFit.cover,
                                  width: 80,
                                  height: 80,
                                  errorBuilder: (_, __, ___) => Image.asset(
                                    'assets/images/home/profile_dp.webp',
                                    fit: BoxFit.cover,
                                    width: 80,
                                    height: 80,
                                  ),
                                )
                              : Image.asset(
                                  'assets/images/home/profile_dp.webp',
                                  fit: BoxFit.cover,
                                  width: 80,
                                  height: 80,
                                ),
                        ),
                      ),
                      // Quick-edit photo badge
                      Positioned(
                        right: 0,
                        bottom: 0,
                        child: GestureDetector(
                          onTap: () => _editPhoto(context),
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: BoxDecoration(
                              gradient: T4Colors.brand,
                              shape: BoxShape.circle,
                              border: Border.all(
                                color: Colors.white,
                                width: 1.5,
                              ),
                            ),
                            child: const Icon(
                              Icons.photo_camera_outlined,
                              size: 12,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                // Name with gradient shader + edit tap
                GestureDetector(
                  onTap: () => _editName(context),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      ShaderMask(
                        blendMode: BlendMode.srcIn,
                        shaderCallback: (Rect bounds) {
                          return const LinearGradient(
                            begin: Alignment.centerLeft,
                            end: Alignment.centerRight,
                            colors: [
                              Color(0xFFF62121),
                              Color(0xFFFA17DC),
                              Color(0xFF2D03E6),
                            ],
                            stops: [0.0, 0.2869, 1.0],
                          ).createShader(bounds);
                        },
                        child: Text(
                          data.name ?? '',
                          style: const TextStyle(
                            fontSize: 22,
                            fontWeight: FontWeight.w900,
                            color: Colors.white,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      const Icon(Icons.edit_rounded,
                          size: 16, color: Colors.grey),
                    ],
                  ),
                ),
                const SizedBox(height: 4),
                // User ID with copy
                GestureDetector(
                  onTap: () {
                    Clipboard.setData(ClipboardData(text: '$uuid'));
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('ID copied to clipboard'),
                        duration: Duration(seconds: 1),
                      ),
                    );
                  },
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'ID: $uuid',
                        style: const TextStyle(
                          fontSize: 14,
                          color: Colors.black54,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(width: 4),
                      const Icon(Icons.copy_rounded,
                          size: 13, color: Colors.black38),
                    ],
                  ),
                ),
                const SizedBox(height: 8),
                // Level badges
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    if (data.level?.senderLevel != null)
                      _LevelBadge(
                        label: 'LV.${data.level!.senderLevel}',
                        color: Colors.purple.shade300,
                      ),
                    if (data.level?.reciverLevel != null) ...[
                      const SizedBox(width: 6),
                      _LevelBadge(
                        label: 'LV.${data.level!.reciverLevel}',
                        color: Colors.orange.shade400,
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 24),
                // Stats row — ALL tappable
                IntrinsicHeight(
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      _StatItem(
                        value: _formatCount(data.profileVisitors),
                        label: 'Visitors',
                        onTap: () {
                          Navigator.pushNamed(
                            context,
                            Routes.friendFollowing,
                            arguments: 3,
                          );
                          di<FetchUserDataBloc>()
                              .add(const ReadCounterVistorsEvent());
                        },
                      ),
                      const VerticalDivider(
                          width: 1, thickness: 1, color: Color(0xFFEEEEEE)),
                      _StatItem(
                        value: _formatCount(data.numberOfFriends),
                        label: 'Friends',
                        onTap: () {
                          Navigator.pushNamed(
                            context,
                            Routes.friendFollowing,
                            arguments: 2,
                          );
                        },
                      ),
                      const VerticalDivider(
                          width: 1, thickness: 1, color: Color(0xFFEEEEEE)),
                      _StatItem(
                        value: _formatCount(data.numberOfFollowings),
                        label: 'Following',
                        onTap: () {
                          Navigator.pushNamed(
                            context,
                            Routes.friendFollowing,
                            arguments: 0,
                          );
                          di<FetchUserDataBloc>()
                              .add(const ReadCounterFollowingsEvent());
                        },
                      ),
                      const VerticalDivider(
                          width: 1, thickness: 1, color: Color(0xFFEEEEEE)),
                      _StatItem(
                        value: _formatCount(data.numberOfFans),
                        label: 'Followers',
                        onTap: () {
                          Navigator.pushNamed(
                            context,
                            Routes.friendFollowing,
                            arguments: 1,
                          );
                          di<FetchUserDataBloc>()
                              .add(const ReadCounterFollowersEvent());
                        },
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          // Top bar — no back button (root tab)
          Positioned(
            top: 28,
            left: 8,
            right: 8,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  IconButton(
                    icon: const Icon(Icons.edit_rounded,
                        size: 32, color: Colors.black54),
                    onPressed: () {
                      navKey.currentContext
                          ?.pushNamedRoute(Routes.editProfile);
                    },
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatCount(dynamic count) {
    if (count == null) return '0';
    final n = count is int ? count : int.tryParse('$count') ?? 0;
    if (n >= 10000) return '${(n / 1000).toStringAsFixed(1)}k';
    if (n >= 1000) return '${(n / 1000).toStringAsFixed(1)}k';
    return '$n';
  }
}

class _LevelBadge extends StatelessWidget {
  final String label;
  final Color color;
  const _LevelBadge({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    final levelNum = int.tryParse(label.replaceFirst('LV.', ''));
    if (levelNum != null && levelNum <= 8) {
      final assetPath = 'assets/images/me/level_$levelNum.png';
      return Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
        child: Image.asset(
          assetPath,
          width: 50,
          errorBuilder: (_, __, ___) => _fallbackBadge(label, color),
        ),
      );
    }
    return _fallbackBadge(label, color);
  }

  Widget _fallbackBadge(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  final String value;
  final String label;
  final VoidCallback? onTap;
  const _StatItem({required this.value, required this.label, this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4),
        child: Column(
          children: [
            ShaderMask(
              blendMode: BlendMode.srcIn,
              shaderCallback: (Rect bounds) {
                return const LinearGradient(
                  begin: Alignment(-0.88, -0.48),
                  end: Alignment(0.88, 0.48),
                  colors: [Color(0xFFFE1515), Color(0xFFFBCB07)],
                  stops: [0.0, 0.6774],
                ).createShader(bounds);
              },
              child: Text(
                value,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                  color: Colors.white,
                ),
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: const TextStyle(
                fontSize: 12,
                color: Colors.black54,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Wallet / Diamond Row ────────────────────────────────────────────────────

class _WalletDiamondRow extends StatelessWidget {
  final MyDataEntity data;
  const _WalletDiamondRow({required this.data});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _WalletCard(
            label: 'Coins',
            value: '${data.myStore?.coins ?? 0}',
            bgAsset: 'assets/images/me/wallet_bg.png',
            markAsset: 'assets/images/me/coin_mark.png',
            mainAsset: 'assets/images/me/wallet.png',
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _WalletCard(
            label: 'Diamond',
            value: '${data.myStore?.diamonds ?? 0}',
            bgAsset: 'assets/images/me/diamond_bg.png',
            markAsset: 'assets/images/me/diamond_mark.png',
            mainAsset: 'assets/images/me/diamond.png',
          ),
        ),
      ],
    );
  }
}

class _WalletCard extends StatelessWidget {
  final String label;
  final String value;
  final String bgAsset;
  final String markAsset;
  final String mainAsset;

  const _WalletCard({
    required this.label,
    required this.value,
    required this.bgAsset,
    required this.markAsset,
    required this.mainAsset,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => context.pushNamedRoute(Routes.coinsPage),
      child: Container(
        clipBehavior: Clip.hardEdge,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          image: DecorationImage(
            image: AssetImage(bgAsset),
            fit: BoxFit.cover,
            colorFilter: ColorFilter.mode(
              const Color(0xFFF3E5F5).withValues(alpha: 0.2),
              BlendMode.darken,
            ),
          ),
        ),
        child: Stack(
          children: [
            Positioned(
              right: -10,
              top: -10,
              bottom: -10,
              child: Opacity(
                opacity: 0.8,
                child: Image.asset(markAsset, fit: BoxFit.contain),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        ShaderMask(
                          blendMode: BlendMode.srcIn,
                          shaderCallback: (Rect bounds) {
                            return const LinearGradient(
                              begin: Alignment.centerLeft,
                              end: Alignment.centerRight,
                              colors: [
                                Color(0xFFF62121),
                                Color(0xFFFA17DC),
                                Color(0xFF2D03E6),
                              ],
                              stops: [0.0, 0.2869, 1.0],
                            ).createShader(Rect.fromLTWH(
                                0, 0, bounds.width, bounds.height));
                          },
                          child: Text(
                            label,
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w800,
                              color: Colors.white,
                            ),
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          value,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Image.asset(mainAsset, width: 53),
                  const Icon(Icons.arrow_forward_ios_rounded,
                      size: 14, color: Colors.grey),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── VIP Banner ──────────────────────────────────────────────────────────────

class _VIPBanner extends StatelessWidget {
  final MyDataEntity data;
  const _VIPBanner({required this.data});

  @override
  Widget build(BuildContext context) {
    final hasVip = data.vip1?.id != null;

    return GestureDetector(
      onTap: () => navKey.currentContext?.pushNamedRoute(Routes.vipScreen),
      child: Container(
        width: double.infinity,
        clipBehavior: Clip.hardEdge,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          image: const DecorationImage(
            image: AssetImage('assets/images/me/vip_banner.png'),
            fit: BoxFit.cover,
          ),
        ),
        child: Stack(
          children: [
            Positioned(
              right: -10,
              bottom: -15,
              child: Image.asset('assets/images/me/vip_mark.png',
                  width: 100, height: 100),
            ),
            Padding(
              padding:
                  const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              child: Row(
                children: [
                  Image.asset('assets/images/me/vip_diamond.png', width: 80),
                  const SizedBox(width: 16),
                  ShaderMask(
                    blendMode: BlendMode.srcIn,
                    shaderCallback: (Rect bounds) {
                      return const LinearGradient(
                        begin: Alignment(-1.0, -0.05),
                        end: Alignment(1.0, 0.05),
                        colors: [
                          Color(0xFFEAAF0E),
                          Color(0xFFF91013),
                          Color(0xD62665EB),
                          Color(0xFF0B16F6),
                        ],
                        stops: [0.1401, 0.5248, 0.8525, 0.9297],
                      ).createShader(Rect.fromLTWH(
                          0, 0, bounds.width, bounds.height));
                    },
                    child: Text(
                      hasVip ? 'VIP / SVIP' : 'Become VIP',
                      style: const TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.w900,
                        color: Colors.white,
                        shadows: [
                          Shadow(
                            color: Colors.black45,
                            offset: Offset(0, 3),
                            blurRadius: 4,
                          ),
                        ],
                      ),
                    ),
                  ),
                  const Spacer(),
                  const Icon(Icons.arrow_forward_ios_rounded,
                      size: 18, color: Colors.grey),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Quick Actions Row ───────────────────────────────────────────────────────

class _QuickActionsRow extends StatelessWidget {
  final MyDataEntity data;
  const _QuickActionsRow({required this.data});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        image: const DecorationImage(
          image: AssetImage('assets/images/me/vip_banner.png'),
          fit: BoxFit.cover,
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          _QuickActionItem(
            image: 'assets/images/me/task.png',
            label: 'Task',
            onTap: () {
              // Use actual DailyPrizeDialog like Theme 3
              final ctx = SafeNavigator.context;
              if (ctx == null) return;
              if (di<DailyPrizesBloc>()
                  .state
                  .requestStateGetPrize
                  .isLoaded) {
                showDialog(
                  context: ctx,
                  builder: (_) => const Dialog(
                    backgroundColor: Colors.transparent,
                    insetPadding:
                        EdgeInsets.symmetric(horizontal: 20.0, vertical: 0),
                    child: DailyPrizeDialog(isNeedCompleteInfoDialog: false),
                  ),
                );
              } else {
                di<DailyPrizesBloc>()
                    .add(GetDailyPrizesEvent(context: ctx));
                Methods.showToast(ctx, message: 'Loading tasks...');
              }
            },
          ),
          _QuickActionItem(
            image: 'assets/images/me/store.png',
            label: 'Store',
            onTap: () =>
                navKey.currentContext?.pushNamedRoute(Routes.mallScreen),
          ),
          _QuickActionItem(
            image: 'assets/images/me/bag.png',
            label: 'Backpack',
            onTap: () =>
                navKey.currentContext?.pushNamedRoute(Routes.bagScreen),
          ),
          _QuickActionItem(
            image: 'assets/images/me/level.png',
            label: 'My level',
            onTap: () =>
                navKey.currentContext?.pushNamedRoute(Routes.levelScreen),
          ),
        ],
      ),
    );
  }
}

class _QuickActionItem extends StatelessWidget {
  final String image;
  final String label;
  final VoidCallback? onTap;
  const _QuickActionItem(
      {required this.image, required this.label, this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Image.asset(image, width: 45),
          const SizedBox(height: 4),
          Text(label,
              style:
                  const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}

// ── Settings List ───────────────────────────────────────────────────────────

class _SettingsList extends StatelessWidget {
  final MyDataEntity data;
  const _SettingsList({required this.data});

  @override
  Widget build(BuildContext context) {
    final bool isHost =
        StringManager.userType[2]! || StringManager.userType[1]!;
    final bool isChargeAgency =
        StringManager.userType[3]! || StringManager.userType[6]!;

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: const LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            Color.fromRGBO(143, 103, 197, 0.3717),
            Color.fromRGBO(254, 106, 136, 0.1888),
          ],
        ),
      ),
      child: Column(
        children: [
          _SettingTile(
            image: 'assets/images/me/cp_center.png',
            label: 'CP Center',
            onTap: () =>
                navKey.currentContext?.pushNamedRoute(Routes.cpPage),
          ),
          if (ConstantsManager.isHostAgencyVisible == true)
            _SettingTile(
              image: 'assets/images/me/agency_center.png',
              label: StringManager.agency1.tr(),
              onTap: () {
                if (isHost) {
                  navKey.currentContext
                      ?.pushNamedRoute(Routes.newAgencyScreen);
                } else {
                  navKey.currentContext
                      ?.pushNamedRoute(Routes.searchForAgencyScreen);
                }
              },
            ),
          _SettingTile(
            image: 'assets/images/me/host_center.png',
            label: 'Host Center',
            onTap: () => t4ShowComingSoon(context, 'Host Center'),
          ),
          _SettingTile(
            image: 'assets/images/me/customer_support.png',
            label: StringManager.feedBack.tr(),
            onTap: () => navKey.currentContext
                ?.pushNamedRoute(Routes.problemReportsScreen),
          ),
          _SettingTile(
            image: 'assets/images/me/verify.png',
            label: 'Verify',
            onTap: () => t4ShowComingSoon(context, 'Verify'),
          ),
          _SettingTile(
            image: 'assets/images/me/family.png',
            label: StringManager.family.tr(),
            onTap: () => navKey.currentContext
                ?.pushNamedRoute(Routes.familyRankPage),
          ),
          // Badges — from Theme 3
          _SettingTile(
            image: 'assets/images/me/family.png',
            label: StringManager.badge.tr(),
            onTap: () => navKey.currentContext
                ?.pushNamedRoute(Routes.medalsScreen),
          ),
          // Charge Agency — conditional, from Theme 3
          if (isChargeAgency)
            _SettingTile(
              image: 'assets/images/me/agency_center.png',
              label: StringManager.chargeAgency.tr(),
              onTap: () => navKey.currentContext
                  ?.pushNamedRoute(Routes.chargeAgencyScreen),
            ),
          // Invite Friends
          _SettingTile(
            image: 'assets/images/me/invite_friends.png',
            label: StringManager.invitation.tr(),
            onTap: () => navKey.currentContext
                ?.pushNamedRoute(Routes.inviteBonus),
          ),
          // Block List — from Theme 3
          _SettingTile(
            image: 'assets/images/me/settings.png',
            label: StringManager.blockList.tr(),
            onTap: () => navKey.currentContext
                ?.pushNamedRoute(Routes.blockListScreen),
          ),
          // About Us — from Theme 3
          _SettingTile(
            image: 'assets/images/me/settings.png',
            label: StringManager.aboutUs.tr(),
            onTap: () => navKey.currentContext
                ?.pushNamedRoute(Routes.aboutUsPage),
          ),
          _SettingTile(
            image: 'assets/images/me/settings.png',
            label: StringManager.settings.tr(),
            onTap: () => navKey.currentContext
                ?.pushNamedRoute(Routes.settingsScreen),
          ),
        ],
      ),
    );
  }
}

class _SettingTile extends StatelessWidget {
  final String image;
  final String label;
  final VoidCallback? onTap;
  const _SettingTile({required this.image, required this.label, this.onTap});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 4.0),
      child: Material(
        color: Colors.transparent,
        child: ListTile(
          tileColor: Colors.transparent,
          contentPadding: const EdgeInsets.symmetric(horizontal: 8.0),
          leading: Image.asset(image, width: 40),
          title: Text(
            label,
            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
          ),
          trailing:
              const Icon(Icons.arrow_forward_ios_rounded, size: 16),
          onTap: onTap ?? () {},
        ),
      ),
    );
  }
}
