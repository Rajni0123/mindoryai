import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../data/quiz_categories.dart';
import '../../providers/app_data_provider.dart';

const _requiredColor = Color(0xFFEF4444);

const _difficulties = ['Easy', 'Medium', 'Hard'];
const _durations = [
  (5, '5 Minutes'),
  (10, '10 Minutes'),
  (15, '15 Minutes'),
  (20, '20 Minutes'),
];

class QuizTopicScreen extends ConsumerStatefulWidget {
  const QuizTopicScreen({super.key});

  @override
  ConsumerState<QuizTopicScreen> createState() => _QuizTopicScreenState();
}

class _QuizTopicScreenState extends ConsumerState<QuizTopicScreen> {
  int? _examIndex;
  int? _subjectIndex;
  String? _topic;
  String? _difficulty;
  int? _durationMinutes;
  String _language = 'english';
  bool _languageInitialized = false;

  QuizExamCategory? get _exam =>
      _examIndex != null ? quizExamCategories[_examIndex!] : null;

  QuizSubjectGroup? get _subject {
    final exam = _exam;
    if (exam == null || _subjectIndex == null) return null;
    return exam.subjects[_subjectIndex!];
  }

  bool get _canStart =>
      _exam != null &&
      _subject != null &&
      _topic != null &&
      _difficulty != null &&
      _durationMinutes != null;

  @override
  Widget build(BuildContext context) {
    if (!_languageInitialized) {
      _languageInitialized = true;
      final lang = ref.read(languageProvider.notifier).apiValue;
      if (lang == 'hindi' || lang == 'english') {
        _language = lang;
      }
    }

    final c = context.dash;

    return Scaffold(
      backgroundColor: c.background,
      appBar: AppBar(
        backgroundColor: c.background,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        title: Text(
          'Mock Test',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w800,
            fontSize: 18,
            color: c.textPrimary,
          ),
        ),
        centerTitle: false,
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 100),
              children: [
                _SetupRow(
                  icon: Icons.description_outlined,
                  title: 'EXAM',
                  required: true,
                  subtitle: _exam?.name ?? 'Select exam',
                  enabled: true,
                  locked: false,
                  onTap: _pickExam,
                ),
                const SizedBox(height: 10),
                _SetupRow(
                  icon: Icons.menu_book_outlined,
                  title: 'SUBJECT',
                  required: true,
                  subtitle: _subject?.name ??
                      (_exam == null ? 'Select exam first' : 'Select subject'),
                  enabled: _exam != null,
                  locked: _exam == null,
                  onTap: _pickSubject,
                ),
                const SizedBox(height: 10),
                _SetupRow(
                  icon: Icons.bookmark_outline_rounded,
                  title: 'TOPIC',
                  required: true,
                  subtitle: _topic ??
                      (_subject == null ? 'Select subject first' : 'Select topic'),
                  enabled: _subject != null,
                  locked: _subject == null,
                  onTap: _pickTopic,
                ),
                const SizedBox(height: 10),
                _SetupRow(
                  icon: Icons.trending_up_rounded,
                  title: 'DIFFICULTY LEVEL',
                  required: false,
                  subtitle: _difficulty ??
                      (_topic == null ? 'Select topic first' : 'Select difficulty'),
                  enabled: _topic != null,
                  locked: _topic == null,
                  onTap: _pickDifficulty,
                ),
                const SizedBox(height: 16),
                _LanguageSection(
                  selected: _language,
                  onChanged: (v) => setState(() => _language = v),
                ),
                const SizedBox(height: 10),
                _SetupRow(
                  icon: Icons.schedule_rounded,
                  title: 'DURATION',
                  required: true,
                  subtitle: _durationMinutes != null
                      ? '$_durationMinutes Minutes'
                      : 'Select duration',
                  enabled: true,
                  locked: false,
                  onTap: _pickDuration,
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _canStart ? _startQuiz : null,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      disabledBackgroundColor:
                          AppColors.primary.withValues(alpha: 0.35),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                      elevation: 0,
                    ),
                    child: Text(
                      'Start Mock Test',
                      style: GoogleFonts.plusJakartaSans(
                        fontWeight: FontWeight.w700,
                        fontSize: 16,
                      ),
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

  void _startQuiz() {
    final exam = _exam!;
    final subject = _subject!;
    AppRouter.go(context, AppRoutes.quiz, args: {
      'topic': _topic!,
      'subject': subject.name,
      'examType': exam.name,
      'language': _language,
      'difficulty': _difficulty,
      'durationMinutes': _durationMinutes,
    });
  }

  Future<void> _pickExam() async {
    final picked = await _showOptionsSheet<int>(
      title: 'Select Exam',
      options: List.generate(
        quizExamCategories.length,
        (i) => _SheetOption(
          value: i,
          label: quizExamCategories[i].name,
          subtitle: quizExamCategories[i].subtitle,
        ),
      ),
    );
    if (picked == null) return;
    setState(() {
      _examIndex = picked;
      _subjectIndex = null;
      _topic = null;
      _difficulty = null;
    });
  }

  Future<void> _pickSubject() async {
    final exam = _exam;
    if (exam == null) return;
    final picked = await _showOptionsSheet<int>(
      title: 'Select Subject',
      options: List.generate(
        exam.subjects.length,
        (i) => _SheetOption(value: i, label: exam.subjects[i].name),
      ),
    );
    if (picked == null) return;
    setState(() {
      _subjectIndex = picked;
      _topic = null;
      _difficulty = null;
    });
  }

  Future<void> _pickTopic() async {
    final subject = _subject;
    if (subject == null) return;
    final picked = await _showOptionsSheet<String>(
      title: 'Select Topic',
      options: subject.topics
          .map((t) => _SheetOption(value: t, label: t))
          .toList(),
    );
    if (picked == null) return;
    setState(() {
      _topic = picked;
      _difficulty = null;
    });
  }

  Future<void> _pickDifficulty() async {
    if (_topic == null) return;
    final picked = await _showOptionsSheet<String>(
      title: 'Difficulty Level',
      options: _difficulties
          .map((d) => _SheetOption(value: d, label: d))
          .toList(),
    );
    if (picked == null) return;
    setState(() => _difficulty = picked);
  }

  Future<void> _pickDuration() async {
    final picked = await _showOptionsSheet<int>(
      title: 'Select Duration',
      options: _durations
          .map((d) => _SheetOption(value: d.$1, label: d.$2))
          .toList(),
    );
    if (picked == null) return;
    setState(() => _durationMinutes = picked);
  }

  Future<T?> _showOptionsSheet<T>({
    required String title,
    required List<_SheetOption<T>> options,
  }) {
    final c = context.dash;
    return showModalBottomSheet<T>(
      context: context,
      backgroundColor: c.card,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 8),
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: c.cardBorder,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
              child: Text(
                title,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 17,
                  fontWeight: FontWeight.w800,
                  color: c.textPrimary,
                ),
              ),
            ),
            Flexible(
              child: ListView.separated(
                shrinkWrap: true,
                padding: const EdgeInsets.fromLTRB(12, 0, 12, 16),
                itemCount: options.length,
                separatorBuilder: (_, __) => const SizedBox(height: 4),
                itemBuilder: (_, i) {
                  final opt = options[i];
                  return ListTile(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    tileColor: c.surface,
                    title: Text(
                      opt.label,
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        color: c.textPrimary,
                      ),
                    ),
                    subtitle: opt.subtitle != null
                        ? Text(
                            opt.subtitle!,
                            style: TextStyle(fontSize: 12, color: c.textMuted),
                          )
                        : null,
                    trailing: Icon(
                      Icons.chevron_right_rounded,
                      color: c.textMuted,
                    ),
                    onTap: () => Navigator.pop(ctx, opt.value),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SheetOption<T> {
  final T value;
  final String label;
  final String? subtitle;

  const _SheetOption({
    required this.value,
    required this.label,
    this.subtitle,
  });
}

class _SetupRow extends StatelessWidget {
  final IconData icon;
  final String title;
  final bool required;
  final String subtitle;
  final bool enabled;
  final bool locked;
  final VoidCallback onTap;

  const _SetupRow({
    required this.icon,
    required this.title,
    required this.required,
    required this.subtitle,
    required this.enabled,
    required this.locked,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final hasValue = !locked &&
        subtitle != 'Select exam' &&
        subtitle != 'Select exam first' &&
        subtitle != 'Select subject' &&
        subtitle != 'Select subject first' &&
        subtitle != 'Select topic' &&
        subtitle != 'Select topic first' &&
        subtitle != 'Select difficulty' &&
        subtitle != 'Select duration';

    return Material(
      color: c.card,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: enabled ? onTap : null,
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: c.cardBorder),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(
                  alpha: context.isDark ? 0.2 : 0.04,
                ),
                blurRadius: 10,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: context.isDark
                      ? c.cardBorder.withValues(alpha: 0.45)
                      : const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  icon,
                  size: 22,
                  color: enabled ? c.textSecondary : c.textMuted,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text(
                          title,
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.6,
                            color: enabled ? c.textPrimary : c.textMuted,
                          ),
                        ),
                        if (required) ...[
                          const SizedBox(width: 2),
                          const Text(
                            '*',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: _requiredColor,
                            ),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 3),
                    Text(
                      subtitle,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: hasValue ? FontWeight.w600 : FontWeight.w400,
                        color: hasValue ? c.textPrimary : c.textMuted,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(
                locked ? Icons.lock_outline_rounded : Icons.chevron_right_rounded,
                size: 22,
                color: locked ? c.textMuted.withValues(alpha: 0.5) : c.textMuted,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _LanguageSection extends StatelessWidget {
  final String selected;
  final ValueChanged<String> onChanged;

  const _LanguageSection({
    required this.selected,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              'LANGUAGE',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 11,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.6,
                color: c.textPrimary,
              ),
            ),
            const SizedBox(width: 2),
            const Text(
              '*',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: _requiredColor,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _LanguageChip(
                label: 'English',
                icon: Icons.language_rounded,
                selected: selected == 'english',
                onTap: () => onChanged('english'),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _LanguageChip(
                label: 'Hindi',
                icon: Icons.translate_rounded,
                selected: selected == 'hindi',
                onTap: () => onChanged('hindi'),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _LanguageChip extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool selected;
  final VoidCallback onTap;

  const _LanguageChip({
    required this.label,
    required this.icon,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final borderColor =
        selected ? AppColors.primary : c.cardBorder;
    final fg = selected ? AppColors.primary : c.textSecondary;

    return Material(
      color: c.card,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: borderColor, width: selected ? 2 : 1),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(
                  alpha: context.isDark ? 0.15 : 0.03,
                ),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 20, color: fg),
              const SizedBox(width: 8),
              Text(
                label,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: fg,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
