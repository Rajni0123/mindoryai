import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../core/utils/study_profile_utils.dart';
import '../../providers/app_data_provider.dart';
import '../../providers/providers.dart';
import '../../widgets/common_widgets.dart';
import '../../widgets/exam_search_picker.dart';

class EditProfileScreen extends ConsumerStatefulWidget {
  const EditProfileScreen({super.key});

  @override
  ConsumerState<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  final _nameCtrl = TextEditingController();
  final _mobileCtrl = TextEditingController();

  String? _exam;
  late String _class;
  late String _subjects;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final user = ref.read(authProvider).user;
    final prefs = ref.read(userStudyPreferencesProvider);

    _nameCtrl.text = user?.name ?? '';
    _mobileCtrl.text = user?.mobile ?? '';

    final savedExam = user?.targetExam ?? prefs.targetExam;
    if (StudyProfileUtils.isKnownExam(savedExam)) {
      _exam = StudyProfileUtils.normalizeExam(savedExam);
    }

    _class = user?.studentClass ?? prefs.studentClass;
    if (!StudyProfileUtils.setupClasses.contains(_class)) _class = '12';

    final subjectOptions = StudyProfileUtils.subjectsForExam(_exam ?? '');
    final savedSubjects = user?.subjects ?? prefs.subjects;
    _subjects = savedSubjects.isNotEmpty && subjectOptions.contains(savedSubjects)
        ? savedSubjects
        : subjectOptions.first;
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _mobileCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final name = _nameCtrl.text.trim();
    if (name.length < 2) {
      _snack('Enter a valid name (min 2 characters)');
      return;
    }

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
      await ref.read(apiServiceProvider).updateProfile(
            name: name,
            targetExam: _exam,
            studentClass: studentClass,
            subjects: subjects,
          );

      await ref.read(userStudyPreferencesProvider.notifier).save(
            targetExam: _exam!,
            studentClass: studentClass,
            subjects: subjects,
          );

      await ref.read(authProvider.notifier).refreshUser();
      ref.read(homeDashboardProvider.notifier).load();

      if (mounted) {
        _snack('Profile updated');
        Navigator.pop(context);
      }
    } catch (_) {
      if (mounted) _snack('Could not update profile. Try again.');
    } finally {
      if (mounted) setState(() => _saving = false);
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
    final user = ref.watch(authProvider).user;
    final displayEmail = StudyProfileUtils.displayEmail(user?.email);

    return Scaffold(
      appBar: AppBar(title: const Text('Edit Profile')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Center(
            child: CircleAvatar(
              radius: 48,
              backgroundColor: AppColors.primary.withValues(alpha: 0.15),
              backgroundImage:
                  user?.avatar != null ? NetworkImage(user!.avatar!) : null,
              child: user?.avatar == null
                  ? Text(
                      _initials(user?.name),
                      style: const TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.w700,
                        color: AppColors.primary,
                      ),
                    )
                  : null,
            ),
          ),
          const SizedBox(height: 28),
          _field('Full Name', _nameCtrl, Icons.person_outline_rounded),
          const SizedBox(height: 12),
          _field('Mobile', _mobileCtrl, Icons.phone_outlined, readOnly: true),
          const SizedBox(height: 8),
          Text(
            'Mobile is linked to your OTP login and cannot be changed here.',
            style: TextStyle(fontSize: 12, color: c.textMuted),
          ),
          if (displayEmail != null) ...[
            const SizedBox(height: 12),
            AppCard(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              child: Row(
                children: [
                  const Icon(Icons.email_outlined,
                      color: AppColors.primary, size: 22),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Email',
                            style: TextStyle(
                                fontSize: 12, color: c.textMuted)),
                        const SizedBox(height: 2),
                        Text(displayEmail,
                            style: TextStyle(
                                fontWeight: FontWeight.w500,
                                color: c.textPrimary)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 24),
          const SectionTitle(title: 'Preparation'),
          const SizedBox(height: 4),
          Text(
            'Update what you are preparing for',
            style: TextStyle(fontSize: 13, color: c.textMuted),
          ),
          const SizedBox(height: 12),
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
          const SizedBox(height: 28),
          SizedBox(
            width: double.infinity,
            height: 52,
            child: ElevatedButton(
              onPressed: _saving ? null : _save,
              child: Text(_saving ? 'Saving...' : 'Save Changes'),
            ),
          ),
        ],
      ),
    );
  }

  String _initials(String? name) {
    if (name == null || name.trim().isEmpty) return 'S';
    return name.trim()[0].toUpperCase();
  }

  Widget _field(
    String label,
    TextEditingController ctrl,
    IconData icon, {
    bool readOnly = false,
  }) {
    final c = context.dash;
    return AppCard(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      child: TextField(
        controller: ctrl,
        readOnly: readOnly,
        style: TextStyle(color: c.textPrimary, fontWeight: FontWeight.w500),
        decoration: AppInputDecoration.insideCard(
          context,
          labelText: label,
          prefixIcon: icon,
        ),
      ),
    );
  }
}
