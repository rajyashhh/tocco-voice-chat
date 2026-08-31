// import 'package:general/src/core/index.dart';

// class DarkTheme {
//   static final ThemeData theme = ThemeData(
//     fontFamily: "AppFont",
//     brightness: Brightness.dark,

//     colorScheme: const ColorScheme.dark(
//       primary: ColorManager.primary,
//       secondary: ColorManager.secondaryColor,
//       surface: ColorManager.black,
//     ),
//     scaffoldBackgroundColor: ColorManager.scaffoldBackgroundColor,
//     appBarTheme: const AppBarTheme(
//       backgroundColor: ColorManager.scaffoldBackgroundColor,
//       surfaceTintColor: ColorManager.transparent,
//     ),
//     textTheme: TextTheme(
//       titleLarge: TextStyle(
//         fontSize: 20.sp,
//         fontWeight: FontWeight.w600,
//         color: ColorManager.white,
//       ),
//       bodyLarge: TextStyle(
//         fontSize: 16.sp,
//         color: ColorManager.white,
//       ),
//       bodyMedium: TextStyle(
//         fontSize: 14.sp,
//         color: ColorManager.white,
//       ),
//       bodySmall: TextStyle(
//         fontSize: 12.sp,
//         color: ColorManager.white.withValues(alpha:0.50),
//       ),
//     ),

//     inputDecorationTheme: InputDecorationTheme(
//       filled: true,
//       fillColor: ColorManager.transparent,
//       enabledBorder: OutlineInputBorder(
//         borderRadius: 30.radius,
//         borderSide: BorderSide.none
//       ),
//       border:  OutlineInputBorder(
//         borderRadius: 30.radius,
//         borderSide: BorderSide.none
//       ),
//       focusedBorder: OutlineInputBorder(
//         borderRadius: 30.radius,
//         borderSide: const BorderSide(
//           color: ColorManager.secondaryColor,
//           width: 1.0,
//         ),
//       ),
//       errorBorder: OutlineInputBorder(
//         borderRadius: 30.radius,
//         borderSide: const BorderSide(
//           color: ColorManager.redAccount,
//           width: 1.0,
//         ),
//       ),
//     ),
//     dividerTheme: const DividerThemeData(
//       color: ColorManager.grey2,
//       thickness: 1.0,
//     ),

//     checkboxTheme: CheckboxThemeData(
//       fillColor: const WidgetStatePropertyAll(ColorManager.borderColor),
//       checkColor: const WidgetStatePropertyAll(
//         ColorManager.secondaryColor,
//       ),
//       shape: RoundedRectangleBorder(
//         borderRadius: 7.5.radius,
//       ),
//     ),
   

//     pageTransitionsTheme: const PageTransitionsTheme(
//       builders: {
//         TargetPlatform.android: FadeUpwardsPageTransitionsBuilder(),
//         TargetPlatform.iOS: FadeUpwardsPageTransitionsBuilder(),
//       },
//     ),
//   );
// }