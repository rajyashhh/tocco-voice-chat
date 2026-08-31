import 'package:loading_animation_widget/loading_animation_widget.dart';
import 'package:lottie/lottie.dart';
import 'package:general/src/core/index.dart';

class HandlingDataWidget extends StatelessWidget {
  const HandlingDataWidget({
    super.key,
    required this.reqState,
    this.childEmpty,
    required this.title,
    required this.subTitle,
    this.onTap,
    this.isBlock,
    this.buttonTitle,
    this.titleStyle,
    this.subTitleStyle,
    this.isLoadingCenter,
    this.isNeedLoadingWidget = true,
    this.accentColor,
    required this.child,
  });

  final RequestState reqState;
  final Widget child;
  final Widget? childEmpty;
  final String? isBlock;
  final String? buttonTitle;
  final String title, subTitle;
  final VoidCallback? onTap;
  final TextStyle? titleStyle;
  final TextStyle? subTitleStyle;
  final bool? isNeedLoadingWidget;
  final bool? isLoadingCenter;

  /// Accent override for the loading spinner and the retry-button border.
  /// Room/live screens pass the theme-independent [ColorManager.roomGold] so
  /// in-room loading/error states never follow the app theme (owner rule);
  /// null keeps the theme-driven [ColorManager.primary] default.
  final Color? accentColor;
  @override
  Widget build(BuildContext context) {
    if (reqState.isLoading) {
      return (isNeedLoadingWidget == false)
          ? child
          : LoadingView(isLoadingCenter: isLoadingCenter, color: accentColor);
    } else if (reqState.isError) {
      return ErrorView(
        onTap: onTap,
        isBlock: isBlock,
        subTitleStyle: subTitleStyle,
        titleStyle: titleStyle,
        accentColor: accentColor,
      );
    } else if (reqState.isOffline) {
      return _OfflineView(
        onTap: onTap,
        subTitleStyle: subTitleStyle,
        titleStyle: titleStyle,
        accentColor: accentColor,
      );
    } else if (reqState.userBan) {
      return BanUserWidget(
        onTap: onTap,
        subTitleStyle: subTitleStyle,
        titleStyle: titleStyle,
        title: title,
        message: subTitle,
      );
    } else if (reqState.isEmpty) {
      return childEmpty ??
          EmptyView(
            title: title,
            subTitle: subTitle,
            onTap: onTap,
            subTitleStyle: subTitleStyle,
            titleStyle: titleStyle,
            buttonTitle: buttonTitle,
            accentColor: accentColor,
          );
    } else {
      return child;
    }
  }
}

class LoadingView extends StatelessWidget {
  const LoadingView({super.key, this.isLoadingCenter, this.color});

  final bool? isLoadingCenter;

  /// Spinner color override — see [HandlingDataWidget.accentColor]. When set
  /// (room/live screens) the theme_1 special loading view is bypassed too, so
  /// the in-room loading state is identical under every theme.
  final Color? color;
  @override
  Widget build(BuildContext context) {
    return color == null && ConstantsManager.isTheme1
        ? _NewLoadingView(isLoadingCenter: isLoadingCenter)
        : isLoadingCenter ?? false
            ? SizedBox(
                height: 320.h,
                child: LoadingAnimationWidget.staggeredDotsWave(
                  color: color ?? ColorManager.primary,
                  size: 30.h,
                ),
              )
            : Center(
                child: LoadingAnimationWidget.staggeredDotsWave(
                  color: color ?? ColorManager.primary,
                  size: 30.h,
                ),
              );
  }
}

class _NewLoadingView extends StatelessWidget {
  const _NewLoadingView({required this.isLoadingCenter});

  final bool? isLoadingCenter;

  @override
  Widget build(BuildContext context) {
    final content = Container(
      width: 45.w,
      height: 45.h,
      decoration: BoxDecoration(
        color: ColorManager.scaffoldBg,
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: ColorManager.black.withValues(alpha: 0.1),
            blurRadius: 20,
            spreadRadius: 0,
            offset: const Offset(0, 4),
          ),
          BoxShadow(
            color: ColorManager.black.withValues(alpha: 0.05),
            blurRadius: 10,
            spreadRadius: 0,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Center(
        child: SizedBox(
          width: 22.5.w,
          height: 22.5.h,
          child: CircularProgressIndicator.adaptive(
            strokeWidth: 2,
            valueColor: AlwaysStoppedAnimation<Color>(
              ColorManager.textPrimary,
            ),
          ),
        ),
      ),
    );

    return isLoadingCenter ?? false
        ? SizedBox(
            height: 320.h,
            child: Center(child: content),
          )
        : Center(child: content);
  }
}

class ErrorView extends StatelessWidget {
  const ErrorView({
    super.key,
    this.onTap,
    this.isBlock,
    this.subTitleStyle,
    this.titleStyle,
    this.accentColor,
  });
  final VoidCallback? onTap;
  final String? isBlock;
  final TextStyle? titleStyle;
  final TextStyle? subTitleStyle;
  final Color? accentColor;

  @override
  Widget build(BuildContext context) {
    return ErrorOrEmptyWidget(
      image: AssetsManager.error,
      title: StringManager.someWrong.tr(),
      message: isBlock ?? StringManager.someThingWrong.tr(),
      onTap: onTap,
      titleStyle: titleStyle,
      subTitleStyle: subTitleStyle,
      accentColor: accentColor,
    );
  }
}

class _OfflineView extends StatelessWidget {
  const _OfflineView({
    this.onTap,
    this.subTitleStyle,
    this.titleStyle,
    this.accentColor,
  });

  final VoidCallback? onTap;
  final TextStyle? titleStyle;
  final TextStyle? subTitleStyle;
  final Color? accentColor;

  @override
  Widget build(BuildContext context) {
    return ErrorOrEmptyWidget(
      image: AssetsManager.noWifi,
      title: StringManager.noConnection.tr(),
      message: StringManager.unableToConnect.tr(),
      onTap: onTap,
      titleStyle: titleStyle,
      subTitleStyle: subTitleStyle,
      accentColor: accentColor,
    );
  }
}

class EmptyView extends StatelessWidget {
  const EmptyView({
    super.key,
    this.onTap,
    required this.title,
    this.titleStyle,
    this.subTitleStyle,
    this.buttonTitle,
    this.accentColor,
    required this.subTitle,
  });
  final String title, subTitle;
  final String? buttonTitle;
  final VoidCallback? onTap;
  final TextStyle? titleStyle;
  final TextStyle? subTitleStyle;
  final Color? accentColor;

  @override
  Widget build(BuildContext context) {
    return ErrorOrEmptyWidget(
      image: AssetsManager.empty,
      title: tr(title),
      message: tr(subTitle),
      onTap: onTap,
      titleStyle: titleStyle,
      subTitleStyle: subTitleStyle,
      buttonTitle: buttonTitle,
      accentColor: accentColor,
    );
  }
}

class ErrorOrEmptyWidget extends StatelessWidget {
  const ErrorOrEmptyWidget({
    super.key,
    required this.image,
    required this.title,
    required this.message,
    this.onTap,
    this.size,
    this.titleStyle,
    this.subTitleStyle,
    this.buttonTitle,
    this.buttonColor,
    this.accentColor,
  });

  final String image;
  final String? buttonTitle;
  final String title;
  final String message;
  final VoidCallback? onTap;
  final double? size;
  final TextStyle? titleStyle;
  final TextStyle? subTitleStyle;
  final Color? buttonColor;
  final Color? accentColor;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: context.paddingOnly(start: 10, end: 10),
      child: Align(
        alignment: AlignmentDirectional.center,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            if (image.isNotEmpty)
              Padding(
                padding: context.paddingOnly(
                    end: image == AssetsManager.loading ? 15 : 0),
                child: Lottie.asset(
                  image,
                  height: size?.h ?? 90.h,
                  width: size?.w ?? 90.w,
                ),
              ),
            TextWidget(
              title,
              textAlign: TextAlign.center,
              style: titleStyle ??
                  context.bodyLarge.w600.colorExt(accentColor != null
                      ? ColorManager.roomTextPrimary
                      : ColorManager.textPrimary),
            ),
            5.hBox,
            TextWidget(
              message,
              textAlign: TextAlign.center,
              style: subTitleStyle ??
                  context.bodyMedium.copyWith(
                    color: accentColor != null
                        ? ColorManager.roomSecondaryText
                        : ColorManager.secondaryText,
                    height: 1.30,
                  ),
            ),
            if (onTap != null) ...[
              30.hBox,
              Padding(
                padding: context.paddingSymmetric(horizontal: 30),
                child: ButtonWidget(
                  title: buttonTitle ?? StringManager.refresh.tr(),
                  backgroundColor: buttonColor ?? ColorManager.transparent,
                  titleColor: accentColor != null
                      ? ColorManager.roomTextPrimary
                      : ColorManager.textPrimary,
                  borderColor: accentColor ?? ColorManager.primary,
                  fontWeight: FontWeight.w400,
                  onPressed: onTap ?? () {},
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class BanUserWidget extends StatelessWidget {
  const BanUserWidget({
    super.key,
    required this.title,
    required this.message,
    this.onTap,
    this.size,
    this.titleStyle,
    this.subTitleStyle,
    this.buttonTitle,
    this.buttonColor,
  });

  final String? buttonTitle;
  final String title;
  final String message;
  final VoidCallback? onTap;
  final double? size;
  final TextStyle? titleStyle;
  final TextStyle? subTitleStyle;
  final Color? buttonColor;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: context.paddingOnly(start: 10, end: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        mainAxisAlignment: MainAxisAlignment.center,
        mainAxisSize: MainAxisSize.min,
        children: [
          Image.asset(
            AssetsManager.ban,
            height: size?.h ?? 90.h,
            width: size?.w ?? 90.w,
            // fit: BoxFit.cover,
          ),
          10.hBox,
          TextWidget(
            title,
            textAlign: TextAlign.center,
            style:
                titleStyle ?? context.bodyLarge.w600.colorExt(ColorManager.red),
          ),
          5.hBox,
        ],
      ),
    );
  }
}
