import 'dart:io';

import 'package:file_picker/file_picker.dart';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/auth.dart';
import 'package:general/src/features/auth/domain/entities/third_party_entity.dart';

part 'add_information_event.dart';

part 'add_information_state.dart';

class AddInformationBloc
    extends Bloc<BaseAddInformationEvent, AddInformationState> {
  final AddInfoUc addInfoUc;

  AddInformationBloc({required this.addInfoUc})
      : super(
          AddInformationState(
            formKey: GlobalKey<FormState>(),
            name: TextEditingController(),
            birthday: TextEditingController(),
            country: TextEditingController(),
            gender: TextEditingController(),
            email: TextEditingController(),
          ),
        ) {
    on<UsernameEvent>(_usernameEvent);
    on<EmileEvent>(_emailEvent);
    on<SelectedGenderEvent>(_genderEvent);
    on<SelectedBirthdayEvent>(_birthdayEvent);
    on<AddInformationEvent>(_addInformation);
    on<PickImageEvent>(_pickFileEvent);
    on<UserImageEvent>(_userImageEvent);
    on<ThirdPartyEvent>(_kThirdPartyEvent);
  }

  // Events

  void _kThirdPartyEvent(
      ThirdPartyEvent event, Emitter<AddInformationState> emit) {
    emit(
      state.copyWith(kThirdPartyEntity: event.kThirdPartyEntity),
    );
  }

  void _usernameEvent(
    UsernameEvent event,
    Emitter<AddInformationState> emit,
  ) =>
      emit(state.copyWith(name: event.name));

  void _userImageEvent(
    UserImageEvent event,
    Emitter<AddInformationState> emit,
  ) =>
      emit(state.copyWith(image: event.image));

  void _emailEvent(
    EmileEvent event,
    Emitter<AddInformationState> emit,
  ) =>
      emit(state.copyWith(name: event.emile));

  void _genderEvent(
    SelectedGenderEvent event,
    Emitter<AddInformationState> emit,
  ) =>
      emit(state.copyWith(gender: event.gender));

  void _birthdayEvent(
    SelectedBirthdayEvent event,
    Emitter<AddInformationState> emit,
  ) {
    if (event.dateTime == null) return;
    final String formattedDate =
        DateFormat('yyyy-MM-dd').format(event.dateTime ?? DateTime.now());
    emit(state.copyWith(birthday: formattedDate));
  }

  Future<void> _pickFileEvent(
    PickImageEvent event,
    Emitter<AddInformationState> emit,
  ) async {
    try {
      final result = await FilePicker.pickFiles(
        type: FileType.image,
        allowMultiple: false,
      );

      if (result != null && result.files.isNotEmpty) {
        final path = result.files.single.path!;
        final file = File(path);

        final compressed =
            await Methods().compressFile(xFile: XFile(file.path));
        final imageFile = File(compressed.path);

        emit(state.copyWith(image: imageFile));
      } else {
        emit(state.copyWith(isImageNull: true));
      }
    } catch (e) {
      emit(state.copyWith(isImageNull: true));
    }
  }

  Future<void> _addInformation(
    AddInformationEvent event,
    Emitter<AddInformationState> emit,
  ) async {
    if (state.formKey.currentState?.validate() == false) {
      return;
    }
    emit(
      state.copyWith(requestState: RequestState.loading),
    );

    String uuid = await Methods().signInAnonymously();

    final result = await addInfoUc(
      InformationParametersUC(
        name: state.name.text,
        image: state.image,
        email: state.email.text,
        gender: state.gender.text == StringManager.male.tr() ? 1 : 0,
        date: Methods().convertNumerals(state.birthday.text, toEnglish: true),
        // countryId: CountriesScreen.countryId.value,
        uuid: uuid,
        isUpdateOnlyUid: event.isUpdateOnlyUid,
      ),
    );

    result.fold(
      (left) {
        emit(
          state.copyWith(
            requestState: RequestState.error,
            message: NetworkExceptions.getErrorMessage(left),
          ),
        );
        Methods.showToast(event.context, message: state.message, isError: true);
      },
      (right) {
        Methods.identifyUserForCrashlytics();
        emit(
          state.copyWith(
              requestState: RequestState.loaded, message: right.message),
        );
        if (event.isNavLayout == true) {
          Methods.showToast(event.context, message: state.message);
          event.context.pushNamedAndRemoveUntil(Routes.layout);
        }
      },
    );
  }

  @override
  Future<void> close() {
    state.name.dispose();
    state.birthday.dispose();
    state.country.dispose();
    state.gender.dispose();
    state.email.dispose();
    return super.close();
  }
}
