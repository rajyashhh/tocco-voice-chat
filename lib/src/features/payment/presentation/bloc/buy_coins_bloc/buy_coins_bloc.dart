import 'package:general/src/core/index.dart';
import 'package:general/src/features/payment/domain/use_case/buy_coins_use_case.dart';
import 'package:general/src/features/payment/domain/use_case/redirect_link_use_case.dart';
import 'package:general/src/features/payment/presentation/bloc/buy_coins_bloc/buy_coins_event.dart';
import 'package:general/src/features/payment/presentation/bloc/buy_coins_bloc/buy_coins_state.dart';
import 'package:url_launcher/url_launcher.dart';

class BuyCoinsBloc extends Bloc<BaseBuyCoinsEvent, BuyCoinsState> {
  final BuyCoinsUseCase buyCoinsUseCase;
  final RedirectLinkUseCase redirectLinkUseCase;

  BuyCoinsBloc({
    required this.buyCoinsUseCase,
    required this.redirectLinkUseCase,
  }) : super(const BuyCoinsState()) {
    on<BuyCoinsEvent>((event, emit) async {
      emit(state.copyWith(reqState: RequestState.loading));
      final result = await buyCoinsUseCase(
          BuyCoinsParam(method: event.method, productId: event.productId));

      result.fold(
        (left) {
          emit(
            state.copyWith(
                error: NetworkExceptions.getErrorMessage(left),
                reqState: handleErrorResponse(left)),
          );
          Methods.showToast(event.context, message: state.error, isError: true);
        },
        (right) async {
          emit(
            state.copyWith(
              data: right.data,
              reqState: handleLoadedResponse<String>(right.data),
            ),
          );
          final uri = Uri.tryParse(right.data.toString());
          final bool launched = uri != null &&
              (uri.isScheme('http') || uri.isScheme('https')) &&
              await canLaunchUrl(uri) &&
              await launchUrl(uri, mode: LaunchMode.externalApplication);
          if (!launched) {
            Methods.showToast(
              event.context,
              message: StringManager.someThingWentWrong.tr(),
              isError: true,
            );
          }
        },
      );
    });
    on<ChangeValueEvent>((event, emit) async {
      emit(state.copyWith(
          selectedTab: event.value,
          controllerIndex: event.controllerIndex,
          changeText: event.changeText));
    });

    on<RedirectLinkEvent>(
      (event, emit) async {
        emit(state.copyWith(reqStateRedirect: RequestState.loading));
        final result = await redirectLinkUseCase(event.link ?? '');

        result.fold(
          (left) {
            emit(
              state.copyWith(
                  errorRedirect: NetworkExceptions.getErrorMessage(left),
                  reqStateRedirect: handleErrorResponse(left)),
            );
          },
          (right) async {
            emit(
              state.copyWith(
                errorRedirect: right.data,
                reqStateRedirect: handleLoadedResponse<String>(right.data),
              ),
            );
          },
        );
      },
    );
  }
}
