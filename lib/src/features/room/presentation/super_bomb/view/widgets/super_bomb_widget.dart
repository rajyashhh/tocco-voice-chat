import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/bottom_dialog.dart';
import 'package:general/src/core/widgets/show_svga.dart';
import 'package:general/src/features/room/presentation/super_bomb/bloc/get_super_bombs_bloc/get_super_bombs_bloc.dart';
import 'package:general/src/features/room/presentation/super_bomb/bloc/get_super_bombs_theme_bloc/get_super_bombs_theme_bloc.dart';
import 'package:general/src/features/room/presentation/super_bomb/view/super_bomb_dialog.dart';
import 'package:general/src/features/room/room.dart';

class SuperBombWidget extends StatelessWidget {
  const SuperBombWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        di<GetSuperBombsBloc>()
            .add(SelectSuperBomb(SuperBoomController.roomBoomLevel.value - 1));
        bottomDailog(
          context: context,
          widget: const SuperBombDialog(),
        );
      },
      child: Builder(builder: (context) {
        final themeState = di<GetSuperBombsThemeBloc>().state;
        final cachedLevelFile = themeState
            .getCachedLevelBoomFile(SuperBoomController.roomBoomLevel.value);
        final cachedFullFile = themeState.getCachedProgressAnimationFile(100);
        return Stack(
          children: [
            ShowSVGA(
              fileCacheSvga: cachedLevelFile,
              height: 80.h,
              width: 80.w,
            ),
            ShowSVGA(
              fileCacheSvga: cachedFullFile,
              height: 80.h,
              width: 80.w,
            ),
          ],
        );
      }),
    );
  }
}
