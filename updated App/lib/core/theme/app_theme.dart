import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'dashboard_theme.dart';

class AppColors {
  static const Color primary = Color(0xFF705CF6);
  static const Color primaryLight = Color(0xFF7B61FF);
  static const Color primaryDark = Color(0xFF5B4AE0);
  static const Color secondary = Color(0xFF5B8CFF);
  static const Color background = Color(0xFFFFFFFF);
  static const Color surface = Color(0xFFF5F7FF);
  static const Color surfaceCard = Color(0xFFF8FAFF);
  static const Color textPrimary = Color(0xFF121826);
  static const Color textSecondary = Color(0xFF6B7280);
  static const Color textMuted = Color(0xFF9CA3AF);
  static const Color success = Color(0xFF22C55E);
  static const Color error = Color(0xFFEF4444);
  static const Color warning = Color(0xFFF59E0B);
  static const Color streakGold = Color(0xFFFFB800);
  static const Color purpleBubble = Color(0xFF705CF6);
  static const Color aiBubble = Color(0xFFFFFFFF);

  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF7B61FF), Color(0xFF5B8CFF)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient cardGradient = LinearGradient(
    colors: [Color(0xFFF5F0FF), Color(0xFFEEF4FF)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}

class AppTheme {
  static ThemeData get lightTheme => _build(Brightness.light);

  static ThemeData get darkTheme => _build(Brightness.dark);

  static ThemeData _build(Brightness brightness) {
    final isDark = brightness == Brightness.dark;
    final dash = isDark ? DashboardColors.dark : DashboardColors.light;

    final base = ThemeData(
      useMaterial3: true,
      brightness: brightness,
      scaffoldBackgroundColor: dash.background,
      colorScheme: ColorScheme(
        brightness: brightness,
        primary: AppColors.primary,
        onPrimary: Colors.white,
        secondary: AppColors.secondary,
        onSecondary: Colors.white,
        error: AppColors.error,
        onError: Colors.white,
        surface: dash.surface,
        onSurface: dash.textPrimary,
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: dash.background,
        foregroundColor: dash.textPrimary,
        elevation: 0,
        centerTitle: false,
      ),
      cardTheme: CardThemeData(
        color: dash.card,
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: false,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: dash.cardBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: dash.cardBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
      bottomNavigationBarTheme: BottomNavigationBarThemeData(
        backgroundColor: dash.surface,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: dash.textMuted,
        type: BottomNavigationBarType.fixed,
        elevation: 8,
      ),
      dividerTheme: DividerThemeData(color: dash.cardBorder, thickness: 1),
      iconTheme: IconThemeData(color: dash.textPrimary),
      listTileTheme: ListTileThemeData(
        iconColor: AppColors.primary,
        textColor: dash.textPrimary,
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: dash.card,
        titleTextStyle: TextStyle(
          color: dash.textPrimary,
          fontWeight: FontWeight.w700,
          fontSize: 18,
        ),
        contentTextStyle: TextStyle(color: dash.textSecondary, fontSize: 14),
      ),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: dash.card,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: isDark ? const Color(0xFF1E2130) : const Color(0xFF1F2937),
        contentTextStyle: const TextStyle(
          color: Colors.white,
          fontSize: 14,
          fontWeight: FontWeight.w500,
        ),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        actionTextColor: AppColors.primaryLight,
      ),
      switchTheme: SwitchThemeData(
        thumbColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) return AppColors.primary;
          return dash.textMuted;
        }),
        trackColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return AppColors.primary.withValues(alpha: 0.35);
          }
          return dash.cardBorder;
        }),
      ),
      extensions: [dash],
    );

    return base.copyWith(
      textTheme: GoogleFonts.interTextTheme(base.textTheme).apply(
        bodyColor: dash.textPrimary,
        displayColor: dash.textPrimary,
      ),
    );
  }
}
