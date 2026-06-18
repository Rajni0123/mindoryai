import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/utils/study_profile_utils.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/app_data_provider.dart';
import '../../providers/providers.dart';
import '../../widgets/common_widgets.dart';
import '../../widgets/exam_search_picker.dart';

class ExamGoalScreen extends ConsumerStatefulWidget {
  const ExamGoalScreen({super.key});

  @override
  ConsumerState<ExamGoalScreen> createState() => _ExamGoalScreenState();
}

class _ExamGoalScreenState extends ConsumerState<ExamGoalScreen> {
  String? _exam;
  late String _class;
  late String _subjects;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final prefs = ref.read(userStudyPreferencesProvider);
    final user = ref.read(authProvider).user;

    final savedExam = user?.targetExam ?? prefs.targetExam;
    if (StudyProfileUtils.isKnownExam(savedExam)) {
      _exam = StudyProfileUtils.normalizeExam(savedExam);
    }

    _class = user?.studentClass ?? prefs.studentClass;
    if (!StudyProfileUtils.setupClasses.contains(_class)) _class = '12';

    final options = StudyProfileUtils.subjectsForExam(_exam ?? '');
    final savedSubjects = prefs.subjects;
    _subjects = savedSubjects.isNotEmpty && options.contains(savedSubjects)
        ? savedSubjects
        : options.first;
  }

  Future<void> _save() async {
    if (_exam == null || !StudyProfileUtils.isKnownExam(_exam!)) {
      _snack('Select your target exam from the search list');
      return;
    }

    final needsBoard = StudyProfileUtils.requiresBoardSetup(_exam!);
    final studentClass =
        needsBoard ? _class : StudyProfileUtils.defaultClassForExam(_exam!);
    final subjects =
        needsBoard ? _subjects : StudyProfileUtils.defaultSubjectsForExam(_exam!);

    setState(() => _saving = true);
    try {
      final user = ref.read(authProvider).user;
      final name = user?.name.trim();
      if (name != null &&
          name.isNotEmpty &&
          !RegExp(r'^User \d{4}$').hasMatch(name)) {
        await ref.read(apiServiceProvider).updateProfile(
              name: name,
              targetExam: _exam,
              studentClass: studentClass,
              subjects: subjects,
            );
      } else {
        await ref.read(apiServiceProvider).updateProfile(
              targetExam: _exam,
              studentClass: studentClass,
              subjects: subjects,
            );
      }

      await ref.read(userStudyPreferencesProvider.notifier).save(
            targetExam: _exam!,
            studentClass: studentClass,
            subjects: subjects,
          );
      await ref.read(authProvider.notifier).refreshUser();
      ref.read(homeDashboardProvider.notifier).load();
    } catch (_) {
      if (mounted) _snack('Saved locally. Sync when online.');
      await ref.read(userStudyPreferencesProvider.notifier).save(
            targetExam: _exam!,
            studentClass: studentClass,
            subjects: subjects,
          );
    }
    if (mounted) {
      setState(() => _saving = false);
      _snack('Study profile updated');
      Navigator.pop(context);
    }
  }

  void _snack(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  void _onExamSelected(String exam) {
    setState(() {
      _exam = exam;
      final options = StudyProfileUtils.subjectsForExam(exam);
      if (!options.contains(_subjects)) _subjects = options.first;
    });
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;

    return Scaffold(
      appBar: AppBar(title: const Text('Exam Goal')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            'Set your target exam so BlinkStudy can personalize your study plan.',
            style: TextStyle(fontSize: 14, color: c.textMuted, height: 1.4),
          ),
          const SizedBox(height: 20),
          const SectionTitle(title: 'What are you preparing for?'),
          const SizedBox(height: 8),
          AppCard(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
            child: ExamSearchPicker(
              selectedExam: _exam,
              onExamSelected: _onExamSelected,
              onExamCleared: () => setState(() => _exam = null),
              classLevel: _class,
              subjects: _subjects,
              onClassChanged: (v) => setState(() => _class = v),
              onSubjectsChanged: (v) => setState(() => _subjects = v),
            ),
          ),
          const SizedBox(height: 32),
          SizedBox(
            width: double.infinity,
            height: 52,
            child: ElevatedButton(
              onPressed: _saving ? null : _save,
              child: Text(_saving ? 'Saving...' : 'Save Exam Goal'),
            ),
          ),
        ],
      ),
    );
  }
}
