import 'package:flutter/material.dart';

import 'moment_screen.dart';
import 'online_user.dart';

class MainMomentScreen extends StatefulWidget {
  const MainMomentScreen({super.key});

  @override
  State<MainMomentScreen> createState() => _MainMomentScreenState();
}

class _MainMomentScreenState extends State<MainMomentScreen> {
  int selectedIndex = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBodyBehindAppBar: true,

      appBar: AppBar(
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        automaticallyImplyLeading: false,

        title: Row(
          children: [
            GestureDetector(
              onTap: () {
                setState(() {
                  selectedIndex = 0;
                });
              },
              child: Text(
                'Moment',
                style: TextStyle(
                  color: selectedIndex == 0
                      ? Colors.black
                      : Colors.black38,
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),

            const SizedBox(width: 28),

            GestureDetector(
              onTap: () {
                setState(() {
                  selectedIndex = 1;
                });
              },
              child: Text(
                'Online',
                style: TextStyle(
                  color: selectedIndex == 1
                      ? Colors.black
                      : Colors.black38,
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
      ),

      body: selectedIndex == 0
          ? const MomentScreen()
          : const OnlineScreen(),
    );
  }
}

class _NavItem extends StatelessWidget {
  final String title;
  final bool isSelected;
  final VoidCallback onTap;

  const _NavItem({
    required this.title,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 18,
              fontWeight:
              isSelected ? FontWeight.w700 : FontWeight.w500,
              color: isSelected
                  ? Colors.white
                  : Colors.white.withOpacity(0.5),
            ),
          ),

          const SizedBox(height: 6),

          // Optional selected indicator
          if (isSelected)
            Container(
              height: 2,
              width: 30,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
        ],
      ),
    );
  }
}