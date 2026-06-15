import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'dashboard_theme.dart';
import '../../providers/providers.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Semantic dashboard colors that adapt to light / dark mode.
@immutable
class DashboardColors extends ThemeExtension<DashboardColors> {
  final Color background;
  final Color surface;
  final Color card;
  final Color cardBorder;
  final Color textPrimary;
  final Color textSecondary;
  final Color textMuted;
  final Color accentGlow;
  final Color heroOverlay;
  final LinearGradient ambientGradient;

  const DashboardColors({
    required this.background,
    required this.surface,
    required this.card,
    required this.cardBorder,
    required this.textPrimary,
    required this.textSecondary,
    required this.textMuted,
    required this.accentGlow,
    required this.heroOverlay,
    required this.ambientGradient,
  });

  static const light = DashboardColors(
    background: Color(0xFFF7F8FC),
    surface: Color(0xFFFFFFFF),
    card: Color(0xFFFFFFFF),
    cardBorder: Color(0xFFE8EBF4),
    textPrimary: Color(0xFF0F1222),
    textSecondary: Color(0xFF4B5568),
    textMuted: Color(0xFF9CA3AF),
    accentGlow: Color(0x1A705CF6),
    heroOverlay: Color(0x0F705CF6),
    ambientGradient: LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: [Color(0xFFF0EDFF), Color(0xFFF7F8FC), Color(0xFFEEF4FF)],
    ),
  );

  static const dark = DashboardColors(
    background: Color(0xFF0A0A0F),
    surface: Color(0xFF13131A),
    card: Color(0xFF1A1A24),
    cardBorder: Color(0xFF2A2A38),
    textPrimary: Color(0xFFF2F2F8),
    textSecondary: Color(0xFFB4B4C8),
    textMuted: Color(0xFF6E6E82),
    accentGlow: Color(0x33705CF6),
    heroOverlay: Color(0x1F705CF6),
    ambientGradient: LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: [Color(0xFF12101F), Color(0xFF0A0A0F), Color(0xFF0D1117)],
    ),
  );

  @override
  DashboardColors copyWith({
    Color? background,
    Color? surface,
    Color? card,
    Color? cardBorder,
    Color? textPrimary,
    Color? textSecondary,
    Color? textMuted,
    Color? accentGlow,
    Color? heroOverlay,
    LinearGradient? ambientGradient,
  }) {
    return DashboardColors(
      background: background ?? this.background,
      surface: surface ?? this.surface,
      card: card ?? this.card,
      cardBorder: cardBorder ?? this.cardBorder,
      textPrimary: textPrimary ?? this.textPrimary,
      textSecondary: textSecondary ?? this.textSecondary,
      textMuted: textMuted ?? this.textMuted,
      accentGlow: accentGlow ?? this.accentGlow,
      heroOverlay: heroOverlay ?? this.heroOverlay,
      ambientGradient: ambientGradient ?? this.ambientGradient,
    );
  }

  @override
  DashboardColors lerp(ThemeExtension<DashboardColors>? other, double t) {
    if (other is! DashboardColors) return this;
    return DashboardColors(
      background: Color.lerp(background, other.background, t)!,
      surface: Color.lerp(surface, other.surface, t)!,
      card: Color.lerp(card, other.card, t)!,
      cardBorder: Color.lerp(cardBorder, other.cardBorder, t)!,
      textPrimary: Color.lerp(textPrimary, other.textPrimary, t)!,
      textSecondary: Color.lerp(textSecondary, other.textSecondary, t)!,
      textMuted: Color.lerp(textMuted, other.textMuted, t)!,
      accentGlow: Color.lerp(accentGlow, other.accentGlow, t)!,
      heroOverlay: Color.lerp(heroOverlay, other.heroOverlay, t)!,
      ambientGradient: ambientGradient,
    );
  }
}

extension DashboardThemeX on BuildContext {
  DashboardColors get dash =>
      Theme.of(this).extension<DashboardColors>() ?? DashboardColors.light;

  bool get isDark => Theme.of(this).brightness == Brightness.dark;

  TextStyle get displayLarge => GoogleFonts.plusJakartaSans(
        fontSize: 28,
        fontWeight: FontWeight.w800,
        color: dash.textPrimary,
        letterSpacing: -0.8,
        height: 1.15,
      );

  TextStyle get displayMedium => GoogleFonts.plusJakartaSans(
        fontSize: 18,
        fontWeight: FontWeight.w700,
        color: dash.textPrimary,
        letterSpacing: -0.4,
      );

  TextStyle get bodyMedium => GoogleFonts.inter(
        fontSize: 14,
        fontWeight: FontWeight.w400,
        color: dash.textSecondary,
        height: 1.45,
      );

  TextStyle get labelSmall => GoogleFonts.inter(
        fontSize: 11,
        fontWeight: FontWeight.w500,
        color: dash.textMuted,
        letterSpacing: 0.2,
      );

  TextStyle get sectionTitle => GoogleFonts.plusJakartaSans(
        fontSize: 13,
        fontWeight: FontWeight.w700,
        color: dash.textMuted,
        letterSpacing: 1.2,
      );

  /// Toggle theme based on what the user currently sees.
  void toggleTheme(WidgetRef ref) {
    final isDark = Theme.of(this).brightness == Brightness.dark;
    ref.read(themeModeProvider.notifier).setMode(
          isDark ? ThemeMode.light : ThemeMode.dark,
        );
  }
}
