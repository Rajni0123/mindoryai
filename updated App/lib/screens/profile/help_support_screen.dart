import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_constants.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../widgets/common_widgets.dart';

class HelpSupportScreen extends StatelessWidget {
  const HelpSupportScreen({super.key});

  Future<void> _open(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;

    return Scaffold(
      appBar: AppBar(title: const Text('Help & Support')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          AppCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Need help?',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: c.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Our team usually replies within 24 hours.',
                  style: TextStyle(fontSize: 14, color: c.textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          const SectionTitle(title: 'Contact'),
          _tile(
            context,
            Icons.mail_outline_rounded,
            'Email Support',
            'support@blinkstudy.in',
            () => _open('mailto:support@blinkstudy.in'),
          ),
          _tile(
            context,
            Icons.language_rounded,
            'Visit Website',
            AppConstants.websiteUrl,
            () => _open(AppConstants.websiteUrl),
          ),
          _tile(
            context,
            Icons.support_agent_rounded,
            'Support Page',
            'Get help & FAQs',
            () => _open('${AppConstants.websiteUrl}/support'),
          ),
          const SizedBox(height: 20),
          const SectionTitle(title: 'Legal'),
          _tile(
            context,
            Icons.privacy_tip_outlined,
            'Privacy Policy',
            null,
            () => _open('${AppConstants.websiteUrl}/privacy'),
          ),
          _tile(
            context,
            Icons.description_outlined,
            'Terms of Service',
            null,
            () => _open('${AppConstants.websiteUrl}/terms'),
          ),
          _tile(
            context,
            Icons.receipt_long_outlined,
            'Refund Policy',
            null,
            () => _open('${AppConstants.websiteUrl}/refund'),
          ),
          const SizedBox(height: 20),
          const SectionTitle(title: 'FAQs'),
          _faq(
            context,
            'How do I use Scan & Solve?',
            'Open Scan tab, point camera at your question, tap the purple scan button. AI solves it in seconds.',
          ),
          _faq(
            context,
            'How to upgrade my plan?',
            'Go to Plans from the menu and pay via Razorpay. Your plan activates instantly.',
          ),
          _faq(
            context,
            'OTP not received?',
            'Check network, wait 30 seconds, then retry. Contact support if the issue persists.',
          ),
        ],
      ),
    );
  }

  Widget _tile(
    BuildContext context,
    IconData icon,
    String title,
    String? subtitle,
    VoidCallback onTap,
  ) {
    final c = context.dash;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        child: ListTile(
          leading: Icon(icon, color: AppColors.primary),
          title: Text(
            title,
            style: TextStyle(fontWeight: FontWeight.w600, color: c.textPrimary),
          ),
          subtitle: subtitle != null
              ? Text(subtitle, style: TextStyle(fontSize: 12, color: c.textMuted))
              : null,
          trailing: Icon(Icons.chevron_right_rounded, color: c.textMuted),
          onTap: onTap,
          contentPadding: EdgeInsets.zero,
        ),
      ),
    );
  }

  Widget _faq(BuildContext context, String q, String a) {
    final c = context.dash;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        child: ExpansionTile(
          tilePadding: EdgeInsets.zero,
          title: Text(
            q,
            style: TextStyle(
              fontWeight: FontWeight.w600,
              fontSize: 14,
              color: c.textPrimary,
            ),
          ),
          iconColor: AppColors.primary,
          collapsedIconColor: c.textMuted,
          children: [
            Align(
              alignment: Alignment.centerLeft,
              child: Text(
                a,
                style: TextStyle(fontSize: 13, color: c.textMuted, height: 1.5),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
