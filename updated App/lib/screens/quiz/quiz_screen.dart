import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../exam_prep/test_completed_screen.dart';
import '../../models/models.dart';
import '../../providers/providers.dart';
import '../../widgets/common_widgets.dart';

class QuizScreen extends ConsumerStatefulWidget {
  final String topic;
  final String subject;
  final String examType;
  final String language;

  const QuizScreen({
    super.key,
    this.topic = 'Laws of Motion',
    this.subject = 'Physics',
    this.examType = 'JEE',
    this.language = 'english',
  });

  @override
  ConsumerState<QuizScreen> createState() => _QuizScreenState();
}

class _QuizScreenState extends ConsumerState<QuizScreen> {
  int _currentIndex = 0;
  int? _selectedIndex;
  bool _answered = false;
  int _score = 0;
  List<QuizQuestion> _questions = [];
  bool _loading = true;
  String? _error;
  int _secondsLeft = 600;
  Timer? _timer;
  final DateTime _startedAt = DateTime.now();

  @override
  void initState() {
    super.initState();
    _loadQuiz();
  }

  Future<void> _loadQuiz() async {
    setState(() {
      _loading = true;
      _error = null;
      _questions = [];
    });
    try {
      final api = ref.read(apiServiceProvider);
      final data = await api.generateQuizByTopic(
        topic: widget.topic,
        subject: widget.subject,
        examType: widget.examType,
        count: 10,
        language: widget.language,
      );
      final raw = (data['questions'] as List?) ?? [];
      final qs = raw
          .map((e) => QuizQuestion.fromJson(e as Map<String, dynamic>))
          .where((q) => q.isValid)
          .toList();

      if (qs.isEmpty) {
        throw Exception('No valid questions returned');
      }

      if (!mounted) return;
      setState(() {
        _questions = qs;
        _secondsLeft = qs.length * 60;
        _loading = false;
      });
      _startTimer();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.toString().contains('limit')
            ? 'Daily quiz limit reached. Upgrade your plan.'
            : 'Could not generate quiz. Check connection and try again.';
      });
    }
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (!mounted) return;
      if (_secondsLeft <= 0) {
        t.cancel();
        _showResult();
        return;
      }
      setState(() => _secondsLeft--);
    });
  }

  String get _timerLabel {
    final m = _secondsLeft ~/ 60;
    final s = _secondsLeft % 60;
    return '${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  void _selectAnswer(int index) {
    if (_answered || _questions.isEmpty) return;
    setState(() {
      _selectedIndex = index;
      _answered = true;
      if (index == _questions[_currentIndex].correctIndex) _score++;
    });
  }

  void _nextQuestion() {
    if (_questions.isEmpty) return;
    if (_currentIndex < _questions.length - 1) {
      setState(() {
        _currentIndex++;
        _selectedIndex = null;
        _answered = false;
      });
    } else {
      _showResult();
    }
  }

  Future<void> _showResult() async {
    _timer?.cancel();
    final total = _questions.length;
    if (total == 0) return;
    final wrong = total - _score;
    final elapsed = DateTime.now().difference(_startedAt).inSeconds;

    try {
      final api = ref.read(apiServiceProvider);
      await api.submitQuizAttempt({
        'title': '${widget.examType} — ${widget.subject} — ${widget.topic}',
        'subject': widget.subject,
        'topic': widget.topic,
        'difficulty_level': 'medium',
        'total_questions': total,
        'correct_answers': _score,
        'wrong_answers': wrong,
        'skipped_questions': 0,
        'score': total > 0 ? ((_score / total) * 100).round() : 0,
        'time_taken_seconds': elapsed.clamp(1, 86400),
        'status': 'completed',
      });
    } catch (_) {}

    if (!mounted) return;
    AppRouter.goReplace(
      context,
      AppRoutes.testCompleted,
      args: TestResultArgs(
        score: _score,
        total: total,
        rank: 215,
        rankImprovement: 23,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;

    if (_loading) {
      return Scaffold(
        backgroundColor: c.background,
        appBar: AppBar(title: Text('${widget.examType} Quiz')),
        body: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(
                width: 40,
                height: 40,
                child: CircularProgressIndicator(
                  strokeWidth: 2.5,
                  color: AppColors.primary,
                ),
              ),
              const SizedBox(height: 20),
              Text(
                'Generating AI quiz...',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: c.textPrimary,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                '${widget.subject} • ${widget.topic}',
                style: TextStyle(fontSize: 13, color: c.textMuted),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      );
    }

    if (_error != null) {
      return Scaffold(
        backgroundColor: c.background,
        appBar: AppBar(title: Text('${widget.examType} Quiz')),
        body: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.error_outline_rounded, size: 56, color: c.textMuted),
              const SizedBox(height: 16),
              Text(
                _error!,
                textAlign: TextAlign.center,
                style: TextStyle(color: c.textPrimary, height: 1.4),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _loadQuiz,
                  child: const Text('Try Again'),
                ),
              ),
              const SizedBox(height: 10),
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Go Back'),
              ),
            ],
          ),
        ),
      );
    }

    if (_questions.isEmpty) {
      return Scaffold(
        backgroundColor: c.background,
        appBar: AppBar(title: Text('${widget.examType} Quiz')),
        body: const Center(child: Text('No questions available')),
      );
    }

    final q = _questions[_currentIndex];
    final progress = (_currentIndex + 1) / _questions.length;

    return Scaffold(
      backgroundColor: c.background,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(widget.examType,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
            Text(
              '${widget.subject} • ${widget.topic}',
              style: TextStyle(fontSize: 11, color: c.textMuted),
            ),
          ],
        ),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 8),
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              _timerLabel,
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                color: AppColors.primary,
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: Center(
              child: Text(
                '${_currentIndex + 1}/${_questions.length}',
                style: TextStyle(fontWeight: FontWeight.w600, color: c.textPrimary),
              ),
            ),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(6),
              child: LinearProgressIndicator(
                value: progress,
                backgroundColor: c.cardBorder,
                color: AppColors.primary,
                minHeight: 8,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Question ${_currentIndex + 1} of ${_questions.length}',
              style: TextStyle(fontSize: 12, color: c.textMuted),
            ),
            const SizedBox(height: 20),
            AppCard(
              child: Text(
                q.question,
                style: TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w600,
                  height: 1.5,
                  color: c.textPrimary,
                ),
              ),
            ),
            const SizedBox(height: 16),
            ...List.generate(q.options.length, (i) => _buildOption(i, q, c)),
            if (_answered) ...[
              const SizedBox(height: 14),
              _buildFeedback(q, c),
            ],
            const Spacer(),
            if (_answered)
              SizedBox(
                width: double.infinity,
                height: 52,
                child: ElevatedButton(
                  onPressed: _nextQuestion,
                  child: Text(
                    _currentIndex < _questions.length - 1
                        ? 'Next Question'
                        : 'Finish Quiz',
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildOption(int index, QuizQuestion q, DashboardColors c) {
    final labels = ['A', 'B', 'C', 'D', 'E', 'F'];
    final label = index < labels.length ? labels[index] : '${index + 1}';
    final isSelected = _selectedIndex == index;
    final isCorrect = index == q.correctIndex;
    Color? bg;
    Color? border;

    if (_answered) {
      if (isCorrect) {
        bg = AppColors.success.withValues(alpha: 0.12);
        border = AppColors.success;
      } else if (isSelected) {
        bg = AppColors.error.withValues(alpha: 0.12);
        border = AppColors.error;
      }
    } else if (isSelected) {
      bg = AppColors.primary.withValues(alpha: 0.1);
      border = AppColors.primary;
    }

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: bg ?? c.card,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: () => _selectAnswer(index),
          borderRadius: BorderRadius.circular(16),
          child: Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: border ?? c.cardBorder,
                width: 1.5,
              ),
            ),
            child: Row(
              children: [
                Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    color: (border ?? c.textMuted).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Center(
                    child: Text(
                      label,
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        color: border ?? c.textSecondary,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Text(
                    q.options[index],
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w500,
                      color: c.textPrimary,
                    ),
                  ),
                ),
                if (_answered && isCorrect)
                  const Icon(Icons.check_circle_rounded, color: AppColors.success),
                if (_answered && isSelected && !isCorrect)
                  const Icon(Icons.cancel_rounded, color: AppColors.error),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildFeedback(QuizQuestion q, DashboardColors c) {
    final correct = _selectedIndex == q.correctIndex;
    return AppCard(
      color: correct
          ? AppColors.success.withValues(alpha: 0.08)
          : AppColors.error.withValues(alpha: 0.08),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                correct ? Icons.celebration_rounded : Icons.lightbulb_rounded,
                color: correct ? AppColors.success : AppColors.warning,
              ),
              const SizedBox(width: 8),
              Text(
                correct ? 'Correct!' : 'Not quite',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
                  color: correct ? AppColors.success : AppColors.warning,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            q.explanation ?? 'Review this topic in your notes.',
            style: TextStyle(color: c.textSecondary, height: 1.5),
          ),
        ],
      ),
    );
  }
}
