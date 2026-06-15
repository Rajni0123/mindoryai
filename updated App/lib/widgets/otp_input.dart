import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';

class OtpInputRow extends StatefulWidget {
  final int length;
  final ValueChanged<String> onCompleted;
  final ValueChanged<String>? onChanged;
  final bool enabled;

  const OtpInputRow({
    super.key,
    this.length = 4,
    required this.onCompleted,
    this.onChanged,
    this.enabled = true,
  });

  @override
  OtpInputRowState createState() => OtpInputRowState();
}

class OtpInputRowState extends State<OtpInputRow> {
  late final List<TextEditingController> _controllers;
  late final List<FocusNode> _focusNodes;

  @override
  void initState() {
    super.initState();
    _controllers = List.generate(widget.length, (_) => TextEditingController());
    _focusNodes = List.generate(widget.length, (_) => FocusNode());
    for (final node in _focusNodes) {
      node.addListener(() => setState(() {}));
    }
  }

  String get value => _controllers.map((c) => c.text).join();

  void clear() {
    for (final c in _controllers) {
      c.clear();
    }
    _focusNodes.first.requestFocus();
    widget.onChanged?.call('');
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return Row(
      children: List.generate(widget.length, (i) {
        return Expanded(
          child: Padding(
            padding: EdgeInsets.only(right: i == widget.length - 1 ? 0 : 10),
            child: _box(i, c),
          ),
        );
      }),
    );
  }

  Widget _box(int index, DashboardColors c) {
    final focused = _focusNodes[index].hasFocus;
    final filled = _controllers[index].text.isNotEmpty;
    final active = focused || filled;

    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      height: 58,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        border: Border.all(
          color: active ? AppColors.primary : c.cardBorder,
          width: active ? 2 : 1,
        ),
        borderRadius: BorderRadius.circular(14),
        color: c.card,
        boxShadow: active
            ? [
                BoxShadow(
                  color: AppColors.primary.withValues(alpha: 0.15),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ]
            : null,
      ),
      child: TextField(
        controller: _controllers[index],
        focusNode: _focusNodes[index],
        enabled: widget.enabled,
        textAlign: TextAlign.center,
        keyboardType: TextInputType.number,
        maxLength: 1,
        style: context.displayMedium.copyWith(fontSize: 22, letterSpacing: 2),
        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
        decoration: const InputDecoration(
          counterText: '',
          border: InputBorder.none,
          contentPadding: EdgeInsets.zero,
        ),
        onChanged: (val) {
          if (val.isNotEmpty && index < widget.length - 1) {
            _focusNodes[index + 1].requestFocus();
          } else if (val.isEmpty && index > 0) {
            _focusNodes[index - 1].requestFocus();
          }
          setState(() {});
          widget.onChanged?.call(value);
          if (value.length == widget.length) widget.onCompleted(value);
        },
      ),
    );
  }

  @override
  void dispose() {
    for (final c in _controllers) {
      c.dispose();
    }
    for (final f in _focusNodes) {
      f.dispose();
    }
    super.dispose();
  }
}
