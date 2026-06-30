import 'package:flutter_web_auth_2/flutter_web_auth_2.dart';
import '../core/constants/app_constants.dart';

/// Opens Google OAuth in the system browser (same web client + redirect as the website).
class GoogleOAuthService {
  static const _mobileState = 'blinkstudy_mobile_app';
  static const _appCallbackScheme = 'com.blinkstudy.app';

  static Future<String> signIn({
    required String clientId,
    required String redirectUri,
  }) async {
    final normalizedRedirect = _normalizeRedirectUri(redirectUri);
    final authUrl = Uri.https('accounts.google.com', '/o/oauth2/v2/auth', {
      'client_id': clientId,
      'redirect_uri': normalizedRedirect,
      'response_type': 'code',
      'scope': 'openid email profile',
      'access_type': 'offline',
      'prompt': 'select_account',
      'state': _mobileState,
    });

    final result = await FlutterWebAuth2.authenticate(
      url: authUrl.toString(),
      callbackUrlScheme: _appCallbackScheme,
      options: const FlutterWebAuth2Options(
        intentFlags: ephemeralIntentFlags,
      ),
    );

    final callback = Uri.parse(result);
    final error = callback.queryParameters['error'];
    if (error != null && error.isNotEmpty) {
      throw GoogleOAuthException(
        callback.queryParameters['error_description'] ??
            error,
      );
    }

    final code = callback.queryParameters['code'];
    if (code == null || code.isEmpty) {
      throw GoogleOAuthException('No authorization code received from Google.');
    }

    return code;
  }

  /// Website callback lives on blinkstudy.in, not api subdomain.
  static String resolveRedirectUri([String? fromApi]) {
    final raw = (fromApi ?? '').trim();
    if (raw.isEmpty) {
      return '${AppConstants.websiteUrl}/auth/google/callback';
    }
    return _normalizeRedirectUri(raw);
  }

  static String _normalizeRedirectUri(String uri) {
    final parsed = Uri.parse(uri);
    final host = parsed.host.toLowerCase();
    if (host == 'api.blinkstudy.in' || host.startsWith('api.')) {
      return '${AppConstants.websiteUrl}/auth/google/callback';
    }
    return uri;
  }
}

class GoogleOAuthException implements Exception {
  GoogleOAuthException(this.message);
  final String message;

  @override
  String toString() => message;
}
