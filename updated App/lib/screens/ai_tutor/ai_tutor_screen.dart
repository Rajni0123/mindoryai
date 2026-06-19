import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_markdown/flutter_markdown.dart';
import 'package:speech_to_text/speech_to_text.dart' as stt;
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../models/models.dart';
import '../../providers/app_data_provider.dart';
import '../../providers/providers.dart';

class AiTutorScreen extends ConsumerStatefulWidget {
  const AiTutorScreen({super.key});

  @override
  ConsumerState<AiTutorScreen> createState() => _AiTutorScreenState();
}

class _AiTutorScreenState extends ConsumerState<AiTutorScreen> {
  final _controller = TextEditingController();
  final _scrollController = ScrollController();
  final List<ChatMessage> _messages = [];
  String? _chatId;
  bool _loading = false;
  final stt.SpeechToText _speech = stt.SpeechToText();
  bool _listening = false;

  @override
  void initState() {
    super.initState();
    _initChat();
  }

  Future<void> _initChat() async {
    try {
      final api = ref.read(apiServiceProvider);
      final chat = await api.createChat(title: 'AI Tutor');
      setState(() {
        _chatId = (chat['chat']?['id'] ?? chat['id']).toString();
        _messages.add(ChatMessage(
          id: '0',
          content:
              'Hi! I\'m **BlinkAI**, your study assistant. Ask me anything about Physics, Chemistry, Maths, or any exam topic!',
          isUser: false,
        ));
      });
    } catch (_) {
      setState(() {
        _messages.add(ChatMessage(
          id: '0',
          content: 'Hi! I\'m **BlinkAI**. Ask me any study question!',
          isUser: false,
        ));
      });
    }
  }

  Future<void> _send(String text) async {
    if (text.trim().isEmpty || _loading) return;
    final userText = text.trim();
    setState(() {
      _messages.add(ChatMessage(
        id: 'user_${_messages.length}',
        content: userText,
        isUser: true,
      ));
      _loading = true;
    });
    _controller.clear();
    _scrollToBottom();

    try {
      final api = ref.read(apiServiceProvider);
      if (_chatId != null) {
        final lang = ref.read(languageProvider.notifier).apiValue;
        final result = await api.sendChatMessage(
          _chatId!,
          content: userText,
          language: lang,
        );
        setState(() {
          _messages.add(ChatMessage(
            id: result.messageId ?? 'ai_${_messages.length}',
            content: result.content.isNotEmpty
                ? result.content
                : 'I\'ll help you with that!',
            isUser: false,
            question: result.question,
          ));
        });
      }
    } catch (e) {
      setState(() {
        _messages.add(ChatMessage(
          id: 'demo_${_messages.length}',
          content: _demoResponse(userText),
          isUser: false,
          question: userText,
        ));
      });
    } finally {
      setState(() => _loading = false);
      _scrollToBottom();
    }
  }

  void _showActionToast(String message) {
    FocusManager.instance.primaryFocus?.unfocus();
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text(message),
          behavior: SnackBarBehavior.floating,
          margin: const EdgeInsets.fromLTRB(16, 0, 16, 96),
          duration: const Duration(seconds: 2),
        ),
      );
  }

  Future<void> _copyMessage(ChatMessage msg) async {
    final plain = msg.content
        .replaceAll(RegExp(r'\*\*([^*]+)\*\*'), r'$1')
        .replaceAll(RegExp(r'\*([^*]+)\*'), r'$1')
        .replaceAll(RegExp(r'`([^`]+)`'), r'$1')
        .trim();
    await Clipboard.setData(ClipboardData(text: plain));
    if (mounted) _showActionToast('Copied to clipboard');
  }

  int _messageIndex(ChatMessage msg) =>
      _messages.indexWhere((m) => m.id == msg.id);

  void _updateMessage(ChatMessage msg, ChatMessage updated) {
    final i = _messageIndex(msg);
    if (i < 0) return;
    setState(() => _messages[i] = updated);
  }

  Future<void> _submitFeedback(
    ChatMessage msg, {
    required String type,
    String? reason,
    String? comment,
  }) async {
    if (_chatId == null || msg.isUser) return;

    try {
      final api = ref.read(apiServiceProvider);
      final res = await api.submitAiFeedback(
        chatId: _chatId!,
        messageId: msg.id,
        feedbackType: type,
        messageContent: msg.content,
        question: msg.question,
        feedbackReason: reason,
        userComment: comment,
      );

      _updateMessage(msg, msg.copyWith(feedback: type));

      if (!mounted) return;
      final followUp = res['follow_up'] as Map?;
      _showActionToast(
        type == 'like'
            ? 'Thanks! This helps BlinkAI learn your style.'
            : followUp?['message']?.toString() ??
                'Thanks for the feedback. We\'ll improve!',
      );
    } catch (_) {
      if (mounted) _showActionToast('Could not send feedback. Try again.');
    }
  }

  Future<void> _likeMessage(ChatMessage msg) async {
    if (msg.feedback == 'like') return;
    await _submitFeedback(msg, type: 'like');
  }

  Future<void> _dislikeMessage(ChatMessage msg) async {
    if (msg.feedback == 'dislike') return;

    final reason = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: context.dash.card,
      showDragHandle: true,
      builder: (ctx) {
        final sheetColors = ctx.dash;
        const options = [
          ('too_complex', 'Too complex / hard to understand'),
          ('wrong_answer', 'Wrong or incorrect answer'),
          ('missing_steps', 'Missing steps in explanation'),
          ('needs_examples', 'Needs more examples'),
          ('too_simple', 'Too basic / need more depth'),
          ('language_issue', 'Language preference issue'),
        ];
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'What went wrong?',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: sheetColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 12),
                ...options.map(
                  (opt) => ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text(
                      opt.$2,
                      style: TextStyle(
                        fontSize: 14,
                        color: sheetColors.textSecondary,
                      ),
                    ),
                    onTap: () => Navigator.pop(ctx, opt.$1),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );

    if (reason == null) return;
    await _submitFeedback(msg, type: 'dislike', reason: reason);
  }

  Future<void> _startVoice() async {
    if (_listening) {
      await _speech.stop();
      setState(() => _listening = false);
      return;
    }
    final available = await _speech.initialize();
    if (!available) return;
    setState(() => _listening = true);
    await _speech.listen(
      onResult: (r) {
        if (r.finalResult && r.recognizedWords.isNotEmpty) {
          _send(r.recognizedWords);
          setState(() => _listening = false);
        }
      },
    );
  }

  Future<void> _saveLastNote() async {
    final last = _messages.reversed
        .firstWhere((m) => !m.isUser, orElse: () => _messages.last);
    final ok = await ref.read(savedNotesProvider.notifier).save(
          content: last.content,
          subject: 'AI Tutor',
          source: 'AI Tutor',
        );
    if (mounted) {
      _showActionToast(
        ok ? 'Saved to revision notes' : 'Could not save note',
      );
    }
  }

  Future<void> _generateQuiz() async {
    final lastUser = _messages.reversed
        .firstWhere((m) => m.isUser, orElse: () => _messages.first);
    AppRouter.go(context, AppRoutes.quiz, args: {
      'topic': lastUser.content.length > 60
          ? lastUser.content.substring(0, 60)
          : lastUser.content,
      'subject': 'Physics',
      'examType': 'JEE',
      'language': ref.read(languageProvider.notifier).apiValue,
    });
  }

  String _demoResponse(String q) {
    if (q.toLowerCase().contains('newton')) {
      return '''**Newton\'s Second Law of Motion**

The acceleration of an object is directly proportional to the net force acting on it and inversely proportional to its mass.

**Formula:** F = ma

Where:
- F = Force (Newtons)
- m = mass (kg)
- a = acceleration (m/s²)

**Example:** A 2 kg object accelerated at 3 m/s² experiences F = 2 × 3 = **6 N**''';
    }
    return 'Great question! Let me explain step by step...\n\nThis concept is fundamental for your exam preparation. Would you like a simpler explanation or another example?';
  }

  void _scrollToBottom() {
    Future.delayed(const Duration(milliseconds: 100), () {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;

    return Scaffold(
      backgroundColor: c.background,
      appBar: AppBar(
        backgroundColor: c.background,
        surfaceTintColor: Colors.transparent,
        title: Row(
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                gradient: AppColors.primaryGradient,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.smart_toy_rounded, color: Colors.white, size: 20),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'BlinkAI',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: c.textPrimary,
                  ),
                ),
                Text(
                  'AI Tutor',
                  style: TextStyle(fontSize: 11, color: c.textMuted),
                ),
              ],
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.all(16),
              itemCount: _messages.length + (_loading ? 1 : 0),
              itemBuilder: (context, i) {
                if (i == _messages.length) {
                  return Padding(
                    padding: const EdgeInsets.all(12),
                    child: Row(
                      children: [
                        const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                        const SizedBox(width: 12),
                        Text(
                          'BlinkAI is thinking...',
                          style: TextStyle(color: c.textMuted),
                        ),
                      ],
                    ),
                  );
                }
                return _buildBubble(_messages[i]);
              },
            ),
          ),
          _buildSuggestions(),
          _buildInput(),
        ],
      ),
    );
  }

  Widget _buildBubble(ChatMessage msg) {
    final c = context.dash;
    final isUser = msg.isUser;
    final liked = msg.feedback == 'like';
    final disliked = msg.feedback == 'dislike';
    final aiTextColor = c.textPrimary;
    final aiStrongColor = AppColors.primaryLight;

    return Align(
      alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(14),
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.82),
        decoration: BoxDecoration(
          color: isUser ? AppColors.purpleBubble : c.card,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(18),
            topRight: const Radius.circular(18),
            bottomLeft: Radius.circular(isUser ? 18 : 4),
            bottomRight: Radius.circular(isUser ? 4 : 18),
          ),
          border: isUser ? null : Border.all(color: c.cardBorder),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: context.isDark ? 0.25 : 0.04),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            MarkdownBody(
              data: msg.content,
              styleSheet: MarkdownStyleSheet(
                p: TextStyle(
                  color: isUser ? Colors.white : aiTextColor,
                  fontSize: 14,
                  height: 1.5,
                ),
                strong: TextStyle(
                  color: isUser ? Colors.white : aiStrongColor,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
            if (!isUser) ...[
              const SizedBox(height: 10),
              Row(
                children: [
                  _actionIcon(
                    Icons.copy_rounded,
                    onTap: () => _copyMessage(msg),
                    tooltip: 'Copy',
                  ),
                  _actionIcon(
                    liked ? Icons.thumb_up : Icons.thumb_up_outlined,
                    onTap: liked ? null : () => _likeMessage(msg),
                    tooltip: 'Helpful',
                    active: liked,
                    activeColor: AppColors.success,
                  ),
                  _actionIcon(
                    disliked ? Icons.thumb_down : Icons.thumb_down_outlined,
                    onTap: disliked ? null : () => _dislikeMessage(msg),
                    tooltip: 'Not helpful',
                    active: disliked,
                    activeColor: AppColors.error,
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _actionIcon(
    IconData icon, {
    VoidCallback? onTap,
    String? tooltip,
    bool active = false,
    Color? activeColor,
  }) {
    final c = context.dash;
    final color = active
        ? (activeColor ?? AppColors.primary)
        : c.textMuted;
    return Padding(
      padding: const EdgeInsets.only(right: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.all(4),
          child: Icon(icon, size: 18, color: color),
        ),
      ),
    );
  }

  Widget _buildSuggestions() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          _chip('Explain in simple words', () => _send('Explain in simple words')),
          _chip('Give another example', () => _send('Give another example')),
          _chip('Generate quiz', _generateQuiz),
          _chip('Save note', _saveLastNote),
        ],
      ),
    );
  }

  Widget _chip(String label, VoidCallback onTap) {
    final c = context.dash;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ActionChip(
        label: Text(
          label,
          style: TextStyle(fontSize: 12, color: c.textSecondary),
        ),
        backgroundColor: c.card,
        side: BorderSide(color: c.cardBorder),
        onPressed: onTap,
      ),
    );
  }

  Widget _buildInput() {
    final c = context.dash;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: c.surface,
        border: Border(top: BorderSide(color: c.cardBorder)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: context.isDark ? 0.2 : 0.05),
            blurRadius: 10,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: SafeArea(
        child: Row(
          children: [
            IconButton(
              icon: Icon(
                _listening ? Icons.mic : Icons.mic_rounded,
                color: _listening ? AppColors.error : AppColors.primary,
              ),
              onPressed: _startVoice,
            ),
            Expanded(
              child: TextField(
                controller: _controller,
                style: TextStyle(color: c.textPrimary),
                cursorColor: AppColors.primary,
                decoration: InputDecoration(
                  hintText: 'Ask anything...',
                  hintStyle: TextStyle(color: c.textMuted),
                  filled: true,
                  fillColor: c.card,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: BorderSide(color: c.cardBorder),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: BorderSide(color: c.cardBorder),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: const BorderSide(color: AppColors.primary),
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                ),
                onSubmitted: _send,
              ),
            ),
            GestureDetector(
              onTap: () => _send(_controller.text),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  gradient: AppColors.primaryGradient,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(Icons.send_rounded, color: Colors.white, size: 20),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }
}
