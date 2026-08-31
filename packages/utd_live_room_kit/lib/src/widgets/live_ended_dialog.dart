import 'package:flutter/material.dart';

/// Shows the package's built-in "the live has ended" dialog.
///
/// Shown to every NON-host participant when the host leaves the room (the live
/// is over). Self-contained (hardcoded EN + AR text) so the package has no
/// dependency on the host app's localization — mirrors [showUTDBannedDialog].
/// Resolves when the user dismisses it.
Future<void> showUTDLiveEndedDialog(BuildContext context) {
  return showDialog<void>(
    context: context,
    barrierDismissible: false,
    builder: (dialogCtx) {
      return AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Row(
          children: [
            Icon(Icons.podcasts, color: Colors.redAccent, size: 24),
            SizedBox(width: 8),
            Expanded(
              child: Text(
                'The live has ended',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
        content: const Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'انتهى البث المباشر',
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 12),
            Text(
              'The host has ended the live.',
              style: TextStyle(fontSize: 14),
            ),
            SizedBox(height: 4),
            Text(
              'قام المضيف بإنهاء البث المباشر.',
              style: TextStyle(fontSize: 13, color: Colors.grey),
            ),
          ],
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.of(dialogCtx).pop(),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.redAccent,
              foregroundColor: Colors.white,
            ),
            child: const Text('OK'),
          ),
        ],
      );
    },
  );
}
