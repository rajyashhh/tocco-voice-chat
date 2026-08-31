import 'package:general/src/core/index.dart';

class DatePickerWidget extends StatelessWidget {
  const DatePickerWidget({
    super.key,
    required this.onDateTimeChanged,
    this.dateTime,
    required this.controller,
    this.title,
  });

  final TextEditingController controller;
  final String? title;
  final DateTime? dateTime;
  final ValueChanged<DateTime> onDateTimeChanged;

  @override
  Widget build(BuildContext context) {
    return TextInputWidget(
       controller.text==""? StringManager.birthday.tr():controller.text,
      label: TextWidget(
        StringManager.birthday.tr(),
        style: context.bodyLarge.colorExt(
          ColorManager.secondaryText,
        ),
      ),
      controller: controller,
      title: title,
      textColor: ColorManager.textPrimary,
      textStyle: context.bodyMedium.colorExt(ColorManager.secondaryText),
      hintStyle: context.bodyMedium.colorExt(ColorManager.secondaryText),
      suffixColor: ColorManager.primary,
      prefixColor: ColorManager.primary,
      readOnly: true,
      enabledBorder: UnderlineInputBorder(
        // Theme-aware hairline (fixed gray was invisible on the dark default).
        borderSide: BorderSide(color: ColorManager.cardBorderColor),
      ),
      focusedBorder: const UnderlineInputBorder(
        borderSide: BorderSide(color: Colors.green),
      ),
      errorBorder: const UnderlineInputBorder(
        borderSide: BorderSide(color: Colors.red),
      ),
      onTap: () {

        Methods.showCupertinoDatePicker(
          context: context,
          initialDateTime: dateTime ?? DateTime.now(),
          onDateTimeChanged: onDateTimeChanged,

        );
      },

      validator: (value) {
        if (value == null || value.isEmpty) {
          return StringManager.requiredField.tr();
        }
        return null;
      },
    );
  }
}
