import 'package:general/src/core/index.dart';
import 'package:general/src/features/cp/presentation/cp_store/bloc/cp_relations/cp_relations_bloc.dart';
import 'component/all_friends_view.dart';

class CpStorePage extends StatefulWidget {
  const CpStorePage({super.key});

  @override
  State<CpStorePage> createState() => _CpStorePageState();
}

class _CpStorePageState extends State<CpStorePage> {
  @override
  void initState() {
    if (!di<GetCpRelationsBloc>().state.userStates.isLoaded) {
      di<GetCpRelationsBloc>().add(GetCpRelationsEvents());
    }
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return BackgroundImgWidget(
      child: Scaffold(
        backgroundColor: ColorManager.transparent,
        appBar: AppBarWidget(
          backgroundColor: ColorManager.transparent,
          title: StringManager.relationSpecial.tr(),
          titleStyle: context.bodyLarge.bold.colorExt(ColorManager.textPrimary),
        ),
        body: BlocBuilder<GetCpRelationsBloc, CpRelationsStates>(
          bloc: di<GetCpRelationsBloc>(),
          buildWhen: (prev, curr) =>
              prev.userStates != curr.userStates ||
              prev.errorMessage != curr.errorMessage ||
              prev.data != curr.data,
          builder: (context, state) {
            return HandlingDataWidget(
                reqState: state.userStates,
                title: state.errorMessage,
                subTitle: state.errorMessage,
                child: GridView.builder(
                  padding: context.paddingSymmetric(horizontal: 8, vertical: 8),
                  itemCount: state.data?.data.length ?? 0,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 3,
                    childAspectRatio: 0.8,
                  ),
                  itemBuilder: (context, index) {
                    return GestureDetector(
                      onTap: () {
                        showModalBottomSheet(
                          context: context,
                          isScrollControlled: true,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.only(
                              topRight: 20.radiusCircular,
                              topLeft: 20.radiusCircular,
                            ),
                          ),
                          builder: (context) => AllFriendsView(
                            relationId:
                                state.data?.data[index].id.toString() ?? "",
                          ),
                        );
                      },
                      child: Container(
                        margin: context.paddingAll(5),
                        padding: context.paddingAll(10),
                        decoration: BoxDecoration(
                          borderRadius: 5.radius,
                          color: ColorManager.transparent,
                        ),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            ImageViewWidget(
                              url: EndPoints.getImage(
                                  state.data?.data[index].image ?? ""),
                              width: 60.w,
                              height: 70.h,
                              boxFit: BoxFit.contain,
                            ),
                            5.hBox,
                            TextWidget(
                              state.data?.data[index].title.toString() ?? "",
                              style: context.bodyMedium
                                  .size(12)
                                  .w600
                                  .colorExt(ColorManager.textPrimary),
                              maxLines: 1,
                            ),
                            5.hBox,
                            FittedBox(
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  CoinIcon(
                                    size: 23.h,
                                    fallbackAsset: AssetsManager.cpCoin,
                                  ),
                                  5.wBox,
                                  FittedBox(
                                    child: TextWidget(
                                      state.data?.data[index].price
                                              .toString() ??
                                          "0",
                                      style: context.bodyMedium
                                          .size(12)
                                          .w600
                                          .colorExt(ColorManager.textPrimary),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ));
          },
        ),
      ),
    );
  }
}
