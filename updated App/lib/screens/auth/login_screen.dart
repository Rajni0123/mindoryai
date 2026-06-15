import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/constants/app_constants.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/providers.dart';
import '../../widgets/common_widgets.dart';
import '../../widgets/otp_input.dart';

enum LoginStep { phone, otp }

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _mobileController = TextEditingController();
  final _otpKey = GlobalKey<OtpInputRowState>();

  LoginStep _step = LoginStep.phone;
  int _resendSeconds = 0;
  String _otpValue = '';

  @override
  void dispose() {
    _mobileController.dispose();
    super.dispose();
  }

  bool _isValidMobile(String mobile) =>
      RegExp(r'^[6-9]\d{9}$').hasMatch(mobile);

  Future<void> _sendOtp({bool resend = false}) async {
    final mobile = _mobileController.text.trim();
    if (!_isValidMobile(mobile)) {
      _toast('Enter a valid 10-digit mobile number');
      return;
    }

    final ok = await ref.read(authProvider.notifier).sendOtp(mobile);
    if (!mounted) return;

    if (ok) {
      setState(() {
        _step = LoginStep.otp;
        _resendSeconds = AppConstants.isDemoMobile(mobile) ? 0 : 60;
        _otpValue = '';
      });
      if (_resendSeconds > 0) _startResendTimer();
    } else {
      _toast(ref.read(authProvider).error ?? 'Failed to send OTP');
    }
  }

  void _startResendTimer() {
    Future.doWhile(() async {
      await Future.delayed(const Duration(seconds: 1));
      if (!mounted || _resendSeconds <= 0) return false;
      setState(() => _resendSeconds--);
      return _resendSeconds > 0;
    });
  }

  Future<void> _verifyOtp([String? otp]) async {
    final code = otp ?? _otpValue;
    if (code.length < AppConstants.otpLength) {
      _toast('Enter ${AppConstants.otpLength}-digit OTP');
      return;
    }

    final ok = await ref.read(authProvider.notifier).verifyOtp(
          _mobileController.text.trim(),
          code,
        );

    if (!mounted) return;

    if (ok) {
      final needsSetup = ref.read(authProvider).needsStudySetup;
      AppRouter.goReplace(
        context,
        needsSetup ? AppRoutes.profileSetup : AppRoutes.main,
      );
    } else {
      _toast(ref.read(authProvider).error ?? 'Invalid OTP');
      _otpKey.currentState?.clear();
    }
  }

  void _useDemoAccount() {
    _mobileController.text = AppConstants.demoMobile;
    _sendOtp();
  }

  void _toast(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), behavior: SnackBarBehavior.floating),
    );
  }

  void _goBack() {
    setState(() {
      _step = LoginStep.phone;
      _otpValue = '';
      _resendSeconds = 0;
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authProvider);
    final mobile = _mobileController.text.trim();
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    final keyboardOpen = bottom > 0;
    final c = context.dash;

    return Scaffold(
      resizeToAvoidBottomInset: true,
      body: Stack(
        children: [
          Positioned.fill(
            child: DecoratedBox(decoration: BoxDecoration(gradient: c.ambientGradient)),
          ),
          if (!keyboardOpen) ...[
            Positioned(
              top: -60,
              right: -40,
              child: Container(
                width: 200,
                height: 200,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: AppColors.primary.withValues(alpha: context.isDark ? 0.15 : 0.1),
                ),
              ),
            ),
            Positioned(
              bottom: 80,
              left: -80,
              child: Container(
                width: 180,
                height: 180,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: AppColors.secondary.withValues(alpha: context.isDark ? 0.1 : 0.07),
                ),
              ),
            ),
          ],
          SafeArea(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                  child: Row(
                    children: [
                      if (_step == LoginStep.otp)
                        _IconCircleBtn(
                          icon: LucideIcons.arrowLeft,
                          onTap: _goBack,
                        )
                      else
                        const SizedBox(width: 44),
                      const Spacer(),
                      const ThemeToggleButton(),
                    ],
                  ),
                ),
                Expanded(
                  child: SingleChildScrollView(
                    physics: const ClampingScrollPhysics(),
                    padding: EdgeInsets.fromLTRB(24, 16, 24, 16 + bottom),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _BrandMark(),
                        SizedBox(height: keyboardOpen ? 20 : 40),
                        AnimatedSwitcher(
                          duration: const Duration(milliseconds: 300),
                          switchInCurve: Curves.easeOutCubic,
                          switchOutCurve: Curves.easeInCubic,
                          transitionBuilder: (child, anim) => FadeTransition(
                            opacity: anim,
                            child: SlideTransition(
                              position: Tween<Offset>(
                                begin: const Offset(0, 0.04),
                                end: Offset.zero,
                              ).animate(anim),
                              child: child,
                            ),
                          ),
                          child: _step == LoginStep.phone
                              ? _phoneStep(auth, compact: keyboardOpen)
                              : _otpStep(auth, mobile),
                        ),
                        if (_step == LoginStep.phone && !keyboardOpen) ...[
                          const SizedBox(height: 28),
                          _DemoCard(onTap: _useDemoAccount)
                              .animate()
                              .fadeIn(delay: 200.ms, duration: 450.ms)
                              .slideY(begin: 0.05, end: 0),
                        ],
                        const SizedBox(height: 20),
                        Center(
                          child: Text(
                            'By continuing you accept Terms & Privacy',
                            textAlign: TextAlign.center,
                            style: context.labelSmall.copyWith(fontSize: 11, height: 1.4),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _phoneStep(AuthState auth, {bool compact = false}) {
    return Column(
      key: const ValueKey('phone'),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Welcome to BlinkStudy', style: context.displayLarge.copyWith(fontSize: 26)),
        const SizedBox(height: 8),
        Text(
          'Your AI study companion.\nSign in with your mobile number.',
          style: context.bodyMedium.copyWith(height: 1.5),
        ),
        SizedBox(height: compact ? 20 : 32),
        Text('Mobile number', style: context.labelSmall.copyWith(fontWeight: FontWeight.w600)),
        const SizedBox(height: 10),
        _PhoneField(controller: _mobileController),
        const SizedBox(height: 24),
        _PrimaryButton(
          label: 'Continue',
          icon: LucideIcons.arrowRight,
          loading: auth.isLoading,
          onPressed: auth.isLoading ? null : _sendOtp,
        ),
      ],
    )
        .animate()
        .fadeIn(duration: 500.ms)
        .slideY(begin: 0.06, end: 0, curve: Curves.easeOutCubic);
  }

  Widget _otpStep(AuthState auth, String mobile) {
    return Column(
      key: const ValueKey('otp'),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 52,
          height: 52,
          decoration: BoxDecoration(
            gradient: AppColors.primaryGradient,
            borderRadius: BorderRadius.circular(16),
          ),
          child: const Icon(LucideIcons.shieldCheck, color: Colors.white, size: 26),
        ),
        const SizedBox(height: 20),
        Text('Verify OTP', style: context.displayLarge.copyWith(fontSize: 26)),
        const SizedBox(height: 8),
        Text(
          'Code sent to +91 $mobile',
          style: context.bodyMedium,
        ),
        if (AppConstants.isDemoMobile(mobile)) ...[
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: AppColors.primary.withValues(alpha: 0.2)),
            ),
            child: Text(
              'Demo OTP: ${AppConstants.demoOtp}',
              style: GoogleFonts.jetBrainsMono(
                fontSize: 13,
                color: AppColors.primary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
        const SizedBox(height: 28),
        OtpInputRow(
          key: _otpKey,
          length: AppConstants.otpLength,
          enabled: !auth.isLoading,
          onChanged: (v) => setState(() => _otpValue = v),
          onCompleted: _verifyOtp,
        ),
        const SizedBox(height: 18),
        Center(
          child: _resendSeconds > 0
              ? Text(
                  'Resend in ${_resendSeconds}s',
                  style: context.labelSmall,
                )
              : GestureDetector(
                  onTap: auth.isLoading ? null : () => _sendOtp(resend: true),
                  child: Text(
                    'Resend code',
                    style: context.bodyMedium.copyWith(
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary,
                      decoration: TextDecoration.underline,
                    ),
                  ),
                ),
        ),
        const SizedBox(height: 24),
        _PrimaryButton(
          label: 'Verify & Continue',
          icon: LucideIcons.logIn,
          loading: auth.isLoading,
          onPressed: auth.isLoading ? null : () => _verifyOtp(),
        ),
      ],
    )
        .animate()
        .fadeIn(duration: 450.ms)
        .slideY(begin: 0.05, end: 0);
  }
}

// ─── Brand & UI components ────────────────────────────────────────────────────

class _BrandMark extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Blink',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 28,
                fontWeight: FontWeight.w800,
                color: context.dash.textPrimary,
                letterSpacing: -0.5,
              ),
            ),
            ShaderMask(
              shaderCallback: (b) => AppColors.primaryGradient.createShader(b),
              child: Text(
                'Study',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                  letterSpacing: -0.5,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 6),
        Text(
          'AI-powered competitive exam prep',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 13,
            fontWeight: FontWeight.w500,
            color: context.dash.textMuted,
          ),
        ),
      ],
    ).animate().fadeIn(duration: 400.ms);
  }
}

class _IconCircleBtn extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;

  const _IconCircleBtn({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Material(
      color: c.card,
      shape: const CircleBorder(),
      child: InkWell(
        onTap: onTap,
        customBorder: const CircleBorder(),
        child: SizedBox(
          width: 44,
          height: 44,
          child: Icon(icon, size: 20, color: c.textPrimary),
        ),
      ),
    );
  }
}

class _PhoneField extends StatelessWidget {
  final TextEditingController controller;

  const _PhoneField({required this.controller});

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 6),
      decoration: BoxDecoration(
        color: c.card,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: c.cardBorder),
        boxShadow: [
          BoxShadow(
            color: c.accentGlow,
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              '+91',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: AppColors.primary,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: TextField(
              controller: controller,
              keyboardType: TextInputType.phone,
              inputFormatters: [
                FilteringTextInputFormatter.digitsOnly,
                LengthLimitingTextInputFormatter(10),
              ],
              style: GoogleFonts.plusJakartaSans(
                fontSize: 20,
                fontWeight: FontWeight.w600,
                color: c.textPrimary,
                letterSpacing: 1.5,
              ),
              decoration: InputDecoration(
                hintText: '9876543210',
                hintStyle: GoogleFonts.plusJakartaSans(
                  fontSize: 20,
                  color: c.textMuted,
                  letterSpacing: 1.5,
                ),
                border: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),
          const SizedBox(width: 8),
          Icon(LucideIcons.smartphone, size: 20, color: c.textMuted),
          const SizedBox(width: 8),
        ],
      ),
    );
  }
}

class _PrimaryButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool loading;
  final VoidCallback? onPressed;

  const _PrimaryButton({
    required this.label,
    required this.icon,
    required this.loading,
    required this.onPressed,
  });

  @override
  Widget build(BuildContext context) {
    final enabled = onPressed != null && !loading;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: enabled ? onPressed : null,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          width: double.infinity,
          height: 56,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            gradient: enabled ? AppColors.primaryGradient : null,
            color: enabled ? null : context.dash.cardBorder,
            boxShadow: enabled
                ? [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.4),
                      blurRadius: 16,
                      offset: const Offset(0, 6),
                    ),
                  ]
                : null,
          ),
          child: Center(
            child: loading
                ? const SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.5,
                      color: Colors.white,
                    ),
                  )
                : Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        label,
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: enabled ? Colors.white : context.dash.textMuted,
                        ),
                      ),
                      if (enabled) ...[
                        const SizedBox(width: 8),
                        Icon(icon, size: 18, color: Colors.white),
                      ],
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}

class _DemoCard extends StatelessWidget {
  final VoidCallback onTap;

  const _DemoCard({required this.onTap});

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: c.card,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: AppColors.primary.withValues(alpha: 0.25),
            ),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.08),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(LucideIcons.flaskConical, color: AppColors.primary, size: 20),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Backend test account',
                      style: context.displayMedium.copyWith(fontSize: 14),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      '${AppConstants.demoMobile}  ·  OTP ${AppConstants.demoOtp}',
                      style: GoogleFonts.jetBrainsMono(
                        fontSize: 12,
                        color: c.textMuted,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(LucideIcons.chevronRight, size: 18, color: c.textMuted),
            ],
          ),
        ),
      ),
    );
  }
}
