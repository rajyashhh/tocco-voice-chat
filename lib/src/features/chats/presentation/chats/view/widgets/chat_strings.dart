/// Local labels for the redesigned chats screen that don't yet have entries in
/// the shared [StringManager]. These are kept slice-local on purpose; once the
/// i18n keys are added to core they should be migrated to `StringManager.x.tr()`
/// (see central_changes_needed in the slice report).
class ChatStrings {
  ChatStrings._();

  static const String unreadTab = 'غير مقروءة';
  static const String newChat = 'محادثة جديدة';
  static const String newGroup = 'مجموعة جديدة';
  static const String chooseFriend = 'اختر صديقاً';

  // Contact discovery + invite (new-chat picker).
  static const String findFriendsFromContacts =
      'ابحث عن أصدقائك من جهات الاتصال';
  static const String contactsPermissionDenied =
      'إذن جهات الاتصال مرفوض — اضغط للمحاولة';
  static const String contactsOnApp = 'جهات اتصالك على التطبيق';
  static const String noContactsOnApp = 'لا أحد من جهات اتصالك على التطبيق بعد';
  static const String inviteFriendsToApp = 'ادعُ أصدقاءك إلى التطبيق';
  static const String invite = 'دعوة';

  // In-app friends picker (new chat).
  static const String loadingContacts = 'جارٍ تحميل جهات اتصالك...';
  static const String friendsOnApp = 'أصدقاؤك على التطبيق';
  static const String noFriendsYet = 'ليس لديك أصدقاء بعد';
  static const String friendsFetchError = 'تعذّر تحميل قائمة الأصدقاء';
  static const String inviteFromContacts = 'دعوة من جهات الاتصال';

  // Inline-search fallback (no local conversation matched).
  static const String startNewChat = 'بدء محادثة جديدة';
  static const String noSearchResults = 'لا توجد نتائج';
  static const String searchInContacts = 'ابحث في جهات الاتصال';

  // Phone-book picker screen (the dedicated contacts screen).
  static const String contacts = 'جهات الاتصال';
  static const String tryAgain = 'حاول مرة أخرى';
  static const String contactsFetchError = 'تعذّر تحميل جهات الاتصال';
  static const String noPhoneContacts = 'لا توجد جهات اتصال على هذا الجهاز';
  static const String noContactsMatch = 'لا توجد نتائج تطابق البحث';
  static const String messageAction = 'دردشة';

  // Group profile screen (members header).
  static const String youBadge = 'أنت';
  static const String myPhoneLabel = 'رقم موبايلي';
}
