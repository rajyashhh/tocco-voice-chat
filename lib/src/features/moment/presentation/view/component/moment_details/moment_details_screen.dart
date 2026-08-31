import 'package:general/src/core/widgets/gender_widget.dart';
import 'package:general/src/features/moment/moment.dart';
import 'package:readmore/readmore.dart';
import '../../../../../../core/index.dart';

class MomentDetailstScreen extends StatefulWidget {
  final int momentId;
  final int currentMomentIndex;
  final int? imageIndex;
  final MomentEntity currentMoment;
  final MomentType type;
  final MomentBloc momentBloc;

  const MomentDetailstScreen(
      {super.key,
      required this.momentBloc,
      required this.momentId,
      required this.type,
      this.imageIndex,
      required this.currentMoment,
      required this.currentMomentIndex});

  @override
  State<MomentDetailstScreen> createState() => _MomentDetailstScreenState();
}

class _MomentDetailstScreenState extends State<MomentDetailstScreen>
    with TickerProviderStateMixin {
  int currentPage = 0;
  @override
  initState() {
    currentPage = widget.imageIndex ?? 0;
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<MomentBloc, MomentStates>(
      bloc: widget.momentBloc,
      buildWhen: (prev, curr) =>
          prev.moments != curr.moments ||
          prev.followMoments != curr.followMoments ||
          prev.myMoments != curr.myMoments ||
          prev.latestMoments != curr.latestMoments,
      builder: (context, state) {
        return SafeArea(
          child: Scaffold(
            backgroundColor: ColorManager.black,
            resizeToAvoidBottomInset: true,
            body: Stack(
              children: [
                SizedBox(
                  height: ScreenUtil().screenHeight * 0.95,
                  child: PageView.builder(
                    itemCount: widget.currentMoment.images.length,
                    controller:
                        PageController(initialPage: widget.imageIndex ?? 0),
                    onPageChanged: (index) {
                      setState(() {
                        currentPage = index;
                      });
                    },
                    itemBuilder: (context, index) {
                      return InteractiveViewer(
                        child: ImageViewWidget(
                          url: widget.currentMoment.images[index].image,
                          isLocal: widget.currentMoment.images[index].isLocal,
                          boxFit: BoxFit.contain,
                          width: double.infinity,
                        ),
                      );
                    },
                  ),
                ),
                SizedBox(
                  height: 50.h,
                  child: AppBarWidget(
                    title:
                        '${currentPage + 1}/${widget.currentMoment.images.length}',
                    backgroundColor: ColorManager.transparent,
                    titleStyle: context.bodyMedium
                        .colorExt(ColorManager.white)
                        .size(16),
                    iconColor: ColorManager.white,
                  ),
                ),
                Positioned(
                  bottom: 0,
                  child: Container(
                    width: ScreenUtil().screenWidth,
                    padding:
                        context.paddingSymmetric(vertical: 5, horizontal: 15),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        GestureDetector(
                          onTap: () {
                            Methods().userProfileNavigator(
                              context: context,
                              userId: '${widget.currentMoment.userId}',
                            );
                          },
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.start,
                            children: [
                              UserImage(
                                image: widget.currentMoment.userImage,
                                displayName: widget.currentMoment.userName,
                                frame: widget.currentMoment.frame,
                                frameSize: 75.w,
                                imageSize: 50.w,
                                // positionedBottom: 13,
                                boxFit: BoxFit.fill,
                              ),
                              10.wBox,
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                mainAxisAlignment: MainAxisAlignment.start,
                                children: [
                                  TextWidget(
                                    widget.currentMoment.userName,
                                    style: context.bodySmall.w600
                                        .colorExt(ColorManager.white)
                                        .size(16),
                                  ),
                                  10.hBox,
                                  GenderWidget(
                                    age: widget.currentMoment.age,
                                    gender: widget.currentMoment.gender,
                                  )
                                ],
                              ),
                              const Spacer(),
                              if (widget.type != MomentType.myMoment)
                                if (!(widget.type == MomentType.recommend
                                            ? state.moments[
                                                widget.currentMomentIndex]
                                            : widget.type == MomentType.follow
                                                ? state.followMoments[
                                                    widget.currentMomentIndex]
                                                : widget.type ==
                                                        MomentType.myMoment
                                                    ? state.myMoments[widget
                                                        .currentMomentIndex]
                                                    : state.latestMoments[widget
                                                        .currentMomentIndex])
                                        .isFollow &&
                                    MyDataModel.getInstance().id.toString() !=
                                        widget.currentMoment.userId.toString())
                                  InkWell(
                                    onTap: () {
                                      widget.momentBloc.add(MomentFollowEvent(
                                          momentId:
                                              widget.currentMoment.momentId,
                                          currentMoment: widget.currentMoment,
                                          userID: widget.currentMoment.userId));
                                    },
                                    child: Container(
                                      padding: context.paddingSymmetric(
                                          horizontal: 10, vertical: 5),
                                      margin: context.paddingSymmetric(
                                        horizontal: 10,
                                      ),
                                      decoration: BoxDecoration(
                                        color: ColorManager.primary,
                                        shape: BoxShape.rectangle,
                                        borderRadius: 20.radius,
                                      ),
                                      child: Row(
                                        mainAxisAlignment:
                                            MainAxisAlignment.center,
                                        children: [
                                          TextWidget(StringManager.follow.tr(),
                                              style: context.bodyMedium
                                                  .size(14)
                                                  .colorExt(
                                                      ColorManager.whiteColor))
                                        ],
                                      ),
                                    ),
                                  )
                            ],
                          ),
                        ),
                        10.hBox,
                        ReadMoreText(
                          widget.currentMoment.moment,
                          trimMode: TrimMode.Line,
                          trimLines: 2,
                          style: TextStyle(
                              fontSize: 15.sp,
                              color: ColorManager.white,
                              fontWeight: FontWeight.w600),
                          trimCollapsedText: StringManager.showMore,
                          trimExpandedText: StringManager.showLess,
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
