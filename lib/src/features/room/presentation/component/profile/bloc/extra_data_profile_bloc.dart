import 'dart:async';
import 'package:general/src/features/room/data/model/extra_profile_data_model.dart';
import 'package:general/src/features/room/domain/use_case/extra_profile_data_uc.dart';
import 'package:general/src/core/index.dart';

part 'extra_data_profile_events.dart';
part 'extra_data_profile_states.dart';

class FetchExtraDataBloc
    extends Bloc<ExtraProfileDataEvent, FetchExtraDataStates> {
  final ExtraProfileDataUc _extraProfileDataUc;

  FetchExtraDataBloc(
    this._extraProfileDataUc,
  ) : super(const FetchExtraDataStates()) {
    on<FetchExtraDataProfile>(_etchExtraDataProfile);
  }

  Future<void> _etchExtraDataProfile(
      FetchExtraDataProfile event, Emitter<FetchExtraDataStates> emit) async {
    emit(state.copyWith(requestState: RequestState.loading));
    try {
      final result = await _extraProfileDataUc.call(event.userId);
      result.fold(
        (left) => emit(state.copyWith(
            requestState: handleErrorResponse(left),
            extraDataMessage: NetworkExceptions.getErrorMessage(left))),
        (right) {
          emit(state.copyWith(
              extraProfileData: right.data,
              requestState:
                  handleLoadedResponse<ExtraProfileDataModel>(right.data)));
        },
      );
    } catch (e) {
      emit(state.copyWith(
        requestState: RequestState.error,
        extraDataMessage: e.toString(),
      ));
    }
  }
}
