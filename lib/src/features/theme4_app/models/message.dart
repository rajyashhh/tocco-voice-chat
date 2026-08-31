import 'package:flutter/material.dart';

/// A single conversation row on the Messages screen.
class Conversation {
  final String name;
  final String avatar; // asset path, or empty when [icon] is used instead
  final IconData? icon; // used by system / official / group pinned rows
  final List<Color>? iconGradient;
  final String lastMessage;
  final String time;
  final int unread;
  final bool isPinned;

  const Conversation({
    required this.name,
    this.avatar = '',
    this.icon,
    this.iconGradient,
    required this.lastMessage,
    required this.time,
    this.unread = 0,
    this.isPinned = false,
  });
}
