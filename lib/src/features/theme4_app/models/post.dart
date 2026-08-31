/// A "Moment" post — static feed content for the Moment screen.
class Post {
  final String author;
  final String avatar;
  final String timeAgo;
  final String text;
  final List<String> images;
  final int likes;
  final int comments;
  final int shares;

  const Post({
    required this.author,
    required this.avatar,
    required this.timeAgo,
    required this.text,
    required this.images,
    this.likes = 0,
    this.comments = 0,
    this.shares = 0,
  });
}
