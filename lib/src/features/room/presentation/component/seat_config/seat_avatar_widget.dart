import 'package:avatar_glow/avatar_glow.dart';
import 'package:general/src/core/index.dart';
import 'package:general/src/core/widgets/cache/cache_alpha_widget.dart';
import 'package:general/src/core/widgets/cache/cache_vap_widget.dart';
import 'package:general/src/core/widgets/cache/cache_video_widget.dart';
import 'package:general/src/features/room/presentation/component/seat_config/charisma_badge_widget.dart';
import 'package:general/src/features/room/presentation/component/seat_config/seat_metrics.dart';
import 'package:general/src/features/room/room.dart';

class SeatAvatarWidget extends StatelessWidget {
  final String userId;
  final Map<String, String> attributes;

  /// The FULL seat slot size, in device-real px, supplied by the audio-room kit
  /// — the same value the empty/locked builders receive. The avatar diameter and
  /// every sub-element are derived from it via [SeatMetrics], so the occupied,
  /// empty and locked seats share one size source and stay identical in every
  /// mode and on every device.
  final double size;

  const SeatAvatarWidget({
    super.key,
    required this.userId,
    this.attributes = const {},
    required this.size,
  });

  @override
  Widget build(BuildContext context) {
    final int id = int.tryParse(userId) ?? 0;

    // All data comes from backend metadata (attributes) — no local cache.
    final String resolvedImage = attributes['avatar'] ?? "";
    final String? frame = attributes['fr'];
    final String? frameType = attributes['frt'];
    final String displayName = attributes['name'] ?? "";
    final String colorName = attributes['cn'] ?? '';

    final m = SeatMetrics.forSeat(size);
    final double avatarSize = m.avatar;

    // Build avatar image widget using the kit-provided size.
    // The initials circle is ALWAYS rendered as the base layer, with the photo
    // painted over it once loaded. An occupied seat whose occupant arrived with
    // empty metadata (reconnect race) or whose avatar URL hangs used to render
    // nothing at all — an invisible seat (owner report 2026-06-11, seat 7).
    final Widget avatarImage = Stack(
      alignment: Alignment.center,
      children: [
        InitialsAvatar(name: displayName, size: avatarSize),
        if (resolvedImage.isNotEmpty)
          ImageViewWidget(
            url: resolvedImage,
            boxFit: BoxFit.cover,
            width: avatarSize,
            height: avatarSize,
            shape: BoxShape.circle,
            // Transparent while loading so the initials base shows through
            // instead of an invisible/shimmer slot on the dark room background.
            isStopLoadingAndError: false,
            // Avatar context: a failing URL falls back to initials, not the logo.
            displayName: displayName,
          ),
      ],
    );

    return Stack(
      clipBehavior: Clip.none,
      alignment: Alignment.center,
      children: [
        // ================== Avatar Image with Glow ==================
        Center(
          child: _SpeakingGlow(
            userId: userId,
            avatarSize: avatarSize,
            child: avatarImage,
          ),
        ),

        // ================== Frame ==================
        if (frame != null && frame != "")
          _buildFrameWidget(
            frame: frame,
            frameType: frameType,
            id: id,
            width: m.frame,
            height: m.frame,
          ),

        // ================== Name ==================
        if (displayName.isNotEmpty)
          Positioned(
            bottom: m.namePosBottom,
            left: 0,
            right: 0,
            child: Center(
              child: SizedBox(
                width: m.nameMaxWidth,
                child: TextScroll(
                  displayName,
                  velocity: const Velocity(pixelsPerSecond: Offset(50, 0)),
                  pauseBetween: const Duration(milliseconds: 1000),
                  textAlign: TextAlign.center,
                  style: context.bodyMedium
                      .copyWith(fontSize: m.nameFont)
                      .w600
                      .colorExt(
                        colorName.isNotEmpty
                            ? Color(
                              int.parse(colorName.replaceAll('#', '0xff')),
                            )
                            : ColorManager.onDark,
                      ),
                ),
              ),
            ),
          ),

        // ================== Charisma Badge ==================
        CharismaBadge(userId: userId, imageSize: avatarSize),

        // ================== Mic State (below avatar, right side) ==================
        if (userId.isNotEmpty) _MicState(imageSize: avatarSize, userId: userId),

        // ================== Emoji Overlay ==================
        _EmojiOverlay(userId: userId, imageSize: avatarSize),
      ],
    );
  }

  Widget _buildFrameWidget({
    required String frame,
    required String? frameType,
    required int id,
    required double width,
    required double height,
  }) {
    if (frameType == "svga") {
      return CacheSvgaWidget(
        key: ValueKey("frame_$id"),
        url: frame,
        width: width,
        height: height,
        boxFit: BoxFit.fill,
      );
    } else if (frameType == "alpha") {
      return CacheAlphaWidget(
        url: frame,
        width: width,
        height: height,
        isLoop: true,
      );
    } else {
      return ImageViewWidget(
        key: ValueKey("frame_$id"),
        url: frame,
        isFromRoom: true,
        width: width,
        height: height,
        shape: BoxShape.circle,
        boxFit: BoxFit.fill,
      );
    }
  }
}

// ================== Speaking Glow ==================
class _SpeakingGlow extends StatelessWidget {
  final String userId;
  final double avatarSize;
  final Widget child;

  const _SpeakingGlow({
    required this.userId,
    required this.avatarSize,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    final controller = RoomData.instance.utdController;
    if (controller == null || userId.isEmpty) {
      return child;
    }

    return ValueListenableBuilder<Set<String>>(
      valueListenable: controller.activeSpeakers,
      builder: (context, speakers, _) {
        final isMuted = controller.mutedParticipants.value.contains(userId);
        final isSpeaking = speakers.contains(userId) && !isMuted;

        return AvatarGlow(
          animate: isSpeaking,
          glowColor: Colors.greenAccent,
          glowRadiusFactor: 0.3,
          duration: const Duration(milliseconds: 1000),
          child: child,
        );
      },
    );
  }
}

// ================== Mic STATE ==================
class _MicState extends StatelessWidget {
  final double imageSize;
  final String userId;

  const _MicState({required this.imageSize, required this.userId});

  @override
  Widget build(BuildContext context) {
    final controller = RoomData.instance.utdController;
    if (controller == null) return const SizedBox();

    final m = SeatMetrics.forAvatar(imageSize);
    return ValueListenableBuilder<Set<String>>(
      valueListenable: controller.mutedParticipants,
      builder: (context, muted, _) {
        final isMuted = muted.contains(userId);
        if (!isMuted) return const SizedBox();

        return Positioned(
          bottom: 0,
          right: 0,
          child: Container(
            width: m.micCircle,
            height: m.micCircle,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: ColorManager.onDark,
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.2),
                  blurRadius: 2,
                  offset: const Offset(0, 1),
                ),
              ],
            ),
            child: Image.asset(
              AssetsManager.muteUser,
              color: ColorManager.redIcons,
              width: m.micInner,
              height: m.micInner,
              fit: BoxFit.contain,
            ),
          ),
        );
      },
    );
  }
}

// ================== Emoji Overlay ==================
class _EmojiOverlay extends StatelessWidget {
  final String userId;
  final double imageSize;

  const _EmojiOverlay({required this.userId, required this.imageSize});

  @override
  Widget build(BuildContext context) {
    return Positioned.fill(
      child: ValueListenableBuilder<int>(
        valueListenable: EmojieController.updateEmojie,
        builder: (context, _, __) {
          final emoji = EmojieController.emojies.value[userId]?.emojie;
          final type = EmojieController.emojies.value[userId]?.type;
          if (emoji == null) return const SizedBox();

          return type == "svga"
              ? CacheSvgaWidget(url: emoji, height: imageSize, width: imageSize)
              : type == "vap"
              ? CachedVapWidget(url: emoji, height: imageSize, width: imageSize)
              : type == "alpha"
              ? CacheAlphaWidget(
                url: emoji,
                height: imageSize,
                width: imageSize,
              )
              : type == "mp4"
              ? CacheVideoWidget(
                videoUrl: emoji,
                height: imageSize,
                width: imageSize,
              )
              : ImageViewWidget(
                isFromRoom: true,
                url: emoji,
                height: imageSize,
                width: imageSize,
                radius: 50,
                boxFit: BoxFit.scaleDown,
              );
        },
      ),
    );
  }
}
