import 'package:general/src/features/chats/chats.dart';

import 'widgets/notification_card.dart';

class NotificationScreen extends StatefulWidget {
  const NotificationScreen({super.key});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    if (!di<GetSystemChatBloc>().state.systemReqState.isLoaded) {
      di<GetSystemChatBloc>().add(const GetSystemChatEvent());
    }

    _scrollController.addListener(() {
      // لو عايز تزود لما يوصل لأول القائمة (بما إنها reversed)
      if (_scrollController.position.pixels ==
          _scrollController.position.maxScrollExtent) {
        di<GetSystemChatBloc>().add(LoadMoreSystemChatEvent());
      }
    });

    super.initState();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorManager.scaffoldBgAlt,
      appBar: AppBarWidget(
        backgroundColor: ColorManager.scaffoldBg,
        title: StringManager.notification.tr(),
      ),
      body: Column(
        children: [
          Expanded(
            child: BlocBuilder<GetSystemChatBloc, GetSystemChatsStates>(
                bloc: di<GetSystemChatBloc>(),
                buildWhen: (prev, curr) =>
                    prev.systemReqState != curr.systemReqState ||
                    prev.systemEntity != curr.systemEntity,
                builder: (BuildContext context, GetSystemChatsStates state) {
                  return HandlingDataWidget(
                    reqState: state.systemReqState,
                    title: StringManager.noAlerts.tr(),
                    subTitle: StringManager.emptyNotifications.tr(),
                    onTap: () => di<GetSystemChatBloc>()
                        .add(const GetSystemChatEvent(isLoading: false)),
                    child: ListView.builder(
                      controller: _scrollController,
                      padding: const EdgeInsets.only(
                        top: 5,
                        bottom: 3,
                      ),
                      itemBuilder: (context, index) {
                        if (state.systemEntity[index].title != "") {
                          return NotificationCard(
                            userId: state.systemEntity[index].fromUserId,
                            index: index,
                            content: state.systemEntity[index].title,
                            created: state.systemEntity[index].created,
                            img: state.systemEntity[index].img,
                          );
                        }
                        return const SizedBox();
                      },
                      shrinkWrap: true,
                      reverse: true,
                      physics: const AlwaysScrollableScrollPhysics(),
                      itemCount: state.systemEntity.length,
                    ),
                  );
                }),
          ),
        ],
      ),
    );
  }
}
