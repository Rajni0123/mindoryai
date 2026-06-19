import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../core/utils/study_profile_utils.dart';
import '../../providers/app_data_provider.dart';
import '../../providers/providers.dart';
import '../../models/user_badges.dart';
import '../../widgets/dynamic_badge.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).user;
    final prefs = ref.watch(userStudyPreferencesProvider);
    final dash = ref.watch(homeDashboardProvider);
    final badges = dash.badges;
    final c = context.dash;
    final name = _displayName(user?.name);
    final targetExam = user?.targetExam ?? prefs.targetExam;
    final studentClass = user?.studentClass ?? prefs.studentClass;
    final subjects = user?.subjects ?? prefs.subjects;
    final aspirantLabel = StudyProfileUtils.aspirantLabel(targetExam);
    final profileSubtitle = StudyProfileUtils.profileSubtitle(
      studentClass: studentClass,
      subjects: subjects,
      targetExam: targetExam,
    );

    return Scaffold(
      backgroundColor: c.background,
      body: SafeArea(
        child: Column(
          children: [
            _ProfileTopBar(
              onBack: () {
                if (Navigator.of(context).canPop()) {
                  Navigator.of(context).pop();
                } else {
                  ref.read(navIndexProvider.notifier).state = 0;
                }
              },
              onSettings: () => AppRouter.go(context, AppRoutes.settings),
            ),
            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.symmetric(horizontal: 22),
                child: Column(
                  children: [
                    const SizedBox(height: 12),
                    _ProfileHero(
                      name: name,
                      aspirantLabel: aspirantLabel,
                      profileSubtitle: profileSubtitle,
                      avatarUrl: user?.avatar,
                    ).stagger(0),
                    const SizedBox(height: 20),
                    _ProfileStatsCard(
                      level: dash.level,
                      xp: dash.xp,
                      xpMax: dash.xpMax,
                      streak: dash.streak,
                      tests: badges.stats.totalQuizzes,
                      battles: badges.stats.battleWins,
                      rankPercentile: badges.stats.xpPercentile,
                    ).stagger(1),
                    const SizedBox(height: 22),
                    _ProfileBadgesSection(
                      badges: badges.unlocked,
                      onViewAll: () => BadgeGuideSheet.show(context),
                    ).stagger(2),
                    const SizedBox(height: 16),
                    _MenuSection(
                      examGoalLabel: _examGoalLabel(targetExam),
                      onEditProfile: () => AppRouter.go(context, AppRoutes.editProfile),
                      onExamGoal: () => AppRouter.go(context, AppRoutes.examGoal),
                      onSettings: () => AppRouter.go(context, AppRoutes.settings),
                      onHelp: () => AppRouter.go(context, AppRoutes.helpSupport),
                      onLogout: () => _logout(context, ref),
                    ).stagger(3),
                    const SizedBox(height: 100),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  static String _displayName(String? name) {
    if (name == null || name.trim().isEmpty) return 'Arjun';
    return name.trim().split(' ').first;
  }

  static String _examGoalLabel(String? exam) {
    if (exam == null || exam.trim().isEmpty) return 'Not set';
    final e = exam.trim();
    if (RegExp(r'\d{4}').hasMatch(e)) return e;
    return '$e ${DateTime.now().year}';
  }

  static Future<void> _logout(BuildContext context, WidgetRef ref) async {
    final c = context.dash;
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('Cancel', style: TextStyle(color: c.textMuted)),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Logout', style: TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
    if (confirm == true) {
      await ref.read(authProvider.notifier).logout();
      if (context.mounted) {
        AppRouter.goClear(context, AppRoutes.login);
      }
    }
  }
}

extension _ProfileAnimate on Widget {
  Widget stagger(int index) => animate(delay: (80 * index).ms)
      .fadeIn(duration: 480.ms, curve: Curves.easeOutCubic)
      .slideY(begin: 0.05, end: 0, curve: Curves.easeOutCubic);
}

class _ProfileTopBar extends StatelessWidget {
  final VoidCallback onBack;
  final VoidCallback onSettings;

  const _ProfileTopBar({required this.onBack, required this.onSettings});

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 8, 12, 4),
      child: Row(
        children: [
          _TopIconBtn(icon: LucideIcons.arrowLeft, onTap: onBack),
          Expanded(
            child: Text(
              'Profile',
              textAlign: TextAlign.center,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: c.textPrimary,
                letterSpacing: -0.3,
              ),
            ),
          ),
          _TopIconBtn(icon: LucideIcons.settings2, onTap: onSettings),
        ],
      ),
    );
  }
}

class _TopIconBtn extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;

  const _TopIconBtn({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: SizedBox(
          width: 44,
          height: 44,
          child: Icon(icon, size: 22, color: context.dash.textPrimary),
        ),
      ),
    );
  }
}

class _ProfileHero extends StatelessWidget {
  final String name;
  final String aspirantLabel;
  final String profileSubtitle;
  final String? avatarUrl;

  const _ProfileHero({
    required this.name,
    required this.aspirantLabel,
    required this.profileSubtitle,
    this.avatarUrl,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Column(
      children: [
        SizedBox(
          width: 108,
          height: 108,
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                width: 108,
                height: 108,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFF7B61FF), AppColors.primary],
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.35),
                      blurRadius: 24,
                      offset: const Offset(0, 10),
                    ),
                  ],
                ),
                child: ClipOval(
                  child: avatarUrl != null && avatarUrl!.isNotEmpty
                      ? Image.network(
                          avatarUrl!,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) =>
                              _AvatarIllustration(name: name),
                        )
                      : _AvatarIllustration(name: name),
                ),
              ),
              Positioned(
                right: 4,
                bottom: 4,
                child: Container(
                  width: 18,
                  height: 18,
                  decoration: BoxDecoration(
                    color: const Color(0xFF22C55E),
                    shape: BoxShape.circle,
                    border: Border.all(color: c.card, width: 2.5),
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 18),
        Text(
          name,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 26,
            fontWeight: FontWeight.w800,
            color: c.textPrimary,
            letterSpacing: -0.5,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          aspirantLabel,
          style: context.bodyMedium.copyWith(
            fontSize: 15,
            fontWeight: FontWeight.w600,
            color: AppColors.primary,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          profileSubtitle,
          style: context.labelSmall.copyWith(fontSize: 13),
        ),
      ],
    );
  }
}

class _AvatarIllustration extends StatelessWidget {
  final String name;
  const _AvatarIllustration({required this.name});

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.primary,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Positioned(
            top: 28,
            child: Container(
              width: 52,
              height: 52,
              decoration: const BoxDecoration(
                color: Color(0xFFFFD4B8),
                shape: BoxShape.circle,
              ),
            ),
          ),
          Positioned(
            top: 52,
            child: Container(
              width: 64,
              height: 36,
              decoration: const BoxDecoration(
                color: Color(0xFF4A90D9),
                borderRadius: BorderRadius.vertical(bottom: Radius.circular(20)),
              ),
            ),
          ),
          Positioned(
            top: 22,
            child: Container(
              width: 58,
              height: 28,
              decoration: const BoxDecoration(
                color: Color(0xFF2C1810),
                borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ProfileStatsCard extends StatelessWidget {
  final int level;
  final int xp;
  final int xpMax;
  final int streak;
  final int tests;
  final int battles;
  final int rankPercentile;

  const _ProfileStatsCard({
    required this.level,
    required this.xp,
    required this.xpMax,
    required this.streak,
    required this.tests,
    required this.battles,
    required this.rankPercentile,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final progress = xpMax > 0 ? (xp / xpMax).clamp(0.0, 1.0) : 0.0;
    final rankLabel = rankPercentile > 0
        ? '#${((100 - rankPercentile) * 50).clamp(1, 9999)}'
        : '—';

    return Container(
      decoration: BoxDecoration(
        color: c.card,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: c.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: context.isDark ? 0.2 : 0.05),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 14),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFF7B61FF), Color(0xFF705CF6)],
              ),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(
                          LucideIcons.hexagon,
                          color: Colors.white,
                          size: 22,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Level $level',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'XP ${_formatXp(xp)} / ${_formatXp(xpMax)}',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.white.withValues(alpha: 0.85),
                              ),
                            ),
                            const SizedBox(height: 8),
                            ClipRRect(
                              borderRadius: BorderRadius.circular(4),
                              child: LinearProgressIndicator(
                                value: progress,
                                minHeight: 5,
                                backgroundColor:
                                    Colors.white.withValues(alpha: 0.25),
                                valueColor: const AlwaysStoppedAnimation<Color>(
                                  Colors.white,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      'Streak',
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.white.withValues(alpha: 0.8),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '$streak Days 🔥',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 18),
            child: Row(
              children: [
                Expanded(child: _StatCell(label: 'Tests', value: '$tests')),
                Container(width: 1, height: 36, color: c.cardBorder),
                Expanded(child: _StatCell(label: 'Battles', value: '$battles')),
                Container(width: 1, height: 36, color: c.cardBorder),
                Expanded(
                  child: _StatCell(label: 'Rank', value: rankLabel),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  static String _formatXp(int n) {
    if (n >= 1000) {
      final k = n / 1000;
      return k == k.roundToDouble()
          ? '${k.toStringAsFixed(0)}k'
          : '${k.toStringAsFixed(1)}k';
    }
    return '$n';
  }
}

class _StatCell extends StatelessWidget {
  final String label;
  final String value;

  const _StatCell({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Column(
      children: [
        Text(label, style: context.labelSmall.copyWith(fontSize: 13)),
        const SizedBox(height: 6),
        Text(
          value,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 22,
            fontWeight: FontWeight.w800,
            color: c.textPrimary,
            letterSpacing: -0.5,
          ),
        ),
      ],
    );
  }
}

class _ProfileBadgesSection extends StatelessWidget {
  final List<ApiBadge> badges;
  final VoidCallback? onViewAll;

  const _ProfileBadgesSection({
    required this.badges,
    this.onViewAll,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final shown = badges.take(5).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              'Badges',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: c.textPrimary,
              ),
            ),
            const Spacer(),
            if (onViewAll != null)
              GestureDetector(
                onTap: onViewAll,
                child: Text(
                  'View all',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.primary,
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(height: 14),
        if (shown.isEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: c.card,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: c.cardBorder),
            ),
            child: Text(
              'Complete quizzes & battles to earn badges',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 13, color: c.textMuted),
            ),
          )
        else
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: shown
                .map((b) => _CircularBadge(badge: b))
                .toList(),
          ),
      ],
    );
  }
}

class _CircularBadge extends StatelessWidget {
  final ApiBadge badge;

  const _CircularBadge({required this.badge});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 56,
      height: 56,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: badge.gradient.first.withValues(alpha: 0.15),
        boxShadow: [
          BoxShadow(
            color: badge.gradient.first.withValues(alpha: 0.25),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Center(
        child: Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: LinearGradient(
              colors: badge.gradient,
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
          child: Icon(badge.iconData, color: Colors.white, size: 20),
        ),
      ),
    );
  }
}

class _MenuSection extends StatelessWidget {
  final String examGoalLabel;
  final VoidCallback onEditProfile;
  final VoidCallback onExamGoal;
  final VoidCallback onSettings;
  final VoidCallback onHelp;
  final VoidCallback onLogout;

  const _MenuSection({
    required this.examGoalLabel,
    required this.onEditProfile,
    required this.onExamGoal,
    required this.onSettings,
    required this.onHelp,
    required this.onLogout,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        _MenuTile(
          icon: LucideIcons.user,
          label: 'Edit Profile',
          onTap: onEditProfile,
        ),
        const SizedBox(height: 10),
        _MenuTile(
          icon: LucideIcons.graduationCap,
          label: 'Exam Goal',
          trailing: examGoalLabel,
          onTap: onExamGoal,
        ),
        const SizedBox(height: 10),
        _MenuTile(
          icon: LucideIcons.settings,
          label: 'Settings',
          onTap: onSettings,
        ),
        const SizedBox(height: 10),
        _MenuTile(
          icon: LucideIcons.helpCircle,
          label: 'Help & Support',
          onTap: onHelp,
        ),
        const SizedBox(height: 10),
        _MenuTile(
          icon: LucideIcons.logOut,
          label: 'Logout',
          onTap: onLogout,
          isDestructive: true,
          showChevron: false,
        ),
      ],
    );
  }
}

class _MenuTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final String? trailing;
  final VoidCallback onTap;
  final bool isDestructive;
  final bool showChevron;

  const _MenuTile({
    required this.icon,
    required this.label,
    required this.onTap,
    this.trailing,
    this.isDestructive = false,
    this.showChevron = true,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final color = isDestructive ? AppColors.error : c.textPrimary;
    final iconColor = isDestructive ? AppColors.error : c.textSecondary;

    return Material(
      color: c.card,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 15),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: c.cardBorder),
          ),
          child: Row(
            children: [
              Icon(icon, size: 21, color: iconColor),
              const SizedBox(width: 14),
              Expanded(
                child: Text(
                  label,
                  style: GoogleFonts.inter(
                    fontSize: 15,
                    fontWeight: FontWeight.w500,
                    color: color,
                  ),
                ),
              ),
              if (trailing != null) ...[
                Flexible(
                  child: Text(
                    trailing!,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: c.textMuted,
                    ),
                  ),
                ),
                const SizedBox(width: 6),
              ],
              if (showChevron)
                Icon(LucideIcons.chevronRight, size: 18, color: c.textMuted),
            ],
          ),
        ),
      ),
    );
  }
}
