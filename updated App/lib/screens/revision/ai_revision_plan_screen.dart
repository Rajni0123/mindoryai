import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../models/models.dart';
import '../../providers/revision_provider.dart';

class AiRevisionPlanScreen extends ConsumerStatefulWidget {
  const AiRevisionPlanScreen({super.key});

  @override
  ConsumerState<AiRevisionPlanScreen> createState() =>
      _AiRevisionPlanScreenState();
}

class _AiRevisionPlanScreenState extends ConsumerState<AiRevisionPlanScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(revisionProvider.notifier).loadPlan());
  }

  void _onDayTap(RevisionPlanDay day) {
    if (day.action == 'mock_test') {
      AppRouter.go(context, AppRoutes.mockTest);
      return;
    }
    AppRouter.go(context, AppRoutes.quiz, args: {
      'topic': day.topic,
      'subject': day.subject,
      'examType': 'JEE',
      'language': 'english',
    });
  }

  @override
  Widget build(BuildContext context) {
    final t = _ArpTheme.of(context);
    final revision = ref.watch(revisionProvider);
    final plan = revision.plan;
    final days = plan?.days ?? [];

    return Scaffold(
      backgroundColor: t.bg,
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _Header(t: t, onRefresh: () {
              ref.read(revisionProvider.notifier).loadPlan();
            }),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 4, 20, 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    plan?.personalized == true
                        ? 'Made for you — based on your weak spots'
                        : 'Your starter path — complete a quiz to personalize',
                    style: GoogleFonts.inter(fontSize: 14, color: t.muted),
                  ),
                  if (plan != null) ...[
                    const SizedBox(height: 6),
                    Text(
                      'Accuracy ${plan.userAccuracy.toStringAsFixed(0)}% · '
                      '${plan.weakCount} weak areas · '
                      '${plan.userStreak} day streak',
                      style: GoogleFonts.inter(fontSize: 12, color: t.muted),
                    ),
                  ],
                ],
              ),
            ).animate().fadeIn(duration: 350.ms),
            if (revision.loading && days.isEmpty)
              const Expanded(
                child: Center(child: CircularProgressIndicator()),
              )
            else if (days.isEmpty)
              Expanded(
                child: Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(
                      revision.error ?? 'No plan available. Take a quiz first.',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: t.muted),
                    ),
                  ),
                ),
              )
            else
              Expanded(
                child: RefreshIndicator(
                  onRefresh: () => ref.read(revisionProvider.notifier).loadPlan(),
                  child: ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(
                      parent: BouncingScrollPhysics(),
                    ),
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
                    itemCount: days.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 4),
                    itemBuilder: (context, i) {
                      final day = days[i];
                      return _DayRow(
                        day: day,
                        isFirst: i == 0,
                        isLast: i == days.length - 1,
                        prevCompleted: i > 0 ? days[i - 1].completed : false,
                        t: t,
                        onTap: () => _onDayTap(day),
                      )
                          .animate(delay: (60 * i).ms)
                          .fadeIn(duration: 400.ms)
                          .slideX(begin: -0.04, end: 0);
                    },
                  ),
                ),
              ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
              child: _PlanFooter(t: t, personalized: plan?.personalized ?? false)
                  .animate(delay: 320.ms)
                  .fadeIn(duration: 450.ms)
                  .slideY(begin: 0.06, end: 0),
            ),
          ],
        ),
      ),
    );
  }
}

class _ArpTheme {
  final Color bg;
  final Color card;
  final Color cardBorder;
  final Color text;
  final Color muted;
  final Color line;
  final Color success;
  final bool isDark;

  const _ArpTheme({
    required this.bg,
    required this.card,
    required this.cardBorder,
    required this.text,
    required this.muted,
    required this.line,
    required this.success,
    required this.isDark,
  });

  factory _ArpTheme.of(BuildContext context) {
    final c = context.dash;
    final dark = context.isDark;
    return _ArpTheme(
      bg: dark ? const Color(0xFF0A0E14) : c.background,
      card: dark ? const Color(0xFF1A1D24) : c.card,
      cardBorder: dark ? const Color(0xFF252A34) : c.cardBorder,
      text: c.textPrimary,
      muted: c.textMuted,
      line: dark ? const Color(0xFF2A3040) : const Color(0xFFD1D5DB),
      success: const Color(0xFF22C55E),
      isDark: dark,
    );
  }
}

class _Header extends StatelessWidget {
  final _ArpTheme t;
  final VoidCallback onRefresh;

  const _Header({required this.t, required this.onRefresh});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 10, 12, 4),
      child: Row(
        children: [
          Material(
            color: Colors.transparent,
            child: InkWell(
              onTap: () => Navigator.pop(context),
              borderRadius: BorderRadius.circular(12),
              child: SizedBox(
                width: 44,
                height: 44,
                child: Icon(LucideIcons.arrowLeft, size: 22, color: t.text),
              ),
            ),
          ),
          Expanded(
            child: Text(
              'Your Comeback Plan',
              textAlign: TextAlign.center,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: t.text,
              ),
            ),
          ),
          IconButton(
            icon: Icon(LucideIcons.refreshCw, size: 20, color: t.muted),
            onPressed: onRefresh,
          ),
        ],
      ),
    );
  }
}

class _DayRow extends StatelessWidget {
  final RevisionPlanDay day;
  final bool isFirst;
  final bool isLast;
  final bool prevCompleted;
  final _ArpTheme t;
  final VoidCallback onTap;

  const _DayRow({
    required this.day,
    required this.isFirst,
    required this.isLast,
    required this.prevCompleted,
    required this.t,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          SizedBox(
            width: 36,
            child: Column(
              children: [
                if (!isFirst)
                  Expanded(
                    child: Center(
                      child: Container(
                        width: 2,
                        color: prevCompleted ? t.success : t.line,
                      ),
                    ),
                  ),
                _TimelineNode(completed: day.completed, t: t),
                if (!isLast)
                  Expanded(
                    child: Center(
                      child: Container(
                        width: 2,
                        color: day.completed ? t.success : t.line,
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 14),
          Expanded(child: _DayCard(day: day, t: t, onTap: onTap)),
        ],
      ),
    );
  }
}

class _TimelineNode extends StatelessWidget {
  final bool completed;
  final _ArpTheme t;

  const _TimelineNode({required this.completed, required this.t});

  @override
  Widget build(BuildContext context) {
    if (completed) {
      return Container(
        width: 22,
        height: 22,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: t.success,
          boxShadow: [
            BoxShadow(
              color: t.success.withValues(alpha: 0.55),
              blurRadius: 12,
              spreadRadius: 2,
            ),
          ],
        ),
        child: Center(
          child: Container(
            width: 8,
            height: 8,
            decoration: const BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white,
            ),
          ),
        ),
      );
    }

    return Container(
      width: 22,
      height: 22,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(color: t.line, width: 2),
        color: t.card,
      ),
    );
  }
}

class _DayCard extends StatelessWidget {
  final RevisionPlanDay day;
  final _ArpTheme t;
  final VoidCallback onTap;

  const _DayCard({required this.day, required this.t, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final completed = day.completed;
    final subtitle = day.successRate != null
        ? '${day.subject} · ${day.successRate!.toStringAsFixed(0)}% accuracy'
        : day.subject;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 18),
          decoration: BoxDecoration(
            color: t.card,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: completed ? t.success.withValues(alpha: 0.45) : t.cardBorder,
            ),
            boxShadow: completed
                ? [
                    BoxShadow(
                      color: t.success.withValues(alpha: 0.12),
                      blurRadius: 16,
                    ),
                  ]
                : null,
          ),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Day ${day.day}',
                      style: GoogleFonts.inter(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: completed ? t.success : t.muted,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      day.topic,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: t.text,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(subtitle, style: GoogleFonts.inter(fontSize: 12, color: t.muted)),
                  ],
                ),
              ),
              if (completed)
                Container(
                  width: 28,
                  height: 28,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: t.success.withValues(alpha: 0.15),
                  ),
                  child: Icon(LucideIcons.check, size: 16, color: t.success),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _PlanFooter extends StatelessWidget {
  final _ArpTheme t;
  final bool personalized;

  const _PlanFooter({required this.t, required this.personalized});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: t.card,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: t.cardBorder),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: const Color(0xFF705CF6).withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.flag_rounded,
              color: Color(0xFF705CF6),
              size: 22,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              personalized
                  ? 'This plan updates as you practice — focus on one day at a time and your scores will climb.'
                  : 'Finish a few quizzes first. We\'ll turn your mistakes into a clear day-by-day revision path.',
              style: GoogleFonts.inter(
                fontSize: 13,
                height: 1.45,
                color: t.text.withValues(alpha: 0.9),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
