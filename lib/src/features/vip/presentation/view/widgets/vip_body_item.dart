part of '../vip_screen.dart';

class VipBodyItem extends StatelessWidget {
  const VipBodyItem({
    super.key,
    required this.color,
    required this.vipCenterEntity,
  });

  final VipCenterEntity vipCenterEntity;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<GetVipThemeSettingsBloc, GetVipThemeSettingsState>(
      bloc: di<GetVipThemeSettingsBloc>(),
      buildWhen: (prev, curr) => prev.data != curr.data,
      builder: (context, themeState) {
        // Get background URL from theme settings based on VIP level
        final url = themeState.getBackgroundForVip(vipCenterEntity.level ?? 0);
        final hasNetworkBackground = url != null && url.isNotEmpty;

        return Scaffold(
          body: Stack(
            children: [
              // Background layer
              if (hasNetworkBackground)
                Positioned.fill(
                  child: CacheImageWidget(
                    url: url,
                    width: MediaQuery.of(context).size.width,
                    height: MediaQuery.of(context).size.height,
                    boxFit: BoxFit.fill,
                  ),
                )
              else
                Positioned.fill(
                  child: Container(
                    decoration: BoxDecoration(
                      color: ColorManager.vipBackgroundColor[
                          vipCenterEntity.level == 0
                              ? 1
                              : (vipCenterEntity.level ?? 0) >
                                      ColorManager.vipBackgroundColor.length
                                  ? ColorManager.vipBackgroundColor.length - 1
                                  : (vipCenterEntity.level ?? 1) - 1],
                      image: DecorationImage(
                        image: AssetImage(
                          AssetsManager.vipBackground(vip: 7),
                        ),
                        fit: BoxFit.fill,
                      ),
                    ),
                  ),
                ),
              // Content layer
              Padding(
                padding: context.paddingSymmetric(horizontal: 15),
                child: SizedBox(
                  height: ScreenUtil().screenHeight,
                  width: ScreenUtil().screenWidth,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      170.hBox,
                      (vipCenterEntity.img1 ?? "").contains(".svga") ||
                              (vipCenterEntity.img1 ?? "").contains(".zz") ||
                              (vipCenterEntity.img1 ?? "").contains(".zzz")
                          ? CacheSvgaWidget(
                              url: EndPoints.getImage(
                                  vipCenterEntity.img1 ?? ""),
                              width: 230.w,
                              height: 120.h,
                              boxFit: BoxFit.cover,
                            )
                          : Center(
                              child: ImageViewWidget(
                                url: EndPoints.getImage(
                                    vipCenterEntity.img1 ?? ""),
                                width: 230.w,
                                height: 120.h,
                                boxFit: BoxFit.cover,
                              ),
                            ),
                      (ScreenUtil().screenHeight / 8).hBox,
                      65.hBox,
                      Expanded(
                        child: SizedBox(
                          width: ScreenUtil().screenWidth,
                          child: SingleChildScrollView(
                            child: Column(
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  crossAxisAlignment: CrossAxisAlignment.center,
                                  children: [
                                    Image.asset(
                                      AssetsManager.titleIconPrivilege,
                                      height: 25.h,
                                      width: 25.w,
                                    ),
                                    10.wBox,
                                    Padding(
                                      padding: context.paddingOnly(bottom: 5),
                                      child: TextWidget(
                                          StringManager.vipPrivilege.tr(),
                                          style:
                                              context.bodyMedium.bold.colorExt(
                                            ColorManager.defaultTextVip,
                                          )),
                                    ),
                                    10.wBox,
                                    Transform.flip(
                                      flipX: true,
                                      child: Image.asset(
                                        AssetsManager.titleIconPrivilege,
                                        height: 25.h,
                                        width: 25.w,
                                      ),
                                    ),
                                  ],
                                ),
                                Builder(
                                  builder: (context) {
                                    final activePrivileges = vipCenterEntity
                                        .privilgesData
                                        ?.where((privilege) =>
                                            privilege.active == true)
                                        .toList();
                                    return TextWidget(
                                      '(${(activePrivileges ?? []).length.toString()}/${(vipCenterEntity.privilgesData ?? []).length})',
                                      style: context.bodyMedium.colorExt(
                                        ColorManager.onDark,
                                      ),
                                    );
                                  },
                                ),
                                15.hBox,
                                GridView.builder(
                                  shrinkWrap: true,
                                  itemCount:
                                      vipCenterEntity.privilgesData?.length ??
                                          0,
                                  physics: const NeverScrollableScrollPhysics(),
                                  padding:
                                      context.paddingSymmetric(horizontal: 15),
                                  itemBuilder: (context, __) {
                                    return InkWell(
                                      borderRadius: 8.radius,
                                      onTap: () {
                                        showDialog(
                                          context: context,
                                          builder: (_) => AnimatedDialog(
                                            title: vipCenterEntity
                                                .privilgesData?[__]
                                                .privileg
                                                ?.name,
                                            description: vipCenterEntity
                                                .privilgesData?[__]
                                                .privileg
                                                ?.title,
                                            isHideConfirm: true,
                                            onTap: () => context.popRoute(),
                                            child: Padding(
                                              padding: context.paddingOnly(
                                                  bottom: 10),
                                              child: Methods().isSvgaFile(vipCenterEntity
                                                          .privilgesData?[__]
                                                          .privileg
                                                          ?.image ??
                                                      '')
                                                  ? CacheSvgaWidget(
                                                      url: vipCenterEntity
                                                              .privilgesData?[
                                                                  __]
                                                              .privileg
                                                              ?.image ??
                                                          '',
                                                      boxFit: BoxFit.fill,
                                                      height: 80,
                                                      width: 90,
                                                    )
                                                  : (vipCenterEntity
                                                                  .privilgesData?[
                                                                      __]
                                                                  .privileg
                                                                  ?.imageType ??
                                                              '') ==
                                                          'mp4'
                                                      ? CacheVideoWidget(
                                                          videoUrl: vipCenterEntity
                                                                  .privilgesData?[
                                                                      __]
                                                                  .privileg
                                                                  ?.image ??
                                                              '',
                                                          height: 80,
                                                          width: 90,
                                                          isReels: true,
                                                        )
                                                      : (vipCenterEntity.privilgesData?[__].privileg?.imageType ??
                                                                      '') ==
                                                                  'vap' &&
                                                              Methods.isVideoFile(
                                                                  vipCenterEntity
                                                                          .privilgesData?[__]
                                                                          .privileg
                                                                          ?.image ??
                                                                      '')
                                                          ? CachedVapWidget(
                                                              isLoop: true,
                                                              url: vipCenterEntity
                                                                      .privilgesData?[
                                                                          __]
                                                                      .privileg
                                                                      ?.image ??
                                                                  '',
                                                              height: 80,
                                                              width: 90,
                                                            )
                                                          : (vipCenterEntity.privilgesData?[__].privileg?.imageType ?? '') == 'alpha'
                                                              ? CacheAlphaWidget(
                                                                  url: vipCenterEntity
                                                                          .privilgesData?[
                                                                              __]
                                                                          .privileg
                                                                          ?.image ??
                                                                      '',
                                                                  height: 80,
                                                                  width: 90,
                                                                  isLoop: true,
                                                                )
                                                              : ImageViewWidget(
                                                                  url: vipCenterEntity
                                                                          .privilgesData?[
                                                                              __]
                                                                          .privileg
                                                                          ?.image ??
                                                                      '',
                                                                  boxFit: BoxFit
                                                                      .contain,
                                                                  height: 80,
                                                                  width: 90,
                                                                  heightLoading:
                                                                      100.h,
                                                                  widthLoading:
                                                                      90,
                                                                  isRoomProfile:
                                                                      true,
                                                                ),
                                            ),
                                          ),
                                        );
                                      },
                                      child: MainPrivilegesItem(
                                        image1: vipCenterEntity
                                                .privilgesData?[__].img1 ??
                                            '',
                                        image2: vipCenterEntity
                                                .privilgesData?[__].img2 ??
                                            '',
                                        title: vipCenterEntity
                                                .privilgesData?[__].name ??
                                            '',
                                        isActive: vipCenterEntity
                                                .privilgesData?[__].active ??
                                            false,
                                      ),
                                    );
                                  },
                                  gridDelegate:
                                      SliverGridDelegateWithFixedCrossAxisCount(
                                    crossAxisCount: 3,
                                    childAspectRatio: 1.3,
                                    mainAxisSpacing: 5.w,
                                    crossAxisSpacing: 5.w,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          bottomNavigationBar: VipBottomBar(
            color: color,
            vipBadge: vipCenterEntity.img1 ?? '',
            expire: vipCenterEntity.expire.toString(),
            id: vipCenterEntity.id.toString(),
            price: vipCenterEntity.price.toString(),
            name: vipCenterEntity.name.toString(),
          ),
        );
      },
    );
  }
}
