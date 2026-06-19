class AppConstants {
  static const String appName = 'BlinkStudy';
  static const String packageId = 'com.blinkstudy.app';

  /// Override at build time: --dart-define=API_BASE_URL=https://yourdomain.com/api
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://blinkstudy.in/api',
  );

  static const String websiteUrl = String.fromEnvironment(
    'WEBSITE_URL',
    defaultValue: 'https://blinkstudy.in',
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
