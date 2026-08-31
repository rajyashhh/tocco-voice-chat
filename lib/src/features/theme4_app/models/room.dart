/// A voice / party room card on the Room screen.
class Room {
  final String title;
  final String cover;
  final String hostCountry;
  final String tag; // e.g. Party, Game, Music
  final int listeners;
  final bool isHot;

  const Room({
    required this.title,
    required this.cover,
    required this.tag,
    this.hostCountry = '🇮🇳',
    this.listeners = 0,
    this.isHot = false,
  });
}
