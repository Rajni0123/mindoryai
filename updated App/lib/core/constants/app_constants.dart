class AppConstants {
  static const String appName = 'BlinkStudy';
  static const String packageId = 'com.blinkstudy.app';

  /// Override at build time: --dart-define=API_BASE_URL=https://api.blinkstudy.in/api
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://api.blinkstudy.in/api',
  );

  static const String websiteUrl = String.fromEnvironment(
    'WEBSITE_URL',
    defaultValue: 'https://blinkstudy.in',
  );

  /// Google OAuth web client ID (serverClientId). Override via --dart-define if needed.
  static const String googleWebClientId = String.fromEnvironment(
    'GOOGLE_WEB_CLIENT_ID',
    defaultValue:
        '1049240109464-3s91jnceb09kmcuretpab57d1dr69dip.apps.googleusercontent.com',
  );

  /// Must match the redirect URI registered in Google Cloud Console (same as website).
  static const String googleRedirectUri = String.fromEnvironment(
    'GOOGLE_REDIRECT_URI',
    defaultValue: 'https://blinkstudy.in/auth/google/callback',
  );

  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';

  static const int otpLength = 4;

  /// Debug-only demo phone from build flags — never embed OTP in the client.
  static const String demoMobile = String.fromEnvironment(
    'DEMO_MOBILE',
    defaultValue: '',
  );

  static bool get hasDemoAccount => demoMobile.isNotEmpty;

  static bool isDemoMobile(String mobile) =>
      hasDemoAccount && mobile == demoMobile;

  static const double cardRadius = 20.0;
  static const double buttonRadius = 16.0;
  static const double navBarHeight = 72.0;
}
