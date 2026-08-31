import 'package:smooth_page_indicator/smooth_page_indicator.dart';
import '../../../core/index.dart';

class OnBoardingScreen extends StatefulWidget {
  const OnBoardingScreen({super.key});

  @override
  State<OnBoardingScreen> createState() => OnBoardingScreenState();
}

class OnBoardingScreenState extends State<OnBoardingScreen> {
  final List<String> titles = [
    StringManager.onBoarding1title.tr(),
    StringManager.onBoarding2title.tr(),
    StringManager.onBoarding3title.tr(),
  ];
  final List<String> subTitles = [
    StringManager.onBoarding1Subtitlt.tr(),
    StringManager.onBoarding2Subtitlt.tr(),
    StringManager.onBoarding3Subtitlt.tr(),
  ];
  final List<String> images = [
    AssetsManager.onboarding1,
    AssetsManager.onboarding2,
    AssetsManager.onboarding3,
  ];
  final List<String> buttonText = [
    StringManager.next.tr(),
    StringManager.next.tr(),
    StringManager.getStarted.tr(),
  ];

  int currentPage = 0;
  final PageController pageController = PageController();
  static bool isFirstTime = true;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorManager.scaffoldBg,
      body: Stack(
        alignment: Alignment.topCenter,
        children: [
          Positioned(
            top: 80,
            right: 0,
            left: 0,
            bottom: 0,
            child: PageView.builder(
              itemCount: 3,
              onPageChanged: (index) {
                setState(() {
                  currentPage = index;
                });
              },
              itemBuilder: (context, index) {
                return _OnBoardingBody(
                  image: images[index],
                  title: titles[index],
                  subTitle: subTitles[index],
                  index: index,
                  pageController: pageController,
                );
              },
              pageSnapping: true,
              scrollDirection: Axis.horizontal,
              controller: pageController,
            ),
          ),
          Positioned(
            bottom: 140.h,
            child: SmoothPageIndicator(
              controller: pageController,
              count: 3,
              effect: ExpandingDotsEffect(
                dotHeight: 5,
                dotWidth: 12,
                activeDotColor: ColorManager.primary,
                dotColor: ColorManager.lightGray,
              ),
            ),
          ),
          Positioned(
            bottom: 40.h,
            right: 30.w,
            left: 30.w,
            child: ButtonWidget(
              width: ScreenUtil().screenWidth * 0.8,
              height: ScreenUtil().screenHeight * 0.070,
              onPressed: () async {
                if (currentPage < titles.length - 1) {
                  pageController.nextPage(
                    duration: const Duration(milliseconds: 500),
                    curve: Curves.easeInOut,
                  );
                } else {
                  Navigator.pushNamedAndRemoveUntil(
                      context, Routes.intro, (_) => false);
                  await Methods.saveOnBoarding();
                }
              },
              title: buttonText[currentPage],
            ),
          ),
        ],
      ),
    );
  }
}

class _OnBoardingBody extends StatelessWidget {
  final int index;
  final String title;
  final String subTitle;
  final String image;
  final PageController pageController;

  const _OnBoardingBody({
    required this.index,
    required this.title,
    required this.subTitle,
    required this.image,
    required this.pageController,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      color: ColorManager.scaffoldBg,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.start,
        children: [
          Image.asset(
            image,
            scale: 4,
          ),
          index == 0 ? 0.hBox : 34.0.hBox,
          Text(
            title,
            style: context.bodyMedium
                .size(16)
                .colorExt(ColorManager.textPrimary)
                .w700,
          ),
          4.hBox,
          SizedBox(
            width: ScreenUtil().screenWidth * 0.8,
            child: Text(
              subTitle,
              style: context.bodyMedium
                  .size(15)
                  .colorExt(ColorManager.textPrimary.withValues(alpha: 0.55)),
              textAlign: TextAlign.center,
            ),
          ),
        ],
      ),
    );
  }
}
