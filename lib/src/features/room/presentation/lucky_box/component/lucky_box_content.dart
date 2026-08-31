import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/on_multiable_tab.dart';
import 'package:general/src/features/room/presentation/lucky_box/widgets/coins_lucky_box.dart';
import 'package:general/src/features/room/presentation/lucky_box/widgets/quantity_users_box.dart';
import 'package:general/src/features/room/room.dart';
import 'tab_bar_body.dart';

class LuckyBoxContent extends StatefulWidget {
  const LuckyBoxContent({
    super.key,
    required this.luckyBoxEntity,
    required this.room,
  });
  final LuckyBoxEntity? luckyBoxEntity;
  final EnterRoomModel room;
  @override
  State<LuckyBoxContent> createState() => LuckyBoxContentState();
}

class LuckyBoxContentState extends State<LuckyBoxContent>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();

    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    )..repeat(reverse: true);

    _scaleAnimation = Tween<double>(begin: 1.0, end: 1.1).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<LuckyBoxBloc, LuckyBoxState>(
      bloc: di<LuckyBoxBloc>(),
      buildWhen: (prev, curr) => prev.isLuckyBoxTap != curr.isLuckyBoxTap,
      builder: (context, state) {
        return ValueListenableBuilder(
          valueListenable: LuckyBoxVariables.notifierTypeBox,
          builder: (context, value, child) => Container(
            height: ScreenUtil().screenHeight / 1.85,
            width: ScreenUtil().screenWidth,
            padding: context.paddingSymmetric(horizontal: 20),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.only(
                topLeft: 15.radiusCircular,
                topRight: 15.radiusCircular,
              ),
              image: DecorationImage(
                image: AssetImage(
                  AssetsManager.bgLuckyBox,
                ),
                fit: BoxFit.fill,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                20.hBox,
                const TabBarLuckyBoxBody(),
                20.hBox,
                Text(
                  StringManager.coins_.tr(),
                  style: context.bodySmall.w600.colorExt(ColorManager.yellow),
                ),
                // Detect Coins
                10.hBox,
                CoinsLuckyBox(
                  boxes: state.isLuckyBoxTap == true
                      ? widget.luckyBoxEntity?.normalBox ?? []
                      : widget.luckyBoxEntity?.superBox ?? [],
                ),
                if (state.isLuckyBoxTap != true) ...[
                  30.hBox,
                  Align(
                    alignment: AlignmentDirectional.topCenter,
                    child: Container(
                      width: MediaQuery.sizeOf(context).width,
                      height: 40.h,
                      padding: context.paddingAll(3),
                      decoration: BoxDecoration(
                        borderRadius: 30.radius,
                        border: Border.all(color: ColorManager.whiteColor),
                        gradient: LinearGradient(
                          colors: [
                            ColorManager.redAccount,
                            ColorManager.whiteColor.withValues(alpha: 0.5),
                            ColorManager.whiteColor.withValues(alpha: 0.6),
                          ],
                        ),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          5.wBox,
                          UserImage(
                            image:
                                '${MyDataModel.getInstance().profile?.image}',
                            displayName: MyDataModel.getInstance().name ?? '',
                            imageSize: 35.h,
                            borderRadius: 30.radius,
                          ),
                          10.wBox,
                          Text(
                            "ID: ${MyDataModel.getInstance().uuid}",
                            style: context.bodyLarge
                                .colorExt(ColorManager.darkYellow),
                          ),
                          10.wBox,
                          Text(
                            StringManager.sendASuperLuck.tr(),
                            style: context.titleLarge.w400
                                .colorExt(ColorManager.roomTextPrimary),
                          ),
                        ],
                      ),
                    ),
                  ),
                  10.hBox,
                  Align(
                    alignment: AlignmentDirectional.topCenter,
                    child: Text(
                      StringManager.superLuckyBagWillBeDisplayedInAllRoom.tr(),
                      style: context.bodyLarge.colorExt(ColorManager.roomTextPrimary),
                    ),
                  ),
                ],
                20.hBox,
                if (state.isLuckyBoxTap == true)
                  Text(StringManager.quantity.tr(),
                      style:
                          context.bodySmall.w600.colorExt(ColorManager.yellow)),
                // Detect Quantity
                if (state.isLuckyBoxTap == true) 5.hBox,
                if (state.isLuckyBoxTap == true &&
                    (state.luckyBoxList ?? []).isNotEmpty)
                  QuantityUsersBox(
                    boxes: state.luckyBoxList ?? [],
                  ),
                const Spacer(),
                // Send Button
                if (((state.luckyBoxItem ?? '').isNotEmpty &&
                        state.isLuckyBoxTap == true) ||
                    (state.isLuckyBoxTap == false &&
                        (state.superBoxCoins ?? '').isNotEmpty))
                  ScaleTransition(
                    scale: _scaleAnimation,
                    child: Align(
                      alignment: AlignmentDirectional.bottomCenter,
                      child: MultiTapCard(
                        onTap: () {
                          if (((state.luckyBoxItem ?? '').isEmpty &&
                                  state.isLuckyBoxTap == true) ||
                              (state.isLuckyBoxTap == false &&
                                  (state.superBoxCoins ?? '').isEmpty)) {
                            Methods.showToast(
                              context,
                              message: StringManager.coinsIsEmpty.tr(),
                            );
                            return;
                          }

                          if ((state.superBoxCoins ?? '').isNotEmpty &&
                              state.isLuckyBoxTap == false) {
                            di<LuckyBoxBloc>().add(
                              SendLuckyBoxEvent(
                                boxId: (widget.luckyBoxEntity?.superBox ?? [])
                                        .isNotEmpty
                                    ? widget
                                        .luckyBoxEntity!
                                        .superBox[
                                            state.indexItemSelectedSuper ?? 0]
                                        .id
                                        .toString()
                                    : '0',
                                roomId: widget.room.id.toString(),
                                quantity: (widget.luckyBoxEntity?.superBox ??
                                            [])
                                        .isNotEmpty
                                    ? widget
                                        .luckyBoxEntity!
                                        .superBox[
                                            state.indexItemSelectedSuper ?? 0]
                                        .userNum
                                        .toString()
                                    : '0',
                              ),
                            );
                          } else {
                            if (state.luckyBoxQuantity == '') {
                              Methods.showToast(
                                context,
                                message: StringManager.quantityIsEmpty.tr(),
                              );
                              return;
                            }
                            di<LuckyBoxBloc>().add(
                              SendLuckyBoxEvent(
                                boxId: (widget.luckyBoxEntity?.normalBox ?? [])
                                        .isNotEmpty
                                    ? widget
                                        .luckyBoxEntity!
                                        .normalBox[
                                            state.indexItemSelectedNormal ?? 0]
                                        .id
                                        .toString()
                                    : '0',
                                roomId: widget.room.id.toString(),
                                quantity: state.luckyBoxQuantity ?? '',
                              ),
                            );
                          }
                        },
                        child: Container(
                          width: 600.h,
                          height: 50.h,
                          padding: EdgeInsetsDirectional.all(7.h),
                          decoration: BoxDecoration(
                            image: DecorationImage(
                              image: AssetImage(
                                AssetsManager.sendBackground,
                              ),
                            ),
                          ),
                          child: Center(
                            child: Text(
                              StringManager.send.tr(),
                              style:
                                  context.titleLarge.colorExt(ColorManager.red),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                5.hBox,
                Align(
                  alignment: AlignmentDirectional.center,
                  child: Text(
                    textAlign: TextAlign.center,
                    StringManager.goldCoins.tr(),
                    style: context.bodyMedium.colorExt(ColorManager.darkYellow),
                  ),
                ),
                Align(
                  alignment: AlignmentDirectional.center,
                  child: Text(
                    (state.isLuckyBoxTap ?? true)
                        ? "${StringManager.boxTime.tr()} "
                            "${(widget.luckyBoxEntity?.normalBox != null && (state.indexItemSelectedNormal ?? 0) < (widget.luckyBoxEntity?.normalBox.length ?? 0)) ? widget.luckyBoxEntity?.normalBox[state.indexItemSelectedNormal ?? 0].time : "0"} (${StringManager.hours.tr()})"
                        : "${StringManager.boxTime.tr()} "
                            "${(widget.luckyBoxEntity?.superBox != null && (state.indexItemSelectedSuper ?? 0) < (widget.luckyBoxEntity?.superBox.length ?? 0)) ? widget.luckyBoxEntity?.superBox[state.indexItemSelectedSuper ?? 0].time : "0"} ${StringManager.minutes.tr()}",
                    style: context.bodyMedium.colorExt(ColorManager.roomTextPrimary),
                  ),
                ),

                10.hBox,
              ],
            ),
          ),
        );
      },
    );
  }
}
