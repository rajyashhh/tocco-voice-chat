import 'package:general/src/core/index.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_my_level/get_my_level_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/bloc/bloc_my_level/get_my_level_event.dart';
import 'package:general/src/features/room/data/model/gift_category_model.dart';
import 'package:general/src/features/room/presentation/gifts/gift.dart';
import 'package:general/src/features/room/presentation/gifts/view/gift_room_base_design.dart';
import 'package:general/src/features/room/presentation/gifts/view/gift_room_new_theme_design.dart';
import 'package:general/src/features/room/presentation/gifts/view/widgets/search_textfield.dart';
import 'package:general/src/features/room/room.dart';

class GiftScreen extends StatefulWidget {
  final MyDataModel myDataModel;
  final EnterRoomModel roomData;
  final List<UTDParticipant>? users;
  final bool isSingleUser;
  final bool isAudioRoom;
  final String? userId;
  final String? userImage;
  final String? userName;
  final String? momentId;

  const GiftScreen({
    required this.myDataModel,
    required this.users,
    required this.roomData,
    required this.isSingleUser,
    required this.isAudioRoom,
    required this.userId,
    required this.userImage,
    required this.userName,
    this.momentId,
    super.key,
  });

  static GiftsEntity? chosenGift;

  @override
  State<GiftScreen> createState() => _GiftScreenState();
}

class _GiftScreenState extends State<GiftScreen> with TickerProviderStateMixin {
  late TabController? giftController;

  @override
  void initState() {
    super.initState();
    if (!di<FetchGiftBloc>().state.reqStateGiftCategory.isLoaded) {
      di<FetchGiftBloc>().add(const FetchGiftCategoryEvent());
    }
    if (ConstantsManager.isTheme1 ||
        ConstantsManager.isTheme2 ||
        ConstantsManager.isTheme3) {
      di<GetMyLevelBloc>().add(const GetMyLevelData());
    }
    GiftBottomBar.numberOfGift.value = 1;

    if (widget.isAudioRoom == false) {
      GiftUser.userSelected.value.clear();
      GiftUserOnly.userSelected = widget.userId ?? "";
      GiftUser.userSelected.value.putIfAbsent(
        0,
        () => SelectedObject(
          userId: widget.userId ?? "",
          selected: true,
          name: widget.userName ?? "",
        ),
      );
    }

    GiftBottomBar.giftType = TypeGift.normal;
    GiftScreen.chosenGift = null;

    _initializeTabController();
  }

  /// Selects the first gift in the given list
  void _selectFirstGift(List<GiftsEntity> gifts, {int? categoryId}) {
    if (gifts.isNotEmpty) {
      final firstGift = gifts.first;
      GiftScreen.chosenGift = firstGift;
      di<GiftBloc>().add(
        ChangeGiftDataEvent(
          numOfGift: 0,
          giftId: firstGift.id,
          giftPrice: firstGift.price,
          categoryId: categoryId,
        ),
      );
    }
  }

  void _initializeTabController() {
    final state = di<FetchGiftBloc>().state;

    // Filter categories based on isRoom
    final filteredCategories = state.giftCategory.where((item) {
      if (item.type == 'lucky_gift') {
        return widget.isAudioRoom;
      }
      return true;
    }).toList();

    // +1 for bag tab only if not using new theme (new theme doesn't show bag
    // tab in TabBar), +1 for the leading Most Used tab.
    final tabLength = (ConstantsManager.isTheme1 ||
                ConstantsManager.isTheme2 ||
                ConstantsManager.isTheme3
            ? filteredCategories.length
            : filteredCategories.length + 1) +
        1;

    giftController = TabController(length: tabLength, vsync: this);

    giftController?.addListener(
      () {
        final controllerIndex = giftController?.index ?? 0;
        final state = di<FetchGiftBloc>().state;

        if (controllerIndex == 0) {
          // Most Used tab: plain sends; MostUsedGiftView owns auto-selection.
          GiftBottomBar.giftType = TypeGift.normal;
          GiftScreen.chosenGift = null;
          di<GiftBloc>().add(
            const ChangeGiftDataEvent(
                numOfGift: -1, giftId: -1, giftPrice: -1, categoryId: -1),
          );
          return;
        }
        final index = controllerIndex - 1;

        // Check if it's the last tab (Bag tab) - only for old theme
        if (!ConstantsManager.isTheme1 &&
            !ConstantsManager.isTheme2 &&
            !ConstantsManager.isTheme3 &&
            index == filteredCategories.length) {
          // Bag tab
          GiftBottomBar.giftType = TypeGift.bag;
          if (state.bagReqState != RequestState.loaded) {
            di<FetchGiftBloc>().add(const FetchBagGiftEvent());
          } else {
            // Auto-select first bag gift if already loaded
            _selectFirstGift(state.bagGifts,
                categoryId: -2); // Use -2 for bag category
          }
        } else if (index < filteredCategories.length) {
          // Regular category tab
          final category = filteredCategories[index];
          final typeGift = TypeGift.fromString(category.type);
          GiftBottomBar.giftType = typeGift;
          di<FetchGiftBloc>().add(SetCategoryIdEvent(category.id));

          // Fetch gifts if not loaded
          if (state.getReqStateById(category.id) != RequestState.loaded) {
            di<FetchGiftBloc>().add(
              FetchGiftsByCategoryEvent(
                categoryId: category.id,
                typeId: category.id,
              ),
            );
          } else {
            // Auto-select first gift if already loaded
            _selectFirstGift(state.getGiftsById(category.id),
                categoryId: category.id);
          }
        }
      },
    );

    // Prefetch the first category so switching off the Most Used tab is
    // instant. No gift auto-selection here: the sheet opens on Most Used,
    // which owns its own selection (a category pick would be invisible).
    if (filteredCategories.isNotEmpty) {
      final firstCategory = filteredCategories.first;
      if (state.getReqStateById(firstCategory.id) != RequestState.loaded) {
        di<FetchGiftBloc>().add(SetCategoryIdEvent(firstCategory.id));

        di<FetchGiftBloc>().add(
          FetchGiftsByCategoryEvent(
            categoryId: firstCategory.id,
            typeId: firstCategory.id,
          ),
        );
      }
    }
  }

  @override
  void dispose() {
    if (GiftBottomBar.typeCandy.value == TypeCandy.luckyCandy) {
      LuckyGiftService.instance.resetGuard();
      LuckyGiftService.instance.endAllLuckyGift();
    }
    GiftBottomBar.typeCandy.value = TypeCandy.non;

    GiftScreen.chosenGift = null;
    final filteredCategories =
        di<FetchGiftBloc>().state.giftCategory.where((item) {
      if (item.type == 'lucky_gift') {
        return widget.isAudioRoom;
      }
      return true;
    }).toList();
    if (filteredCategories.isNotEmpty) {
      final firstCategory = filteredCategories.first;
      di<FetchGiftBloc>().add(SetCategoryIdEvent(firstCategory.id));
    }

    GiftTabBarWithSearchState.showBagGiftNotifier.value = false;
    GiftTabBarWithSearchState.showSearchNotifier.value = false;
    di<GiftBloc>().add(
      const ChangeGiftDataEvent(
        numOfGift: -1,
        giftId: -1,
        giftPrice: -1,
        categoryId: -1,
      ),
    );
    giftController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<FetchGiftBloc, FetchGiftsStates>(
      bloc: di<FetchGiftBloc>(),
      buildWhen: (previous, current) {
        return previous.reqStateGiftCategory != current.reqStateGiftCategory ||
            previous.categoryReqStates != current.categoryReqStates ||
            previous.bagReqState != current.bagReqState ||
            previous.searchReqState != current.searchReqState;
      },
      listener: (context, state) {
        // Re-initialize tab controller when categories are loaded.
        // The controller now always carries the +1 Most Used tab, so compare
        // against the expected length instead of checking for 0.
        final expectedFiltered = state.giftCategory.where((item) {
          if (item.type == 'lucky_gift') {
            return widget.isAudioRoom;
          }
          return true;
        }).length;
        final expectedLength = (ConstantsManager.isTheme1 ||
                    ConstantsManager.isTheme2 ||
                    ConstantsManager.isTheme3
                ? expectedFiltered
                : expectedFiltered + 1) +
            1;
        if (state.reqStateGiftCategory == RequestState.loaded &&
            (giftController == null ||
                giftController!.length != expectedLength)) {
          setState(() {
            giftController?.dispose();
            _initializeTabController();
          });
        }

        // Auto-select first gift when gifts are loaded for current category.
        // Skipped while the Most Used tab (index 0) is showing — it owns
        // selection there and a category pick would be invisible.
        final currentCategoryId = state.categoryId;
        if ((giftController?.index ?? 0) != 0 &&
            currentCategoryId != null &&
            state.getReqStateById(currentCategoryId) == RequestState.loaded) {
          final gifts = state.getGiftsById(currentCategoryId);
          if (gifts.isNotEmpty && GiftScreen.chosenGift == null) {
            _selectFirstGift(gifts, categoryId: currentCategoryId);
          }
        }

        // Auto-select first bag gift when bag is loaded
        if (state.bagReqState == RequestState.loaded &&
            GiftBottomBar.giftType == TypeGift.bag &&
            GiftScreen.chosenGift == null) {
          _selectFirstGift(state.bagGifts,
              categoryId: -2); // Use -2 for bag category
        }
      },
      builder: (context, state) {
        if (state.reqStateGiftCategory == RequestState.loading) {
          return const Center(child: CircularProgressIndicator(color: ColorManager.roomGold));
        }

        if (state.reqStateGiftCategory == RequestState.error) {
          return Center(
              child: Text(state.msgGiftCategory,
                  style: context.bodyMedium.colorExt(ColorManager.roomTextPrimary)));
        }

        final filteredCategories = state.giftCategory.where((item) {
          if (item.type == 'lucky_gift') {
            return widget.isAudioRoom;
          }
          return true;
        }).toList();
        final category = di<FetchGiftBloc>().state.giftCategory.firstWhere(
              (c) => c.id == di<FetchGiftBloc>().state.categoryId,
              orElse: () =>
                  const GiftCategoryModel(id: -1, title: '', type: ''),
            );

        return ValueListenableBuilder<TypeCandy>(
          valueListenable: GiftBottomBar.typeCandy,
          builder: (BuildContext context, TypeCandy type, _) {
            if (type == TypeCandy.luckyCandy) {
              return SizedBox(
                height: MediaQuery.of(context).size.height / 5,
                child: GiftBottomBar(
                  roomData: widget.roomData,
                  isRoom: widget.isAudioRoom,
                  showData: false,
                  momentId: widget.momentId,
                  sizeFactor: 1.0,
                  containerBackgroundColor: ColorManager.transparent,
                  containerTopPadding: 0,
                  coinImageScale: 4,
                  rechargeTextColor: ColorManager.orange,
                  selectionColor: ColorManager.roomGold,
                  showRechargeArrow: true,
                  rechargeArrowColor: ColorManager.orange,
                  sendButtonBorderWidth: 1,
                  sendButtonBorderColor: ColorManager.roomGold,
                  numberDropdownIconColor: const Color(0xFF2196F3),
                  sendButtonGradientColors: const [],
                  sendButtonBorderRadius: 30,
                  dialogBackgroundColor: ColorManager.greyTabBar,
                  dialogBorderColor:
                      ColorManager.roomGold.withValues(alpha: 0.2),
                  numberFormatPrefix: 'item',
                ),
              );
            }
            final keyboardHeight = MediaQuery.of(context).viewInsets.bottom;

            return AnimatedPadding(
              duration: const Duration(milliseconds: 250),
              padding: EdgeInsets.only(bottom: keyboardHeight),
              child: SizedBox(
                height: MediaQuery.of(context).size.height * 0.6,
                width: double.infinity,
                child: ConstantsManager.isTheme1 ||
                        ConstantsManager.isTheme2 ||
                        ConstantsManager.isTheme3
                    ? GiftRoomNewThemeDesign(
                        state: state,
                        filteredCategories: filteredCategories,
                        category: category,
                        giftController: giftController,
                        myDataModel: widget.myDataModel,
                        roomData: widget.roomData,
                        isAudioRoom: widget.isAudioRoom,
                        isSingleUser: widget.isSingleUser,
                        users: widget.users,
                        userId: widget.userId,
                        userImage: widget.userImage,
                        userName: widget.userName,
                        momentId: widget.momentId,
                      )
                    : GiftRoomBaseDesign(
                        state: state,
                        filteredCategories: filteredCategories,
                        giftController: giftController,
                        myDataModel: widget.myDataModel,
                        roomData: widget.roomData,
                        isAudioRoom: widget.isAudioRoom,
                        isSingleUser: widget.isSingleUser,
                        users: widget.users,
                        userId: widget.userId,
                        userImage: widget.userImage,
                        userName: widget.userName,
                        momentId: widget.momentId,
                      ),
              ),
            );
          },
        );
      },
    );
  }
}
