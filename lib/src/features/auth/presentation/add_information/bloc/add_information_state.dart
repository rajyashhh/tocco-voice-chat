part of 'add_information_bloc.dart';

class AddInformationState extends Equatable {
  final TextEditingController name, birthday, country , gender,email;
  final File? image;
  final RequestState requestState;
  final String message;
  final GlobalKey<FormState> formKey;
  final ThirdPartyEntity? kThirdPartyEntity;

  const AddInformationState({
    required this.formKey,
    required this.name,
    required this.birthday,
    required this.email,
    required this.country,
    required this.gender,
    this.image,
    this.requestState = RequestState.idle,
    this.message = '',
    this.kThirdPartyEntity ,
  });

  AddInformationState copyWith({
    String? name,
    String? birthday,
    String? country,
    String? gender,
    String? email,
    RequestState? requestState,
    File? image,
    String? message,
    bool isImageNull = false,
    GlobalKey<FormState>? formKey,
    ThirdPartyEntity? kThirdPartyEntity,

  }) {
    return AddInformationState(
      name: this.name.copyWith(text: name),
      birthday: this.birthday.copyWith(text: birthday),
      country: this.country.copyWith(text: country),
      gender: this.gender.copyWith(text: gender),
      email: this.email.copyWith(text: email),
      requestState: requestState ?? this.requestState,
      message: message ?? this.message,
      image: isImageNull ? null : image ?? this.image,
      formKey: formKey ?? this.formKey,
      kThirdPartyEntity: kThirdPartyEntity ?? this.kThirdPartyEntity,

    );
  }

  @override
  List<Object?> get props => [
        formKey,
        name,
        birthday,
        country,
        gender,
        email,
        image,
        requestState,
        message,
    kThirdPartyEntity
      ];
}
