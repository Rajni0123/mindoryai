import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/providers.dart';

class BattleFriendsSection extends ConsumerStatefulWidget {
  const BattleFriendsSection({
    super.key,
    required this.myName,
    required this.onStartBattle,
  });

  final String myName;
  final Future<void> Function() onStartBattle;

  @override
  ConsumerState<BattleFriendsSection> createState() =>
      _BattleFriendsSectionState();
}

class _BattleFriendsSectionState extends ConsumerState<BattleFriendsSection> {
  final _joinCodeCtrl = TextEditingController();
  bool _joining = false;
  bool _starting = false;

  @override
  void dispose() {
    _joinCodeCtrl.dispose();
    super.dispose();
  }

  Future<void> _joinWithCode() async {
    final code = _joinCodeCtrl.text.trim().toUpperCase();
    if (code.length < 4) {
      _snack('Enter a valid room code');
      return;
    }
    setState(() => _joining = true);
    try {
      final res = await ref.read(apiServiceProvider).joinBattle(code);
      final data = res['data'] as Map<String, dynamic>?;
      if (!mounted) return;
      AppRouter.go(context, AppRoutes.battleLobby, args: {
        'code': data?['room_code']?.toString() ?? code,
        'roomId': data?['room_id'],
        'isHost': false,
      });
    } catch (_) {
      if (mounted) _snack('Could not join. Check the code and try again.');
    } finally {
      if (mounted) setState(() => _joining = false);
    }
  }

  Future<void> _startBattle() async {
    setState(() => _starting = true);
    try {
      await widget.onStartBattle();
    } finally {
      if (mounted) setState(() => _starting = false);
    }
  }

  void _snack(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _StartBattleCard(
          myName: widget.myName,
          starting: _starting,
          onStart: _startBattle,
        ),
        const SizedBox(height: 14),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: c.card,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: c.cardBorder),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: context.isDark ? 0.2 : 0.04),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Join with Code',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: c.textPrimary,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Enter room code shared by your friend',
                style: TextStyle(fontSize: 12, color: c.textMuted),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _joinCodeCtrl,
                      textCapitalization: TextCapitalization.characters,
                      style: TextStyle(color: c.textPrimary, fontSize: 14),
                      decoration: InputDecoration(
                        hintText: 'Enter room code',
                        hintStyle: TextStyle(color: c.textMuted, fontSize: 13),
                        filled: true,
                        fillColor: c.background,
                        isDense: true,
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 12,
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: c.cardBorder),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: c.cardBorder),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: const BorderSide(color: AppColors.primary),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  SizedBox(
                    height: 44,
                    child: ElevatedButton(
                      onPressed: _joining ? null : _joinWithCode,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 18),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        elevation: 0,
                      ),
                      child: Text(
                        _joining ? '...' : 'Join',
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _StartBattleCard extends StatelessWidget {
  const _StartBattleCard({
    required this.myName,
    required this.starting,
    required this.onStart,
  });

  final String myName;
  final bool starting;
  final VoidCallback onStart;

  static const _kBlue = Color(0xFF4D9FFF);
  static const _kAvatarBlue = Color(0xFF5B8CFF);
  static const _kAvatarOrange = Color(0xFFFF9F5A);

  @override
  Widget build(BuildContext context) {
    final c = context.dash;

    return Container(
      padding: const EdgeInsets.fromLTRB(18, 20, 18, 20),
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
      child: Column(
        children: [
          Text(
            'Battle with Friend',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: c.textPrimary,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Challenge your friend and win XP!',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 13, color: c.textMuted, height: 1.35),
          ),
          const SizedBox(height: 22),
          Row(
            children: [
              Expanded(
                child: _Avatar(
                  name: myName,
                  label: 'You',
                  color: _kAvatarBlue,
                ),
              ),
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 8),
                child: Text(
                  'VS',
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                    color: _kBlue,
                    fontSize: 20,
                  ),
                ),
              ),
              const Expanded(
                child: _Avatar(
                  name: 'Friend',
                  label: 'Waiting',
                  color: _kAvatarOrange,
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Wrap(
            alignment: WrapAlignment.center,
            spacing: 8,
            runSpacing: 8,
            children: [
              _SettingChip(label: 'Physics', icon: Icons.bolt_outlined, c: c),
              _SettingChip(
                label: '10 Questions',
                icon: Icons.quiz_outlined,
                c: c,
              ),
              _SettingChip(
                label: '5 mins',
                icon: Icons.timer_outlined,
                c: c,
              ),
            ],
          ),
          const SizedBox(height: 18),
          SizedBox(
            width: double.infinity,
            height: 50,
            child: ElevatedButton(
              onPressed: starting ? null : onStart,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
                elevation: 0,
              ),
              child: Text(
                starting ? 'Creating room...' : 'Start Battle ⚡',
                style: GoogleFonts.plusJakartaSans(
                  fontWeight: FontWeight.w700,
                  fontSize: 15,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SettingChip extends StatelessWidget {
  final String label;
  final IconData icon;
  final DashboardColors c;

  const _SettingChip({
    required this.label,
    required this.icon,
    required this.c,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: c.background,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: c.cardBorder),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: c.textMuted),
          const SizedBox(width: 5),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: c.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({
    required this.name,
    required this.label,
    required this.color,
  });

  final String name;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';

    return Column(
      children: [
        Stack(
          clipBehavior: Clip.none,
          children: [
            Container(
              width: 60,
              height: 60,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: color.withValues(alpha: 0.15),
                border: Border.all(color: color, width: 2),
              ),
              child: Center(
                child: Text(
                  initial,
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: color,
                  ),
                ),
              ),
            ),
            Positioned(
              bottom: -2,
              right: -2,
              child: Container(
                width: 22,
                height: 22,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: color,
                  shape: BoxShape.circle,
                  border: Border.all(color: c.card, width: 2),
                ),
                child: Text(
                  initial,
                  style: const TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Text(
          name.length > 10 ? '${name.substring(0, 10)}…' : name,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: c.textPrimary,
          ),
        ),
        Text(label, style: TextStyle(fontSize: 11, color: c.textMuted)),
      ],
    );
  }
}
