import 'package:general/src/core/index.dart';

import 'chat_strings.dart';

/// Polished loading state for the new-chat friend picker: a centered title,
/// a circular spinner and an indeterminate linear bar, all in the app palette.
/// Replaces the bare [CircularProgressIndicator] so the picker reads as a
/// designed screen while friends load.
class ContactsLoadingWidget extends StatelessWidget {
  const ContactsLoadingWidget({super.key, this.message});

  /// Overrides the default "loading your contacts" label.
  final String? message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: context.paddingSymmetric(horizontal: 40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              height: 40.h,
              width: 40.h,
              child: CircularProgressIndicator(
                strokeWidth: 2.5,
                valueColor:
                    AlwaysStoppedAnimation<Color>(ColorManager.primary),
              ),
            ),
            18.hBox,
            TextWidget(
              message ?? ChatStrings.loadingContacts,
              isTranslate: false,
              textAlign: TextAlign.center,
              style: context.bodyLarge.colorExt(ColorManager.textPrimary).w500,
            ),
            16.hBox,
            ClipRRect(
              borderRadius: 8.radius,
              child: LinearProgressIndicator(
                minHeight: 4.h,
                backgroundColor: ColorManager.primary.withValues(alpha: 0.15),
                valueColor:
                    AlwaysStoppedAnimation<Color>(ColorManager.primary),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
