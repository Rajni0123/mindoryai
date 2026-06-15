import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/theme/app_theme.dart';
import '../core/theme/dashboard_theme.dart';
import '../providers/providers.dart';

/// Flat decoration for inputs inside [AppCard] — avoids the inner grey box.
class AppInputDecoration {
  static InputDecoration insideCard(
    BuildContext context, {
    String? labelText,
    String? hintText,
    IconData? prefixIcon,
    Widget? suffixIcon,
  }) {
    final c = context.dash;
    return InputDecoration(
      filled: false,
      labelText: labelText,
      hintText: hintText,
      prefixIcon: prefixIcon != null
          ? Icon(prefixIcon, color: AppColors.primary, size: 22)
          : null,
      suffixIcon: suffixIcon,
      border: InputBorder.none,
      enabledBorder: InputBorder.none,
      focusedBorder: InputBorder.none,
      contentPadding: const EdgeInsets.symmetric(vertical: 8),
      labelStyle: TextStyle(color: c.textMuted, fontSize: 13),
      hintStyle: TextStyle(color: c.textMuted, fontSize: 14),
    );
  }
}

class AppCard extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry? padding;
  final Gradient? gradient;
  final Color? color;

  const AppCard({
    super.key,
    required this.child,
    this.padding,
    this.gradient,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Container(
      padding: padding ?? const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: gradient == null ? (color ?? c.card) : null,
        gradient: gradient,
        borderRadius: BorderRadius.circular(20),
        border: gradient == null ? Border.all(color: c.cardBorder) : null,
        boxShadow: [
          BoxShadow(
            color: c.accentGlow,
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: child,
    );
  }
}

class SectionTitle extends StatelessWidget {
  final String title;
  final String? action;
  final VoidCallback? onAction;

  const SectionTitle({
    super.key,
    required this.title,
    this.action,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: c.textPrimary,
            ),
          ),
          if (action != null)
            GestureDetector(
              onTap: onAction,
              child: Text(
                action!,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: AppColors.primary,
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class QuickActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color iconBg;
  final VoidCallback onTap;

  const QuickActionButton({
    super.key,
    required this.icon,
    required this.label,
    required this.iconBg,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: iconBg.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(icon, color: iconBg, size: 26),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w500,
              color: c.textSecondary,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

class ProgressRing extends StatelessWidget {
  final double percent;
  final double size;
  final String label;

  const ProgressRing({
    super.key,
    required this.percent,
    this.size = 120,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return SizedBox(
      width: size,
      height: size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          SizedBox(
            width: size,
            height: size,
            child: CircularProgressIndicator(
              value: percent / 100,
              strokeWidth: 10,
              backgroundColor: c.cardBorder,
              valueColor: const AlwaysStoppedAnimation(AppColors.primary),
              strokeCap: StrokeCap.round,
            ),
          ),
          Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                '${percent.toInt()}%',
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                  color: c.textPrimary,
                ),
              ),
              Text(
                label,
                style: TextStyle(
                  fontSize: 11,
                  color: c.textMuted,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

/// Shared sun/moon theme toggle button.
class ThemeToggleButton extends ConsumerWidget {
  final double size;
  const ThemeToggleButton({super.key, this.size = 21});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final c = context.dash;
    final isDark = context.isDark;
    return IconButton(
      icon: Icon(
        isDark ? Icons.light_mode_rounded : Icons.dark_mode_rounded,
        size: size,
        color: c.textPrimary,
      ),
      onPressed: () => context.toggleTheme(ref),
      tooltip: isDark ? 'Light mode' : 'Dark mode',
    );
  }
}
