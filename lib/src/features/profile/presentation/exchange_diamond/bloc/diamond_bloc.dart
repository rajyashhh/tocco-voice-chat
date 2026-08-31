import 'package:general/src/core/index.dart';
import 'package:general/src/features/profile/presentation/coins/bloc/my_store_bloc/my_store_bloc.dart';
import 'package:general/src/features/profile/profile.dart';

part 'diamond_event.dart';

part 'diamond_state.dart';

class DiamondBloc extends Bloc<DiamondEvent, DiamondState> {
  GetReplaceWithGoldUC getReplaceWithGoldUseCase;
  ExchangeDiamondsUC exchangeDiamondsUseCase;
  DiamondBloc({
    required this.getReplaceWithGoldUseCase,
    required this.exchangeDiamondsUseCase,
  }) : super(const DiamondState()) {
    on<GetDiamondDataEvent>(_getDiamondList);
    on<ExchangeDiamondEvent>(_exchangeDiamond);
    on<SelectDiamondEvent>(_selectDiamond);
  }

  void _getDiamondList(
      GetDiamondDataEvent event, Emitter<DiamondState> emit) async {
    if(event.isLoading== true){
      emit(state.copyWith(reqStateDiamond: RequestState.loading));

    }
    final result = await getReplaceWithGoldUseCase.call();

    result.fold(
        (error) => emit(state.copyWith(
            messageDiamond: NetworkExceptions.getErrorMessage(error),
            reqStateDiamond: handleErrorResponse(error))),
        (success) => emit(state.copyWith(
            diamondData: success,
            reqStateDiamond:
                handleLoadedResponse<ReplaceWithGoldModel>(success))));
  }

  void _exchangeDiamond(
      ExchangeDiamondEvent event, Emitter<DiamondState> emit) async {
    emit(state.copyWith(reqStateExchange: RequestState.loading));
    final result = await exchangeDiamondsUseCase.call(event.itemId);
    result.fold(
      (left) {
        emit(state.copyWith(
          messageExchange: NetworkExceptions.getErrorMessage(left),
          reqStateExchange: handleErrorResponse(left)));
          
        Methods.showToast(event.context, message: NetworkExceptions.getErrorMessage(left), isError: true);
      },
      (right) {
        emit(state.copyWith(
            messageExchange: right,
            reqStateExchange: handleLoadedResponse<String>(right)));
        di<MyStoreBloc>().add(EditMyCoinsAndDiamondsLocally(
            coins: state.coins, diamonds: state.diamonds));
        Methods.showToast(event.context, message: StringManager.success.tr(),);
        event.context.popRoute();
      },
    );
  }

  void _selectDiamond(
      SelectDiamondEvent event, Emitter<DiamondState> emit) async {
    emit(state.copyWith(
      itemId: event.itemId,
      diamonds: event.diamonds,
      coins: event.coins,
    ));
  }
}
