import 'package:general/src/core/index.dart';

class GiftRankScreen extends StatelessWidget {
  final GiftRankArguments args;

  const GiftRankScreen({super.key, required this.args});

  @override
  Widget build(BuildContext context) {
    final momentGiftList = args.momentGiftList;
    final topIndexes = args.topIndexes;
    return Scaffold(
      appBar: AppBarWidget(
        title: TextWidget(
          StringManager.giftRank,
          style: context.bodyMedium.colorExt(ColorManager.textPrimary),
        ),
        backgroundColor: ColorManager.scaffoldBg,
      ),
      backgroundColor: ColorManager.scaffoldBg,
      body: Column(
        children: [
          momentGiftList.isNotEmpty
              ? Expanded(
                  child: ListView.builder(
                    itemCount: momentGiftList.length, // Use the passed list
                    itemBuilder: (context, index) {
                      final gift = momentGiftList[index];
                      final isTopUser = index == topIndexes;

                      return Padding(
                        padding: context.paddingSymmetric(
                          horizontal: 16,
                          vertical: 8,
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            // Rank number (you might need to implement ranking logic based on totalNumGift)
                            TextWidget(
                              '${index + 1}',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 18,
                              ),
                            ),
                            const SizedBox(width: 12),
                            // Avatar or placeholder
                            Stack(
                              children: [
                                ImageViewWidget(
                                  url: gift.image,
                                  boxFit: BoxFit.cover,
                                  width: 45.w,
                                  height: 45.h,
                                  shape: BoxShape.circle,
                                ),
                                if (isTopUser)
                                  const Positioned(
                                    top: 0,
                                    left: 0,
                                    child: Icon(
                                      Icons.star,
                                      color: ColorManager.orange,
                                      size: 14,
                                    ),
                                  ),
                              ],
                            ),
                            const SizedBox(width: 12),
                            // Name and subtitle
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  TextWidget(
                                    gift.name, // Use name from MomentGiftModel
                                    style: const TextStyle(
                                        fontWeight: FontWeight.bold),
                                  ),
                                  TextWidget(
                                    "${gift.totalNumGift} ${StringManager.giftSent}",
                                    // Use totalNumGift
                                    style: TextStyle(
                                        fontSize: 13,
                                        color:
                                            ColorManager.grey.withAlpha(200)),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                )
              : Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      // Rank number (you might need to implement ranking logic based on totalNumGift)
                      const TextWidget(
                        '${1}',
                        style: TextStyle(
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(width: 12),
                      // Avatar or placeholder
                      CircleAvatar(
                        radius: 24,
                        backgroundColor: ColorManager.grey.withAlpha(70),
                        child: Image.asset(
                          AssetsManager.seat,
                          width: 30,
                          height: 30,
                        ),
                      ),
                      const SizedBox(width: 12),
                      // Name and subtitle
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const TextWidget(
                              StringManager
                                  .giftList, // Use name from MomentGiftModel
                              style: TextStyle(fontWeight: FontWeight.w500),
                            ),
                            TextWidget(
                              StringManager.giveGift, // Use totalNumGift
                              style: TextStyle(
                                  fontSize: 13,
                                  color: ColorManager.grey.withAlpha(200)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
        ],
      ),
    );
  }
}
