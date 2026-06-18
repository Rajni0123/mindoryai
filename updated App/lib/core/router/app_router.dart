import 'package:flutter/material.dart';
import '../../screens/ai_tutor/ai_tutor_screen.dart';
import '../../screens/auth/login_screen.dart';
import '../../screens/auth/profile_setup_screen.dart';
import '../../screens/auth/splash_screen.dart';
import '../../screens/battles/battle_lobby_screen.dart';
import '../../screens/battles/battles_screen.dart';
import '../../screens/daily_challenge/daily_challenge_screen.dart';
import '../../screens/exam_prep/exam_prep_screen.dart';
import '../../screens/exam_prep/test_completed_screen.dart';
import '../../screens/exam_prep/mock_test_screen.dart';
import '../../screens/main_shell.dart';
import '../../screens/notifications/notifications_screen.dart';
import '../../screens/performance/performance_screen.dart';
import '../../screens/performance/weakness_analysis_screen.dart';
import '../../screens/plans/plans_screen.dart';
import '../../screens/profile/profile_screen.dart';
import '../../screens/profile/settings_screen.dart';
import '../../screens/profile/edit_profile_screen.dart';
import '../../screens/profile/exam_goal_screen.dart';
import '../../screens/profile/help_support_screen.dart';
import '../../screens/quiz/quiz_screen.dart';
import '../../screens/quiz/quiz_topic_screen.dart';
import '../../screens/revision/ai_revision_plan_screen.dart';
import '../../screens/revision/flashcard_screen.dart';
import '../../screens/revision/revision_screen.dart';
import '../../screens/revision/saved_notes_screen.dart';
import '../../screens/scan_solve/scan_solve_screen.dart';
import '../../screens/leaderboard/leaderboard_screen.dart';
import '../../screens/topper/topper_connect_screen.dart';

class AppRoutes {
  static const splash = '/';
  static const login = '/login';
  static const profileSetup = '/profile-setup';
  static const main = '/main';
  static const home = '/home';
  static const aiTutor = '/ai-tutor';
  static const quizTopics = '/quiz-topics';
  static const quiz = '/quiz';
  static const scanSolve = '/scan-solve';
  static const examPrep = '/exam-prep';
  static const mockTest = '/mock-test';
  static const testCompleted = '/test-completed';
  static const performance = '/performance';
  static const weaknessAnalysis = '/weakness-analysis';
  static const battles = '/battles';
  static const battleLobby = '/battle-lobby';
  static const revision = '/revision';
  static const revisionPlan = '/revision-plan';
  static const flashcards = '/flashcards';
  static const savedNotes = '/saved-notes';
  static const profile = '/profile';
  static const settings = '/settings';
  static const editProfile = '/edit-profile';
  static const examGoal = '/exam-goal';
  static const helpSupport = '/help-support';
  static const notifications = '/notifications';
  static const dailyChallenge = '/daily-challenge';
  static const plans = '/plans';
  static const topperConnect = '/topper-connect';
  static const leaderboard = '/leaderboard';
}

class AppRouter {
  static Route<dynamic> onGenerateRoute(RouteSettings settings) {
    switch (settings.name) {
      case AppRoutes.splash:
        return _page(const SplashScreen(), settings);
      case AppRoutes.login:
        return _page(const LoginScreen(), settings);
      case AppRoutes.profileSetup:
        return _page(const ProfileSetupScreen(), settings);
      case AppRoutes.main:
        return _page(const MainShell(), settings);
      case AppRoutes.aiTutor:
        return _page(const AiTutorScreen(), settings);
      case AppRoutes.quizTopics:
        return _page(const QuizTopicScreen(), settings);
      case AppRoutes.quiz:
        final args = settings.arguments as Map<String, dynamic>?;
        return _page(QuizScreen(
          topic: args?['topic']?.toString() ?? 'Laws of Motion',
          subject: args?['subject']?.toString() ?? 'Physics',
          examType: args?['examType']?.toString() ?? 'JEE',
          language: args?['language']?.toString() ?? 'english',
        ), settings);
      case AppRoutes.scanSolve:
        return _page(const ScanSolveScreen(), settings);
      case AppRoutes.examPrep:
        return _page(const ExamPrepScreen(), settings);
      case AppRoutes.mockTest:
        return _page(const MockTestScreen(), settings);
      case AppRoutes.testCompleted:
        final args = settings.arguments as TestResultArgs? ??
            const TestResultArgs(score: 42, total: 50);
        return _page(TestCompletedScreen(args: args), settings);
      case AppRoutes.performance:
        return _page(const PerformanceScreen(), settings);
      case AppRoutes.weaknessAnalysis:
        return _page(const WeaknessAnalysisScreen(), settings);
      case AppRoutes.battles:
        return _page(const BattlesScreen(), settings);
      case AppRoutes.battleLobby:
        return _page(const BattleLobbyScreen(), settings);
      case AppRoutes.revision:
        return _page(const RevisionScreen(), settings);
      case AppRoutes.revisionPlan:
        return _page(const AiRevisionPlanScreen(), settings);
      case AppRoutes.flashcards:
        return _page(const FlashcardScreen(), settings);
      case AppRoutes.savedNotes:
        return _page(const SavedNotesScreen(), settings);
      case AppRoutes.profile:
        return _page(const ProfileScreen(), settings);
      case AppRoutes.settings:
        return _page(const SettingsScreen(), settings);
      case AppRoutes.editProfile:
        return _page(const EditProfileScreen(), settings);
      case AppRoutes.examGoal:
        return _page(const ExamGoalScreen(), settings);
      case AppRoutes.helpSupport:
        return _page(const HelpSupportScreen(), settings);
      case AppRoutes.notifications:
        return _page(const NotificationsScreen(), settings);
      case AppRoutes.dailyChallenge:
        return _page(const DailyChallengeScreen(), settings);
      case AppRoutes.plans:
        return _page(const PlansScreen(), settings);
      case AppRoutes.topperConnect:
        return _page(const TopperConnectScreen(), settings);
      case AppRoutes.leaderboard:
        return _page(const LeaderboardScreen(), settings);
      default:
        return _page(const SplashScreen(), settings);
    }
  }

  static MaterialPageRoute<T> _page<T>(Widget child, RouteSettings settings) {
    return MaterialPageRoute<T>(builder: (_) => child, settings: settings);
  }

  // Navigation helpers
  static void go(BuildContext context, String route, {Object? args}) {
    Navigator.pushNamed(context, route, arguments: args);
  }

  static void goReplace(BuildContext context, String route, {Object? args}) {
    Navigator.pushReplacementNamed(context, route, arguments: args);
  }

  static void goClear(BuildContext context, String route) {
    Navigator.pushNamedAndRemoveUntil(context, route, (_) => false);
  }

  static void back(BuildContext context) => Navigator.pop(context);
}
