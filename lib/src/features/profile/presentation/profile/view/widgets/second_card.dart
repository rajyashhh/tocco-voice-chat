part of 'package:general/src/features/profile/presentation/profile/view/profile_screen.dart';

class SecondCard extends StatelessWidget {
  const SecondCard({super.key});

  @override
  Widget build(BuildContext context) {
    return ProfileCard(
      child: Column(
        children: [
          ListTileBody(
            image: AssetsManager.walletTigerIcon,
            title: StringManager.wallet.tr(),
            onTap: () {
              Navigator.pushNamed(context, Routes.coinsPage);
            },
          ),
          3.hBox,
          ListTileBody(
            image: AssetsManager.mallShoppingIcon,
            title: StringManager.store.tr(),
            onTap: () => context.pushNamedRoute(Routes.mallScreen),
          ),
          3.hBox,

          BlocBuilder<FetchUserDataBloc, FetchUserDataState>(
              bloc: di<FetchUserDataBloc>(),
              buildWhen: (prev, curr) => prev.userEntity?.vip1 != curr.userEntity?.vip1,
              builder: (context, state) {
                return ListTileBody(
                  image: AssetsManager.vip,
                  title: StringManager.vip.tr(),
                  onTap: () => context.pushNamedRoute(Routes.vipScreen),
                  isVip: ((MyDataModel.getInstance().vip1?.img1 ?? "") == "")
                      ? true
                      : false,
                );
              }),
          3.hBox,
          ListTileBody(
            image: AssetsManager.icDecoration,
            title: StringManager.myBag.tr(),
            onTap: () => context.pushNamedRoute(Routes.bagScreen),
          ),
        ],
      ),
    );
  }
}
