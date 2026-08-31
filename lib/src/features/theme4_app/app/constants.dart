import 'package:flutter/material.dart';

import 'theme.dart';
import '../models/user.dart';
import '../models/post.dart';
import '../models/message.dart';
import '../models/room.dart';

/// All hardcoded dummy data for the demo lives here.
///
/// Nothing in this file talks to a backend — every list is a static, in-memory
/// constant used purely to populate the UI.
class AppData {
  AppData._();

  static const String appName = 'Tocco';
  static const String appTagline = 'Voice Chat & Party Rooms';

  // ---------------------------------------------------------------- Users ---
  static const List<AppUser> users = [
    AppUser(name: 'Aria', userId: '100231', avatar: 'assets/images/avatar1.png', country: '🇮🇳', age: 21, tagline: 'Loves singing 🎤'),
    AppUser(name: 'Mila', userId: '100232', avatar: 'assets/images/avatar2.png', country: '🇺🇸', age: 24, tagline: 'Gamer girl 🎮'),
    AppUser(name: 'Zara', userId: '100233', avatar: 'assets/images/avatar3.png', country: '🇦🇪', age: 23, tagline: 'Coffee & chats ☕'),
    AppUser(name: 'Leo', userId: '100234', avatar: 'assets/images/avatar4.png', country: '🇧🇷', age: 26, isFemale: false, tagline: 'Let\'s vibe 🎧'),
    AppUser(name: 'Noor', userId: '100235', avatar: 'assets/images/avatar5.png', country: '🇵🇰', age: 20, tagline: 'Poetry lover ✨'),
    AppUser(name: 'Sky', userId: '100236', avatar: 'assets/images/avatar6.png', country: '🇮🇩', age: 22, tagline: 'Dance all night 💃'),
    AppUser(name: 'Kai', userId: '100237', avatar: 'assets/images/avatar7.png', country: '🇯🇵', age: 25, isFemale: false, tagline: 'Anime & beats'),
    AppUser(name: 'Ivy', userId: '100238', avatar: 'assets/images/avatar8.png', country: '🇹🇷', age: 21, tagline: 'New here 🌸'),
  ];

  // ----------------------------------------------------------- Home banners -
  static const List<String> banners = [
    'assets/images/banner1.png',
    'assets/images/banner2.png',
    'assets/images/featured.png',
  ];

  static const List<HomeCategory> homeCategories = [
    HomeCategory('Popular', Icons.local_fire_department_rounded, [AppColors.rose, AppColors.pink]),
    HomeCategory('Nearby', Icons.near_me_rounded, [AppColors.blue, AppColors.cyan]),
    HomeCategory('New', Icons.auto_awesome_rounded, [AppColors.purple, AppColors.indigo]),
    HomeCategory('Games', Icons.sports_esports_rounded, [AppColors.indigo, AppColors.blue]),
    HomeCategory('Music', Icons.music_note_rounded, [AppColors.pink, AppColors.purple]),
  ];

  // ---------------------------------------------------------------- Posts ---
  static const List<Post> posts = [
    Post(
      author: 'Aria',
      avatar: 'assets/images/avatar1.png',
      timeAgo: '2m ago',
      text: 'Golden hour hits different today 🌇 Who\'s up for a karaoke room tonight?',
      images: ['assets/images/post1.png', 'assets/images/post2.png'],
      likes: 1243,
      comments: 89,
      shares: 12,
    ),
    Post(
      author: 'Kai',
      avatar: 'assets/images/avatar7.png',
      timeAgo: '18m ago',
      text: 'New beat dropping in the music room 🎧🔥 come through!',
      images: ['assets/images/post3.png'],
      likes: 832,
      comments: 41,
      shares: 7,
    ),
    Post(
      author: 'Sky',
      avatar: 'assets/images/avatar6.png',
      timeAgo: '1h ago',
      text: 'Dance challenge unlocked 💃 tag me in your moments!',
      images: ['assets/images/post4.png', 'assets/images/post5.png', 'assets/images/post6.png'],
      likes: 2891,
      comments: 203,
      shares: 56,
    ),
    Post(
      author: 'Noor',
      avatar: 'assets/images/avatar5.png',
      timeAgo: '3h ago',
      text: 'A little poetry for the soul ✨ "Stars can\'t shine without darkness."',
      images: ['assets/images/post2.png'],
      likes: 564,
      comments: 33,
      shares: 9,
    ),
  ];

  // ---------------------------------------------------------------- Rooms ---
  static const List<Room> rooms = [
    Room(title: 'Late Night Karaoke 🎤', cover: 'assets/images/room1.png', tag: 'Music', hostCountry: '🇮🇳', listeners: 1240, isHot: true),
    Room(title: 'Ludo Champions 🎲', cover: 'assets/images/room2.png', tag: 'Game', hostCountry: '🇺🇸', listeners: 860),
    Room(title: 'Chill & Chat ☕', cover: 'assets/images/room3.png', tag: 'Party', hostCountry: '🇦🇪', listeners: 432),
    Room(title: 'Beat Drop Lounge 🎧', cover: 'assets/images/room4.png', tag: 'Music', hostCountry: '🇧🇷', listeners: 1789, isHot: true),
    Room(title: 'Truth or Dare 😈', cover: 'assets/images/room5.png', tag: 'Party', hostCountry: '🇵🇰', listeners: 654),
    Room(title: 'Squad Up Gaming 🕹️', cover: 'assets/images/room6.png', tag: 'Game', hostCountry: '🇯🇵', listeners: 921),
  ];

  static const List<String> roomTabs = ['Party', 'Game', 'Music', 'New', 'Friends'];

  static const List<CountryFilter> countries = [
    CountryFilter('Global', '🌍'),
    CountryFilter('India', '🇮🇳'),
    CountryFilter('USA', '🇺🇸'),
    CountryFilter('UAE', '🇦🇪'),
    CountryFilter('Brazil', '🇧🇷'),
    CountryFilter('Japan', '🇯🇵'),
  ];

  // ------------------------------------------------------------ Messages ----
  static const List<Conversation> pinnedConversations = [
    Conversation(
      name: 'System Notification',
      icon: Icons.notifications_active_rounded,
      iconGradient: [AppColors.purple, AppColors.indigo],
      lastMessage: 'Your daily check-in reward is ready 🎁',
      time: '09:24',
      unread: 1,
      isPinned: true,
    ),
    Conversation(
      name: 'Official Notification',
      icon: Icons.verified_rounded,
      iconGradient: [AppColors.blue, AppColors.cyan],
      lastMessage: 'Welcome to Tocco! Tap to explore features.',
      time: 'Mon',
      isPinned: true,
    ),
    Conversation(
      name: 'Global Group Chat',
      icon: Icons.public_rounded,
      iconGradient: [AppColors.pink, AppColors.rose],
      lastMessage: 'Mila: anyone up for a party room? 🎉',
      time: '08:51',
      unread: 12,
      isPinned: true,
    ),
  ];

  static const List<Conversation> conversations = [
    Conversation(name: 'Aria', avatar: 'assets/images/avatar1.png', lastMessage: 'That karaoke room was so fun 😍', time: '10:32', unread: 2),
    Conversation(name: 'Kai', avatar: 'assets/images/avatar7.png', lastMessage: 'Sent you the new beat 🎧', time: '09:58'),
    Conversation(name: 'Zara', avatar: 'assets/images/avatar3.png', lastMessage: 'Coffee tomorrow? ☕', time: 'Yesterday', unread: 5),
    Conversation(name: 'Leo', avatar: 'assets/images/avatar4.png', lastMessage: 'gg! good game tonight', time: 'Yesterday'),
    Conversation(name: 'Noor', avatar: 'assets/images/avatar5.png', lastMessage: 'Loved your poem ✨', time: 'Tue'),
    Conversation(name: 'Sky', avatar: 'assets/images/avatar6.png', lastMessage: 'Dance challenge accepted 💃', time: 'Mon', unread: 1),
    Conversation(name: 'Ivy', avatar: 'assets/images/avatar8.png', lastMessage: 'Hi! New here, any tips? 🌸', time: 'Mon'),
  ];

  // ------------------------------------------------------------- Profile ----
  static const AppUser me = AppUser(
    name: 'Yashraj',
    userId: '888001',
    avatar: 'assets/images/avatar4.png',
    country: '🇮🇳',
    age: 23,
    isFemale: false,
    tagline: 'Building cool things ✨',
  );

  static const List<ProfileStat> profileStats = [
    ProfileStat('Visitors', '1.2k'),
    ProfileStat('Friends', '348'),
    ProfileStat('Followers', '5.6k'),
  ];

  static const List<ProfileWallet> wallet = [
    ProfileWallet('Wallet', '₹1,250', Icons.account_balance_wallet_rounded, [AppColors.purple, AppColors.indigo]),
    ProfileWallet('Diamonds', '8,420', Icons.diamond_rounded, [AppColors.blue, AppColors.cyan]),
    ProfileWallet('VIP', 'Gold', Icons.workspace_premium_rounded, [AppColors.rose, AppColors.pink]),
  ];

  static const List<ProfileOption> profileOptions = [
    ProfileOption('Tasks', Icons.checklist_rounded),
    ProfileOption('Store', Icons.storefront_rounded),
    ProfileOption('Backpack', Icons.backpack_rounded),
    ProfileOption('My Level', Icons.military_tech_rounded),
    ProfileOption('CP Center', Icons.favorite_rounded),
    ProfileOption('Agency Center', Icons.apartment_rounded),
    ProfileOption('Host Center', Icons.headset_mic_rounded),
    ProfileOption('Customer Support', Icons.support_agent_rounded),
    ProfileOption('Verify', Icons.verified_user_rounded),
    ProfileOption('Family', Icons.diversity_3_rounded),
    ProfileOption('Invite Friends', Icons.person_add_alt_1_rounded),
    ProfileOption('Settings', Icons.settings_rounded),
  ];
}

// --------------------------------------------------------- tiny value types -

class HomeCategory {
  final String label;
  final IconData icon;
  final List<Color> gradient;
  const HomeCategory(this.label, this.icon, this.gradient);
}

class CountryFilter {
  final String name;
  final String flag;
  const CountryFilter(this.name, this.flag);
}

class ProfileStat {
  final String label;
  final String value;
  const ProfileStat(this.label, this.value);
}

class ProfileWallet {
  final String label;
  final String value;
  final IconData icon;
  final List<Color> gradient;
  const ProfileWallet(this.label, this.value, this.icon, this.gradient);
}

class ProfileOption {
  final String label;
  final IconData icon;
  const ProfileOption(this.label, this.icon);
}
