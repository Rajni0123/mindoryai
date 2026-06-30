import 'package:flutter_web_auth_2/flutter_web_auth_2.dart';

/// Opens Google OAuth in the system browser (same web client + redirect as the website).
class GoogleOAuthService {
  static Future<String> signIn({
    required String clientId,
    required String redirectUri,
  }) async {
    final redirect = Uri.parse(redirectUri);
    final authUrl = Uri.https('accounts.google.com', '/o/oauth2/v2/auth', {
      'client_id': clientId,
      'redirect_uri': redirectUri,
      'response_type': 'code',
      'scope': 'openid email profile',
      'access_type': 'offline',
      'prompt': 'select_account',
    });

    final result = await FlutterWebAuth2.authenticate(
      url: authUrl.toString(),
      callbackUrlScheme: redirect.scheme,
      options: FlutterWebAuth2Options(
        httpsHost: redirect.host,
        httpsPath: redirect.path,
      ),
    );

    final callback = Uri.parse(result);
    final error = callback.queryParameters['error'];
    if (error != null && error.isNotEmpty) {
      throw GoogleOAuthException(
        callback.queryParameters['error_description'] ??
            callback.queryParameters['error'] ??
            'Google sign-in was cancelled.',
      );
    }

    final code = callback.queryParameters['code'];
    if (code == null || code.isEmpty) {
      throw GoogleOAuthException('No authorization code received from Google.');
    }

    return code;
  }
}

class GoogleOAuthException implements Exception {
  GoogleOAuthException(this.message);
  final String message;

  @override
  String toString() => message;
}
