import 'package:general/src/core/index.dart';
import 'package:general/src/features/room/data/model/user_in_room_model.dart';
import 'package:general/src/features/room/presentation/component/profile/new_profile_body.dart';
import 'package:general/src/features/room/presentation/component/profile/profile_body.dart';
import 'package:general/src/features/room/room.dart';

class UserRoomProfile extends StatelessWidget {
  final EnterRoomModel roomData;
  final String userId;

  const UserRoomProfile({
    super.key,
    required this.roomData,
    required this.userId,
  });

  @override
  Widget build(BuildContext context) {
    final int id = int.tryParse(userId) ?? 0;

    // Guard: a non-numeric/invalid identity collapses to 0. Never read the cache
    // or fetch with id 0 — the Hive cache key "0" gets poisoned by earlier failed
    // lookups, so id-0 reads return a DIFFERENT (previously tapped) user. Show the
    // graceful empty state instead of someone else's profile.
    if (id <= 0) {
      return _emptyProfile(context);
    }

    UserInRoomModel? cachedUser;
    try {
      cachedUser = UsersCache().getUser(id);
    } catch (_) {}
    if (cachedUser != null) {
      return _buildProfileBody(cachedUser);
    }

    return FutureBuilder<UserInRoomModel?>(
      future: getUsersByIds([id]).then((map) => map[id]),
      builder: (context, snapshot) {
        // Show loading while waiting for data
        if (snapshot.connectionState == ConnectionState.waiting) {
          return ConstrainedBox(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.sizeOf(context).height * 0.4,
              minHeight: 300.h,
            ),
            child: Container(
              decoration: BoxDecoration(
                color: ColorManager.whiteGrey,
                borderRadius:
                    BorderRadius.vertical(top: Radius.circular(10.r)),
              ),
              child: const Center(
                child: CircularProgressIndicator(
                    color: ColorManager.roomGold),
              ),
            ),
          );
        }

        // If data is null after loading (API failed or user not found)
        if (snapshot.data == null) {
          return _emptyProfile(context);
        }

        return _buildProfileBody(snapshot.data!);
      },
    );
  }

  Widget _emptyProfile(BuildContext context) {
    return ConstrainedBox(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.sizeOf(context).height * 0.4,
        minHeight: 300.h,
      ),
      child: Container(
        decoration: BoxDecoration(
          color: ColorManager.whiteGrey,
          borderRadius: BorderRadius.vertical(top: Radius.circular(10.r)),
        ),
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.error_outline, size: 48.w, color: ColorManager.grey),
              10.hBox,
              TextWidget(
                StringManager.error.tr(),
                style: context.bodyMedium.colorExt(ColorManager.grey),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildProfileBody(UserInRoomModel userData) {
    final myId = MyDataModel.getInstance().id;
    final isMyProfile = myId != null && myId.toString() == userId;
    return ConstantsManager.isTheme1
        ? NewProfileBody(
            roomData: roomData,
            userData: userData,
            userId: userId,
            isMyProfile: isMyProfile,
          )
        : ProfileBody(
            roomData: roomData,
            userData: userData,
            userId: userId,
            isMyProfile: isMyProfile,
          );
  }
}
