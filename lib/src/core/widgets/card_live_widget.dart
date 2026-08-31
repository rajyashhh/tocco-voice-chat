import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/country_icon.dart';
import 'package:general/src/core/widgets/id_with_copy.dart';
import 'package:general/src/core/widgets/reversible_auto_scroll_list_widget.dart';
import 'package:general/src/features/home/presentation/home/view/widgets/lock_pk_icon.dart';
import 'package:general/src/features/home/presentation/home/view/widgets/visitors_count.dart';

import '../../features/agency/agency.dart';
import '../../features/home/domain/entities/room_entity.dart';

class CardLiveWidget extends StatelessWidget {
  final RoomEntity roomEntity;
  final int? index;
  final bool isGame, isFamily, isRoomSearch;
  final UserEntity? user;

  const CardLiveWidget({
    this.index,
    required this.roomEntity,
    this.isGame = false,
    this.isFamily = false,
    this.isRoomSearch = false,
    this.user,
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: isFamily ? null : context.paddingSymmetric(horizontal: 10),
      padding: context.paddingSymmetric(horizontal: 5, vertical: 5),
      decoration: BoxDecoration(
        color: ColorManager.transparent,
        borderRadius: 5.radius,
      ),
      child: Row(
        children: [
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: ImageViewWidget(
              // Cover → host avatar → name initials; never the logo placeholder.
              url: (roomEntity.cover ?? '').isNotEmpty
                  ? roomEntity.cover!
                  : roomEntity.ownerImage ?? '',
              boxFit: BoxFit.cover,
              width: 80.w,
              height: 80.h,
              radius: 8.r,
              displayName: roomEntity.name ?? '',
            ),
          ),
          10.wBox,
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: TextWidget(
                        roomEntity.name ?? '',
                        style: context.bodyLarge.w500
                            .colorExt(ColorManager.textPrimary),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    10.wBox,
                    VisitorsCount(
                      color: ColorManager.textPrimary,
                      count: (roomEntity.visitorsCount ?? 0).toString(),
                      fontSize: 12.sp,
                    ),
                  ],
                ),
                10.hBox,
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    ConstrainedBox(
                      constraints: BoxConstraints(
                        maxWidth: 220.w,
                        minWidth: 1.0.w,
                      ),
                      child: TextWidget(
                        roomEntity.roomIntro ?? '',
                        maxLines: 2,
                        style: context.bodyMedium
                            .colorExt(ColorManager.secondaryText),
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.start,
                      ),
                    ),
                    if (roomEntity.roomLevelImage != "")
                      ImageViewWidget(
                        url: roomEntity.roomLevelImage ?? '',
                        boxFit: BoxFit.cover,
                        width: 40.w,
                        height: 40.h,
                      ),
                  ],
                ),
                5.hBox,
                Row(
                  children: [
                    IdWithCopyIcon(
                      isNeedCopyIcon: false,
                      isSpecial: isRoomSearch
                          ? (user?.specialId != null &&
                              (user?.specialId ?? '') != '')
                          : (roomEntity.ownerSpecialId != null &&
                              (roomEntity.ownerSpecialId ?? '') != ''),
                      specialImg: isRoomSearch
                          ? user?.idImage ?? ''
                          : roomEntity.ownerSpecialId ?? '',
                      color: isRoomSearch
                          ? user?.imageColorEntity?.color
                          : roomEntity.ownerImageColor?.color,
                      img: isRoomSearch
                          ? user?.imageColorEntity?.image
                          : roomEntity.ownerImageColor?.image,
                      idStyle: context.bodyMedium.bold
                          .colorExt(
                            (isRoomSearch
                                    ? Methods.safeHexColor(
                                        user?.imageColorEntity?.color)
                                    : Methods.safeHexColor(
                                        roomEntity.ownerImageColor?.color)) ??
                                ColorManager.greyColor,
                          )
                          .copyWith(height: 0.1, fontSize: 11.sp),
                      userId: roomEntity.uuidOwnerRoom ?? '',
                    ),
                    5.wBox,
                    if (roomEntity.country?.id != 0 &&
                        (roomEntity.country?.name ?? '').isNotEmpty &&
                        roomEntity.isCountryHidden == false)
                      CountryIcon(
                        country: roomEntity.country?.flag ?? '',
                        iso: roomEntity.country?.iso,
                        countryId: (roomEntity.country?.id ?? '-1').toString(),
                        borderRadius: 0.radius,
                      ),
                    5.wBox,
                    if ((roomEntity.achievementImages ?? []).isNotEmpty)
                      Expanded(
                        child: ConstrainedBox(
                          constraints: BoxConstraints(
                            minWidth: 1.0.w,
                            maxHeight: 25.h,
                            minHeight: 20.h,
                          ),
                          child: ReversibleAutoScrollListWidget(
                            items: roomEntity.achievementImages ?? [],
                          ),
                        ),
                      ),
                    5.wBox,
                    const Spacer(),
                    LockAndPkIcon(
                      isPK: roomEntity.isPK ?? false,
                      isPassword: roomEntity.passwordStatus ?? false,
                      isHasLuckyBox: roomEntity.hasLuckyBox ?? false,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
