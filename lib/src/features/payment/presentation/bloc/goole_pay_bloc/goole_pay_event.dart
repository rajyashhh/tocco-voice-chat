import 'package:equatable/equatable.dart';

abstract class BaseGooglePayEvent extends Equatable {
  const BaseGooglePayEvent();

  @override
  List<Object> get props => [];
}

class GooglePayEvent extends BaseGooglePayEvent {
  final String purchaseToken, productId;

  const GooglePayEvent({required this.purchaseToken, required this.productId});
}
