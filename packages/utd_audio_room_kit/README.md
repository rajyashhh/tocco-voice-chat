# UTD Audio Room Kit

A Flutter package for building live audio room experiences, powered by [LiveKit](https://livekit.io/). Provides seat management, real-time messaging via data channels, minimize/PiP support, and 8 built-in layout modes.

## Features

- **8 Layout Modes** — seat2, seat8, seat9, seat12, seat16, seat22, couples, cinema
- **Seat Management** — take, leave, lock, unlock, kick, mute, and swap seats
- **Media Controls** — microphone, camera, and speaker control with reactive state
- **Real-time Messaging** — data channel messages with 60fps batching and UUID deduplication
- **Reconnection** — tiered strategy: light sync (<15s), full sync (<60s), force exit (>60s)
- **Minimize / PiP** — minimize the room to a small floating overlay with back-button interception
- **Full Customization** — replace any UI section (header, messages, controls, background, seats) with your own widgets

## Installation

Add the package to your `pubspec.yaml`:

```yaml
dependencies:
  utd_audio_room_kit:
    path: packages/utd_audio_room_kit
```

### Platform Setup

#### iOS

Add microphone permissions to `ios/Runner/Info.plist`:

```xml
<key>NSMicrophoneUsageDescription</key>
<string>Required for audio room participation</string>
```

Add the audio background mode in Xcode under **Signing & Capabilities > Background Modes > Audio, AirPlay, and Picture in Picture**.

#### Android

Add the following permissions to `android/app/src/main/AndroidManifest.xml`:

```xml
<uses-permission android:name="android.permission.RECORD_AUDIO" />
<uses-permission android:name="android.permission.INTERNET" />
```

Set `minSdkVersion` to at least **21** in `android/app/build.gradle`.

## Quick Start

### 1. Basic Audio Room

```dart
import 'package:utd_audio_room_kit/utd_audio_room_kit.dart';

UTDAudioRoom(
  url: 'wss://your-livekit-server.com',
  token: 'generated-jwt-token',
  userId: 'user123',
  userName: 'John Doe',
  roomId: 'room456',
  layoutMode: '3', // seat9 layout
  config: UTDAudioRoomConfig.audience(),
  onControllerReady: (controller) {
    // Store the controller for later use
  },
  onLeave: () {
    Navigator.of(context).pop();
  },
);
```

### 2. Host Configuration

Use `UTDAudioRoomConfig.host()` for hosts, which enables the microphone on join and places the host at seat index 0:

```dart
UTDAudioRoom(
  url: 'wss://your-livekit-server.com',
  token: hostToken,
  userId: 'host123',
  userName: 'Room Host',
  roomId: 'room456',
  config: UTDAudioRoomConfig.host(),
  onControllerReady: (controller) {
    _roomController = controller;
  },
);
```

### 3. Enable Minimize / PiP

Wrap your `MaterialApp` (or the parent navigator) with `UTDMiniPopScope` so the room minimizes instead of closing when the user taps back:

```dart
UTDMiniPopScope(
  controller: _roomController,
  child: MaterialApp(
    home: HomePage(),
  ),
);
```

Add the `UTDMiniOverlayPage` to your widget tree (typically via an `Overlay` or `Stack` at the root):

```dart
Stack(
  children: [
    MyApp(),
    UTDMiniOverlayPage(controller: _roomController),
  ],
);
```

When minimized, a small floating widget appears at the bottom-right corner. Tapping it restores the room.

## Seat Management

Access seat operations through `UTDRoomController.seatController`:

```dart
final seats = controller.seatController;

// Take / leave a seat
await seats.takeSeat(0, 'userId123');
await seats.leaveSeat('userId123');

// Lock / unlock a seat (host/admin)
await seats.lockSeat(2);
await seats.unlockSeat(2);

// Kick a user from their seat
await seats.kickFromSeat(1);

// Mute / unmute a specific seat
seats.muteSeat(3);
seats.unmuteSeat(3);

// Swap two seat occupants
await seats.swapSeats(0, 4);

// Query seat state
final index = seats.getSeatIndexByUserId('userId123');
final isSeated = seats.isUserOnSeat('userId123');
```

Listen to seat changes reactively:

```dart
controller.seatController.seats.addListener(() {
  final currentSeats = controller.seatController.seats.value;
  for (final seat in currentSeats) {
    print('Seat ${seat.index}: ${seat.isEmpty ? "empty" : seat.occupantUserId}');
  }
});
```

## Media Controls

Access media operations through `UTDRoomController.mediaController`:

```dart
final media = controller.mediaController;

// Toggle mic / speaker
await media.toggleMicrophone();
await media.toggleSpeaker();

// Set explicit state
await media.setMicrophoneEnabled(true);
await media.setSpeakerOn(false);

// Listen to state changes
media.isMicEnabled.addListener(() {
  print('Mic enabled: ${media.isMicEnabled.value}');
});
```

## Messaging

### Broadcast a Message

```dart
await controller.sendRoomMessage({
  'messageContent': {
    'message': 'gift_sent',
    'giftId': 'crown',
    'amount': 100,
  }
});
```

### Send a Targeted Message

```dart
await controller.sendTargetedMessage(
  {'messageContent': {'message': 'private_invite'}},
  ['targetUserId1', 'targetUserId2'],
);
```

### Listen with the Message Router

`UTDMessageRouter` provides structured message handling by type:

```dart
final router = UTDMessageRouter(controller.roomManager);

router.registerHandlers({
  'gift_sent': (data, context) {
    // Handle gift
  },
  'seat_request': (data, context) {
    // Handle seat request
  },
});

router.start();

// Clean up when done
router.dispose();
```

### Raw Data Stream

```dart
controller.roomManager.dataStream.listen((data) {
  print('Received raw data: $data');
});
```

## UI Customization

### Replace Individual Sections

```dart
UTDAudioRoom(
  url: url,
  token: token,
  userId: userId,
  userName: userName,
  roomId: roomId,
  config: UTDAudioRoomConfig(
    headerWidget: MyCustomHeader(),
    messagesWidget: MyChatWidget(),
    controlsBarWidget: MyControlsBar(),
    backgroundWidget: MyAnimatedBackground(),
    foregroundWidget: MyOverlayEffects(),
    pkWidget: MyPKBattleWidget(),
  ),
);
```

### Custom Seat Builders

```dart
UTDAudioRoom(
  url: url,
  token: token,
  userId: userId,
  userName: userName,
  roomId: roomId,
  config: UTDAudioRoomConfig(
    seatBuilder: (seat, size) {
      return MyCustomSeatWidget(seat: seat, size: size);
    },
    avatarBuilder: (userId, size) {
      return CircleAvatar(
        radius: size / 2,
        backgroundImage: NetworkImage(getAvatarUrl(userId)),
      );
    },
    emptySeatBuilder: (index, size) {
      return Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          border: Border.all(color: Colors.grey),
        ),
        child: Icon(Icons.add),
      );
    },
  ),
);
```

### Fully Custom Layout

Use `containerBuilder` to take full control of seat arrangement:

```dart
UTDAudioRoom(
  url: url,
  token: token,
  userId: userId,
  userName: userName,
  roomId: roomId,
  containerBuilder: (seats, seatWidgetCreator) {
    return GridView.builder(
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 4,
      ),
      itemCount: seats.length,
      itemBuilder: (context, index) {
        return seatWidgetCreator(seats[index]);
      },
    );
  },
);
```

## Layout Modes

| Mode      | Key   | Seats | Description                  |
|-----------|-------|-------|------------------------------|
| `seat2`   | `'7'` | 2     | One-on-one conversation      |
| `seat8`   | `'2'` | 8     | Standard group               |
| `seat9`   | `'3'` | 9     | 3x3 grid                    |
| `seat12`  | `'4'` | 12    | Medium panel                 |
| `seat16`  | `'5'` | 16    | Large panel                  |
| `seat22`  | `'6'` | 22    | Large audience               |
| `couples` | `'8'` | 2     | Side-by-side pair            |
| `cinema`  | `'9'` | 9     | Cinema-style arrangement     |

Pass the key string as `layoutMode` to `UTDAudioRoom`.

## Architecture Overview

```
UTDRoomController
├── UTDRoomManager        — LiveKit room connection and lifecycle
├── UTDSeatController     — Seat state management (ValueNotifier)
└── UTDMediaController    — Mic, camera, speaker controls (ValueNotifier)

UTDReconnectionHandler    — Tiered reconnection strategy
UTDSyncManager            — Periodic and on-demand state synchronization
UTDMessageRouter          — Type-based message dispatch
UTDMessageBatcher         — 60fps message batching with deduplication
```

All state is exposed via `ValueNotifier`, making it straightforward to integrate with any state management approach (BLoC, Provider, Riverpod, or plain `ValueListenableBuilder`).

## API Reference

### Key Classes

| Class                    | Purpose                                         |
|--------------------------|--------------------------------------------------|
| `UTDAudioRoom`           | Main widget — drop-in audio room UI              |
| `UTDAudioRoomConfig`     | Room configuration and widget customization      |
| `UTDRoomController`      | Central controller for room, seats, and media     |
| `UTDSeatController`      | Seat operations (take, leave, lock, kick, swap)   |
| `UTDMediaController`     | Mic, camera, and speaker toggles                  |
| `UTDRoomManager`         | LiveKit room connection and data streams          |
| `UTDMessageRouter`       | Register handlers for specific message types      |
| `UTDMiniPopScope`        | Back-button interception for minimize             |
| `UTDMiniOverlayPage`     | Floating mini player when room is minimized       |
| `SeatState`              | Immutable model for a single seat                 |
| `UTDParticipant`         | Immutable model for a room participant            |

### Enums

| Enum                     | Values                                                          |
|--------------------------|-----------------------------------------------------------------|
| `UTDConnectionState`     | `disconnected`, `connecting`, `connected`, `reconnecting`, `error` |
