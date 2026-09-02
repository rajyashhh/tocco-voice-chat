import 'package:flutter/material.dart';

import '../../app/constants.dart';
import '../../app/theme.dart';
import '../../models/user.dart';
import '../../widgets/coming_soon.dart';
import '../../widgets/gradient_background.dart';

/// Home tab — search + ranking actions, a banner, and a vertical list of online users.
class OnlineScreen extends StatelessWidget {
  const OnlineScreen({super.key});

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
        child: Column(
          children: [
            _HomeTopBar(),
            Expanded(
              child: _HomeContent(),
            ),
          ],
        ),
      ),
    );
  }
}

class _HomeContent extends StatelessWidget {
  const _HomeContent();

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 110),
      children: [
        const _BannerPlaceholder(),
        const SizedBox(height: 20),
        ...AppData.users.map((user) => Padding(
          padding: const EdgeInsets.only(bottom:6),
          child: _UserCard(user: user),
        )),
      ],
    );
  }
}

class _HomeTopBar extends StatelessWidget {
  const _HomeTopBar();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 16, 8),
      child: Row(
        children: [
          const Text(
            'Online user',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.w800,
              color: Color(0xFF1E1B2E),
            ),
          ),
          const Spacer(),
          IconButton(
            icon: const Icon(Icons.search_rounded, size: 28, color: Colors.black87),
            onPressed: () => showComingSoon(context, 'Search'),
          ),
          // GestureDetector(
          //     onTap: () => showComingSoon(context, 'Sort'),
          //     child: Container(
          //       child: Image.asset('assets/images/home/trophy.webp'),
          //     )
          // ),
        ],
      ),
    );
  }
}

class _BannerPlaceholder extends StatelessWidget {
  const _BannerPlaceholder();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 120,
      width: double.infinity,
      decoration: BoxDecoration(
        color: const Color(0xFFD9D9D9),
        borderRadius: BorderRadius.circular(16),
      ),
      child: const Center(
        child: Icon(
          Icons.menu_rounded,
          size: 48,
          color: Colors.white,
        ),
      ),
    );
  }
}

class _UserCard extends StatelessWidget {
  final AppUser user;
  const _UserCard({required this.user});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Color.fromRGBO(110, 66, 186, 0.51),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Image.asset(
              'assets/images/home/profile_dp.webp',
              height: 75,
              fit: BoxFit.cover,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  user.name,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                  ),
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    _Badge(
                      color: const Color(0xFFFF8EC7),
                      child: Row(
                        children: [
                          const Icon(Icons.female, size: 12, color: Colors.white),
                          const SizedBox(width: 2),
                          Text(
                            '${user.age}',
                            style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 6),
                    Container(
                      child: Image.asset('assets/images/home/level_tag.webp'),
                    )
                  ],
                ),
                const SizedBox(height: 4),
                const Text(
                  '3.2km',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.black54,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          _HiButton(onTap: () => showComingSoon(context, 'Say Hi to ${user.name}')),
        ],
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  final Color color;
  final Widget child;
  const _Badge({required this.color, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(10),
      ),
      child: child,
    );
  }
}

class _HiButton extends StatelessWidget {
  final VoidCallback onTap;
  const _HiButton({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
          child: Image.asset('assets/images/home/hi_button.webp')
      ),
    );
  }
}