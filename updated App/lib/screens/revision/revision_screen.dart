import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/providers.dart';
import '../../providers/revision_provider.dart';
import '../../widgets/common_widgets.dart';

class RevisionScreen extends ConsumerStatefulWidget {
  const RevisionScreen({super.key});

  @override
  ConsumerState<RevisionScreen> createState() => _RevisionScreenState();
}

class _RevisionScreenState extends ConsumerState<RevisionScreen> {
  String _subject = 'All';

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(revisionProvider.notifier).loadAll());
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final revision = ref.watch(revisionProvider);
    final notesCount = ref.watch(savedNotesProvider).length;
    final flashCount = revision.flashcards.length;
    final weakCount = revision.weakTopics.length;
    final planDays = revision.plan?.days.length ?? 0;
    final completedDays =
        revision.plan?.days.where((d) => d.completed).length ?? 0;

    final firstCard = revision.flashcards.isNotEmpty
        ? revision.flashcards.first
        : null;

    const subjects = [
      ('All', LucideIcons.layoutGrid, AppColors.primary),
      ('Physics', LucideIcons.zap, Color(0xFF3B82F6)),
      ('Chemistry', LucideIcons.flaskConical, Color(0xFF22C55E)),
      ('Maths', LucideIcons.calculator, Color(0xFF8B5CF6)),
      ('Biology', LucideIcons.dna, Color(0xFFEC4899)),
    ];

    return Scaffold(
      backgroundColor: c.background,
      body: SafeArea(
        child: revision.loading && revision.plan == null
            ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
            : RefreshIndicator(
                onRefresh: () => ref.read(revisionProvider.notifier).loadAll(),
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(
                    parent: BouncingScrollPhysics(),
                  ),
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Revision',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 24,
                          fontWeight: FontWeight.w800,
                          color: c.textPrimary,
                          letterSpacing: -0.4,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Revise smart. Fix weak spots. Score better.',
                        style: TextStyle(fontSize: 13, color: c.textMuted),
                      ),
                      const SizedBox(height: 18),
                      _ComebackPlanCard(
                        personalized: revision.plan?.personalized == true,
                        completedDays: completedDays,
                        totalDays: planDays,
                        onTap: () => AppRouter.go(context, AppRoutes.revisionPlan),
                      ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.04, end: 0),
                      if (revision.error != null) ...[
                        const SizedBox(height: 10),
                        Text(
                          revision.error!,
                          style: TextStyle(color: c.textMuted, fontSize: 13),
                        ),
                      ],
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: _StatChip(
                              icon: LucideIcons.layers,
                              label: 'Flashcards',
                              value: '$flashCount',
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: _StatChip(
                              icon: LucideIcons.target,
                              label: 'Weak areas',
                              value: '$weakCount',
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: _StatChip(
                              icon: LucideIcons.notebookPen,
                              label: 'Notes',
                              value: '$notesCount',
                            ),
                          ),
                        ],
                      ).animate(delay: 60.ms).fadeIn(duration: 380.ms),
                      const SizedBox(height: 20),
                      Text(
                        'Subjects',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          color: c.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 10),
                      SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: Row(
                          children: subjects.map((s) {
                            final selected = _subject == s.$1;
                            return Padding(
                              padding: const EdgeInsets.only(right: 8),
                              child: FilterChip(
                                label: Text(s.$1),
                                avatar: Icon(s.$2, size: 16, color: s.$3),
                                selected: selected,
                                onSelected: (_) => setState(() => _subject = s.$1),
                                selectedColor: s.$3.withValues(alpha: 0.15),
                                checkmarkColor: s.$3,
                                labelStyle: TextStyle(
                                  fontWeight: FontWeight.w600,
                                  color: selected ? s.$3 : c.textSecondary,
                                ),
                                side: BorderSide(
                                  color: selected ? s.$3 : c.cardBorder,
                                ),
                                backgroundColor: c.card,
                              ),
                            );
                          }).toList(),
                        ),
                      ),
                      const SizedBox(height: 22),
                      SectionTitle(
                        title: 'Flashcards',
                        action: flashCount > 0 ? 'See all' : null,
                        onAction: flashCount > 0
                            ? () => AppRouter.go(context, AppRoutes.flashcards)
                            : null,
                      ),
                      const SizedBox(height: 8),
                      _FlashcardHero(
                        title: firstCard?.front ?? 'Start with your first flashcard',
                        hint: firstCard?.back ?? 'Quick recall builds long-term memory',
                        subject: firstCard?.subject ?? 'General',
                        count: flashCount,
                        onTap: () => AppRouter.go(context, AppRoutes.flashcards),
                      ).animate(delay: 100.ms).fadeIn(duration: 420.ms),
                      const SizedBox(height: 22),
                      const SectionTitle(title: 'Study Resources'),
                      const SizedBox(height: 8),
                      _ResourceTile(
                        title: 'Saved Notes',
                        subtitle: 'Your important points in one place',
                        icon: LucideIcons.bookmark,
                        count: notesCount,
                        onTap: () => AppRouter.go(context, AppRoutes.savedNotes),
                      ),
                      _ResourceTile(
                        title: 'Formula Flashcards',
                        subtitle: 'Revise formulas on the go',
                        icon: LucideIcons.sigma,
                        count: flashCount,
                        onTap: () => AppRouter.go(context, AppRoutes.flashcards),
                      ),
                      _ResourceTile(
                        title: 'Weak Topics Quiz',
                        subtitle: 'Practice where you struggle most',
                        icon: LucideIcons.circleAlert,
                        count: weakCount,
                        onTap: () => AppRouter.go(context, AppRoutes.quizTopics),
                      ),
                      _ResourceTile(
                        title: 'Daily Revision',
                        subtitle: 'Short daily drill to stay sharp',
                        icon: LucideIcons.calendarCheck,
                        count: revision.revisionNeeded.length,
                        onTap: () => AppRouter.go(context, AppRoutes.dailyChallenge),
                      ),
                    ],
                  ),
                ),
              ),
      ),
    );
  }
}

class _ComebackPlanCard extends StatelessWidget {
  const _ComebackPlanCard({
    required this.personalized,
    required this.completedDays,
    required this.totalDays,
    required this.onTap,
  });

  final bool personalized;
  final int completedDays;
  final int totalDays;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            gradient: LinearGradient(
              colors: [
                AppColors.primary.withValues(alpha: context.isDark ? 0.35 : 0.12),
                AppColors.secondary.withValues(alpha: context.isDark ? 0.2 : 0.08),
              ],
            ),
            border: Border.all(
              color: AppColors.primary.withValues(alpha: 0.25),
            ),
          ),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: const Icon(
                    Icons.route_rounded,
                    color: AppColors.primary,
                    size: 26,
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Your Comeback Plan',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                          color: c.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        personalized
                            ? 'Built around your weak topics — $completedDays of $totalDays days done'
                            : 'Take one quiz — we\'ll shape a plan that fits you',
                        style: TextStyle(
                          fontSize: 13,
                          color: c.textSecondary,
                          height: 1.35,
                        ),
                      ),
                    ],
                  ),
                ),
                Icon(Icons.chevron_right_rounded, color: c.textMuted),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _StatChip extends StatelessWidget {
  const _StatChip({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return AppCard(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: AppColors.primary),
          const SizedBox(height: 8),
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
    );
  }
}

class _FlashcardHero extends StatelessWidget {
  const _FlashcardHero({
    required this.title,
    required this.hint,
    required this.subject,
    required this.count,
    required this.onTap,
  });

  final String title;
  final String hint;
  final String subject;
  final int count;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: AppCard(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  subject,
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontWeight: FontWeight.w700,
                    fontSize: 11,
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                title,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: GoogleFonts.plusJakartaSans(
                  fontWeight: FontWeight.w700,
                  fontSize: 17,
                  color: c.textPrimary,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                hint,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(fontSize: 13, color: c.textMuted, height: 1.35),
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  Text(
                    count > 0 ? 'Review now ($count)' : 'Open flashcards',
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(width: 4),
                  const Icon(Icons.arrow_forward_rounded,
                      size: 16, color: AppColors.primary),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ResourceTile extends StatelessWidget {
  const _ResourceTile({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.count,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final int count;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: AppCard(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, color: AppColors.primary, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 14,
                          color: c.textPrimary,
                        ),
                      ),
                      Text(
                        subtitle,
                        style: TextStyle(fontSize: 12, color: c.textMuted),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                  decoration: BoxDecoration(
                    color: c.background,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: c.cardBorder),
                  ),
                  child: Text(
                    '$count',
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      color: AppColors.primary,
                      fontSize: 12,
                    ),
                  ),
                ),
                const SizedBox(width: 6),
                Icon(Icons.chevron_right_rounded, color: c.textMuted, size: 22),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
