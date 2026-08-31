part of 'package:general/src/features/profile/presentation/profile/view/profile_screen.dart';

class ProfileCard extends StatelessWidget {
  final Widget child;

  const ProfileCard({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    // A single elevated surface tier that reads correctly on every theme: the
    // per-theme [surfaceCardColor] (dark navy on the default/dark variant, white
    // on the light ones) with a hairline [cardBorderColor] and rounded corners,
    // insetting with the body padding instead of forcing the full screen width.
    return Container(
      width: double.infinity,
      clipBehavior: Clip.antiAlias,
      padding: context.paddingSymmetric(vertical: 6),
      decoration: ColorManager.cardDecoration(
        borderRadius: 16.radius,
        border: Border.all(color: ColorManager.cardBorderColor),
      ),
      child: child,
    );
  }
}
