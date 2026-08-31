import 'dart:ui';

import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/cache/cache_alpha_widget.dart';
import 'package:general/src/core/widgets/cache/cache_vap_widget.dart';
import 'package:general/src/core/widgets/cache/cache_video_widget.dart';
import 'package:general/src/core/widgets/id_with_copy.dart';
import 'package:general/src/features/mall_bag/presentation/component/custom_avatar.dart';

class TestItemsController {
  // Create a private constructor
  TestItemsController._privateConstructor();

  // Create a static instance variable
  static final TestItemsController _instance =
      TestItemsController._privateConstructor();

  // Provide a static getter to access the singleton instance
  static TestItemsController get instance => _instance;

  void onMallCardTap(
    BuildContext context,
    TestMallBagParam param,
    MallOrBagType type,
  ) {
    if (type == MallOrBagType.intro) {
      _showSvgDialog(context, param);
    }
    if (type == MallOrBagType.frame) {
      _showFrameDialog(context, param);
    }
    if (type == MallOrBagType.bubble) {
      _showBubbleDialog(context, param);
    }
    if (type == MallOrBagType.specialId) {
      _showSpecialIdDialog(context, param);
    }
    if (type == MallOrBagType.profileFrame) {
      _showProfileFrameDialog(context, param);
    }
  }

  void _showProfileFrameDialog(BuildContext context, TestMallBagParam param) {
    final String vipImage = param.svg ?? "";
    Widget buildVipWidget() {
      if (Methods().isSvgaFile(vipImage)) {
        return CacheSvgaWidget(
          url: vipImage,
          boxFit: BoxFit.fill,
          width: ScreenUtil().screenWidth,
        );
      } else if (param.type == 'mp4') {
        return CacheVideoWidget(
          videoUrl: vipImage,
          width: ScreenUtil().screenWidth,
          isReels: true,
        );
      } else if (param.type == 'vap' && Methods.isVideoFile(vipImage)) {
        return CachedVapWidget(
          isLoop: true,
          url: vipImage,
          width: ScreenUtil().screenWidth,
        );
      } else if (param.type == 'alpha') {
        return CacheAlphaWidget(
          url: vipImage,
          width: ScreenUtil().screenWidth,
          isLoop: true,
        );
      } else {
        return ImageViewWidget(
          url: vipImage,
          boxFit: BoxFit.cover,
          width: ScreenUtil().screenWidth,
          heightLoading: 100.h,
          widthLoading: ScreenUtil().screenWidth,
          isRoomProfile: true,
        );
      }
    }

    svgDialog(
      context: context,
      widget: Stack(
        alignment: Alignment.center,
        children: [
          // Blurred background
          Positioned.fill(
            child: ClipRRect(
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
                child: Container(
                  width: ScreenUtil().screenWidth,
                  height: ScreenUtil().screenHeight,
                  color: Colors.black.withValues(alpha: 0.6),
                ),
              ),
            ),
          ),

          Positioned.fill(
            top: ScreenUtil().screenHeight * 0.2,
            child: Padding(
              padding: context.paddingOnly(
                top: 0.h,
              ),
              child: buildVipWidget(),
            ),
          ),

          // Profile Information
          Positioned.fill(
            child: Padding(
              padding: context.paddingSymmetric(horizontal: 40),
              child: Column(
                children: [
                  (ScreenUtil().screenHeight * 0.37).hBox,
                  CustomAvatar(
                    image: MyDataModel.getInstance().profile?.image ?? "",
                    name: MyDataModel.getInstance().name ?? '',
                    size: 80.h,
                  ),
                  25.hBox,
                  TextWidget(
                    MyDataModel.getInstance().name ?? '',
                    style: context.bodyMedium.copyWith(
                      fontSize: 18.sp,
                      fontWeight: FontWeight.w500,
                      color: (MyDataModel.getInstance().vip1?.colorName ??
                                  '') !=
                              ''
                          ? Color(
                              int.parse(
                                (MyDataModel.getInstance().vip1?.colorName ??
                                        '')
                                    .replaceFirst('#', '0xff'),
                              ),
                            )
                          : Colors.white,
                    ),
                  ),
                  5.hBox,
                  Row(
                    children: [
                      const Spacer(),
                      IdWithCopyIcon(
                        userId: MyDataModel.getInstance().uuid ?? '',
                        isNeedCopyIcon: true,
                        isSpecial:
                            (((MyDataModel.getInstance().specialId ?? 0) != 0)),
                        specialImg:
                            MyDataModel.getInstance().specialIdImage ?? '',
                        color:
                            MyDataModel.getInstance().imageColorEntity?.color,
                        img: MyDataModel.getInstance().imageColorEntity?.image,
                        mainAxisAlignment: MainAxisAlignment.start,
                        idColor: ColorManager.greyTextColor,
                        idStyle: context.bodyMedium.bold
                            .colorExt(
                              Methods.safeHexColor(MyDataModel.getInstance()
                                      .imageColorEntity
                                      ?.color) ??
                                  ColorManager.textPrimary,
                            )
                            .copyWith(height: 0.1, fontSize: 11.sp),
                      ),
                      const Spacer(),
                    ],
                  ),
                  25.hBox,
                  ...List.generate(2, (_) {
                    return Column(
                      children: [
                        ClipRRect(
                          borderRadius: 10.radius,
                          child: BackdropFilter(
                            filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
                            child: Container(
                              height: 65.h,
                              padding: context.paddingSymmetric(horizontal: 10),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.2),
                                borderRadius: 10.radius,
                              ),
                            ),
                          ),
                        ),
                        5.hBox,
                      ],
                    );
                  }),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showFrameDialog(BuildContext context, TestMallBagParam param) {
    svgDialog(
      context: context,
      widget: Stack(
        children: [
          Center(
            child: CustomAvatar(
              image: MyDataModel.getInstance().profile?.image ?? "",
              name: MyDataModel.getInstance().name ?? '',
              size: 90.h,
            ),
          ),
          Center(
            child: param.type == "alpha"
                ? CacheAlphaWidget(
                    url: EndPoints.getImage(param.svg),
                    height: 170.h,
                    width: 170.h,
                    isLoop: true,
                  )
                : (param.type ?? "") == "svga" || (param.type ?? "") == "zz"
                    ? CacheSvgaWidget(
                        url: param.svg ?? '',
                        height: 170.h,
                      )
                    : (param.type ?? "") == "vap"
                        ? CachedVapWidget(
                            url: EndPoints.getImage(param.svg ?? ''),
                            height: 170.h,
                            width: 170.h,
                            isLoop: true,
                          )
                        : (param.type ?? "") == "mp4"
                            ? CacheVideoWidget(
                                videoUrl: EndPoints.getImage(param.svg ?? ''),
                                height: 170.h,
                                width: 170.h,
                                isReels: true,
                              )
                            : ImageViewWidget(
                                url: param.svg ?? '',
                                height: 170.h,
                                width: 170.h,
                              ),
          )
        ],
      ),
    );
  }

  void _showSvgDialog(BuildContext context, TestMallBagParam param) {
    svgDialog(
      context: context,
      widget: Stack(
        children: [
          param.type == "alpha"
              ? CacheAlphaWidget(
                  url: EndPoints.getImage(param.svg),
                  height: ScreenUtil().screenHeight,
                  width: ScreenUtil().screenWidth,
                  isLoop: true,
                )
              : param.type == "vap"
                  ? IgnorePointer(
                      child: CachedVapWidget(
                        isLoop: true,
                        url: EndPoints.getImage(
                          param.svg ?? '',
                        ),
                      ),
                    )
                  : (param.type ?? "") == "svga" ||
                          (param.type ?? "") == ".zz" ||
                          (param.type ?? "") == ".zzz"
                      ? CacheSvgaWidget(url: param.svg ?? '')
                      : (param.type ?? "") == "mp4"
                          ? CacheVideoWidget(
                              videoUrl: EndPoints.getImage(param.svg ?? ''),
                              isReels: true,
                              isShowGift: true,
                            )
                          : ImageViewWidget(
                              url: param.svg ?? '',
                            ),
        ],
      ),
    );
  }

  void _showBubbleDialog(BuildContext context, TestMallBagParam param) {
    showDialog(
      context: context,
      builder: (context) {
        return Stack(
          alignment: AlignmentDirectional.center,
          children: [
            // 60.hBox,
            ImageViewWidget(
              url: param.image ?? '',
              boxFit: BoxFit.fill,
              isBubble: true,
              child: Padding(
                padding: context.paddingAll(5),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Padding(
                      padding: EdgeInsets.only(left: 30.w, right: 30.w),
                      child: TextWidget(
                        StringManager.helloEveryone,
                        style: context.bodyMedium.bold
                            .colorExt(ColorManager.textPrimary),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            // Align(
            //   alignment: AlignmentDirectional.bottomCenter,
            //   child: Text( param.name?? "",
            //       style:  context.bodyMedium.w600
            //           .colorExt(
            //           ColorManager.textPrimary)),
            // ),
            // 25.hBox,
            // ImageViewWidget(
            //     url: param.image ?? '',
            //     boxFit: BoxFit.fill,
            //     isBubble: true,
            //
            //     child: Padding(
            //     padding: context.paddingAll(5),
            // child: Column(
            //   mainAxisAlignment: MainAxisAlignment.start,
            //   crossAxisAlignment: CrossAxisAlignment.start,
            //   children: [
            //     Padding(
            //       padding: EdgeInsets.only(
            //           left: 8.w, right: 8.w),
            //       child: TextWidget(
            //         StringManager.niceToMeetYou,
            //         style:  context.bodyMedium
            //             .size(13)
            //             .colorExt(
            //             ColorManager.textPrimary),
            //       ),
            //     ),
            //   ],
            // ),
            //     ),
            // ),
          ],
        );
      },
    );
  }

  void _showSpecialIdDialog(BuildContext context, TestMallBagParam param) {
    showDialog(
      context: context,
      builder: (context) {
        return Center(
          child: ImageViewWidget(
            url: param.image ?? '',
            height: ScreenUtil().screenHeight * 0.3,
            width: ScreenUtil().screenWidth * 0.9,
            boxFit: BoxFit.contain,
          ),
        );
      },
    );
  }

  Future<void> svgDialog({
    required BuildContext context,
    required Widget widget,
    Color? color,
  }) {
    return showGeneralDialog(
      context: context,
      barrierLabel: "",
      barrierDismissible: true,
      transitionDuration: const Duration(milliseconds: 400),
      barrierColor: Colors.black.withValues(alpha: (0.5)),
      transitionBuilder: (context, animation, secondaryAnimation, child) {
        return fromBottom(animation, secondaryAnimation, child);
      },
      pageBuilder: (context, animation, secondaryAnimation) {
        return Align(
          alignment: const Alignment(0, 1),
          child: Material(
            type: MaterialType.transparency,
            child: widget,
          ),
        );
      },
    );
  }

  fromBottom(Animation<double> animation, Animation<double> secondaryAnimation,
      Widget child) {
    return SlideTransition(
      position: Tween<Offset>(
        end: Offset.zero,
        begin: const Offset(1.0, 0.0),
      ).animate(animation),
      child: child,
    );
  }
}
