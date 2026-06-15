import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
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

    return Scaffold(
      backgroundColor: context.dash.background,
      body: SafeArea(
        child: Column(
          children: [
            _TopBar(
              name: name,
              onNotifications: () => AppRouter.go(context, AppRoutes.notifications),
              onProfile: () => ref.read(navIndexProvider.notifier).state = 4,
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
                      Row(
                        children: [
                          Expanded(
                            child: _StreakCard(streak: streak),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: _LevelCard(level: level, xp: xp, xpMax: xpMax),
                          ),
                        ],
                      ),
                      const SizedBox(height: 20),
                      _TodaysPlanSection(
                        tasks: dash.todayPlan,
                        progress: dash.dailyProgress,
                        completed: dash.planCompleted,
                        total: dash.planTotal,
                        onTaskTap: (task) => _onPlanTaskTap(context, task),
                        onAnalytics: () =>
                            ref.read(navIndexProvider.notifier).state = 1,
                      ),
                      const SizedBox(height: 22),
                      _QuickActionsRow(
                        onScan: () => ref.read(navIndexProvider.notifier).state = 2,
                        onAiTutor: () => AppRouter.go(context, AppRoutes.aiTutor),
                        onQuiz: () => AppRouter.go(context, AppRoutes.quizTopics),
                        onBattles: () => ref.read(navIndexProvider.notifier).state = 3,
                        onRevision: () => AppRouter.go(context, AppRoutes.revision),
                      ),
                      const SizedBox(height: 26),
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
                      const SizedBox(height: 22),
                      _DailyChallengeCard(
                        info: dailyChallenge,
                        onTap: () => AppRouter.go(context, AppRoutes.dailyChallenge),
                      ),
                      const SizedBox(height: 18),
                      if (upcoming.daysLeft != null || upcoming.examDate != null)
                        _UpcomingExamCard(
                          examName: upcoming.examName,
                          daysLeft: upcoming.daysLeft ?? 0,
                          examDate: upcoming.examDate ?? 'Set your exam date',
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

  static void _onPlanTaskTap(BuildContext context, HomePlanTask task) {
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
  const _StreakCard({required this.streak});

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final progress = (streak / 60).clamp(0.0, 1.0);
    return _StatCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.local_fire_department_rounded,
                  color: Color(0xFFF97316), size: 22),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  '$streak Day Streak',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: c.textPrimary,
                  ),
                ),
              ),
              _HexBadge(value: streak),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            streak > 0 ? "You're on fire! Keep it up." : 'Start your streak today!',
            style: TextStyle(fontSize: 11, color: c.textMuted, height: 1.3),
          ),
          const SizedBox(height: 12),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: SizedBox(
              height: 8,
              child: Stack(
                children: [
                  Container(color: c.cardBorder),
                  FractionallySizedBox(
                    widthFactor: progress,
                    child: Container(
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          colors: [Color(0xFFF97316), Color(0xFF3B82F6)],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
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

  const _LevelCard({required this.level, required this.xp, required this.xpMax});

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final progress = xpMax > 0 ? (xp / xpMax).clamp(0.0, 1.0) : 0.0;
    return _StatCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                'Level $level',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  color: c.textPrimary,
                ),
              ),
              const Spacer(),
              Icon(LucideIcons.chevronRight, size: 16, color: c.textMuted),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            'XP ${_formatXp(xp)} / ${_formatXp(xpMax)}',
            style: TextStyle(fontSize: 11, color: c.textMuted),
          ),
          const SizedBox(height: 12),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: SizedBox(
              height: 8,
              child: Stack(
                children: [
                  Container(color: c.cardBorder),
                  FractionallySizedBox(
                    widthFactor: progress,
                    child: Container(color: AppColors.primary),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  static String _formatXp(int n) {
    if (n >= 1000) return '${(n / 1000).toStringAsFixed(1)}k'.replaceAll('.0k', 'k');
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

class _HexBadge extends StatelessWidget {
  final int value;
  const _HexBadge({required this.value});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 32,
      height: 32,
      decoration: BoxDecoration(
        color: const Color(0xFFFBBF24).withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFFBBF24), width: 1.5),
      ),
      child: Center(
        child: Text(
          '$value',
          style: const TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w800,
            color: Color(0xFFD97706),
          ),
        ),
      ),
    );
  }
}

// ─── Today's Plan (horizontal: tasks + progress) ──────────────────────────────

class _TodaysPlanSection extends StatelessWidget {
  final List<HomePlanTask> tasks;
  final int progress;
  final int completed;
  final int total;
  final void Function(HomePlanTask task) onTaskTap;
  final VoidCallback onAnalytics;

  const _TodaysPlanSection({
    required this.tasks,
    required this.progress,
    required this.completed,
    required this.total,
    required this.onTaskTap,
    required this.onAnalytics,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Container(
      padding: const EdgeInsets.all(18),
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
              const SizedBox(width: 8),
              const Icon(Icons.auto_awesome, size: 16, color: AppColors.primary),
              const SizedBox(width: 4),
              Text(
                'AI Generated',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: AppColors.primary.withValues(alpha: 0.8),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: tasks.isEmpty
                    ? Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Text(
                          'Your personalized plan is loading…',
                          style: TextStyle(fontSize: 13, color: c.textMuted),
                        ),
                      )
                    : Column(
                        children: tasks
                            .map((t) => _PlanTaskRow(
                                  task: t,
                                  onTap: () => onTaskTap(t),
                                ))
                            .toList(),
                      ),
              ),
              const SizedBox(width: 12),
              _DailyProgressPanel(
                progress: progress,
                completed: completed,
                total: total,
                onAnalytics: onAnalytics,
              ),
            ],
          ),
        ],
      ),
    ).animate().fadeIn(delay: 100.ms, duration: 450.ms);
  }
}

class _DailyProgressPanel extends StatelessWidget {
  final int progress;
  final int completed;
  final int total;
  final VoidCallback onAnalytics;

  const _DailyProgressPanel({
    required this.progress,
    required this.completed,
    required this.total,
    required this.onAnalytics,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return SizedBox(
      width: 100,
      child: Column(
        children: [
          SizedBox(
            width: 88,
            height: 88,
            child: Stack(
              alignment: Alignment.center,
              children: [
                SizedBox(
                  width: 88,
                  height: 88,
                  child: CircularProgressIndicator(
                    value: progress / 100,
                    strokeWidth: 8,
                    backgroundColor: c.cardBorder,
                    color: AppColors.primary,
                    strokeCap: StrokeCap.round,
                  ),
                ),
                Text(
                  '$progress%',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: c.textPrimary,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Daily Progress',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 11, color: c.textMuted),
          ),
          Text(
            progress >= 70 ? 'Great Progress!' : 'Keep going!',
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: AppColors.primary,
            ),
          ),
          if (total > 0) ...[
            const SizedBox(height: 4),
            Text(
              '$completed/$total',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 10, color: c.textMuted),
            ),
          ],
          const SizedBox(height: 6),
          TextButton(
            onPressed: onAnalytics,
            style: TextButton.styleFrom(
              padding: EdgeInsets.zero,
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
            ),
            child: const Text(
              'View Analytics',
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    );
  }
}

class _PlanTaskRow extends StatelessWidget {
  final HomePlanTask task;
  final VoidCallback onTap;

  const _PlanTaskRow({required this.task, required this.onTap});

  Color get _color {
    final s = task.subject.toLowerCase();
    if (s.contains('chem')) return const Color(0xFFEF4444);
    if (s.contains('math')) return const Color(0xFF8B5CF6);
    if (s.contains('bio')) return const Color(0xFF22C55E);
    if (s.contains('quiz')) return const Color(0xFF3B82F6);
    if (s.contains('revision')) return const Color(0xFFF59E0B);
    return const Color(0xFF14B8A6);
  }

  IconData get _icon {
    final s = task.subject.toLowerCase();
    if (s.contains('chem')) return LucideIcons.flaskConical;
    if (s.contains('math')) return LucideIcons.calculator;
    if (s.contains('bio')) return LucideIcons.leaf;
    if (s.contains('quiz')) return LucideIcons.circleHelp;
    if (s.contains('revision')) return LucideIcons.bookMarked;
    return LucideIcons.atom;
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final color = _color;
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(12),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 2),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(_icon, size: 18, color: color),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${task.subject}: ${task.title}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: c.textPrimary,
                        ),
                      ),
                      Text(task.subtitle,
                          style: TextStyle(fontSize: 11, color: c.textMuted)),
                    ],
                  ),
                ),
                if (task.completed)
                  const Icon(Icons.check_circle_rounded,
                      size: 20, color: Color(0xFF22C55E))
                else
                  Icon(LucideIcons.chevronRight, size: 16, color: c.textMuted),
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
      ('Quiz', LucideIcons.circleHelp, const Color(0xFFF59E0B), onQuiz),
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
    final progress = topicsTotal > 0 ? topicsDone / topicsTotal : 0.0;

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: c.card,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: c.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(LucideIcons.atom, color: AppColors.primary, size: 24),
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
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: 16,
                              color: c.textPrimary,
                            ),
                          ),
                        ),
                        Icon(LucideIcons.chevronRight, size: 18, color: c.textMuted),
                      ],
                    ),
                    Text(
                      '$subject • $topic',
                      style: TextStyle(fontSize: 13, color: c.textMuted),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: SizedBox(
              height: 10,
              child: Stack(
                children: [
                  Container(color: c.cardBorder),
                  FractionallySizedBox(
                    widthFactor: progress.clamp(0.0, 1.0),
                    child: Container(
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          colors: [Color(0xFF22C55E), AppColors.primary],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            '$topicsDone / $topicsTotal Topics',
            style: TextStyle(fontSize: 12, color: c.textMuted),
          ),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: onContinue,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
                elevation: 0,
              ),
              child: const Text('Continue', style: TextStyle(fontWeight: FontWeight.w700)),
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

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final joinedLabel = info.participants > 0
        ? '+${info.participants} joined'
        : 'Be the first to join';

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Ink(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [
                AppColors.primary.withValues(alpha: 0.08),
                c.card,
              ],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: AppColors.primary.withValues(alpha: 0.2)),
          ),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            info.title,
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 17,
                              fontWeight: FontWeight.w800,
                              color: AppColors.primary,
                            ),
                          ),
                        ),
                        if (info.completed)
                          const Icon(Icons.check_circle_rounded,
                              size: 18, color: Color(0xFF22C55E)),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      info.description,
                      style: TextStyle(
                        fontSize: 13,
                        color: c.textSecondary,
                        height: 1.4,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        _AvatarStack(),
                        const SizedBox(width: 8),
                        Text(joinedLabel,
                            style: TextStyle(fontSize: 12, color: c.textMuted)),
                        const SizedBox(width: 6),
                        const Icon(Icons.arrow_forward_rounded,
                            size: 16, color: AppColors.primary),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  color: const Color(0xFFFBBF24).withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(Icons.emoji_events_rounded,
                    color: Color(0xFFF59E0B), size: 36),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AvatarStack extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 56,
      height: 24,
      child: Stack(
        children: List.generate(3, (i) {
          return Positioned(
            left: i * 16.0,
            child: CircleAvatar(
              radius: 12,
              backgroundColor: [
                AppColors.primary,
                const Color(0xFF22C55E),
                const Color(0xFFF59E0B),
              ][i],
              child: Text(
                '${i + 1}',
                style: const TextStyle(fontSize: 9, color: Colors.white),
              ),
            ),
          );
        }),
      ),
    );
  }
}

// ─── Upcoming exam ────────────────────────────────────────────────────────────

class _UpcomingExamCard extends StatelessWidget {
  final String examName;
  final int daysLeft;
  final String examDate;
  final VoidCallback onTap;

  const _UpcomingExamCard({
    required this.examName,
    required this.daysLeft,
    required this.examDate,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
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
            onTap: onTap,
            borderRadius: BorderRadius.circular(20),
            child: Ink(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: c.card,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: c.cardBorder),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          examName,
                          style: TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 16,
                            color: c.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 8),
                        RichText(
                          text: TextSpan(
                            style: TextStyle(fontSize: 14, color: c.textSecondary),
                            children: [
                              const TextSpan(text: ''),
                              TextSpan(
                                text: '$daysLeft',
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFFF97316),
                                  fontSize: 16,
                                ),
                              ),
                              const TextSpan(text: ' Days Left'),
                            ],
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(examDate, style: TextStyle(fontSize: 13, color: c.textMuted)),
                      ],
                    ),
                  ),
                  Container(
                    width: 56,
                    height: 56,
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: const Icon(Icons.hourglass_top_rounded,
                        color: AppColors.primary, size: 30),
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
