import 'package:general/src/core/index.dart';
import 'package:general/src/features/home/presentation/daily_prize/bloc/daily_prizes_bloc.dart';
import 'package:general/src/features/home/presentation/daily_prize/view/daily_prize_item.dart';
import 'package:general/src/features/home/presentation/daily_prize/view/daily_prize_picked.dart';

class DailyPrizeDialog extends StatelessWidget {
  final bool isNeedCompleteInfoDialog;

  const DailyPrizeDialog({super.key, required this.isNeedCompleteInfoDialog});

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<DailyPrizesBloc, DailyPrizesState>(
      bloc: di<DailyPrizesBloc>(),
      listener: (context, state) {
        if (state.requestStateOpenPrize.isLoaded) {
          final entity = state.dailyPrizesEntity;
          final currentDay = entity?.currentDay ?? 0;
          final gifts = entity?.gifts;
          if (gifts != null && currentDay > 0 && currentDay <= gifts.length) {
            Navigator.pop(context);
            showDialog(
              context: context,
              builder: (context) => Dialog(
                backgroundColor: ColorManager.transparent,
                child: DailyPrizePicked(
                  image: gifts[currentDay - 1].gift.image,
                  name: gifts[currentDay - 1].gift.name,
                ),
              ),
            );
          }
          return;
        }

        if (state.requestStateOpenPrize.isError) {
          Methods.showToast(
            context,
            message: state.openPrizeMessage,
            isError: true,
          );
          Navigator.pop(context);

          return;
        }
      },
      builder: (context, state) {
        return HandlingDataWidget(
          reqState: state.requestStateGetPrize,
          title: StringManager.noDataYet.tr(),
          subTitle: StringManager.noDataYet.tr(),
          child: _buildDialogContent(context, state),
        );
      },
    );
  }

  Widget _buildDialogContent(BuildContext context, DailyPrizesState state) {
    return Container(
      // height: ScreenUtil().screenHeight * 0.47,
      width: ScreenUtil().screenWidth,
      decoration: BoxDecoration(
        color: ColorManager.surfaceCardColor,
        borderRadius: 15.radius,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          _buildHeaderText(context, state.dailyPrizesEntity?.totalDays?? 0),
          25.hBox,
          _row(context, state, [1, 2, 3, 4]),
          5.hBox,
          _row(context, state, [5, 6, 7]),
          25.hBox,
          _buildClaimButton(context, state),
          25.hBox,
        ],
      ),
    );
  }

  Widget _buildHeaderText(BuildContext context, int totalDays) {
    // Per-theme header: [primary]/[buttonTextColor] branch on the active
    // variant, so the dialog renders each theme's own palette (golden dark,
    // orange-beige, blue-white, pink) with no per-theme special cases.
    final headerGradient =
        GradientHelper.buildGradientFromRightColor(ColorManager.primary);
    final headerTextColor = ColorManager.buttonTextColor;

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.only(
          topLeft: 15.radiusCircular,
          topRight: 15.radiusCircular,
        ),
        gradient: headerGradient,
      ),
      padding: context.paddingOnly(start: 20, top: 20, end: 20, bottom: 10),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextWidget(
                  textAlign: TextAlign.center,
                  StringManager.weeklySignIn.tr(),
                  style: context.titleLarge.w400.colorExt(headerTextColor),
                ),
                TextWidget(
                  textAlign: TextAlign.center,
                  StringManager.youSignInDay(day: totalDays.toString()),
                  style: context.bodySmall.w600.colorExt(headerTextColor),
                ),
              ],
            ),
            Image.asset(
              AssetsManager.dailyPrize,
              scale: 4.5,
            ),
          ],
        ),
      ),
    );
  }

  Widget _row(
    BuildContext context,
    DailyPrizesState state,
    List<int> days,
  ) {
    return Padding(
      padding: context.paddingSymmetric(horizontal: 15),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: days.map((day) {
          return Expanded(
            flex: day == 7 ? 2 : 1,
            child: _dailyPrize(day, state),
          );
        }).toList(),
      ),
    );
  }

  Widget _dailyPrize(int day, DailyPrizesState state) {
    final entity = state.dailyPrizesEntity;
    final currentDay = entity?.currentDay ?? 0;
    final isReceived = entity?.isReceived ?? false;

    final isThisDay = currentDay == day;
    final isPastDay = currentDay > day;

    final isTodayReceived = isThisDay && isReceived;

    final isPreviousDayReceived = isPastDay;

    final showCheck = isTodayReceived || isPreviousDayReceived;

    final image = entity?.gifts[day - 1].gift.image ?? '';

    return DailyPrizeItem(
      day: day,
      isThisDay: isThisDay,
      isLastDay: isPastDay,
      takenPrize: showCheck,
      image: image,
    );
  }

  Widget _buildClaimButton(BuildContext context, DailyPrizesState state) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        ButtonWidget(
          onPressed: () {
            final entity = state.dailyPrizesEntity;
            final currentDay = entity?.currentDay ?? 0;
            final result = entity?.gifts;

            if (entity?.isReceived == true) {
              Methods.showToast(
                context,
                message: StringManager.receivedGift.tr(),
                isError: true,
              );
            } else if (result != null &&
                currentDay > 0 &&
                currentDay <= result.length) {
              di<DailyPrizesBloc>().add(OpenDailyPrizesEvent());
            } else {
              Methods.showToast(
                context,
                message: StringManager.noGiftsToday.tr(),
                isError: true,
              );
            }
          },
          isLoading: state.requestStateOpenPrize.isLoading,
          title: StringManager.signIn.tr(),
          width: ScreenUtil().screenWidth * 0.4,
          height: 45.h,
          backgroundColor: ColorManager.primary,
          // onDark (white) — the shipped title color on the default golden
          // button, and readable on the theme_1/2/3 saturated primaries too.
          titleColor: ColorManager.onDark,
        ),
      ],
    );
  }
}
