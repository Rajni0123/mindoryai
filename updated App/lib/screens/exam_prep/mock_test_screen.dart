import 'package:flutter/material.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import 'test_completed_screen.dart';
import '../../widgets/common_widgets.dart';

class MockTestScreen extends StatefulWidget {
  const MockTestScreen({super.key});

  @override
  State<MockTestScreen> createState() => _MockTestScreenState();
}

class _MockTestScreenState extends State<MockTestScreen> {
  bool _started = false;
  int _timeLeft = 45 * 60;
  int _current = 0;
  int? _selected;

  final _questions = List.generate(
    5,
    (i) => 'Question ${i + 1}: A 2 kg block on a frictionless surface is pulled with 10 N force. Find acceleration.',
  );

  @override
  void initState() {
    super.initState();
    if (_started) _tick();
  }

  void _tick() {
    Future.doWhile(() async {
      await Future.delayed(const Duration(seconds: 1));
      if (!mounted || _timeLeft <= 0) return false;
      setState(() => _timeLeft--);
      return _timeLeft > 0;
    });
  }

  String get _timer {
    final m = _timeLeft ~/ 60;
    final s = _timeLeft % 60;
    return '${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    if (!_started) return _intro();
    return _exam();
  }

  Widget _intro() {
    return Scaffold(
      appBar: AppBar(title: const Text('AI Mock Test')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            AppCard(
              gradient: AppColors.primaryGradient,
              child: const Column(
                children: [
                  Icon(Icons.assignment_rounded, color: Colors.white, size: 48),
                  SizedBox(height: 12),
                  Text('JEE Main 2024 — Full Mock',
                      style: TextStyle(
                          color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
                  SizedBox(height: 8),
                  Text('30 Questions • 45 Minutes • All Subjects',
                      style: TextStyle(color: Colors.white70)),
                ],
              ),
            ),
            const SizedBox(height: 24),
            _infoRow(Icons.quiz_rounded, '30 MCQs', 'Physics, Chemistry, Maths'),
            _infoRow(Icons.timer_rounded, '45 minutes', 'Auto-submit on timeout'),
            _infoRow(Icons.analytics_rounded, 'Instant result', 'Subject-wise analysis'),
            const Spacer(),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  setState(() => _started = true);
                  _tick();
                },
                child: const Text('Start Mock Test'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _infoRow(IconData icon, String title, String sub) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: AppCard(
        padding: const EdgeInsets.all(14),
        child: Row(
          children: [
            Icon(icon, color: AppColors.primary),
            const SizedBox(width: 14),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
                Text(sub, style: const TextStyle(color: AppColors.textMuted, fontSize: 12)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _exam() {
    return Scaffold(
      appBar: AppBar(
        title: Text('Q ${_current + 1}/${_questions.length}'),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 16),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: _timeLeft < 300 ? AppColors.error.withOpacity(0.15) : AppColors.surface,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Icon(Icons.timer_rounded,
                    size: 16,
                    color: _timeLeft < 300 ? AppColors.error : AppColors.primary),
                const SizedBox(width: 4),
                Text(_timer,
                    style: TextStyle(
                        fontWeight: FontWeight.w700,
                        color: _timeLeft < 300 ? AppColors.error : AppColors.textPrimary)),
              ],
            ),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            AppCard(
              child: Text(_questions[_current],
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600, height: 1.5)),
            ),
            const SizedBox(height: 16),
            ...['2 m/s²', '5 m/s²', '10 m/s²', '20 m/s²'].asMap().entries.map((e) {
              final i = e.key;
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: GestureDetector(
                  onTap: () => setState(() => _selected = i),
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: _selected == i
                          ? AppColors.primary.withOpacity(0.1)
                          : Colors.white,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                          color: _selected == i ? AppColors.primary : AppColors.surface),
                    ),
                    child: Text(['A', 'B', 'C', 'D'][i] + '. ${e.value}',
                        style: const TextStyle(fontWeight: FontWeight.w500)),
                  ),
                ),
              );
            }),
            const Spacer(),
            Row(
              children: [
                if (_current > 0)
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => setState(() => _current--),
                      child: const Text('Previous'),
                    ),
                  ),
                if (_current > 0) const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () {
                      if (_current < _questions.length - 1) {
                        setState(() {
                          _current++;
                          _selected = null;
                        });
                      } else {
                        _showResult();
                      }
                    },
                    child: Text(_current < _questions.length - 1 ? 'Next' : 'Submit'),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _showResult() {
    AppRouter.goReplace(
      context,
      AppRoutes.testCompleted,
      args: TestResultArgs(
        score: _current + 1,
        total: _questions.length,
        rank: 215,
        rankImprovement: 23,
      ),
    );
  }
}
