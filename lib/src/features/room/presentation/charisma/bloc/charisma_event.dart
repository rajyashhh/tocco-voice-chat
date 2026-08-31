part of'charisma_bloc.dart';

abstract class CharismaEvent extends Equatable {
  const CharismaEvent();
}

class InitCharismaEvent extends CharismaEvent {
  const InitCharismaEvent();
  @override
  List<Object?> get props => [];
}

class StartCharismaEvent extends CharismaEvent {
  final String roomId;

  const StartCharismaEvent({required this.roomId});
  @override
  List<Object?> get props => [roomId];
}

class GetCharismaExtraDataEvent extends CharismaEvent {
  final String roomId;
  const GetCharismaExtraDataEvent({required this.roomId});
  @override
  List<Object?> get props => [roomId];
}

class ResetCharismaEvent extends CharismaEvent {
  final String roomId;
  final String ownerId;
  final BuildContext context;
  const ResetCharismaEvent({required this.roomId,required this.ownerId,required this.context});
  @override
  List<Object?> get props => [roomId,ownerId,context];
}

/// Writes the authoritative server charisma totals into the render state.
/// [data] carries each user's floored cumulative room total (totalValue) shipped
/// by the backend in the gift frame or enter-room payload. The server is the
/// source of truth, so a value always replaces the prior one.
class UpdateCharismaEvent extends CharismaEvent {
  final List<CharismaModel> data;

  const UpdateCharismaEvent({required this.data});
  @override
  List<Object?> get props => [data];
}

class FetchCharismaLevelsEvent extends CharismaEvent {
  const FetchCharismaLevelsEvent();
  @override
  List<Object?> get props => [];
}
