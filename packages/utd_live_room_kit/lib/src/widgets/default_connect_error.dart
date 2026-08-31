import 'package:flutter/material.dart';

import '../theme/utd_room_strings.dart';
import '../theme/utd_room_theme.dart';

/// Default view shown when the room fails to connect and the consumer did not
/// supply `onConnectError`. Replaces the otherwise-infinite loader with a clear
/// message and Retry / Exit actions.
class UTDConnectErrorView extends StatelessWidget {
  final UTDRoomTheme theme;
  final UTDRoomStrings strings;
  final VoidCallback onRetry;
  final VoidCallback onExit;

  const UTDConnectErrorView({
    super.key,
    required this.theme,
    required this.strings,
    required this.onRetry,
    required this.onExit,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.wifi_off, color: theme.danger, size: 48),
            const SizedBox(height: 16),
            Text(
              strings.connectionFailed,
              textAlign: TextAlign.center,
              style: TextStyle(
                  color: theme.onSurface,
                  fontSize: 16,
                  fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                OutlinedButton(
                  onPressed: onExit,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: theme.onSurface,
                    side: BorderSide(
                        color: theme.onSurface.withValues(alpha: 0.4)),
                  ),
                  child: Text(strings.exit),
                ),
                const SizedBox(width: 12),
                ElevatedButton(
                  onPressed: onRetry,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: theme.primary,
                    foregroundColor: Colors.white,
                  ),
                  child: Text(strings.retry),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
