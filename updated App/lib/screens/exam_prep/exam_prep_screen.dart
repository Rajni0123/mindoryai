import 'package:flutter/material.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../models/models.dart';
import '../../widgets/common_widgets.dart';

class ExamPrepScreen extends StatelessWidget {
  const ExamPrepScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final topics = [
      TopicProgress(name: 'Laws of Motion', progress: 0.85),
      TopicProgress(name: 'Gravitation', progress: 0.72),
      TopicProgress(name: 'Thermodynamics', progress: 0.45, isWeak: true),
      TopicProgress(name: 'Electrostatics', progress: 0.38, isWeak: true),
      TopicProgress(name: 'Optics', progress: 0.60, isWeak: true),
    ];

    return Scaffold(
      appBar: AppBar(title: const Text('Exam Prep')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            AppCard(
              child: Row(
                children: [
                  const ProgressRing(percent: 68, label: 'Syllabus'),
                  const SizedBox(width: 20),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('JEE Main 2024',
                            style: TextStyle(
                                fontWeight: FontWeight.w800, fontSize: 18)),
                        SizedBox(height: 4),
                        Text('Physics Syllabus Progress',
                            style: TextStyle(color: AppColors.textMuted)),
                        SizedBox(height: 12),
                        Row(
                          children: [
                            Icon(Icons.warning_amber_rounded,
                                color: AppColors.warning, size: 18),
                            SizedBox(width: 6),
                            Text('Weak (3)',
                                style: TextStyle(
                                    color: AppColors.warning,
                                    fontWeight: FontWeight.w600)),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            const SectionTitle(title: 'Topic Breakdown'),
            ...topics.map((t) => _topicTile(t)),
            const SizedBox(height: 20),
            AppCard(
              gradient: AppColors.primaryGradient,
              child: Row(
                children: [
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('AI Mock Test',
                            style: TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w700,
                                fontSize: 17)),
                        SizedBox(height: 4),
                        Text('30 questions • 45 min',
                            style: TextStyle(color: Colors.white70, fontSize: 13)),
                      ],
                    ),
                  ),
                  ElevatedButton(
                    onPressed: () => AppRouter.go(context, AppRoutes.mockTest),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: AppColors.primary,
                    ),
                    child: const Text('Start'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            _examChip(context, 'NEET 2024'),
            _examChip(context, 'UPSC Prelims'),
            _examChip(context, 'CBSE Class 12'),
          ],
        ),
      ),
    );
  }

  Widget _topicTile(TopicProgress topic) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        color: Colors.white,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(topic.name,
                      style: const TextStyle(fontWeight: FontWeight.w600)),
                ),
                if (topic.isWeak)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: AppColors.warning.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Text('Weak',
                        style: TextStyle(
                            color: AppColors.warning,
                            fontSize: 11,
                            fontWeight: FontWeight.w600)),
                  ),
                const SizedBox(width: 8),
                Text('${(topic.progress * 100).toInt()}%',
                    style: const TextStyle(
                        fontWeight: FontWeight.w700, color: AppColors.primary)),
              ],
            ),
            const SizedBox(height: 8),
            ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: LinearProgressIndicator(
                value: topic.progress,
                          color: topic.isWeak ? AppColors.warning : AppColors.primary,
                minHeight: 6,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _examChip(BuildContext context, String name) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: GestureDetector(
        onTap: () => AppRouter.go(context, AppRoutes.examPrep),
        child: AppCard(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          color: Colors.white,
          child: Row(
            children: [
              const Icon(Icons.school_rounded, color: AppColors.primary),
              const SizedBox(width: 12),
              Expanded(
                child: Text(name, style: const TextStyle(fontWeight: FontWeight.w600)),
              ),
              const Icon(Icons.chevron_right_rounded, color: AppColors.textMuted),
            ],
          ),
        ),
      ),
    );
  }
}
