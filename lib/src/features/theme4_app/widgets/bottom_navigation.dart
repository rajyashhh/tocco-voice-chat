import 'package:flutter/material.dart';

import '../app/theme.dart';

/// Describes a single bottom-nav destination.
class NavItem {
  final IconData icon;
  final IconData activeIcon;
  final String label;
  const NavItem(this.icon, this.activeIcon, this.label);
}

/// A custom, animated bottom navigation bar.
///
/// The selected tab gains a gradient pill and an animated scale — it does not
/// use the stock [BottomNavigationBar] visuals so the brand styling is exact,
/// but it behaves identically (tap an item → [onTap] with its index).
class AppBottomNavigation extends StatelessWidget {
  final int currentIndex;
  final ValueChanged<int> onTap;

  const AppBottomNavigation({
    super.key,
    required this.currentIndex,
    required this.onTap,
  });

  static const List<NavItem> items = [
    NavItem(Icons.home_outlined, Icons.home_rounded, 'Home'),
    NavItem(Icons.auto_awesome_outlined, Icons.auto_awesome_rounded, 'Moment'),
    NavItem(Icons.chat_bubble_outline_rounded, Icons.chat_bubble_rounded, 'Messages'),
    NavItem(Icons.person_outline_rounded, Icons.person_rounded, 'Me'),
  ];

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).padding.bottom;
    return Container(
      padding: EdgeInsets.fromLTRB(8, 8, 8, bottomInset),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            Color.fromRGBO(145, 116, 213, 1),
            Color.fromRGBO(159, 131, 215, 0.97)
          ]
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.purple.withValues(alpha: 0.10),
            blurRadius: 24,
            offset: const Offset(0, -6),
          ),
        ],
      ),
      child: Row(
        children: [
          for (int i = 0; i < items.length; i++)
            Expanded(child: _NavButton(
              item: items[i],
              selected: i == currentIndex,
              onTap: () => onTap(i),
            )),
        ],
      ),
    );
  }
}

class _NavButton extends StatelessWidget {
  final NavItem item;
  final bool selected;
  final VoidCallback onTap;

  const _NavButton({required this.item, required this.selected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppRadii.pill),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 260),
        curve: Curves.easeOutCubic,
        padding: const EdgeInsets.symmetric(vertical: 0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            AnimatedScale(
              scale: selected ? 1.0 : 0.92,
              duration: const Duration(milliseconds: 260),
              curve: Curves.easeOutBack,
              child: SizedBox(
                height: 48,
                child: Center(
                  child: Image.asset(
                    'assets/images/home/${item.label.toLowerCase()}.webp',
                    fit: BoxFit.contain,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 2),
            AnimatedDefaultTextStyle(
              duration: const Duration(milliseconds: 200),
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: selected ? Color.fromRGBO(112, 18, 162, 1) : Color.fromRGBO(112, 18, 162, 0.5),
              ),
              child: Text(item.label),
            ),
          ],
        ),
      ),
    );
  }
}
