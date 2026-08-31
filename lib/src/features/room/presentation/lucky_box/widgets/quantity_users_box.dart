import 'package:general/src/core/index.dart';
import 'package:general/src/features/room/room.dart';

class QuantityUsersBox extends StatelessWidget {
  final List<String> boxes;

  const QuantityUsersBox({super.key, required this.boxes});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<LuckyBoxBloc, LuckyBoxState>(
        bloc: di<LuckyBoxBloc>(),
        buildWhen: (prev, curr) => prev.luckyBoxQuantity != curr.luckyBoxQuantity,
        builder: (context, state) {
          return SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: List.generate(
                boxes.length,
                (index) {
                  return ValueListenableBuilder(
                    valueListenable: LuckyBoxVariables.notifierQuantity,
                    builder: (context, value, child) => InkWell(
                      onTap: () {
                        if (state.luckyBoxQuantity != boxes[index]) {
                          di<LuckyBoxBloc>()
                              .add(SelectLuckyBoxQuantity(index: index));
                        }
                      },
                      child: Container(
                        padding: context.paddingSymmetric(
                            horizontal: 20, vertical: 10),
                        decoration: BoxDecoration(
                          color:
                              state.luckyBoxQuantity == boxes[index].toString()
                                  ? ColorManager.whiteColor
                                  : ColorManager.transparent,
                          border: Border.all(
                            color: state.luckyBoxQuantity ==
                                    boxes[index].toString()
                                ? ColorManager.whiteColor
                                : ColorManager.lightYellow,
                            width: 1.2,
                          ),
                        ),
                        child: Center(
                          child: Text(
                            boxes[index].toString(),
                            style: context.bodyLarge.w700.colorExt(
                              state.luckyBoxQuantity == boxes[index].toString()
                                  ? ColorManager.redAccount
                                  : ColorManager.lightYellow,
                            ),
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          );
        });
  }
}
