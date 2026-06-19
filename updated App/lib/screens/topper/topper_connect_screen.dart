import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../providers/providers.dart';
import '../../widgets/common_widgets.dart';

class TopperConnectScreen extends ConsumerStatefulWidget {
  const TopperConnectScreen({super.key});

  @override
  ConsumerState<TopperConnectScreen> createState() => _TopperConnectScreenState();
}

class _TopperConnectScreenState extends ConsumerState<TopperConnectScreen> {
  List<Map<String, dynamic>> _toppers = [];
  List<Map<String, dynamic>> _doubts = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final api = ref.read(apiServiceProvider);
    try {
      final t = await api.getToppers();
      final d = await api.getPublicDoubts(sort: 'trending');
      setState(() {
        _toppers = t.map((e) => Map<String, dynamic>.from(e as Map)).toList();
        _doubts = d.map((e) => Map<String, dynamic>.from(e as Map)).toList();
      });
    } catch (_) {}
    setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Topper Connect'),
        actions: [
          IconButton(
            icon: const Icon(Icons.leaderboard_rounded),
            onPressed: () => AppRouter.go(context, AppRoutes.leaderboard),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
          : RefreshIndicator(
              onRefresh: _load,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    AppCard(
                      gradient: AppColors.primaryGradient,
                      child: const Row(
                        children: [
                          Icon(Icons.groups_rounded, color: Colors.white, size: 40),
                          SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Learn from Toppers',
                                    style: TextStyle(
                                        color: Colors.white,
                                        fontWeight: FontWeight.w800,
                                        fontSize: 18)),
                                SizedBox(height: 4),
                                Text('AI-moderated public discussions',
                                    style: TextStyle(color: Colors.white70, fontSize: 13)),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),
                    const SectionTitle(title: 'Verified Toppers'),
                    if (_toppers.isEmpty)
                      const Text('No toppers available',
                          style: TextStyle(color: AppColors.textMuted))
                    else
                      ..._toppers.map(_topperCard),
                    const SizedBox(height: 20),
                    const SectionTitle(title: 'Public Doubts'),
                    if (_doubts.isEmpty)
                      const Text('No public doubts yet',
                          style: TextStyle(color: AppColors.textMuted))
                    else
                      ..._doubts.map(_doubtTile),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _topperCard(Map<String, dynamic> t) {
    final name = t['name']?.toString() ?? 'Topper';
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: AppCard(
        child: Row(
          children: [
            CircleAvatar(
              radius: 28,
              backgroundColor: AppColors.primary.withOpacity(0.15),
              child: Text(name[0],
                  style: const TextStyle(
                      color: AppColors.primary, fontWeight: FontWeight.w800, fontSize: 20)),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                  Text(t['exam']?.toString() ?? t['topper_exam']?.toString() ?? '',
                      style: const TextStyle(color: AppColors.primary, fontSize: 12)),
                ],
              ),
            ),
            ElevatedButton(
              onPressed: () {},
              child: const Text('Connect'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _doubtTile(Map<String, dynamic> d) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(d['question']?.toString() ?? '',
                style: const TextStyle(fontWeight: FontWeight.w600, height: 1.4)),
            const SizedBox(height: 8),
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(d['subject']?.toString() ?? '',
                      style: const TextStyle(
                          color: AppColors.primary, fontSize: 11, fontWeight: FontWeight.w600)),
                ),
                const Spacer(),
                Text('${d['upvotes'] ?? 0} upvotes',
                    style: const TextStyle(color: AppColors.textMuted, fontSize: 12)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
