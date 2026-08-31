part of 'package:general/src/features/profile/presentation/f_f_f_v/view/page/f_f_f_screen.dart';

class FFFVUserInfoRow extends StatelessWidget {
  final UserEntity user;
  final Widget? endIcon;
  final bool? isUserVip;
  final VoidCallback? select;
  final EdgeInsetsGeometry? margin;
  final EdgeInsetsGeometry? padding;

  const FFFVUserInfoRow({
    required this.user,
    this.endIcon,
    super.key,
    this.isUserVip,
    this.margin,
    this.padding,
    this.select,
  });

  @override
  Widget build(BuildContext context) {
    final String? colorCode = user.colorName;
    final Color? parsedColor = Methods.safeHexColor(colorCode);
    final bool hasValidColor = parsedColor != null;
    final Color resolvedColor = parsedColor ?? ColorManager.textPrimary;
    return Container(
      margin: margin ?? context.paddingSymmetric(horizontal: 20),
      padding: padding ?? context.paddingAll(20),
      decoration: BoxDecoration(
        borderRadius: 5.radius,
        color: ColorManager.scaffoldBg,
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10.0),
        child: InkWell(
          onTap: select ??
              () {
                Methods().userProfileNavigator(
                  context: context,
                  userId: '${user.id}',
                );
              },
          child: Column(
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                mainAxisAlignment: MainAxisAlignment.start,
                children: [
                  UserImage(
                    imageSize: 50.w,
                    displayName: user.name ?? '',
                    image: EndPoints.getImage(
                      user.profile?.image ?? '',
                    ),
                    frame: user.frame,
                    frameSize: 70.w,
                  ),
                  10.wBox,
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      ConstrainedBox(
                        constraints: BoxConstraints(
                          maxWidth: 170.w,
                          minWidth: 1.w,
                        ),
                        child: GradientTextVip(
                          isVip: hasValidColor,
                          width: 150.w,
                          text: '${user.name}',
                          color: resolvedColor,
                          mainAxisAlignment: MainAxisAlignment.center,
                          textAlign: TextAlign.center,
                          textStyle: context.bodyMedium
                              .size(16)
                              .colorExt(resolvedColor),
                        ),
                      ),
                      3.hBox,
                      Row(
                        children: [
                          if (user.vip != null && user.vip?.level != 0) ...[
                            VipContainer(
                              vip: user.vip?.img1 ?? '',
                              width: 32.w,
                              height: 15.h,
                            ),
                          ],
                          5.wBox,
                          LevelContainer(
                            image: user.level?.senderImage ?? '',
                            height: 30.h,
                            width: 30.w,
                          ),
                          5.wBox,
                          LevelContainer(
                            image: user.level?.receiverImage ?? '',
                            height: 30.h,
                            width: 30.w,
                          ),
                        ],
                      ),
                    ],
                  ),
                  const Spacer(),
                  endIcon ?? const SizedBox()
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
