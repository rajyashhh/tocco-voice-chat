part of 'package:general/src/features/home/presentation/home/view/home_page.dart';

/// Theme1 home country strip — thin wrapper around the shared
/// [TopCountriesBar] (countries with the most active rooms, offline flags,
/// arrow opens the full dialog).
class CountriesBody extends StatelessWidget {
  final bool? isFromExplore;

  const CountriesBody({
    super.key,
    this.isFromExplore = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: context.paddingSymmetric(horizontal: 10),
      child: const TopCountriesBar(),
    );
  }
}