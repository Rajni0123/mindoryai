import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/providers.dart';

class TestResultArgs {
  final int score;
  final int total;
  final int rank;
  final int rankImprovement;

  const TestResultArgs({
    required this.score,
    required this.total,
    this.rank = 215,
    this.rankImprovement = 23,
  });

  int get accuracy => total > 0 ? ((score / total) * 100).round() : 0;
}

class TestCompletedScreen extends ConsumerWidget {
  final TestResultArgs args;

  const TestCompletedScreen({super.key, required this.args});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final t = _TcTheme.of(context);
    final name = _firstName(ref.watch(authProvider).user?.name);

    return Scaffold(
      backgroundColor: t.bg,
      body: SafeArea(
        child: Column(
          children: [
            _Header(t: t),
            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Column(
                  children: [
                    const SizedBox(height: 8),
                    _TrophyHero(t: t)
                        .animate()
                        .fadeIn(duration: 500.ms)
                        .scale(begin: const Offset(0.85, 0.85), curve: Curves.easeOutBack),
                    const SizedBox(height: 24),
                    Text(
                      'Great Job, $name! 🔥',
                      textAlign: TextAlign.center,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 26,
                        fontWeight: FontWeight.w800,
                        color: t.text,
                        letterSpacing: -0.5,
                      ),
                    ).animate(delay: 120.ms).fadeIn(duration: 400.ms),
                    const SizedBox(height: 8),
                    Text(
                      'You just completed the test.',
                      style: GoogleFonts.inter(fontSize: 15, color: t.muted),
                    ).animate(delay: 160.ms).fadeIn(duration: 400.ms),
                    const SizedBox(height: 28),
                    _StatsGrid(args: args, t: t)
                        .animate(delay: 200.ms)
                        .fadeIn(duration: 450.ms)
                        .slideY(begin: 0.06, end: 0),
                    const SizedBox(height: 32),
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
              child: _ViewAnalysisBtn(
                onTap: () {
                  AppRouter.goReplace(context, AppRoutes.weaknessAnalysis);
                },
              ).animate(delay: 280.ms).fadeIn(duration: 400.ms).slideY(begin: 0.08, end: 0),
            ),
          ],
        ),
      ),
    );
  }

  static String _firstName(String? name) {
    if (name == null || name.trim().isEmpty) return 'Arjun';
    return name.trim().split(' ').first;
  }
}

// ─── Theme ────────────────────────────────────────────────────────────────────

class _TcTheme {
  final Color bg;
  final Color card;
  final Color cardBorder;
  final Color text;
  final Color muted;
  final bool isDark;

  const _TcTheme({
    required this.bg,
    required this.card,
    required this.cardBorder,
    required this.text,
    required this.muted,
    required this.isDark,
  });

  factory _TcTheme.of(BuildContext context) {
    final c = context.dash;
    final dark = context.isDark;
    return _TcTheme(
      bg: dark ? const Color(0xFF0D0D12) : c.background,
      card: dark ? const Color(0xFF16161E) : c.card,
      cardBorder: dark ? const Color(0xFF252530) : c.cardBorder,
      text: c.textPrimary,
      muted: c.textMuted,
      isDark: dark,
    );
  }
}

// ─── Header ───────────────────────────────────────────────────────────────────

class _Header extends StatelessWidget {
  final _TcTheme t;
  const _Header({required this.t});

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
              'Test Completed',
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

// ─── Trophy hero ──────────────────────────────────────────────────────────────

class _TrophyHero extends StatelessWidget {
  final _TcTheme t;
  const _TrophyHero({required this.t});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 200,
      width: double.infinity,
      child: Stack(
        alignment: Alignment.center,
        children: [
          CustomPaint(
            size: const Size(double.infinity, 200),
            painter: _ConfettiPainter(),
          ),
          Container(
            width: 140,
            height: 140,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFFFFD700).withValues(alpha: 0.25),
                  blurRadius: 40,
                  spreadRadius: 4,
                ),
              ],
            ),
          ),
          Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const _TrophyWidget(),
              const SizedBox(height: 4),
              Container(
                width: 56,
                height: 14,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF7B61FF), Color(0xFF5B4AE0)],
                  ),
                  borderRadius: BorderRadius.circular(4),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.4),
                      blurRadius: 12,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _TrophyWidget extends StatelessWidget {
  const _TrophyWidget();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 90,
      height: 100,
      child: CustomPaint(painter: _TrophyPainter()),
    );
  }
}

class _TrophyPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final gold = Paint()
      ..shader = const LinearGradient(
        begin: Alignment.topCenter,
        end: Alignment.bottomCenter,
        colors: [Color(0xFFFFE566), Color(0xFFFFB800), Color(0xFFE6A000)],
      ).createShader(Rect.fromLTWH(0, 0, size.width, size.height));

    final cup = Path()
      ..moveTo(size.width * 0.25, size.height * 0.15)
      ..lineTo(size.width * 0.75, size.height * 0.15)
      ..lineTo(size.width * 0.68, size.height * 0.55)
      ..quadraticBezierTo(
        size.width * 0.5,
        size.height * 0.68,
        size.width * 0.32,
        size.height * 0.55,
      )
      ..close();
    canvas.drawPath(cup, gold);

    // Handles
    final handle = Paint()
      ..color = const Color(0xFFFFD700)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 5;
    canvas.drawArc(
      Rect.fromCircle(
        center: Offset(size.width * 0.12, size.height * 0.35),
        radius: 14,
      ),
      -math.pi * 0.5,
      math.pi,
      false,
      handle,
    );
    canvas.drawArc(
      Rect.fromCircle(
        center: Offset(size.width * 0.88, size.height * 0.35),
        radius: 14,
      ),
      -math.pi * 0.5,
      math.pi,
      false,
      handle,
    );

    // Stem
    canvas.drawRect(
      Rect.fromLTWH(size.width * 0.42, size.height * 0.55, size.width * 0.16, size.height * 0.18),
      gold,
    );

    // Star
    final starPaint = Paint()..color = const Color(0xFFFFF8DC);
    _drawStar(canvas, Offset(size.width * 0.5, size.height * 0.32), 10, starPaint);
  }

  void _drawStar(Canvas canvas, Offset c, double r, Paint paint) {
    final path = Path();
    for (var i = 0; i < 5; i++) {
      final angle = -math.pi / 2 + i * 4 * math.pi / 5;
      final pt = Offset(c.dx + r * math.cos(angle), c.dy + r * math.sin(angle));
      i == 0 ? path.moveTo(pt.dx, pt.dy) : path.lineTo(pt.dx, pt.dy);
    }
    path.close();
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class _ConfettiPainter extends CustomPainter {
  static final _pieces = List.generate(24, (i) {
    final rng = math.Random(i * 7);
    return _ConfettiPiece(
      x: rng.nextDouble(),
      y: rng.nextDouble() * 0.7,
      color: [
        const Color(0xFFFF6B9D),
        const Color(0xFF22C55E),
        const Color(0xFFFF9F43),
        const Color(0xFF5B8CFF),
        const Color(0xFFFFD700),
      ][i % 5],
      size: 4 + rng.nextDouble() * 4,
      rotation: rng.nextDouble() * math.pi,
    );
  });

  @override
  void paint(Canvas canvas, Size size) {
    for (final p in _pieces) {
      canvas.save();
      canvas.translate(p.x * size.width, p.y * size.height);
      canvas.rotate(p.rotation);
      canvas.drawRect(
        Rect.fromCenter(center: Offset.zero, width: p.size, height: p.size * 0.6),
        Paint()..color = p.color,
      );
      canvas.restore();
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class _ConfettiPiece {
  final double x, y, size, rotation;
  final Color color;
  const _ConfettiPiece({
    required this.x,
    required this.y,
    required this.color,
    required this.size,
    required this.rotation,
  });
}

// ─── Stats grid ───────────────────────────────────────────────────────────────

class _StatsGrid extends StatelessWidget {
  final TestResultArgs args;
  final _TcTheme t;

  const _StatsGrid({required this.args, required this.t});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _StatCard(
                label: 'Score',
                value: '${args.score} / ${args.total}',
                t: t,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _StatCard(
                label: 'Accuracy',
                value: '${args.accuracy}%',
                t: t,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        _RankCard(
          rank: args.rank,
          improvement: args.rankImprovement,
          t: t,
        ),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final _TcTheme t;

  const _StatCard({required this.label, required this.value, required this.t});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 20),
      decoration: BoxDecoration(
        color: t.card,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: t.cardBorder),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: GoogleFonts.inter(fontSize: 13, color: t.muted)),
          const SizedBox(height: 8),
          Text(
            value,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 24,
              fontWeight: FontWeight.w800,
              color: t.text,
            ),
          ),
        ],
      ),
    );
  }
}

class _RankCard extends StatelessWidget {
  final int rank;
  final int improvement;
  final _TcTheme t;

  const _RankCard({
    required this.rank,
    required this.improvement,
    required this.t,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 20),
      decoration: BoxDecoration(
        color: t.card,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: t.cardBorder),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Rank', style: GoogleFonts.inter(fontSize: 13, color: t.muted)),
                const SizedBox(height: 8),
                Text(
                  '#$rank',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                    color: t.text,
                  ),
                ),
              ],
            ),
          ),
          Row(
            children: [
              const Icon(LucideIcons.trendingUp, size: 20, color: Color(0xFF22C55E)),
              const SizedBox(width: 4),
              Text(
                '$improvement',
                style: GoogleFonts.inter(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: const Color(0xFF22C55E),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

// ─── CTA ──────────────────────────────────────────────────────────────────────

class _ViewAnalysisBtn extends StatelessWidget {
  final VoidCallback onTap;
  const _ViewAnalysisBtn({required this.onTap});

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
          child: Text(
            'View Analysis',
            textAlign: TextAlign.center,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
        ),
      ),
    );
  }
}
