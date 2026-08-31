import 'package:flutter/material.dart';

import '../../app/constants.dart';
import '../../app/theme.dart';
import '../../widgets/coming_soon.dart';
import '../../widgets/gradient_background.dart';
import '../../widgets/moment_card.dart';

/// Moment tab — a social feed with "Recommend" / "Following" segments, a camera
/// action and a builder-driven list of [MomentCard]s.
class MomentScreen extends StatefulWidget {
  const MomentScreen({super.key});

  @override
  State<MomentScreen> createState() => _MomentScreenState();
}

class _MomentScreenState extends State<MomentScreen> {
  int _tab = 0; // 0 = Recommend, 1 = Following

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
      child: SafeArea(
        bottom: false,
        child: Column(
          children: [
            _MomentTopBar(
              tab: _tab,
              onTab: (i) => setState(() => _tab = i),
              onCamera: () => showComingSoon(context, 'Camera'),
            ),
            Expanded(
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 300),
                child: _tab == 0
                    ? const _Feed(key: ValueKey('recommend'))
                    : const _EmptyFollowing(key: ValueKey('following')),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MomentTopBar extends StatelessWidget {
  final int tab;
  final ValueChanged<int> onTab;
  final VoidCallback onCamera;

  const _MomentTopBar({required this.tab, required this.onTab, required this.onCamera});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 10, 16, 10),
      child: Row(
        children: [
          _Segment(label: 'Recommend', selected: tab == 0, onTap: () => onTab(0)),
          const SizedBox(width: 24),
          _Segment(label: 'Following', selected: tab == 1, onTap: () => onTab(1)),
          const Spacer(),
          IconButton(
            onPressed: onCamera,
            icon: const Icon(Icons.photo_camera_rounded, size: 28, color: Color(0xFF1E1B2E)),
          ),
        ],
      ),
    );
  }
}

class _Segment extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onTap;
  const _Segment({required this.label, required this.selected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: selected ? const Color(0xFF1E1B2E) : Color.fromRGBO(28, 13, 36, 0.5),
            ),
          ),
        ],
      ),
    );
  }
}

class _Feed extends StatelessWidget {
  const _Feed({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(0, 10, 0, 110),
      itemCount: AppData.posts.length,
      separatorBuilder: (_, __) => Divider(color: Colors.black.withValues(alpha: 0.05), height: 1),
      itemBuilder: (_, i) => MomentCard(post: AppData.posts[i]),
    );
  }
}

class _EmptyFollowing extends StatelessWidget {
  const _EmptyFollowing({super.key});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 88,
            height: 88,
            decoration: BoxDecoration(
              color: AppColors.purple.withValues(alpha: 0.08),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.group_add_rounded, size: 40, color: AppColors.purple),
          ),
          const SizedBox(height: 16),
          const Text('No moments yet', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
          const SizedBox(height: 6),
          const Text('Follow people to see their moments here',
              style: TextStyle(fontSize: 13, color: AppColors.subtle)),
        ],
      ),
    );
  }
}
