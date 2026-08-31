import 'package:general/src/core/index.dart';

void showCustomLoadingDialog(BuildContext context) {
  showDialog(
    context: context,
    barrierDismissible: false,
    barrierColor: ColorManager.transparent,
    builder: (_) => const Center(child: _LoadingDialog()),
  );
}

class _LoadingDialog extends StatelessWidget {
  const _LoadingDialog();

  @override
  Widget build(BuildContext context) {
    return ConstantsManager.isTheme1
        ? const LoadingView()
        : Center(
            child: Container(
              width: 120.w,
              padding: context.paddingAll(17.5),
              decoration: BoxDecoration(
                color: ColorManager.black,
                borderRadius: 8.radius,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const LoadingWidget(),
                  15.hBox,
                  TextWidget(
                    StringManager.loading,
                    style: context.bodyMedium
                        .size(14)
                        .w600
                        .colorExt(ColorManager.onDark),
                  ),
                ],
              ),
            ),
          );
  }
}
