part of '../mall_page.dart';

class _MallProductItem extends StatefulWidget {
  final MallEntity data;
  final MallOrBagType tabType;
  final bool isLoading;
  final bool isSelected;
  const _MallProductItem({
    required this.data,
    required this.tabType,
    this.isLoading = false,
    this.isSelected = false,
  });

  @override
  State<_MallProductItem> createState() => _MallProductItemState();
}

class _MallProductItemState extends State<_MallProductItem> {
  double? _downloadProgress;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: context.paddingSymmetric(vertical: 5, horizontal: 3),
      decoration: BoxDecoration(
        border: widget.isSelected
            ? Border.all(color: ColorManager.primary, width: 2)
            : null,
        borderRadius: 15.radius,
        color: ColorManager.white,
      ),
      child: Stack(
        children: [
          Padding(
            padding: context.paddingAll(7),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    TextWidget(
                      "${(widget.data).expire.toString()}days",
                      style: context.bodyMedium
                          .size(11)
                          .w600
                          .colorExt(ColorManager.greyTextColor),
                    ),
                    GestureDetector(
                      onTap: () => _handleItemView(
                        context,
                        widget.data,
                      ),
                      child: Image.asset(
                        AssetsManager.playCircle,
                        width: 25,
                      ),
                    ),
                  ],
                ),
                ImageViewWidget(
                  url: widget.data.image ?? '',
                  width: 100.w,
                  height: 100.h,
                  boxFit: BoxFit.contain,
                ),
                FittedBox(
                  child: Text(
                    (widget.data).name ?? "",
                    style: context.bodyMedium
                        .size(16)
                        .w600
                        .colorExt(ColorManager.greyTextColor),
                  ),
                ),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    CoinIcon(
                      width: 23,
                      height: 23,
                      fallbackAsset: AssetsManager.coinPayment,
                    ),
                    TextWidget(
                      (widget.data).price.toString(),
                      style: context.bodyMedium
                          .size(16)
                          .w600
                          .colorExt(ColorManager.yellowTextColor),
                    ),
                  ],
                ),
              ],
            ),
          ),
          if (_downloadProgress != null)
            Container(
              decoration: BoxDecoration(
                color: Colors.black45,
                borderRadius: 15.radius,
              ),
            ),
          if (_downloadProgress != null)
            Positioned.fill(
              child: Center(
                child: Padding(
                  padding: EdgeInsets.symmetric(horizontal: 20.w),
                  child: LinearProgressIndicator(
                    value: _downloadProgress,
                    backgroundColor: Colors.black45,
                    color: ColorManager.white,
                    minHeight: 10.h,
                    borderRadius: BorderRadius.circular(20),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Future<void> _handleItemView(BuildContext context, MallEntity data) async {
    if (_downloadProgress != null) return; // Already downloading

    // Check cache
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
      setState(() {
        _downloadProgress = 0.0;
      });

      if (isSvga) {
        await SVGAAssetCacheManager().downloadWithProgress(
          EndPoints.getImage(itemUrl),
          onReceiveProgress: (received, total) {
            if (total != -1 && mounted) {
              setState(() {
                _downloadProgress = received / total;
              });
            }
          },
        );
      } else if (isMp4) {
        await VideoAssetCacheManager().downloadWithProgress(
          EndPoints.getImage(itemUrl),
          onReceiveProgress: (received, total) {
            if (total != -1 && mounted) {
              setState(() {
                _downloadProgress = received / total;
              });
            }
          },
        );
      } else if (isVap) {
        await VapAssetCacheManager().downloadWithProgress(
          EndPoints.getImage(itemUrl),
          onReceiveProgress: (received, total) {
            if (total != -1 && mounted) {
              setState(() {
                _downloadProgress = received / total;
              });
            }
          },
        );
      } else if (isAlpha) {
        await AlphaAssetCacheManager().downloadWithProgress(
          EndPoints.getImage(itemUrl),
          onReceiveProgress: (received, total) {
            if (total != -1 && mounted) {
              setState(() {
                _downloadProgress = received / total;
              });
            }
          },
        );
      } else {
        await AssetCacheManager().downloadWithProgress(
          EndPoints.getImage(itemUrl),
          onReceiveProgress: (received, total) {
            if (total != -1 && mounted) {
              setState(() {
                _downloadProgress = received / total;
              });
            }
          },
        );
      }

      if (!mounted) return;
      setState(() {
        _downloadProgress = null;
      });
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
      return; // Don't select immediately after download, let user tap again or auto-select? User asked to "start to caching it and show progress", implies not selecting yet.
    }
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
