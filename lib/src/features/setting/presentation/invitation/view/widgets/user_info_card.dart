import 'package:general/src/core/index.dart';
import 'package:general/src/features/setting/domain/entities/invite_user_entity.dart';

class UserDetailsForParent extends StatelessWidget {
  final InvitationUsersEntity data;
  const UserDetailsForParent({super.key, required this.data});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: 20.w,
      ),
      child: Container(
        padding: EdgeInsets.symmetric(
          horizontal: 20.w,
          vertical: 10.h,
        ),
        margin: EdgeInsets.symmetric(
          vertical: 10.h,
        ),
        decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(
              10.h,
            ),
            border: Border.all(color: ColorManager.mainColor, width: 1)),
        child: Column(
          children: [
            5.hBox,
            TextWidget(
              StringManager.profitProcess.tr(),
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            5.hBox,
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                UserImage(
                  image: data.image,
                  displayName: data.name,
                  imageSize: 60.w,
                ),
                TextWidget(
                  data.name,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
            5.hBox,
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                TextWidget(
                  StringManager.userID.tr(),
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                TextWidget(
                  data.invitedId.toString(),
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
            5.hBox,
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                TextWidget(StringManager.coinsCharged.tr(),
                    style: Theme.of(context).textTheme.bodyMedium),
                TextWidget(
                  data.userCharge.toString(),
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
            5.hBox,
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                TextWidget(
                  StringManager.percentage.tr(),
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                TextWidget(
                  data.parentPercentage.toString(),
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
