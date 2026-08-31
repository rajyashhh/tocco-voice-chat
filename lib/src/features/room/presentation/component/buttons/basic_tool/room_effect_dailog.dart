import 'package:flutter/cupertino.dart';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/domain/entities/close_effect_entity.dart';
import 'package:general/src/features/room/presentation/manager/clear_mode_manager/clear_mode_bloc.dart';
import 'package:general/src/features/room/presentation/manager/clear_mode_manager/clear_mode_event.dart';
import 'package:general/src/features/room/presentation/manager/clear_mode_manager/clear_mode_state.dart';

class RoomEffectDailog extends StatelessWidget {
  const RoomEffectDailog({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: ScreenUtil().screenHeight * 0.45,
      width: ScreenUtil().screenWidth,
      decoration: BoxDecoration(
        color: const Color(0xff090810),
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(15.r),
          topRight: Radius.circular(15.r),
        ),
      ),
      child: BlocConsumer<ClearModeBloc, ClearModeState>(
        bloc: di<ClearModeBloc>(),
        listener: (context, state) {
          if (state is ClearModeSuccessState) {
            final roomEffects = MyDataModel.getInstance().roomEffects;
            if (roomEffects != null) {
              roomEffects.showBanner = state.data.showBanner;
              roomEffects.showGift = state.data.showGift;
              roomEffects.showEntring = state.data.showEntring;
            }
          }
        },
        builder: (context, state) {
          if (state is ClearModeSuccessState) {
            return roomEffectBody(context, state.data);
          }
          final roomEffects = MyDataModel.getInstance().roomEffects;
          if (roomEffects == null) {
            return const Center(child: CircularProgressIndicator(color: ColorManager.roomGold));
          }
          return roomEffectBody(context, roomEffects);
        },
      ),
    );
  }

  Widget roomEffectBody(BuildContext context, CloseEffectEntity data) {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          8.hBox,
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 15.0.h),
            child: Row(
              children: [
                Text(
                  StringManager.blockGift.tr(),
                  style: context.bodyMedium.w400.colorExt(
                    ColorManager.whiteColor,
                  ),
                ),
                const Spacer(),
                Transform.scale(
                  scaleY: 0.9,
                  child: CupertinoSwitch(
                    activeTrackColor: ColorManager.roomGold,
                    inactiveTrackColor: ColorManager.scaffoldBackgroundColor,
                    value: data.showGift ?? false,
                    onChanged: (value) {
                      di<ClearModeBloc>()
                          .add(const ClearModeEvent(key: "show_git"));
                    },
                  ),
                ),
              ],
            ),
          ),
          5.hBox,
          Container(
            width: ScreenUtil().screenWidth,
            color: const Color(0xff111018),
            padding:
                context.paddingOnly(start: 15, end: 15, top: 10, bottom: 30),
            child: Text(
              StringManager.giftEffect.tr(),
              style: context.bodySmall.size(10).colorExt(
                    ColorManager.whiteColor.withValues(
                      alpha: (0.6),
                    ),
                  ),
            ),
          ),
          5.hBox,
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 15.0.h),
            child: Row(
              children: [
                Text(
                  StringManager.entries.tr(),
                  style: context.bodyMedium.w400.colorExt(
                    ColorManager.whiteColor,
                  ),
                ),
                const Spacer(),
                Transform.scale(
                  scaleY: 0.9,
                  child: CupertinoSwitch(
                    activeTrackColor: ColorManager.roomGold,
                    inactiveTrackColor: ColorManager.scaffoldBackgroundColor,
                    value: data.showEntring ?? false,
                    onChanged: (value) {
                      di<ClearModeBloc>().add(
                        const ClearModeEvent(key: "show_intro"),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
          5.hBox,
          Container(
            width: ScreenUtil().screenWidth,
            color: const Color(0xff111018),
            padding: context.paddingOnly(
              start: 15,
              end: 15,
              top: 10,
              bottom: 30,
            ),
            child: Text(
              StringManager.entriesEffect.tr(),
              style: context.bodySmall.size(10).colorExt(
                    ColorManager.whiteColor.withValues(alpha: (0.6)),
                  ),
            ),
          ),
          5.hBox,
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 15.0.h),
            child: Row(
              children: [
                Text(
                  StringManager.banners.tr(),
                  style: context.bodyMedium.w400.colorExt(
                    ColorManager.whiteColor,
                  ),
                ),
                const Spacer(),
                Transform.scale(
                  scaleY: 0.9,
                  child: CupertinoSwitch(
                    activeTrackColor: ColorManager.roomGold,
                    inactiveTrackColor: ColorManager.scaffoldBackgroundColor,
                    value: data.showBanner ?? false,
                    onChanged: (value) {
                      di<ClearModeBloc>().add(
                        const ClearModeEvent(key: "show_banner"),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
          5.hBox,
          Container(
            width: ScreenUtil().screenWidth,
            color: const Color(0xff111018),
            padding: context.paddingOnly(
              start: 15,
              end: 15,
              top: 10,
              bottom: 30,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  StringManager.bannersEffect.tr(),
                  style: context.bodySmall.size(10).colorExt(
                        ColorManager.white.withValues(alpha: (0.6)),
                      ),
                ),
                5.hBox,
                Text(
                  StringManager.bannersEffectAlert.tr(),
                  style: context.bodySmall
                      .size(10)
                      .colorExt(ColorManager.redIndicator),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
