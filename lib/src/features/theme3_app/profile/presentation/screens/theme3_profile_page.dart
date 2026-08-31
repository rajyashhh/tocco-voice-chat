import 'package:general/src/core/index.dart';
import 'package:general/src/features/agency/presentation/charge_agency/view/component/host_withdrawel_screen/bloc/get_setting_manager/get_setting_bloc.dart';
import 'package:general/src/features/auth/auth.dart';
import 'package:general/src/features/profile/presentation/coins/bloc/my_store_bloc/my_store_bloc.dart';
import 'package:general/src/features/theme3_app/profile/presentation/widgets/theme3_profile_header.dart';
import 'package:general/src/features/theme3_app/profile/presentation/widgets/theme3_quick_grid.dart';
import 'package:general/src/features/theme3_app/profile/presentation/widgets/theme3_menu_list.dart';

/// Theme3 (NEXO) "Me" tab — light lavender profile screen per the design
/// brief. Bloc wiring (fetch/refresh sequencing of [FetchUserDataBloc],
/// [GetSettingBloc], [MyStoreBloc] and the [HandlingDataWidget]/
/// [RefreshIndicatorWidget] chrome) is copied unchanged from
/// [Theme2ProfilePage] — only the layout/colors differ.
class Theme3ProfilePage extends StatelessWidget {
  const Theme3ProfilePage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorManager.theme3Background,
      body: SafeArea(
        child: BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
          bloc: di<FetchUserDataBloc>(),
          buildWhen: (prev, curr) =>
              prev.reqState != curr.reqState ||
              prev.userEntity != curr.userEntity,
          builder: (context, state) {
            return RefreshIndicatorWidget(
              onRefresh: () async {
                di<FetchUserDataBloc>().add(
                  const FetchMyDataEvent(isLoading: false),
                );
                di<GetSettingBloc>().add(const GetSettingsEvent());
                di<MyStoreBloc>().add(const GetMyStoreEvent());
              },
              child: HandlingDataWidget(
                reqState: state.reqState,
                title: '',
                subTitle: '',
                onTap: () => di<FetchUserDataBloc>()
                    .add(const FetchMyDataEvent(isLoading: false)),
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Theme3ProfileHeader(
                        data: state.userEntity ?? const MyDataEntity(),
                      ),
                      16.hBox,
                      Theme3StatsRow(
                        data: state.userEntity ?? const MyDataEntity(),
                      ),
                      16.hBox,
                      Theme3QuickGrid(
                        data: state.userEntity ?? const MyDataEntity(),
                      ),
                      16.hBox,
                      Theme3MenuList(
                        data: state.userEntity ?? const MyDataEntity(),
                      ),
                      30.hBox,
                    ],
                  ),
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}