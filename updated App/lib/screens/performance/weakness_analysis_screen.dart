import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../models/models.dart';
import '../../providers/revision_provider.dart';

class WeaknessAnalysisScreen extends ConsumerStatefulWidget {
  const WeaknessAnalysisScreen({super.key});

  @override
  ConsumerState<WeaknessAnalysisScreen> createState() =>
      _WeaknessAnalysisScreenState();
}

class _WeaknessAnalysisScreenState extends ConsumerState<WeaknessAnalysisScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(revisionProvider.notifier).loadProfile());
  }

  List<_WeakArea> _mapWeakAreas(List<WeakTopicItem> topics) {
    const palettes = [
      [Color(0xFF7B61FF), Color(0xFF4D9FFF)],
      [Color(0xFF7B61FF), Color(0xFF5B8CFF)],
      [Color(0xFF5B8CFF), Color(0xFF6EC8FF)],
      [Color(0xFF4DD4FF), Color(0xFF22D3EE)],
      [Color(0xFF4D9FFF), Color(0xFF8B5CF6)],
    ];

    return topics.asMap().entries.map((e) {
      final item = e.value;
      final progress = (item.successRate / 100).clamp(0.05, 1.0);
      final colors = palettes[e.key % palettes.length];
      final label = item.subject.isNotEmpty
          ? '${item.topic} (${item.subject})'
          : item.topic;
      return _WeakArea(label, progress, colors);
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final t = _WaTheme.of(context);
    final revision = ref.watch(revisionProvider);
    final weakAreas = _mapWeakAreas(revision.weakTopics);
    final strength = revision.strengthScore;

    return Scaffold(
      backgroundColor: t.bg,
      body: SafeArea(
        child: Column(
          children: [
            _Header(t: t),
            Expanded(
              child: revision.loading && weakAreas.isEmpty
                  ? const Center(child: CircularProgressIndicator())
                  : RefreshIndicator(
                      onRefresh: () =>
                          ref.read(revisionProvider.notifier).loadProfile(),
                      child: SingleChildScrollView(
                        physics: const AlwaysScrollableScrollPhysics(
                          parent: BouncingScrollPhysics(),
                        ),
                        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
                        child: Column(
                          children: [
                            _StrengthGauge(percent: strength, t: t)
                                .animate()
                                .fadeIn(duration: 500.ms)
                                .scale(
                                  begin: const Offset(0.92, 0.92),
                                  curve: Curves.easeOutBack,
                                ),
                            const SizedBox(height: 32),
                            if (weakAreas.isEmpty)
                              Text(
                                'No weak topics yet — take more quizzes!',
                                style: TextStyle(color: t.muted),
                              )
                            else
                              _WeakAreasCard(areas: weakAreas, t: t)
                                  .animate(delay: 100.ms)
                                  .fadeIn(duration: 450.ms)
                                  .slideY(begin: 0.05, end: 0),
                          ],
                        ),
                      ),
                    ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
              child: _AiRevisionButton(
                onTap: () => AppRouter.go(context, AppRoutes.revisionPlan),
              ).animate(delay: 180.ms).fadeIn(duration: 400.ms).slideY(begin: 0.08, end: 0),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Theme ────────────────────────────────────────────────────────────────────

class _WaTheme {
  final Color bg;
  final Color card;
  final Color cardBorder;
  final Color text;
  final Color muted;
  final Color track;
  final bool isDark;

  const _WaTheme({
    required this.bg,
    required this.card,
    required this.cardBorder,
    required this.text,
    required this.muted,
    required this.track,
    required this.isDark,
  });

  factory _WaTheme.of(BuildContext context) {
    final c = context.dash;
    final dark = context.isDark;
    return _WaTheme(
      bg: dark ? const Color(0xFF0A0C14) : c.background,
      card: dark ? const Color(0xFF141824) : c.card,
      cardBorder: dark ? const Color(0xFF232838) : c.cardBorder,
      text: c.textPrimary,
      muted: c.textMuted,
      track: dark ? const Color(0xFF1E2433) : c.cardBorder,
      isDark: dark,
    );
  }
}

class _WeakArea {
  final String name;
  final double progress;
  final List<Color> gradient;

  const _WeakArea(this.name, this.progress, this.gradient);
}

// ─── Header ───────────────────────────────────────────────────────────────────

class _Header extends StatelessWidget {
  final _WaTheme t;
  const _Header({required this.t});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 10, 12, 6),
      child: Row(
        children: [
          Material(
            color: Colors.transparent,
            child: InkWell(
              onTap: () => AppRouter.back(context),
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
              'Weakness Analysis',
              textAlign: TextAlign.center,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: t.text,
              ),
            ),
          ),
          const SizedBox(width: 44),
        ],
      ),
    );
  }
}

// ─── Strength gauge ───────────────────────────────────────────────────────────

class _StrengthGauge extends StatelessWidget {
  final int percent;
  final _WaTheme t;

  const _StrengthGauge({required this.percent, required this.t});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 220,
      height: 220,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Container(
            width: 220,
            height: 220,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF22C55E).withValues(alpha: t.isDark ? 0.15 : 0.1),
                  blurRadius: 30,
                  spreadRadius: 2,
                ),
                BoxShadow(
                  color: const Color(0xFFF59E0B).withValues(alpha: t.isDark ? 0.12 : 0.08),
                  blurRadius: 24,
                  spreadRadius: 0,
                ),
              ],
            ),
          ),
          CustomPaint(
            size: const Size(220, 220),
            painter: _GaugePainter(
              progress: percent / 100,
              trackColor: t.track,
            ),
          ),
          Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                '$percent%',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 44,
                  fontWeight: FontWeight.w800,
                  color: t.text,
                  letterSpacing: -1,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Overall Strength',
                style: GoogleFonts.inter(fontSize: 13, color: t.muted),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _GaugePainter extends CustomPainter {
  final double progress;
  final Color trackColor;

  _GaugePainter({required this.progress, required this.trackColor});

  @override
  void paint(Canvas canvas, Size size) {
    final c = Offset(size.width / 2, size.height / 2);
    final r = size.width / 2 - 14;
    const sw = 14.0;

    canvas.drawArc(
      Rect.fromCircle(center: c, radius: r),
      -math.pi * 0.75,
      math.pi * 1.5,
      false,
      Paint()
        ..color = trackColor
        ..style = PaintingStyle.stroke
        ..strokeWidth = sw
        ..strokeCap = StrokeCap.round,
    );

    final sweep = math.pi * 1.5 * progress;
    canvas.drawArc(
      Rect.fromCircle(center: c, radius: r),
      -math.pi * 0.75,
      sweep,
      false,
      Paint()
        ..shader = const SweepGradient(
          startAngle: -math.pi * 0.75,
          endAngle: math.pi * 0.75,
          colors: [
            Color(0xFF22C55E),
            Color(0xFFFFD700),
            Color(0xFFF59E0B),
            Color(0xFFEF4444),
          ],
          stops: [0.0, 0.35, 0.65, 1.0],
        ).createShader(Rect.fromCircle(center: c, radius: r))
        ..style = PaintingStyle.stroke
        ..strokeWidth = sw
        ..strokeCap = StrokeCap.round,
    );
  }

  @override
  bool shouldRepaint(covariant _GaugePainter old) =>
      old.progress != progress || old.trackColor != trackColor;
}

// ─── Weak areas card ──────────────────────────────────────────────────────────

class _WeakAreasCard extends StatelessWidget {
  final List<_WeakArea> areas;
  final _WaTheme t;

  const _WeakAreasCard({required this.areas, required this.t});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(18, 18, 18, 8),
      decoration: BoxDecoration(
        color: t.card,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: t.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: t.isDark ? 0.2 : 0.05),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Weak Areas',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: t.text,
            ),
          ),
          const SizedBox(height: 18),
          ...areas.map((a) => _WeakAreaRow(area: a, t: t)),
        ],
      ),
    );
  }
}

class _WeakAreaRow extends StatelessWidget {
  final _WeakArea area;
  final _WaTheme t;

  const _WeakAreaRow({required this.area, required this.t});

  @override
  Widget build(BuildContext context) {
    final pct = (area.progress * 100).round();
    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                area.name,
                style: GoogleFonts.inter(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: t.text,
                ),
              ),
              Text(
                '$pct%',
                style: GoogleFonts.inter(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: t.text,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: SizedBox(
              height: 8,
              child: Stack(
                children: [
                  Container(color: t.track),
                  FractionallySizedBox(
                    widthFactor: area.progress,
                    child: Container(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(colors: area.gradient),
                        borderRadius: BorderRadius.circular(6),
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

// ─── Comeback plan CTA ────────────────────────────────────────────────────────

class _AiRevisionButton extends StatelessWidget {
  final VoidCallback onTap;
  const _AiRevisionButton({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 18),
          decoration: BoxDecoration(
            color: AppColors.primary,
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.45),
                blurRadius: 20,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.route_rounded, size: 20, color: Colors.white),
              const SizedBox(width: 10),
              Text(
                'Your Comeback Plan',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
