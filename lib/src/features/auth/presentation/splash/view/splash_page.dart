import 'dart:async';
import 'dart:io';
import 'package:general/src/core/index.dart';
import 'package:general/src/features/auth/auth.dart';
import 'package:general/src/features/auth/presentation/splash/config_app/config_app_bloc.dart';
import 'package:ipapi/ipapi.dart';
import 'package:ipapi/models/geo_data.dart';
import 'package:loading_animation_widget/loading_animation_widget.dart';

class SplashPage extends StatefulWidget {
  const SplashPage({super.key});

  @override
  State<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends State<SplashPage> {
  bool _hasNavigated = false;
  StreamSubscription<ConfigAppState>? _configSubscription;

  @override
  void initState() {
    super.initState();
    if (Platform.isAndroid) {
      ConstantsManager.devicePlatform = StringManager.android;
    } else if (Platform.isIOS) {
      ConstantsManager.devicePlatform = StringManager.ios;
    }

    di<ConfigAppBloc>().add(
      ConfigAppEvent(
        devicePLATFORM: ConstantsManager.devicePlatform,
        versionApp: ConstantsManager.appVersionCode.toString(),
      ),
    );

    // Listen for config response and then navigate
    _listenToConfigAndNavigate();

    _initLocation();
  }

  void _listenToConfigAndNavigate() {
    final configBloc = di<ConfigAppBloc>();

    // Check if already loaded/error
    if (configBloc.state.requestState.isLoaded ||
        configBloc.state.requestState.isEmpty ||
        configBloc.state.requestState.isError) {
      if (!_hasNavigated) {
        _hasNavigated = true;
        context.read<SplashBloc>().add(const SplashNavigationEvent());
      }
      return;
    }

    // Listen for state changes
    _configSubscription = configBloc.stream.listen((state) {
      if (state.requestState.isLoaded ||
          state.requestState.isEmpty ||
          state.requestState.isError) {
        if (!_hasNavigated) {
          _hasNavigated = true;
          context.read<SplashBloc>().add(const SplashNavigationEvent());
        }
        _configSubscription?.cancel();
      }
    });
  }

  Future<void> _initLocation() async {
    try {
      GeoData? geoData;
      geoData = await IpApi.getData();

      ConstantsManager.updateLocation(
        latitude: geoData?.lat ?? 0.0,
        longitude: geoData?.lon ?? 0.0,
        isoCode: geoData?.countryCode ?? "unknown",
      );
    } catch (e) {
      debugPrint('Error fetching location data: $e');
      ConstantsManager.updateLocation(
        latitude: 0.0,
        longitude: 0.0,
        isoCode: "unknown",
      );
    }
  }

  @override
  void dispose() {
    _configSubscription?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<SplashBloc, SplashState>(
      buildWhen: (prev, curr) => prev != curr,
      builder: (context, state) {
        return Scaffold(
          // Per-variant page fill (dark navy on the default identity) — a
          // hardcoded white splash flashed a white page before every launch
          // of the all-dark default theme.
          backgroundColor: ConstantsManager.isVariantBuildB
              ? const Color(0xFFf13492)
              : ColorManager.scaffoldBg,
          body: SizedBox(
            width: MediaQuery.sizeOf(context).width,
            height: MediaQuery.sizeOf(context).height,
            child: ConstantsManager.isVariantBuildB
                ? Column(
                    children: [
                      Expanded(
                        child: Container(
                          width: ScreenUtil().screenWidth,
                          color: const Color(0xFFf13492),
                        ),
                      ),
                      Expanded(
                        flex: 2,
                        child: Image.asset(
                          AssetsManager.firstPart,
                          width: ScreenUtil().screenWidth,
                          fit: BoxFit.contain,
                        ),
                      ),
                      Expanded(
                        child: Container(
                          width: ScreenUtil().screenWidth,
                          color: const Color(0xFFf13492),
                        ),
                      ),
                      Expanded(
                        flex: 2,
                        child: Image.asset(
                          AssetsManager.secondPart,
                          width: ScreenUtil().screenWidth,
                          fit: BoxFit.contain,
                        ),
                      ),
                    ],
                  )
                : Column(
                    mainAxisAlignment: MainAxisAlignment.start,
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      100.hBox,
                      Image.asset(
                        AssetsManager.logo,
                        height: 150.h,
                        width: 150.w,
                      ),
                      const Spacer(),
                      SizedBox(
                        height: 100.h,
                        child: LoadingAnimationWidget.staggeredDotsWave(
                          color: ColorManager.primary,
                          size: 50.h,
                        ),
                      ),
                      Text(
                        "Loading some resources...",
                        style:
                            context.bodySmall.w400.colorExt(ColorManager.secondaryText),
                      ),
                      30.hBox,
                    ],
                  ),
          ),
        );
      },
    );
  }
}
