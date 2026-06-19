import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/providers.dart';
import '../../providers/app_data_provider.dart';
import 'battle_friends_section.dart';

// Accent colors (work in light & dark)
const _kPurple = Color(0xFF7B61FF);
const _kBlue = Color(0xFF4D9FFF);
const _kWinGold = Color(0xFFFFD700);
const _kAvatarBlue = Color(0xFF5B8CFF);

@immutable
class _BattleTheme {
  const _BattleTheme(this.dash, this.isDark);
  final DashboardColors dash;
  final bool isDark;

  Color get bg => dash.background;
  Color get card => dash.card;
  Color get cardInner =>
      isDark ? const Color(0xFF151D35) : const Color(0xFFF5F0FF);
  Color get tabBar => dash.surface;
  Color get textPrimary => dash.textPrimary;
  Color get textMuted => dash.textMuted;
  Color get border => dash.cardBorder;

  static _BattleTheme of(BuildContext context) =>
      _BattleTheme(context.dash, context.isDark);
}

class BattlesScreen extends ConsumerStatefulWidget {
  const BattlesScreen({super.key});

  @override
  ConsumerState<BattlesScreen> createState() => _BattlesScreenState();
}

class _BattlesScreenState extends ConsumerState<BattlesScreen> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authProvider).user;
    final myName = _firstName(user?.name);

    return Scaffold(
      backgroundColor: context.dash.background,
      body: SafeArea(
        child: Column(
          children: [
            _BattleHeader(
              onBack: () => ref.read(navIndexProvider.notifier).state = 0,
            ),
            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 100),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _BattleTabBar(
                      selected: _tab,
                      onSelect: (i) => setState(() => _tab = i),
                    ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.04, end: 0),
                    const SizedBox(height: 18),
                    if (_tab == 0) ...[
                      BattleFriendsSection(
                        myName: myName,
                        onStartBattle: () => _startBattle(context),
                      ).animate(delay: 80.ms).fadeIn(duration: 500.ms).slideY(begin: 0.06, end: 0),
                      const SizedBox(height: 22),
                      _LiveBattlesSection(
                        compact: true,
                        onViewAll: () => setState(() => _tab = 2),
                      ).animate(delay: 120.ms).fadeIn(duration: 450.ms),
                      const SizedBox(height: 22),
                      const _YourStatsCard()
                          .animate(delay: 160.ms)
                          .fadeIn(duration: 450.ms)
                          .slideY(begin: 0.04, end: 0),
                    ] else if (_tab == 1) ...[
                      _LeaderboardCta(
                        onTap: () => AppRouter.go(context, AppRoutes.leaderboard),
                      ),
                    ] else ...[
                      _LiveBattlesSection(compact: false)
                          .animate(delay: 80.ms)
                          .fadeIn(duration: 500.ms),
                    ],
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _startBattle(BuildContext context) async {
    try {
      final res = await ref.read(apiServiceProvider).createBattle(
            topic: 'Physics — Laws of Motion',
            subject: 'Physics',
            maxPlayers: 2,
          );
      final data = res['data'] as Map<String, dynamic>?;
      final code = data?['room_code']?.toString() ?? '';
      final roomId = data?['room_id'];
      if (!context.mounted) return;
      AppRouter.go(context, AppRoutes.battleLobby, args: {
        'code': code,
        'roomId': roomId,
        'isHost': true,
      });
    } catch (e) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not create battle')),
      );
    }
  }

  static String _firstName(String? name) {
    if (name == null || name.trim().isEmpty) return 'Arjun';
    return name.trim().split(' ').first;
  }
}

// ─── Header ───────────────────────────────────────────────────────────────────

class _BattleHeader extends StatelessWidget {
  final VoidCallback onBack;
  const _BattleHeader({required this.onBack});

  @override
  Widget build(BuildContext context) {
    final t = _BattleTheme.of(context);
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 10, 16, 6),
      child: Row(
        children: [
          _HeaderBtn(icon: LucideIcons.arrowLeft, onTap: onBack),
          Expanded(
            child: Text(
              'Battle',
              textAlign: TextAlign.center,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: t.textPrimary,
              ),
            ),
          ),
          _HeaderBtn(
            icon: LucideIcons.hexagon,
            onTap: () => AppRouter.go(context, AppRoutes.plans),
          ),
        ],
      ),
    );
  }
}

class _HeaderBtn extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;
  const _HeaderBtn({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final t = _BattleTheme.of(context);
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: SizedBox(
          width: 44,
          height: 44,
          child: Icon(icon, size: 22, color: t.textPrimary),
        ),
      ),
    );
  }
}

// ─── Tab bar ──────────────────────────────────────────────────────────────────

class _BattleTabBar extends StatelessWidget {
  final int selected;
  final ValueChanged<int> onSelect;

  const _BattleTabBar({required this.selected, required this.onSelect});

  static const _tabs = ['Friends', 'Topper', 'Live'];

  @override
  Widget build(BuildContext context) {
    final t = _BattleTheme.of(context);
    return Row(
      children: List.generate(_tabs.length, (i) {
        final active = selected == i;
        final isLiveTab = i == 2;
        return Expanded(
          child: GestureDetector(
            onTap: () => onSelect(i),
            behavior: HitTestBehavior.opaque,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      _tabs[i],
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 14,
                        fontWeight: active ? FontWeight.w700 : FontWeight.w500,
                        color: active ? AppColors.primary : t.textMuted,
                      ),
                    ),
                    if (isLiveTab) ...[
                      const SizedBox(width: 5),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 6,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFF3B30),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: const Text(
                          'Live',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 9,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 10),
                AnimatedContainer(
                  duration: const Duration(milliseconds: 220),
                  height: 3,
                  width: active ? 48 : 0,
                  decoration: BoxDecoration(
                    color: AppColors.primary,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ],
            ),
          ),
        );
      }),
    );
  }
}

// ─── Live battles preview / full list ─────────────────────────────────────────

class _LiveBattlesSection extends ConsumerWidget {
  const _LiveBattlesSection({
    required this.compact,
    this.onViewAll,
  });

  final bool compact;
  final VoidCallback? onViewAll;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final t = _BattleTheme.of(context);

    return FutureBuilder(
      future: ref.read(apiServiceProvider).getBattleRooms(),
      builder: (context, snap) {
        final rooms = snap.data as List? ?? [];
        final limit = compact ? 3 : rooms.length;

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (compact)
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Live Battles',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: t.textPrimary,
                      ),
                    ),
                  ),
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
              )
            else
              Text(
                'Live Battles',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: t.textPrimary,
                ),
              ),
            const SizedBox(height: 12),
            if (snap.connectionState == ConnectionState.waiting)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(20),
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              )
            else if (rooms.isEmpty)
              _PlaceholderPanel(
                icon: LucideIcons.zap,
                title: 'No Live Battles',
                subtitle: 'Start a battle or check back soon.',
              )
            else
              ...rooms.take(limit).map((raw) {
                final r = Map<String, dynamic>.from(raw as Map);
                return Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: _LiveBattleRow(room: r),
                );
              }),
          ],
        );
      },
    );
  }
}

class _LiveBattleRow extends StatelessWidget {
  const _LiveBattleRow({required this.room});

  final Map<String, dynamic> room;

  String _firstName(String? name) {
    if (name == null || name.trim().isEmpty) return 'Player';
    return name.trim().split(' ').first;
  }

  @override
  Widget build(BuildContext context) {
    final t = _BattleTheme.of(context);
    final host = _firstName(room['host_name']?.toString());
    final players = room['current_players'] as int? ?? 1;
    final opponent = players > 1 ? 'Riya' : 'Waiting';
    final topic = room['topic']?.toString() ?? room['title']?.toString() ?? 'Physics';
    final subject = topic.split('—').first.split('-').first.trim();
    final qCount = room['question_count'] as int? ?? 10;
    final watchers = ((room['current_players'] as int? ?? 1) * 21) + 12;
    final code = room['room_code']?.toString();

    return Material(
      color: t.card,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: code != null
            ? () => AppRouter.go(context, AppRoutes.battleLobby, args: {'code': code})
            : null,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: t.border),
          ),
          child: Row(
            children: [
              _MiniAvatarPair(name1: host, name2: opponent),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '$host VS $opponent',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: t.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      '$subject • $qCount Qs',
                      style: TextStyle(fontSize: 12, color: t.textMuted),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 6,
                        height: 6,
                        decoration: const BoxDecoration(
                          color: Color(0xFFFF3B30),
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        'Live',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: const Color(0xFFFF3B30),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '$watchers watching',
                    style: TextStyle(fontSize: 11, color: t.textMuted),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _MiniAvatarPair extends StatelessWidget {
  const _MiniAvatarPair({required this.name1, required this.name2});

  final String name1;
  final String name2;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 52,
      height: 32,
      child: Stack(
        children: [
          Positioned(
            left: 0,
            child: _MiniAvatar(name: name1, color: _kAvatarBlue),
          ),
          Positioned(
            left: 22,
            child: _MiniAvatar(
              name: name2,
              color: const Color(0xFFFF9F5A),
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniAvatar extends StatelessWidget {
  const _MiniAvatar({required this.name, required this.color});

  final String name;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';
    return Container(
      width: 32,
      height: 32,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: color.withValues(alpha: 0.2),
        border: Border.all(color: color, width: 1.5),
      ),
      child: Center(
        child: Text(
          initial,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w800,
            color: color,
          ),
        ),
      ),
    );
  }
}

// ─── Your stats ───────────────────────────────────────────────────────────────

class _YourStatsCard extends ConsumerWidget {
  const _YourStatsCard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(homeDashboardProvider);

    return FutureBuilder(
      future: ref.read(apiServiceProvider).getBattleHistory(),
      builder: (context, snap) {
        final items = snap.data as List? ?? [];
        var won = 0;
        var xpFromBattles = 0;
        for (final raw in items) {
          final m = Map<String, dynamic>.from(raw as Map);
          if (m['result']?.toString() == 'won') won++;
          xpFromBattles += (m['xp_earned'] as num?)?.toInt() ??
              (m['xp'] as num?)?.toInt() ??
              0;
        }
        final total = items.length;
        final winRate = total > 0 ? ((won / total) * 100).round() : 0;
        final xp = xpFromBattles > 0 ? xpFromBattles : dashboard.xp;

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Your Stats',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: context.dash.textPrimary,
              ),
            ),
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(18, 20, 14, 20),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(20),
                gradient: const LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFF7B61FF), Color(0xFF705CF6)],
                ),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.35),
                    blurRadius: 20,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Row(
                      children: [
                        _StatColumn(
                          value: won > 0 ? '$won' : '0',
                          label: 'Battles Won',
                        ),
                        _StatColumn(
                          value: total > 0 ? '$winRate%' : '—',
                          label: 'Win Rate',
                        ),
                        _StatColumn(
                          value: xp > 0 ? '$xp' : '0',
                          label: 'XP Earned',
                        ),
                      ],
                    ),
                  ),
                  Container(
                    width: 52,
                    height: 52,
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.15),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      LucideIcons.shield,
                      color: Colors.white,
                      size: 26,
                    ),
                  ),
                ],
              ),
            ),
          ],
        );
      },
    );
  }
}

class _StatColumn extends StatelessWidget {
  const _StatColumn({required this.value, required this.label});

  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Text(
            value,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w500,
              color: Colors.white.withValues(alpha: 0.85),
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Leaderboard CTA ──────────────────────────────────────────────────────────

class _LeaderboardCta extends StatelessWidget {
  final VoidCallback onTap;
  const _LeaderboardCta({required this.onTap});

  @override
  Widget build(BuildContext context) {
    final t = _BattleTheme.of(context);
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Ink(
          width: double.infinity,
          padding: const EdgeInsets.all(22),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            gradient: LinearGradient(
              colors: [
                _kPurple.withValues(alpha: t.isDark ? 0.35 : 0.15),
                _kBlue.withValues(alpha: t.isDark ? 0.2 : 0.1),
              ],
            ),
            border: Border.all(color: _kPurple.withValues(alpha: 0.4)),
          ),
          child: Column(
            children: [
              const Icon(LucideIcons.trophy, size: 40, color: _kWinGold),
              const SizedBox(height: 14),
              Text(
                'View Leaderboard',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: t.textPrimary,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                'See where you rank among friends & toppers',
                textAlign: TextAlign.center,
                style: GoogleFonts.inter(fontSize: 13, color: t.textMuted),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(colors: [_kPurple, _kBlue]),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  'Open Leaderboard',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    ).animate().fadeIn(duration: 400.ms);
  }
}

// ─── Placeholder tabs ─────────────────────────────────────────────────────────

class _PlaceholderPanel extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;

  const _PlaceholderPanel({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    final t = _BattleTheme.of(context);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: t.card,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: t.border),
      ),
      child: Column(
        children: [
          Icon(icon, size: 40, color: _kPurple),
          const SizedBox(height: 16),
          Text(
            title,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: t.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            subtitle,
            textAlign: TextAlign.center,
            style: GoogleFonts.inter(fontSize: 14, color: t.textMuted),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 400.ms);
  }
}
