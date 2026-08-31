import 'package:general/src/features/vip/vip.dart';

class MainPrivilegesItem extends StatelessWidget {
  final String image1;
  final String image2;
  final String title;
  final bool isActive;
  const MainPrivilegesItem({
    super.key,
    required this.image2,
    required this.image1,
    required this.isActive,
    required this.title,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        ImageViewWidget(
          url: isActive ? image1 : image2,
          width: 50.w,
          height: 50.w,
          //color: ColorManager.primary,
        ),
        5.hBox,
        Padding(
          padding: context.paddingSymmetric(horizontal: 5),
          child: TextWidget(
            title,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: context.bodySmall.bold.colorExt(
              ColorManager.defaultTextVip,
            ),
            textAlign: TextAlign.center,
          ),
        ),
      ],
    );
  }
}
