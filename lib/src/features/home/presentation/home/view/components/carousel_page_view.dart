import 'package:carousel_slider/carousel_slider.dart';
import 'package:general/src/features/home/domain/entities/carousel_entity.dart';
import 'package:general/src/features/home/presentation/home/bloc/get_carousel_manager/get_carousel_bloc.dart';
import '../../../../../../core/index.dart';
import '../../../../domain/entities/room_entity.dart';

class CarouselPageView extends StatelessWidget {
  final List<CarouselEntity>? sliders;
  final String source;

  const CarouselPageView({this.sliders, required this.source, super.key});

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: ConstantsManager.isTheme1 ? 5.radius : 15.radius,
      child: Stack(
        children: [
          CarouselSlider(
            items: List.generate(
              sliders?.length ?? 0,
              (index) => Padding(
                padding: context.paddingSymmetric(horizontal: 0),
                child: GestureDetector(
                  onTap: () async {
                    switch (sliders![index].type) {
                      case 'normal':
                        break;
                      case 'room':
                        di<RoomStateManager>().navigateToRoom(
                          RoomEntryRequest(
                            context: context,
                            roomData: RoomEntity(
                              passwordStatus:
                                  sliders?[index].myRoomData?.passwordStatus,
                              ownerId: sliders?[index].ownerId,
                              id: sliders?[index].myRoomData?.id ?? 0,
                              name: sliders?[index].myRoomData?.name ?? "",
                              cover: sliders?[index].myRoomData?.cover ?? "",
                              roomBackground:
                                  sliders?[index].myRoomData?.background ?? "",
                              mode:
                                  sliders?[index].myRoomData?.toString() ?? '',
                              uuidOwnerRoom:
                                  sliders![index].myRoomData?.ownerUuid ?? "",
                              giftPrice:
                                  sliders?[index].myRoomData?.giftPrice ?? "",
                            ),
                            isLive: false,
                          ),
                        );
                        break;
                      case 'link':
                        final url = sliders?[index].url ?? "";
                        if (url.contains("wa.me") ||
                            url.contains("whatsapp.com")) {
                          Methods().whatsAppLink(context, url);
                        } else {
                          if (context.mounted) {
                            Navigator.pushNamed(
                              context,
                              Routes.webViewEvents,
                              arguments: {
                                'url': url,
                                'type': 'events',
                              },
                            );
                          }
                        }
                        break;
                      case 'event':
                        String token = Methods.getUserToken();
                        String lang = HiveManager().getData<String>(
                                KeysManager.USER_BOX,
                                KeysManager.LANG_CODE_KEY) ??
                            "en";
                        String? baseUrl = EndPoints.baseURL;
                        String? bucketName = EndPoints.storageURL;

                        if (context.mounted &&
                            sliders?[index].url != null &&
                            sliders![index].url!.isNotEmpty) {
                          Uri originalUri = Uri.parse(sliders![index].url!);
                          Map<String, String?> updatedParams =
                              Map.from(originalUri.queryParameters);

                          updatedParams.putIfAbsent('token', () => token);
                          updatedParams.putIfAbsent('lang', () => lang);
                          updatedParams.putIfAbsent('base_url', () => baseUrl);
                          updatedParams.putIfAbsent(
                              'bucket_name', () => bucketName);

                          Uri finalUri = originalUri.replace(
                              queryParameters: updatedParams);
                          String finalUrl = finalUri.toString();
                          Navigator.pushNamed(
                            context,
                            Routes.webViewEvents,
                            arguments: {
                              'url': finalUrl,
                              'type': 'events',
                            },
                          );
                        } else if (context.mounted) {
                          // The event's GeneralRole.url is unset from the
                          // admin panel (Admin > General rules) — surface
                          // this instead of a silent no-op tap.
                          Methods.showToast(
                            context,
                            message: StringManager.notAvailabale.tr(),
                            isError: true,
                          );
                        }
                    }
                  },
                  child: sliders?[index].type == 'event'
                      ? EventSliderItem(
                          image: sliders?[index].img,
                          avatar: sliders?[index].avatar,
                          avatar2: sliders?[index].cpAvatar2,
                          name: sliders?[index].cpName,
                          name2: sliders?[index].cpName2,
                          eventType: sliders?[index].eventType,
                        )
                      : ImageViewWidget(
                          url: sliders?[index].img ?? "",
                          boxFit: BoxFit.fill,
                          width: ScreenUtil().screenWidth,
                        ),
                ),
              ),
            ),
            options: CarouselOptions(
              initialPage: 0,
              reverse: false,
              autoPlay: true,
              viewportFraction: 1,
              enableInfiniteScroll: true,
              height: ConstantsManager.isTheme1 ? 100.h : 110.h,
              autoPlayInterval:
                  Duration(seconds: ConstantsManager.isTheme1 ? 3 : 5),
              autoPlayAnimationDuration: Duration(
                  milliseconds: ConstantsManager.isTheme1 ? 400 : 600),
              autoPlayCurve: Curves.linear,
              enlargeCenterPage: false,
              onPageChanged: (index, reason) {
                di<GetCarouselBloc>()
                    .add(ChangeCarsouleIndex(index: index, type: source));
              },
            ),
          ),
          PositionedDirectional(
            start: ConstantsManager.isTheme1 ? 0.0 : 15,
            bottom: ConstantsManager.isTheme1 ? -4.0 : 2.0,
            end: ConstantsManager.isTheme1 ? 0.0 : null,
            child: Padding(
              padding: context.paddingSymmetric(vertical: 8.0),
              child: BlocBuilder<GetCarouselBloc, GetCarouselState>(
                bloc: di<GetCarouselBloc>(),
                buildWhen: (prev, curr) =>
                    prev.topHomeIndex != curr.topHomeIndex ||
                    prev.middleHomeIndex != curr.middleHomeIndex,
                builder: (context, state) {
                  int currentIndex = source == 'homeTop'
                      ? state.topHomeIndex
                      : state.middleHomeIndex;

                  return Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(
                      sliders?.length ?? 0,
                      (index) {
                        bool isActive = index == currentIndex;

                        return AnimatedContainer(
                          duration: const Duration(milliseconds: 100),
                          margin: const EdgeInsets.symmetric(horizontal: 4.0),
                          width: ConstantsManager.isTheme1
                              ? 10.0
                              : isActive
                                  ? 12.0
                                  : 8.0,
                          height:
                              ConstantsManager.isTheme1 ? 4.0 : 8.0,
                          decoration: BoxDecoration(
                            // Panel-driven: active dot uses the icon tint at full
                            // strength, inactive a reduced-opacity derivation of
                            // the SAME color (no hardcoded white/grey).
                            color: isActive
                                ? ColorManager.iconColor
                                : ColorManager.iconColor
                                    .withValues(alpha: 0.4),
                            borderRadius: BorderRadius.circular(4),
                          ),
                        );
                      },
                    ),
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class EventSliderItem extends StatelessWidget {
  const EventSliderItem({
    super.key,
    required this.image,
    required this.avatar,
    required this.avatar2,
    required this.name,
    required this.name2,
    required this.eventType,
  });

  final String? image;
  final String? avatar;
  final String? avatar2;
  final String? name2;
  final String? name;
  final String? eventType;

  @override
  Widget build(BuildContext context) {
    return eventType == "pk_event"
        ? PKSlider(image: image, avatar: avatar)
        : eventType == "charge_event"
            ? RechargeSlider(image: image, avatar: avatar)
            : eventType == "weekly_cp"
                ? CpSlider(
                    image: image,
                    avatar: avatar,
                    avatar2: avatar2,
                    name: name,
                    name2: name2,
                  )
                : WeeklyStarSlider(image: image, avatar: avatar);
  }
}

class PKSlider extends StatelessWidget {
  const PKSlider({
    super.key,
    required this.image,
    required this.avatar,
  });

  final String? image;
  final String? avatar;

  @override
  Widget build(BuildContext context) {
    return Stack(
      alignment: Alignment.center,
      children: [
        Positioned(
          bottom: 20.h,
          right: 38.0.h,
          child: Stack(
            alignment: Alignment.center,
            children: [
              if (avatar != null && avatar != "")
                ImageViewWidget(
                  url: avatar ?? "",
                  width: 65.h,
                  height: 65.h,
                  shape: BoxShape.circle,
                  boxFit: BoxFit.fill,
                )
            ],
          ),
        ),
        image == null
            ? const SizedBox()
            : ClipRRect(
                borderRadius: 10.radius,
                child: ImageViewWidget(
                  width: ScreenUtil().screenWidth,
                  boxFit: BoxFit.fill,
                  url: image ?? '',
                ),
              ),
      ],
    );
  }
}

class RechargeSlider extends StatelessWidget {
  const RechargeSlider({
    super.key,
    required this.image,
    required this.avatar,
  });

  final String? image;
  final String? avatar;

  @override
  Widget build(BuildContext context) {
    return Stack(
      alignment: Alignment.center,
      children: [
        Positioned(
          bottom: 22.0.h,
          right: 33.5.h,
          child: Stack(
            alignment: Alignment.center,
            children: [
              if (avatar != null)
                ImageViewWidget(
                  url: avatar ?? "",
                  boxFit: BoxFit.fill,
                  width: 65.h,
                  shape: BoxShape.circle,
                  height: 65.h,
                )
            ],
          ),
        ),
        image == null
            ? const SizedBox()
            : ClipRRect(
                borderRadius: 10.radius,
                child: ImageViewWidget(
                  width: ScreenUtil().screenWidth,
                  boxFit: BoxFit.fill,
                  url: image ?? '',
                ),
              ),
      ],
    );
  }
}

class WeeklyStarSlider extends StatelessWidget {
  const WeeklyStarSlider({
    super.key,
    required this.image,
    required this.avatar,
  });

  final String? image;
  final String? avatar;

  @override
  Widget build(BuildContext context) {
    return Stack(
      alignment: Alignment.center,
      children: [
        Positioned(
          bottom: 22.0.h,
          right: 36.0.h,
          child: Stack(
            alignment: Alignment.center,
            children: [
              if (avatar != null)
                ImageViewWidget(
                  url: avatar ?? "",
                  boxFit: BoxFit.fill,
                  width: 55.h,
                  shape: BoxShape.circle,
                  height: 55.h,
                )
            ],
          ),
        ),
        image == null
            ? const SizedBox()
            : ClipRRect(
                borderRadius: 10.radius,
                child: ImageViewWidget(
                  width: ScreenUtil().screenWidth,
                  boxFit: BoxFit.fill,
                  url: image ?? '',
                ),
              ),
      ],
    );
  }
}

class CpSlider extends StatelessWidget {
  const CpSlider({
    super.key,
    required this.image,
    required this.avatar2,
    required this.avatar,
    required this.name,
    required this.name2,
  });

  final String? image;
  final String? avatar, avatar2, name, name2;

  @override
  Widget build(BuildContext context) {
    return Stack(
      alignment: Alignment.center,
      children: [
        Positioned(
          bottom: 28.h,
          right: 60.w,
          child: ((avatar ?? '') != '')
              ? ImageViewWidget(
                  url: avatar ?? "",
                  boxFit: BoxFit.fill,
                  width: 60.w,
                  height: 60.h,
                  shape: BoxShape.circle,
                )
              : const SizedBox(),
        ),
        Positioned(
          bottom: 28.h,
          right: 125.w,
          child: ((avatar2 ?? '') != '')
              ? ImageViewWidget(
                  url: avatar2 ?? "",
                  boxFit: BoxFit.fill,
                  width: 60.w,
                  height: 60.h,
                  shape: BoxShape.circle,
                )
              : const SizedBox(),
        ),
        image == null
            ? const SizedBox()
            : ClipRRect(
                borderRadius: 10.radius,
                child: ImageViewWidget(
                  width: ScreenUtil().screenWidth,
                  boxFit: BoxFit.fill,
                  url: image ?? '',
                ),
              ),
        Positioned(
          bottom: 5.h,
          right: 55.w,
          child: ((name ?? '') != '')
              ? Text(
                  shortName(name ?? ''),
                  style: context.bodySmall,
                )
              : const SizedBox(),
        ),
        Positioned(
          bottom: 5.h,
          right: 130.w,
          child: ((name2 ?? '') != '')
              ? Text(
                  shortName(name2 ?? ''),
                  style: context.bodySmall,
                )
              : const SizedBox(),
        ),
      ],
    );
  }

  String shortName(String? name) {
    if (name == null || name.isEmpty) return '';
    return name.length <= 6 ? name : name.substring(0, 6);
  }
}
