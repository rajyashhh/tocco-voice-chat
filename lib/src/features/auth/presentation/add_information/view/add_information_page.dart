import 'dart:io';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/auth.dart';
import 'package:general/src/features/auth/domain/entities/third_party_entity.dart';

part 'components/form_add_info_body.dart';

part 'components/pick_image_body.dart';

class AddInformationPage extends StatefulWidget {
  const AddInformationPage({super.key});

  @override
  State<AddInformationPage> createState() => _AddInformationPageState();
}

class _AddInformationPageState extends State<AddInformationPage> {

  ThirdPartyEntity? kThirdPartyEntity;

  Future<void> _google() async {
    if ('${kThirdPartyEntity?.type}' == "google") {
      if (kThirdPartyEntity?.data.googleID.displayName != null) {
        di<AddInformationBloc>().add(
          UsernameEvent(
            name: kThirdPartyEntity?.data.googleID.displayName,
          ),
        );
      }

      if (kThirdPartyEntity?.data.googleID.photoUrl != null) {
        final File image = await Methods()
            .getImageFileFromNetwork(kThirdPartyEntity?.data.googleID.photoUrl);
        di<AddInformationBloc>().add(
          UserImageEvent(image: image),
        );
      }
    }
  }

  @override
  void initState() {
    super.initState();
    kThirdPartyEntity = di<AddInformationBloc>().state.kThirdPartyEntity;
    if (kThirdPartyEntity != null) {
      _google();
      // if ('${kThirdPartyEntity?.type}' == "apple") {
      //   if (kThirdPartyEntity?.data.givenName != null) {}
      // }
      // if ('${kThirdPartyEntity?.type}' == "huawei") {
      //   if (kThirdPartyEntity?.data.givenName != null) {}
      // }
    }
  }


  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorManager.scaffoldBg,
      body: WillPopScope(
        onWillPop: () async {
          // This prevents going back
          return false;
        },
        child: SafeArea(
          child: SingleChildScrollView(
            child: BlocBuilder<AddInformationBloc, AddInformationState>(
              buildWhen: (prev, curr) => prev != curr,
              builder: (__, state) {
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    30.hBox,
                    TextWidget(
                      StringManager.hiTemp.tr(),
                      padding: context.paddingSymmetric(horizontal: 10),
                      style: context.bodyMedium.bold.size(22).colorExt(ColorManager.textPrimary),
                    ),
                    2.hBox,
                    TextWidget(
                      StringManager.improveTheInfo2.tr(),
                      padding: context.paddingSymmetric(horizontal: 10),
                      textAlign: TextAlign.center,
                      style: context.bodySmall.w500.colorExt(ColorManager.textPrimary).size(14),
                    ),
                    40.hBox,
        
        
        
                    // TextWidget(
                    //   state.name.text,
                    //   style: context.bodyMedium.size(16).colorExt(ColorManager.textPrimary),
                    // ),
        
                    _FormAddInfoBody(state: state),
                  ],
                );
              },
            ),
          ),
        ),
      ),
    );
  }
}
/* Row(
                    children: [
                      Expanded(
                        child: GenderPickerWidget(
                          controller: __.gender,
                          onChanged: (value) {
                            di<AddInformationBloc>().add(
                              SelectedGenderEvent(
                                gender: value == 0
                                    ? StringManager.male.tr()
                                    : StringManager.female.tr(),
                              ),
                            );
                          },
                        ),
                      ),
                      16.wBox,
                      Expanded(
                        child: DatePickerWidget(
                          controller: __.birthday,
                          dateTime: __.birthday.text.isNotEmpty
                              ? DateTime.parse(
                                  Methods().convertNumerals(__.birthday.text,
                                      toEnglish: true),
                                )
                              : null,
                          onDateTimeChanged: (value) => context
                              .read<AddInformationBloc>()
                              .add(SelectedBirthdayEvent(dateTime: value)),
                        ),
                      ),
                    ],
                  ),*/
