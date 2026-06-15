import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/providers.dart';
import 'battle_friends_section.dart';

// Accent colors (work in light & dark)
const _kPurple = Color(0xFF7B61FF);
const _kBlue = Color(0xFF4D9FFF);
const _kWinGold = Color(0xFFFFD700);
const _kLossOrange = Color(0xFFFF7A00);
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
                    const SizedBox(height: 20),
                    if (_tab == 0) ...[
                      BattleFriendsSection(
                        myName: myName,
                        onStartBattle: () => _startBattle(context),
                      ).animate(delay: 80.ms).fadeIn(duration: 500.ms).slideY(begin: 0.06, end: 0),
                      const SizedBox(height: 28),
                      Text(
                        'Recent Battles',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: context.dash.textPrimary,
                        ),
                      ).animate(delay: 140.ms).fadeIn(duration: 400.ms),
                      const SizedBox(height: 12),
                      _RecentBattlesList()
                          .animate(delay: 180.ms)
                          .fadeIn(duration: 450.ms)
                          .slideY(begin: 0.05, end: 0),
                    ] else if (_tab == 1) ...[
                      _LeaderboardCta(
                        onTap: () => AppRouter.go(context, AppRoutes.leaderboard),
                      ),
                    ] else ...[
                      _LiveRoomsList()
                          .animate(delay: 80.ms).fadeIn(duration: 500.ms),
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
    return Container(
      padding: const EdgeInsets.all(5),
      decoration: BoxDecoration(
        color: t.tabBar,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: t.border),
      ),
      child: Row(
        children: List.generate(_tabs.length, (i) {
          final active = selected == i;
          return Expanded(
            child: GestureDetector(
              onTap: () => onSelect(i),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 280),
                curve: Curves.easeOutCubic,
                padding: const EdgeInsets.symmetric(vertical: 12),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(12),
                  gradient: active
                      ? LinearGradient(
                          colors: [
                            _kPurple.withValues(alpha: 0.35),
                            _kBlue.withValues(alpha: 0.2),
                          ],
                        )
                      : null,
                  boxShadow: active
                      ? [
                          BoxShadow(
                            color: _kPurple.withValues(alpha: 0.35),
                            blurRadius: 16,
                            offset: const Offset(0, 4),
                          ),
                        ]
                      : null,
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      _tabs[i],
                      style: GoogleFonts.inter(
                        fontSize: 14,
                        fontWeight: active ? FontWeight.w700 : FontWeight.w500,
                        color: active ? t.textPrimary : t.textMuted,
                      ),
                    ),
                    if (active) ...[
                      const SizedBox(height: 6),
                      Container(
                        width: 36,
                        height: 3,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(2),
                          gradient: const LinearGradient(
                            colors: [_kPurple, _kBlue],
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: _kPurple.withValues(alpha: 0.8),
                              blurRadius: 8,
                            ),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          );
        }),
      ),
    );
  }
}

// ─── Recent battles ───────────────────────────────────────────────────────────

class _RecentBattlesList extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final t = _BattleTheme.of(context);
    return FutureBuilder(
      future: ref.read(apiServiceProvider).getBattleHistory(),
      builder: (context, snap) {
        final items = snap.data as List? ?? [];
        if (items.isEmpty) {
          return Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: t.card,
              borderRadius: BorderRadius.circular(18),
            ),
            child: Text('No battles yet — start one!',
                style: GoogleFonts.inter(color: t.textMuted, fontSize: 13)),
          );
        }
        return Container(
          decoration: BoxDecoration(
            color: t.card,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: t.border),
          ),
          child: Column(
            children: items.take(5).map((raw) {
              final m = Map<String, dynamic>.from(raw as Map);
              final opponent = m['opponent_name']?.toString() ?? 'Student';
              final won = m['result']?.toString() == 'won';
              return _RecentRow(
                opponent: opponent,
                avatarColor: _kAvatarBlue,
                result: won
                    ? _BattleResult.won
                    : _BattleResult.score(
                        my: m['my_score'] as int? ?? 0,
                        their: m['opponent_score'] as int? ?? 0,
                      ),
              );
            }).toList(),
          ),
        );
      },
    );
  }
}

enum _BattleResultType { won, score }

class _BattleResult {
  final _BattleResultType type;
  final int? myScore;
  final int? theirScore;

  const _BattleResult._(this.type, {this.myScore, this.theirScore});

  static const won = _BattleResult._(_BattleResultType.won);
  static _BattleResult score({required int my, required int their}) =>
      _BattleResult._(_BattleResultType.score, myScore: my, theirScore: their);
}

class _RecentRow extends StatelessWidget {
  final String opponent;
  final Color avatarColor;
  final _BattleResult result;

  const _RecentRow({
    required this.opponent,
    required this.avatarColor,
    required this.result,
  });

  @override
  Widget build(BuildContext context) {
    final t = _BattleTheme.of(context);
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: avatarColor.withValues(alpha: 0.25),
              border: Border.all(color: avatarColor.withValues(alpha: 0.5)),
            ),
            child: Center(
              child: Text(
                opponent[0],
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Text(
              'vs $opponent',
              style: GoogleFonts.inter(
                fontSize: 15,
                fontWeight: FontWeight.w600,
                color: t.textPrimary,
              ),
            ),
          ),
          if (result.type == _BattleResultType.won)
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(LucideIcons.trophy, size: 16, color: _kWinGold),
                const SizedBox(width: 6),
                Text(
                  'You Won',
                  style: GoogleFonts.inter(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: _kWinGold,
                  ),
                ),
              ],
            )
          else
            Text(
              'Score ${result.myScore} - ${result.theirScore}',
              style: GoogleFonts.inter(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: _kLossOrange,
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

// ─── Live rooms ───────────────────────────────────────────────────────────────

class _LiveRoomsList extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final t = _BattleTheme.of(context);
    return FutureBuilder(
      future: ref.read(apiServiceProvider).getBattleRooms(),
      builder: (context, snap) {
        final rooms = snap.data as List? ?? [];
        if (rooms.isEmpty) {
          return _PlaceholderPanel(
            icon: LucideIcons.zap,
            title: 'No Live Rooms',
            subtitle: 'Create a battle or check back soon.',
          );
        }
        return Column(
          children: rooms.map((raw) {
            final r = Map<String, dynamic>.from(raw as Map);
            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Material(
                color: t.card,
                borderRadius: BorderRadius.circular(16),
                child: ListTile(
                  title: Text(r['title']?.toString() ?? 'Battle',
                      style: TextStyle(color: t.textPrimary)),
                  subtitle: Text(
                    '${r['topic']} · ${r['current_players']}/${r['max_players']} players',
                    style: TextStyle(color: t.textMuted, fontSize: 12),
                  ),
                  trailing: Text(r['room_code']?.toString() ?? '',
                      style: const TextStyle(
                          color: _kPurple, fontWeight: FontWeight.w800)),
                  onTap: () {
                    final code = r['room_code']?.toString();
                    if (code != null) {
                      AppRouter.go(context, AppRoutes.battleLobby, args: {'code': code});
                    }
                  },
                ),
              ),
            );
          }).toList(),
        );
      },
    );
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
