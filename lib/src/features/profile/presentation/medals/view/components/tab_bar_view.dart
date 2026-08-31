part of '../medals_page.dart';

class MedalsTabBarView extends StatelessWidget {
  const MedalsTabBarView({
    super.key,
    required this.type,
    required this.badges,
    required this.reqState,
    required this.error,
  });

  final String type;
  final List<AchievementLevelEntity> badges;
  final RequestState reqState;
  final String error;

  @override
  Widget build(BuildContext context) {
    return HandlingDataWidget(
      reqState: reqState,
      title: StringManager.noAchievements.tr(),
      subTitle: StringManager.noAchievementSubtitle.tr(),
      titleStyle: context.bodyMedium.w700.colorExt(ColorManager.textPrimary),
      child: GridView.builder(
        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          mainAxisSpacing: 5.h,
          crossAxisSpacing: 3.w,
        ),
        itemCount: badges.length,
        padding: context.paddingAll(15),
        itemBuilder: (context, index) {
          return Material(
            color: ColorManager.transparent,
            shadowColor: ColorManager.transparent,
            elevation: 0,
            child: InkWell(
              borderRadius: 20.radius,
              onTap: () {
                _showDescriptionDialog(
                  context,
                  title: badges[index].description,
                  img: badges[index].validImage,
                );
              },
              child: Column(
                children: [
                  badges[index].image.contains('.svga')
                      ? CacheSvgaWidget(
                          url: type == "4"
                              ? badges[index].image
                              : badges[index].enable == 1
                                  ? badges[index].validImage
                                  : badges[index].image,
                          boxFit: BoxFit.fill,
                          height: 140.h,
                          width: 140.h,
                        )
                      : ImageViewWidget(
                          url: type == "4"
                              ? badges[index].image
                              : badges[index].enable == 1
                                  ? badges[index].validImage
                                  : badges[index].image,
                          boxFit: BoxFit.fill,
                          height: 140.h,
                          width: 140.h,
                        ),
                  5.hBox,
                  Text(
                    badges[index].description,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    maxLines: 1,
                    style: context.bodyMedium.bold
                        .colorExt(ColorManager.textPrimary),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _showDescriptionDialog(
    BuildContext context, {
    required String title,
    required String img,
  }) {
    showDialog(
      context: context,
      useSafeArea: false,
      barrierDismissible: true,
      builder: (_) {
        return GestureDetector(
          onTap: () => Navigator.of(context).pop(),
          child: Container(
            height: ScreenUtil().screenHeight,
            color: ColorManager.black.withValues(alpha: (0.75)),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Stack(
                  alignment: AlignmentDirectional.center,
                  children: [
                    ShowSVGA(
                      svgaAssetPath: AssetsManager.backgroundMedalsSvga,
                      height: 250.h,
                      width: 250.w,
                    ),
                    img.contains('.svga')
                        ? CacheSvgaWidget(
                            url: EndPoints.getImage(img),
                            height: 200.h,
                            width: 300.w,
                            boxFit: BoxFit.contain,
                          )
                        : img.contains('.vap')
                            ? CachedVapWidget(
                                url: EndPoints.getImage(img),
                                height: 200.h,
                                width: 300.w,
                              )
                            : ImageViewWidget(
                                url: img,
                                height: 200.h,
                                width: 300.w,
                                boxFit: BoxFit.contain,
                              ),
                  ],
                ),
                10.hBox,
                TextWidget(
                  title,
                  style:
                      context.bodyLarge.bold.colorExt(ColorManager.textPrimary),
                  textAlign: TextAlign.center,
                  padding: context.paddingSymmetric(horizontal: 25),
                )
              ],
            ),
          ),
        );
      },
    );
  }
}
