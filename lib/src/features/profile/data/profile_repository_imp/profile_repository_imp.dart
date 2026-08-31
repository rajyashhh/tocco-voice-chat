import 'package:general/src/features/auth/data/model/user_levels_model.dart';
import 'package:general/src/features/profile/data/model/get_badges_model.dart';
import 'package:general/src/features/profile/data/model/top_support.dart';
import 'package:general/src/features/profile/data/model/user_badges_model.dart';
import 'package:general/src/features/profile/data/model/user_intro_model.dart';
import 'package:general/src/features/profile/data/model/user_room_model.dart';
import 'package:general/src/features/profile/profile.dart';
import '../../../../core/index.dart';
import '../../../auth/auth.dart';
import '../model/gold_coin_model.dart';
import '../model/levels_badges_model.dart';
import '../model/levels_model.dart';

class ProfileRepositoryImp extends ProfileBaseRepository {
  final BaseProfileRemotelyDataSource remotelyDataSource;

  ProfileRepositoryImp({required this.remotelyDataSource});

  @override
  ResultFuture<BaseResponse<MyDataModel>> getMyData() {
    return execute<BaseResponse<MyDataModel>>(
        () => remotelyDataSource.getMyData());
  }

    @override
  ResultFuture<BaseResponse<LevelModel>> getMyLevelsData() {
    return execute<BaseResponse<LevelModel>>(
        () => remotelyDataSource.getMyLevelsData());
  }

  @override
  ResultFuture<BaseResponse<List<UserModel>>> getFriendsOrFollowers(
      FFFParameter fffParameter) {
    return execute<BaseResponse<List<UserModel>>>(
        () => remotelyDataSource.getFriendsOrFollowers(fffParameter));
  }

  @override
  ResultFuture<BaseResponse<String>> makeFollow({String? userId}) {
    return execute<BaseResponse<String>>(
        () => remotelyDataSource.follow(userId: userId!));
  }

  @override
  ResultFuture<BaseResponse<String>> makeUnFollow({String? userId}) {
    return execute<BaseResponse<String>>(
        () => remotelyDataSource.unFollow(userId: userId!));
  }

  @override
  ResultFuture<BaseResponse<List<AllLevels>>> fetchLevels(int type) {
    return execute<BaseResponse<List<AllLevels>>>(
        () => remotelyDataSource.fetchLevels(type));
  }

  @override
  ResultFuture<MyStoreModel> myStore() {
    return execute<MyStoreModel>(
      () => remotelyDataSource.myStore(),
    );
  }

  @override
  ResultFuture<BaseResponse<List<GoldCoinsModel>>> getGoldCoinPrices() {
    return execute<BaseResponse<List<GoldCoinsModel>>>(
      () => remotelyDataSource.getGoldCoinPrices(),
    );
  }

  @override
  ResultFuture<BaseResponse<List<UserModel>>> getVisitors(
      {FFFParameter? page}) {
    return execute<BaseResponse<List<UserModel>>>(
      () => remotelyDataSource.getVisitors(page: page),
    );
  }

  @override
  ResultFuture<String> addBloc({required String userId}) {
    return execute<String>(
      () => remotelyDataSource.addBloc(userId: userId),
    );
  }

  @override
  ResultFuture<String> removeBloc({required String userId}) {
    return execute<String>(
      () => remotelyDataSource.removeBloc(userId: userId),
    );
  }

  @override
  ResultFuture<BaseResponse<List<GiftHistoryModel>>> getGiftHistory(
      {required String userId}) {
    return execute<BaseResponse<List<GiftHistoryModel>>>(
      () => remotelyDataSource.getGiftHistory(userId: userId),
    );
  }

  @override
  ResultFuture<BaseResponse<UserModel>> getUserData(
      {GetUserDataParameter? params}) {
    return execute<BaseResponse<UserModel>>(
      () => remotelyDataSource.getUserData(params: params!),
    );
  }

  @override
  ResultFuture<String> userReport({required UserReportParameter userReport}) {
    return execute<String>(
      () => remotelyDataSource.reportUser(userReport: userReport),
    );
  }

  @override
  ResultFuture<BaseResponse<List<ImageData>>> getUserBadge(String id) async {
    return execute<BaseResponse<List<ImageData>>>(
      () => remotelyDataSource.userBadges(id),
    );
  }

  @override
  ResultFuture<BaseResponse<List<GetBadgesModel>>> fetchBadges(
      {required String type}) {
    return execute<BaseResponse<List<GetBadgesModel>>>(
        () => remotelyDataSource.fetchBadges(type: type));
  }

  @override
  ResultFuture<BaseResponse<List<ImageData>>> getMyAllBadge({String? id}) {
    return execute<BaseResponse<List<ImageData>>>(
        () => remotelyDataSource.getMyAllBadge(id: id ?? ''));
  }

  @override
  ResultFuture<BaseResponse<List<BillModel>>> fetchBill(
      {required BillParam param}) {
    return execute<BaseResponse<List<BillModel>>>(
        () => remotelyDataSource.fetchBill(param: param));
  }

  @override
  ResultFuture<String> pickMyBadges(List<int> ids) {
    return execute<String>(() => remotelyDataSource.pickMyBadges(ids));
  }

  @override
  ResultFuture<ReplaceWithGoldModel> getReplaceWithDiamondData() {
    return execute<ReplaceWithGoldModel>(
        () => remotelyDataSource.getReplaceWithDiamondData());
  }

  @override
  ResultFuture<String> exchangeDiamond({required String itemId}) {
    return execute<String>(
        () => remotelyDataSource.exchangeDiamond(itemId: itemId));
  }

  @override
  ResultFuture<BaseResponse<TopSupportModel>> getUserSupporter(String id) {
    return execute<BaseResponse<TopSupportModel>>(
        () => remotelyDataSource.getUserSupporter(id));
  }

  @override
  ResultFuture<BaseResponse<BadgesModel>> levelsBadges(int type) async {
    return execute<BaseResponse<BadgesModel>>(
      () => remotelyDataSource.levelsBadges(type),
    );
  }

  @override
  ResultFuture<BaseResponse<RoomLevelBadgesModel>> roomLevelBadges() async {
    return execute<BaseResponse<RoomLevelBadgesModel>>(
      () => remotelyDataSource.roomLevelBadges(),
    );
  }

  @override
  ResultFuture<BaseResponse<List<UserIntroModel>>> getUserIntro(
      {required String userId}) {
    return execute<BaseResponse<List<UserIntroModel>>>(
      () => remotelyDataSource.getUserIntro(userId: userId),
    );
  }

  @override
  ResultFuture<BaseResponse<UserLevelsModel>> userLevels() {
    return execute<BaseResponse<UserLevelsModel>>(
      () => remotelyDataSource.userLevels(),
    );
  }

  @override
  ResultFuture<BaseResponse<UserRoomsModel>> userRooms(int userId) {
    return execute<BaseResponse<UserRoomsModel>>(
      () => remotelyDataSource.userRooms(userId),
    );
  }

  @override
  ResultFuture<BaseResponse<UserBadgesModel>> getUserBadges(int userId) {
    return execute<BaseResponse<UserBadgesModel>>(
      () => remotelyDataSource.getUserBadges(userId),
    );
  }

  @override
  ResultFuture<BaseResponse<int>> changeCountry(String countryId) {
    return execute<BaseResponse<int>>(
      () => remotelyDataSource.changeCountry(countryId),
    );
  }
}
