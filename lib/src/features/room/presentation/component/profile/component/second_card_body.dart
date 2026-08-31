import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/reversible_auto_scroll_list_widget.dart';
import 'package:general/src/features/auth/domain/entities/user_entity.dart';
import 'package:general/src/features/room/presentation/component/profile/widgets/row_item_widget.dart';
import '../../../../data/model/user_in_room_model.dart';
import '../bloc/extra_data_profile_bloc.dart';

class SecondCardRoomProfileBody extends StatelessWidget {
  const SecondCardRoomProfileBody({super.key, required this.userData});

  final UserInRoomModel userData;
  @override
  Widget build(BuildContext context) {
    return BlocBuilder<FetchExtraDataBloc, FetchExtraDataStates>(
      bloc: di.isRegistered<FetchExtraDataBloc>() ? di<FetchExtraDataBloc>() : null,
      buildWhen: (prev, curr) => prev.extraProfileData != curr.extraProfileData,
      builder: (context, state) {
        final hasCp =
            (state.extraProfileData?.cp?.user?.image ?? '').isNotEmpty;
        final hasMedals =
            (state.extraProfileData?.achievementImages ?? []).isNotEmpty;
        final hasGifts = (state.extraProfileData?.gifts ?? []).isNotEmpty;
        final hasFamily = state.extraProfileData?.familyData != null &&
            (state.extraProfileData?.familyData?.id ?? 0) != 0;

        return Column(
          children: [
            // CP relationship — only when the user actually HAS a CP.
            if (hasCp) ...[
              InkWell(
                radius: 10,
                onTap: () {
                  context.popRoute();
                  Methods().userProfileNavigator(
                    context: context,
                    user_: const UserEntity(),
                    userId: userData.id.toString(),
                  );
                },
                child: RowItemWidget(
                  title: StringManager.cpRelationship,
                  widget: Stack(
                    alignment: Alignment.center,
                    children: [
                      Row(
                        children: [
                          ImageViewWidget(
                              height: 50.h,
                              width: 50.h,
                              shape: BoxShape.circle,
                              displayName: userData.name ?? '',
                              url: userData.image ?? ''),
                          8.wBox,
                          ImageViewWidget(
                              height: 50.h,
                              width: 50.h,
                              shape: BoxShape.circle,
                              displayName:
                                  state.extraProfileData?.cp?.user?.name ?? '',
                              url:
                                  state.extraProfileData?.cp?.user?.image ?? ""),
                        ],
                      ),
                      Image.asset(
                        AssetsManager.heartCpProfile,
                        height: 30.h,
                        width: 30.h,
                      ),
                    ],
                  ),
                ),
              ),
              5.hBox,
            ],
            // وسام (medal) — only when the user actually HAS medals.
            if (hasMedals) ...[
              InkWell(
                radius: 10,
                onTap: () {
                  context.popRoute();
                  Methods().userProfileNavigator(
                    context: context,
                    user_: const UserEntity(),
                    userId: userData.id.toString(),
                  );
                },
                child: RowItemWidget(
                  title: StringManager.wesam,
                  widget: Padding(
                    padding: context.paddingSymmetric(vertical: 10),
                    child: ConstrainedBox(
                      constraints: BoxConstraints(
                        maxWidth: 180.w,
                        maxHeight: 35.h,
                        minHeight: 35.h,
                      ),
                      child: ReversibleAutoScrollListWidget<String>(
                        items:
                            state.extraProfileData?.achievementImages ?? [],
                        size: 35,
                      ),
                    ),
                  ),
                ),
              ),
              5.hBox,
            ],
            // Gifts — only when the user actually HAS gifts.
            if (hasGifts) ...[
              InkWell(
                radius: 10,
                onTap: () {
                  context.popRoute();
                  Methods().userProfileNavigator(
                    context: context,
                    user_: const UserEntity(),
                    userId: userData.id.toString(),
                  );
                },
                child: RowItemWidget(
                  title: StringManager.gift,
                  widget: Padding(
                    padding: context.paddingSymmetric(vertical: 10),
                    child: ConstrainedBox(
                      constraints: BoxConstraints(
                        maxWidth: 180.w,
                        maxHeight: 35.h,
                        minHeight: 35.h,
                      ),
                      child: ReversibleAutoScrollListWidget<String>(
                        items: state.extraProfileData?.gifts
                                ?.map((item) => item.data.img)
                                .toList() ??
                            [],
                        size: 35,
                      ),
                    ),
                  ),
                ),
              ),
              5.hBox,
            ],
            // Family — only when the user actually belongs to a family.
            if (hasFamily)
              InkWell(
                radius: 10,
                onTap: () {
                  Navigator.pushNamed(context, Routes.familyScreen,
                      arguments: '${state.extraProfileData?.familyData?.id}');
                },
                child: RowItemWidget(
                  title: StringManager.family,
                  widget: TextWidget(
                    state.extraProfileData?.familyData?.name ?? '',
                    style: context.bodyMedium.bold.colorExt(ColorManager.grey),
                  ),
                ),
              ),
          ],
        );
      },
    );
  }
}
