import 'dart:io';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/room/presentation/component/seat_config/seat_metrics.dart';
import 'package:general/src/features/room/room.dart';

class EmptySeat extends StatelessWidget {
  final int index;

  /// The full seat slot size provided by the audio-room kit. The image is sized
  /// to [SeatMetrics.avatar] — the SAME shared source the occupied avatar and
  /// the locked seat use — so all seat states match exactly. It is wrapped in
  /// [Flexible] so it shrinks when the slot is too small (e.g. seat8 mode) to
  /// leave room for the index label — otherwise the column overflows the tight
  /// square the kit lays the seat out in.
  final double size;

  const EmptySeat({super.key, required this.index, required this.size});

  @override
  Widget build(BuildContext context) {
    final m = SeatMetrics.forSeat(size);
    return ValueListenableBuilder<bool>(
      valueListenable: PkController.showPK,
      builder: (context, isPK, _) {
        if (isPK) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              Flexible(
                child: AspectRatio(
                  aspectRatio: 1,
                  child: SizedBox(
                    width: m.avatar,
                    height: m.avatar,
                    child:
                        (PkController.isPK.value &&
                                PkController.isTeam1Seat(index))
                            ? Image.asset(
                              AssetsManager.team1,
                              fit: BoxFit.contain,
                            )
                            : (PkController.isPK.value &&
                                PkController.isTeam2Seat(index))
                            ? Image.asset(
                              AssetsManager.team2,
                              fit: BoxFit.contain,
                            )
                            : Container(
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                color: ColorManager.onDark.withValues(alpha: 0.4),
                              ),
                              child: Center(
                                child: Image.asset(
                                  AssetsManager.seat,
                                  color: ColorManager.onDark.withValues(alpha: 0.5),
                                  scale: 17,
                                ),
                              ),
                            ),
                  ),
                ),
              ),
              Text(
                "${index + 1}",
                textAlign: TextAlign.center,
                style: context.bodyMedium
                    .copyWith(fontSize: m.indexFont)
                    .w400
                    .colorExt(ColorManager.onDark),
              ),
            ],
          );
        } else {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              Flexible(
                child: AspectRatio(
                  aspectRatio: 1,
                  child: SizedBox(
                    width: m.avatar,
                    height: m.avatar,
                    child: Container(
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: ColorManager.onDark.withValues(alpha: 0.4),
                      ),
                      child:
                          Methods.getMicImageOpen() != null
                              ? Image.file(
                                File(Methods.getMicImageOpen() ?? ''),
                                fit: BoxFit.cover,
                              )
                              : Image.asset(
                                AssetsManager.seat,
                                color: ColorManager.onDark.withValues(alpha: 0.5),
                              ),
                    ),
                  ),
                ),
              ),
              Text(
                "${index + 1}",
                textAlign: TextAlign.center,
                style: context.bodyMedium
                    .copyWith(fontSize: m.indexFont)
                    .w400
                    .colorExt(ColorManager.onDark),
              ),
            ],
          );
        }
      },
    );
  }
}
