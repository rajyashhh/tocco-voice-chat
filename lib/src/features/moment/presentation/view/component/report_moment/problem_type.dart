


import '../../../../../../core/index.dart';

class ProblemType extends StatefulWidget {
  static int seletedMomentProblem = 0;

  //  static const String pornAr = ;
  //   static const String otherAr = ;
  //   static const String bullyingAr = ;
  //   static const String violenceAr = ;
  static List<String> types = [
    StringManager.porn.tr(),
    StringManager.bullying.tr(),
    StringManager.violence.tr(),
    StringManager.other.tr(),
  ];
  static List<String> typesArabic = [
    "اباحية",
    "تنمر",
    "عنف",
    "اخري",
  ];

  const ProblemType({super.key});

  @override
  State<ProblemType> createState() => _ProblemTypeState();
}

class _ProblemTypeState extends State<ProblemType> {
  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 35.h,
      child: ListView.builder(
          shrinkWrap: true,
          scrollDirection: Axis.horizontal,
          itemCount: ProblemType.types.length,
          itemBuilder: (context, index) {
            return GestureDetector(
              onTap: () {
                setState(() {
                  ProblemType.seletedMomentProblem = index;
                });
              },
              child: Container(
                margin:context.paddingSymmetric(horizontal: 10),
                padding: context.paddingSymmetric(horizontal: 15,vertical: 0),
                decoration: BoxDecoration(
                  borderRadius:4.radius,
                  border: Border.all(
                    color: ProblemType.seletedMomentProblem!=index?ColorManager.black:ColorManager.transparent
                  ),
                  color: ProblemType.seletedMomentProblem == index
                      ? ColorManager.primary
                      : Colors.white,
                ),
                child: Center(
                  child: Text(ProblemType.types[index],style: context.bodyMedium.w400.colorExt(ProblemType.seletedMomentProblem!=index?ColorManager.blackColor:ColorManager.whiteColor),),
                ),
              ),
            );
          }),
    );
  }
}