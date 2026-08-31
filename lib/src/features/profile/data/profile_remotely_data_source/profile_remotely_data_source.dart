import 'dart:io';

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

abstract class BaseProfileRemotelyDataSource {
  Future<BaseResponse<MyDataModel>> getMyData();

  Future<BaseResponse<LevelModel>> getMyLevelsData();

  Future<BaseResponse<List<UserModel>>> getFriendsOrFollowers(
      FFFParameter fffParameter);

  Future<BaseResponse<String>> follow({required String userId});

  Future<BaseResponse<String>> unFollow({required String userId});

  Future<BaseResponse<List<AllLevels>>> fetchLevels(
    int type,
  );

  Future<MyStoreModel> myStore();

  Future<BaseResponse<List<GoldCoinsModel>>> getGoldCoinPrices();

  Future<BaseResponse<List<UserModel>>> getVisitors({FFFParameter? page});

  Future<String> addBloc({required String userId});

  Future<String> removeBloc({required String userId});

  Future<BaseResponse<List<GiftHistoryModel>>> getGiftHistory({
    required String userId,
  });

  Future<BaseResponse<List<UserIntroModel>>> getUserIntro({
    required String userId,
  });

  Future<BaseResponse<UserModel>> getUserData({
    GetUserDataParameter params,
  });

  Future<String> reportUser({required UserReportParameter userReport});

  Future<BaseResponse<List<ImageData>>> userBadges(String userId);

  Future<BaseResponse<TopSupportModel>> getUserSupporter(String userId);

  Future<BaseResponse<List<BillModel>>> fetchBill({required BillParam param});

  Future<ReplaceWithGoldModel> getReplaceWithDiamondData();

  Future<String> exchangeDiamond({required String itemId});

  Future<BaseResponse<List<GetBadgesModel>>> fetchBadges({
    required String type,
  });

  Future<BaseResponse<List<ImageData>>> getMyAllBadge({String id});

  Future<String> pickMyBadges(List<int> ids);

  Future<BaseResponse<BadgesModel>> levelsBadges(int type);

  Future<BaseResponse<RoomLevelBadgesModel>> roomLevelBadges();

  Future<BaseResponse<UserLevelsModel>> userLevels();

  Future<BaseResponse<UserRoomsModel>> userRooms(int userId);

  Future<BaseResponse<UserBadgesModel>> getUserBadges(int userId);

  Future<BaseResponse<int>> changeCountry(String countryId);
}

class ProfileRemotelyDataSource extends BaseProfileRemotelyDataSource {
  final DioFactory dioFactory;

  ProfileRemotelyDataSource({required this.dioFactory});

  @override
  Future<BaseResponse<MyDataModel>> getMyData() async {
    final response = await dioFactory.get(
      EndPoints.getMyDataUrl,
    );

    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => MyDataModel.fromJson(response.data['data']),
    );
  }

  @override
  Future<BaseResponse<LevelModel>> getMyLevelsData() async {
    final response = await dioFactory.get(
      EndPoints.getMyLevelData,
    );

    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => LevelModel.fromJson(response.data['data']),
    );
  }

  @override
  Future<BaseResponse<List<UserModel>>> getFriendsOrFollowers(
      FFFParameter fffParameter) async {
    final response = fffParameter.page == null
        ? await dioFactory
            .get("${EndPoints.relations}?type=${fffParameter.type}")
        : await dioFactory.get(
            "${EndPoints.relations}?keywords=${fffParameter.keyWord ?? ''}&type=${fffParameter.type}&page=${fffParameter.page}",
          );
    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) =>
          (json as List).map((element) => UserModel.fromJson(element)).toList(),
    );
  }

  @override
  Future<BaseResponse<String>> follow({required String userId}) async {
    final response = await dioFactory.post(
      EndPoints.follow,
      data: {'user_id': userId},
    );
    return BaseResponse.fromJson(response.data,
        fromJsonT: (json) => response.data['message'] ?? "");
  }

  @override
  Future<BaseResponse<String>> unFollow({required String userId}) async {
    final response = await dioFactory.post(
      EndPoints.unFollow,
      data: {'user_id': userId},
    );
    return BaseResponse.fromJson(response.data,
        fromJsonT: (json) => response.data['message']);
  }

  @override
  Future<BaseResponse<List<AllLevels>>> fetchLevels(int type) async {
    final response = await dioFactory.get(
      EndPoints.showLevels(type),
    );
    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => (json as List<dynamic>)
          .map((element) => AllLevels.fromJson(element))
          .toList(),
    );
  }

  @override
  Future<MyStoreModel> myStore() async {
    final response = await dioFactory.get(
      EndPoints.myStore,
    );
    return MyStoreModel.fromJson(response.data['data']['my_store']);
  }

  @override
  Future<BaseResponse<List<GoldCoinsModel>>> getGoldCoinPrices() async {
    final response = await dioFactory.get(
      EndPoints.getGoldData,
    );
    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => (json as List<dynamic>)
          .map((element) => GoldCoinsModel.fromJson(element))
          .toList(),
    );
  }

  @override
  Future<BaseResponse<List<UserModel>>> getVisitors(
      {FFFParameter? page}) async {
    final response = page?.page == null
        ? await dioFactory.get(
            "${EndPoints.getVisitors}?keywords=${page?.keyWord ?? ''}",
          )
        : await dioFactory.get(
            "${EndPoints.getVisitors}?keywords=${page?.keyWord ?? ''}&page=${page?.page}",
          );
    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => (json as List<dynamic>)
          .map((element) => UserModel.fromJson(element))
          .toList(),
    );
  }

  @override
  Future<String> addBloc({required String userId}) async {
    final response = await dioFactory.post(
      EndPoints.addBloc,
      data: {'user_id': userId},
    );
    return response.data['message'];
  }

  @override
  Future<String> removeBloc({required String userId}) async {
    final response = await dioFactory.post(
      EndPoints.removeBloc,
      data: {'user_id': userId},
    );
    return response.data['message'];
  }

  @override
  Future<BaseResponse<List<GiftHistoryModel>>> getGiftHistory(
      {required String userId}) async {
    final response = await dioFactory.get(
      EndPoints.getGiftHistory(userId),
    );
    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => (json as List<dynamic>)
          .map((element) => GiftHistoryModel.fromJson(element))
          .toList(),
    );
  }

  @override
  Future<BaseResponse<List<UserIntroModel>>> getUserIntro(
      {required String userId}) async {
    final response = await dioFactory.get(
      EndPoints.getUserIntro(userId),
    );
    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => (json as List<dynamic>)
          .map((element) => UserIntroModel.fromJson(element))
          .toList(),
    );
  }

  @override
  Future<BaseResponse<UserModel>> getUserData(
      {GetUserDataParameter? params}) async {
    final response = await dioFactory.get(
      EndPoints.getUserData(userId: params!.userId, isVisit: params.isVisit),
    );

    return BaseResponse.fromJson(response.data, fromJsonT: (json) {
      return UserModel.fromJson(response.data['data']);
    });
  }

  @override
  Future<String> reportUser({required UserReportParameter userReport}) async {
    FormData formData;
    if (userReport.image == null) {
      formData = FormData.fromMap({
        'id': userReport.id,
        'type_report': userReport.typeReport,
        'report_content': userReport.reportContent,
      });
    } else {
      File file = userReport.image!;
      String fileName = file.path.split('/').last;

      formData = FormData.fromMap({
        "image": await MultipartFile.fromFile(file.path, filename: fileName),
        'id': userReport.id,
        'type_report': userReport.typeReport,
        'report_content': userReport.reportContent,
      });
    }
    final response = await dioFactory.post(
      EndPoints.userReport,
      data: formData,
    );
    return response.data['message'];
  }

  @override
  Future<BaseResponse<List<ImageData>>> userBadges(String userId) async {
    final response = await dioFactory.get(
      EndPoints.getUserBadges(userId),
    );

    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => (json as List<dynamic>)
          .map((element) => ImageData.fromJson(element))
          .toList(),
    );
  }

  @override
  Future<BaseResponse<List<GetBadgesModel>>> fetchBadges(
      {required String type}) async {
    final response = await DioFactory().get(
      EndPoints.getBadges(type),
    );
    final Map<String, dynamic> resultData = response.data;
    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => List<GetBadgesModel>.from(
        resultData["data"].map(
          (element) => GetBadgesModel.fromJson(element),
        ),
      ),
    );
  }

  @override
  Future<BaseResponse<List<ImageData>>> getMyAllBadge({String? id}) async {
    final response = await DioFactory().get(
      EndPoints.getMyAllBadge(id ?? ''),
    );

    final Map<String, dynamic> resultData = response.data;
    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => List<ImageData>.from(
        resultData["data"].map(
          (element) => ImageData.fromJson(element),
        ),
      ),
    );
  }

  @override
  Future<String> pickMyBadges(List<int> ids) async {
    Map body = {"ids": ids};
    final response =
        await DioFactory().post(EndPoints.pickMyBadges, data: body);

    return response.data['message'];
  }

  @override
  Future<BaseResponse<List<BillModel>>> fetchBill(
      {required BillParam param}) async {
    final response =
        await dioFactory.get(EndPoints.fetchBill, queryParameters: {
      'type': param.type,
      'start_date': param.startDate,
      'end_date': param.endDate,
      'page': param.page,
      'class': param.shippingType,
    });

    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => (json as List<dynamic>)
          .map((element) => BillModel.fromJson(element))
          .toList(),
    );
  }

  @override
  Future<ReplaceWithGoldModel> getReplaceWithDiamondData() async {
    final response =
        await DioFactory().get(EndPoints.getReplaceWithDiamondData);

    Map<String, dynamic> resultData = response.data;
    ReplaceWithGoldModel data = ReplaceWithGoldModel.fromJson(resultData);

    return data;
  }

  @override
  Future<String> exchangeDiamond({required String itemId}) async {
    final body = {
      'item_id': itemId,
    };
    final response = await DioFactory().post(
      EndPoints.exchangeDiamonds,
      data: body,
    );

    return response.data['message'].toString();
  }

  @override
  Future<BaseResponse<TopSupportModel>> getUserSupporter(String userId) async {
    final response = await dioFactory.get(
      EndPoints.getUserSupporter(userId),
    );
    return BaseResponse.fromJson(response.data,
        fromJsonT: (json) => TopSupportModel.fromJson(json));
  }

  @override
  Future<BaseResponse<BadgesModel>> levelsBadges(int type) async {
    final response = await dioFactory.get(
      EndPoints.levelBadges(type),
    );

    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => BadgesModel.fromJson(json),
    );
  }

  @override
  Future<BaseResponse<RoomLevelBadgesModel>> roomLevelBadges() async {
    Methods.printLog('API CALL: ${EndPoints.roomLevelBadges}');
    final response = await dioFactory.get(
      EndPoints.roomLevelBadges,
    );
    Methods.printLog('API RESPONSE: ${response.data}');

    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => RoomLevelBadgesModel.fromJson(json),
    );
  }

  @override
  Future<BaseResponse<UserLevelsModel>> userLevels() async {
    final response = await dioFactory.get(
      EndPoints.userLevels,
    );

    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => UserLevelsModel.fromJson(
        response.data['data'],
      ),
    );
  }

  @override
  Future<BaseResponse<UserRoomsModel>> userRooms(int userId) async {
    final response = await dioFactory.get(
      EndPoints.userRooms(userId),
    );

    UserRoomsModel data = UserRoomsModel.fromJson(response.data['data']);
    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => data,
    );
  }

  @override
  Future<BaseResponse<UserBadgesModel>> getUserBadges(int userId) async {
    final response = await dioFactory.get(
      EndPoints.userBadges(userId),
    );

    UserBadgesModel data = UserBadgesModel.fromJson(response.data['data']);
    return BaseResponse.fromJson(
      response.data,
      fromJsonT: (json) => data,
    );
  }

  @override
  Future<BaseResponse<int>> changeCountry(String countryId) async {
    final response = await dioFactory.post(
      EndPoints.changeCountry,
      data: {
        "country_id": countryId,
      },
    );

    return BaseResponse.fromJson(response.data);
  }
}
