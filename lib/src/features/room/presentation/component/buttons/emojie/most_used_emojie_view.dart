import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/cache/cache_alpha_widget.dart';
import 'package:general/src/core/widgets/cache/cache_vap_widget.dart';
import 'package:general/src/core/widgets/cache/cache_video_widget.dart';
import 'package:general/src/features/room/data/model/most_used_snapshots.dart';
import 'package:general/src/features/room/room.dart';

/// First tab of the in-room emoji sheet: the user's own most-used emojis,
/// sorted by use count (device-local, per account — see [MostUsedTracker]).
class MostUsedEmojieView extends StatefulWidget {
  final String userId;

  const MostUsedEmojieView({required this.userId, super.key});

  /// Shared send path: local preview + realtime broadcast + usage recording.
  /// Called from both this tab and the category grids so the counter is
  /// bumped exactly once per actual send.
  static void sendEmojie(BuildContext context, EmojiEntity emoji,
      String userId) {
    Navigator.pop(context);

    EmojieController().showingEmojie(
      userId: userId,
      emojieData: EmojieData(
        emojie: emoji.emoji,
        emojieId: emoji.id,
        length: emoji.tLength,
        type: emoji.type,
      ),
      timeEmojie: emoji.tLength,
    );

    sendRoomData(
      data: {
        "messageContent": {
          "message": "showEmojie",
          "id": emoji.id,
          "emoji": emoji.emoji,
          "t_length": emoji.tLength,
          "id_user": userId,
          "type": emoji.type,
        }
      },
    );

    MostUsedTracker.emoji.record(emoji.id, emoji.toMostUsedJson());
  }

  @override
  State<MostUsedEmojieView> createState() => _MostUsedEmojieViewState();
}

class _MostUsedEmojieViewState extends State<MostUsedEmojieView> {
  List<EmojiEntity> _emojis = const [];
  bool _loaded = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final items = await MostUsedTracker.emoji.topItems();
    if (!mounted) return;
    setState(() {
      _emojis = items.map(EmojiModel.fromJson).toList();
      _loaded = true;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (!_loaded) {
      return const Center(child: LoadingView(color: ColorManager.roomGold));
    }

    if (_emojis.isEmpty) {
      return Center(
        child: EmptyView(
          accentColor: ColorManager.roomGold,
          title: StringManager.mostUsed,
          subTitle: StringManager.mostUsedEmojiEmptyMsg,
          titleStyle: context.bodyMedium.colorExt(ColorManager.white),
          subTitleStyle: context.bodySmall.colorExt(ColorManager.white),
        ),
      );
    }

    return GridView.builder(
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 5,
        crossAxisSpacing: 10.0,
        mainAxisSpacing: 10.0,
      ),
      padding: context.paddingAll(0),
      itemCount: _emojis.length,
      itemBuilder: (context, index) {
        final emoji = _emojis[index];
        return InkWell(
          onTap: () =>
              MostUsedEmojieView.sendEmojie(context, emoji, widget.userId),
          child: Padding(
            padding: context.paddingAll(10),
            child: emoji.type == "svga"
                ? CacheSvgaWidget(url: EndPoints.getImage(emoji.emoji))
                : emoji.type == "vap"
                    ? CachedVapWidget(url: emoji.emoji)
                    : emoji.type == "alpha"
                        ? CacheAlphaWidget(url: emoji.emoji)
                        : emoji.type == "mp4"
                            ? CacheVideoWidget(videoUrl: emoji.emoji)
                            : ImageViewWidget(
                                url: emoji.emoji,
                                boxFit: BoxFit.contain,
                              ),
          ),
        );
      },
    );
  }
}