import 'package:flutter/material.dart';

import '../app/theme.dart';
import '../models/message.dart';

/// A single row on the Messages screen.
///
/// Renders either an asset avatar or a gradient icon badge (for the pinned
/// system / official / group rows), plus an unread count badge.
class MessageTile extends StatelessWidget {
  final Conversation conversation;
  final VoidCallback? onTap;

  const MessageTile({super.key, required this.conversation, this.onTap});

  @override
  Widget build(BuildContext context) {
    final c = conversation;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppRadii.md),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
        child: Row(
          children: [
            _Leading(conversation: c),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          c.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
                        ),
                      ),
                      Text(c.time, style: const TextStyle(fontSize: 12, color: AppColors.subtle)),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          c.lastMessage,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontSize: 13, color: AppColors.subtle),
                        ),
                      ),
                      if (c.unread > 0) _UnreadBadge(count: c.unread),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Leading extends StatelessWidget {
  final Conversation conversation;
  const _Leading({required this.conversation});

  @override
  Widget build(BuildContext context) {
    final c = conversation;
    if (c.icon != null) {
      return Container(
        width: 52,
        height: 52,
        decoration: BoxDecoration(
          gradient: LinearGradient(colors: c.iconGradient ?? [AppColors.purple, AppColors.pink]),
          borderRadius: BorderRadius.circular(AppRadii.md),
          boxShadow: AppShadows.card,
        ),
        child: Icon(c.icon, color: Colors.white, size: 26),
      );
    }
    return ClipRRect(
      borderRadius: BorderRadius.circular(AppRadii.md),
      child: Image.asset(c.avatar, width: 52, height: 52, fit: BoxFit.cover),
    );
  }
}

class _UnreadBadge extends StatelessWidget {
  final int count;
  const _UnreadBadge({required this.count});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(left: 8),
      constraints: const BoxConstraints(minWidth: 20),
      height: 20,
      padding: const EdgeInsets.symmetric(horizontal: 6),
      alignment: Alignment.center,
      decoration: BoxDecoration(
        gradient: AppColors.sunset,
        borderRadius: BorderRadius.circular(AppRadii.pill),
      ),
      child: Text(
        count > 99 ? '99+' : '$count',
        style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700),
      ),
    );
  }
}
