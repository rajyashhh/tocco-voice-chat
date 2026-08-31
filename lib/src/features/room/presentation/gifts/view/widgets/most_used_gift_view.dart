import 'package:general/src/core/index.dart';
import 'package:general/src/features/room/presentation/gifts/gift.dart';
import 'package:general/src/features/room/room.dart';

/// First tab of the gift box: the user's own most-sent gifts, sorted by use
/// count (device-local, per account — see [MostUsedTracker]). Reuses the
/// shared gift grid so selection/download/animation behave exactly like the
/// category tabs; each hosting design passes its own palette.
class MostUsedGiftView extends StatefulWidget {
  final MyDataModel myDataModel;

  /// Mirror of the hosting sheet's category filter: the audio sheet hides
  /// lucky gifts outside audio rooms, so the Most Used tab must too.
  final bool includeLucky;
  final double gridPaddingTop;
  final Color? cardBackgroundColor;
  final Color selectedBorderColor;
  final Color normalPriceColor;
  final Color selectedPriceColor;
  final bool enableAnimation;
  final bool showDownloadProgress;
  final bool showMusicIcon;

  const MostUsedGiftView({
    required this.myDataModel,
    required this.includeLucky,
    required this.gridPaddingTop,
    required this.cardBackgroundColor,
    required this.selectedBorderColor,
    required this.normalPriceColor,
    required this.selectedPriceColor,
    required this.enableAnimation,
    required this.showDownloadProgress,
    required this.showMusicIcon,
    super.key,
  });

  @override
  State<MostUsedGiftView> createState() => _MostUsedGiftViewState();
}

class _MostUsedGiftViewState extends State<MostUsedGiftView> {
  List<GiftsEntity> _gifts = const [];
  bool _loaded = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final items = await MostUsedTracker.gift.topItems();
    if (!mounted) return;
    var gifts = items.map(GiftsModel.fromJson).cast<GiftsEntity>().toList();
    if (!widget.includeLucky) {
      gifts = gifts.where((g) => g.type != 'lucky_gift').toList();
    }
    setState(() {
      _gifts = gifts;
      _loaded = true;
    });

    // Match the category tabs' behavior: auto-select the first gift when the
    // tab shows and nothing else is selected yet.
    if (gifts.isNotEmpty && GiftScreen.chosenGift == null) {
      final first = gifts.first;
      GiftScreen.chosenGift = first;
      di<GiftBloc>().add(
        ChangeGiftDataEvent(
          numOfGift: 0,
          giftId: first.id,
          giftPrice: first.price,
          categoryId: MostUsedTracker.giftCategoryId,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_loaded) {
      return const Center(child: LoadingView(color: ColorManager.roomGold));
    }

    if (_gifts.isEmpty) {
      return Center(
        child: EmptyView(
          accentColor: ColorManager.roomGold,
          title: StringManager.mostUsed,
          subTitle: StringManager.mostUsedGiftEmptyMsg,
          titleStyle: context.bodyMedium.colorExt(ColorManager.white),
          subTitleStyle: context.bodySmall.colorExt(ColorManager.white),
        ),
      );
    }

    return PageViewGiftWidget(
      giftType: TypeGift.normal,
      data: _gifts,
      state: RequestState.loaded,
      message: '',
      userCoins: widget.myDataModel.myStore?.coins.toString() ?? '',
      myData: widget.myDataModel,
      categoryId: MostUsedTracker.giftCategoryId,
      gridPaddingTop: widget.gridPaddingTop,
      cardBackgroundColor: widget.cardBackgroundColor,
      selectedBorderColor: widget.selectedBorderColor,
      normalPriceColor: widget.normalPriceColor,
      selectedPriceColor: widget.selectedPriceColor,
      enableAnimation: widget.enableAnimation,
      showDownloadProgress: widget.showDownloadProgress,
      showMusicIcon: widget.showMusicIcon,
    );
  }
}