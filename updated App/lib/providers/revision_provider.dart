import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/models.dart';
import 'providers.dart' show apiServiceProvider;

class RevisionState {
  final bool loading;
  final String? error;
  final int strengthScore;
  final double overallAccuracy;
  final List<WeakTopicItem> weakTopics;
  final List<WeakTopicItem> revisionNeeded;
  final RevisionPlan? plan;
  final List<FlashcardItem> flashcards;

  const RevisionState({
    this.loading = false,
    this.error,
    this.strengthScore = 0,
    this.overallAccuracy = 0,
    this.weakTopics = const [],
    this.revisionNeeded = const [],
    this.plan,
    this.flashcards = const [],
  });

  RevisionState copyWith({
    bool? loading,
    String? error,
    int? strengthScore,
    double? overallAccuracy,
    List<WeakTopicItem>? weakTopics,
    List<WeakTopicItem>? revisionNeeded,
    RevisionPlan? plan,
    List<FlashcardItem>? flashcards,
  }) {
    return RevisionState(
      loading: loading ?? this.loading,
      error: error,
      strengthScore: strengthScore ?? this.strengthScore,
      overallAccuracy: overallAccuracy ?? this.overallAccuracy,
      weakTopics: weakTopics ?? this.weakTopics,
      revisionNeeded: revisionNeeded ?? this.revisionNeeded,
      plan: plan ?? this.plan,
      flashcards: flashcards ?? this.flashcards,
    );
  }
}

class RevisionNotifier extends StateNotifier<RevisionState> {
  final Ref _ref;

  RevisionNotifier(this._ref) : super(const RevisionState());

  Future<void> loadProfile() async {
    state = state.copyWith(loading: true, error: null);
    try {
      final data = await _ref.read(apiServiceProvider).getRevisionProfile();
      final weakList = (data['weak_topics'] as List? ?? [])
          .map((e) => WeakTopicItem.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();
      final revisionList = (data['revision_needed'] as List? ?? [])
          .map((e) => WeakTopicItem.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();

      state = state.copyWith(
        loading: false,
        strengthScore: (data['strength_score'] as num?)?.toInt() ?? 0,
        overallAccuracy: (data['overall_accuracy'] as num?)?.toDouble() ?? 0,
        weakTopics: weakList,
        revisionNeeded: revisionList,
      );
    } catch (e) {
      state = state.copyWith(
        loading: false,
        error: 'Could not load revision profile',
      );
    }
  }

  Future<void> loadPlan() async {
    state = state.copyWith(loading: true, error: null);
    try {
      final plan = await _ref.read(apiServiceProvider).getRevisionPlan();
      state = state.copyWith(loading: false, plan: plan);
    } catch (_) {
      state = state.copyWith(
        loading: false,
        error: 'Could not load revision plan',
      );
    }
  }

  Future<void> loadFlashcards({String? subject}) async {
    state = state.copyWith(loading: true, error: null);
    try {
      final cards =
          await _ref.read(apiServiceProvider).getRevisionFlashcards(subject: subject);
      state = state.copyWith(loading: false, flashcards: cards);
    } catch (_) {
      state = state.copyWith(
        loading: false,
        error: 'Could not load flashcards',
      );
    }
  }

  Future<void> loadAll() async {
    state = state.copyWith(loading: true, error: null);
    try {
      final api = _ref.read(apiServiceProvider);
      final results = await Future.wait([
        api.getRevisionProfile(),
        api.getRevisionPlan(),
        api.getRevisionFlashcards(),
      ]);

      final profile = results[0] as Map<String, dynamic>;
      final plan = results[1] as RevisionPlan;
      final cards = results[2] as List<FlashcardItem>;

      final weakList = (profile['weak_topics'] as List? ?? [])
          .map((e) => WeakTopicItem.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();
      final revisionList = (profile['revision_needed'] as List? ?? [])
          .map((e) => WeakTopicItem.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();

      state = RevisionState(
        loading: false,
        strengthScore: (profile['strength_score'] as num?)?.toInt() ?? 0,
        overallAccuracy: (profile['overall_accuracy'] as num?)?.toDouble() ?? 0,
        weakTopics: weakList,
        revisionNeeded: revisionList,
        plan: plan,
        flashcards: cards,
      );
    } catch (_) {
      state = state.copyWith(
        loading: false,
        error: 'Could not load revision data',
      );
    }
  }
}

final revisionProvider =
    StateNotifierProvider<RevisionNotifier, RevisionState>((ref) {
  return RevisionNotifier(ref);
});
