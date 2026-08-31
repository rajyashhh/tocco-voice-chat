import 'package:general/src/features/auth/data/model/level_model.dart';
import 'package:general/src/features/auth/data/model/user_levels_model.dart';
import 'package:general/src/features/auth/data/model/user_model.dart';
import 'package:general/src/features/profile/data/model/get_badges_model.dart';
import 'package:general/src/features/profile/data/model/top_support.dart';
import 'package:general/src/features/profile/data/model/user_badges_model.dart';
import 'package:general/src/features/profile/data/model/user_intro_model.dart';
import 'package:general/src/features/profile/data/model/user_room_model.dart';
import 'package:general/src/features/profile/profile.dart';
import '../../../../core/index.dart';
import '../../data/model/gold_coin_model.dart';
import '../../data/model/levels_badges_model.dart';
import '../../data/model/levels_model.dart';

abstract class ProfileBaseRepository {
  ResultFuture<BaseResponse<MyDataModel>> getMyData();
  ResultFuture<BaseResponse<LevelModel>> getMyLevelsData();
  ResultFuture<BaseResponse<List<UserModel>>> getFriendsOrFollowers(
    FFFParameter fffParameter,
  );
  ResultFuture<BaseResponse<String>> makeFollow({String userId});
  ResultFuture<BaseResponse<String>> makeUnFollow({String userId});
  ResultFuture<BaseResponse<List<AllLevels>>> fetchLevels(int type);
  ResultFuture<MyStoreModel> myStore();
  ResultFuture<BaseResponse<List<GoldCoinsModel>>> getGoldCoinPrices();
  ResultFuture<BaseResponse<List<UserModel>>> getVisitors({FFFParameter? page});
  // ResultFuture<BaseResponse<List<ImageData>>>  getUserBadge(String id);
  ResultFuture<String> addBloc({required String userId});
  ResultFuture<String> removeBloc({required String userId});
  ResultFuture<BaseResponse<List<GiftHistoryModel>>> getGiftHistory(
      {required String userId});
  ResultFuture<BaseResponse<List<UserIntroModel>>> getUserIntro(
      {required String userId});
  ResultFuture<BaseResponse<UserModel>> getUserData({
    GetUserDataParameter params,
  });
  ResultFuture<String> userReport({required UserReportParameter userReport});
  ResultFuture<BaseResponse<List<ImageData>>> getUserBadge(String id);
  ResultFuture<BaseResponse<TopSupportModel>> getUserSupporter(String id);
  ResultFuture<BaseResponse<List<BillModel>>> fetchBill(
      {required BillParam param});
  ResultFuture<ReplaceWithGoldModel> getReplaceWithDiamondData();
  ResultFuture<String> exchangeDiamond({required String itemId});
  ResultFuture<BaseResponse<List<GetBadgesModel>>> fetchBadges({
    required String type,
  });

  ResultFuture<String> pickMyBadges(List<int> ids);
  ResultFuture<BaseResponse<List<ImageData>>> getMyAllBadge({String id});
  ResultFuture<BaseResponse<BadgesModel>> levelsBadges(int type);
  ResultFuture<BaseResponse<RoomLevelBadgesModel>> roomLevelBadges();
  ResultFuture<BaseResponse<UserLevelsModel>> userLevels();
  ResultFuture<BaseResponse<UserRoomsModel>> userRooms(int userId);
  ResultFuture<BaseResponse<UserBadgesModel>> getUserBadges(int userId);
  ResultFuture<BaseResponse<int>> changeCountry(String countryId);
}
