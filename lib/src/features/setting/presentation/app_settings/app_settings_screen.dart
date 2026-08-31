import 'package:general/src/core/index.dart';
import 'package:general/src/core/utils/mic_background_helper.dart';

class AppSettingsScreen extends StatefulWidget {
  const AppSettingsScreen({super.key});

  @override
  State<AppSettingsScreen> createState() => _AppSettingsScreenState();
}

class _AppSettingsScreenState extends State<AppSettingsScreen> {
  late bool _muteMicInBackground;

  @override
  void initState() {
    super.initState();
    _muteMicInBackground = MicBackgroundHelper.shouldMuteMicInBackground;
  }

  Future<void> _onMuteMicChanged(bool value) async {
    setState(() {
      _muteMicInBackground = value;
    });
    await MicBackgroundHelper.setMuteMicInBackground(value);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorManager.scaffoldBgAlt,
      appBar: AppBarWidget(
        backgroundColor: ColorManager.scaffoldBgAlt,
        title: StringManager.appSettings.tr(),
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: ScreenUtil().screenWidth,
            color: ColorManager.scaffoldBg,
            padding: context.paddingSymmetric(horizontal: 18, vertical: 15),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      TextWidget(
                        StringManager.muteMicInBackground.tr(),
                        style: context.bodyMedium
                            .size(15)
                            .w500
                            .colorExt(ColorManager.textPrimary),
                      ),
                      6.hBox,
                      TextWidget(
                        StringManager.muteMicInBackgroundDescription.tr(),
                        style: context.bodySmall
                            .size(12)
                            .w400
                            .colorExt(ColorManager.secondaryText),
                        maxLines: 5,
                      ),
                    ],
                  ),
                ),
                16.wBox,
                Switch(
                  value: _muteMicInBackground,
                  onChanged: _onMuteMicChanged,
                  activeThumbColor: ColorManager.primary,
                  activeTrackColor: ColorManager.primary.withValues(alpha: 0.5),
                  inactiveThumbColor: ColorManager.grey,
                  inactiveTrackColor: ColorManager.grey.withValues(alpha: 0.3),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
