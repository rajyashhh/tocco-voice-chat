import 'package:general/src/core/index.dart';

class CountryEntity extends Equatable {
  final int? id;
  final String? name;
  final String? photo;
  final String? lang;
  final String? phoneCode;
  final String? iso;
  final String? nameEn;
  final List<SupporterEntity>? supporters;  // ✅ new field

  /// Number of active rooms in this country (server-computed) — drives the
  /// home country bar ordering.
  final int? totalRooms;

  const CountryEntity({
    this.id,
    this.name,
    this.photo,
    this.lang,
    this.phoneCode,
    this.iso,
    this.nameEn,
    this.supporters,
    this.totalRooms,
  });

  @override
  List<Object?> get props => [
    id,
    name,
    photo,
    lang,
    phoneCode,
    iso,
    nameEn,
    supporters,
    totalRooms,
  ];
}


class SupporterEntity extends Equatable {
  final int? id;
  final String? uuid;
  final String? name;
  final String? avatar;
  final int? total;

  const SupporterEntity({
    this.id,
    this.uuid,
    this.name,
    this.avatar,
    this.total,
  });

  @override
  List<Object?> get props => [id, uuid, name, avatar, total];
}