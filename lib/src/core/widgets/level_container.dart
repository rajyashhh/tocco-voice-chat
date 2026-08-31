import 'package:general/src/core/index.dart';

class LevelContainer extends StatelessWidget {
  final String? image;
  final int? level;
  final BoxFit? boxFit;
  final double? height;
  final double? width;
  final bool isComment;

  const LevelContainer({
    this.image,
    super.key,
    this.boxFit,
    this.height,
    this.width,
    this.isComment = false,
    this.level,
  });

  @override
  Widget build(BuildContext context) {
    return image == null || (image ?? '').isEmpty
        ? const SizedBox()
        : ImageViewWidget(
            url: image ?? "",
            width: width ?? 40.w,
            height: height ?? 30.h,
            boxFit: boxFit ?? BoxFit.contain,
            radius: 20,
          );
  }
}
