import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../data/quiz_categories.dart';
import '../../providers/app_data_provider.dart';
import '../../widgets/common_widgets.dart';

const _quizLanguages = [
  ('english', 'English', '🇬🇧'),
  ('hindi', 'हिंदी', '🇮🇳'),
  ('hinglish', 'Hinglish', '🔀'),
];

class QuizTopicScreen extends ConsumerStatefulWidget {
  const QuizTopicScreen({super.key});

  @override
  ConsumerState<QuizTopicScreen> createState() => _QuizTopicScreenState();
}

class _QuizTopicScreenState extends ConsumerState<QuizTopicScreen> {
  int _selectedExam = 0;
  String _selectedLanguage = 'english';
  bool _languageInitialized = false;

  String _languageLabel(String code) {
    return _quizLanguages
        .firstWhere((l) => l.$1 == code, orElse: () => _quizLanguages.first)
        .$2;
  }

  @override
  Widget build(BuildContext context) {
    if (!_languageInitialized) {
      _languageInitialized = true;
      _selectedLanguage = ref.read(languageProvider.notifier).apiValue;
    }

    final c = context.dash;
    final exam = quizExamCategories[_selectedExam];

    return Scaffold(
      backgroundColor: c.background,
      appBar: AppBar(
        title: const Text('Practice Quiz'),
        centerTitle: false,
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
            child: AppCard(
              gradient: AppColors.primaryGradient,
              child: const Row(
                children: [
                  Icon(Icons.auto_awesome_rounded, color: Colors.white, size: 32),
                  SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'AI Quiz Engine',
                          style: TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                            fontSize: 18,
                          ),
                        ),
                        SizedBox(height: 4),
                        Text(
                          'Competitive exam practice with instant feedback',
                          style: TextStyle(color: Colors.white70, fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Quiz language',
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 14,
                    color: c.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _quizLanguages.map((lang) {
                    final selected = _selectedLanguage == lang.$1;
                    return ChoiceChip(
                      label: Text('${lang.$3} ${lang.$2}'),
                      selected: selected,
                      onSelected: (_) => setState(() => _selectedLanguage = lang.$1),
                      selectedColor: AppColors.primary.withValues(alpha: 0.18),
                      labelStyle: TextStyle(
                        fontWeight: FontWeight.w600,
                        color: selected ? AppColors.primary : c.textSecondary,
                      ),
                      side: BorderSide(
                        color: selected ? AppColors.primary : c.cardBorder,
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 4),
                Text(
                  'Questions will be generated in $_languageLabel($_selectedLanguage)',
                  style: TextStyle(fontSize: 12, color: c.textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          SizedBox(
            height: 44,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 20),
              itemCount: quizExamCategories.length,
              separatorBuilder: (_, __) => const SizedBox(width: 8),
              itemBuilder: (context, i) {
                final cat = quizExamCategories[i];
                final selected = i == _selectedExam;
                return ChoiceChip(
                  label: Text(cat.name),
                  selected: selected,
                  onSelected: (_) => setState(() => _selectedExam = i),
                  selectedColor: cat.color.withValues(alpha: 0.2),
                  labelStyle: TextStyle(
                    fontWeight: FontWeight.w600,
                    color: selected ? cat.color : c.textSecondary,
                  ),
                  side: BorderSide(
                    color: selected ? cat.color : c.cardBorder,
                  ),
                );
              },
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 14, 20, 6),
            child: Row(
              children: [
                Icon(exam.icon, color: exam.color, size: 22),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        exam.name,
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 17,
                          color: c.textPrimary,
                        ),
                      ),
                      Text(
                        exam.subtitle,
                        style: TextStyle(fontSize: 12, color: c.textMuted),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
              itemCount: exam.subjects.length,
              itemBuilder: (context, index) {
                final subject = exam.subjects[index];
                return _SubjectBlock(
                  exam: exam,
                  subject: subject,
                  index: index,
                  language: _selectedLanguage,
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _SubjectBlock extends StatelessWidget {
  final QuizExamCategory exam;
  final QuizSubjectGroup subject;
  final int index;
  final String language;

  const _SubjectBlock({
    required this.exam,
    required this.subject,
    required this.index,
    required this.language,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: AppCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: exam.color.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(subject.icon, color: exam.color, size: 20),
                ),
                const SizedBox(width: 10),
                Text(
                  subject.name,
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                    color: c.textPrimary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: subject.topics.map((topic) {
                return ActionChip(
                  label: Text(topic),
                  backgroundColor: exam.color.withValues(alpha: 0.08),
                  side: BorderSide(color: exam.color.withValues(alpha: 0.25)),
                  labelStyle: TextStyle(
                    color: exam.color,
                    fontWeight: FontWeight.w500,
                    fontSize: 13,
                  ),
                  onPressed: () => AppRouter.go(
                    context,
                    AppRoutes.quiz,
                    args: {
                      'topic': topic,
                      'subject': subject.name,
                      'examType': exam.name,
                      'language': language,
                    },
                  ),
                );
              }).toList(),
            ),
          ],
        ),
      ),
    )
        .animate(delay: (60 * index).ms)
        .fadeIn(duration: 400.ms)
        .slideY(begin: 0.04, end: 0);
  }
}
