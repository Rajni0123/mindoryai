import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/theme/app_theme.dart';
import '../core/theme/dashboard_theme.dart';
import '../providers/providers.dart';
import 'ai_tutor/ai_tutor_screen.dart';
import 'battles/battles_screen.dart';
import 'home/home_screen.dart';
import 'performance/performance_screen.dart';
import 'scan_solve/scan_solve_screen.dart';

class MainShell extends ConsumerStatefulWidget {
  const MainShell({super.key});

  @override
  ConsumerState<MainShell> createState() => _MainShellState();
}

class _MainShellState extends ConsumerState<MainShell> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(authProvider.notifier).refreshUser();
    });
  }

  @override
  Widget build(BuildContext context) {
    final index = ref.watch(navIndexProvider);

    final screens = [
      const HomeScreen(),
      const AiTutorScreen(),
      const ScanSolveScreen(),
      const PerformanceScreen(),
      const BattlesScreen(),
    ];

    return Scaffold(
      body: IndexedStack(index: index, children: screens),
      bottomNavigationBar: _BottomNav(index: index),
    );
  }
}

class _BottomNav extends ConsumerWidget {
  final int index;
  const _BottomNav({required this.index});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final c = context.dash;
    return Container(
      decoration: BoxDecoration(
        color: c.surface,
        border: Border(top: BorderSide(color: c.cardBorder, width: 0.5)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: context.isDark ? 0.3 : 0.06),
            blurRadius: 20,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              _item(context, ref, Icons.home_rounded, 'Home', 0),
              _item(context, ref, Icons.smart_toy_rounded, 'AI Tutor', 1),
              _scan(context, ref),
              _item(context, ref, Icons.bar_chart_rounded, 'Progress', 3),
              _item(context, ref, Icons.sports_esports_rounded, 'Battles', 4),
            ],
          ),
        ),
      ),
    );
  }

  Widget _item(BuildContext context, WidgetRef ref, IconData icon, String label, int i) {
    final active = index == i;
    final c = context.dash;
    return Expanded(
      child: GestureDetector(
        onTap: () => ref.read(navIndexProvider.notifier).state = i,
        behavior: HitTestBehavior.opaque,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: active ? AppColors.primary : c.textMuted, size: 22),
            const SizedBox(height: 2),
            SizedBox(
              height: 6,
              child: active
                  ? Container(
                      width: 5,
                      height: 5,
                      decoration: const BoxDecoration(
                        color: AppColors.primary,
                        shape: BoxShape.circle,
                      ),
                    )
                  : null,
            ),
            const SizedBox(height: 2),
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(
                label,
                maxLines: 1,
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: active ? FontWeight.w600 : FontWeight.w400,
                  color: active ? AppColors.primary : c.textMuted,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _scan(BuildContext context, WidgetRef ref) {
    final onScan = index == 2
        ? () => ref.read(scanCaptureTriggerProvider.notifier).state++
        : () => ref.read(navIndexProvider.notifier).state = 2;
    return Expanded(
      child: GestureDetector(
        onTap: onScan,
        child: Transform.translate(
          offset: const Offset(0, -14),
          child: Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              gradient: AppColors.primaryGradient,
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                  color: AppColors.primary.withValues(alpha: 0.4),
                  blurRadius: 16,
                  offset: const Offset(0, 6),
                ),
              ],
              border: Border.all(
                color: index == 2 ? AppColors.primaryLight : context.dash.surface,
                width: 3,
              ),
            ),
            child: const Icon(
              Icons.document_scanner_rounded,
              color: Colors.white,
              size: 26,
            ),
          ),
        ),
      ),
    );
  }
}
