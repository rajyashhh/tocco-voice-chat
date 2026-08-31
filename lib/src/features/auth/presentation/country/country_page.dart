import 'dart:developer';

import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/auth.dart';
import 'package:general/src/features/chats/chats.dart';

import '../../../profile/presentation/profile/view/page/edit_info_screen/bloc/edit_information/edit_information_bloc.dart';

class CountriesScreen extends StatefulWidget {
  const CountriesScreen({super.key, this.isEditProfile = false});
  final bool isEditProfile;
  static ValueNotifier<int?> countryId = ValueNotifier<int?>(null);

  @override
  State<CountriesScreen> createState() => _CountriesScreenState();
}

class _CountriesScreenState extends State<CountriesScreen> {
  late final TextEditingController searchController;
  final ValueNotifier<List<CountryEntity>> countries =
      ValueNotifier<List<CountryEntity>>([]);
  final CountriesBloc _countriesBloc = di<CountriesBloc>();

  @override
  void initState() {
    super.initState();
    searchController = TextEditingController();
    if (!_countriesBloc.state.requestState.isLoaded) {
      _countriesBloc.add(const FetchCountriesEvent());
    }
  }

  @override
  void dispose() {
    super.dispose();
    searchController.dispose();
  }

  void search(List<CountryEntity> country) {
    countries.value = country
        .where((country) => '${country.name}'
            .toLowerCase()
            .startsWith(searchController.text.trim()))
        .toList();
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        // This prevents going back
        return false;
      },
      child: BlocBuilder<CountriesBloc, CountriesState>(
        bloc: _countriesBloc,
        buildWhen: (prev, curr) => prev.requestState != curr.requestState || prev.countries != curr.countries,
        builder: (context, state) {
          return Scaffold(
            extendBody: true,
            backgroundColor: ColorManager.scaffoldBg,
            appBar: AppBarWidget(
              isShowBack: false,
              title: StringManager.country.tr(),
              backgroundColor: ColorManager.scaffoldBg,
            ),
            body: Column(
              children: [
                TextWidget(
                  StringManager.selectCountry.tr(),
                  padding: context.paddingSymmetric(horizontal: 15),
                  style: context.bodyMedium
                      .copyWith(color: ColorManager.textPrimary),
                ),
                Expanded(
                  child: HandlingDataWidget(
                    reqState: state.requestState,
                    title: StringManager.noCountries.tr(),
                    subTitle: StringManager.noCountriesSubTitle.tr(),
                    onTap: () {
                      _countriesBloc.add(const FetchCountriesEvent());
                      log('qwerty');
                    },
                    child: ValueListenableBuilder<List<CountryEntity>>(
                      valueListenable: countries,
                      builder: (context, countryList, _) {
                        return ListView.builder(
                          shrinkWrap: true,
                          physics: const AlwaysScrollableScrollPhysics(),
                          itemCount: countryList.isEmpty
                              ? state.countries.length
                              : countryList.length,
                          itemBuilder: (context, index) => _buildDefaultCountry(
                            country: countryList.isEmpty
                                ? state.countries[index]
                                : countryList[index],
                          ),
                        );
                      },
                    ),
                  ),
                ),
              ],
            ),
            bottomNavigationBar: Padding(
              padding:
                  context.paddingSymmetric(horizontal: 70.w, vertical: 30.h),
              child: ButtonWidget(
                height: 50.h,
                width: 250.w,
                // elevation: 5,
                backgroundColor: ColorManager.primary,
                onPressed: () {
                  if (widget.isEditProfile == true) {
                    di<EditInformationBloc>().add(
                      const EditInformationEvent(),
                    );
                    return;
                  }
                  if (CountriesScreen.countryId.value != null) {
                    Navigator.pushNamedAndRemoveUntil(
                        context, Routes.addInformation, (_) => false);
                  }
                },
                fontSize: 15.h,
                title: StringManager.save.tr(),
              ),
            ),
          );
        },
      ),
    );
  }

  InkWell _buildDefaultCountry({required CountryEntity country}) {
    return InkWell(
      onTap: () {
        CountriesScreen.countryId.value = country.id;
      },
      child: ValueListenableBuilder<int?>(
        valueListenable: CountriesScreen.countryId,
        builder: (context, selectedCountryId, _) {
          return Container(
            width: ScreenUtil().screenWidth,
            padding: context.paddingSymmetric(horizontal: 15, vertical: 10),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              mainAxisAlignment: MainAxisAlignment.start,
              children: [
                Text(
                  country.name ?? '',
                  style: context.bodyMedium.w600.colorExt(
                      selectedCountryId == country.id
                          ? ColorManager.primary
                          : ColorManager.secondaryText),
                ),
                const Spacer(),
                Container(
                  height: 35.h,
                  width: 35.h,
                  decoration: const BoxDecoration(
                    boxShadow: [],
                  ),
                  child: CountryFlagWidget(
                    iso: country.iso,
                    fallbackUrl: country.photo,
                    height: 35.h,
                    width: 35.h,
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  // Widget _buildSearchBody({
  //   required TextEditingController controller,
  //   required VoidCallback onTap,
  // }) {
  //   return Padding(
  //     padding: context.paddingOnly(
  //       start: 15,
  //       end: 15,
  //       bottom: 20,
  //     ),
  //     child: Row(
  //       children: [
  //         Expanded(
  //           child: TextInputWidget(
  //             enabledBorder:InputBorder.none,
  //             focusedBorder:  InputBorder.none,
  //             border:  InputBorder.none,
  //             errorBorder: InputBorder.none,
  //             StringManager.pleaseInputCountryName.tr(),
  //             textColor: ColorManager.black,
  //             contentPadding: context.paddingSymmetric(horizontal: 15),
  //             controller: controller,
  //             onTap: () {
  //               controller.clear();
  //               countries.value = const [];
  //             },
  //             suffixIcon: CupertinoIcons.clear,
  //             prefixIcon: Padding(
  //               padding: context.paddingAll(10),
  //               child: ImageWidget(
  //                 image: AssetsManager.search,
  //                 color: ColorManager.grey,
  //                 height: 10,
  //                 width: 10,
  //               ),
  //             ),
  //           ),
  //         ),
  //         5.wBox,
  //         TextButton(
  //           onPressed: onTap,
  //           style: TextButton.styleFrom(
  //             padding: context.paddingOnly(bottom: 5),
  //           ),
  //           child: Text(
  //             StringManager.search.tr(),
  //             style: TextStyle(
  //               fontSize: 17.5.sp,
  //               decoration: TextDecoration.underline,
  //               color: ColorManager.secondaryColor,
  //               decorationColor: ColorManager.secondaryColor,
  //               decorationThickness: 2,
  //             ),
  //           ),
  //         ),
  //       ],
  //     ),
  //   );
  // }
}
