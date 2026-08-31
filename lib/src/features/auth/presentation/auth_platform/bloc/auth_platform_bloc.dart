import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/auth.dart';
import 'package:general/src/features/auth/domain/entities/third_party_entity.dart';

part 'auth_platform_event.dart';
part 'auth_platform_state.dart';

class AuthPlatformBloc extends Bloc<AuthPlatformEvent, AuthPlatformState> {
  final SignInWithGoogleUC signInWithGoogleUC;
  final SignInWithAppleUC signInWithAppleUC;
  final SignInWithHuaweiUC signInWithHuaweiUC;

  AuthPlatformBloc({
    required this.signInWithGoogleUC,
    required this.signInWithAppleUC,
    required this.signInWithHuaweiUC,
  }) : super(
          const AuthPlatformState(),
        ) {
    on<SignInGoogleEvent>(_googleEvent);
    on<SignInAppleEvent>(_appleEvent);
    on<SignInHuaweiEvent>(_huaweiEvent);
  }
  // Events
  Future<void> _googleEvent(
    SignInGoogleEvent event,
    Emitter<AuthPlatformState> emit,
  ) async {
    emit(state.copyWith(requestStateGoogle: RequestState.loading));

    final Either<NetworkExceptions, BaseResponse<GoogleModel>> result;
    try {
      result = await signInWithGoogleUC.call();
    } catch (_) {
      emit(state.copyWith(requestStateGoogle: RequestState.error));
      return;
    }
    result.fold(
      (failure) {
        emit(
          state.copyWith(
            msgErrorGoogle: NetworkExceptions.getErrorMessage(failure),
            requestStateGoogle: RequestState.error,
          ),
        );
        Methods.showToast(
          navKey.currentContext,
          message: state.msgErrorGoogle,
          isError: true,
        );
      },
      (success) async {
        emit(
          state.copyWith(
            userDataWithGoogle: success.data,
            requestStateGoogle: RequestState.loaded,
          ),
        );
        Methods.identifyUserForCrashlytics();
        Methods.showToast(
          navKey.currentContext,
          message: success.message,
        );
        await Methods.saveUserToken(token_: success.data?.data.authToken);
        if (event.context.mounted) {
          if (success.data?.data.isFirst == true) {
            di<AddInformationBloc>().add(
              ThirdPartyEvent(
                kThirdPartyEntity: ThirdPartyEntity(
                  data: state.userDataWithGoogle,
                  type: 'google',
                ),
              ),
            );
            di<AddInformationBloc>().add(AddInformationEvent(
              context: event.context,
            ));
          } else {
            event.context.pushNamedAndRemoveUntil(Routes.layout);
          }
        }

        await Methods.saveUserLoginAccountIdToken(
          token: success.data?.data.authToken ?? '',
          accountId: success.data?.data.id.toString() ?? '',
        );
      },
    );
  }

  void _appleEvent(
      SignInAppleEvent event, Emitter<AuthPlatformState> emit) async {
    emit(state.copyWith(requestStateApple: RequestState.loading));

    final result = await signInWithAppleUC.call();
    result.fold(
      (failure) => emit(
        state.copyWith(
          msgErrorApple: NetworkExceptions.getErrorMessage(failure),
          requestStateApple: RequestState.error,
        ),
      ),
      (success) async {
        emit(
          state.copyWith(
            userDataWithApple: success.data,
            requestStateApple: RequestState.loaded,
          ),
        );

        Methods.identifyUserForCrashlytics();
        Methods.showToast(event.context, message: StringManager.success);
        await Methods.saveUserToken(token_: success.data?.data.authToken);
        if (event.context.mounted) {
          if (success.data?.data.isFirst == true) {
            event.context.pushNamedRoute(Routes.register,
                arguments: ThirdPartyEntity(
                    data: state.userDataWithApple?.data, type: 'apple'));
          } else {
            event.context.pushNamedAndRemoveUntil(Routes.layout);
          }
        }
        await Methods.saveUserLoginAccountIdToken(
          token: success.data?.data.authToken ?? '',
          accountId: success.data?.data.id.toString() ?? '',
        );
      },
    );
  }

  void _huaweiEvent(
      SignInHuaweiEvent event, Emitter<AuthPlatformState> emit) async {
    emit(state.copyWith(requestStateHuawei: RequestState.loading));

    final result = await signInWithHuaweiUC.call();
    result.fold(
      (failure) => emit(
        state.copyWith(
          msgErrorHuawei: NetworkExceptions.getErrorMessage(failure),
          requestStateHuawei: RequestState.error,
        ),
      ),
      (success) async {
        emit(
          state.copyWith(
            userDataWithHuawei: success.data,
            requestStateHuawei: RequestState.loaded,
          ),
        );
        Methods.identifyUserForCrashlytics();
        Methods.showToast(event.context, message: StringManager.success);
        await Methods.saveUserToken(token_: success.data?.data.authToken);
        if (event.context.mounted) {
          if (success.data?.data.isFirst == true) {
            event.context.pushNamedRoute(Routes.register,
                arguments: ThirdPartyEntity(
                    data: state.userDataWithHuawei?.data, type: 'huawei'));
          } else {
            event.context.pushNamedAndRemoveUntil(Routes.layout);
          }
        }
        await Methods.saveUserLoginAccountIdToken(
          token: success.data?.data.authToken ?? '',
          accountId: success.data?.data.id.toString() ?? '',
        );
      },
    );
  }
}
