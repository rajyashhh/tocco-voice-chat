import 'package:general/src/core/index.dart';
import 'package:general/src/features/room/room.dart';

class TabBarLuckyBoxBody extends StatelessWidget {
  const TabBarLuckyBoxBody({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<LuckyBoxBloc, LuckyBoxState>(
      bloc: di<LuckyBoxBloc>(),
      buildWhen: (prev, curr) => prev.isLuckyBoxTap != curr.isLuckyBoxTap,
      builder: (context, state) {
        return ValueListenableBuilder(
          valueListenable: LuckyBoxVariables.notifierTypeBox,
          builder: (context, value, child) => Align(
            alignment: AlignmentDirectional.topCenter,
            child: Container(
              width: 400.h,
              height: 40.h,
              decoration: BoxDecoration(
                color: const Color(0xFF8E0634),
                borderRadius: 20.radius,
              ),
              child: Row(
                children: [
                  Expanded(
                    flex: 1,
                    child: InkWell(
                      onTap: () {
                        di<LuckyBoxBloc>()
                            .add(ChangeTabBarState(isLuckyBox: true));
                      },
                      child: Container(
                        decoration: BoxDecoration(
                          gradient: state.isLuckyBoxTap == true
                              ? LinearGradient(
                                  stops: const [0.0, 0.0, 1.0, 1.0],
                                  colors: ColorManager.luckyBoxGradient,
                                )
                              : null,
                          borderRadius: 30.radius,
                          border: state.isLuckyBoxTap == true
                              ? Border.all(color: Colors.white, width: 0.5)
                              : null,
                        ),
                        child: Center(
                          child: Text(
                            StringManager.luckyBox.tr(),
                            style: context.bodyLarge.w600
                                .colorExt(ColorManager.yellow),
                          ),
                        ),
                      ),
                    ),
                  ),
                  Expanded(
                    flex: 1,
                    child: InkWell(
                      onTap: () {
                        di<LuckyBoxBloc>()
                            .add(ChangeTabBarState(isLuckyBox: false));
                      },
                      child: Container(
                        decoration: BoxDecoration(
                          gradient: state.isLuckyBoxTap != true
                              ? LinearGradient(
                                  stops: const [0.0, 0.0, 1.0, 1.0],
                                  colors: ColorManager.luckyBoxGradient,
                                )
                              : null,
                          borderRadius: 30.radius,
                          border: state.isLuckyBoxTap != true
                              ? Border.all(color: Colors.white, width: 0.5)
                              : null,
                        ),
                        child: Center(
                          child: Text(
                            StringManager.superBox.tr(),
                            style: context.bodyLarge.w600
                                .colorExt(ColorManager.yellow),
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}
