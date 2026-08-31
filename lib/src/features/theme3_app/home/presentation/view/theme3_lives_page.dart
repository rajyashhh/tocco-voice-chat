import 'package:general/src/core/index.dart';
import 'package:general/src/features/home/presentation/home/bloc/home_manager/home_bloc.dart';
import 'package:general/src/features/theme3_app/home/presentation/view/widgets/theme3_lives_body.dart';

/// Standalone "Live" list screen — the same content as the home page's Live
/// chip ([Theme3LivesBody]), reachable from outside the home (e.g. the Reels
/// top bar). Refreshes the live-rooms slice on open. Structurally identical
/// to [Theme2LivesPage] — theme3 chrome (light background/text tokens)
/// instead of the theme2 dark background image.
class Theme3LivesPage extends StatefulWidget {
  const Theme3LivesPage({super.key});

  @override
  State<Theme3LivesPage> createState() => _Theme3LivesPageState();
}

class _Theme3LivesPageState extends State<Theme3LivesPage> {
  final HomeBloc _bloc = di<HomeBloc>();

  @override
  void initState() {
    super.initState();
    _bloc.add(const FetchLiveRoomsEvent());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorManager.theme3Background,
      appBar: AppBar(
        backgroundColor: ColorManager.theme3Background,
        elevation: 0,
        title: TextWidget(
          StringManager.live.tr(),
          style: context.bodyLarge.w600.colorExt(ColorManager.theme3TextPrimary),
        ),
        iconTheme: const IconThemeData(color: ColorManager.theme3TextPrimary),
        centerTitle: true,
      ),
      body: Theme3LivesBody(bloc: _bloc),
    );
  }
}
