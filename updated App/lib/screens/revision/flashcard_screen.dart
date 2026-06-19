import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme/app_theme.dart';
import '../../models/models.dart';
import '../../providers/revision_provider.dart';

class FlashcardScreen extends ConsumerStatefulWidget {
  const FlashcardScreen({super.key});

  @override
  ConsumerState<FlashcardScreen> createState() => _FlashcardScreenState();
}

class _FlashcardScreenState extends ConsumerState<FlashcardScreen> {
  int _index = 0;
  bool _flipped = false;
  String _filter = 'All';

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(revisionProvider.notifier).loadFlashcards());
  }

  List<FlashcardItem> get _cards {
    final all = ref.watch(revisionProvider).flashcards;
    if (_filter == 'All') return all;
    return all
        .where((c) => c.subject.toLowerCase() == _filter.toLowerCase())
        .toList();
  }

  Future<void> _reload() async {
    await ref.read(revisionProvider.notifier).loadFlashcards();
    setState(() {
      _index = 0;
      _flipped = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    final revision = ref.watch(revisionProvider);
    final cards = _cards;

    if (revision.loading && cards.isEmpty) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (cards.isEmpty) {
      return Scaffold(
        appBar: AppBar(title: const Text('Flashcards')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  revision.error ?? 'No flashcards yet',
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => _reload(),
                  child: const Text('Retry'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final safeIndex = _index.clamp(0, cards.length - 1);
    if (safeIndex != _index) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) setState(() => _index = safeIndex);
      });
    }

    final card = cards[safeIndex];
    final allCards = revision.flashcards;
    final subjects = {
      'All',
      ...allCards.map((c) => c.subject).where((s) => s.isNotEmpty),
    }.toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Flashcards'),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: Center(
              child: Text('${safeIndex + 1}/${cards.length}',
                  style: const TextStyle(fontWeight: FontWeight.w600)),
            ),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: subjects
                    .map((s) => Padding(
                          padding: const EdgeInsets.only(right: 8),
                          child: FilterChip(
                            label: Text(s),
                            selected: _filter == s,
                            onSelected: (_) => setState(() {
                              _filter = s;
                              _index = 0;
                              _flipped = false;
                            }),
                            selectedColor: AppColors.primary.withValues(alpha: 0.15),
                            checkmarkColor: AppColors.primary,
                          ),
                        ))
                    .toList(),
              ),
            ),
            const SizedBox(height: 32),
            Expanded(
              child: GestureDetector(
                onTap: () => setState(() => _flipped = !_flipped),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  width: double.infinity,
                  decoration: BoxDecoration(
                    gradient:
                        _flipped ? AppColors.primaryGradient : AppColors.cardGradient,
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.primary.withValues(alpha: 0.2),
                        blurRadius: 20,
                        offset: const Offset(0, 10),
                      ),
                    ],
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          card.subject,
                          style: TextStyle(
                            color: _flipped ? Colors.white70 : AppColors.primary,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 20),
                        child: Text(
                          _flipped ? card.back : card.front,
                          style: TextStyle(
                            fontSize: _flipped ? 24 : 20,
                            fontWeight: FontWeight.w800,
                            color: _flipped ? Colors.white : AppColors.textPrimary,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        _flipped ? 'Tap to see question' : 'Tap to reveal answer',
                        style: TextStyle(
                          color: _flipped ? Colors.white54 : AppColors.textMuted,
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: safeIndex > 0
                        ? () => setState(() {
                              _index--;
                              _flipped = false;
                            })
                        : null,
                    icon: const Icon(Icons.arrow_back_rounded),
                    label: const Text('Previous'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: safeIndex < cards.length - 1
                        ? () => setState(() {
                              _index++;
                              _flipped = false;
                            })
                        : null,
                    icon: const Icon(Icons.arrow_forward_rounded),
                    label: const Text('Next'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _reviewBtn('Hard', AppColors.error, () => _nextCard(cards)),
                _reviewBtn('Good', AppColors.warning, () => _nextCard(cards)),
                _reviewBtn('Easy', AppColors.success, () => _nextCard(cards)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _nextCard(List<FlashcardItem> cards) {
    if (_index < cards.length - 1) {
      setState(() {
        _index++;
        _flipped = false;
      });
    }
  }

  Widget _reviewBtn(String label, Color color, VoidCallback onTap) {
    return OutlinedButton(
      onPressed: onTap,
      style: OutlinedButton.styleFrom(foregroundColor: color, side: BorderSide(color: color)),
      child: Text(label),
    );
  }
}
