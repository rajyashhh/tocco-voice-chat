import 'package:general/src/features/room/domain/entities/emoji_entity.dart';

import '../../../../core/utils/methods.dart';

class EmojiModel extends EmojiEntity {
  const EmojiModel({
    required super.emoji,
    required super.id,
    required super.userId,
    required super.name,
    required super.pid,
    required super.sort,
    required super.tLength,
    required super.type,
  });

  factory EmojiModel.fromJson(Map<String, dynamic> json) {
    return EmojiModel(
      userId: parseValue<String>(json['user_id'], "0"),
      emoji: parseValue<String>(json["emoji"], ""),
      id: parseValue<int>(json["id"], 0),
      name: parseValue<String>(json["name"], ""),
      pid: parseValue<int>(json["pid"], 0),
      sort: parseValue<int>(json["sort"], 1),
      tLength: parseValue<int>(json["t_length"], 0),
      type: parseValue<String>(json["type"], ""),
    );
  }
}
