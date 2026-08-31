/// Central registry of every local asset path used by the demo.
///
/// All assets live under `assets/` and are bundled with the app — there are no
/// network images anywhere in this project.
class AppAssets {
  AppAssets._();

  static const String _img = 'assets/images';

  // Branding
  static const String logo = '$_img/logo.png';

  // Banners
  static const String banner1 = '$_img/banner1.png';
  static const String banner2 = '$_img/banner2.png';
  static const String featured = '$_img/featured.png';

  // Avatars (1..8)
  static List<String> avatars = List.generate(8, (i) => '$_img/avatar${i + 1}.png');
  static String avatar(int i) => avatars[i % avatars.length];

  // Moment posts (1..6)
  static List<String> posts = List.generate(6, (i) => '$_img/post${i + 1}.png');
  static String post(int i) => posts[i % posts.length];

  // Room covers (1..6)
  static List<String> rooms = List.generate(6, (i) => '$_img/room${i + 1}.png');
  static String room(int i) => rooms[i % rooms.length];
}
