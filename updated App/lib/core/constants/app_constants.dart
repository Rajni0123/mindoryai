class AppConstants {
  static const String appName = 'BlinkStudy';
  static const String packageId = 'com.blinkstudy.app';
  static const String apiBaseUrl = 'https://blinkstudy.in/api';
  static const String websiteUrl = 'https://blinkstudy.in';

  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';

  static const int otpLength = 4;

  /// Backend test account (permanent on blinkstudy.in — see .env TEST_PHONE).
  static const String demoMobile = '9999999999';
  static const String demoOtp = '1234';

  static bool isDemoMobile(String mobile) => mobile == demoMobile;

  static const double cardRadius = 20.0;
  static const double buttonRadius = 16.0;
  static const double navBarHeight = 72.0;
}
