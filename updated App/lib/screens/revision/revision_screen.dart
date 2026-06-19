import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../models/models.dart';
import '../../providers/providers.dart';
import '../../providers/revision_provider.dart';

class RevisionScreen extends ConsumerStatefulWidget {
  const RevisionScreen({super.key});

  @override
  ConsumerState<RevisionScreen> createState() => _RevisionScreenState();
}

class _RevisionScreenState extends ConsumerState<RevisionScreen> {
  String _subject = 'All';
  bool _flashHeroVisible = true;

  static const _subjects = [
    _SubjectFilter('All', LucideIcons.atom, AppColors.primary),
    _SubjectFilter('Physics', LucideIcons.zap, Color(0xFFEF4444)),
    _SubjectFilter('Chemistry', LucideIcons.flaskConical, Color(0xFF22C55E)),
    _SubjectFilter('Maths', LucideIcons.flame, Color(0xFFF59E0B)),
    _SubjectFilter('Biology', LucideIcons.leaf, Color(0xFF14B8A6)),
  ];

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(revisionProvider.notifier).loadAll());
  }

  List<FlashcardItem> _filteredCards(List<FlashcardItem> all) {
    if (_subject == 'All') return all;
    return all
        .where((c) => c.subject.toLowerCase() == _subject.toLowerCase())
        .toList();
  }

  void _onSubjectSelected(String subject) {
    setState(() => _subject = subject);
    if (subject == 'All') {
      ref.read(revisionProvider.notifier).loadFlashcards();
    } else {
      ref.read(revisionProvider.notifier).loadFlashcards(subject: subject);
    }
  }

  void _showSearch() {
    final revision = ref.read(revisionProvider);
    final c = context.dash;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: c.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        var query = '';
        return StatefulBuilder(
          builder: (context, setSheetState) {
            final results = revision.flashcards.where((card) {
              if (query.trim().isEmpty) return false;
              final q = query.toLowerCase();
              return card.front.toLowerCase().contains(q) ||
                  card.back.toLowerCase().contains(q) ||
                  card.subject.toLowerCase().contains(q);
            }).take(8).toList();

            return Padding(
              padding: EdgeInsets.fromLTRB(
                20,
                16,
                20,
                MediaQuery.of(context).viewInsets.bottom + 24,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: c.cardBorder,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    autofocus: true,
                    onChanged: (v) => setSheetState(() => query = v),
                    decoration: InputDecoration(
                      hintText: 'Search flashcards, topics…',
                      prefixIcon: const Icon(LucideIcons.search, size: 20),
                      filled: true,
                      fillColor: c.background,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: BorderSide(color: c.cardBorder),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: BorderSide(color: c.cardBorder),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  if (query.trim().isNotEmpty && results.isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      child: Text(
                        'No matches found',
                        style: TextStyle(color: c.textMuted),
                      ),
                    )
                  else
                    ...results.map(
                      (card) => ListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(
                          card.front,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        subtitle: Text(card.subject),
                        onTap: () {
                          Navigator.pop(context);
                          AppRouter.go(context, AppRoutes.flashcards);
                        },
                      ),
                    ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final revision = ref.watch(revisionProvider);
    final notesCount = ref.watch(savedNotesProvider).length;
    final cards = _filteredCards(revision.flashcards);
    final flashCount = cards.length;
    final formulaCount = revision.flashcards
        .where((c) => c.type.toLowerCase() == 'formula')
        .length;
    final weakCount = revision.weakTopics.length;
    final pastMistakesCount = revision.weakTopics.fold<int>(
          0,
          (sum, w) => sum + w.attempts,
        ) +
        revision.revisionNeeded.length;
    final dailyDue = revision.revisionNeeded.isNotEmpty;

    final deckTitle = _deckTitle(cards);
    final deckCount = flashCount > 0 ? flashCount : revision.flashcards.length;

    return Scaffold(
      backgroundColor: c.background,
      body: SafeArea(
        child: revision.loading && revision.plan == null
            ? const Center(
                child: CircularProgressIndicator(color: AppColors.primary),
              )
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
                      _RevisionHeader(
                        onBack: () {
                          if (Navigator.of(context).canPop()) {
                            Navigator.of(context).pop();
                          } else {
                            ref.read(navIndexProvider.notifier).state = 0;
                          }
                        },
                        onSearch: _showSearch,
                      ).animate().fadeIn(duration: 380.ms),
                      const SizedBox(height: 18),
                      _SubjectFilterBar(
                        subjects: _subjects,
                        selected: _subject,
                        onSelect: _onSubjectSelected,
                      ).animate(delay: 40.ms).fadeIn(duration: 400.ms),
                      const SizedBox(height: 22),
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              'AI Flashcards',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 16,
                                fontWeight: FontWeight.w700,
                                color: c.textPrimary,
                              ),
                            ),
                          ),
                          if (deckCount > 0)
                            GestureDetector(
                              onTap: () =>
                                  AppRouter.go(context, AppRoutes.flashcards),
                              child: Text(
                                'View all',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.primary,
                                ),
                              ),
                            ),
                        ],
                      ).animate(delay: 70.ms).fadeIn(duration: 380.ms),
                      const SizedBox(height: 14),
                      if (_flashHeroVisible)
                        _StackedFlashcardHero(
                          title: deckTitle,
                          cardCount: deckCount,
                          onReview: () =>
                              AppRouter.go(context, AppRoutes.flashcards),
                          onDismiss: () =>
                              setState(() => _flashHeroVisible = false),
                        ).animate(delay: 90.ms).fadeIn(duration: 420.ms),
                      if (!_flashHeroVisible) ...[
                        _RevisionCategoryTile(
                          title: 'AI Flashcards',
                          subtitle: deckCount > 0
                              ? '$deckCount Cards'
                              : 'No cards yet',
                          icon: LucideIcons.layers,
                          iconColor: AppColors.primary,
                          iconBg: AppColors.primary.withValues(alpha: 0.12),
                          onTap: () =>
                              AppRouter.go(context, AppRoutes.flashcards),
                        ),
                      ],
                      const SizedBox(height: 14),
                      _RevisionCategoryTile(
                        title: 'Formulas',
                        subtitle: formulaCount > 0
                            ? '$formulaCount Cards'
                            : '${deckCount > 0 ? deckCount : 26} Cards',
                        icon: LucideIcons.bookOpen,
                        iconColor: const Color(0xFF3B82F6),
                        iconBg: const Color(0xFF3B82F6).withValues(alpha: 0.12),
                        onTap: () =>
                            AppRouter.go(context, AppRoutes.flashcards),
                      ).animate(delay: 110.ms).fadeIn(duration: 380.ms),
                      const SizedBox(height: 10),
                      _RevisionCategoryTile(
                        title: 'Saved Notes',
                        subtitle: notesCount > 0
                            ? '$notesCount Notes'
                            : '12 Notes',
                        icon: LucideIcons.fileText,
                        iconColor: const Color(0xFF60A5FA),
                        iconBg: const Color(0xFF60A5FA).withValues(alpha: 0.12),
                        onTap: () =>
                            AppRouter.go(context, AppRoutes.savedNotes),
                      ).animate(delay: 130.ms).fadeIn(duration: 380.ms),
                      const SizedBox(height: 10),
                      _RevisionCategoryTile(
                        title: 'Weak Topics',
                        subtitle: weakCount > 0
                            ? '$weakCount Topics'
                            : '8 Topics',
                        icon: LucideIcons.circleAlert,
                        iconColor: const Color(0xFFF59E0B),
                        iconBg: const Color(0xFFF59E0B).withValues(alpha: 0.12),
                        onTap: () => AppRouter.go(
                          context,
                          AppRoutes.weaknessAnalysis,
                        ),
                      ).animate(delay: 150.ms).fadeIn(duration: 380.ms),
                      const SizedBox(height: 10),
                      _RevisionCategoryTile(
                        title: 'Past Mistakes',
                        subtitle: pastMistakesCount > 0
                            ? '$pastMistakesCount Questions'
                            : '15 Questions',
                        icon: LucideIcons.clipboardList,
                        iconColor: const Color(0xFFF97316),
                        iconBg: const Color(0xFFF97316).withValues(alpha: 0.12),
                        onTap: () => AppRouter.go(
                          context,
                          AppRoutes.weaknessAnalysis,
                        ),
                      ).animate(delay: 170.ms).fadeIn(duration: 380.ms),
                      const SizedBox(height: 10),
                      _RevisionCategoryTile(
                        title: 'Daily Revision',
                        subtitle: dailyDue ? 'Due for today' : 'All caught up',
                        icon: LucideIcons.calendarCheck,
                        iconColor: AppColors.primary,
                        iconBg: AppColors.primary.withValues(alpha: 0.12),
                        onTap: () => AppRouter.go(
                          context,
                          AppRoutes.revisionPlan,
                        ),
                      ).animate(delay: 190.ms).fadeIn(duration: 380.ms),
                      if (revision.error != null) ...[
                        const SizedBox(height: 14),
                        Text(
                          revision.error!,
                          style: TextStyle(color: c.textMuted, fontSize: 13),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
      ),
    );
  }

  static String _deckTitle(List<FlashcardItem> cards) {
    if (cards.isEmpty) return 'Work, Energy & Power';
    final first = cards.first;
    if (first.front.length <= 36) return first.front;
    return '${first.subject} Revision';
  }
}

class _SubjectFilter {
  final String label;
  final IconData icon;
  final Color color;

  const _SubjectFilter(this.label, this.icon, this.color);
}

class _RevisionHeader extends StatelessWidget {
  const _RevisionHeader({
    required this.onBack,
    required this.onSearch,
  });

  final VoidCallback onBack;
  final VoidCallback onSearch;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Row(
      children: [
        _HeaderIconBtn(icon: LucideIcons.arrowLeft, onTap: onBack),
        Expanded(
          child: Text(
            'Revision',
            textAlign: TextAlign.center,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: c.textPrimary,
            ),
          ),
        ),
        _HeaderIconBtn(icon: LucideIcons.search, onTap: onSearch),
      ],
    );
  }
}

class _HeaderIconBtn extends StatelessWidget {
  const _HeaderIconBtn({required this.icon, required this.onTap});

  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: SizedBox(
          width: 44,
          height: 44,
          child: Icon(icon, size: 22, color: context.dash.textPrimary),
        ),
      ),
    );
  }
}

class _SubjectFilterBar extends StatelessWidget {
  const _SubjectFilterBar({
    required this.subjects,
    required this.selected,
    required this.onSelect,
  });

  final List<_SubjectFilter> subjects;
  final String selected;
  final ValueChanged<String> onSelect;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return SizedBox(
      height: 88,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: subjects.length,
        separatorBuilder: (_, __) => const SizedBox(width: 14),
        itemBuilder: (context, i) {
          final s = subjects[i];
          final active = selected == s.label;
          return GestureDetector(
            onTap: () => onSelect(s.label),
            child: SizedBox(
              width: 58,
              child: Column(
                children: [
                  AnimatedContainer(
                    duration: const Duration(milliseconds: 220),
                    width: 52,
                    height: 52,
                    decoration: BoxDecoration(
                      color: active
                          ? s.color
                          : s.color.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(
                        s.label == 'All' ? 14 : 26,
                      ),
                      border: active
                          ? null
                          : Border.all(
                              color: s.color.withValues(alpha: 0.2),
                            ),
                    ),
                    child: Icon(
                      s.icon,
                      size: 22,
                      color: active ? Colors.white : s.color,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    s.label,
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 12,
                      fontWeight:
                          active ? FontWeight.w700 : FontWeight.w500,
                      color: active ? s.color : c.textMuted,
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _StackedFlashcardHero extends StatelessWidget {
  const _StackedFlashcardHero({
    required this.title,
    required this.cardCount,
    required this.onReview,
    required this.onDismiss,
  });

  final String title;
  final int cardCount;
  final VoidCallback onReview;
  final VoidCallback onDismiss;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 168,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Positioned(
            top: 14,
            left: 18,
            right: 6,
            child: _FlashcardLayer(
              opacity: 0.35,
              offset: 0,
              child: _FlashcardFace(
                title: title,
                cardCount: cardCount,
                onReview: onReview,
                onDismiss: null,
                showContent: false,
              ),
            ),
          ),
          Positioned(
            top: 8,
            left: 10,
            right: 12,
            child: _FlashcardLayer(
              opacity: 0.55,
              offset: 0,
              child: _FlashcardFace(
                title: title,
                cardCount: cardCount,
                onReview: onReview,
                onDismiss: null,
                showContent: false,
              ),
            ),
          ),
          Positioned(
            top: 0,
            left: 0,
            right: 18,
            child: _FlashcardFace(
              title: title,
              cardCount: cardCount,
              onReview: onReview,
              onDismiss: onDismiss,
              showContent: true,
            ),
          ),
        ],
      ),
    );
  }
}

class _FlashcardLayer extends StatelessWidget {
  const _FlashcardLayer({
    required this.child,
    required this.opacity,
    required this.offset,
  });

  final Widget child;
  final double opacity;
  final double offset;

  @override
  Widget build(BuildContext context) {
    return Opacity(opacity: opacity, child: child);
  }
}

class _FlashcardFace extends StatelessWidget {
  const _FlashcardFace({
    required this.title,
    required this.cardCount,
    required this.onReview,
    required this.onDismiss,
    required this.showContent,
  });

  final String title;
  final int cardCount;
  final VoidCallback onReview;
  final VoidCallback? onDismiss;
  final bool showContent;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: showContent ? onReview : null,
        borderRadius: BorderRadius.circular(20),
        child: Ink(
          height: 148,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFF7B61FF), Color(0xFF4D9FFF)],
            ),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.3),
                blurRadius: 20,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Stack(
            children: [
              if (showContent) ...[
                Positioned(
                  right: 16,
                  top: 0,
                  bottom: 0,
                  child: Opacity(
                    opacity: 0.35,
                    child: Icon(
                      LucideIcons.atom,
                      size: 88,
                      color: Colors.white.withValues(alpha: 0.9),
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(18, 18, 18, 16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              title,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 18,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                                height: 1.2,
                              ),
                            ),
                          ),
                          if (onDismiss != null)
                            GestureDetector(
                              onTap: onDismiss,
                              child: Container(
                                width: 28,
                                height: 28,
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.18),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.close,
                                  size: 16,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        cardCount > 0 ? '$cardCount Cards' : '10 Cards',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.white.withValues(alpha: 0.9),
                        ),
                      ),
                      const Spacer(),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            'Review Now',
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 14,
                              fontWeight: FontWeight.w700,
                              color: Colors.white,
                            ),
                          ),
                          const SizedBox(width: 4),
                          const Icon(
                            Icons.arrow_forward_rounded,
                            size: 16,
                            color: Colors.white,
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _RevisionCategoryTile extends StatelessWidget {
  const _RevisionCategoryTile({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.iconColor,
    required this.iconBg,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Color iconColor;
  final Color iconBg;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Material(
      color: c.card,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: c.cardBorder),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(
                  alpha: context.isDark ? 0.15 : 0.04,
                ),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: iconBg,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, size: 20, color: iconColor),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: c.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: TextStyle(fontSize: 12, color: c.textMuted),
                    ),
                  ],
                ),
              ),
              Icon(LucideIcons.chevronRight, size: 18, color: c.textMuted),
            ],
          ),
        ),
      ),
    );
  }
}
