import 'package:general/src/core/index.dart';

/// WhatsApp-style INLINE search bar for the chats screen. An editable field
/// (typing filters the list in place) with a leading magnifier and a trailing
/// clear button while a query is present. Cleaner, slimmer look the owner
/// preferred — the contacts shortcut now lives in a dedicated FAB.
class ChatsSearchBar extends StatelessWidget {
  const ChatsSearchBar({
    super.key,
    required this.controller,
    required this.onChanged,
  });

  final TextEditingController controller;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    final fg = ColorManager.textPrimary;
    // Pill-shaped translucent background so the purple gradient bleeds through
    // — gives the WhatsApp-on-dark look without the heavy white card.
    return Container(
      height: 48.h,
      padding: context.paddingSymmetric(horizontal: 16),
      decoration: BoxDecoration(
        color: fg.withValues(alpha: 0.04),
        borderRadius: 24.radius,
        border: Border.all(
          color: fg.withValues(alpha: 0.10),
          width: 0.5,
        ),
      ),
      child: Row(
        children: [
          Icon(Icons.search, size: 22.sp, color: fg.withValues(alpha: 0.55)),
          12.wBox,
          Expanded(
            child: TextField(
              controller: controller,
              onChanged: onChanged,
              cursorColor: ColorManager.primary,
              style: context.bodyLarge.size(15).colorExt(fg),
              decoration: InputDecoration(
                filled: false,
                isDense: true,
                border: InputBorder.none,
                contentPadding: EdgeInsets.zero,
                hintText: StringManager.search.tr(),
                hintStyle: context.bodyLarge
                    .size(15)
                    .w400
                    .colorExt(fg.withValues(alpha: 0.55)),
              ),
            ),
          ),
          ValueListenableBuilder<TextEditingValue>(
            valueListenable: controller,
            builder: (_, value, __) => value.text.isEmpty
                ? const SizedBox.shrink()
                : InkWell(
                    onTap: () {
                      controller.clear();
                      onChanged('');
                    },
                    child: Icon(Icons.close,
                        size: 20.sp, color: fg.withValues(alpha: 0.7)),
                  ),
          ),
        ],
      ),
    );
  }
}
