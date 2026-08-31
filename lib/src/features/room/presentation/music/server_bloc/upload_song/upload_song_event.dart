part of 'upload_song_bloc.dart';

abstract class BaseUploadSongEvent extends Equatable {
  const BaseUploadSongEvent();

  @override
  List<Object?> get props => [];
}

class GetPreSignedUrlEvent extends BaseUploadSongEvent {
  final UploadSongParam param;
  final BuildContext context;

  const GetPreSignedUrlEvent(this.context, this.param);

  @override
  List<Object?> get props => [param];
}

class UploadFileToStorageEvent extends BaseUploadSongEvent {
  final BuildContext context;

  final UploadSongParam param;

  const UploadFileToStorageEvent(this.context, this.param);

  @override
  List<Object?> get props => [param, context];
}

class NotifyBackendEvent extends BaseUploadSongEvent {
  final BuildContext context;

  const NotifyBackendEvent(
    this.context,
  );

  @override
  List<Object?> get props => [context];
}

class GetMusicEvent extends BaseUploadSongEvent {
  // final BuildContext context;

  const GetMusicEvent(//   this.context,
      );

  @override
  List<Object?> get props => [];
}

class GetMyMusicEvent extends BaseUploadSongEvent {
  // final BuildContext context;

  const GetMyMusicEvent(//   this.context,
      );

  @override
  List<Object?> get props => [];
}

class PickSongEvent extends BaseUploadSongEvent {
  final BuildContext context;

  const PickSongEvent(
    this.context,
  );

  @override
  List<Object?> get props => [
        context,
      ];
}

class FavoriteSongEvent extends BaseUploadSongEvent {
  final int id;

  const FavoriteSongEvent({
    required this.id,
  });

  @override
  List<Object?> get props => [
        id,
      ];
}
