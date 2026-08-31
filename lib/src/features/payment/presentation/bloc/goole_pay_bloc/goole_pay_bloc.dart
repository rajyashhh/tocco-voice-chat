import 'package:general/src/core/index.dart';
import 'package:general/src/features/payment/domain/use_case/google_pay_use_case.dart';
import 'package:general/src/features/payment/presentation/bloc/goole_pay_bloc/goole_pay_event.dart';
import 'package:general/src/features/payment/presentation/bloc/goole_pay_bloc/goole_pay_state.dart';

class GooglePayBloc extends Bloc<BaseGooglePayEvent, GooglePayState> {
  final GooglePayUseCase googlePayUseCase;

  GooglePayBloc({
    required this.googlePayUseCase,
  }) : super(const GooglePayState()) {
    on<GooglePayEvent>((event, emit) async {
      emit(state.copyWith(reqState: RequestState.loading));
      final result = await googlePayUseCase(GooglePayParam(
          purchaseToken: event.purchaseToken, productId: event.productId));

      result.fold(
        (left) => emit(
          state.copyWith(
              error: NetworkExceptions.getErrorMessage(left),
              reqState: handleErrorResponse(left)),
        ),
        (right) {
          emit(
            state.copyWith(
              data: right.data,
              reqState: handleLoadedResponse<String>(right.data),
            ),
          );
        },
      );
    });
  }
}
