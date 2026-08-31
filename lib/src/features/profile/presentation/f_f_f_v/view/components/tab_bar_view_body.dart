part of 'package:general/src/features/profile/presentation/f_f_f_v/view/page/f_f_f_screen.dart';

class TabViewBody extends StatefulWidget {
  final List<UserEntity> entity;
  final List<UserEntity> localList;
  final RelationType fType_;
  final RequestState reqState;
  final bool isPaginating;

  const TabViewBody({
    super.key,
    required this.entity,
    required this.localList,
    required this.fType_,
    required this.reqState,
    this.isPaginating = false,
  });

  @override
  State<TabViewBody> createState() => _TabViewBodyState();
}

class _TabViewBodyState extends State<TabViewBody> {
  final _bloc = di<GetFollowerOrFollowingBloc>();

  @override
  void initState() {
    super.initState();
    _bloc.add(AddListenerFollowingEvent());
    _bloc.add(AddListenerFriendsEvent());
    _bloc.add(AddListenerFollowersEvent());
    _bloc.add(AddListenerVisitorsEvent());
  }

  @override
  void dispose() {
    _bloc.add(RemoveListenerFollowingEvent());
    _bloc.add(RemoveListenerFriendsEvent());
    _bloc.add(RemoveListenerFollowersEvent());
    _bloc.add(RemoveListenerVisitorsEvent());

    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicatorWidget(
      onRefresh: () async {
        if (widget.fType_ == RelationType.following) {
          di<GetFollowerOrFollowingBloc>()
              .add(const GetFollowersThemEvent(loading: false));
        } else if (widget.fType_ == RelationType.friends) {
          di<GetFollowerOrFollowingBloc>()
              .add(const GetFriendsEvent(loading: false));
        } else if (widget.fType_ == RelationType.followers) {
          di<GetFollowerOrFollowingBloc>()
              .add(const GetFollowersEvent(loading: false));
        } else if (widget.fType_ == RelationType.visitors) {
          di<GetFollowerOrFollowingBloc>()
              .add(const GetVisitorsEvent(loading: false));
        }
      },
      child: Column(
        children: [
          Expanded(
            child: ListView.separated(
              itemCount: widget.entity.length,
              padding: context.paddingSymmetric(vertical: 0, horizontal: 0),
              controller: widget.fType_ == RelationType.following
                  ? _bloc.state.scrollControllerFollowing
                  : widget.fType_ == RelationType.followers
                      ? _bloc.state.scrollControllerFollowers
                      : widget.fType_ == RelationType.friends
                          ? _bloc.state.scrollControllerFriends
                          : _bloc.state.scrollControllerVisitor,
              separatorBuilder: (context, index) => 8.hBox,
              itemBuilder: (context, index) {
                return FFFVUserInfoRow(
                  padding: context.paddingOnly(bottom: 10.h),
                  user: widget.entity[index],
                  margin: EdgeInsets.symmetric(horizontal: 5.w),
                  endIcon: widget.fType_ == RelationType.visitors
                      ? TextWidget(
                          Methods().timeDifference(
                            _bloc.state.getVisitors[index].visitTime.toString(),
                          ),
                          style: context.bodyMedium
                              .size(12)
                              .colorExt(ColorManager.secondaryText))
                      : (widget.entity[index].isFollow ?? true)
                          ? TextButtonWidget(
                              onTap: () {
                                showDialog(
                                    context: context,
                                    builder: (_) => AnimatedDialog(
                                          title: StringManager.warning.tr(),
                                          cancelText: StringManager.no.tr(),
                                          description:
                                              StringManager.sureUnFollow.tr(),
                                          titleDivider: false,
                                          conText: StringManager.yes.tr(),
                                          color: ColorManager.primary,
                                          onTap: () {
                                            if (widget.entity[index].isFollow ??
                                                true) {
                                              di<FollowBloc>().add(
                                                UnFollowEvent(
                                                  userId: widget
                                                      .entity[index].id
                                                      .toString(),
                                                  relationType: widget.fType_,
                                                ),
                                              );
                                              Navigator.of(context).pop();
                                            }
                                          },
                                        ));
                              },
                              fontColor: ColorManager.secondaryText,
                              content:
                                  ((widget.entity[index].isFollow ?? true) &&
                                          (widget.entity[index].isFollowingMe ??
                                              false))
                                      ? StringManager.mutualFollow.tr()
                                      : StringManager.followed.tr(),
                              fontSize: 14.sp,
                              fontWeight: FontWeight.w500,
                            )
                          : ButtonWidget(
                              onPressed: () {
                                if (widget.entity[index].isFollow == false) {
                                  di<FollowBloc>().add(
                                    FollowEvent(
                                      userEntity: widget.entity[index],
                                      relationType: widget.fType_,
                                    ),
                                  );
                                }
                              },
                              radius: 30.r,
                              width: 80.w,
                              paddingButton: context.paddingZero(),
                              height: 30.h,
                              titleColor: ColorManager.onDark,
                              backgroundColor: ColorManager.primary,
                              title: StringManager.followings.tr(),
                              fontSize: 14.sp,
                              isFittedBox: false,
                              borderColor: ColorManager.primary,
                              fontWeight: FontWeight.w500,
                            ),
                );
              },
            ),
          ),
          if (widget.isPaginating) const CircleLoadingWidget(),
        ],
      ),
    );
  }
}
