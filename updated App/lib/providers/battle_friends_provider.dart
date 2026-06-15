import 'dart:convert';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

class BattleFriend {
  final String id;
  final String name;
  final String? mobile;

  const BattleFriend({
    required this.id,
    required this.name,
    this.mobile,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'mobile': mobile,
      };

  factory BattleFriend.fromJson(Map<String, dynamic> json) => BattleFriend(
        id: json['id']?.toString() ?? '',
        name: json['name']?.toString() ?? 'Friend',
        mobile: json['mobile']?.toString(),
      );
}

final battleFriendsProvider =
    StateNotifierProvider<BattleFriendsNotifier, List<BattleFriend>>((ref) {
  return BattleFriendsNotifier();
});

class BattleFriendsNotifier extends StateNotifier<List<BattleFriend>> {
  BattleFriendsNotifier() : super(const []) {
    _load();
  }

  static const _storageKey = 'battle_friends_v1';

  Future<void> _load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_storageKey);
    if (raw == null || raw.isEmpty) return;
    try {
      final list = jsonDecode(raw) as List;
      state = list
          .map((e) => BattleFriend.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();
    } catch (_) {}
  }

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    final encoded = jsonEncode(state.map((f) => f.toJson()).toList());
    await prefs.setString(_storageKey, encoded);
  }

  Future<void> addFriend({required String name, String? mobile}) async {
    final trimmed = name.trim();
    if (trimmed.length < 2) return;
    final friend = BattleFriend(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      name: trimmed,
      mobile: mobile?.trim().isEmpty == true ? null : mobile?.trim(),
    );
    state = [...state, friend];
    await _persist();
  }

  Future<void> removeFriend(String id) async {
    state = state.where((f) => f.id != id).toList();
    await _persist();
  }
}
