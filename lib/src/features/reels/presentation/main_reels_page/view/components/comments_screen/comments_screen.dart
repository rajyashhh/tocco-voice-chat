import 'package:general/src/features/reels/domain/entities/reel_entity.dart';
import 'package:general/src/features/reels/presentation/main_reels_page/bloc/get_comments/get_comments_bloc.dart';
import 'package:general/src/features/reels/presentation/main_reels_page/bloc/get_reels/get_reels_bloc.dart';
import 'package:general/src/features/reels/presentation/main_reels_page/bloc/make_comments/make_comments_bloc.dart';
import 'package:general/src/features/reels/presentation/main_reels_page/view/components/comments_screen/widgets/comment_item.dart';
import 'package:loading_animation_widget/loading_animation_widget.dart';

import '../../../../../../../core/index.dart';

class CommentsScreen extends StatefulWidget {
  final String reelId;
  final ReelsType filter;
  final ReelsEntity currentReel;
  final GetReelsBloc getReelsBloc;

  const CommentsScreen({
    required this.reelId,
    required this.currentReel,
    required this.filter,
    required this.getReelsBloc,
    super.key,
  });

  @override
  State<CommentsScreen> createState() => _CommentsScreenState();
}

class _CommentsScreenState extends State<CommentsScreen> {
  late TextEditingController textController;

  final ScrollController _emojiScrollController = ScrollController();
  final List<String> emojis = ["😁", "😍", "😂", "😳", "😏", "😅", "🥺", "😌"];

  void _addEmoji(String emoji) {
    textController.text = textController.text + emoji;
  }

  /// Resolves the live comment count for this reel from the feed by id so the
  /// optimistic +1 (applied across feeds on post) shows immediately, instead of
  /// the captured [CommentsScreen.currentReel] snapshot. Falls back to the
  /// snapshot when the reel isn't present in any feed list (e.g. deep link).
  int _resolveCommentCount(GetReelsState state) {
    final id = int.tryParse(widget.reelId);
    if (id != null) {
      for (final list in [
        state.reelsList,
        state.followingReelsList,
        state.myReelsList,
      ]) {
        for (final reel in list) {
          if (reel.id == id) return reel.commentCount ?? 0;
        }
      }
    }
    return widget.currentReel.commentCount ?? 0;
  }

  /// True while there are older comment pages still to load, so the list shows
  /// a bottom pagination spinner. The scroll listener (GetCommentsBloc) fetches
  /// the next page automatically as the user reaches the end.
  bool _hasMoreComments(GetCommentsState state) =>
      state.commentsList.isNotEmpty &&
      state.lastPage != -1 &&
      state.currentPage < state.lastPage;

  @override
  void initState() {
    textController = TextEditingController();
    di<GetCommentsBloc>().add(GetCommentsEvent(
        param: ReelParam(reelId: widget.reelId), isLoading: true));
    di<GetCommentsBloc>().add(AddListenerCommentEvent(reelId: widget.reelId));

    super.initState();
  }

  @override
  void dispose() {
    textController.dispose();
    _emojiScrollController.dispose();
    di<GetCommentsBloc>()
        .add(RemoveListenerCommentEvent(reelId: widget.reelId));

    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    double bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return BlocBuilder<GetCommentsBloc, GetCommentsState>(
      bloc: di<GetCommentsBloc>(),
      buildWhen: (prev, curr) =>
          prev.requestState != curr.requestState ||
          prev.scrollControllerComments != curr.scrollControllerComments ||
          prev.commentsList != curr.commentsList ||
          prev.currentPage != curr.currentPage ||
          prev.lastPage != curr.lastPage,
      builder: (context, state) {
        return AnimatedPadding(
          duration: const Duration(milliseconds: 200),
          padding: EdgeInsets.only(bottom: bottomInset),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 0, vertical: 5),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    50.wBox,
                    BlocSelector<GetReelsBloc, GetReelsState, int>(
                      bloc: widget.getReelsBloc,
                      selector: (state) => _resolveCommentCount(state),
                      builder: (context, count) => TextWidget(
                        "$count ${StringManager.comments.tr()}",
                        // Per-theme text getter: the sheet surface is the
                        // light [surfaceCardColor], so the now-fixed-white
                        // theme2 token would be invisible on it under every
                        // variant (it used to alias [textPrimary]).
                        style: context.bodyMedium.bold
                            .size(14)
                            .colorExt(ColorManager.textPrimary),
                      ),
                    ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        IconButton(
                          onPressed: () {
                            Navigator.pop(context);
                          },
                          icon: Icon(
                            Icons.close,
                            size: 18,
                            color: ColorManager.iconColor,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              Expanded(
                child: HandlingDataWidget(
                  subTitle: StringManager.reelsNoCommentsTitle.tr(),
                  title: StringManager.comments.tr(),
                  // theme_3 + theme_1 are light palettes: the sheet surface is
                  // the light [surfaceCardColor], so fixed white would be
                  // invisible — use the per-theme text getters there.
                  titleStyle: context.bodyMedium.colorExt(
                      ConstantsManager.isTheme3 || ConstantsManager.isTheme1
                          ? ColorManager.textPrimary
                          : ColorManager.white),
                  subTitleStyle: context.bodyMedium.colorExt(
                      ConstantsManager.isTheme3 || ConstantsManager.isTheme1
                          ? ColorManager.secondaryText
                          : ColorManager.white),
                  reqState: state.requestState,
                  onTap: () => di<GetCommentsBloc>().add(GetCommentsEvent(
                      param: ReelParam(reelId: widget.reelId),
                      isLoading: true)),
                  child: ListView.builder(
                    controller: state.scrollControllerComments,
                    padding: EdgeInsets.zero,
                    itemCount: state.commentsList.length +
                        (_hasMoreComments(state) ? 1 : 0),
                    itemBuilder: (context, index) {
                      if (index >= state.commentsList.length) {
                        return Padding(
                          padding: EdgeInsets.symmetric(vertical: 14.h),
                          child: Center(
                            child: LoadingAnimationWidget.staggeredDotsWave(
                              color: ColorManager.primary,
                              size: 26.h,
                            ),
                          ),
                        );
                      }
                      return CommentItem(
                          commentEntity: state.commentsList[index]);
                    },
                  ),
                ),
              ),
              Container(
                padding: EdgeInsets.only(
                  bottom: bottomInset > 0 ? 8 : 8,
                  top: 8.h,
                  left: 10.w,
                  right: 10.w,
                ),
                width: ScreenUtil().screenWidth,
                decoration: BoxDecoration(
                  // Per-variant composer bar: theme_3 (NEXO) merges with its
                  // dark surface token; theme_1 (beige) and theme_2 (blue-
                  // white) get a light bar matching their light palettes (a
                  // dark bar on the light sheet would break the theme). The
                  // default golden variant keeps its legacy dark composer
                  // untouched (reelsComposer* tokens).
                  color: ConstantsManager.isTheme3
                      ? ColorManager.theme3SurfaceDark
                      : ConstantsManager.isTheme1
                          ? ColorManager.theme1BackgroundAlt
                          : ConstantsManager.isTheme2
                              ? ColorManager.theme2BackgroundAlt
                              : ColorManager.reelsComposerBg,
                  border: Border(
                      top: BorderSide(
                          color: ConstantsManager.isTheme1 ||
                                  ConstantsManager.isTheme2
                              ? ColorManager.borderColor
                              : ColorManager.reelsComposerBorder,
                          width: 0.5)),
                ),
                child: Column(
                  children: [
                    SizedBox(
                      height: 50,
                      child: ListView.builder(
                        controller: _emojiScrollController,
                        scrollDirection: Axis.horizontal,
                        itemCount: emojis.length,
                        itemBuilder: (context, index) {
                          return GestureDetector(
                            onTap: () => _addEmoji(emojis[index]),
                            child: Padding(
                              padding: context.paddingSymmetric(horizontal: 10),
                              child: TextWidget(
                                emojis[index],
                                style: context.bodyMedium.bold.size(25),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        UserImage(
                          image: MyDataModel.getInstance().profile?.image ?? '',
                          displayName: MyDataModel.getInstance().name ?? '',
                          imageSize: 38.w,
                        ),
                        12.wBox,
                        Expanded(
                          child: SizedBox(
                            height: 45.h,
                            child: TextInputWidget(
                              maxLength: 255,
                              StringManager.reelsAddComment.tr(),
                              suffixIconConstraints: BoxConstraints(
                                  maxHeight: 30.h, maxWidth: 55.w),
                              suffixIcon: InkWell(
                                onTap: () {
                                  if (textController.text.isNotEmpty) {
                                    di<MakeCommentsBloc>().add(
                                        MakeCommentsEvent(
                                            ReelParam(
                                                reelId: widget.reelId,
                                                comment: textController.text),
                                            widget.filter,
                                            widget.getReelsBloc));
                                    textController.clear();
                                  }
                                },
                                child: Container(
                                  width: 50.w,
                                  height: 28.h,
                                  margin: context.paddingOnly(end: 10),
                                  decoration: BoxDecoration(
                                    shape: BoxShape.rectangle,
                                    color: ColorManager.redIcons,
                                    borderRadius: 20.radius,
                                  ),
                                  child: const Icon(Icons.arrow_upward_rounded,
                                      size: 20, color: ColorManager.white),
                                ),
                              ),
                              controller: textController,
                              // theme_1/theme_2: light field + dark ink on
                              // their light bars; default/theme_3 keep the
                              // legacy dark composer field.
                              fillColor: ConstantsManager.isTheme1 ||
                                      ConstantsManager.isTheme2
                                  ? ColorManager.surfaceCardColor
                                  : ColorManager.reelsComposerField,
                              hintStyle: context.bodyMedium.colorExt(
                                  ConstantsManager.isTheme1 ||
                                          ConstantsManager.isTheme2
                                      ? ColorManager.secondaryText
                                      : ColorManager.reelsComposerHint),
                              textColor: ConstantsManager.isTheme1 ||
                                      ConstantsManager.isTheme2
                                  ? ColorManager.textPrimary
                                  : ColorManager.white,
                              cursorColor: ConstantsManager.isTheme1 ||
                                      ConstantsManager.isTheme2
                                  ? ColorManager.textPrimary
                                  : ColorManager.white,
                              enabledBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(30),
                                borderSide: BorderSide(
                                    color: ConstantsManager.isTheme1 ||
                                            ConstantsManager.isTheme2
                                        ? ColorManager.borderColor
                                        : ColorManager.reelsComposerBorder,
                                    width: 0.5),
                              ),
                              focusedBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(30),
                                borderSide: BorderSide(
                                    color: ConstantsManager.isTheme1 ||
                                            ConstantsManager.isTheme2
                                        ? ColorManager.primary
                                            .withValues(alpha: 0.6)
                                        : ColorManager.reelsComposerAccent
                                            .withValues(alpha: 0.6),
                                    width: 0.8),
                              ),
                              contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 15, vertical: 5),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
