import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../providers/providers.dart';
import '../../widgets/common_widgets.dart';

class DailyChallengeScreen extends ConsumerStatefulWidget {
  const DailyChallengeScreen({super.key});

  @override
  ConsumerState<DailyChallengeScreen> createState() =>
      _DailyChallengeScreenState();
}

class _DailyChallengeScreenState extends ConsumerState<DailyChallengeScreen> {
  bool _loading = true;
  bool _started = false;
  bool _available = false;
  String? _error;
  String _title = 'Today\'s Challenge';
  String _subject = 'Physics';
  int _participants = 0;
  int _rewardCredits = 50;
  List<_Q> _questions = [];
  List<Map<String, dynamic>> _leaderboard = [];

  int _currentQ = 0;
  int? _selected;
  bool _answered = false;
  final List<int> _answers = [];
  DateTime? _challengeStartedAt;

  @override
  void initState() {
    super.initState();
    _loadChallenge();
  }

  Future<void> _loadChallenge() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final api = ref.read(apiServiceProvider);
      final data = await api.getDailyChallenge();
      final challenge = data['challenge'] as Map<String, dynamic>?;
      final available = data['available'] == true;
      setState(() {
        _available = available;
        if (challenge != null) {
          _title = challenge['title']?.toString() ?? _title;
          _subject = challenge['subject']?.toString() ?? _subject;
          _participants = challenge['participants_count'] as int? ?? 0;
          _rewardCredits = challenge['reward_credits'] as int? ?? 50;
        }
        _leaderboard = (data['leaderboard'] as List?)
                ?.map((e) => Map<String, dynamic>.from(e as Map))
                .toList() ??
            [];
        if (!available) {
          _error = data['message']?.toString() ??
              'No challenge available today. Check back tomorrow!';
        }
      });
    } catch (e) {
      setState(() => _error = 'Could not load challenge. Pull to retry.');
    } finally {
      setState(() => _loading = false);
    }
  }

  Future<void> _startChallenge() async {
    setState(() => _loading = true);
    try {
      final api = ref.read(apiServiceProvider);
      final data = await api.startDailyChallenge();
      final qs = (data['questions'] as List?) ?? [];
      setState(() {
        _questions = qs.map((q) {
          final m = Map<String, dynamic>.from(q as Map);
          final options = (m['options'] as List?)?.map((o) => o.toString()).toList() ?? [];
          return _Q(m['question']?.toString() ?? '', options);
        }).toList();
        _started = _questions.isNotEmpty;
        _currentQ = 0;
        _selected = null;
        _answered = false;
        _answers.clear();
        _challengeStartedAt = DateTime.now();
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to start: ${e.toString()}')),
        );
      }
    } finally {
      setState(() => _loading = false);
    }
  }

  Future<void> _submitChallenge() async {
    final elapsed =
        DateTime.now().difference(_challengeStartedAt ?? DateTime.now()).inSeconds;
    int serverScore = _answers.length;
    try {
      final api = ref.read(apiServiceProvider);
      final res = await api.submitDailyChallenge(
        answers: _answers,
        timeTakenSeconds: elapsed.clamp(1, 3600),
      );
      serverScore = res['score'] as int? ??
          res['attempt']?['score'] as int? ??
          serverScore;
    } catch (_) {}

    if (!mounted) return;
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Challenge Complete! 🎉'),
        content: Text(
          'Score: $serverScore / ${_questions.length}\n+$_rewardCredits credits earned!',
        ),
        actions: [
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              AppRouter.back(context);
            },
            child: const Text('Done'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_loading && !_started) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator(color: AppColors.primary)),
      );
    }
    if (_started) return _buildQuiz();
    return _buildIntro();
  }

  Widget _buildIntro() {
    return Scaffold(
      appBar: AppBar(title: const Text('Daily Challenge')),
      body: RefreshIndicator(
        onRefresh: _loadChallenge,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(24),
          child: Column(
            children: [
              AppCard(
                gradient: AppColors.primaryGradient,
                child: Column(
                  children: [
                    const Icon(Icons.emoji_events_rounded,
                        color: Colors.white, size: 48),
                    const SizedBox(height: 12),
                    Text(_title,
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 22,
                            fontWeight: FontWeight.w800)),
                    const SizedBox(height: 8),
                    Text('$_subject • Live from server',
                        style: const TextStyle(color: Colors.white70)),
                    const SizedBox(height: 20),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        _stat('$_participants', 'Participants'),
                        _stat('Top 10%', 'Your Rank'),
                        _stat('+$_rewardCredits', 'Credits'),
                      ],
                    ),
                  ],
                ),
              ),
              if (_error != null) ...[
                const SizedBox(height: 16),
                Text(_error!,
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: AppColors.textSecondary)),
              ],
              const SizedBox(height: 24),
              const SectionTitle(title: 'Leaderboard'),
              if (_leaderboard.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 12),
                  child: Text('No scores yet — be the first!',
                      style: TextStyle(color: AppColors.textMuted)),
                )
              else
                ..._leaderboard.map((e) => _leaderTile(
                      e['rank'] as int? ?? 0,
                      '${e['name']} — ${e['score']}/${e['total'] ?? '?'}',
                    )),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _available ? _startChallenge : null,
                  child: Text(_available ? 'Start Challenge' : 'Not Available'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _stat(String val, String label) {
    return Column(
      children: [
        Text(val,
            style: const TextStyle(
                color: Colors.white, fontWeight: FontWeight.w800, fontSize: 18)),
        Text(label, style: const TextStyle(color: Colors.white60, fontSize: 11)),
      ],
    );
  }

  Widget _leaderTile(int rank, String name) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        child: Row(
          children: [
            Text('#$rank',
                style: TextStyle(
                    fontWeight: FontWeight.w800,
                    color: rank <= 3 ? AppColors.streakGold : AppColors.textMuted)),
            const SizedBox(width: 14),
            Expanded(
                child: Text(name, style: const TextStyle(fontWeight: FontWeight.w600))),
            if (rank <= 3)
              const Icon(Icons.emoji_events_rounded,
                  color: AppColors.streakGold, size: 18),
          ],
        ),
      ),
    );
  }

  Widget _buildQuiz() {
    final q = _questions[_currentQ];
    return Scaffold(
      appBar: AppBar(
        title: const Text('Daily Challenge'),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: Center(
              child: Text('${_currentQ + 1}/${_questions.length}',
                  style: const TextStyle(fontWeight: FontWeight.w700)),
            ),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: LinearProgressIndicator(
                value: (_currentQ + 1) / _questions.length,
                backgroundColor: AppColors.surface,
                color: AppColors.primary,
                minHeight: 6,
              ),
            ),
            const SizedBox(height: 24),
            AppCard(
              child: Text(q.text,
                  style: const TextStyle(
                      fontSize: 17, fontWeight: FontWeight.w600, height: 1.5)),
            ),
            const SizedBox(height: 16),
            ...List.generate(q.options.length, (i) => _option(i, q)),
            const Spacer(),
            if (_answered)
              ElevatedButton(
                onPressed: () {
                  if (_currentQ < _questions.length - 1) {
                    setState(() {
                      _currentQ++;
                      _selected = null;
                      _answered = false;
                    });
                  } else {
                    _submitChallenge();
                  }
                },
                child: Text(_currentQ < _questions.length - 1 ? 'Next' : 'Finish'),
              ),
          ],
        ),
      ),
    );
  }

  Widget _option(int i, _Q q) {
    final sel = _selected == i;
    Color? bg;
    if (_answered && sel) bg = AppColors.primary.withOpacity(0.1);
    else if (sel) bg = AppColors.primary.withOpacity(0.1);

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: GestureDetector(
        onTap: _answered
            ? null
            : () => setState(() {
                  _selected = i;
                  _answered = true;
                  _answers.add(i);
                }),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: bg ?? Colors.transparent,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
                color: sel ? AppColors.primary : AppColors.surface),
          ),
          child: Text(q.options[i],
              style: const TextStyle(fontWeight: FontWeight.w500)),
        ),
      ),
    );
  }
}

class _Q {
  final String text;
  final List<String> options;
  _Q(this.text, this.options);
}
