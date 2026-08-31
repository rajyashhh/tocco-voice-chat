import 'package:general/src/features/setting/presentation/invitation/bloc/invite_bloc.dart';
import 'package:general/src/features/setting/presentation/invitation/view/widgets/user_info_card.dart';

import '../../../../../../core/index.dart';

class ParentUsersScreen extends StatefulWidget {
  const ParentUsersScreen({
    super.key,
  });

  @override
  State<ParentUsersScreen> createState() => _ParentUsersScreenState();
}

class _ParentUsersScreenState extends State<ParentUsersScreen> {
  @override
  void initState() {
    di<SendInviteBloc>()
        .add(GetEarnInviteUserEvent());

    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar:  AppBarWidget(
        title: StringManager.invitation.tr(),
      ),
      body: BlocBuilder<SendInviteBloc, SendInviteState>(
        bloc: di<SendInviteBloc>(),
        buildWhen: (prev, curr) => prev.getInviteUserRequest != curr.getInviteUserRequest || prev.getInviteUserSuccess != curr.getInviteUserSuccess,
        builder: (BuildContext context, SendInviteState state) {
          // NOTE: this widget is the Scaffold BODY. Wrapping the list in
          // Expanded here attached a Flexible directly under the Scaffold's
          // CustomMultiChildLayout once the state flipped to loaded ->
          // "MultiChildLayoutParentData is not a subtype of FlexParentData"
          // (Crashlytics, 333 events/5d). The body slot is already bounded,
          // so the ListView needs no Expanded at all.
          final users = state.getInviteUserSuccess ?? const [];
          return HandlingDataWidget(
            reqState: state.getInviteUserRequest,
            title: '',
            subTitle: '',
            child: ListView.builder(
              itemCount: users.length,
              itemBuilder: (context, i) {
                return UserDetailsForParent(
                  data: users[i],
                );
              },
            ),
          );
        },
      ),
    );
  }
}

// switch (stateRequest) {
//   case RequestState.loaded:
//     return SizedBox(
//       width: ConfigSize.screenWidth,
//       height: ConfigSize.screenHeight,
//       child: ListView.builder(
//         itemCount: data.length,
//         itemBuilder: (context, i) {
//           return UserDetailsForParent(
//             data: data[i],
//           );
//         },
//       ),
//     );
//   case RequestState.loading:
//     return const LoadingWidget();
//   case RequestState.error:
//     log('user${message}');
//     return CustomErrorWidget(
//       message: message,
//     );
// }
