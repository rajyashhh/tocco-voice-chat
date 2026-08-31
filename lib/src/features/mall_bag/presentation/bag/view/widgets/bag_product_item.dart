part of '../bag_page.dart';

class BagProductItem extends StatefulWidget {
  final MyBagEntity data;
  final MallOrBagType tabType;
  final bool isLoading;

  const BagProductItem({
    required this.data,
    required this.tabType,
    this.isLoading = false,
    super.key,
  });

  @override
  State<BagProductItem> createState() => BagProductItemState();
}

class BagProductItemState extends State<BagProductItem> {
  final ValueNotifier<double?> downloadProgress = ValueNotifier(null);

  Future<void> prepareAndOpenItem(
    BuildContext context,
    MyBagEntity data, {
    bool isShowTestScreen = true,
  }) async {
    if (downloadProgress.value != null) return;

    final itemUrl = data.svg ?? '';
    final isSvga = data.imageType == 'svga';
    final isMp4 = data.imageType == 'mp4';
    final isVap = data.imageType == 'vap';
    final isAlpha = data.imageType == 'alpha';

    File? cachedFile;
    if (isSvga) {
      cachedFile = await SVGAAssetCacheManager().isExistFile(itemUrl);
    } else if (isMp4) {
      cachedFile = await VideoAssetCacheManager().isExistFile(itemUrl);
    } else if (isVap) {
      cachedFile = await VapAssetCacheManager().isExistFile(itemUrl);
    } else if (isAlpha) {
      cachedFile = await AlphaAssetCacheManager().isExistFile(itemUrl);
    } else {
      cachedFile = await AssetCacheManager().isExistFile(itemUrl);
    }

    if (cachedFile == null) {
      downloadProgress.value = 0.0;

      Future<File?> downloadFuture;

      if (isSvga) {
        downloadFuture = SVGAAssetCacheManager().downloadWithProgress(
          EndPoints.getImage(itemUrl),
          onReceiveProgress: (received, total) {
            if (total != -1) {
              downloadProgress.value = received / total;
            }
          },
        );
      } else if (isMp4) {
        downloadFuture = VideoAssetCacheManager().downloadWithProgress(
          EndPoints.getImage(itemUrl),
          onReceiveProgress: (received, total) {
            if (total != -1) {
              downloadProgress.value = received / total;
            }
          },
        );
      } else if (isVap) {
        downloadFuture = VapAssetCacheManager().downloadWithProgress(
          EndPoints.getImage(itemUrl),
          onReceiveProgress: (received, total) {
            if (total != -1) {
              downloadProgress.value = received / total;
            }
          },
        );
      } else if (isAlpha) {
        downloadFuture = AlphaAssetCacheManager().downloadWithProgress(
          EndPoints.getImage(itemUrl),
          onReceiveProgress: (received, total) {
            if (total != -1) {
              downloadProgress.value = received / total;
            }
          },
        );
      } else {
        downloadFuture = AssetCacheManager().downloadWithProgress(
          EndPoints.getImage(itemUrl),
          onReceiveProgress: (received, total) {
            if (total != -1) {
              downloadProgress.value = received / total;
            }
          },
        );
      }

      await downloadFuture;
      downloadProgress.value = null;

      if (isShowTestScreen) {
        TestItemsController.instance.onMallCardTap(
          context,
          TestMallBagParam(
            image: widget.data.image,
            svg: widget.data.svg,
            type: widget.data.imageType,
            name: widget.data.name,
          ),
          widget.tabType,
        );
      }
      return;
    }

    if (isShowTestScreen) {
      TestItemsController.instance.onMallCardTap(
        context,
        TestMallBagParam(
          image: widget.data.image,
          svg: widget.data.svg,
          type: widget.data.imageType,
          name: widget.data.name,
        ),
        widget.tabType,
      );
    }
  }

  @override
  void dispose() {
    downloadProgress.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<MyBagBloc, MyBagState>(
      bloc: di<MyBagBloc>(),
      buildWhen: (prev, curr) => prev.selectedItemBubble != curr.selectedItemBubble || prev.selectedItemFrame != curr.selectedItemFrame || prev.selectedItemIntro != curr.selectedItemIntro || prev.selectedItemSpecialId != curr.selectedItemSpecialId || prev.selectedItemProfileFrame != curr.selectedItemProfileFrame || prev.isBubbleNull != curr.isBubbleNull || prev.isFrameNull != curr.isFrameNull || prev.isIntroNull != curr.isIntroNull || prev.isSpecialIdNull != curr.isSpecialIdNull || prev.isProfileFrameNull != curr.isProfileFrameNull,
      builder: (context, state) {
        return InkWell(
          onTap: () {
            di<MyBagBloc>().add(
              SelectBagItemEvent(
                selectedItem: widget.data,
                type: widget.tabType == MallOrBagType.bubble
                    ? 0
                    : widget.tabType == MallOrBagType.frame
                        ? 1
                        : widget.tabType == MallOrBagType.intro
                            ? 2
                            : widget.tabType == MallOrBagType.specialId
                                ? 3
                                : 4,
              ),
            );
          },
          child: Container(
            margin: context.paddingAll(5),
            decoration: BoxDecoration(
              borderRadius: 15.radius,
              border: (widget.tabType == MallOrBagType.bubble
                              ? state.selectedItemBubble?.id
                              : widget.tabType == MallOrBagType.frame
                                  ? state.selectedItemFrame?.id
                                  : widget.tabType == MallOrBagType.intro
                                      ? state.selectedItemIntro?.id
                                      : widget.tabType ==
                                              MallOrBagType.specialId
                                          ? state.selectedItemSpecialId?.id
                                          : state
                                              .selectedItemProfileFrame?.id) ==
                          widget.data.id &&
                      (widget.tabType == MallOrBagType.bubble
                          ? state.isBubbleNull != true
                          : widget.tabType == MallOrBagType.frame
                              ? state.isFrameNull != true
                              : widget.tabType == MallOrBagType.intro
                                  ? state.isIntroNull != true
                                  : widget.tabType == MallOrBagType.specialId
                                      ? state.isSpecialIdNull != true
                                      : state.isProfileFrameNull != true)
                  ? Border.all(color: ColorManager.primary, width: 2)
                  : null,
              color: ColorManager.white,
            ),
            child: Stack(
              children: [
                Column(
                  mainAxisAlignment: MainAxisAlignment.start,
                  children: [
                    Align(
                      alignment: Alignment.topRight,
                      child: IconButton(
                        onPressed: () => prepareAndOpenItem(
                          context,
                          widget.data,
                        ),
                        icon: Image.asset(
                          AssetsManager.playCircle,
                          width: 20,
                        ),
                      ),
                    ),
                    ImageViewWidget(
                      url: widget.data.image ?? '',
                      width: 100.w,
                      height: 110.h,
                      boxFit: BoxFit.contain,
                    ),
                    FittedBox(
                      child: Text(
                        widget.data.name ?? "",
                        style: context.bodyMedium
                            .size(14)
                            .w600
                            .colorExt(ColorManager.greyTextColor),
                      ),
                    ),
                    Flexible(
                      child: Padding(
                        padding: context.paddingAll(3),
                        child: TextWidget(
                          (widget.data.expire?.length ?? 1) > 10
                              ? Methods()
                                  .getTimeDifference(widget.data.expire ?? "")
                              : "${widget.data.expire ?? ""} ${StringManager.days.tr()}",
                          style: context.bodyMedium
                              .size(10)
                              .w600
                              .colorExt(ColorManager.greyTextColor),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ),
                    20.wBox,
                  ],
                ),
                ValueListenableBuilder<double?>(
                  valueListenable: downloadProgress,
                  builder: (context, progress, child) {
                    if (progress == null) return const SizedBox.shrink();
                    return Stack(
                      children: [
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.black45,
                            borderRadius: 15.radius,
                          ),
                        ),
                        Positioned.fill(
                          child: Center(
                            child: Padding(
                              padding: EdgeInsets.symmetric(horizontal: 20.w),
                              child: LinearProgressIndicator(
                                value: progress,
                                backgroundColor: Colors.black45,
                                color: ColorManager.white,
                                minHeight: 10.h,
                                borderRadius: BorderRadius.circular(20),
                              ),
                            ),
                          ),
                        ),
                      ],
                    );
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
