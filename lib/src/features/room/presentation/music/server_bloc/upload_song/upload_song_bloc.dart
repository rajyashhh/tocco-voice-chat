import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/room/domain/entities/music_url.dart';
import 'package:general/src/features/room/domain/use_case/get_music_uc.dart';
import 'package:general/src/features/room/domain/use_case/get_my_music_uc.dart';
import 'package:general/src/features/room/domain/use_case/upload_song_use_case.dart';
import 'package:path_provider/path_provider.dart';
import 'package:flutter_media_metadata/flutter_media_metadata.dart';

part 'upload_song_event.dart';

part 'upload_song_state.dart';

class UploadSongBloc extends Bloc<BaseUploadSongEvent, UploadSongState> {
  final GetPreSignedUrlSongUseCase getPreSignedUrlUseCase;
  final UploadFileSongToStorageUseCase uploadFileToStorageUseCase;
  final NotifyBackendSongUseCase notifyBackendUseCase;
  final GetMyMusicUc getMyMusicUc;
  final GetMusicUc getMusicUc;

  UploadSongBloc(this.getPreSignedUrlUseCase, this.uploadFileToStorageUseCase,
      this.notifyBackendUseCase, this.getMusicUc, this.getMyMusicUc)
      : super(const UploadSongState()) {
    on<GetPreSignedUrlEvent>((event, emit) async {
      emit(state.copyWith(requestState: RequestState.loading));

      final result = await getPreSignedUrlUseCase(event.param.song!);
      result.fold(
        (failure) => emit(
          state.copyWith(
            requestState: handleErrorResponse(failure),
            message: NetworkExceptions.getErrorMessage(failure),
          ),
        ),
        (success) {
          emit(
            state.copyWith(
              backendName: success['name'],
              preSignedUrl: success['upload_url'],
            ),
          );
          add(
            UploadFileToStorageEvent(
              event.context,
              UploadSongParam(
                  backendName: success['name'],
                  preSignedUrl: success['upload_url'],
                  song: event.param.song),
            ),
          );
        },
      );
    });
    on<UploadFileToStorageEvent>((event, emit) async {
      final result = await uploadFileToStorageUseCase(event.param);
      result.fold(
        (failure) => emit(
          state.copyWith(
            requestState: handleErrorResponse(failure),
            message: NetworkExceptions.getErrorMessage(failure),
          ),
        ),
        (success) {
          add(NotifyBackendEvent(
            event.context,
          ));
        },
      );
    });
    on<NotifyBackendEvent>((event, emit) async {
      final result = await notifyBackendUseCase(UploadSongParam(
          description: state.descreption,
          backendName: state.backendName,
          songImage: state.songImage,
          songName: state.songName));
      result.fold(
        (failure) => emit(
          state.copyWith(
            requestState: handleErrorResponse(failure),
            message: NetworkExceptions.getErrorMessage(failure),
          ),
        ),
        (success) {
          emit(
            state.copyWith(
              requestState: RequestState.loaded,
            ),
          );
          add(const GetMyMusicEvent());
        },
      );
    });
    on<GetMusicEvent>((event, emit) async {
      final result = await getMusicUc();
      result.fold(
        (failure) => emit(
          state.copyWith(
            musicListRequestState: handleErrorResponse(failure),
            message: NetworkExceptions.getErrorMessage(failure),
          ),
        ),
        (success) {
          emit(
            state.copyWith(
                musicListRequestState: RequestState.loaded,
                musicListData: success.data),
          );
        },
      );
    });
    on<GetMyMusicEvent>((event, emit) async {
      final result = await getMyMusicUc();
      result.fold(
        (failure) => emit(
          state.copyWith(
            myMusicListRequestState: handleErrorResponse(failure),
            message: NetworkExceptions.getErrorMessage(failure),
          ),
        ),
        (success) {
          final List<int> likedIds = List<int>.from(
            HiveManager().getData<List<dynamic>>(
                    KeysManager.MUSIC_BOX, KeysManager.LIKED_SONG_IDS_KEY,
                    defaultValue: []) ??
                [],
          );

          final List<MusicUrlEntity> updatedSongs = success.data!.map((song) {
            final bool isLiked = likedIds.contains(song.id);
            return song.copyWith(isLiked: isLiked);
          }).toList();

          final List<MusicUrlEntity> likedSongs =
              updatedSongs.where((song) => song.isLiked).toList();

          emit(
            state.copyWith(
                myMusicListRequestState: RequestState.loaded,
                myMusicListData: updatedSongs,
                favoriteListData: likedSongs),
          );
        },
      );
    });
    on<PickSongEvent>((event, emit) async {
      final FilePickerResult? result = await FilePicker.pickFiles(
        type: FileType.audio,
        allowMultiple: false,
      );

      if (result == null || result.files.isEmpty) {
        return;
      }

      final PlatformFile pickedFile = result.files.first;
      final File videoFile = File(pickedFile.path!);
      final metadata = await MetadataRetriever.fromFile(videoFile);
      final Uint8List? albumArt = metadata.albumArt;
      String songImage = '';
      if (albumArt != null) {
        final tempDir = await getTemporaryDirectory();
        songImage = tempDir.path;
      }

      // Keep the real song title: the uploaded object gets a random storage
      // name, so the backend can only show what we send it here.
      String songName = (metadata.trackName ?? '').trim();
      if (songName.isEmpty) {
        songName = pickedFile.name;
        final dot = songName.lastIndexOf('.');
        if (dot > 0) songName = songName.substring(0, dot);
      }

      add(
        GetPreSignedUrlEvent(event.context, UploadSongParam(song: videoFile)),
      );

      emit(state.copyWith(
          reelVideo: videoFile, songImage: songImage, songName: songName));
    });
    on<FavoriteSongEvent>((event, emit) async {
      final List<MusicUrlEntity> songs = List.of(state.myMusicListData);

      final int index = songs.indexWhere((element) => element.id == event.id);
      if (index != -1) {
        final updatedSong =
            songs[index].copyWith(isLiked: !songs[index].isLiked);

        songs[index] = updatedSong;

        final List<int> likedIds = List<int>.from(
          HiveManager().getData<List<dynamic>>(
                  KeysManager.MUSIC_BOX, KeysManager.LIKED_SONG_IDS_KEY,
                  defaultValue: []) ??
              [],
        );

        if (updatedSong.isLiked) {
          if (!likedIds.contains(updatedSong.id)) {
            likedIds.add(updatedSong.id);
          }
        } else {
          likedIds.remove(updatedSong.id);
        }

        await HiveManager().saveData<List<int>>(
          KeysManager.MUSIC_BOX,
          KeysManager.LIKED_SONG_IDS_KEY,
          likedIds,
        );

        final List<MusicUrlEntity> likedSongs =
            songs.where((song) => song.isLiked).toList();
        emit(state.copyWith(
            myMusicListData: songs, favoriteListData: likedSongs));
      }
    });
  }
}
