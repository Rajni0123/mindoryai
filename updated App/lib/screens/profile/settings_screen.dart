import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/router/app_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/app_data_provider.dart';
import '../../providers/providers.dart';
import '../../widgets/common_widgets.dart';

class SettingsScreen extends ConsumerStatefulWidget {
  const SettingsScreen({super.key});

  @override
  ConsumerState<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends ConsumerState<SettingsScreen> {
  String _language = 'English';
  bool _notifications = true;
  bool _soundEffects = true;

  @override
  void initState() {
    super.initState();
    _language = ref.read(languageProvider);
    if (_language.toLowerCase().contains('hindi')) {
      _language = 'Hindi';
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = context.isDark;
    final prefs = ref.watch(userStudyPreferencesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Settings')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const SectionTitle(title: 'Preferences'),
          _tile(Icons.language_rounded, 'Language', _language, () => _pickLanguage()),
          _switchTile(
            Icons.notifications_rounded,
            'Push Notifications',
            _notifications,
            (v) => setState(() => _notifications = v),
          ),
          _switchTile(
            Icons.dark_mode_rounded,
            'Dark Mode',
            isDark,
            (v) => ref.read(themeModeProvider.notifier).setMode(
                  v ? ThemeMode.dark : ThemeMode.light,
                ),
          ),
          _switchTile(
            Icons.volume_up_rounded,
            'Sound Effects',
            _soundEffects,
            (v) => setState(() => _soundEffects = v),
          ),
          const SizedBox(height: 20),
          const SectionTitle(title: 'Study'),
          _tile(Icons.school_rounded, 'Target Exam', prefs.targetExam,
              () => AppRouter.go(context, AppRoutes.examGoal)),
          _tile(Icons.class_rounded, 'Class', 'Class ${prefs.studentClass}',
              () => AppRouter.go(context, AppRoutes.examGoal)),
          _tile(Icons.subject_rounded, 'Subjects', prefs.subjects,
              () => AppRouter.go(context, AppRoutes.examGoal)),
          const SizedBox(height: 20),
          const SectionTitle(title: 'Account'),
          _tile(Icons.person_rounded, 'Edit Profile', null,
              () => AppRouter.go(context, AppRoutes.editProfile)),
          _tile(Icons.security_rounded, 'Privacy', null,
              () => AppRouter.go(context, AppRoutes.helpSupport)),
          _tile(Icons.help_outline_rounded, 'Help & Support', null,
              () => AppRouter.go(context, AppRoutes.helpSupport)),
          _tile(Icons.info_outline_rounded, 'About BlinkStudy', 'v1.4.0', () {}),
        ],
      ),
    );
  }

  Widget _tile(IconData icon, String title, String? subtitle, VoidCallback onTap) {
    final c = context.dash;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        child: ListTile(
          leading: Icon(icon, color: AppColors.primary),
          title: Text(title, style: TextStyle(fontWeight: FontWeight.w600, color: c.textPrimary)),
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

  Widget _switchTile(
    IconData icon,
    String title,
    bool value,
    ValueChanged<bool> onChanged,
  ) {
    final c = context.dash;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
        child: SwitchListTile(
          secondary: Icon(icon, color: AppColors.primary),
          title: Text(title, style: TextStyle(fontWeight: FontWeight.w600, color: c.textPrimary)),
          value: value,
          onChanged: onChanged,
          contentPadding: EdgeInsets.zero,
        ),
      ),
    );
  }

  void _pickLanguage() {
    final c = context.dash;
    showModalBottomSheet(
      context: context,
      builder: (_) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Padding(
            padding: const EdgeInsets.all(20),
            child: Text(
              'Select Language',
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 18,
                color: c.textPrimary,
              ),
            ),
          ),
          ...['English', 'Hindi', 'Hinglish'].map((l) => ListTile(
                title: Text(l, style: TextStyle(color: c.textPrimary)),
                trailing: _language == l
                    ? const Icon(Icons.check, color: AppColors.primary)
                    : null,
                onTap: () {
                  setState(() => _language = l);
                  ref.read(languageProvider.notifier).setLanguage(l.toLowerCase());
                  Navigator.pop(context);
                },
              )),
          const SizedBox(height: 16),
        ],
      ),
    );
  }
}
