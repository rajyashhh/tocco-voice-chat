part of 'package:general/src/features/home/presentation/home/view/home_page.dart';

// NEW THEME
class _RoomCardBody extends StatelessWidget {
  const _RoomCardBody({
    super.key,
    required this.data,
    this.isLoading = false,
    this.isShowSVGA = false,
  });

  final RoomEntity data;
  final bool isLoading;
  final bool isShowSVGA;

  static const _gradientDecoration = BoxDecoration(
    gradient: LinearGradient(
      colors: [Color(0x66000000), Colors.transparent],
      begin: AlignmentDirectional.bottomCenter,
      end: AlignmentDirectional.topCenter,
    ),
  );

  @override
  Widget build(BuildContext context) {
    // Cache dimension calculations
    final topPosition = isShowSVGA ? 20.h : 5.h;
    final sidePosition = isShowSVGA ? 12.w : 5.w;
    final containerHeight = isShowSVGA ? 60.h : 50.h;
    final startPadding = isShowSVGA ? 12.5 : 7.5;

    return Stack(
      clipBehavior: Clip.none,
      children: [
        MultiTapCard(
          onTap: () {
            if (isLoading) {
              di<RoomStateManager>().navigateToRoom(
                RoomEntryRequest(
                  context: context,
                  roomData: data,
                  isLive: false,
                ),
              );
            }
          },
          child: ClipRRect(
            borderRadius: 8.radius,
            child: Stack(
              alignment: AlignmentDirectional.bottomCenter,
              clipBehavior: Clip.none,
              children: [
                Positioned.fill(
                  child: ImageViewWidget(
                    url: data.cover ?? "",
                    boxFit: BoxFit.cover,
                    displayName: data.name ?? '',
                    isRoomCover: true,
                  ),
                ),
                PositionedDirectional(
                  top: topPosition,
                  end: 5.w,
                  child: LockAndPkIcon(
                    isPK: data.isPK ?? false,
                    isPassword: data.passwordStatus ?? false,
                    isHasLuckyBox: data.hasLuckyBox ?? false,
                  ),
                ),
                if (data.roomLevelImage != null &&
                    data.roomLevelImage!.isNotEmpty)
                  Positioned(
                    top: isShowSVGA ? 20.h : 10.h,
                    left: sidePosition,
                    right: sidePosition,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.start,
                      children: [
                        ImageViewWidget(
                          url: data.roomLevelImage!,
                          boxFit: BoxFit.cover,
                          width: 30.w,
                          height: 30.h,
                        ),
                      ],
                    ),
                  ),
                Align(
                  alignment: AlignmentDirectional.bottomCenter,
                  child: Container(
                    width: ScreenUtil().screenWidth,
                    height: containerHeight,
                    padding: context.paddingOnly(
                      start: startPadding,
                      end: 3.5,
                    ),
                    decoration: _gradientDecoration,
                    child: SizedBox.shrink(
                      child: Align(
                        alignment: AlignmentDirectional.topStart,
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                ImageViewWidget(
                                  url: data.country?.flag ?? "",
                                  height: 25,
                                  width: 25,
                                ),
                                5.0.wBox,
                                Expanded(
                                  child: TextWidget(
                                    Methods.capitalizeFirstLetter(
                                        data.name ?? ""),
                                    style: context.bodyMedium
                                        .colorExt(ColorManager.onDark),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                                5.0.wBox,
                              ],
                            ),
                            Row(
                              children: [
                                Expanded(
                                  child: ConstrainedBox(
                                    constraints:
                                        BoxConstraints(maxWidth: 120.w),
                                    child: Text(
                                      data.roomIntro ?? '',
                                      style: context.bodySmall
                                          .colorExt(ColorManager.onDark),
                                      overflow: TextOverflow.ellipsis,
                                      maxLines: 1,
                                    ),
                                  ),
                                ),
                                5.wBox,
                                VisitorsCount(
                                  count: (data.visitorsCount ?? 0).toString(),
                                  fontSize: 12,
                                  color: ColorManager.onDark,
                                  fontWeight: FontWeight.w500,
                                  icon: AssetsManager.icHomeItemAudio,
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
        if (isShowSVGA)
          Positioned(
            top: -15.h,
            right: -10.w,
            left: -10.w,
            bottom: -15.h,
            child: RepaintBoundary(
              child: SizedBox(
                height: ScreenUtil().screenWidth,
                width: ScreenUtil().screenWidth,
                child: ShowSVGA(
                  svgaAssetPath: AssetsManager.grid1,
                  fit: BoxFit.fill,
                ),
              ),
            ),
          ),
      ],
    );
  }
}
