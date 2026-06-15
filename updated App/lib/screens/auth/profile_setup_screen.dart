import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../core/utils/study_profile_utils.dart';
import '../../providers/app_data_provider.dart';
import '../../providers/providers.dart';

class ProfileSetupScreen extends ConsumerStatefulWidget {
  const ProfileSetupScreen({super.key});

  @override
  ConsumerState<ProfileSetupScreen> createState() => _ProfileSetupScreenState();
}

class _ProfileSetupScreenState extends ConsumerState<ProfileSetupScreen> {
  final _nameController = TextEditingController();
  final _examSearchController = TextEditingController();
  final _examFocusNode = FocusNode();

  String? _exam;
  late String _class;
  late String _subjects;
  bool _saving = false;
  bool _showSuggestions = false;

  @override
  void initState() {
    super.initState();
    final user = ref.read(authProvider).user;
    final prefs = ref.read(userStudyPreferencesProvider);
    _nameController.text = _defaultName(user?.name);

    final savedExam = user?.targetExam ?? prefs.targetExam;
    if (StudyProfileUtils.isKnownExam(savedExam)) {
      _exam = StudyProfileUtils.normalizeExam(savedExam);
      _examSearchController.text = _exam!;
    }

    _class = user?.studentClass ?? prefs.studentClass;
    if (!StudyProfileUtils.setupClasses.contains(_class)) {
      _class = '12';
    }
    _syncSubjectsForExam(_exam ?? StudyProfileUtils.defaultExams.first, prefs.subjects);

    _examSearchController.addListener(_onExamSearchChanged);
    _examFocusNode.addListener(_onExamFocusChanged);
  }

  void _onExamSearchChanged() {
    final text = _examSearchController.text;
    if (_exam != null && text != _exam) {
      setState(() => _exam = null);
    }
    if (_showSuggestions || _examFocusNode.hasFocus) {
      setState(() => _showSuggestions = true);
    }
  }

  void _onExamFocusChanged() {
    if (_examFocusNode.hasFocus) {
      setState(() => _showSuggestions = true);
    }
  }

  void _syncSubjectsForExam(String exam, String? fallback) {
    final options = StudyProfileUtils.subjectsForExam(exam);
    _subjects = fallback != null && options.contains(fallback)
        ? fallback
        : options.first;
  }

  String _defaultName(String? name) {
    if (name == null || name.trim().isEmpty) return '';
    if (RegExp(r'^User \d{4}$').hasMatch(name.trim())) return '';
    return name.trim();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _examSearchController
      ..removeListener(_onExamSearchChanged)
      ..dispose();
    _examFocusNode
      ..removeListener(_onExamFocusChanged)
      ..dispose();
    super.dispose();
  }

  List<String> get _filteredExams =>
      StudyProfileUtils.searchExams(_examSearchController.text);

  void _selectExam(String exam) {
    setState(() {
      _exam = exam;
      _examSearchController.text = exam;
      _showSuggestions = false;
      _syncSubjectsForExam(exam, _subjects);
    });
    _examFocusNode.unfocus();
  }

  Future<void> _submit() async {
    final name = _nameController.text.trim();
    if (name.length < 2) {
      _toast('Enter your name to continue');
      return;
    }

    final exam = _exam ?? StudyProfileUtils.searchExams(_examSearchController.text).firstOrNull;
    if (exam == null || !StudyProfileUtils.isKnownExam(exam)) {
      _toast('Search and select your exam from the list');
      setState(() => _showSuggestions = true);
      _examFocusNode.requestFocus();
      return;
    }

    final needsBoard = StudyProfileUtils.requiresBoardSetup(exam);
    final studentClass =
        needsBoard ? _class : StudyProfileUtils.defaultClassForExam(exam);
    final subjects =
        needsBoard ? _subjects : StudyProfileUtils.defaultSubjectsForExam(exam);

    setState(() => _saving = true);
    try {
      await ref.read(apiServiceProvider).completeStudyProfile(
            name: name,
            targetExam: exam,
            studentClass: studentClass,
            subjects: subjects,
          );

      await ref.read(userStudyPreferencesProvider.notifier).save(
            targetExam: exam,
            studentClass: studentClass,
            subjects: subjects,
          );

      await ref.read(authProvider.notifier).refreshUser();
      ref.read(authProvider.notifier).markStudySetupComplete();
      ref.read(homeDashboardProvider.notifier).load();

      if (!mounted) return;
      AppRouter.goClear(context, AppRoutes.main);
    } catch (e) {
      if (mounted) _toast('Could not save profile. Try again.');
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _toast(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), behavior: SnackBarBehavior.floating),
    );
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final needsBoard =
        _exam != null && StudyProfileUtils.requiresBoardSetup(_exam!);
    final suggestions = _filteredExams;

    return Scaffold(
      backgroundColor: c.background,
      body: SafeArea(
        child: GestureDetector(
          onTap: () {
            _examFocusNode.unfocus();
            setState(() => _showSuggestions = false);
          },
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        gradient: AppColors.primaryGradient,
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: const Icon(
                        Icons.school_rounded,
                        color: Colors.white,
                        size: 22,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Quick Setup',
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 20,
                              fontWeight: FontWeight.w800,
                              color: c.textPrimary,
                            ),
                          ),
                          Text(
                            'Personalize BlinkStudy in 30 seconds',
                            style: TextStyle(fontSize: 12, color: c.textMuted),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Expanded(
                  child: SingleChildScrollView(
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: c.card,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: c.cardBorder),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          _fieldLabel('Your name'),
                          const SizedBox(height: 6),
                          TextField(
                            controller: _nameController,
                            style: TextStyle(
                              fontWeight: FontWeight.w600,
                              color: c.textPrimary,
                            ),
                            decoration: _inputDecoration(
                              c,
                              hint: 'Enter your name',
                              icon: Icons.person_outline_rounded,
                            ),
                          ),
                          const SizedBox(height: 16),
                          _fieldLabel('What are you preparing for?'),
                          const SizedBox(height: 6),
                          TextField(
                            controller: _examSearchController,
                            focusNode: _examFocusNode,
                            style: TextStyle(
                              fontWeight: FontWeight.w600,
                              color: c.textPrimary,
                            ),
                            decoration: _inputDecoration(
                              c,
                              hint: 'Search — JEE, UPSC, SSC, RRB, IBPS...',
                              icon: Icons.search_rounded,
                              suffix: _exam != null
                                  ? Icon(
                                      Icons.check_circle_rounded,
                                      color: AppColors.primary,
                                      size: 20,
                                    )
                                  : null,
                            ),
                            onTap: () => setState(() => _showSuggestions = true),
                          ),
                          if (_showSuggestions && suggestions.isNotEmpty) ...[
                            const SizedBox(height: 8),
                            _ExamSuggestionList(
                              exams: suggestions.take(12).toList(),
                              selected: _exam,
                              onSelect: _selectExam,
                            ),
                          ],
                          if (_exam != null && !_showSuggestions) ...[
                            const SizedBox(height: 8),
                            _SelectedExamChip(
                              exam: _exam!,
                              onClear: () {
                                setState(() {
                                  _exam = null;
                                  _examSearchController.clear();
                                  _showSuggestions = true;
                                });
                                _examFocusNode.requestFocus();
                              },
                            ),
                          ],
                          if (needsBoard) ...[
                            const SizedBox(height: 16),
                            Row(
                              children: [
                                Expanded(
                                  child: _compactDropdown(
                                    label: 'Class',
                                    value: _class,
                                    items: StudyProfileUtils.setupClasses,
                                    labelBuilder: (v) => 'Class $v',
                                    onChanged: (v) => setState(() => _class = v),
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: _compactDropdown(
                                    label: 'Subjects',
                                    value: _subjects,
                                    items: StudyProfileUtils.subjectsForExam(_exam!),
                                    onChanged: (v) => setState(() => _subjects = v),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Text(
                              'Select your class and subject stream',
                              style: TextStyle(fontSize: 11, color: c.textMuted),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 14),
                SizedBox(
                  height: 50,
                  child: ElevatedButton(
                    onPressed: _saving ? null : _submit,
                    child: Text(
                      _saving ? 'Personalizing...' : 'Start Learning',
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  InputDecoration _inputDecoration(
    DashboardColors c, {
    required String hint,
    required IconData icon,
    Widget? suffix,
  }) {
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: c.textMuted, fontSize: 13),
      filled: true,
      fillColor: c.background,
      prefixIcon: Icon(icon, size: 20, color: c.textMuted),
      suffixIcon: suffix,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: c.cardBorder),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: c.cardBorder),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
      ),
    );
  }

  Widget _fieldLabel(String text) {
    return Text(
      text,
      style: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w700,
        color: context.dash.textMuted,
        letterSpacing: 0.3,
      ),
    );
  }

  Widget _compactDropdown({
    required String label,
    required String value,
    required List<String> items,
    required ValueChanged<String> onChanged,
    String Function(String)? labelBuilder,
  }) {
    final c = context.dash;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _fieldLabel(label),
        const SizedBox(height: 6),
        DropdownButtonFormField<String>(
          initialValue: items.contains(value) ? value : items.first,
          isExpanded: true,
          decoration: InputDecoration(
            filled: true,
            fillColor: c.background,
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: c.cardBorder),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: c.cardBorder),
            ),
          ),
          dropdownColor: c.card,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: c.textPrimary,
          ),
          items: items
              .map(
                (e) => DropdownMenuItem(
                  value: e,
                  child: Text(labelBuilder?.call(e) ?? e),
                ),
              )
              .toList(),
          onChanged: (v) {
            if (v != null) onChanged(v);
          },
        ),
      ],
    );
  }
}

class _ExamSuggestionList extends StatelessWidget {
  const _ExamSuggestionList({
    required this.exams,
    required this.selected,
    required this.onSelect,
  });

  final List<String> exams;
  final String? selected;
  final ValueChanged<String> onSelect;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Material(
      color: c.background,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: c.cardBorder),
        ),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxHeight: 260),
          child: ListView.separated(
            shrinkWrap: true,
            padding: EdgeInsets.zero,
            itemCount: exams.length,
            separatorBuilder: (_, __) => Divider(height: 1, color: c.cardBorder),
            itemBuilder: (context, index) {
              final exam = exams[index];
              final isSelected = selected == exam;
              return InkWell(
                onTap: () => onSelect(exam),
                child: Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                  child: Row(
                    children: [
                      Icon(
                        Icons.school_outlined,
                        size: 18,
                        color: isSelected ? AppColors.primary : c.textMuted,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          exam,
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight:
                                isSelected ? FontWeight.w700 : FontWeight.w600,
                            color:
                                isSelected ? AppColors.primary : c.textPrimary,
                          ),
                        ),
                      ),
                      if (isSelected)
                        const Icon(
                          Icons.check_rounded,
                          size: 18,
                          color: AppColors.primary,
                        ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}

class _SelectedExamChip extends StatelessWidget {
  const _SelectedExamChip({
    required this.exam,
    required this.onClear,
  });

  final String exam;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          const Icon(Icons.verified_rounded, size: 16, color: AppColors.primary),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              exam,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: AppColors.primary,
              ),
            ),
          ),
          GestureDetector(
            onTap: onClear,
            child: Icon(
              Icons.close_rounded,
              size: 16,
              color: context.dash.textMuted,
            ),
          ),
        ],
      ),
    );
  }
}

extension on List<String> {
  String? get firstOrNull => isEmpty ? null : first;
}
