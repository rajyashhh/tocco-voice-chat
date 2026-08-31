import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/presentation/country/bloc/countries_bloc.dart';
import 'package:general/src/features/room/presentation/gifts/gift.dart';
import 'package:general/src/features/room/presentation/manager/emojie_manager/emojie_bloc.dart';
import 'package:general/src/features/room/presentation/manager/emojie_manager/emojie_event.dart';
import 'package:general/src/features/setting/setting.dart';

part 'widgets/language_screen_item.dart';

class LanguageScreen extends StatefulWidget {
  final bool isAfterSplash;
  const LanguageScreen({super.key, required this.isAfterSplash});

  @override
  State<LanguageScreen> createState() => _LanguageScreenState();
}

class _LanguageScreenState extends State<LanguageScreen> {
  late ValueNotifier<String> selectedLanguageNotifier;
  late List<Map<dynamic, dynamic>> languages;

  @override
  void initState() {
    selectedLanguageNotifier = ValueNotifier<String>(widget.isAfterSplash
        ? ""
        : HiveManager().getData<String>(
                KeysManager.USER_BOX, KeysManager.LANG_CODE_KEY) ??
            "en");
    languages = [
      {
        StringManager.title.tr(): StringManager.english.tr(),
        StringManager.code: 'en'
      },
      {
        StringManager.title.tr(): StringManager.arabic.tr(),
        StringManager.code: 'ar'
      },
      {
        StringManager.title.tr(): StringManager.turkish.tr(),
        StringManager.code: 'tr'
      },
      {
        StringManager.title.tr(): StringManager.urdu.tr(),
        StringManager.code: 'ur'
      },
      {
        StringManager.title.tr(): StringManager.india.tr(),
        StringManager.code: 'hi'
      },
      if (ConstantsManager.isShowIndonesia)
        {
          StringManager.title.tr(): StringManager.indonesia.tr(),
          StringManager.code: 'id'
        },
    ];

    super.initState();
  }

  void _saveLanguage() async {
    final selectedLanguage = selectedLanguageNotifier.value;
    await context.setLocale(Locale(selectedLanguage));
    final languageBloc = di<LanguageBloc>();
    DioFactory().updateLanguageHeader(selectedLanguage);
    languageBloc.add(ChangeLanguageEvent(selectedLanguage));
    if (widget.isAfterSplash) {
      navKey.currentContext?.pushNamedAndRemoveUntil(Routes.onBoardingScreen);
      await Methods.saveLanguageScreen();
    } else {
      context.pushReplacementNamedRoute(Routes.splash);
      di<CountriesBloc>().add(const FetchCountriesEvent());
      di<FetchGiftBloc>().add(const FetchGiftCategoryEvent());
      di<EmojieBloc>().add(const FetchEmojisCategoryEvent());
      // di<GetAgencyBadgesBloc>().add(GetAgencyBadgesEvent());
    }
  }

  @override
  Widget build(BuildContext context) {
    final languageBloc = di<LanguageBloc>();

    return Scaffold(
      backgroundColor: ColorManager.scaffoldBg,
      appBar: AppBarWidget(
        title: StringManager.language.tr(),
        backgroundColor: ColorManager.transparent,
      ),
      body: BlocBuilder<LanguageBloc, LanguageState>(
        bloc: languageBloc,
        buildWhen: (prev, curr) => false,
        builder: (context, state) {
          return ValueListenableBuilder<String>(
            valueListenable: selectedLanguageNotifier,
            builder: (context, selectedLanguage, child) {
              return ListView.builder(
                padding:
                    context.paddingSymmetric(vertical: 10.h, horizontal: 10.w),
                itemCount: languages.length,
                itemBuilder: (context, index) {
                  return LanguageScreenItem(
                    title: languages[index][StringManager.title.tr()] ?? '',
                    currentValue: selectedLanguageNotifier.value ==
                        languages[index][StringManager.code],
                    onChanged: (value) async {
                      if (value) {
                        currentIndex = 0;
                        selectedLanguageNotifier.value =
                            languages[index][StringManager.code] ?? '';
                        _saveLanguage();
                      }
                    },
                  );
                },
              );
            },
          );
        },
      ),
    );
  }
}
