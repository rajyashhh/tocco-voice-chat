import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/bottom_dialog.dart';
import 'package:general/src/features/auth/auth.dart';
import 'package:general/src/features/games/presentation/games/view/widgets/country_widget.dart';
import 'package:general/src/features/home/presentation/home/bloc/get_carousel_manager/get_carousel_bloc.dart';
import 'package:general/src/features/home/presentation/home/bloc/home_manager/home_bloc.dart';
import 'package:general/src/features/home/presentation/home/view/components/countries_dialog.dart';

/// Shared home country-filter bar (all themes): countries that currently have
/// the most active rooms, flags from the offline library, plus the trailing
/// arrow that opens the full [CountriesDialog]. Tapping a chip filters rooms +
/// banners in place; tapping the selected chip clears the filter.
class TopCountriesBar extends StatefulWidget {
  const TopCountriesBar({super.key});

  @override
  State<TopCountriesBar> createState() => _TopCountriesBarState();
}

class _TopCountriesBarState extends State<TopCountriesBar> {
  @override
  void initState() {
    super.initState();
    final bloc = di<CountriesBloc>();
    if (!bloc.state.categoriesRequestState.isLoaded) {
      bloc.add(const FetchCountryCategoriesEvent());
    }
  }

  void _select(CountryEntity country) {
    di<CountriesBloc>().add(
      SelectedCountryEvent(
        countryId: '${country.id}',
        countryEntity: country,
      ),
    );
    di<HomeBloc>().add(
      FilterPopularRoomsEvent(
        isLoading: false,
        isFirstPage: true,
        countryId: country.id,
      ),
    );
    di<GetCarouselBloc>().add(
      GetCountryCarouselEvent(
        isLoading: false,
        countryId: '${country.id}',
      ),
    );
  }

  void _clear() {
    di<CountriesBloc>().add(
      const SelectedCountryEvent(countryId: '', isNullCountry: true),
    );
    di<HomeBloc>().add(
      const FilterPopularRoomsEvent(
        isLoading: true,
        isFirstPage: true,
        countryId: null,
      ),
    );
    di<GetCarouselBloc>().add(const ResetCountryCarouselEvent());
  }

  @override
  Widget build(BuildContext context) {
    final bloc = di<CountriesBloc>();
    return BlocBuilder<CountriesBloc, CountriesState>(
      bloc: bloc,
      buildWhen: (previous, current) =>
          previous.homeCountries != current.homeCountries ||
          previous.homeCountriesRequestState !=
              current.homeCountriesRequestState ||
          previous.countryEntity != current.countryEntity ||
          previous.countryId != current.countryId,
      builder: (context, state) {
        // The bar is chrome: while loading/error it just collapses instead of
        // spinners/error blocks in the middle of the home page.
        if (state.homeCountries.isEmpty) return const SizedBox.shrink();

        return SizedBox(
          height: 40.h,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Expanded(
                child: ListView.builder(
                  padding: context.paddingZero(),
                  scrollDirection: Axis.horizontal,
                  itemCount: state.homeCountries.length,
                  itemBuilder: (context, index) {
                    final country = state.homeCountries[index];
                    final isSelected = state.countryEntity?.id == country.id &&
                        state.countryId != '';
                    return Center(
                      child: CountryWidget(
                        countryEntity: country,
                        isSelected: isSelected,
                        onTap: () => isSelected ? _clear() : _select(country),
                      ),
                    );
                  },
                ),
              ),
              GestureDetector(
                onTap: () {
                  bottomDailog(
                    context: context,
                    widget: const CountriesDialog(),
                  );
                },
                child: Container(
                  margin: context.paddingOnly(end: 3.0),
                  padding: context.paddingAll(4.0),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: ColorManager.primary,
                  ),
                  child: Center(
                    child: Icon(
                      Icons.arrow_forward_ios_rounded,
                      color: ColorManager.buttonTextColor,
                      size: 12.h,
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}