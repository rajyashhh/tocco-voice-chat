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
    return const GradientBackground(
      gradient: LinearGradient(
        begin: Alignment.centerLeft,
        end: Alignment.centerRight,
        colors: [
          Color.fromRGBO(136, 35, 232, 0.5),
          Color.fromRGBO(139, 48, 243, 0.5),
          Color.fromRGBO(63, 60, 249, 0.5),
          Color.fromRGBO(12, 96, 235, 0.5)
        ],
      ),
      showBlobs: false,
      child: SafeArea(
        bottom: false,
        child: SingleChildScrollView(
          padding: EdgeInsets.fromLTRB(8, 0, 8, 120),
          child: Column(
            children: [
              _ProfileHeaderCard(),
              SizedBox(height: 16),
              _WalletDiamondRow(),
              SizedBox(height: 16),
              _VIPBanner(),
              SizedBox(height: 16),
              _QuickActionsRow(),
              SizedBox(height: 16),
              _SettingsList(),
            ],
          ),
        ),
      ),
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
                    const SizedBox(width: 8),
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
                    icon: const Icon(Icons.ios_share_rounded, size: 32, color: Colors.black54),
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
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(
        label,
        style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w900),
      ),
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
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                Color(0xFFBB0DF5),
                Color(0xFF250FE5),
              ],
              stops: [
                0.0, // -5.32% clamped to valid range
                1.0, // 98.64% rounded to full gradient range
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
  const _WalletCard({required this.label, required this.value, required this.icon, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(16),
      ),
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
                        0.0,    // -27.48% clamped to range
                        0.2869, // 28.69%
                        1.0,    // 109.71% clamped to range
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
                Text(value, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900)),
              ],
            ),
          ),
          Icon(icon, size: 32, color: Colors.orangeAccent),
          const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: Colors.grey),
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
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFFFFF0CA), Color(0xFFFFD1E8)],
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          const Icon(Icons.workspace_premium_rounded, size: 40, color: Colors.purple),
          const SizedBox(width: 16),
          ShaderMask(
            blendMode: BlendMode.srcIn,
            shaderCallback: (Rect bounds) {
              return const LinearGradient(
                begin: Alignment(-1.0, -0.05), // ~93 degrees direction
                end: Alignment(1.0, 0.05),
                colors: [
                  Color(0xFFEAAF0E),
                  Color(0xFFF91013),
                  Color(0xD62665EB), // rgba(38, 101, 235, 0.84) -> 84% opacity is 0xD6 alpha
                  Color(0xFF0B16F6),
                ],
                stops: [
                  0.1401, // 14.01%
                  0.5248, // 52.48%
                  0.8525, // 85.25%
                  0.9297, // 92.97%
                ],
              ).createShader(Rect.fromLTWH(0, 0, bounds.width, bounds.height));
            },
            child: const Text(
              'VIP / SVIP',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w900,
                color: Colors.white, // Changed to white for proper mask application
              ),
            ),
          ),
          const Spacer(),
          const Icon(Icons.arrow_forward_ios_rounded, size: 18, color: Colors.grey),
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
        color: const Color(0xFFFFF0E0).withValues(alpha: 0.5),
        borderRadius: BorderRadius.circular(16),
      ),
      child: const Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          _QuickActionItem(icon: Icons.assignment_rounded, label: 'Task'),
          _QuickActionItem(icon: Icons.storefront_rounded, label: 'Store'),
          _QuickActionItem(icon: Icons.backpack_rounded, label: 'Backpack'),
          _QuickActionItem(icon: Icons.military_tech_rounded, label: 'My level'),
        ],
      ),
    );
  }
}

class _QuickActionItem extends StatelessWidget {
  final IconData icon;
  final String label;
  const _QuickActionItem({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, size: 32, color: Colors.orange),
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
        color: Colors.white.withValues(alpha: 0.6),
        borderRadius: BorderRadius.circular(24),
      ),
      child: Column(
        children: [
          _SettingTile(icon: Icons.favorite_rounded, label: 'CP Center', color: Colors.red.shade300),
          _SettingTile(icon: Icons.people_rounded, label: 'Agency Center', color: Colors.blue.shade300),
          _SettingTile(icon: Icons.headset_mic_rounded, label: 'Host Center', color: Colors.purple.shade300),
          _SettingTile(icon: Icons.support_agent_rounded, label: 'Customer support', color: Colors.orange.shade300),
          _SettingTile(icon: Icons.verified_user_rounded, label: 'Verify', color: Colors.teal.shade300),
          _SettingTile(icon: Icons.family_restroom_rounded, label: 'Family', color: Colors.pink.shade300),
          _SettingTile(icon: Icons.person_add_rounded, label: 'Invite friends', color: Colors.indigo.shade300),
          _SettingTile(icon: Icons.settings_rounded, label: 'Setting', color: Colors.blueGrey.shade300),
        ],
      ),
    );
  }
}

class _SettingTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  const _SettingTile({required this.icon, required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.2),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Icon(icon, color: color, size: 24),
      ),
      title: Text(label, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16)),
      trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 16, color: Colors.grey),
      onTap: () {},
    );
  }
}
