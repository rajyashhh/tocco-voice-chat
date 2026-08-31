
import 'package:flutter/cupertino.dart';
import 'package:general/src/features/chats/chats.dart';
import 'package:general/src/features/home/presentation/search_screen/bloc/search_manager/search_bloc.dart';
import 'package:general/src/features/home/presentation/search_screen/bloc/search_manager/search_events.dart';
import 'package:general/src/features/home/presentation/search_screen/bloc/search_manager/search_states.dart';
import 'package:general/src/features/profile/presentation/f_f_f_v/bloc/bloc_get_f_f_f/get_follower_or_following_bloc.dart';
import 'package:general/src/features/profile/presentation/f_f_f_v/bloc/bloc_get_f_f_f/get_follower_or_following_event.dart';
import 'package:general/src/features/profile/presentation/f_f_f_v/bloc/bloc_get_f_f_f/get_follower_or_following_state.dart';

import '../../../../auth/domain/entities/user_entity.dart';

part 'components/friends_user_info_row.dart';

part 'components/tab_bar_view_body.dart';

class FriendsPage extends StatefulWidget {
  const FriendsPage({super.key});

  @override
  State<FriendsPage> createState() => _FFFScreenState();
}

class _FFFScreenState extends State<FriendsPage> with TickerProviderStateMixin {
  //late TabController _controller;
  String text = StringManager.following.tr();
  final GetFollowerOrFollowingBloc _bloc = di<GetFollowerOrFollowingBloc>();

  late TextEditingController textController;

  @override
  void initState() {
    textController = TextEditingController();
    if (!_bloc.state.friendsRequestState.isLoaded) {
      _bloc.add(const GetFriendsEvent(loading: true));
    }
    di<SearchBloc>().add(const FriendsSearchAddListenerEvent());

    super.initState();
  }

  @override
  void dispose() {
    //_controller.dispose();
    di<SearchBloc>().add(const FriendsSearchRemoveListenerEvent());
    textController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<GetFollowerOrFollowingBloc, GetFollowerOrFollowingState>(
      bloc: _bloc,
      buildWhen: (prev, curr) => prev.getFriendsRequest != curr.getFriendsRequest || prev.getFriends != curr.getFriends,
      builder: (context, state) {
        return HandlingDataWidget(
          title: StringManager.titleFriend.tr(),
          subTitle: StringManager.subtitleFriend.tr(),
          reqState: state.getFriendsRequest,
          onTap: () {
            di<GetFollowerOrFollowingBloc>()
                .add(const GetFriendsEvent(loading: true));
          },
          child: Column(
            children: [
              15.hBox,
              CustomSearchBar(
                  onChanged: (str) {
                    di<SearchBloc>()
                        .add(SearchFriendsEvent(keyWord: str ?? ''));
                  },
                  onSubmit: (str) {},
                  onPress: () {}),
              BlocBuilder<SearchBloc, SearchStates>(
                  bloc: di<SearchBloc>(),
                  buildWhen: (prev, curr) => prev.reqStateFriends != curr.reqStateFriends || prev.friendsList != curr.friendsList,
                  builder: (context, __) {
                    if (__.reqStateFriends.isLoaded) {
                      return HandlingDataWidget(
                        title: StringManager.titleFriend.tr(),
                        subTitle: StringManager.subtitleFriend.tr(),
                        reqState: __.reqStateFriends,
                        onTap: () {
                          di<GetFollowerOrFollowingBloc>()
                              .add(const GetFriendsEvent(loading: true));
                        },
                        child: Expanded(
                          child: TabViewBody(
                            entity: __.friendsList?.users ?? [],
                            fType_: RelationType.friends,
                            reqState: __.reqStateFriends,
                            controller: textController,
                            scrollControllerOverride: __.friendsScrollCtrl,
                          ),
                        ),
                      );
                    } else {
                      return Expanded(
                        child: TabViewBody(
                          entity: state.getFriends,
                          fType_: RelationType.friends,
                          reqState: state.getFriendsRequest,
                          controller: textController,
                        ),
                      );
                    }
                  }),
            ],
          ),
        );
      },
    );
  }
}

// Todo: sayed
class CustomSearchBar extends StatelessWidget {
  const CustomSearchBar({
    super.key,
    required this.onSubmit,
    required this.onChanged,
    required this.onPress,
    this.controller,
    this.onEditingComplete,
    // this.focusNode
  });

  final void Function(String)? onSubmit;
  final void Function(String?)? onChanged;
  final void Function()? onEditingComplete;
  final VoidCallback onPress;
  final TextEditingController? controller;

  // final FocusNode? focusNode;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 35.h,
      margin: context.paddingSymmetric(horizontal: 10),
      decoration: BoxDecoration(
        borderRadius: 30.radius,
        // color: ColorManager.white,
      ),
      child: TextInputWidget(
        onEditingComplete: onEditingComplete,
        StringManager.pleaseEnterIdFriends.tr(),
        controller: controller,
        keyboardType: TextInputType.number,
        // Theme card surface (was fixed white — clashed on the dark default).
        fillColor: ColorManager.surfaceCardColor,
        onChanged: onChanged,
        textInputAction: TextInputAction.search,
        onSubmitted: onSubmit,
        textColor: ColorManager.secondaryText.withValues(alpha: (0.3)),
        border: OutlineInputBorder(
          borderSide: BorderSide(
            color: ColorManager.surfaceCardColor,
            width: 1,
          ),
          borderRadius: 30.radius,
        ),
        focusedBorder: OutlineInputBorder(
          borderSide: BorderSide(
            color: ColorManager.surfaceCardColor,
            width: 1,
          ),
          borderRadius: 30.radius,
        ),
        enabledBorder: OutlineInputBorder(
          borderSide: BorderSide(
            color: ColorManager.surfaceCardColor,
            width: 1,
          ),
          borderRadius: 30.radius,
        ),
        errorBorder: OutlineInputBorder(
          borderSide: const BorderSide(
            color: ColorManager.redAccount,
            width: 1,
          ),
          borderRadius: 30.radius,
        ),
        suffixIcon: InkWell(
          onTap: () {
            controller?.clear();
            onChanged?.call('');
            di<SearchBloc>().add(const SearchFriendsEvent(keyWord: ''));
          },
          child: Icon(
            CupertinoIcons.clear_circled_solid,
            color: ColorManager.grey.withValues(alpha: (0.4)),
            size: 15.r,
          ),
        ),
        prefixIcon: Icon(
          Icons.search,
          color: ColorManager.grey.withValues(alpha: (0.4)),
        ),
        hintStyle: context.bodyMedium
            .colorExt(ColorManager.greyTextColor.withValues(alpha: (0.8))),
        // cursorColor: ColorManager.black,
        textStyle: context.bodyLarge.colorExt(ColorManager.textPrimary),
        contentPadding: context.paddingOnly(
          top: 0,
          end: 0,
          bottom: 0,
          start: 0,
        ),
      ),
    );
  }
}
