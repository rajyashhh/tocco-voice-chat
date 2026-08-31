import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/body_theme_background.dart';
import 'package:general/src/core/widgets/bottom_dialog.dart';
import 'package:general/src/features/groups/presentation/public_groups/view/public_groups_screen.dart';
import '../chat_friend_picker_screen.dart';
import 'chat_strings.dart';

/// WhatsApp-style FAB for the chats screen. Opens a small bottom sheet offering
/// "New chat" (friend picker -> 1:1 messages) and "New group" (create group).
class NewChatFab extends StatelessWidget {
  const NewChatFab({super.key});

  @override
  Widget build(BuildContext context) {
    return FloatingActionButton(
      // Distinct Hero tag: this screen now stacks two FABs (contacts + new-chat)
      // in a Column, and the default type-based tag would collide.
      heroTag: 'fab_new_chat',
      backgroundColor: ColorManager.primary,
      onPressed: () => _showSheet(context),
      child: Icon(Icons.chat, color: ColorManager.buttonTextColor, size: 24.h),
    );
  }

  void _showSheet(BuildContext context) {
    // Float the sheet as an opaque card ABOVE the bottom navigation bar (80.h)
    // + the system safe-area inset, so it never overlaps the 5 main nav icons.
    final double bottomLift =
        80.h + MediaQuery.of(context).viewPadding.bottom + 8.h;
    bottomDailog(
      context: context,
      widget: Container(
        margin: EdgeInsets.only(left: 12.w, right: 12.w, bottom: bottomLift),
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(18.r),
        ),
        child: Stack(
          children: [
            // Sheet follows the app body theme (color/gradient/image) like the
            // home; text uses adaptive tokens so it stays readable.
            const Positioned.fill(child: BodyThemeBackground()),
            Padding(
              padding: context.paddingSymmetric(vertical: 12),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
            Container(
              width: 40.w,
              height: 4.h,
              margin: context.paddingOnly(bottom: 8),
              decoration: BoxDecoration(
                color: ColorManager.grey.withValues(alpha: 0.4),
                borderRadius: 4.radius,
              ),
            ),
            _SheetItem(
              icon: Icons.person_outline,
              label: ChatStrings.newChat,
              onTap: () {
                Navigator.pop(context);
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const ChatFriendPickerScreen(),
                  ),
                );
              },
            ),
            _SheetItem(
              icon: Icons.group_add_outlined,
              label: ChatStrings.newGroup,
              onTap: () {
                Navigator.pop(context);
                Navigator.pushNamed(context, Routes.createGroupScreen);
              },
            ),
            _SheetItem(
              icon: Icons.public,
              label: 'اكتشف المجموعات العامة',
              onTap: () {
                Navigator.pop(context);
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const PublicGroupsScreen(),
                  ),
                );
              },
            ),
            8.hBox,
          ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _SheetItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _SheetItem({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: context.paddingSymmetric(horizontal: 20, vertical: 14),
        child: Row(
          children: [
            Container(
              padding: context.paddingAll(10),
              decoration: BoxDecoration(
                color: ColorManager.primary.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: ColorManager.primary, size: 22.h),
            ),
            16.wBox,
            TextWidget(
              label,
              style: context.bodyLarge
                  .colorExt(ColorManager.textPrimary)
                  .w500,
            ),
          ],
        ),
      ),
    );
  }
}
