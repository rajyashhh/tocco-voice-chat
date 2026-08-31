import 'dart:convert';

import 'package:general/src/core/index.dart';

/// Per-user, on-device "Most Used" usage counters (emoji / gift).
/// Pure client-side by design (owner decision): zero extra backend load at
/// million-user scale, and the data is naturally private to the device.
///
/// Storage shape (one Hive key per kind per user, JSON encoded):
///   { "<itemId>": { "c": useCount, "t": lastUsedMs, "i": {item snapshot} } }
///
/// Capped at [_cap] distinct items — when full, the least-recently-used
/// entry is evicted so the map can never grow unbounded.
class MostUsedTracker {
  MostUsedTracker._(this._kind);

  static final MostUsedTracker emoji = MostUsedTracker._('emoji');
  static final MostUsedTracker gift = MostUsedTracker._('gift');

  /// Synthetic gift-category id for the "Most Used" tab.
  /// (-1 = none, -2 = bag, -3 = search are already taken.)
  static const int giftCategoryId = -4;

  static const String _boxName = 'most_used';
  static const int _cap = 50;

  final String _kind;

  /// Per-user key: each account on the device keeps its own counters.
  String get _key => '${_kind}_${MyDataModel.getInstance().id ?? 0}';

  Future<Box> _box() async {
    if (Hive.isBoxOpen(_boxName)) return Hive.box(_boxName);
    return Hive.openBox(_boxName);
  }

  Map<String, dynamic> _read(Box box) {
    final raw = box.get(_key);
    if (raw is! String || raw.isEmpty) return {};
    try {
      final decoded = jsonDecode(raw);
      return decoded is Map<String, dynamic> ? decoded : {};
    } catch (_) {
      return {};
    }
  }

  /// Records one successful use of [id]. [item] is a self-sufficient JSON
  /// snapshot of the entity so the Most Used tab renders without needing the
  /// item's original category to be fetched first.
  Future<void> record(int id, Map<String, dynamic> item) async {
    try {
      final box = await _box();
      final data = _read(box);
      final key = id.toString();
      final prev = data[key];
      final prevCount = prev is Map ? ((prev['c'] as num?)?.toInt() ?? 0) : 0;
      data[key] = {
        'c': prevCount + 1,
        't': DateTime.now().millisecondsSinceEpoch,
        'i': item,
      };

      // LRU cap: evict the least-recently-used entry (never the new one).
      while (data.length > _cap) {
        String? oldestKey;
        num oldestTs = double.maxFinite;
        data.forEach((k, v) {
          if (k == key) return;
          final ts = v is Map ? ((v['t'] as num?) ?? 0) : 0;
          if (ts < oldestTs) {
            oldestTs = ts;
            oldestKey = k;
          }
        });
        if (oldestKey == null) break;
        data.remove(oldestKey);
      }

      await box.put(_key, jsonEncode(data));
    } catch (e) {
      Methods.printLog('[MostUsed:$_kind] record failed: $e');
    }
  }

  /// Item snapshots sorted by use count desc (ties: most recently used first).
  Future<List<Map<String, dynamic>>> topItems() async {
    try {
      final box = await _box();
      final entries = _read(box).values.whereType<Map>().toList()
        ..sort((a, b) {
          final byCount =
              ((b['c'] as num?) ?? 0).compareTo((a['c'] as num?) ?? 0);
          if (byCount != 0) return byCount;
          return ((b['t'] as num?) ?? 0).compareTo((a['t'] as num?) ?? 0);
        });
      return entries
          .map((e) => Map<String, dynamic>.from((e['i'] as Map?) ?? {}))
          .toList();
    } catch (e) {
      Methods.printLog('[MostUsed:$_kind] read failed: $e');
      return [];
    }
  }
}