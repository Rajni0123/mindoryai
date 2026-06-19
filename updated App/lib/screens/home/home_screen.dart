import 'dart:math' as math;

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../models/user_badges.dart';
import '../../providers/app_data_provider.dart';
import '../../providers/providers.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).user;
    final dash = ref.watch(homeDashboardProvider);
    final name = _firstName(user?.name);
    final streak = dash.streak > 0 ? dash.streak : (user?.streak ?? 0);
    final level = dash.level > 1 ? dash.level : (user?.level ?? 1);
    final xp = dash.xp > 0 ? dash.xp : (user?.xp ?? 0);
    final xpMax = dash.xpMax > 0 ? dash.xpMax : (user?.xpToNextLevel ?? 2000);
    final continueData = dash.continueLearning;
    final upcoming = dash.upcomingExam;
    final dailyChallenge = dash.dailyChallenge;
    final badges = dash.badges;

    return Scaffold(
      backgroundColor: context.dash.background,
      body: SafeArea(
        child: Column(
          children: [
            _TopBar(
              name: name,
              onNotifications: () => AppRouter.go(context, AppRoutes.notifications),
              onProfile: () => AppRouter.go(context, AppRoutes.profile),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () => ref.read(homeDashboardProvider.notifier).load(),
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(
                    parent: BouncingScrollPhysics(),
                  ),
                  padding: const EdgeInsets.fromLTRB(20, 4, 20, 120),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _GreetingBlock(name: name),
                      const SizedBox(height: 20),
                      IntrinsicHeight(
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Expanded(
                              child: _StreakCard(
                                streak: streak,
                                badges: badges,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: _LevelCard(
                                level: level,
                                xp: xp,
                                xpMax: xpMax,
                                badges: badges,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 20),
                      _TodaysPlanSection(
                        tasks: dash.todayPlan,
                        totalTasks: dash.todayPlanTotal,
                        coachNote: dash.planCoachNote,
                        progress: dash.dailyProgress,
                        completed: dash.planCompleted,
                        total: dash.planTotal,
                        onTaskTap: (task) => _onPlanTaskTap(context, ref, task),
                        onViewAll: () => AppRouter.go(context, AppRoutes.revisionPlan),
                        onStartBattle: () =>
                            ref.read(navIndexProvider.notifier).state = 4,
                      ),
                      const SizedBox(height: 22),
                      _QuickActionsRow(
                        onScan: () => ref.read(navIndexProvider.notifier).state = 2,
                        onAiTutor: () => ref.read(navIndexProvider.notifier).state = 1,
                        onQuiz: () => AppRouter.go(context, AppRoutes.quizTopics),
                        onBattles: () => ref.read(navIndexProvider.notifier).state = 4,
                        onRevision: () => AppRouter.go(context, AppRoutes.revision),
                      ),
                      const SizedBox(height: 22),
                      _DailyChallengeCard(
                        info: dailyChallenge,
                        onTap: () => AppRouter.go(context, AppRoutes.dailyChallenge),
                      ),
                      const SizedBox(height: 22),
                      _SectionHeader(
                        title: 'Continue Learning',
                        action: 'View all',
                        onAction: () => AppRouter.go(context, AppRoutes.examPrep),
                      ),
                      const SizedBox(height: 12),
                      _ContinueLearningCard(
                        exam: continueData.examName,
                        subject: continueData.subject,
                        topic: continueData.topic,
                        topicsDone: continueData.topicsDone,
                        topicsTotal: continueData.topicsTotal,
                        onContinue: () {
                          if (continueData.examId != null) {
                            AppRouter.go(context, AppRoutes.examPrep);
                          } else if (continueData.topic.isNotEmpty) {
                            AppRouter.go(context, AppRoutes.quiz, args: {
                              'topic': continueData.topic,
                              'subject': continueData.subject,
                              'examType': 'JEE',
                              'language': 'english',
                            });
                          } else {
                            AppRouter.go(context, AppRoutes.examPrep);
                          }
                        },
                      ),
                      const SizedBox(height: 18),
                      _UpcomingExamSection(
                        examName: upcoming.examName.isNotEmpty
                            ? upcoming.examName
                            : continueData.examName,
                        daysLeft: upcoming.daysLeft ?? 23,
                        examDate: upcoming.examDate ?? '29 Jan 2025',
                        onTap: () => AppRouter.go(context, AppRoutes.examPrep),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  static String _firstName(String? name) {
    if (name == null || name.trim().isEmpty) return 'Student';
    return name.trim().split(' ').first;
  }

  static void _onPlanTaskTap(
    BuildContext context,
    WidgetRef ref,
    HomePlanTask task,
  ) {
    switch (task.action) {
      case 'quiz':
        if (task.topic != null && task.topic!.isNotEmpty) {
          AppRouter.go(context, AppRoutes.quiz, args: {
            'topic': task.topic,
            'subject': task.subject,
            'examType': task.examType ?? 'JEE',
            'language': 'english',
          });
        } else {
          AppRouter.go(context, AppRoutes.quizTopics);
        }
        break;
      case 'revision':
        AppRouter.go(context, AppRoutes.revisionPlan);
        break;
      case 'mock_test':
        AppRouter.go(context, AppRoutes.examPrep);
        break;
      case 'battle':
        ref.read(navIndexProvider.notifier).state = 4;
        break;
      case 'revise':
      default:
        if (task.topic != null && task.topic!.isNotEmpty) {
          AppRouter.go(context, AppRoutes.quiz, args: {
            'topic': task.topic,
            'subject': task.subject,
            'examType': task.examType ?? 'JEE',
            'language': 'english',
          });
        } else {
          AppRouter.go(context, AppRoutes.revision);
        }
    }
  }
}

// ─── Top bar ──────────────────────────────────────────────────────────────────

class _TopBar extends ConsumerWidget {
  final String name;
  final VoidCallback onNotifications;
  final VoidCallback onProfile;

  const _TopBar({
    required this.name,
    required this.onNotifications,
    required this.onProfile,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 10, 16, 4),
      child: Row(
        children: [
          ShaderMask(
            shaderCallback: (b) => AppColors.primaryGradient.createShader(b),
            child: Text(
              'BlinkStudy',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 22,
                fontWeight: FontWeight.w800,
                color: Colors.white,
                letterSpacing: -0.5,
              ),
            ),
          ),
          const Spacer(),
          const _ThemeToggleButton(),
          const SizedBox(width: 8),
          _BellButton(onTap: onNotifications),
          const SizedBox(width: 10),
          GestureDetector(
            onTap: onProfile,
            child: Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: AppColors.primaryGradient,
                boxShadow: [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.25),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Center(
                child: Text(
                  name.isNotEmpty ? name[0].toUpperCase() : 'S',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ThemeToggleButton extends ConsumerWidget {
  const _ThemeToggleButton();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final c = context.dash;
    final isDark = ref.watch(themeModeProvider) == ThemeMode.dark;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () => ref.read(themeModeProvider.notifier).toggle(),
        borderRadius: BorderRadius.circular(14),
        child: Container(
          width: 56,
          height: 32,
          padding: const EdgeInsets.all(3),
          decoration: BoxDecoration(
            color: c.cardBorder,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: c.cardBorder),
          ),
          child: Stack(
            children: [
              AnimatedAlign(
                duration: const Duration(milliseconds: 220),
                curve: Curves.easeOutCubic,
                alignment:
                    isDark ? Alignment.centerRight : Alignment.centerLeft,
                child: Container(
                  width: 26,
                  height: 26,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: c.surface,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.12),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Icon(
                    isDark ? LucideIcons.moon : LucideIcons.sun,
                    size: 14,
                    color: isDark ? AppColors.primary : const Color(0xFFF59E0B),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _BellButton extends StatelessWidget {
  final VoidCallback onTap;
  const _BellButton({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: SizedBox(
          width: 42,
          height: 42,
          child: Stack(
            alignment: Alignment.center,
            children: [
              Icon(LucideIcons.bell, size: 22, color: context.dash.textPrimary),
              Positioned(
                top: 10,
                right: 10,
                child: Container(
                  width: 8,
                  height: 8,
                  decoration: BoxDecoration(
                    color: AppColors.error,
                    shape: BoxShape.circle,
                    border: Border.all(color: context.dash.surface, width: 1.5),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ─── Greeting ─────────────────────────────────────────────────────────────────

class _GreetingBlock extends StatelessWidget {
  final String name;
  const _GreetingBlock({required this.name});

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              RichText(
                text: TextSpan(
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 28,
                    fontWeight: FontWeight.w800,
                    color: c.textPrimary,
                    height: 1.2,
                  ),
                  children: [
                    const TextSpan(text: 'Hi, '),
                    TextSpan(
                      text: '$name! 👋',
                      style: const TextStyle(color: AppColors.primary),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 6),
              Text(
                "Let's make today count.",
                style: GoogleFonts.inter(
                  fontSize: 15,
                  color: c.textMuted,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(width: 8),
        CustomPaint(
          size: const Size(72, 48),
          painter: _WavePainter(color: AppColors.primary.withValues(alpha: 0.2)),
        ),
      ],
    ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.04, end: 0);
  }
}

class _WavePainter extends CustomPainter {
  final Color color;
  _WavePainter({required this.color});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3
      ..strokeCap = StrokeCap.round;
    final path = Path();
    path.moveTo(0, size.height * 0.6);
    for (var i = 0; i <= 20; i++) {
      final x = size.width * i / 20;
      final y = size.height * 0.6 + math.sin(i * 0.6) * 8;
      path.lineTo(x, y);
    }
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

// ─── Stats cards ──────────────────────────────────────────────────────────────

class _StreakCard extends StatelessWidget {
  final int streak;
  final UserBadgesPayload badges;

  const _StreakCard({required this.streak, required this.badges});

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final progress = badges.streakProgress.percent > 0
        ? badges.streakProgress.percent / 100
        : streak > 0
            ? (streak / 7).clamp(0.15, 1.0)
            : 0.08;
    final milestoneDays = badges.streakProgress.valueToNext > 0
        ? badges.streakProgress.valueToNext
        : 7;
    final nextBadgeName =
        badges.streakProgress.nextBadge?.name ?? 'Weekly Warrior';

    return _StatCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            height: 32,
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF97316).withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(
                    Icons.local_fire_department_rounded,
                    size: 20,
                    color: Color(0xFFF97316),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    streak > 0 ? '$streak Day Streak' : 'Start your streak!',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      color: c.textPrimary,
                      height: 1.2,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 6),
          SizedBox(
            height: 28,
            child: Align(
              alignment: Alignment.topLeft,
              child: Text(
                streak > 0
                    ? '$milestoneDays days to $nextBadgeName badge'
                    : 'Complete 1 task to begin',
                style: TextStyle(fontSize: 11, color: c.textMuted, height: 1.3),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
          const SizedBox(height: 12),
          _StatProgressBar(
            progress: progress.clamp(0.0, 1.0),
            colors: const [Color(0xFFF97316), Color(0xFF3B82F6)],
          ),
        ],
      ),
    );
  }
}

class _LevelCard extends StatelessWidget {
  final int level;
  final int xp;
  final int xpMax;
  final UserBadgesPayload badges;

  const _LevelCard({
    required this.level,
    required this.xp,
    required this.xpMax,
    required this.badges,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final progress = xpMax > 0
        ? (xp / xpMax).clamp(0.0, 1.0)
        : badges.leagueProgress.percent / 100;

    return _StatCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            height: 32,
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    'Level $level',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      color: c.textPrimary,
                      height: 1.2,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.all(5),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFBBF24).withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(
                    Icons.shield_rounded,
                    size: 18,
                    color: Color(0xFFF59E0B),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 6),
          SizedBox(
            height: 28,
            child: Align(
              alignment: Alignment.topLeft,
              child: Text(
                'XP ${_formatXp(xp)} / ${_formatXp(xpMax)}',
                style: TextStyle(fontSize: 11, color: c.textMuted, height: 1.3),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
          const SizedBox(height: 12),
          _StatProgressBar(
            progress: progress.clamp(0.0, 1.0),
            colors: const [AppColors.primary, AppColors.primaryLight],
          ),
        ],
      ),
    );
  }

  static String _formatXp(int n) {
    if (n >= 1000) return '${(n / 1000).toStringAsFixed(0)}k';
    return '$n';
  }
}

class _StatCard extends StatelessWidget {
  final Widget child;
  const _StatCard({required this.child});

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: c.card,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: c.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: child,
    );
  }
}

class _StatProgressBar extends StatelessWidget {
  final double progress;
  final List<Color> colors;

  const _StatProgressBar({
    required this.progress,
    required this.colors,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return ClipRRect(
      borderRadius: BorderRadius.circular(6),
      child: SizedBox(
        height: 8,
        child: Stack(
          children: [
            Container(color: c.cardBorder),
            FractionallySizedBox(
              widthFactor: progress,
              child: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: colors),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Today's Plan (horizontal: tasks + progress) ──────────────────────────────

class _TodaysPlanSection extends StatelessWidget {
  final List<HomePlanTask> tasks;
  final int totalTasks;
  final String coachNote;
  final int progress;
  final int completed;
  final int total;
  final void Function(HomePlanTask task) onTaskTap;
  final VoidCallback onViewAll;
  final VoidCallback onStartBattle;

  const _TodaysPlanSection({
    required this.tasks,
    required this.totalTasks,
    required this.coachNote,
    required this.progress,
    required this.completed,
    required this.total,
    required this.onTaskTap,
    required this.onViewAll,
    required this.onStartBattle,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final remaining = totalTasks - tasks.length;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: c.card,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: c.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                "Today's Plan",
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 17,
                  fontWeight: FontWeight.w800,
                  color: c.textPrimary,
                ),
              ),
              const Spacer(),
              GestureDetector(
                onTap: onViewAll,
                child: const Text(
                  'View all',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.primary,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(
                    color: AppColors.primary.withValues(alpha: 0.2),
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.sports_cricket_rounded,
                      size: 14,
                      color: AppColors.primary.withValues(alpha: 0.9),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      "Coach's Plan",
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: AppColors.primary,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  coachNote,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 12,
                    height: 1.35,
                    color: c.textSecondary,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(6),
                  child: LinearProgressIndicator(
                    value: progress / 100,
                    minHeight: 8,
                    backgroundColor: c.cardBorder,
                    color: AppColors.primary,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Text(
                '$progress%',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 13,
                  fontWeight: FontWeight.w800,
                  color: c.textPrimary,
                ),
              ),
              if (total > 0) ...[
                const SizedBox(width: 6),
                Text(
                  '$completed/$total',
                  style: TextStyle(fontSize: 11, color: c.textMuted),
                ),
              ],
            ],
          ),
          const SizedBox(height: 14),
          if (tasks.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text(
                'Your session plan is loading…',
                style: TextStyle(fontSize: 13, color: c.textMuted),
              ),
            )
          else
            ...tasks.map(
              (t) => _PlanTaskRow(
                task: t,
                onTap: () => onTaskTap(t),
                onStartBattle: onStartBattle,
                compact: true,
              ),
            ),
          if (remaining > 0) ...[
            const SizedBox(height: 4),
            GestureDetector(
              onTap: onViewAll,
              child: Text(
                '+$remaining more in full plan',
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: AppColors.primary,
                ),
              ),
            ),
          ],
          const SizedBox(height: 10),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.06),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
            ),
            child: Row(
              children: [
                Icon(
                  Icons.emoji_events_outlined,
                  size: 16,
                  color: AppColors.primary.withValues(alpha: 0.85),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Next milestone: complete 3 tasks · +50 XP',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: c.textSecondary,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    ).animate().fadeIn(delay: 100.ms, duration: 450.ms);
  }
}

class _PlanTaskRow extends StatelessWidget {
  final HomePlanTask task;
  final VoidCallback onTap;
  final VoidCallback onStartBattle;
  final bool compact;

  const _PlanTaskRow({
    required this.task,
    required this.onTap,
    required this.onStartBattle,
    this.compact = false,
  });

  Color get _color {
    if (task.action == 'battle') return const Color(0xFFEF4444);
    final s = task.subject.toLowerCase();
    final t = task.title.toLowerCase();
    if (s.contains('chem') || t.contains('chemical')) return const Color(0xFF22C55E);
    if (s.contains('math') || t.contains('question')) return const Color(0xFF3B82F6);
    if (t.contains('weak')) return const Color(0xFFEF4444);
    if (s.contains('quiz')) return const Color(0xFF3B82F6);
    if (s.contains('revision')) return const Color(0xFFF59E0B);
    return const Color(0xFF14B8A6);
  }

  IconData get _icon {
    if (task.action == 'battle') return LucideIcons.swords;
    final s = task.subject.toLowerCase();
    final t = task.title.toLowerCase();
    if (s.contains('chem') || t.contains('chemical')) return LucideIcons.flaskConical;
    if (s.contains('math') || t.contains('question')) return LucideIcons.circleHelp;
    if (t.contains('weak')) return LucideIcons.triangleAlert;
    if (s.contains('quiz')) return LucideIcons.circleHelp;
    if (s.contains('revision')) return LucideIcons.bookMarked;
    return LucideIcons.atom;
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final color = _color;
    final iconSize = compact ? 16.0 : 18.0;
    final boxSize = compact ? 32.0 : 36.0;
    return Padding(
      padding: EdgeInsets.only(bottom: compact ? 8 : 12),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(12),
          child: Padding(
            padding: EdgeInsets.symmetric(vertical: compact ? 1 : 2),
            child: Row(
              children: [
                Container(
                  width: boxSize,
                  height: boxSize,
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(compact ? 8 : 10),
                  ),
                  child: Icon(_icon, size: iconSize, color: color),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        task.title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: compact ? 12 : 13,
                          fontWeight: FontWeight.w600,
                          color: c.textPrimary,
                        ),
                      ),
                      Text(
                        task.subtitle,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: compact ? 10 : 11,
                          color: c.textMuted,
                        ),
                      ),
                    ],
                  ),
                ),
                if (task.action == 'battle' && !task.completed)
                  TextButton(
                    onPressed: onStartBattle,
                    style: TextButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: EdgeInsets.symmetric(
                        horizontal: compact ? 10 : 14,
                        vertical: compact ? 4 : 6,
                      ),
                      minimumSize: Size.zero,
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    child: Text(
                      'Start',
                      style: TextStyle(
                        fontSize: compact ? 11 : 12,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  )
                else if (task.completed)
                  Icon(
                    Icons.check_circle_rounded,
                    size: compact ? 18 : 20,
                    color: const Color(0xFF22C55E),
                  )
                else
                  Icon(
                    Icons.close_rounded,
                    size: compact ? 16 : 18,
                    color: c.textMuted.withValues(alpha: 0.5),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ─── Quick actions ────────────────────────────────────────────────────────────

class _QuickActionsRow extends StatelessWidget {
  final VoidCallback onScan;
  final VoidCallback onAiTutor;
  final VoidCallback onQuiz;
  final VoidCallback onBattles;
  final VoidCallback onRevision;

  const _QuickActionsRow({
    required this.onScan,
    required this.onAiTutor,
    required this.onQuiz,
    required this.onBattles,
    required this.onRevision,
  });

  @override
  Widget build(BuildContext context) {
    final items = [
      ('Scan & Solve', LucideIcons.scanLine, const Color(0xFF3B82F6), onScan),
      ('AI Tutor', LucideIcons.bot, AppColors.primary, onAiTutor),
      ('Mock Test', LucideIcons.circleHelp, const Color(0xFFF59E0B), onQuiz),
      ('Battles', LucideIcons.swords, const Color(0xFFEF4444), onBattles),
      ('Revision', LucideIcons.bookMarked, const Color(0xFF22C55E), onRevision),
    ];

    return Row(
      children: items.map((item) {
        return Expanded(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 3),
            child: _QuickActionTile(
              label: item.$1,
              icon: item.$2,
              color: item.$3,
              onTap: item.$4,
            ),
          ),
        );
      }).toList(),
    );
  }
}

class _QuickActionTile extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _QuickActionTile({
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            color: c.card,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: c.cardBorder),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.03),
                blurRadius: 8,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, size: 22, color: color),
              ),
              const SizedBox(height: 8),
              Text(
                label,
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                  color: c.textSecondary,
                  height: 1.2,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ─── Continue learning ────────────────────────────────────────────────────────

class _SectionHeader extends StatelessWidget {
  final String title;
  final String action;
  final VoidCallback onAction;

  const _SectionHeader({
    required this.title,
    required this.action,
    required this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Row(
      children: [
        Text(
          title,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 17,
            fontWeight: FontWeight.w800,
            color: c.textPrimary,
          ),
        ),
        const Spacer(),
        GestureDetector(
          onTap: onAction,
          child: Text(
            action,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: AppColors.primary,
            ),
          ),
        ),
      ],
    );
  }
}

class _ContinueLearningCard extends StatelessWidget {
  final String exam;
  final String subject;
  final String topic;
  final int topicsDone;
  final int topicsTotal;
  final VoidCallback onContinue;

  const _ContinueLearningCard({
    required this.exam,
    required this.subject,
    required this.topic,
    required this.topicsDone,
    required this.topicsTotal,
    required this.onContinue,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final done = topicsTotal > 0 ? topicsDone : 12;
    final total = topicsTotal > 0 ? topicsTotal : 25;
    final progress = (done / total).clamp(0.0, 1.0);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: c.card,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: c.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: context.isDark ? 0.18 : 0.05),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              LucideIcons.atom,
              color: AppColors.primary,
              size: 22,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        exam,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: GoogleFonts.plusJakartaSans(
                          fontWeight: FontWeight.w800,
                          fontSize: 15,
                          color: c.textPrimary,
                        ),
                      ),
                    ),
                    Icon(LucideIcons.chevronRight, size: 16, color: c.textMuted),
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  '$subject • $topic',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(fontSize: 12, color: c.textMuted),
                ),
                const SizedBox(height: 10),
                LayoutBuilder(
                  builder: (context, constraints) {
                    final barWidth = constraints.maxWidth;
                    final thumbLeft =
                        (barWidth * progress).clamp(0.0, barWidth - 10);
                    return SizedBox(
                      height: 10,
                      child: Stack(
                        clipBehavior: Clip.none,
                        children: [
                          Container(
                            height: 6,
                            margin: const EdgeInsets.only(top: 2),
                            decoration: BoxDecoration(
                              color: c.cardBorder,
                              borderRadius: BorderRadius.circular(4),
                            ),
                          ),
                          FractionallySizedBox(
                            widthFactor: progress,
                            child: Container(
                              height: 6,
                              margin: const EdgeInsets.only(top: 2),
                              decoration: BoxDecoration(
                                color: AppColors.primary,
                                borderRadius: BorderRadius.circular(4),
                              ),
                            ),
                          ),
                          Positioned(
                            left: thumbLeft,
                            top: 0,
                            child: Container(
                              width: 10,
                              height: 10,
                              decoration: BoxDecoration(
                                color: const Color(0xFFFBBF24),
                                shape: BoxShape.circle,
                                border: Border.all(color: Colors.white, width: 1.5),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.12),
                                    blurRadius: 2,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
                const SizedBox(height: 6),
                Text(
                  '$done / $total Topics',
                  style: TextStyle(fontSize: 11, color: c.textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          SizedBox(
            height: 40,
            child: ElevatedButton(
              onPressed: onContinue,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                elevation: 0,
              ),
              child: Text(
                'Continue',
                style: GoogleFonts.plusJakartaSans(
                  fontWeight: FontWeight.w700,
                  fontSize: 13,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Daily challenge ──────────────────────────────────────────────────────────

class _DailyChallengeCard extends StatelessWidget {
  final HomeDailyChallengeInfo info;
  final VoidCallback onTap;

  const _DailyChallengeCard({
    required this.info,
    required this.onTap,
  });

  static const _avatarUrls = [
    'https://randomuser.me/api/portraits/women/44.jpg',
    'https://randomuser.me/api/portraits/men/32.jpg',
    'https://randomuser.me/api/portraits/women/68.jpg',
  ];

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final joinedCount = info.participants > 0 ? info.participants : 120;
    final descLines = info.description.split('\n');
    final line1 = descLines.isNotEmpty
        ? descLines.first
        : 'Solve 10 questions in 10 mins';
    final line2 = descLines.length > 1
        ? descLines[1]
        : 'Win ${info.rewardXp} XP & climb the leaderboard';

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(24),
        child: Ink(
          height: 132,
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: context.isDark
                  ? [
                      const Color(0xFF2A2450),
                      const Color(0xFF1E1B38),
                    ]
                  : [
                      const Color(0xFFF3EEFF),
                      const Color(0xFFEAE2FF),
                    ],
            ),
            borderRadius: BorderRadius.circular(24),
            border: Border.all(
              color: AppColors.primary.withValues(alpha: 0.12),
            ),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.12),
                blurRadius: 18,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(18, 16, 118, 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      info.title,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: AppColors.primary,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      line1,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                        color: c.textSecondary,
                        height: 1.3,
                      ),
                    ),
                    Text(
                      line2,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                        color: c.textSecondary,
                        height: 1.3,
                      ),
                    ),
                    const Spacer(),
                    Row(
                      children: [
                        _ChallengeAvatarStack(urls: _avatarUrls),
                        const SizedBox(width: 8),
                        Text(
                          '+$joinedCount joined',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: c.textMuted,
                          ),
                        ),
                        const Spacer(),
                        Icon(
                          Icons.arrow_forward_rounded,
                          size: 18,
                          color: AppColors.primary.withValues(alpha: 0.85),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              Positioned(
                right: 0,
                top: 0,
                bottom: 0,
                child: ClipRRect(
                  borderRadius: const BorderRadius.horizontal(
                    right: Radius.circular(24),
                  ),
                  child: SizedBox(
                    width: 118,
                    child: OverflowBox(
                      alignment: Alignment.centerRight,
                      maxWidth: 260,
                      child: Image.asset(
                        'assets/images/cup.png',
                        height: 132,
                        fit: BoxFit.cover,
                        alignment: Alignment.centerRight,
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ChallengeAvatarStack extends StatelessWidget {
  const _ChallengeAvatarStack({required this.urls});

  final List<String> urls;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 58,
      height: 26,
      child: Stack(
        clipBehavior: Clip.none,
        children: List.generate(urls.length, (i) {
          return Positioned(
            left: i * 17.0,
            child: Container(
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: Colors.white, width: 2),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.08),
                    blurRadius: 4,
                    offset: const Offset(0, 1),
                  ),
                ],
              ),
              child: CircleAvatar(
                radius: 11,
                backgroundColor: const Color(0xFFE8EBF4),
                backgroundImage: CachedNetworkImageProvider(urls[i]),
                onBackgroundImageError: (_, __) {},
                child: urls[i].isEmpty
                    ? const Icon(Icons.person, size: 12, color: Colors.white)
                    : null,
              ),
            ),
          );
        }),
      ),
    );
  }
}

// ─── Upcoming exam ────────────────────────────────────────────────────────────

class _UpcomingExamSection extends StatefulWidget {
  final String examName;
  final int daysLeft;
  final String examDate;
  final VoidCallback onTap;

  const _UpcomingExamSection({
    required this.examName,
    required this.daysLeft,
    required this.examDate,
    required this.onTap,
  });

  @override
  State<_UpcomingExamSection> createState() => _UpcomingExamSectionState();
}

class _UpcomingExamSectionState extends State<_UpcomingExamSection> {
  bool _dismissed = false;

  @override
  Widget build(BuildContext context) {
    if (_dismissed) return const SizedBox.shrink();

    final c = context.dash;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Upcoming Exam',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 17,
            fontWeight: FontWeight.w800,
            color: c.textPrimary,
          ),
        ),
        const SizedBox(height: 12),
        Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: widget.onTap,
            borderRadius: BorderRadius.circular(18),
            child: Ink(
              padding: const EdgeInsets.fromLTRB(16, 16, 14, 16),
              decoration: BoxDecoration(
                color: c.card,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: c.cardBorder),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(
                      alpha: context.isDark ? 0.18 : 0.05,
                    ),
                    blurRadius: 14,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Stack(
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              widget.examName,
                              style: GoogleFonts.plusJakartaSans(
                                fontWeight: FontWeight.w800,
                                fontSize: 16,
                                color: c.textPrimary,
                              ),
                            ),
                            const SizedBox(height: 10),
                            Row(
                              children: [
                                Container(
                                  width: 22,
                                  height: 22,
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFFBBF24)
                                        .withValues(alpha: 0.2),
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  child: const Icon(
                                    LucideIcons.trophy,
                                    size: 13,
                                    color: Color(0xFFF59E0B),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Text(
                                  '${widget.daysLeft} Days Left',
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                    color: c.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(
                              widget.examDate,
                              style: TextStyle(
                                fontSize: 12,
                                color: c.textMuted,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      _ExamCalendarGraphic(),
                    ],
                  ),
                  Positioned(
                    top: 0,
                    right: 0,
                    child: GestureDetector(
                      onTap: () => setState(() => _dismissed = true),
                      child: Container(
                        width: 26,
                        height: 26,
                        alignment: Alignment.center,
                        child: Icon(
                          Icons.close,
                          size: 18,
                          color: c.textMuted,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _ExamCalendarGraphic extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 72,
      height: 72,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(16),
            ),
          ),
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFF7B61FF), AppColors.primary],
              ),
              borderRadius: BorderRadius.circular(14),
              boxShadow: [
                BoxShadow(
                  color: AppColors.primary.withValues(alpha: 0.25),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  width: 28,
                  height: 8,
                  margin: const EdgeInsets.only(bottom: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.35),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                ...List.generate(3, (i) {
                  return Padding(
                    padding: const EdgeInsets.symmetric(vertical: 1.5),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        _CalendarDot(active: i == 0),
                        const SizedBox(width: 4),
                        _CalendarDot(active: i == 1),
                        const SizedBox(width: 4),
                        _CalendarDot(active: false),
                      ],
                    ),
                  );
                }),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _CalendarDot extends StatelessWidget {
  const _CalendarDot({required this.active});

  final bool active;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: active ? 7 : 5,
      height: active ? 7 : 5,
      decoration: BoxDecoration(
        color: active ? Colors.white : Colors.white.withValues(alpha: 0.45),
        shape: BoxShape.circle,
      ),
    );
  }
}
