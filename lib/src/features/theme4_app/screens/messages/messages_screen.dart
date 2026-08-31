import 'package:flutter/material.dart';

import '../../app/constants.dart';
import '../../app/theme.dart';
import '../../models/message.dart';
import '../../widgets/coming_soon.dart';
import '../../widgets/gradient_background.dart';

/// Messages tab — matching the specific requested design.
class MessagesScreen extends StatelessWidget {
  const MessagesScreen({super.key});

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
            _MessagesTopBar(),
            Expanded(
              child: _MessagesContent(),
            ),
          ],
        ),
      ),
    );
  }
}

class _MessagesTopBar extends StatelessWidget {
  const _MessagesTopBar();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
      child: Stack(
        alignment: Alignment.center,
        children: [
          const Text(
            'Messages',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.w800,
              color: Color(0xFF1E1B2E),
            ),
          ),
          Align(
            alignment: Alignment.centerRight,
            child: IconButton(
              icon: const Icon(Icons.search_rounded, size: 30, color: Colors.white),
              onPressed: () => showComingSoon(context, 'Search Messages'),
            ),
          ),
        ],
      ),
    );
  }
}

class _MessagesContent extends StatelessWidget {
  const _MessagesContent();

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final cardWidth = screenWidth * 0.55; // Slightly more than half to allow for overlap slant

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 110),
      children: [
        SizedBox(
          height: 70,
          child: Stack(
            children: [
              Positioned(
                left: 0,
                top: 0,
                bottom: 0,
                width: 208,
                child: const _NotificationCard(
                  label: 'Official notification',
                  icon: Icons.notifications_rounded,
                  bgAsset: 'assets/images/messages/banner_left.webp',
                  icon_asset: 'assets/images/messages/official_notification.webp',
                ),
              ),
              Positioned(
                right: 0,
                top: 0,
                bottom: 0,
                width: 210,
                child: const _NotificationCard(
                  label: 'Global group chat',
                  icon: Icons.campaign_rounded,
                  bgAsset: 'assets/images/messages/banner_right.webp',
                  icon_asset: 'assets/images/messages/global_notification.webp',
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        const _NotificationCard(
          label: 'Friend request',
          icon: Icons.volume_up_rounded,
          bgColor: Color.fromRGBO(110, 66, 186, 0.51),
          isFullWidth: true,
          icon_asset: 'assets/images/messages/global_notification.webp',
        ),
        const SizedBox(height: 20),
        ...List.generate(
          AppData.conversations.length,
          (i) => _ConversationTile(conversation: AppData.conversations[i]),
        ),
      ],
    );
  }
}

class _NotificationCard extends StatelessWidget {
  final String label;
  final IconData icon;
  final String? bgAsset;
  final Color? bgColor;
  final bool isFullWidth;
  final double? width;
  final String icon_asset ;

  const _NotificationCard({
    required this.label,
    required this.icon,
    this.bgAsset,
    this.bgColor,
    this.isFullWidth = false,
    this.width,
    required this.icon_asset
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: 60,
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: isFullWidth ? BorderRadius.circular(12) : null,
        image: bgAsset != null
            ? DecorationImage(
                image: AssetImage(bgAsset!),
                fit: BoxFit.fill,
              )
            : null,
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          // if (label.toLowerCase() == 'official notification') const SizedBox(width: 20), // Offset for slant
          Container(
            child: (label != 'Friend request') ? Image.asset(icon_asset)
            : Icon(Icons.person, color: Colors.blue, size: 30,),
          ),
          const SizedBox(width: 4),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 14,
              fontWeight: FontWeight.bold,
            ),
          ),
          // if (label.toLowerCase() == 'system notification') const SizedBox(width: 20), // Offset for slant
        ],
      ),
    );
  }
}

class _ConversationTile extends StatelessWidget {
  final Conversation conversation;
  const _ConversationTile({required this.conversation});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(15),
            child: Image.asset(
              'assets/images/home/profile_dp.webp',
              width: 65,
              height: 65,
              fit: BoxFit.cover,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  conversation.name,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E1B2E),
                  ),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Nǐ hǎo ma',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.black54,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '10:24',
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.black.withValues(alpha: 0.4),
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: Colors.red,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Text(
                  '21',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
