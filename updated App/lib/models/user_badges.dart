import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

/// Badge payload from backend `/user/badges` or `revision/profile.badges`.
class UserBadgesPayload {
  final BadgeStats stats;
  final ApiBadge? league;
  final ApiBadge? streak;
  final ApiBadge? battle;
  final ApiBadge? topper;
  final BadgeTierProgress streakProgress;
  final BadgeTierProgress leagueProgress;
  final List<ApiBadge> unlocked;

  const UserBadgesPayload({
    required this.stats,
    this.league,
    this.streak,
    this.battle,
    this.topper,
    this.streakProgress = const BadgeTierProgress(),
    this.leagueProgress = const BadgeTierProgress(),
    this.unlocked = const [],
  });

  factory UserBadgesPayload.fromJson(Map<String, dynamic> json) {
    final primary = json['primary'] as Map<String, dynamic>? ?? {};
    final progress = json['progress'] as Map<String, dynamic>? ?? {};
    final unlocked = (json['unlocked'] as List? ?? [])
        .map((e) => ApiBadge.fromJson(Map<String, dynamic>.from(e as Map)))
        .toList();

    return UserBadgesPayload(
      stats: BadgeStats.fromJson(
        Map<String, dynamic>.from(json['stats'] as Map? ?? {}),
      ),
      league: _badgeFrom(primary['league']),
      streak: _badgeFrom(primary['streak']),
      battle: _badgeFrom(primary['battle']),
      topper: _badgeFrom(primary['topper']),
      streakProgress: BadgeTierProgress.fromJson(
        Map<String, dynamic>.from(progress['streak'] as Map? ?? {}),
      ),
      leagueProgress: BadgeTierProgress.fromJson(
        Map<String, dynamic>.from(progress['league'] as Map? ?? {}),
      ),
      unlocked: unlocked,
    );
  }

  static ApiBadge? _badgeFrom(dynamic raw) {
    if (raw is! Map) return null;
    return ApiBadge.fromJson(Map<String, dynamic>.from(raw));
  }

  static const empty = UserBadgesPayload(stats: BadgeStats());
}

class BadgeStats {
  final int streak;
  final int level;
  final int accuracy;
  final int questionsAnswered;
  final int battleWins;
  final int totalQuizzes;
  final int xpPercentile;
  final bool dailyChallengeCompletedToday;

  const BadgeStats({
    this.streak = 0,
    this.level = 1,
    this.accuracy = 0,
    this.questionsAnswered = 0,
    this.battleWins = 0,
    this.totalQuizzes = 0,
    this.xpPercentile = 0,
    this.dailyChallengeCompletedToday = false,
  });

  factory BadgeStats.fromJson(Map<String, dynamic> json) => BadgeStats(
        streak: (json['streak'] as num?)?.toInt() ?? 0,
        level: (json['level'] as num?)?.toInt() ?? 1,
        accuracy: (json['accuracy'] as num?)?.toInt() ?? 0,
        questionsAnswered: (json['questions_answered'] as num?)?.toInt() ?? 0,
        battleWins: (json['battle_wins'] as num?)?.toInt() ?? 0,
        totalQuizzes: (json['total_quizzes'] as num?)?.toInt() ?? 0,
        xpPercentile: (json['xp_percentile'] as num?)?.toInt() ?? 0,
        dailyChallengeCompletedToday:
            json['daily_challenge_completed_today'] == true,
      );
}

class BadgeTierProgress {
  final double percent;
  final ApiBadge? nextBadge;
  final int valueToNext;

  const BadgeTierProgress({
    this.percent = 0,
    this.nextBadge,
    this.valueToNext = 0,
  });

  factory BadgeTierProgress.fromJson(Map<String, dynamic> json) =>
      BadgeTierProgress(
        percent: (json['percent'] as num?)?.toDouble() ?? 0,
        nextBadge: UserBadgesPayload._badgeFrom(json['next_badge']),
        valueToNext: (json['value_to_next'] as num?)?.toInt() ?? 0,
      );
}

class ApiBadge {
  final String id;
  final String name;
  final String tagline;
  final String category;
  final String icon;
  final List<Color> gradient;
  final Color borderColor;

  const ApiBadge({
    required this.id,
    required this.name,
    required this.tagline,
    required this.category,
    required this.icon,
    required this.gradient,
    required this.borderColor,
  });

  factory ApiBadge.fromJson(Map<String, dynamic> json) {
    final colors = json['colors'] as Map<String, dynamic>? ?? {};
    final gradientRaw = colors['gradient'] as List? ?? ['#705CF6', '#5B21B6'];
    return ApiBadge(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      tagline: json['tagline']?.toString() ?? '',
      category: json['category']?.toString() ?? '',
      icon: json['icon']?.toString() ?? 'star',
      gradient: gradientRaw
          .map((c) => _colorFromHex(c.toString()))
          .toList(),
      borderColor: _colorFromHex(colors['border']?.toString() ?? '#A78BFA'),
    );
  }

  IconData get iconData => BadgeIconMap.resolve(icon);
}

class BadgeIconMap {
  static IconData resolve(String key) {
    switch (key) {
      case 'book_open':
        return LucideIcons.bookOpen;
      case 'shield':
        return LucideIcons.shield;
      case 'gem':
        return LucideIcons.gem;
      case 'diamond':
        return LucideIcons.diamond;
      case 'crown':
        return LucideIcons.crown;
      case 'sparkles':
        return LucideIcons.sparkles;
      case 'fire':
        return Icons.local_fire_department_rounded;
      case 'bolt':
        return Icons.bolt_rounded;
      case 'trophy':
        return Icons.emoji_events_rounded;
      case 'swords':
        return LucideIcons.swords;
      case 'star':
        return LucideIcons.star;
      case 'target':
        return LucideIcons.target;
      case 'calendar_check':
        return LucideIcons.calendarCheck;
      case 'timer':
        return LucideIcons.timer;
      default:
        return LucideIcons.award;
    }
  }
}

Color _colorFromHex(String hex) {
  var value = hex.replaceAll('#', '');
  if (value.length == 6) value = 'FF$value';
  return Color(int.parse(value, radix: 16));
}
