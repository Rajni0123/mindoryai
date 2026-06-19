import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../models/user_badges.dart';
import '../core/theme/dashboard_theme.dart';

/// Renders a badge returned by the backend API.
class DynamicBadge extends StatelessWidget {
  final ApiBadge badge;
  final double size;
  final bool showLabel;
  final bool compact;

  const DynamicBadge({
    super.key,
    required this.badge,
    this.size = 40,
    this.showLabel = false,
    this.compact = false,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    final iconSize = size * 0.48;

    final shield = Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: badge.gradient,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(size * 0.22),
        border: Border.all(color: badge.borderColor, width: 1.5),
        boxShadow: [
          BoxShadow(
            color: badge.gradient.first.withOpacity(0.35),
            blurRadius: size * 0.2,
            offset: Offset(0, size * 0.06),
          ),
        ],
      ),
      child: Icon(badge.iconData, color: Colors.white, size: iconSize),
    );

    if (!showLabel) return shield;

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        shield,
        if (!compact) ...[
          SizedBox(height: size * 0.12),
          Text(
            badge.name,
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: GoogleFonts.plusJakartaSans(
              fontSize: size * 0.22,
              fontWeight: FontWeight.w800,
              color: c.textPrimary,
            ),
          ),
          Text(
            badge.tagline,
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(fontSize: size * 0.18, color: c.textMuted),
          ),
        ],
      ],
    );
  }
}

class BadgeShowcaseRow extends StatelessWidget {
  final List<ApiBadge> badges;
  final VoidCallback? onViewAll;

  const BadgeShowcaseRow({
    super.key,
    required this.badges,
    this.onViewAll,
  });

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    if (badges.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              'Your Badges',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 17,
                fontWeight: FontWeight.w800,
                color: c.textPrimary,
              ),
            ),
            const Spacer(),
            if (onViewAll != null)
              GestureDetector(
                onTap: onViewAll,
                child: const Text(
                  'View chart',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF705CF6),
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 72,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: badges.length,
            separatorBuilder: (_, __) => const SizedBox(width: 10),
            itemBuilder: (_, i) => DynamicBadge(
              badge: badges[i],
              size: 52,
              showLabel: true,
              compact: true,
            ),
          ),
        ),
      ],
    );
  }
}

class BadgeGuideSheet extends StatelessWidget {
  const BadgeGuideSheet({super.key});

  static const _asset = 'assets/logo/batches.jpeg';

  static Future<void> show(BuildContext context) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const BadgeGuideSheet(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;
    return DraggableScrollableSheet(
      initialChildSize: 0.88,
      minChildSize: 0.5,
      maxChildSize: 0.95,
      builder: (context, scrollController) {
        return Container(
          decoration: BoxDecoration(
            color: c.surface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              const SizedBox(height: 12),
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: c.cardBorder,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(20),
                child: Text(
                  'BlinkStudy Badge System',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: c.textPrimary,
                  ),
                ),
              ),
              Expanded(
                child: SingleChildScrollView(
                  controller: scrollController,
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 32),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(16),
                    child: Image.asset(_asset, fit: BoxFit.contain),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
