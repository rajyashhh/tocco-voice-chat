import 'dart:async';

import 'package:general/src/core/index.dart';
import 'package:general/src/core/realtime/realtime_client.dart';
import 'package:general/src/features/auth/domain/entities/my_data_entity.dart';
import 'package:general/src/features/auth/domain/entities/un_read_counter_entity.dart';
import 'package:general/src/features/auth/domain/entities/user_entity.dart';
import 'package:general/src/features/profile/domain/profile_use_case/get_my_data_use_case.dart';
import 'package:general/src/features/profile/domain/profile_use_case/get_user_data_use_case.dart';
import 'package:general/src/features/room/domain/use_case/fetch_users_data_uc.dart';
import 'package:general/src/features/room/presentation/super_bomb/boom_winner_handler.dart';
import 'package:general/src/features/room/presentation/yallow_banner/controller/controller.dart';
import 'package:general/src/features/room/room.dart';

import '../../../../../auth/presentation/add_information/bloc/add_information_bloc.dart';
import '../../../../../auth/presentation/splash/realtime_settings_bloc/realtime_settings_bloc.dart';

part 'get_my_data_event.dart';

part 'get_my_data_state.dart';

class FetchUserDataBloc extends Bloc<BaseGetMyDataEvent, FetchUserDataState> {
  final FetchMyDataUseCase _fetchMyDataUC;
  final FetchUserDataUseCase _fetchUserDataUC;
  final FetchUsersDataUc _fetchUsersDataUc;

  /// Subscription to the Centrifugo non-chat stream (banners / per-user
  /// counters / status / boom-winner). This is the ONLY transport for these
  /// outside-room events now that legacy realtime is removed.
  StreamSubscription<RealtimeNonChatEvent>? _nonChatSub;

  FetchUserDataBloc(
    this._fetchMyDataUC,
    this._fetchUserDataUC,
    this._fetchUsersDataUc,
  ) : super(const FetchUserDataState()) {
    on<FetchMyDataEvent>(_fetchMyDataEvent);
    on<FetchUserEvent>(_fetchUserDataEvent);
    on<UpdateLocalDataEvent>(_updateLocalData);

    on<UnReadCounterFriendsEvent>(_unreadCounterFriendsEvent);
    on<UnReadCounterFollowingsEvent>(_unreadCounterFollowingsEvent);
    on<UnReadCounterFollowersEvent>(_unreadCounterFollowersEvent);
    on<UnReadCounterVistorsEvent>(_unreadCounterVistorsEvent);
    on<UnReadCounterMyBagEvent>(_unreadCounterMyBagEvent);
    on<UnReadCounterSystemMessagesEvent>(_unreadCounterSystemMessagesEvent);
    on<ReadCounterFriendsEvent>(_readCounterFriendsEvent);
    on<ReadCounterFollowingsEvent>(_readCounterFollowingsEvent);
    on<ReadCounterFollowersEvent>(_readCounterFollowersEvent);
    on<ReadCounterVistorsEvent>(_readCounterVistorsEvent);
    on<ReadCounterMyBagEvent>(_readCounterMyBagEvent);
    on<ReadCounterSystemMessagesEvent>(_readCounterSystemMessagesEvent);
    on<ReadCounterOfficialMessagesEvent>(_readCounterOfficialMessagesEvent);
    on<ScrollOffsetChangedEvent>(_scrollOffsetChangedEvent);
    on<ResetShowTitleEvent>(_resetTitleEvent);
    on<UpdateCurrentIndexEvent>(_updateCurrentIndex);
    on<UpdateLengthIndicatorEvent>(_updateLengthIndicator);
    on<UpdatePlayGameEvent>(_updatePlayGameEvent);
    on<ShowTitleEvent>(_showTitleEvent);
    on<FetchUsersDataEvent>(_fetchUsersDataEvent);
  }

  Future<void> _resetTitleEvent(
    ResetShowTitleEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    emit(state.copyWith(showTitle: false));
  }

  Future<void> _showTitleEvent(
    ShowTitleEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    emit(state.copyWith(showTitle: event.show));
  }

  Future<void> _scrollOffsetChangedEvent(
    ScrollOffsetChangedEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    final showTitle = event.offset > 245;
    if (state.showTitle != showTitle) {
      emit(state.copyWith(showTitle: showTitle));
    }
  }

  Future<void> _updateLocalData(
    UpdateLocalDataEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    emit(state.copyWith(
      userEntity: event.userEntity,
    ));
  }

  Future<void> _fetchMyDataEvent(
    FetchMyDataEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    if (event.isLoading == true) {
      emit(state.copyWith(reqState: RequestState.loading));
    }
    final result = await _fetchMyDataUC();

    switch (result) {
      case Left(value: final left):
        emit(
          state.copyWith(
            reqState: handleErrorResponse(left),
            message: NetworkExceptions.getErrorMessage(left),
          ),
        );
      case Right(value: final right):
        Methods.identifyUserForCrashlytics();

        // (Firestore room_users mirror removed — user data now comes from the
        // backend /users/details + Hive cache + UTD-Stream seat attributes.)

        final addInfoContext = SafeNavigator.context;
        if ((right.data?.uid == null || right.data?.uid?.isEmpty == true) &&
            addInfoContext != null) {
          di<AddInformationBloc>().add(
            AddInformationEvent(
              context: addInfoContext,
              isNavLayout: false,
              isUpdateOnlyUid: true,
            ),
          );
        }
        emit(
          state.copyWith(
            userEntity: right.data ?? const MyDataEntity(),
            reqState: handleLoadedResponse<MyDataEntity>(right.data),
          ),
        );

        // All outside-room banners / per-user counters / status / boom-winner
        // arrive over Centrifugo (legacy realtime fully removed). Wire the single shared
        // stream once — guarded by [_nonChatSub] so repeated my-data fetches
        // don't attach duplicate listeners.
        if (_nonChatSub == null) {
          _listenToCentrifugoNonChatEvents();
        }

        // Pull the server-driven realtime settings (`centrifugo_ws` +
        // `use_realtime_banners` via /config/settings -> applyFromSettings) so
        // the admin panel actually controls the client. Honors the (otherwise
        // inert) initRealtime flag the layouts still pass.
        if (event.initRealtime) {
          di<RealtimeSettingsBloc>().add(const FetchRealtimeSettings());
        }
    }
  }

  Future<void> _fetchUserDataEvent(
    FetchUserEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    if (event.isLoading == true) {
      emit(state.copyWith(reqStateUser: RequestState.loading));
    }
    final result = await _fetchUserDataUC(
      GetUserDataParameter(
        userId: event.userId,
        isVisit: event.isVisit,
      ),
    );

    result.fold(
      (left) {
        emit(
          state.copyWith(
            reqStateUser: handleErrorResponse(left),
            message: NetworkExceptions.getErrorMessage(left),
          ),
        );
      },
      (right) {
        emit(
          state.copyWith(
            otherUserEntity: right.data ?? const UserEntity(),
            reqStateUser: handleLoadedResponse<UserEntity>(right.data),
          ),
        );
      },
    );
  }

  void _handleGiftBanner(dynamic decoded) {
    if (decoded is! Map) return;
    final Map<String, dynamic> data = Map<String, dynamic>.from(decoded);
    if (kDebugMode) {
      Methods.printLog("banner =====> $data");
    }
    if (data.isNotEmpty) {
      GiftController().enqueueBanner(Map<String, dynamic>.from(data), 'normal');
    }
  }

  void _handleLuckyBoxBanner(dynamic decoded) {
    if (decoded is! List) return;
    final List<dynamic> dataList = decoded;

    if (kDebugMode) {
      Methods.printLog("banner lucky box =====> $dataList");
    }

    if (dataList.isEmpty) return;

    final Map<String, dynamic> data = dataList.first;

    final Map<String, dynamic> result = {
      "coins": data['coins'],
      "ownerBoxUId": data['boxUId'],
      "ownerBoxName": data['sender']?['s_name'] ?? '',
      "ownerBoxImage": data['sender']?['s_image'] ?? '',
      "ownerRoomId": data['room']?['uuid'] ?? '',
      "room": data['room'] ?? {},
      "ownerBoxSL": data['sender']?['s_sender_level'] ?? 0,
      "ownerBoxRL": data['sender']?['s_receiver_level'] ?? 0,
      "ownerBoxAL": data['ownerBoxAL'] ?? 0,
      "room_type": data['room']['room_type'] ?? 0,
    };

    enqueueLuckyBoxBanner(result);
  }

  void _handleGamesBanner(dynamic decoded) {
    if (decoded is! Map) return;
    final Map<String, dynamic> data = Map<String, dynamic>.from(decoded);

    if (kDebugMode) {
      Methods.printLog("games banner =====> $data");
    }

    if (data.isEmpty) return;

    GiftController().enqueueBanner(data[messageContent], 'game');
  }

  void _handleLuckyGiftBanner(dynamic decoded) {
    if (decoded is! Map) return;
    final Map<String, dynamic> data = Map<String, dynamic>.from(decoded);

    if (kDebugMode) {
      Methods.printLog("lucky Gift banner =====> $data");
    }

    if (data.isEmpty) return;

    GiftController().enqueueBanner(data[messageContent], 'lucky');
  }

  void _handleYallowBanner(dynamic decoded) {
    if (decoded is! Map) return;
    final Map<String, dynamic> data = Map<String, dynamic>.from(decoded);

    if (kDebugMode) {
      Methods.printLog("yallow banner =====> $data");
    }

    if (data.isEmpty) return;

    if (YallowBannerController().isShowYallowBanner.value) {
      Future.delayed(const Duration(seconds: 1), () async {
        YallowBannerController().roomData = RoomEntity(
          id: data[messageContent]['room']['id'],
          name: data[messageContent]['room']['name'],
          cover: data[messageContent]['room']['cover'],
          ownerId: data[messageContent]['room']['owner']['id'],
          uuidOwnerRoom: data[messageContent]['room']['owner']['uuid'],
          roomBackground: data[messageContent]['room']['background'],
          mode: data[messageContent]['room']['mode'].toString(),
          giftPrice: data[messageContent]['room']['gift_price'].toString(),
          passwordStatus: data[messageContent]['ps'],
          roomType: data[messageContent]['room_type'],
        );
        YallowBannerController().showYallowBannerAnimation(
          senderId: data[messageContent]['uId'],
          message: data[messageContent]['umsg'],
          room: YallowBannerController().roomData,
        );
      });
    } else {
      YallowBannerController().roomData = RoomEntity(
        id: data[messageContent]['room']['id'],
        name: data[messageContent]['room']['name'],
        cover: data[messageContent]['room']['cover'],
        ownerId: data[messageContent]['room']['owner']['id'],
        uuidOwnerRoom: data[messageContent]['room']['owner']['uuid'],
        roomBackground: data[messageContent]['room']['background'],
        mode: data[messageContent]['room']['mode'].toString(),
        giftPrice: data[messageContent]['room']['gift_price'].toString(),
        passwordStatus: data[messageContent]['ps'],
        roomType: data[messageContent]['room_type'],
      );
      YallowBannerController().showYallowBannerAnimation(
        senderId: data[messageContent]['uId'],
        message: data[messageContent]['umsg'],
        room: YallowBannerController().roomData,
      );
    }
  }

  void _handleSuperBoomBanner(dynamic decoded) {
    if (decoded is! Map) return;
    final Map<String, dynamic> data = Map<String, dynamic>.from(decoded);

    if (kDebugMode) {
      Methods.printLog("super boom banner =====> $data");
    }

    if (data.isEmpty) return;

    SuperBoomController.addBomb(data["endData"]);
  }

  Future<void> _handleCloseStreamFeature(dynamic decoded) async {
    if (decoded is! Map) return;
    final Map<String, dynamic> data = Map<String, dynamic>.from(decoded);

    if (kDebugMode) {
      Methods.printLog("close stream feature =====> $data");
    }

    if (data.isEmpty) return;

    if (di<RoomStateManager>().isInRoom &&
        !di<RoomStateManager>().isMinimized) {
      final navContext = SafeNavigator.context;
      if (navContext == null) return;
      await di<RoomStateManager>().exitRoom(
        navContext,
        callback: () {
          final cbContext = SafeNavigator.context;
          if (cbContext == null) return;
          Navigator.popUntil(
            cbContext,
            (route) {
              return route.settings.name == Routes.layout;
            },
          );
          // Show toast after navigating back
          Methods.safeShowToast(
            isError: true,
            message: StringManager.roomNotAvailable.tr(),
          );
        },
      );
    } else if (di<RoomStateManager>().isMinimized) {
      // UTD kit: exitRoom tears down the minimized room (leave + reset).
      final navContext = SafeNavigator.context;
      if (navContext == null) return;
      await di<RoomStateManager>().exitRoom(navContext);
      // Show toast after leaving minimized room
      Methods.safeShowToast(
        isError: true,
        message: StringManager.roomNotAvailable.tr(),
      );
    }
  }

  void _handleGameStatus(dynamic decoded) {
    if (decoded is! Map) return;
    final Map<String, dynamic> data = Map<String, dynamic>.from(decoded);
    if (kDebugMode) {
      Methods.printLog("Game =====> $data");
    }
    if (data.isNotEmpty) {
      final current = MyDataModel.getInstance();
      add(UpdatePlayGameEvent(
          canPlay: data.containsKey('can_play')
              ? parseValue<bool>(
                  data['can_play'], current.isGameAvailable ?? false)
              : (current.isGameAvailable ?? false),
          showInviteCode: data.containsKey('show_invite_code')
              ? parseValue<bool>(
                  data['show_invite_code'], current.showInvitationCode ?? false)
              : (current.showInvitationCode ?? false)));
    }
  }

  void _handleCounterIndividual(dynamic decoded) {
    if (decoded is! Map) return;
    final Map<String, dynamic> data = Map<String, dynamic>.from(decoded);

    if (kDebugMode) {
      Methods.printLog("unread-counter-individual =====> $data");
    }

    switch (data['type']) {
      case "friend":
        add(const UnReadCounterFriendsEvent());
        break;
      case "follow":
        add(const UnReadCounterFollowingsEvent());
        break;
      case "follower":
        add(const UnReadCounterFollowersEvent());
        break;
      case "system-messages":
        add(const UnReadCounterSystemMessagesEvent());
        break;
      case "visit-profile":
        add(const UnReadCounterVistorsEvent());
        break;
      case "mybag":
        add(const UnReadCounterMyBagEvent());
        break;
    }
  }

  /// The ONLY transport for the outside-room non-chat events (legacy realtime removed).
  /// Subscribes once to the shared [RealtimeClient.nonChatEvents] stream and
  /// dispatches each event to the same handler logic the legacy realtime path used, so
  /// behavior/payload shapes are unchanged. Wired unconditionally on my-data
  /// load (guarded by [_nonChatSub] against duplicate listeners).
  void _listenToCentrifugoNonChatEvents() {
    final stream = di<RealtimeClient>().nonChatEvents;
    _nonChatSub = stream.listen((RealtimeNonChatEvent event) {
      // De-dup: a viewer INSIDE the banner's room receives the in-room copy via
      // UTD Stream, so drop the Centrifugo copy here to avoid a double banner.
      // Out-of-room viewers (and games, which are always cross-room) flow as
      // before. See [_isInBannerRoom].
      if (_isInBannerRoom(event.event, event.payload)) return;
      switch (event.event) {
        case 'gift_banner':
          _handleGiftBanner(event.payload);
          break;
        case 'superLuckBox':
          _handleLuckyBoxBanner(event.payload);
          break;
        case 'end_room_boom':
          _handleSuperBoomBanner(event.payload);
          break;
        case 'zego_feature':
          unawaited(_handleCloseStreamFeature(event.payload));
          break;
        case 'UnreadCounterIndividual':
          _handleCounterIndividual(event.payload);
          break;
        case 'status-user':
          _handleGameStatus(event.payload);
          break;
        // WAVE-2: backend keeps the legacy `broadcastAs` event names; payload is
        // the same {messageContent: {...}} Map the legacy realtime consumers read.
        case 'win.lucky.gift.event':
          _handleLuckyGiftBanner(event.payload);
          break;
        case 'game.win.event':
          _handleGamesBanner(event.payload);
          break;
        case 'room.comment.event':
          _handleYallowBanner(event.payload);
          break;
        // In-room boom-winner reveal, re-homed off the legacy realtime presence channel
        // `room.boom.rewards.{roomId}` onto Centrifugo. The handler self-filters
        // by the signed-in user id, so a shared banner channel is safe.
        case 'room_boom_rewards':
          final payload = event.payload;
          if (payload is Map) {
            BoomWinnerHandler.show(Map<String, dynamic>.from(payload));
          }
          break;
      }
    });
  }

  /// True when the viewer is currently inside the room this banner targets, in
  /// which case the in-room (UTD Stream) copy renders it and the Centrifugo copy
  /// must be suppressed (zero duplication). Out-of-room viewers return false so
  /// the Centrifugo copy renders. `game.win.event` is always cross-room and
  /// is never suppressed. `room_boom_rewards` carries no room id but self-filters
  /// by user id, so when in-room the in-room copy handles it and this returns
  /// true. Unknown shapes default to false (prefer a rare duplicate over a lost
  /// banner). Room-id key varies per banner — extracted per event below.
  bool _isInBannerRoom(String event, dynamic payload) {
    final roomManager = di<RoomStateManager>();
    if (!roomManager.isInRoom) return false;

    if (event == 'game.win.event') return false;
    if (event == 'room_boom_rewards') return true;

    dynamic bannerRoomId;
    switch (event) {
      case 'gift_banner':
        if (payload is Map) bannerRoomId = payload['gift']?['room_id'];
        break;
      case 'superLuckBox':
        if (payload is List && payload.isNotEmpty) {
          final first = payload.first;
          if (first is Map) bannerRoomId = first['room']?['id'];
        }
        break;
      case 'end_room_boom':
        if (payload is Map) bannerRoomId = payload['endData']?['room_id'];
        break;
      case 'win.lucky.gift.event':
        if (payload is Map) bannerRoomId = payload[messageContent]?['room_id'];
        break;
      case 'room.comment.event':
        // room.comment.event payload structure: {messageContent: {uId, umsg,
        // ps, room: {id, ...}}} — the same envelope [_handleYallowBanner]
        // reads. Reading payload['room'] here was always null (fail-open), so
        // the Centrifugo copy leaked into the banner's own room.
        if (payload is Map) {
          bannerRoomId = payload[messageContent]?['room']?['id'];
        }
        break;
    }

    if (bannerRoomId == null) return false;
    return bannerRoomId.toString() == roomManager.currentRoomId.toString();
  }

  Future<void> _unreadCounterFriendsEvent(
    UnReadCounterFriendsEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model = unreadCounterEntity.copyWith(
        friend: unreadCounterEntity.friend + 1,
      );
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  ////////////////// update can play game room
  Future<void> _updatePlayGameEvent(
    UpdatePlayGameEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    MyDataModel.updateInstance(
        isGameAvailable: event.canPlay,
        showInvitationCode: event.showInviteCode);
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _unreadCounterFollowingsEvent(
    UnReadCounterFollowingsEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model = unreadCounterEntity.copyWith(
        followeds: unreadCounterEntity.followeds + 1,
      );

      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _unreadCounterFollowersEvent(
    UnReadCounterFollowersEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;

    if (unreadCounterEntity != null) {
      UnreadCounterEntity model = unreadCounterEntity.copyWith(
        followers: unreadCounterEntity.followers + 1,
      );
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _unreadCounterVistorsEvent(
    UnReadCounterVistorsEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model = unreadCounterEntity.copyWith(
        visitor: unreadCounterEntity.visitor + 1,
      );
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _unreadCounterMyBagEvent(
    UnReadCounterMyBagEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model = unreadCounterEntity.copyWith(
        myBag: unreadCounterEntity.myBag + 1,
      );
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _unreadCounterSystemMessagesEvent(
    UnReadCounterSystemMessagesEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model = unreadCounterEntity.copyWith(
        systemMessage: unreadCounterEntity.systemMessage + 1,
      );
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _readCounterFriendsEvent(
    ReadCounterFriendsEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model = unreadCounterEntity.copyWith(friend: 0);
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _readCounterFollowingsEvent(
    ReadCounterFollowingsEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model = unreadCounterEntity.copyWith(followeds: 0);
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _readCounterFollowersEvent(
    ReadCounterFollowersEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model = unreadCounterEntity.copyWith(followers: 0);
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _readCounterVistorsEvent(
    ReadCounterVistorsEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model = unreadCounterEntity.copyWith(visitor: 0);
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _readCounterMyBagEvent(
    ReadCounterMyBagEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model = unreadCounterEntity.copyWith(myBag: 0);
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _readCounterSystemMessagesEvent(
    ReadCounterSystemMessagesEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model =
          unreadCounterEntity.copyWith(systemMessage: 0);
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  Future<void> _readCounterOfficialMessagesEvent(
    ReadCounterOfficialMessagesEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    UnreadCounterEntity? unreadCounterEntity =
        MyDataModel.getInstance().unreadCounterEntity;
    if (unreadCounterEntity != null) {
      UnreadCounterEntity model =
          unreadCounterEntity.copyWith(officialMessage: 0);
      MyDataModel.updateInstance(unreadCounterEntity: model);
    }
    emit(
      state.copyWith(userEntity: MyDataModel.getInstance()),
    );
  }

  void _updateCurrentIndex(
      UpdateCurrentIndexEvent event, Emitter<FetchUserDataState> emit) {
    emit(state.copyWith(currentIndex: event.newIndex));
  }

  void _updateLengthIndicator(
      UpdateLengthIndicatorEvent event, Emitter<FetchUserDataState> emit) {
    emit(state.copyWith(lengthIndicator: event.length));
  }

  Future<void> _fetchUsersDataEvent(
    FetchUsersDataEvent event,
    Emitter<FetchUserDataState> emit,
  ) async {
    final result = await _fetchUsersDataUc(event.userIds);

    result.fold(
      (left) {
        emit(
          state.copyWith(
            users: state.users,
          ),
        );
      },
      (right) {
        emit(
          state.copyWith(
            users: right.data,
          ),
        );
      },
    );
  }

  @override
  Future<void> close() {
    _nonChatSub?.cancel();
    _nonChatSub = null;
    return super.close();
  }
}
