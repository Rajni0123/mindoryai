import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/providers.dart';
import '../../widgets/common_widgets.dart';

class BattleLobbyScreen extends ConsumerStatefulWidget {
  const BattleLobbyScreen({super.key});

  @override
  ConsumerState<BattleLobbyScreen> createState() => _BattleLobbyScreenState();
}

class _BattleLobbyScreenState extends ConsumerState<BattleLobbyScreen> {
  Timer? _pollTimer;
  String _code = '';
  int? _roomId;
  String? _friendName;
  bool _isHost = true;
  List<_LobbyPlayer> _players = [];
  bool _canStart = false;
  String _status = 'waiting';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _readArgs());
  }

  void _readArgs() {
    final args =
        ModalRoute.of(context)?.settings.arguments as Map<String, dynamic>?;
    setState(() {
      _code = args?['code']?.toString() ?? '';
      _roomId = args?['roomId'] as int?;
      _friendName = args?['friendName']?.toString();
      _isHost = args?['isHost'] as bool? ?? true;
    });
    if (_roomId != null) _startPolling();
  }

  void _startPolling() {
    _pollTimer?.cancel();
    _pollOnce();
    _pollTimer = Timer.periodic(const Duration(seconds: 2), (_) => _pollOnce());
  }

  Future<void> _pollOnce() async {
    final roomId = _roomId;
    if (roomId == null) return;
    try {
      final res = await ref.read(apiServiceProvider).pollBattle(roomId);
      final data = res['data'] as Map<String, dynamic>? ?? res;
      final participants = data['participants'] as List? ?? [];
      final players = participants.map((p) {
        final m = Map<String, dynamic>.from(p as Map);
        return _LobbyPlayer(
          name: m['name']?.toString() ?? 'Player',
          isHost: m['is_host'] == true,
          status: m['status']?.toString() ?? 'joined',
        );
      }).toList();

      if (!mounted) return;
      setState(() {
        _players = players;
        _canStart = data['can_start'] == true;
        final room = data['room'] as Map<String, dynamic>?;
        _status = room?['status']?.toString() ?? 'waiting';
      });
    } catch (_) {}
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }

  String get _inviteMessage {
    final name = _friendName;
    if (name != null && name.isNotEmpty) {
      return 'Hey $name! Join my BlinkStudy battle 🎯\nRoom code: $_code\nOpen BlinkStudy → Battles → Join with code';
    }
    return 'Join my BlinkStudy battle!\nRoom code: $_code\nOpen BlinkStudy → Battles → Join with code';
  }

  Future<void> _copyCode() async {
    if (_code.isEmpty) return;
    await Clipboard.setData(ClipboardData(text: _inviteMessage));
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Invite copied! Send to your friend on WhatsApp')),
    );
  }

  Future<void> _markReady() async {
    final roomId = _roomId;
    if (roomId == null) return;
    try {
      await ref.read(apiServiceProvider).dio.post('/study-battle/ready', data: {
        'room_id': roomId,
      });
      _pollOnce();
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not mark ready')),
        );
      }
    }
  }

  Future<void> _startBattle() async {
    final roomId = _roomId;
    if (roomId == null) return;
    if (!_isHost) return;
    try {
      await ref.read(apiServiceProvider).dio.post('/study-battle/start', data: {
        'room_id': roomId,
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Battle starting soon!')),
      );
      _pollOnce();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _players.length < 2
                  ? 'Wait for your friend to join with the room code'
                  : 'Could not start battle yet',
            ),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final user = ref.watch(authProvider).user;
    final rawName = user?.name?.trim() ?? '';
    final myName = rawName.isNotEmpty ? rawName.split(' ').first : 'You';

    final displayPlayers = _players.isNotEmpty
        ? _players
        : [_LobbyPlayer(name: myName, isHost: _isHost, status: 'joined')];

    return Scaffold(
      backgroundColor: c.background,
      appBar: AppBar(
        backgroundColor: c.background,
        foregroundColor: c.textPrimary,
        title: const Text('Battle Lobby'),
      ),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            AppCard(
              child: Column(
                children: [
                  Text('Room Code',
                      style: TextStyle(color: c.textMuted, fontSize: 13)),
                  const SizedBox(height: 8),
                  Text(
                    _code.isEmpty ? '—' : _code,
                    style: TextStyle(
                      color: c.textPrimary,
                      fontSize: 34,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 4,
                    ),
                  ),
                  if (_friendName != null) ...[
                    const SizedBox(height: 8),
                    Text(
                      'Send this code to $_friendName',
                      style: TextStyle(fontSize: 12, color: c.textSecondary),
                    ),
                  ],
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      OutlinedButton.icon(
                        onPressed: _code.isEmpty ? null : _copyCode,
                        icon: const Icon(Icons.copy_rounded, size: 16),
                        label: const Text('Copy Invite'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: c.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            Text(
              _status == 'in_progress'
                  ? 'Battle in progress!'
                  : displayPlayers.length > 1
                      ? 'Friend joined — ready up!'
                      : 'Waiting for friend to join...',
              style: TextStyle(color: c.textSecondary, fontSize: 15),
            ),
            const SizedBox(height: 14),
            Expanded(
              child: ListView(
                children: displayPlayers.map((p) {
                  final ready = p.status == 'ready' || p.status == 'playing';
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: AppCard(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          CircleAvatar(
                            backgroundColor:
                                AppColors.primary.withValues(alpha: 0.25),
                            child: Text(
                              p.name.isNotEmpty ? p.name[0].toUpperCase() : '?',
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  p.name,
                                  style: TextStyle(
                                    color: c.textPrimary,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                Text(
                                  p.isHost ? 'Host' : 'Player',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: c.textMuted,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          if (ready)
                            const Icon(Icons.check_circle,
                                color: AppColors.success)
                          else
                            SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: c.textMuted,
                              ),
                            ),
                        ],
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
            if (!_isHost)
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: _markReady,
                  child: const Text('I\'m Ready'),
                ),
              ),
            if (_isHost) ...[
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _canStart ? _startBattle : _copyCode,
                  child: Text(
                    _canStart
                        ? 'Start Battle'
                        : 'Copy Code & Invite Friend',
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _LobbyPlayer {
  final String name;
  final bool isHost;
  final String status;

  const _LobbyPlayer({
    required this.name,
    required this.isHost,
    required this.status,
  });
}
