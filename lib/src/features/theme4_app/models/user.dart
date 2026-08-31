/// A lightweight, immutable user model used across the demo.
///
/// This is pure UI data — there is no serialization, no backend, no identity.
class AppUser {
  final String name;
  final String userId;
  final String avatar;
  final String country;
  final int age;
  final bool isFemale;
  final bool isOnline;
  final String tagline;

  const AppUser({
    required this.name,
    required this.userId,
    required this.avatar,
    this.country = '🇮🇳',
    this.age = 22,
    this.isFemale = true,
    this.isOnline = true,
    this.tagline = 'Say hi 👋',
  });
}
