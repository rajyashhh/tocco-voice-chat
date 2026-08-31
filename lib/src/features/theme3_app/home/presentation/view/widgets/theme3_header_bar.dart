import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/on_multiable_tab.dart';
import 'package:general/src/features/home/presentation/home/bloc/fetch_my_room_data_manager/fetch_my_room_data_bloc.dart';
import 'package:general/src/features/home/presentation/home/bloc/fetch_my_room_data_manager/fetch_my_room_data_state.dart';
import 'package:general/src/features/live_room/presentation/go_live_flow.dart';
import 'package:general/src/features/room/room.dart';

/// Theme3 (NEXO) home header — app name top-start, trophy (ranking) + go-live
/// (start room) + search top-end. Notifications live in the Chat tab, not
/// here. Go-live wiring (bloc/route) is copied unchanged from
/// [Theme2HeaderBar]'s `_handleRoomEntry` — only the icon/color differ.
class Theme3HeaderBar extends StatelessWidget {
  const Theme3HeaderBar({super.key});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsetsDirectional.only(start: 16.w, end: 6.w, top: 4.h),
      child: Row(
        children: [
          Expanded(
            child: TextWidget(
              'Tocco Voice Live',
              style: context.bodyMedium.bold
                  .size(22)
                  .colorExt(ColorManager.theme3TextPrimary),
            ),
          ),
          IconButton(
            onPressed: () =>
                Navigator.pushNamed(context, Routes.rankScreen, arguments: 1),
            icon: Icon(
              Icons.emoji_events,
              color: ColorManager.theme3Cta,
              size: 26.h,
            ),
          ),
          if (ConstantsManager.isAudioRoomsEnabled || ConstantsManager.isShowLive)
            BlocBuilder<FetchMyRoomDataBloc, FetchMyRoomDataState>(
              bloc: di<FetchMyRoomDataBloc>(),
              buildWhen: (prev, curr) => prev.requestState != curr.requestState,
              builder: (context, state) {
                return state.requestState.isLoaded
                    ? MultiTapCard(
                        onTap: () => _handleRoomEntry(context, state),
                        child: Padding(
                          padding: EdgeInsets.symmetric(horizontal: 4.w),
                          child: Icon(
                            Icons.videocam,
                            color: ColorManager.theme3Cta,
                            size: 26.h,
                          ),
                        ),
                      )
                    : const SizedBox.shrink();
              },
            ),
          IconButton(
            onPressed: () => Navigator.pushNamed(context, Routes.searchScreen),
            icon: Icon(
              Icons.search,
              color: ColorManager.theme3TextPrimary,
              size: 26.h,
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _handleRoomEntry(
      BuildContext context, FetchMyRoomDataState state) async {
    if (RoomData.instance.utdController?.minimize.isMinimizing ?? false) {
      final navContext = SafeNavigator.context;
      if (navContext == null) return;
      await di<RoomStateManager>().exitRoom(navContext);
    }

    if (ConstantsManager.isShowLive) {
      if (di<RoomStateManager>().isInRoom) {
        final navContext = SafeNavigator.context;
        if (navContext == null) return;
        await di<RoomStateManager>().exitRoom(
          navContext,
          callback: () {
            GoLiveFlow.showStartDialog(context, state.rooms);
          },
        );
      } else {
        GoLiveFlow.showStartDialog(context, state.rooms);
      }
    } else {
      Methods.handleRoomEntry(context, state.rooms?.audio);
    }
  }
}
