/// UTD Audio Room Kit constants.
class UTDConstants {
  UTDConstants._();

  /// Message batch interval in milliseconds (60fps).
  static const int messageBatchIntervalMs = 16;

  /// Default room options.
  static const bool defaultAdaptiveStream = true;
  static const bool defaultDynacast = true;

  /// Connection retry settings.
  static const int maxConnectRetries = 2;
  static const Duration retryDelay = Duration(milliseconds: 500);

  /// Timeouts that bound blocking WebRTC/SDK operations so a flaky network can
  /// never hang the engine indefinitely (the root cause of the
  /// createTransceiverRTCRtpSender / sendSyncState / waitFor / publishData
  /// timeout crashes). A fired timeout surfaces as a normal exception that the
  /// caller's catch/retry handles, instead of an internal SDK deadlock.
  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration mediaOpTimeout = Duration(seconds: 10);
  static const Duration publishDataTimeout = Duration(seconds: 8);

  /// Data channel reliability.
  static const bool reliableDataChannel = true;
}
