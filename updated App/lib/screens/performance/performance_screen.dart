import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/app_data_provider.dart';
import '../../providers/providers.dart';
import '../../widgets/common_widgets.dart';

class PerformanceScreen extends ConsumerStatefulWidget {
  const PerformanceScreen({super.key});

  @override
  ConsumerState<PerformanceScreen> createState() => _PerformanceScreenState();
}

class _PerformanceScreenState extends ConsumerState<PerformanceScreen> {
  Map<String, dynamic>? _stats;
  List<dynamic> _attempts = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final data = await ref.read(apiServiceProvider).getQuizStats(period: 'weekly');
      _stats = data['stats'] as Map<String, dynamic>?;
      _attempts = data['attempts'] as List? ?? [];
    } catch (_) {
      _stats = null;
      _attempts = [];
    }
    await ref.read(homeDashboardProvider.notifier).load();
    if (mounted) setState(() => _loading = false);
  }

  List<double> _weeklyCounts() {
    final counts = List<double>.filled(7, 0);
    for (final raw in _attempts) {
      final m = Map<String, dynamic>.from(raw as Map);
      final date = DateTime.tryParse(m['date']?.toString() ?? '');
      if (date == null) continue;
      counts[date.weekday - 1] += 1;
    }
    return counts;
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final dash = ref.watch(homeDashboardProvider);
    final weakTopics = dash.weakTopics;

    final avg = _stats?['averageScore'] as int? ?? dash.averageAccuracy;
    final quizzes = _stats?['totalQuizzes'] as int? ?? 0;
    final hours = ((_stats?['totalTimeSeconds'] as int? ?? 0) / 3600.0);
    final displayHours =
        hours > 0 ? hours : dash.studyHoursWeek;
    final streak = _stats?['dayStreak'] as int? ?? dash.streak;
    final readiness = dash.readinessScore > 0 ? dash.readinessScore : avg;
    final improvement = _stats?['improvement']?.toString() ?? '+0%';
    final weekly = _weeklyCounts();
    final chartMax = weekly.reduce((a, b) => a > b ? a : b);
    final maxY = chartMax < 1 ? 3.0 : chartMax + 1;

    return Scaffold(
      backgroundColor: c.background,
      body: SafeArea(
        child: Column(
          children: [
            _PerfHeader(
              onInsights: () =>
                  AppRouter.go(context, AppRoutes.weaknessAnalysis),
            ),
            Expanded(
              child: _loading
                  ? const Center(
                      child: CircularProgressIndicator(color: AppColors.primary),
                    )
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: SingleChildScrollView(
                        physics: const AlwaysScrollableScrollPhysics(
                          parent: BouncingScrollPhysics(),
                        ),
                        padding: const EdgeInsets.fromLTRB(20, 8, 20, 110),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _ReadinessHero(
                              score: readiness,
                              improvement: improvement,
                              level: dash.level,
                            ).animate().fadeIn(duration: 450.ms).slideY(begin: 0.04, end: 0),
                            const SizedBox(height: 18),
                            Row(
                              children: [
                                Expanded(
                                  child: _MetricTile(
                                    icon: LucideIcons.target,
                                    label: 'Accuracy',
                                    value: '$avg%',
                                    color: AppColors.primary,
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: _MetricTile(
                                    icon: LucideIcons.clipboardList,
                                    label: 'Quizzes',
                                    value: '$quizzes',
                                    color: AppColors.secondary,
                                  ),
                                ),
                              ],
                            ).animate(delay: 60.ms).fadeIn(duration: 400.ms),
                            const SizedBox(height: 10),
                            Row(
                              children: [
                                Expanded(
                                  child: _MetricTile(
                                    icon: LucideIcons.clock3,
                                    label: 'Study time',
                                    value: '${displayHours.toStringAsFixed(1)}h',
                                    color: const Color(0xFF22C55E),
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: _MetricTile(
                                    icon: LucideIcons.flame,
                                    label: 'Streak',
                                    value: '$streak days',
                                    color: AppColors.streakGold,
                                  ),
                                ),
                              ],
                            ).animate(delay: 100.ms).fadeIn(duration: 400.ms),
                            const SizedBox(height: 22),
                            Text(
                              'Weekly Activity',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 16,
                                fontWeight: FontWeight.w700,
                                color: c.textPrimary,
                              ),
                            ),
                            const SizedBox(height: 10),
                            AppCard(
                              padding: const EdgeInsets.fromLTRB(12, 16, 12, 12),
                              child: SizedBox(
                                height: 170,
                                child: BarChart(
                                  BarChartData(
                                    maxY: maxY,
                                    gridData: FlGridData(
                                      show: true,
                                      drawVerticalLine: false,
                                      horizontalInterval: maxY / 3,
                                      getDrawingHorizontalLine: (v) => FlLine(
                                        color: c.cardBorder,
                                        strokeWidth: 1,
                                      ),
                                    ),
                                    titlesData: FlTitlesData(
                                      topTitles: const AxisTitles(
                                        sideTitles: SideTitles(showTitles: false),
                                      ),
                                      rightTitles: const AxisTitles(
                                        sideTitles: SideTitles(showTitles: false),
                                      ),
                                      leftTitles: const AxisTitles(
                                        sideTitles: SideTitles(showTitles: false),
                                      ),
                                      bottomTitles: AxisTitles(
                                        sideTitles: SideTitles(
                                          showTitles: true,
                                          getTitlesWidget: (value, meta) {
                                            const days = [
                                              'M',
                                              'T',
                                              'W',
                                              'T',
                                              'F',
                                              'S',
                                              'S',
                                            ];
                                            final i = value.toInt();
                                            if (i < 0 || i > 6) {
                                              return const SizedBox.shrink();
                                            }
                                            return Padding(
                                              padding: const EdgeInsets.only(top: 6),
                                              child: Text(
                                                days[i],
                                                style: TextStyle(
                                                  fontSize: 11,
                                                  fontWeight: FontWeight.w600,
                                                  color: c.textMuted,
                                                ),
                                              ),
                                            );
                                          },
                                        ),
                                      ),
                                    ),
                                    borderData: FlBorderData(show: false),
                                    barGroups: List.generate(7, (i) {
                                      final v = weekly[i];
                                      final active = v > 0;
                                      return BarChartGroupData(
                                        x: i,
                                        barRods: [
                                          BarChartRodData(
                                            toY: v > 0 ? v : 0.15,
                                            color: active
                                                ? AppColors.primary
                                                : c.cardBorder,
                                            width: 18,
                                            borderRadius: const BorderRadius.vertical(
                                              top: Radius.circular(6),
                                            ),
                                          ),
                                        ],
                                      );
                                    }),
                                  ),
                                ),
                              ),
                            ).animate(delay: 140.ms).fadeIn(duration: 420.ms),
                            const SizedBox(height: 22),
                            SectionTitle(
                              title: 'Weak Topics',
                              action: 'View all',
                              onAction: () =>
                                  AppRouter.go(context, AppRoutes.weaknessAnalysis),
                            ),
                            const SizedBox(height: 8),
                            if (weakTopics.isEmpty)
                              AppCard(
                                child: Row(
                                  children: [
                                    Icon(LucideIcons.sparkles,
                                        size: 20, color: c.textMuted),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Text(
                                        'No weak topics yet — take more quizzes!',
                                        style: TextStyle(
                                          color: c.textMuted,
                                          fontSize: 13,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              )
                            else
                              ...weakTopics.asMap().entries.map((e) {
                                final w = e.value;
                                final pct = w.accuracy.clamp(0, 100);
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 10),
                                  child: _WeakTopicTile(
                                    topic: w.topic,
                                    subject: w.subject,
                                    accuracy: pct,
                                  ),
                                ).animate(delay: (180 + e.key * 40).ms)
                                    .fadeIn(duration: 350.ms);
                              }),
                            const SizedBox(height: 18),
                            Row(
                              children: [
                                Expanded(
                                  child: _ActionChip(
                                    icon: LucideIcons.bookOpen,
                                    label: 'Practice',
                                    onTap: () =>
                                        AppRouter.go(context, AppRoutes.quizTopics),
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: _ActionChip(
                                    icon: LucideIcons.brain,
                                    label: 'Revision',
                                    onTap: () =>
                                        AppRouter.go(context, AppRoutes.revision),
                                  ),
                                ),
                              ],
                            ).animate(delay: 220.ms).fadeIn(duration: 400.ms),
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
}

class _PerfHeader extends ConsumerWidget {
  const _PerfHeader({required this.onInsights});

  final VoidCallback onInsights;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final c = context.dash;
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 10, 12, 6),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Your Progress',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: c.textPrimary,
                    letterSpacing: -0.4,
                  ),
                ),
                Text(
                  'Track accuracy, streak & weak areas',
                  style: TextStyle(fontSize: 12, color: c.textMuted),
                ),
              ],
            ),
          ),
          const ThemeToggleButton(size: 20),
          const SizedBox(width: 4),
          IconButton(
            icon: Icon(LucideIcons.chartPie, color: c.textPrimary, size: 22),
            onPressed: onInsights,
            tooltip: 'Weakness analysis',
          ),
        ],
      ),
    );
  }
}

class _ReadinessHero extends StatelessWidget {
  const _ReadinessHero({
    required this.score,
    required this.improvement,
    required this.level,
  });

  final int score;
  final String improvement;
  final int level;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final positive = !improvement.startsWith('-');

    return AppCard(
      padding: const EdgeInsets.all(20),
      child: Row(
        children: [
          SizedBox(
            width: 92,
            height: 92,
            child: Stack(
              alignment: Alignment.center,
              children: [
                SizedBox(
                  width: 92,
                  height: 92,
                  child: CircularProgressIndicator(
                    value: (score / 100).clamp(0.0, 1.0),
                    strokeWidth: 8,
                    backgroundColor: c.cardBorder,
                    valueColor:
                        const AlwaysStoppedAnimation<Color>(AppColors.primary),
                    strokeCap: StrokeCap.round,
                  ),
                ),
                Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      '$score%',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                        color: c.textPrimary,
                      ),
                    ),
                    Text('Ready', style: TextStyle(fontSize: 10, color: c.textMuted)),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 18),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Exam Readiness',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 17,
                    fontWeight: FontWeight.w700,
                    color: c.textPrimary,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'Level $level · Keep practicing daily',
                  style: TextStyle(fontSize: 13, color: c.textSecondary),
                ),
                const SizedBox(height: 10),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: (positive ? AppColors.success : AppColors.error)
                        .withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    '$improvement vs last week',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: positive ? AppColors.success : AppColors.error,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _MetricTile extends StatelessWidget {
  const _MetricTile({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return AppCard(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, size: 20, color: color),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: c.textPrimary,
                  ),
                ),
                Text(label, style: TextStyle(fontSize: 11, color: c.textMuted)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _WeakTopicTile extends StatelessWidget {
  const _WeakTopicTile({
    required this.topic,
    required this.subject,
    required this.accuracy,
  });

  final String topic;
  final String subject;
  final int accuracy;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final weak = accuracy < 60;
    final barColor = weak ? AppColors.error : AppColors.success;

    return AppCard(
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      topic,
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                        color: c.textPrimary,
                      ),
                    ),
                    if (subject.isNotEmpty)
                      Text(
                        subject,
                        style: TextStyle(fontSize: 12, color: c.textMuted),
                      ),
                  ],
                ),
              ),
              Text(
                '$accuracy%',
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  color: barColor,
                  fontSize: 14,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: accuracy / 100,
              minHeight: 6,
              backgroundColor: c.cardBorder,
              valueColor: AlwaysStoppedAnimation(barColor),
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionChip extends StatelessWidget {
  const _ActionChip({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            color: c.card,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: c.cardBorder),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 18, color: AppColors.primary),
              const SizedBox(width: 8),
              Text(
                label,
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: c.textPrimary,
                  fontSize: 13,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
