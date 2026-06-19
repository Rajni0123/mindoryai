import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';
import '../models/models.dart';

class SavedNotesService {
  static const _key = 'saved_revision_notes';

  Future<List<SavedNote>> loadAll() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_key);
    if (raw == null || raw.isEmpty) return [];

    try {
      final list = jsonDecode(raw) as List<dynamic>;
      return list
          .map((e) => SavedNote.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList()
        ..sort((a, b) => b.createdAt.compareTo(a.createdAt));
    } catch (_) {
      return [];
    }
  }

  Future<SavedNote> save({
    required String content,
    String subject = 'General',
    String source = 'App',
    String? title,
  }) async {
    final trimmed = content.trim();
    if (trimmed.isEmpty) {
      throw ArgumentError('Note content cannot be empty');
    }

    final note = SavedNote(
      id: DateTime.now().microsecondsSinceEpoch.toString(),
      title: title ?? SavedNote.titleFromContent(trimmed),
      content: trimmed,
      subject: subject,
      source: source,
      createdAt: DateTime.now(),
    );

    final notes = await loadAll();
    notes.insert(0, note);
    await _persist(notes);
    return note;
  }

  Future<void> delete(String id) async {
    final notes = await loadAll();
    notes.removeWhere((n) => n.id == id);
    await _persist(notes);
  }

  Future<void> _persist(List<SavedNote> notes) async {
    final prefs = await SharedPreferences.getInstance();
    final encoded = jsonEncode(notes.map((n) => n.toJson()).toList());
    await prefs.setString(_key, encoded);
  }
}
