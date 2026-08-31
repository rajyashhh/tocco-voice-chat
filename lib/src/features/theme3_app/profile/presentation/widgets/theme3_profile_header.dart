import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/level_container.dart';
import 'package:general/src/core/widgets/vip_container.dart';
import 'package:general/src/features/auth/auth.dart' hide PickImageEvent;
import 'package:general/src/features/profile/presentation/profile/view/page/edit_info_screen/bloc/edit_information/edit_information_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/edit_info_screen/edit_profile_screen.dart';

/// Theme3 (NEXO light profile) header: avatar LEFT, name/ID/flag/edit to its
/// side, per the design brief's light-lavender profile card. Bloc wiring
/// (quick-edit name/bio/photo via [EditInformationBloc]) is copied unchanged
/// from [Theme2ProfileHeader] — only the layout (avatar+details as a ROW
/// instead of a centered COLUMN) and colors (theme3 tokens) differ.
class Theme3ProfileHeader extends StatelessWidget {
  final MyDataEntity data;

  const Theme3ProfileHeader({super.key, required this.data});

  void _editName(BuildContext context) {
    di<EditInformationBloc>().add(AssignInformationEvent());
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16.0)),
      ),
      builder: (_) => EditInfoScreen(
        params: EditProfileParameter(content: '${data.name}', isUsername: true),
      ),
    );
  }

  void _editPhoto(BuildContext context) {
    di<EditInformationBloc>().add(AssignInformationEvent());
    di<EditInformationBloc>().add(const PickImageEvent(fromCamera: false));
  }

  @override
  Widget build(BuildContext context) {
    final user = MyDataModel.getInstance().convertMyDataEntityToUserEntity(data);

    return Container(
      margin: EdgeInsets.fromLTRB(16.w, 16.h, 16.w, 0),
      padding: EdgeInsets.all(16.r),
      decoration: BoxDecoration(
        color: ColorManager.theme3Card,
        borderRadius: BorderRadius.circular(20.r),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            height: (user.frame != null && user.frame != '') ? 100.w : 64.w,
            width: (user.frame != null && user.frame != '') ? 100.w : 64.w,
            child: Stack(
              clipBehavior: Clip.none,
              alignment: Alignment.center,
              children: [
                InkWell(
                  onTap: () => Methods().userProfileNavigator(
                    context: context,
                    user_: user,
                    userId: user.id.toString() ==
                            MyDataModel.getInstance().id.toString()
                        ? null
                        : user.id.toString(),
                  ),
                  child: UserImage(
                    image: user.profile?.image ?? '',
                    displayName: user.name,
                    imageSize: 64.w,
                    borderRadius: 32.radius,
                    frame: user.frame ?? '',
                    frameType: user.frameType ?? '',
                    frameSize:
                        (user.frame != null && user.frame != '') ? 100.w : 64.w,
                    boxFit: BoxFit.fill,
                  ),
                ),
                Positioned(
                  right: 0,
                  bottom: 0,
                  child: _QuickEditBadge(onTap: () => _editPhoto(context)),
                ),
              ],
            ),
          ),
          12.wBox,
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(
                        user.name ?? '',
                        style: TextStyle(
                          color: ColorManager.theme3TextPrimary,
                          fontSize: 18.sp,
                          fontWeight: FontWeight.w700,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    6.wBox,
                    if ((user.level?.senderImage ?? '').isNotEmpty)
                      LevelContainer(
                        boxFit: BoxFit.contain,
                        level: user.level?.senderLevel ?? 1,
                        image: user.level?.senderImage ?? "",
                      ),
                    4.wBox,
                    InkWell(
                      onTap: () => _editName(context),
                      borderRadius: BorderRadius.circular(20.r),
                      child: Padding(
                        padding: EdgeInsets.all(2.r),
                        child: Icon(
                          Icons.edit,
                          size: 14.sp,
                          color: ColorManager.theme3TextSecondary,
                        ),
                      ),
                    ),
                  ],
                ),
                6.hBox,
                Row(
                  children: [
                    if (CountryFlagWidget.canRender(
                            iso: user.country?.iso,
                            fallbackUrl: user.country?.photo) &&
                        user.isCountryHidden == false) ...[
                      CountryFlagWidget(
                        iso: user.country?.iso,
                        fallbackUrl: user.country?.photo,
                        height: 14,
                        width: 20,
                        boxFit: BoxFit.cover,
                      ),
                      6.wBox,
                    ],
                    if (user.vip != null && user.vip?.level != 0) ...[
                      VipContainer(
                        vip: user.vip!.img1 ?? '',
                        width: 30.w,
                        boxFit: BoxFit.contain,
                      ),
                      6.wBox,
                    ],
                    Flexible(
                      child: Text(
                        'ID:${user.uuid ?? user.id ?? ''}',
                        style: TextStyle(
                          color: ColorManager.theme3TextSecondary,
                          fontSize: 12.sp,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    4.wBox,
                    GestureDetector(
                      onTap: () {
                        Clipboard.setData(
                          ClipboardData(text: '${user.uuid ?? user.id ?? ''}'),
                        );
                        Methods.showToast(
                          context,
                          message: StringManager.theTextHasBeenCopied.tr(),
                        );
                      },
                      child: Icon(
                        Icons.copy,
                        size: 13.sp,
                        color: ColorManager.theme3TextSecondary,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          InkWell(
            onTap: () => Navigator.pushNamed(
              context,
              Routes.editProfile,
              arguments: MyDataModel.getInstance(),
            ),
            borderRadius: BorderRadius.circular(20.r),
            child: Padding(
              padding: EdgeInsets.all(4.r),
              child: Icon(
                Icons.edit_note,
                color: ColorManager.theme3TextSecondary,
                size: 22.sp,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Camera badge anchored on the avatar for photo quick-edit.
class _QuickEditBadge extends StatelessWidget {
  final VoidCallback onTap;

  const _QuickEditBadge({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20.r),
      child: Container(
        padding: EdgeInsets.all(4.r),
        decoration: BoxDecoration(
          gradient: const LinearGradient(colors: ColorManager.theme3CtaGradient),
          shape: BoxShape.circle,
          border: Border.all(color: ColorManager.white, width: 1.5),
        ),
        child: Icon(
          Icons.photo_camera_outlined,
          size: 12.sp,
          color: ColorManager.white,
        ),
      ),
    );
  }
}

/// Stats row: Visitors | Following | Followers, per the design brief.
class Theme3StatsRow extends StatelessWidget {
  final MyDataEntity data;

  const Theme3StatsRow({super.key, required this.data});

  @override
  Widget build(BuildContext context) {
    final user = MyDataModel.getInstance().convertMyDataEntityToUserEntity(data);

    return Container(
      margin: EdgeInsets.symmetric(horizontal: 16.w),
      padding: EdgeInsets.symmetric(vertical: 14.h),
      decoration: BoxDecoration(
        color: ColorManager.theme3Card,
        borderRadius: BorderRadius.circular(16.r),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _StatItem(
            count: '${data.profileVisitors ?? 0}',
            label: StringManager.visitors.tr(),
            onTap: () {
              Navigator.pushNamed(context, Routes.friendFollowing, arguments: 3);
              di<FetchUserDataBloc>().add(const ReadCounterVistorsEvent());
            },
          ),
          _StatItem(
            count: '${user.numberOfFollowings ?? 0}',
            label: StringManager.following.tr(),
            onTap: () {
              Navigator.pushNamed(context, Routes.friendFollowing, arguments: 0);
              di<FetchUserDataBloc>().add(const ReadCounterFollowingsEvent());
            },
          ),
          _StatItem(
            count: '${user.numberOfFans ?? 0}',
            label: StringManager.followers.tr(),
            onTap: () {
              Navigator.pushNamed(context, Routes.friendFollowing, arguments: 1);
              di<FetchUserDataBloc>().add(const ReadCounterFollowersEvent());
            },
          ),
        ],
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  final String count;
  final String label;
  final VoidCallback onTap;

  const _StatItem({required this.count, required this.label, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Column(
        children: [
          Text(
            count,
            style: TextStyle(
              color: ColorManager.theme3TextPrimary,
              fontSize: 18.sp,
              fontWeight: FontWeight.w700,
            ),
          ),
          4.hBox,
          Text(
            label,
            style: TextStyle(
              color: ColorManager.theme3TextSecondary,
              fontSize: 12.sp,
            ),
          ),
        ],
      ),
    );
  }
}
