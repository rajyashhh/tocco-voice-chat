import 'package:flutter/material.dart';

import '../../app/constants.dart';
import '../../app/theme.dart';
import '../../models/user.dart';
import '../../widgets/coming_soon.dart';
import '../../widgets/gradient_background.dart';

/// Home tab — search + ranking actions, a banner, and a vertical list of online users.
import 'package:flutter/material.dart';

import '../../widgets/gradient_background.dart';

/// Room tab — matching the specific requested design.
class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _tab = 1; // "Party" selected by default in screenshot

  @override
  Widget build(BuildContext context) {
    return GradientBackground(
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
      child: Stack(
        children: [
          // The ListView content that can scroll "behind" the banner
          Positioned.fill(
            child: SafeArea(
              top: false,
              bottom: false,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 86, 16, 110), // Padding to start below the sticky banner
                children: [
                  const _BannerPlaceholder(),
                  const SizedBox(height: 12),
                  _FilterRow(),
                  const SizedBox(height: 8),
                  const _FeaturedGrid(),
                  const SizedBox(height: 8),
                  ...List.generate(5, (index) => const _HorizontalRoomCard()),
                ],
              ),
            ),
          ),

          // The Sticky Header layer
          _RoomTopBar(
            selectedTab: _tab,
            onTabChanged: (i) => setState(() => _tab = i),
          ),
        ],
      ),
    );
  }
}

class _RoomTopBar extends StatelessWidget {
  final int selectedTab;
  final ValueChanged<int> onTabChanged;

  const _RoomTopBar({required this.selectedTab, required this.onTabChanged});

  @override
  Widget build(BuildContext context) {
    return Stack(
      alignment: Alignment.topCenter,
      children: [
        Image.asset(
          'assets/images/room/room_upper_banner.webp',
          width: double.infinity,
          height: 100,
          fit: BoxFit.cover,
          alignment: Alignment.bottomCenter,
        ),
        SafeArea(
          bottom: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 10, 16, 10),
            child: Row(
              children: [
                _TopTab(label: 'Me', selected: selectedTab == 0, onTap: () => onTabChanged(0)),
                const SizedBox(width: 20),
                _TopTab(label: 'Party', selected: selectedTab == 1, onTap: () => onTabChanged(1)),
                const SizedBox(width: 20),
                _TopTab(label: 'Game', selected: selectedTab == 2, onTap: () => onTabChanged(2)),
                const Spacer(),
                Image.asset('assets/images/home/trophy.webp'),
                const SizedBox(width: 16),
                const Icon(Icons.search_rounded, size: 28, color: Colors.white),
                const SizedBox(width: 16),
                const Icon(Icons.public_rounded, size: 28, color: Colors.white),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _TopTab extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onTap;

  const _TopTab({required this.label, required this.selected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Text(
        label,
        style: TextStyle(
          fontSize: 20,
          fontWeight: selected ? FontWeight.w800 : FontWeight.w500,
          color: selected ? Colors.white : Colors.white70,
        ),
      ),
    );
  }
}

class _BannerPlaceholder extends StatelessWidget {
  const _BannerPlaceholder();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 100,
      width: double.infinity,
      decoration: BoxDecoration(
        color: const Color(0xFFD9D9D9),
        borderRadius: BorderRadius.circular(16),
      ),
      child: const Center(
        child: Icon(
          Icons.menu_rounded,
          size: 40,
          color: Colors.white,
        ),
      ),
    );
  }
}

class _FilterRow extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
          decoration: BoxDecoration(
            color: Color.fromRGBO(110, 66, 186, 0.51),
            borderRadius: BorderRadius.circular(20),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.1),
                blurRadius: 4,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: const Text(
            'All',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
          ),
        ),
        const SizedBox(width: 12),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          decoration: BoxDecoration(
            color: Color.fromRGBO(110, 66, 186, 0.51),
            borderRadius: BorderRadius.circular(20),
          ),
          child: const Text('🇮🇳', style: TextStyle(fontSize: 20)),
        ),
        const Spacer(),
        GestureDetector(
          child: Image.asset('assets/images/room/room_option.webp'),
        )
      ],
    );
  }
}

class _FeaturedGrid extends StatelessWidget {
  const _FeaturedGrid();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 250,
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Container(
              decoration: BoxDecoration(
                color: Color.fromRGBO(110, 66, 186, 0.51),
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            flex: 1,
            child: Column(
              children: [
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: Color.fromRGBO(110, 66, 186, 0.51),
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: Color.fromRGBO(110, 66, 186, 0.51),
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _HorizontalRoomCard extends StatelessWidget {
  const _HorizontalRoomCard();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 80,
      margin: const EdgeInsets.only(bottom:8),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Color.fromRGBO(110, 66, 186, 0.51),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: Image.asset(
              'assets/images/home/profile_dp.webp',
              width: 60,
              height: 60,
              fit: BoxFit.cover,
            ),
          ),
          const SizedBox(width: 12),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Text could go here if needed
              ],
            ),
          ),
        ],
      ),
    );
  }
}