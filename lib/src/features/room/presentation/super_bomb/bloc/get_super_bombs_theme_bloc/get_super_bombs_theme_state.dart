part of 'get_super_bombs_theme_bloc.dart';

class GetSuperBombsThemeState extends Equatable {
  final RoomBoomThemeModel? themesData;
  final String themesMessage;
  final RequestState themesState;
  final Map<int, File> cachedLevelBoomFiles;
  final Map<int, File> cachedProgressAnimationFiles;
  final Map<int, File> cachedBackgroundFiles;

  const GetSuperBombsThemeState({
    this.themesData,
    this.themesMessage = '',
    this.themesState = RequestState.idle,
    this.cachedLevelBoomFiles = const {},
    this.cachedProgressAnimationFiles = const {},
    this.cachedBackgroundFiles = const {},
  });

  GetSuperBombsThemeState copyWith({
    RoomBoomThemeModel? themesData,
    String? themesMessage,
    RequestState? themesState,
    Map<int, File>? cachedLevelBoomFiles,
    Map<int, File>? cachedProgressAnimationFiles,
    Map<int, File>? cachedBackgroundFiles,
  }) {
    return GetSuperBombsThemeState(
      themesData: themesData ?? this.themesData,
      themesMessage: themesMessage ?? this.themesMessage,
      themesState: themesState ?? this.themesState,
      cachedLevelBoomFiles: cachedLevelBoomFiles ?? this.cachedLevelBoomFiles,
      cachedProgressAnimationFiles:
          cachedProgressAnimationFiles ?? this.cachedProgressAnimationFiles,
      cachedBackgroundFiles:
          cachedBackgroundFiles ?? this.cachedBackgroundFiles,
    );
  }

  /// Get the cached boom file for a given level (1-based)
  File? getCachedLevelBoomFile(int level) {
    return cachedLevelBoomFiles[level];
  }

  /// Get the cached progress animation file for a given percentage
  File? getCachedProgressAnimationFile(double progressValue) {
    if (cachedProgressAnimationFiles.isEmpty) return null;

    // Find the closest percentage breakpoint
    final breakpoints = cachedProgressAnimationFiles.keys.toList()..sort();
    final numValue = progressValue.clamp(0, 100);

    int closest = breakpoints.reduce(
        (a, b) => (numValue - a).abs() < (numValue - b).abs() ? a : b);

    return cachedProgressAnimationFiles[closest];
  }

  /// Get the cached background file for a given level (1-based)
  File? getCachedBackgroundFile(int level) {
    return cachedBackgroundFiles[level];
  }

  @override
  List<Object?> get props => [
        themesData,
        themesMessage,
        themesState,
        cachedLevelBoomFiles,
        cachedProgressAnimationFiles,
        cachedBackgroundFiles,
      ];
}
