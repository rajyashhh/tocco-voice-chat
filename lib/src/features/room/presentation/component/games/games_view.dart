import 'package:general/src/features/games/domain/entities/game_entity.dart';
import 'package:general/src/features/games/games.dart';
import 'package:general/src/features/room/presentation/component/games/leader_cc_game_web_view_screen.dart';
import 'package:general/src/features/room/presentation/manager/open_game_manager/open_game_bloc.dart';
import 'package:general/src/features/room/presentation/manager/open_game_manager/open_game_event.dart';
import 'package:general/src/features/room/room.dart';
import 'game_view_item.dart';

class GamesView extends StatefulWidget {
  final String roomId;
  final bool? isFull;

  const GamesView({
    super.key,
    this.isFull = false,
    required this.roomId,
  });

  @override
  State<GamesView> createState() => _GamesViewState();
}

class _GamesViewState extends State<GamesView> {
  @override
  void initState() {
    if (!di<ExploreBloc>().state.innerReqStateGames.isLoaded) {
      di<ExploreBloc>().add(const FetchGamesEvent(type: 'inner'));
    }

    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 520.h,
      width: double.infinity,
      decoration: BoxDecoration(
        color: ColorManager.black,
        borderRadius: BorderRadius.only(
          topRight: Radius.circular(15.r),
          topLeft: Radius.circular(15.r),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          20.hBox,
          Padding(
            padding: context.paddingOnly(top: 8, start: 15, bottom: 15),
            child: TextWidget(
              StringManager.entertainment,
              style: context.bodyLarge.colorExt(ColorManager.white).w600,
            ),
          ),
          BlocBuilder<ExploreBloc, ExploreState>(
            bloc: di<ExploreBloc>(),
            buildWhen: (prev, curr) =>
                prev.innerReqStateGames != curr.innerReqStateGames ||
                prev.innerGames != curr.innerGames,
            builder: (context, state) {
              final List<GameDataEntity> dynamicGames = [
                ...state.innerGames?.miniGames ?? []
              ];

              final List<GameDataEntity> combinedGames = [...dynamicGames];

              switch (state.innerReqStateGames) {
                case RequestState.loading:
                  return const Center(
                    child: LoadingView(isLoadingCenter: true, color: ColorManager.roomGold),
                  );

                case RequestState.error:
                  return Expanded(
                    child: Column(
                      children: [
                        _buildGamesGrid(combinedGames, state),
                        ErrorView(
                          accentColor: ColorManager.roomGold,
                          onTap: () => di<ExploreBloc>().add(
                            const FetchGamesEvent(type: 'inner'),
                          ),
                        ),
                      ],
                    ),
                  );

                case RequestState.loaded:
                  return _buildGamesGrid(combinedGames, state);
                case RequestState.offline:
                case RequestState.empty:
                case RequestState.idle:
                case RequestState.ban_user:
                  return const SizedBox.shrink();
              }
            },
          ),
        ],
      ),
    );
  }

  void openGame({
    required String url,
    required int type,
    required int inRoom,
  }) {
    final sheetContext = SafeNavigator.context;
    if (sheetContext == null) return;
    showModalBottomSheet<void>(
      context: sheetContext,
      isDismissible: false,
      isScrollControlled: true,
      enableDrag: false,
      elevation: 0,
      barrierColor: ColorManager.transparent,
      backgroundColor: ColorManager.transparent,
      builder: (builderContext) {
        if (type == 3) {
          // Leader CC Game
          String uid = '${MyDataModel.getInstance().id}';
          String token = Methods.getUserToken();
          final lang = Methods.getLang();

          final langMap = {
            'en': 'en-US',
            'ar': 'ar-SA',
            'tr': 'tr-TR',
            'hi': 'hi-IN',
            'ur': 'ur-PK',
          };

          String langCode = langMap[lang] ?? 'en-US';
          String roomId = '${MyDataModel.getInstance().myRoomData?.id}';
          String gameURL =
              '$url&uid=$uid&token=$token&roomid=$roomId&lang=$langCode';
          final screenMode = inRoom == 2
              ? "hd"
              : inRoom == 1
                  ? "full"
                  : "half";

          return LeaderCCGameWebViewScreen(
            gameUrl: gameURL,
            screenMode: screenMode,
          );
        } else {
          return const SizedBox();
        }
      },
    );
  }

  Widget _buildGamesGrid(List<GameDataEntity> games, ExploreState state) {
    return Expanded(
      child: GridView.builder(
        shrinkWrap: true,
        padding: context.paddingSymmetric(horizontal: 10),
        itemCount: games.length,
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 5,
          childAspectRatio: 0.70,
        ),
        itemBuilder: (context, index) {
          final game = games[index];
          return InkWell(
            onTap: () {
              di<OpenGameBloc>().add(OpenGameEvent(id: game.id ?? -1));
              SafeNavigator.pop();

              ForegroundWidget.openGames.value = true;
              if (game.url?.isEmpty == true) return;

              openGame(
                url: game.url.toString(),
                type: game.type ?? 0,
                inRoom: game.inRoom ?? 0,
              );
            },
            child: GamesViewItem(
              img: (game.isStatic ?? false)
                  ? (game.image ?? '')
                  : EndPoints.getImage(game.image),
              title: game.name ?? '',
              isHot: game.isHot ?? 0,
              isAsset: game.isStatic ?? false,
            ),
          );
        },
      ),
    );
  }
}
