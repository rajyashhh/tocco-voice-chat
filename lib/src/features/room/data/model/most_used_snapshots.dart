import 'package:general/src/features/room/domain/entities/emoji_entity.dart';
import 'package:general/src/features/room/domain/entities/gifts_entity.dart';

/// JSON snapshots for the on-device "Most Used" store. Keys mirror
/// [EmojiModel.fromJson] / [GiftsModel.fromJson] so entries round-trip
/// through the models without a second parser.
extension EmojiMostUsedSnapshot on EmojiEntity {
  Map<String, dynamic> toMostUsedJson() => {
        'user_id': userId,
        'emoji': emoji,
        'id': id,
        'name': name,
        'pid': pid,
        'sort': sort,
        't_length': tLength,
        'type': type,
      };
}

extension GiftMostUsedSnapshot on GiftsEntity {
  Map<String, dynamic> toMostUsedJson() => {
        'id': id,
        'name': name,
        'type': type,
        'vip_level': vipLevel,
        'price': price,
        'img': img,
        'show_img': showImg,
        'show_img2': showImg2,
        'music_gift': musicGift,
        'expire': expiry,
        'quantity': quantity,
        'image_type': giftType,
      };
}