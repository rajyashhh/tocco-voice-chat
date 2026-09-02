import 'package:flutter/material.dart';

import '../../app/constants.dart';
import '../../app/theme.dart';
import '../../widgets/coming_soon.dart';
import '../../widgets/gradient_background.dart';

/// Profile screen matching the provided UI design.
class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        // Background image
        Positioned.fill(
          child: Image.asset(
            'assets/images/me/bg.png',
            fit: BoxFit.cover,
          ),
        ),

        // Your existing content
        SafeArea(
          bottom: false,
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(8, 0, 8, 120),
            child: Column(
              children: [
                _ProfileHeaderCard(),
                const SizedBox(height: 16),
                _WalletDiamondRow(),
                const SizedBox(height: 16),
                _VIPBanner(),
                const SizedBox(height: 16),
                _QuickActionsRow(),
                const SizedBox(height: 16),
                _SettingsList(),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _ProfileHeaderCard extends StatelessWidget {
  const _ProfileHeaderCard();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        image: const DecorationImage(
          image: AssetImage('assets/images/me/profile_header_bg.webp'),
          fit: BoxFit.fill,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Stack(
        children: [
          // Content
          Padding(
            padding: const EdgeInsets.only(top: 40, bottom: 20),
            child: Column(
              children: [
                CircleAvatar(
                  radius: 45,
                  backgroundColor: Colors.white,
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(40),
                    child: Image.asset('assets/images/home/profile_dp.webp', fit: BoxFit.cover, width: 80, height: 80),
                  ),
                ),
                const SizedBox(height: 12),
                Row(
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
                          stops: [
                            0.0,    // -27.48% (clamped to valid range)
                            0.2869, // 28.69%
                            1.0,    // 109.71% (clamped to valid range)
                          ],
                        ).createShader(bounds);
                      },
                      child: const Text(
                        'Madelyn Dias',
                        style: TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.w900,
                          color: Colors.white,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    const Icon(Icons.arrow_forward_ios_rounded, size: 16, color: Colors.grey),
                  ],
                ),
                const SizedBox(height: 4),
                const Text(
                  'ID: 123456',
                  style: TextStyle(fontSize: 14, color: Colors.black54, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    _LevelBadge(label: 'LV.4', color: Colors.purple.shade300),
                    _LevelBadge(label: 'LV.8', color: Colors.orange.shade400),
                  ],
                ),
                const SizedBox(height: 24),
                const IntrinsicHeight(
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      _StatItem(value: '2K', label: 'Visitors'),
                      VerticalDivider(width: 1, thickness: 1, color: Color(0xFFEEEEEE)),
                      _StatItem(value: '2K', label: 'Friends'),
                      VerticalDivider(width: 1, thickness: 1, color: Color(0xFFEEEEEE)),
                      _StatItem(value: '1K', label: 'Following'),
                      VerticalDivider(width: 1, thickness: 1, color: Color(0xFFEEEEEE)),
                      _StatItem(value: '10K', label: 'Followers'),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Positioned Top Bar
          Positioned(
            top: 28,
            left: 8,
            right: 8,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 28, color: Colors.black54),
                    onPressed: () {},
                  ),
                  IconButton(
                    icon: const Icon(Icons.edit_rounded, size: 32, color: Colors.black54),
                    onPressed: () {},
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _LevelBadge extends StatelessWidget {
  final String label;
  final Color color;
  const _LevelBadge({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
      child: (label == 'LV.4') ? Image.asset('assets/images/me/level_4.png', width: 50,) :
      Image.asset('assets/images/me/level_8.png', width: 50,),
    );
  }
}

class _StatItem extends StatelessWidget {
  final String value;
  final String label;
  const _StatItem({required this.value, required this.label});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        ShaderMask(
          blendMode: BlendMode.srcIn,
          shaderCallback: (Rect bounds) {
            return const LinearGradient(
              begin: Alignment(-0.88, -0.48), // ~118.81 degrees
              end: Alignment(0.88, 0.48),
              colors: [
                Color(0xFFFE1515),
                Color(0xFFFBCB07),
              ],
              stops: [
                0.0, // Maps -22.58% (clamped to Flutter valid range 0.0 - 1.0)
                0.6774, // Maps 67.74%
              ],
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
        Text(label, style: const TextStyle(fontSize: 12, color: Colors.black54, fontWeight: FontWeight.w600)),
      ],
    );
  }
}

class _WalletDiamondRow extends StatelessWidget {
  const _WalletDiamondRow();

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(child: _WalletCard(label: 'Wallet', value: '120000', icon: Icons.wallet_giftcard_rounded, color: const Color(0xFFFFF4D1))),
        const SizedBox(width: 12),
        Expanded(child: _WalletCard(label: 'Diamond', value: '500000', icon: Icons.diamond_rounded, color: const Color(0xFFF3E5F5))),
      ],
    );
  }
}

class _WalletCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color color;

  const _WalletCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.hardEdge,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        image: DecorationImage(
          image: (label == 'Diamond')
              ? const AssetImage('assets/images/me/diamond_bg.png')
              : const AssetImage('assets/images/me/wallet_bg.png'),
          fit: BoxFit.cover,
          colorFilter: ColorFilter.mode(
            color.withValues(alpha: 0.2),
            BlendMode.darken,
          ),
        ),
      ),
      child: Stack(
        children: [
          // Left Watermark Image
          Positioned(
            right: -10,
            top: -10,
            bottom: -10,
            child: Opacity(
              opacity: 0.8, // Adjust opacity for watermark strength
              child: Image.asset(
                (label == 'Diamond')
                    ? 'assets/images/me/diamond_mark.png' // Or your watermark asset path
                    : 'assets/images/me/coin_mark.png',
                fit: BoxFit.contain,
              ),
            ),
          ),

          // Main Content
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
                            stops: [
                              0.0,
                              0.2869,
                              1.0,
                            ],
                          ).createShader(Rect.fromLTWH(0, 0, bounds.width, bounds.height));
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
                (label == 'Diamond') ? Image.asset('assets/images/me/diamond.png', width: 53,) : Image.asset('assets/images/me/wallet.png', width: 83,),
                const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: Colors.grey),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _VIPBanner extends StatelessWidget {
  const _VIPBanner();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      clipBehavior: Clip.hardEdge, // Clips any watermark overflow neatly inside the rounded corners
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        image: const DecorationImage(
          image: AssetImage('assets/images/me/vip_banner.png'),
          fit: BoxFit.cover,
        ),
      ),
      child: Stack(
        children: [
          // --- Watermark Element ---
          Positioned(
              right: -10,
              bottom: -15,
              child: Image.asset('assets/images/me/vip_mark.png', width: 100, height: 100)
          ),

          // --- Main Content ---
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            child: Row(
              children: [
                Image.asset('assets/images/me/vip_diamond.png', width: 80,),
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
                      stops: [
                        0.1401,
                        0.5248,
                        0.8525,
                        0.9297,
                      ],
                    ).createShader(Rect.fromLTWH(0, 0, bounds.width, bounds.height));
                  },
                  child: const Text(
                    'VIP / SVIP',
                    style: TextStyle(
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
                const Icon(Icons.arrow_forward_ios_rounded, size: 18, color: Colors.grey),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
class _QuickActionsRow extends StatelessWidget {
  const _QuickActionsRow();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        image: const DecorationImage(
          image: AssetImage('assets/images/me/vip_banner.png'), // Path to your background image
          fit: BoxFit.cover,
        ),
      ),
      child: const Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          _QuickActionItem(image: 'assets/images/me/task.png', label: 'Task'),
          _QuickActionItem(image: 'assets/images/me/store.png', label: 'Store'),
          _QuickActionItem(image: 'assets/images/me/bag.png', label: 'Backpack'),
          _QuickActionItem(image: 'assets/images/me/level.png', label: 'My level'),
        ],
      ),
    );
  }
}

class _QuickActionItem extends StatelessWidget {
  final String image;
  final String label;
  const _QuickActionItem({required this.image, required this.label});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Image.asset(image, width: 45,),
        const SizedBox(height: 4),
        Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
      ],
    );
  }
}

class _SettingsList extends StatelessWidget {
  const _SettingsList();

  @override
  Widget build(BuildContext context) {
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
          _SettingTile(image: 'assets/images/me/cp_center.png', label: 'CP Center', color: Colors.red.shade300),
          _SettingTile(image: 'assets/images/me/agency_center.png', label: 'Agency Center', color: Colors.blue.shade300),
          _SettingTile(image: 'assets/images/me/host_center.png', label: 'Host Center', color: Colors.purple.shade300),
          _SettingTile(image: 'assets/images/me/customer_support.png', label: 'Customer support', color: Colors.orange.shade300),
          _SettingTile(image: 'assets/images/me/verify.png', label: 'Verify', color: Colors.teal.shade300),
          _SettingTile(image: 'assets/images/me/family.png', label: 'Family', color: Colors.pink.shade300),
          _SettingTile(image: 'assets/images/me/invite_friends.png', label: 'Invite friends', color: Colors.indigo.shade300),
          _SettingTile(image: 'assets/images/me/settings.png', label: 'Setting', color: Colors.blueGrey.shade300),
        ],
      ),
    );
  }
}

class _SettingTile extends StatelessWidget {
  final String image;
  final String label;
  final Color color;
  final VoidCallback? onTap;

  const _SettingTile({
    required this.image,
    required this.label,
    required this.color,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      // Adjust edge values to fit your layout needs
      padding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 4.0),
      child: Material(
        color: Colors.transparent,
        child: ListTile(
          tileColor: Colors.transparent,
          contentPadding: const EdgeInsets.symmetric(horizontal: 8.0), // Adjust internal spacing
          leading: Container(
            child: Image.asset(image, width: 40,),
          ),
          title: Text(
            label,
            style: const TextStyle(
              fontWeight: FontWeight.w600,
              fontSize: 16,
            ),
          ),
          trailing: const Icon(
            Icons.arrow_forward_ios_rounded,
            size: 16,
          ),
          onTap: onTap ?? () {},
        ),
      ),
    );
  }
}
