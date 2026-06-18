import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/app_data_provider.dart';
import '../../providers/providers.dart';

class LeaderboardScreen extends ConsumerStatefulWidget {
  const LeaderboardScreen({super.key});

  @override
  ConsumerState<LeaderboardScreen> createState() => _LeaderboardScreenState();
}

class _LeaderboardScreenState extends ConsumerState<LeaderboardScreen> {
  bool _friendsTab = true;

  @override
  Widget build(BuildContext context) {
    final t = _LbTheme.of(context);
    final myName = _firstName(ref.watch(authProvider).user?.name);
    final lbAsync = ref.watch(leaderboardProvider);

    return Scaffold(
      backgroundColor: t.bg,
      body: SafeArea(
        child: Column(
          children: [
            _Header(t: t),
            Expanded(
              child: lbAsync.when(
                loading: () =>
                    const Center(child: CircularProgressIndicator(color: AppColors.primary)),
                error: (_, __) => Center(
                  child: Text('Could not load leaderboard',
                      style: GoogleFonts.inter(color: t.muted)),
                ),
                data: (entries) {
                  if (entries.isEmpty) {
                    return Center(
                      child: Text('No rankings yet — play a battle!',
                          style: GoogleFonts.inter(color: t.muted)),
                    );
                  }
                  final users = entries.map((e) {
                    final name = e.isMe ? myName : e.name;
                    return _LbUser(
                      rank: e.rank,
                      name: name,
                      score: e.score,
                      ring: _ringForRank(e.rank),
                      isMe: e.isMe || name == myName,
                    );
                  }).toList();
                  final podium = users.take(3).toList();
                  final list = users.length > 3 ? users.sublist(3) : <_LbUser>[];

                  return SingleChildScrollView(
                    physics: const BouncingScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
                    child: Column(
                      children: [
                        _TabToggle(
                          t: t,
                          friendsSelected: _friendsTab,
                          onSelect: (friends) => setState(() => _friendsTab = friends),
                        ).animate().fadeIn(duration: 400.ms),
                        const SizedBox(height: 24),
                        if (podium.isNotEmpty)
                          _PodiumRow(users: podium, t: t)
                              .animate(delay: 80.ms)
                              .fadeIn(duration: 500.ms)
                              .slideY(begin: 0.06, end: 0),
                        const SizedBox(height: 20),
                        if (list.isNotEmpty)
                          _RankList(users: list, t: t)
                              .animate(delay: 160.ms)
                              .fadeIn(duration: 450.ms)
                              .slideY(begin: 0.05, end: 0),
                        const SizedBox(height: 20),
                        Text(
                          'Live from blinkstudy.in',
                          style: GoogleFonts.inter(fontSize: 12, color: t.muted),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  static Color _ringForRank(int rank) {
    switch (rank) {
      case 1:
        return const Color(0xFFFFD700);
      case 2:
        return const Color(0xFFFF9F43);
      case 3:
        return const Color(0xFFE879F9);
      default:
        return const Color(0xFF5B8CFF);
    }
  }

  static String _firstName(String? name) {
    if (name == null || name.trim().isEmpty) return 'Arjun';
    return name.trim().split(' ').first;
  }
}

// ─── Theme ────────────────────────────────────────────────────────────────────

class _LbTheme {
  final Color bg;
  final Color card;
  final Color cardBorder;
  final Color text;
  final Color muted;
  final Color tabBg;
  final Color gold;
  final bool isDark;

  const _LbTheme({
    required this.bg,
    required this.card,
    required this.cardBorder,
    required this.text,
    required this.muted,
    required this.tabBg,
    required this.gold,
    required this.isDark,
  });

  factory _LbTheme.of(BuildContext context) {
    final c = context.dash;
    final dark = context.isDark;
    return _LbTheme(
      bg: dark ? const Color(0xFF0F0F1E) : c.background,
      card: dark ? const Color(0xFF16162A) : c.card,
      cardBorder: dark ? const Color(0xFF252540) : c.cardBorder,
      text: c.textPrimary,
      muted: c.textMuted,
      tabBg: dark ? const Color(0xFF1A1A30) : c.surface,
      gold: const Color(0xFFFFD700),
      isDark: dark,
    );
  }
}

// ─── Data ─────────────────────────────────────────────────────────────────────

class _LbUser {
  final int rank;
  final String name;
  final int score;
  final Color ring;
  final bool isMe;

  const _LbUser({
    required this.rank,
    required this.name,
    required this.score,
    required this.ring,
    this.isMe = false,
  });

  _LbUser copyWith({String? name}) => _LbUser(
        rank: rank,
        name: name ?? this.name,
        score: score,
        ring: ring,
        isMe: isMe,
      );
}

// ─── Header ───────────────────────────────────────────────────────────────────

class _Header extends StatelessWidget {
  final _LbTheme t;
  const _Header({required this.t});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 10, 12, 6),
      child: Row(
        children: [
          _IconBtn(
            icon: LucideIcons.arrowLeft,
            color: t.text,
            onTap: () => AppRouter.back(context),
          ),
          Expanded(
            child: Text(
              'Leaderboard',
              textAlign: TextAlign.center,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: t.text,
              ),
            ),
          ),
          _IconBtn(
            icon: LucideIcons.clock,
            color: t.text,
            onTap: () {},
          ),
        ],
      ),
    );
  }
}

class _IconBtn extends StatelessWidget {
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _IconBtn({required this.icon, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: SizedBox(width: 44, height: 44, child: Icon(icon, size: 22, color: color)),
      ),
    );
  }
}

// ─── Tab toggle ───────────────────────────────────────────────────────────────

class _TabToggle extends StatelessWidget {
  final _LbTheme t;
  final bool friendsSelected;
  final ValueChanged<bool> onSelect;

  const _TabToggle({
    required this.t,
    required this.friendsSelected,
    required this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: t.tabBg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: t.cardBorder),
      ),
      child: Row(
        children: [
          _tab('Global', !friendsSelected, () => onSelect(false)),
          _tab('Friends', friendsSelected, () => onSelect(true)),
        ],
      ),
    );
  }

  Widget _tab(String label, bool active, VoidCallback onTap) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeOutCubic,
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(10),
            gradient: active
                ? const LinearGradient(
                    colors: [Color(0xFF7B61FF), Color(0xFF4D9FFF)],
                  )
                : null,
            boxShadow: active
                ? [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.35),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ]
                : null,
          ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: GoogleFonts.inter(
              fontSize: 14,
              fontWeight: active ? FontWeight.w700 : FontWeight.w500,
              color: active ? Colors.white : t.muted,
            ),
          ),
        ),
      ),
    );
  }
}

// ─── Podium ───────────────────────────────────────────────────────────────────

class _PodiumRow extends StatelessWidget {
  final List<_LbUser> users;
  final _LbTheme t;

  const _PodiumRow({required this.users, required this.t});

  @override
  Widget build(BuildContext context) {
    // Order: rank 2, rank 1, rank 3
    final ordered = [
      users.firstWhere((u) => u.rank == 2),
      users.firstWhere((u) => u.rank == 1),
      users.firstWhere((u) => u.rank == 3),
    ];
    final heights = [120.0, 150.0, 100.0];

    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: List.generate(3, (i) {
        return Expanded(
          child: Padding(
            padding: EdgeInsets.only(left: i > 0 ? 6 : 0, right: i < 2 ? 6 : 0),
            child: _PodiumCard(user: ordered[i], height: heights[i], t: t),
          ),
        );
      }),
    );
  }
}

class _PodiumCard extends StatelessWidget {
  final _LbUser user;
  final double height;
  final _LbTheme t;

  const _PodiumCard({
    required this.user,
    required this.height,
    required this.t,
  });

  @override
  Widget build(BuildContext context) {
    final isFirst = user.rank == 1;
    return Column(
      children: [
        _AvatarRing(name: user.name, ring: user.ring, size: isFirst ? 64 : 54),
        const SizedBox(height: 10),
        Container(
          height: height,
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: t.isDark
                  ? [
                      const Color(0xFF1E1E38),
                      const Color(0xFF141428),
                    ]
                  : [
                      t.card,
                      t.card.withValues(alpha: 0.85),
                    ],
            ),
            border: Border.all(
              color: isFirst
                  ? t.gold.withValues(alpha: 0.4)
                  : t.cardBorder,
            ),
            boxShadow: [
              if (isFirst)
                BoxShadow(
                  color: t.gold.withValues(alpha: 0.2),
                  blurRadius: 16,
                  offset: const Offset(0, 6),
                )
              else
                BoxShadow(
                  color: Colors.black.withValues(alpha: t.isDark ? 0.2 : 0.06),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
            ],
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                user.name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: GoogleFonts.inter(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: isFirst || user.isMe ? t.gold : t.text,
                ),
              ),
              if (user.isMe) ...[
                const SizedBox(height: 2),
                Text(
                  'You',
                  style: GoogleFonts.inter(
                    fontSize: 11,
                    color: t.gold.withValues(alpha: 0.8),
                  ),
                ),
              ],
              const SizedBox(height: 8),
              Text(
                '${user.score}',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: isFirst ? 22 : 18,
                  fontWeight: FontWeight.w800,
                  color: t.gold,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _AvatarRing extends StatelessWidget {
  final String name;
  final Color ring;
  final double size;

  const _AvatarRing({
    required this.name,
    required this.ring,
    required this.size,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size + 8,
      height: size + 8,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: ring.withValues(alpha: 0.6),
            blurRadius: 14,
            spreadRadius: 1,
          ),
        ],
        border: Border.all(color: ring, width: 3),
      ),
      child: CircleAvatar(
        radius: size / 2,
        backgroundColor: ring.withValues(alpha: 0.2),
        child: Text(
          name.isNotEmpty ? name[0].toUpperCase() : '?',
          style: GoogleFonts.plusJakartaSans(
            fontSize: size * 0.35,
            fontWeight: FontWeight.w800,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}

// ─── Rank list ────────────────────────────────────────────────────────────────

class _RankList extends StatelessWidget {
  final List<_LbUser> users;
  final _LbTheme t;

  const _RankList({required this.users, required this.t});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: t.card,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: t.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: t.isDark ? 0.15 : 0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: List.generate(users.length, (i) {
          final u = users[i];
          return Column(
            children: [
              if (i > 0) Divider(height: 1, color: t.cardBorder, indent: 56),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                child: Row(
                  children: [
                    SizedBox(
                      width: 24,
                      child: Text(
                        '${u.rank}',
                        style: GoogleFonts.inter(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: t.muted,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    CircleAvatar(
                      radius: 18,
                      backgroundColor: u.ring.withValues(alpha: 0.2),
                      child: Text(
                        u.name[0],
                        style: TextStyle(
                          color: u.ring,
                          fontWeight: FontWeight.w700,
                          fontSize: 14,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        u.name,
                        style: GoogleFonts.inter(
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                          color: t.text,
                        ),
                      ),
                    ),
                    Text(
                      '${u.score}',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: t.text,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          );
        }),
      ),
    );
  }
}
