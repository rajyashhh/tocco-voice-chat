import 'package:general/src/features/games/games.dart';
import 'package:general/src/features/room/presentation/component/games/game_loading_screen.dart';
import 'package:wakelock_plus/wakelock_plus.dart';
import 'package:webview_flutter/webview_flutter.dart';

class LeaderCCGameWebViewScreen extends StatefulWidget {
  final String gameUrl;
  final String screenMode; // 'full', 'hd', 'half'

  const LeaderCCGameWebViewScreen({
    super.key,
    required this.gameUrl,
    required this.screenMode,
  });

  @override
  State<LeaderCCGameWebViewScreen> createState() =>
      _LeaderCCGameWebViewScreenState();
}

class _LeaderCCGameWebViewScreenState extends State<LeaderCCGameWebViewScreen> {
  late final WebViewController _controller;
  final ValueNotifier<bool> _isLoading = ValueNotifier(true);

  @override
  void initState() {
    super.initState();
    _initializeWebViewController();
  }

  @override
  void dispose() {
    _isLoading.dispose();
    di<ExploreBloc>().add(const StopGameEvent());

    _controller.clearCache();
    // Keep the screen awake if we're still inside a room after closing the game.
    if (!di<RoomStateManager>().isInRoom) {
      WakelockPlus.disable();
    }
    super.dispose();
  }

  void _initializeWebViewController() {
    try {
      _controller = WebViewController()
        ..setJavaScriptMode(JavaScriptMode.unrestricted)
        ..setBackgroundColor(ColorManager.transparent)
        ..addJavaScriptChannel(
          'pay',
          onMessageReceived: (message) {
            Methods.printLog('💰 pay callback: ${message.message}');
          },
        )
        ..addJavaScriptChannel(
          'closeGame',
          onMessageReceived: (message) {
            Methods.printLog('🕹️ closeGame callback');
            if (mounted) Navigator.pop(context);
          },
        )
        ..addJavaScriptChannel(
          'loadComplete',
          onMessageReceived: (message) {
            Methods.printLog('✅ game load complete');
            _isLoading.value = false;
          },
        )
        ..setNavigationDelegate(
          NavigationDelegate(
            onPageFinished: (url) async {
              await _controller.runJavaScript("""
        console.log = function(){};
        console.error = function(){};
        console.warn = function(){};
      """);
              Methods.printLog('🌍 Page finished: $url');
              _isLoading.value = false;
            },
            onWebResourceError: (error) {
              Methods.printLog('🚨 page error: ${error.description}');
            },
          ),
        )
        ..loadRequest(Uri.parse(widget.gameUrl));
      WakelockPlus.enable();
    } catch (e, s) {
      Methods.printLog('❌ _initializeWebViewController error: $e\n$s');
    }
  }

  /// call updateCoin
  void updateCoin() {
    try {
      _controller.runJavaScript('updateCoin();');
      Methods.printLog('💰 updateCoin called');
    } catch (e, s) {
      Methods.printLog('❌ updateCoin error: $e\n$s');
    }
  }

  double _getHeight(BuildContext context) {
    final size = MediaQuery.of(context).size;

    switch (widget.screenMode) {
      case 'full':
        return size.height;

      case 'hd':
        return size.width * 1044.0 / 750.0;

      case 'half':
      default:
        return size.width;
    }
  }

  @override
  Widget build(BuildContext context) {
    final height = _getHeight(context);
    final isFullScreen = widget.screenMode == 'full';

    return Scaffold(
      backgroundColor: ColorManager.transparent,
      body: Align(
        alignment: AlignmentDirectional.bottomCenter,
        child: SizedBox(
          height: isFullScreen ? null : height,
          child: Stack(
            children: [
              WebViewWidget(controller: _controller),

              /// 🔥 Reactive Loader (no setState)
              ValueListenableBuilder<bool>(
                valueListenable: _isLoading,
                builder: (_, value, __) {
                  return value
                      ? const GameLoadingScreen()
                      : const SizedBox.shrink();
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}
