part of '../level_page.dart';

class LevelView extends StatelessWidget {
  final List<LevelBadgesEntity> data;

  const LevelView({super.key, required this.data});

  @override
  Widget build(BuildContext context) {
    _createRows();
    return SingleChildScrollView(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          LevelCard(
            type: "sender",
            isSender: true,
            color: ColorManager.wealthCard,
            text: StringManager.wealth.tr(),
          ),
          Padding(
            padding: context.paddingAll(10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextWidget(
                  StringManager.wealthDescription.tr(),
                  style: context.bodyMedium
                      .colorExt(ColorManager.onDark)
                      .size(15)
                      .w600,
                ),
                10.hBox,
                TextWidget(
                  " ${StringManager.wealthDescriptionBody.tr()}",
                  style: context.bodyMedium
                      .size(15)
                      .colorExt(ColorManager.greyTextColor),
                )
              ],
            ),
          ),
          20.hBox,
          Container(
            clipBehavior: Clip.hardEdge,
            margin: context.paddingSymmetric(horizontal: 16),
            decoration: BoxDecoration(
              color: const Color(0xFF18183a).withValues(alpha: (0.2)),
              borderRadius: 16.radius,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: context.paddingSymmetric(vertical: 16),
                  decoration: BoxDecoration(
                    color: const Color(0xFF18183a).withValues(alpha: (0.2)),
                    borderRadius: BorderRadius.only(
                      topLeft: 16.radiusCircular,
                      topRight: 16.radiusCircular,
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      Text(
                        StringManager.theLevel.tr(),
                        style: context.bodyMedium.w600
                            .colorExt(ColorManager.onDark),
                      ),
                      Text(
                        StringManager.icLevel.tr(),
                        style: context.bodyMedium.w600
                            .colorExt(ColorManager.onDark),
                      ),
                    ],
                  ),
                ),
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemBuilder: (context, index) {
                    var item = index % 2 == 0;
                    return Container(
                      padding: context.paddingSymmetric(
                          vertical: 12, horizontal: 20),
                      decoration: BoxDecoration(
                        color: item
                            ? const Color(0xff252645)
                            : const Color(0xFF18183a).withValues(alpha: (0.2)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          Text(
                            "${data[index].minlevel}-${data[index].maxlevel}",
                            style: context.bodyMedium.copyWith(
                              color: ColorManager.onDark,
                              fontSize: 16,
                            ),
                          ),
                          ImageViewWidget(
                            url: data[index].badge ?? "",
                            width: 70.w,
                            height: 25.h,
                            boxFit: BoxFit.contain,
                          ),
                        ],
                      ),
                    );
                  },
                  itemCount: data.length,
                )
              ],
            ),
          ),
        ],
      ),
    );
  }

  List<List<LevelBadgesEntity>> _createRows() {
    List<List<LevelBadgesEntity>> rows = [];
    List<LevelBadgesEntity> currentRow = [];
    int rowIndex = 0;

    for (var i = 0; i < data.length; i++) {
      int itemsPerRow = rowIndex % 2 == 0 ? 5 : 4;
      currentRow.add(data[i]);

      if (currentRow.length == itemsPerRow || i == data.length - 1) {
        rows.add(List.from(currentRow));
        currentRow.clear();
        rowIndex++;
      }
    }

    return rows;
  }
}
