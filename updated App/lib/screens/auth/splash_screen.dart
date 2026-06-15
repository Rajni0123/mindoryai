import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/providers.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _navigateWhenReady();
  }

  Future<void> _navigateWhenReady() async {
    await Future.delayed(const Duration(milliseconds: 600));
    while (mounted && ref.read(authProvider).isCheckingAuth) {
      await Future.delayed(const Duration(milliseconds: 80));
    }
    if (!mounted) return;

    final auth = ref.read(authProvider);
    Navigator.pushReplacementNamed(
      context,
      !auth.isAuthenticated
          ? AppRoutes.login
          : auth.needsStudySetup
              ? AppRoutes.profileSetup
              : AppRoutes.main,
    );
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Scaffold(
      body: Center(
        child: SizedBox(
          width: 22,
          height: 22,
          child: CircularProgressIndicator(
            strokeWidth: 2,
            color: c.textPrimary,
          ),
        ),
      ),
    );
  }
}
