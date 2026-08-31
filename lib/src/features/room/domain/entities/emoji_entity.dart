import 'package:equatable/equatable.dart';

class EmojiEntity extends Equatable {
  final int id;
  final int pid;
  final String name;
  final String emoji;
  final int tLength;
  final int sort;
  final String userId;
  final String type;

  const EmojiEntity({
    required this.id,
    required this.pid,
    required this.name,
    required this.emoji,
    required this.tLength,
    required this.sort,
    required this.userId,
    required this.type,
  });

  @override
  List<Object?> get props => [
        id,
        pid,
        name,
        emoji,
        tLength,
        sort,
        userId,
        type,
      ];
}
