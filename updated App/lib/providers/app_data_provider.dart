import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_badges.dart';
import '../models/models.dart';
import '../services/api_service.dart';
import 'providers.dart' show apiServiceProvider;

/// Persisted chat language: english | hindi | hinglish
final languageProvider =
    StateNotifierProvider<LanguageNotifier, String>((ref) => LanguageNotifier());

class LanguageNotifier extends StateNotifier<String> {
  LanguageNotifier() : super('english');

  void setLanguage(String lang) => state = lang;

  String get apiValue {
    switch (state.toLowerCase()) {
      case 'hindi':
        return 'hindi';
      case 'hinglish':
        return 'hinglish';
      default:
        return 'english';
    }
  }
}

class HomeMission {
  final String id;
  final String title;
  final String subtitle;
  final String route;
  final bool done;

  const HomeMission({
    required this.id,
    required this.title,
    required this.subtitle,
    required this.route,
    this.done = false,
  });
}

class WeakTopicAlert {
  final String topic;
  final String subject;
  final int accuracy;

  const WeakTopicAlert({
    required this.topic,
    required this.subject,
    required this.accuracy,
  });
}

class HomePlanTask {
  final String id;
  final String subject;
  final String title;
  final String subtitle;
  final String action;
  final String? topic;
  final String? examType;
  final bool completed;

  const HomePlanTask({
    required this.id,
    required this.subject,
    required this.title,
    required this.subtitle,
    required this.action,
    this.topic,
    this.examType,
    this.completed = false,
  });
}

class HomeDailyChallengeInfo {
  final String title;
  final String description;
  final int participants;
  final int rewardXp;
  final bool available;
  final bool completed;

  const HomeDailyChallengeInfo({
    this.title = 'Daily Challenge',
    this.description = 'Solve today\'s questions and earn XP.',
    this.participants = 0,
    this.rewardXp = 50,
    this.available = false,
    this.completed = false,
  });
}

class HomeContinueLearning {
  final String examName;
  final String subject;
  final String topic;
  final int topicsDone;
  final int topicsTotal;
  final int? examId;

  const HomeContinueLearning({
    this.examName = 'JEE Main',
    this.subject = 'Physics',
    this.topic = 'Continue your prep',
    this.topicsDone = 0,
    this.topicsTotal = 25,
    this.examId,
  });
}

class HomeUpcomingExam {
  final String examName;
  final int? daysLeft;
  final String? examDate;

  const HomeUpcomingExam({
    this.examName = 'Your Exam',
    this.daysLeft,
    this.examDate,
  });
}

class HomeDashboardData {
  final int streak;
  final int level;
  final int xp;
  final int xpMax;
  final int readinessScore;
  final int dailyProgress;
  final int planCompleted;
  final int planTotal;
  final int? examDaysLeft;
  final String? examName;
  final String? examDateLabel;
  final List<HomeMission> missions;
  final List<HomePlanTask> todayPlan;
  final List<WeakTopicAlert> weakTopics;
  final HomeDailyChallengeInfo dailyChallenge;
  final HomeContinueLearning continueLearning;
  final HomeUpcomingExam upcomingExam;
  final double studyHoursWeek;
  final int averageAccuracy;
  final UserBadgesPayload badges;
  final bool loading;
  final String? error;

  const HomeDashboardData({
    this.streak = 0,
    this.level = 1,
    this.xp = 0,
    this.xpMax = 6000,
    this.readinessScore = 0,
    this.dailyProgress = 0,
    this.planCompleted = 0,
    this.planTotal = 0,
    this.examDaysLeft,
    this.examName,
    this.examDateLabel,
    this.missions = const [],
    this.todayPlan = const [],
    this.weakTopics = const [],
    this.dailyChallenge = const HomeDailyChallengeInfo(),
    this.continueLearning = const HomeContinueLearning(),
    this.upcomingExam = const HomeUpcomingExam(),
    this.studyHoursWeek = 0,
    this.averageAccuracy = 0,
    this.badges = UserBadgesPayload.empty,
    this.loading = false,
    this.error,
  });
}

final homeDashboardProvider =
    StateNotifierProvider<HomeDashboardNotifier, HomeDashboardData>((ref) {
  return HomeDashboardNotifier(ref.read(apiServiceProvider));
});

class HomeDashboardNotifier extends StateNotifier<HomeDashboardData> {
  final ApiService _api;

  HomeDashboardNotifier(this._api) : super(const HomeDashboardData(loading: true)) {
    load();
  }

  Future<void> load() async {
    state = const HomeDashboardData(loading: true);
    try {
      final results = await Future.wait([
        _api.getUser(),
        _api.getQuizStats(period: 'weekly'),
        _api.getUsageSummary(),
        _api.getDailyChallenge(),
        _api.getExams(),
      ]);

      final userData = Map<String, dynamic>.from(results[0] as Map);
      final quizStats = results[1] as Map<String, dynamic>;
      final usage = results[2] as Map<String, dynamic>;
      final daily = results[3] as Map<String, dynamic>;
      final exams = results[4] as List;

      final stats = quizStats['stats'] as Map<String, dynamic>? ?? {};
      final avgAccuracy = stats['averageScore'] as int? ?? 0;
      final totalSeconds = stats['totalTimeSeconds'] as int? ?? 0;
      final studyHours = totalSeconds / 3600.0;

      int readiness = avgAccuracy;
      int streak = stats['dayStreak'] as int? ??
          (userData['current_streak'] as int?) ??
          (userData['streak'] as int?) ??
          0;
      int level = (userData['current_level'] as int?) ??
          (userData['level'] as int?) ??
          1;
      int xpDisplay = 0;
      int xpMaxDisplay = 2000;
      List<WeakTopicAlert> weak = [];
      RevisionPlan? revisionPlan;
      UserBadgesPayload badgesPayload = UserBadgesPayload.empty;
      final dailyDone = daily['user_attempt']?['completed'] == true;
      try {
        final revisionProfile = await _api.getRevisionProfile();
        readiness = revisionProfile['strength_score'] as int? ??
            (revisionProfile['overall_accuracy'] as num?)?.toInt() ??
            avgAccuracy;

        final profile = revisionProfile['profile'] as Map?;
        if (profile != null) {
          streak = (profile['streak'] as num?)?.toInt() ?? streak;
          level = (profile['level'] as num?)?.toInt() ?? level;
        }

        if (revisionProfile['badges'] is Map) {
          badgesPayload = UserBadgesPayload.fromJson(
            Map<String, dynamic>.from(revisionProfile['badges'] as Map),
          );
          streak = badgesPayload.stats.streak > streak
              ? badgesPayload.stats.streak
              : streak;
          level = badgesPayload.stats.level > 0
              ? badgesPayload.stats.level
              : level;
        }

        final levelProgress = revisionProfile['level_progress'] as Map?;
        if (levelProgress != null) {
          level = (levelProgress['current_level'] as num?)?.toInt() ?? level;
          final needed = (levelProgress['xp_needed'] as num?)?.toInt() ?? 2000;
          final pct =
              (levelProgress['progress_percent'] as num?)?.toDouble() ?? 0;
          if (pct > 0 && pct < 100) {
            xpMaxDisplay = (needed / (1 - pct / 100)).round();
            xpDisplay = (xpMaxDisplay * pct / 100).round();
          } else if (pct >= 100) {
            xpDisplay = needed;
            xpMaxDisplay = needed > 0 ? needed : 2000;
          }
        }

        final weakList = revisionProfile['weak_topics'] as List? ?? [];
        weak = weakList.take(5).map((w) {
          final m = Map<String, dynamic>.from(w as Map);
          return WeakTopicAlert(
            topic: m['topic']?.toString() ?? m['name']?.toString() ?? 'Topic',
            subject: m['subject']?.toString() ?? '',
            accuracy: (m['success_rate'] as num?)?.toInt() ??
                (((m['accuracy'] as num?)?.toDouble() ?? 0) * 100).round(),
          );
        }).toList();
      } catch (_) {
        int? examId;
        if (exams.isNotEmpty) {
          final e = exams.first as Map;
          examId = e['id'] as int?;
        }

        if (examId != null) {
          try {
            final analysis = await _api.getSubjectAnalysis(examId);
            readiness = analysis['readiness_score'] as int? ??
                analysis['overall_score'] as int? ??
                avgAccuracy;
            final weakList = analysis['weak_topics'] as List? ??
                analysis['weak_subjects'] as List? ??
                [];
            weak = weakList.take(5).map((w) {
              final m = Map<String, dynamic>.from(w as Map);
              return WeakTopicAlert(
                topic: m['topic']?.toString() ?? m['name']?.toString() ?? 'Topic',
                subject: m['subject']?.toString() ?? '',
                accuracy: (m['accuracy'] as num?)?.toInt() ??
                    (m['score'] as num?)?.toInt() ??
                    0,
              );
            }).toList();
          } catch (_) {}
        }
      }

      try {
        revisionPlan = await _api.getRevisionPlan();
        if (revisionPlan.userStreak > streak) streak = revisionPlan.userStreak;
      } catch (_) {}

      final usageMap = usage['usage'] as Map<String, dynamic>? ?? {};
      final challengeAvailable = daily['available'] == true;
      final challengeMap = daily['challenge'] as Map?;

      final missions = <HomeMission>[
        HomeMission(
          id: 'daily',
          title: 'Daily Challenge',
          subtitle: dailyDone
              ? 'Completed today ✓'
              : challengeAvailable
                  ? 'Earn XP — start now'
                  : 'Check back tomorrow',
          route: '/daily-challenge',
          done: dailyDone,
        ),
        HomeMission(
          id: 'quiz',
          title: 'Practice Quiz',
          subtitle: '${stats['totalQuizzes'] ?? 0} done this week',
          route: '/quiz-topics',
          done: (stats['totalQuizzes'] as int? ?? 0) >= 3,
        ),
        HomeMission(
          id: 'ai',
          title: 'Ask AI Tutor',
          subtitle: _usageLabel(usageMap, 'ai_doubt', 'AI chats'),
          route: '/ai-tutor',
          done: false,
        ),
        HomeMission(
          id: 'battle',
          title: 'Study Battle',
          subtitle: _usageLabel(usageMap, 'study_battle', 'Battles'),
          route: '/battles',
          done: false,
        ),
      ];

      final todayPlan = _buildTodayPlan(
        plan: revisionPlan,
        weak: weak,
        stats: stats,
      );

      int planCompleted = 0;
      int planTotal = 0;
      int dailyProgress = 0;
      if (revisionPlan != null && revisionPlan.days.isNotEmpty) {
        planTotal = revisionPlan.days.length;
        planCompleted = revisionPlan.days.where((d) => d.completed).length;
        dailyProgress = ((planCompleted / planTotal) * 100).round().clamp(0, 100);
      } else {
        final done = missions.where((m) => m.done).length;
        dailyProgress = missions.isEmpty
            ? readiness.clamp(0, 100)
            : ((done / missions.length) * 100).round().clamp(0, 100);
        planTotal = todayPlan.length;
        planCompleted = todayPlan.where((t) => t.completed).length;
      }

      final dailyChallenge = _buildDailyChallenge(daily, challengeMap, dailyDone);

      final continueLearning = await _buildContinueLearning(
        userData: userData,
        exams: exams,
        plan: revisionPlan,
        weak: weak,
        stats: stats,
        readiness: readiness,
      );

      final upcomingExam = _buildUpcomingExam(
        userData: userData,
        exams: exams,
      );

      final badgeStats = badgesPayload;

      state = HomeDashboardData(
        streak: streak,
        level: level,
        xp: xpDisplay,
        xpMax: xpMaxDisplay,
        readinessScore: readiness,
        dailyProgress: dailyProgress,
        planCompleted: planCompleted,
        planTotal: planTotal,
        examDaysLeft: upcomingExam.daysLeft,
        examName: upcomingExam.examName,
        examDateLabel: upcomingExam.examDate,
        missions: missions,
        todayPlan: todayPlan,
        weakTopics: weak,
        dailyChallenge: dailyChallenge,
        continueLearning: continueLearning,
        upcomingExam: upcomingExam,
        studyHoursWeek: studyHours,
        averageAccuracy: avgAccuracy,
        badges: badgeStats,
      );
    } catch (e) {
      state = HomeDashboardData(error: e.toString());
    }
  }

  List<HomePlanTask> _buildTodayPlan({
    required RevisionPlan? plan,
    required List<WeakTopicAlert> weak,
    required Map<String, dynamic> stats,
  }) {
    final tasks = <HomePlanTask>[];

    if (plan != null && plan.days.isNotEmpty) {
      for (final day in plan.days) {
        if (day.action == 'mock_test') continue;
        tasks.add(HomePlanTask(
          id: 'plan_day_${day.day}',
          subject: day.subject,
          title: day.topic,
          subtitle: day.completed
              ? 'Completed ✓'
              : day.successRate != null
                  ? '${day.successRate!.round()}% accuracy · 15 min'
                  : '15 min session',
          action: day.action,
          topic: day.topic,
          completed: day.completed,
        ));
        if (tasks.length >= 2) break;
      }
    } else {
      for (final w in weak.take(2)) {
        tasks.add(HomePlanTask(
          id: 'weak_${w.topic}',
          subject: w.subject.isNotEmpty ? w.subject : 'General',
          title: w.topic,
          subtitle: '${w.accuracy}% accuracy · 15 min',
          action: 'revise',
          topic: w.topic,
          completed: w.accuracy >= 70,
        ));
      }
    }

    tasks.add(HomePlanTask(
      id: 'quiz',
      subject: 'Quiz',
      title: '15 Questions Quiz',
      subtitle: '${stats['totalQuizzes'] ?? 0} done this week',
      action: 'quiz',
      completed: (stats['totalQuizzes'] as int? ?? 0) >= 1,
    ));

    tasks.add(HomePlanTask(
      id: 'revision',
      subject: 'Revision',
      title: 'Revise Weak Topics',
      subtitle: weak.isNotEmpty
          ? '${weak.length} weak topics'
          : 'AI plan ready',
      action: 'revision',
      completed: false,
    ));

    return tasks;
  }

  HomeDailyChallengeInfo _buildDailyChallenge(
    Map<String, dynamic> daily,
    Map? challengeMap,
    bool dailyDone,
  ) {
    final timeLimit = (challengeMap?['time_limit_seconds'] as num?)?.toInt() ?? 600;
    final questionCount = (challengeMap?['question_count'] as num?)?.toInt() ?? 10;
    final reward = (challengeMap?['reward_credits'] as num?)?.toInt() ?? 50;
    final participants = (challengeMap?['participants_count'] as num?)?.toInt() ??
        (daily['leaderboard'] as List?)?.length ??
        0;

    return HomeDailyChallengeInfo(
      title: challengeMap?['title']?.toString() ?? 'Daily Challenge',
      description: daily['available'] == true
          ? 'Solve $questionCount questions in ${timeLimit ~/ 60} mins.\nWin $reward XP & climb the leaderboard.'
          : daily['message']?.toString() ??
              'No challenge today. Check back tomorrow!',
      participants: participants,
      rewardXp: reward,
      available: daily['available'] == true,
      completed: dailyDone,
    );
  }

  Future<HomeContinueLearning> _buildContinueLearning({
    required Map<String, dynamic> userData,
    required List exams,
    required RevisionPlan? plan,
    required List<WeakTopicAlert> weak,
    required Map<String, dynamic> stats,
    required int readiness,
  }) async {
    String examName = userData['target_exam']?.toString() ?? '';
    int? examId;
    String subject = 'Physics';
    String topic = 'Continue your prep';
    int topicsDone = (stats['totalQuestions'] as num?)?.toInt() ?? 0;
    int topicsTotal = 25;

    if (exams.isNotEmpty) {
      final e = Map<String, dynamic>.from(exams.first as Map);
      examId = e['id'] as int?;
      if (examName.isEmpty) examName = e['name']?.toString() ?? 'Your Exam';
    }
    if (examName.isEmpty) examName = 'JEE Main';

    if (plan != null) {
      for (final day in plan.days) {
        if (!day.completed && day.action != 'mock_test') {
          subject = day.subject;
          topic = day.topic;
          break;
        }
      }
    } else if (weak.isNotEmpty) {
      subject = weak.first.subject.isNotEmpty ? weak.first.subject : subject;
      topic = weak.first.topic;
    }

    try {
      final history = await _api.getQuizHistory(limit: 1);
      if (history.isNotEmpty) {
        final h = Map<String, dynamic>.from(history.first as Map);
        subject = h['subject']?.toString() ?? subject;
        topic = h['topic']?.toString() ?? topic;
      }
    } catch (_) {}

    if (examId != null) {
      try {
        final analysis = await _api.getSubjectAnalysis(examId);
        topicsDone = (analysis['topics_completed'] as num?)?.toInt() ??
            (analysis['completed_topics'] as num?)?.toInt() ??
            ((analysis['readiness_score'] as num? ?? readiness) / 100 * 25)
                .round();
        topicsTotal = (analysis['total_topics'] as num?)?.toInt() ?? 25;
      } catch (_) {
        topicsDone = ((readiness / 100) * topicsTotal).round();
      }
    } else {
      topicsDone = topicsDone.clamp(0, topicsTotal);
    }

    return HomeContinueLearning(
      examName: examName,
      subject: subject,
      topic: topic,
      topicsDone: topicsDone.clamp(0, topicsTotal),
      topicsTotal: topicsTotal,
      examId: examId,
    );
  }

  HomeUpcomingExam _buildUpcomingExam({
    required Map<String, dynamic> userData,
    required List exams,
  }) {
    String examName = userData['target_exam']?.toString() ?? 'Your Exam';
    String? rawDate = userData['exam_date']?.toString();

    if (exams.isNotEmpty) {
      final e = Map<String, dynamic>.from(exams.first as Map);
      examName = e['name']?.toString() ?? examName;
      rawDate ??= e['exam_date']?.toString();
    }

    int? daysLeft;
    String? dateLabel;
    if (rawDate != null && rawDate.isNotEmpty) {
      final dt = DateTime.tryParse(rawDate);
      if (dt != null) {
        daysLeft = dt.difference(DateTime.now()).inDays;
        if (daysLeft < 0) daysLeft = 0;
        const months = [
          'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
        ];
        dateLabel = '${dt.day} ${months[dt.month - 1]} ${dt.year}';
      }
    }

    return HomeUpcomingExam(
      examName: examName,
      daysLeft: daysLeft,
      examDate: dateLabel,
    );
  }

  String _usageLabel(Map usage, String key, String label) {
    final item = usage[key];
    if (item is Map) {
      final used = item['used'] ?? 0;
      final limit = item['limit'];
      if (limit == 'unlimited') return '$label: $used used';
      return '$label: $used / $limit';
    }
    return label;
  }
}

final leaderboardProvider =
    FutureProvider.autoDispose<List<LeaderboardEntry>>((ref) async {
  final api = ref.read(apiServiceProvider);
  try {
    final battle = await api.getBattleLeaderboard();
    if (battle.isNotEmpty) {
      return battle.asMap().entries.map((e) {
        final m = Map<String, dynamic>.from(e.value as Map);
        return LeaderboardEntry(
          rank: e.key + 1,
          name: m['name']?.toString() ?? m['user_name']?.toString() ?? 'Student',
          score: (m['score'] as num?)?.toInt() ?? (m['total_score'] as num?)?.toInt() ?? 0,
          isMe: m['is_me'] == true,
        );
      }).toList();
    }
  } catch (_) {}

  try {
    final daily = await api.getDailyChallengeLeaderboard();
    return daily.asMap().entries.map((e) {
      final m = Map<String, dynamic>.from(e.value as Map);
      return LeaderboardEntry(
        rank: m['rank'] as int? ?? e.key + 1,
        name: m['name']?.toString() ?? 'Student',
        score: m['score'] as int? ?? 0,
        isMe: false,
      );
    }).toList();
  } catch (_) {
    return [];
  }
});

class LeaderboardEntry {
  final int rank;
  final String name;
  final int score;
  final bool isMe;

  const LeaderboardEntry({
    required this.rank,
    required this.name,
    required this.score,
    this.isMe = false,
  });
}

class UserStudyPreferences {
  final String targetExam;
  final String studentClass;
  final String subjects;

  const UserStudyPreferences({
    this.targetExam = 'JEE Main',
    this.studentClass = '12',
    this.subjects = 'PCM',
  });

  UserStudyPreferences copyWith({
    String? targetExam,
    String? studentClass,
    String? subjects,
  }) {
    return UserStudyPreferences(
      targetExam: targetExam ?? this.targetExam,
      studentClass: studentClass ?? this.studentClass,
      subjects: subjects ?? this.subjects,
    );
  }
}

final userStudyPreferencesProvider =
    StateNotifierProvider<UserStudyPreferencesNotifier, UserStudyPreferences>(
  (ref) => UserStudyPreferencesNotifier(),
);

class UserStudyPreferencesNotifier extends StateNotifier<UserStudyPreferences> {
  UserStudyPreferencesNotifier() : super(const UserStudyPreferences()) {
    _load();
  }

  static const _examKey = 'study_target_exam';
  static const _classKey = 'study_class';
  static const _subjectsKey = 'study_subjects';

  Future<void> _load() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      state = UserStudyPreferences(
        targetExam: prefs.getString(_examKey) ?? state.targetExam,
        studentClass: prefs.getString(_classKey) ?? state.studentClass,
        subjects: prefs.getString(_subjectsKey) ?? state.subjects,
      );
    } catch (_) {}
  }

  Future<void> save({
    required String targetExam,
    required String studentClass,
    required String subjects,
  }) async {
    state = UserStudyPreferences(
      targetExam: targetExam,
      studentClass: studentClass,
      subjects: subjects,
    );
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_examKey, targetExam);
      await prefs.setString(_classKey, studentClass);
      await prefs.setString(_subjectsKey, subjects);
    } catch (_) {}
  }
}
