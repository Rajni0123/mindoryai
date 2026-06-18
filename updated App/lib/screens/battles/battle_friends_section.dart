import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/providers.dart';
import '../../widgets/common_widgets.dart';

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
        const SizedBox(height: 16),
        AppCard(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Join with Code',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: c.textPrimary,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Friend ne jo code bheja hai, yahan daalo',
                style: TextStyle(fontSize: 12, color: c.textMuted),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _joinCodeCtrl,
                      textCapitalization: TextCapitalization.characters,
                      decoration: InputDecoration(
                        hintText: 'Room code',
                        isDense: true,
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 12,
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: c.cardBorder),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  SizedBox(
                    height: 44,
                    child: ElevatedButton(
                      onPressed: _joining ? null : _joinWithCode,
                      child: Text(_joining ? '...' : 'Join'),
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

  static const _kPurple = Color(0xFF7B61FF);
  static const _kBlue = Color(0xFF4D9FFF);
  static const _kAvatarBlue = Color(0xFF5B8CFF);
  static const _kAvatarOrange = Color(0xFFFF9F5A);

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final isDark = context.isDark;

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(22),
        gradient: LinearGradient(
          colors: [
            _kPurple.withValues(alpha: isDark ? 0.5 : 0.25),
            _kBlue.withValues(alpha: isDark ? 0.35 : 0.18),
          ],
        ),
      ),
      child: Container(
        margin: const EdgeInsets.all(1.2),
        padding: const EdgeInsets.fromLTRB(20, 22, 20, 22),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(21),
          color: isDark ? const Color(0xFF151D35) : c.card,
        ),
        child: Column(
          children: [
            Text(
              'Battle with Friend',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: c.textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Start karo — room code milega. Code friend ko bhejo, wo join kar lega.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 13, color: c.textMuted, height: 1.35),
            ),
            const SizedBox(height: 24),
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
                  padding: EdgeInsets.symmetric(horizontal: 6),
                  child: Text(
                    'VS',
                    style: TextStyle(
                      fontWeight: FontWeight.w900,
                      color: _kBlue,
                      fontSize: 18,
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
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: starting ? null : onStart,
                child: Text(
                  starting ? 'Creating room...' : 'Start Battle',
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
              ),
            ),
          ],
        ),
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
        Container(
          width: 64,
          height: 64,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: color.withValues(alpha: 0.2),
            border: Border.all(color: color.withValues(alpha: 0.7), width: 2.5),
          ),
          child: Center(
            child: Text(
              initial,
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w800,
                color: Colors.white,
              ),
            ),
          ),
        ),
        const SizedBox(height: 8),
        Text(
          name.length > 12 ? '${name.substring(0, 12)}…' : name,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: c.textPrimary,
          ),
        ),
        Text(label, style: TextStyle(fontSize: 11, color: c.textMuted)),
      ],
    );
  }
}
