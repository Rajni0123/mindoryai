import 'package:flutter/material.dart';
import '../core/theme/app_theme.dart';
import '../core/theme/dashboard_theme.dart';
import '../core/utils/study_profile_utils.dart';

class ExamSearchPicker extends StatefulWidget {
  const ExamSearchPicker({
    super.key,
    this.selectedExam,
    required this.onExamSelected,
    this.onExamCleared,
    this.showBoardFields = true,
    this.classLevel = '12',
    this.subjects = 'PCM',
    this.onClassChanged,
    this.onSubjectsChanged,
  });

  final String? selectedExam;
  final ValueChanged<String> onExamSelected;
  final VoidCallback? onExamCleared;
  final bool showBoardFields;
  final String classLevel;
  final String subjects;
  final ValueChanged<String>? onClassChanged;
  final ValueChanged<String>? onSubjectsChanged;

  @override
  State<ExamSearchPicker> createState() => _ExamSearchPickerState();
}

class _ExamSearchPickerState extends State<ExamSearchPicker> {
  final _searchController = TextEditingController();
  final _focusNode = FocusNode();
  bool _showSuggestions = false;

  @override
  void initState() {
    super.initState();
    if (widget.selectedExam != null) {
      _searchController.text = widget.selectedExam!;
    }
    _searchController.addListener(_onSearchChanged);
    _focusNode.addListener(_onFocusChanged);
  }

  @override
  void didUpdateWidget(ExamSearchPicker oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.selectedExam != null &&
        widget.selectedExam != oldWidget.selectedExam &&
        _searchController.text != widget.selectedExam) {
      _searchController.text = widget.selectedExam!;
    }
  }

  void _onSearchChanged() {
    if (widget.selectedExam != null &&
        _searchController.text != widget.selectedExam) {
      // Parent owns selection; typing clears via parent if needed.
    }
    if (_showSuggestions || _focusNode.hasFocus) {
      setState(() => _showSuggestions = true);
    }
  }

  void _onFocusChanged() {
    if (_focusNode.hasFocus) setState(() => _showSuggestions = true);
  }

  @override
  void dispose() {
    _searchController
      ..removeListener(_onSearchChanged)
      ..dispose();
    _focusNode
      ..removeListener(_onFocusChanged)
      ..dispose();
    super.dispose();
  }

  void _select(String exam) {
    _searchController.text = exam;
    widget.onExamSelected(exam);
    setState(() => _showSuggestions = false);
    _focusNode.unfocus();
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final exam = widget.selectedExam;
    final suggestions =
        StudyProfileUtils.searchExams(_searchController.text).take(10).toList();
    final needsBoard =
        exam != null && StudyProfileUtils.requiresBoardSetup(exam);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        TextField(
          controller: _searchController,
          focusNode: _focusNode,
          style: TextStyle(
            fontWeight: FontWeight.w600,
            color: c.textPrimary,
            fontSize: 14,
          ),
          decoration: InputDecoration(
            hintText: 'Search — JEE, UPSC, SSC, RRB, IBPS...',
            hintStyle: TextStyle(color: c.textMuted, fontSize: 13),
            prefixIcon: Icon(Icons.search_rounded, color: c.textMuted, size: 20),
            suffixIcon: exam != null
                ? const Icon(Icons.check_circle_rounded,
                    color: AppColors.primary, size: 20)
                : null,
            filled: false,
            border: InputBorder.none,
            enabledBorder: InputBorder.none,
            focusedBorder: InputBorder.none,
            contentPadding: const EdgeInsets.symmetric(vertical: 4),
          ),
          onTap: () => setState(() => _showSuggestions = true),
          onChanged: (text) {
            if (widget.selectedExam != null && text != widget.selectedExam) {
              widget.onExamCleared?.call();
            }
            setState(() => _showSuggestions = true);
          },
        ),
        if (_showSuggestions && suggestions.isNotEmpty) ...[
          const SizedBox(height: 8),
          _SuggestionList(
            exams: suggestions,
            selected: exam,
            onSelect: _select,
          ),
        ],
        if (exam != null && !_showSuggestions) ...[
          const SizedBox(height: 8),
          _SelectedChip(
            exam: exam,
            onClear: () {
              _searchController.clear();
              widget.onExamCleared?.call();
              setState(() => _showSuggestions = true);
              _focusNode.requestFocus();
            },
          ),
        ],
        if (widget.showBoardFields && needsBoard) ...[
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: _BoardDropdown(
                  label: 'Class',
                  value: widget.classLevel,
                  items: StudyProfileUtils.setupClasses,
                  labelBuilder: (v) => 'Class $v',
                  onChanged: widget.onClassChanged,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _BoardDropdown(
                  label: 'Subjects',
                  value: widget.subjects,
                  items: StudyProfileUtils.subjectsForExam(exam),
                  onChanged: widget.onSubjectsChanged,
                ),
              ),
            ],
          ),
        ],
      ],
    );
  }
}

class _SuggestionList extends StatelessWidget {
  const _SuggestionList({
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
    return Container(
      constraints: const BoxConstraints(maxHeight: 220),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: c.cardBorder),
      ),
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
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              child: Row(
                children: [
                  Icon(
                    Icons.school_outlined,
                    size: 16,
                    color: isSelected ? AppColors.primary : c.textMuted,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      exam,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight:
                            isSelected ? FontWeight.w700 : FontWeight.w600,
                        color: isSelected ? AppColors.primary : c.textPrimary,
                      ),
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

class _SelectedChip extends StatelessWidget {
  const _SelectedChip({required this.exam, required this.onClear});

  final String exam;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.2)),
      ),
      child: Row(
        children: [
          const Icon(Icons.verified_rounded, size: 14, color: AppColors.primary),
          const SizedBox(width: 6),
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
            child: Icon(Icons.close_rounded, size: 14, color: context.dash.textMuted),
          ),
        ],
      ),
    );
  }
}

class _BoardDropdown extends StatelessWidget {
  const _BoardDropdown({
    required this.label,
    required this.value,
    required this.items,
    required this.onChanged,
    this.labelBuilder,
  });

  final String label;
  final String value;
  final List<String> items;
  final ValueChanged<String>? onChanged;
  final String Function(String)? labelBuilder;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w700,
            color: c.textMuted,
          ),
        ),
        const SizedBox(height: 4),
        DropdownButtonFormField<String>(
          initialValue: items.contains(value) ? value : items.first,
          isExpanded: true,
          decoration: const InputDecoration(
            filled: false,
            contentPadding: EdgeInsets.symmetric(horizontal: 0, vertical: 4),
            border: InputBorder.none,
            enabledBorder: InputBorder.none,
            focusedBorder: InputBorder.none,
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
            if (v != null) onChanged?.call(v);
          },
        ),
      ],
    );
  }
}
