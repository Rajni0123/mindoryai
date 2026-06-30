import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'dart:io' show Platform;
import 'package:shared_preferences/shared_preferences.dart';
import '../core/constants/app_constants.dart';
import '../core/utils/study_profile_utils.dart';
import '../models/models.dart';
import '../services/api_service.dart';
import '../services/google_oauth_service.dart';
import '../services/saved_notes_service.dart';

final apiServiceProvider = Provider<ApiService>((ref) => ApiService());

final savedNotesServiceProvider =
    Provider<SavedNotesService>((ref) => SavedNotesService());

final savedNotesProvider =
    StateNotifierProvider<SavedNotesNotifier, List<SavedNote>>((ref) {
  return SavedNotesNotifier(ref.read(savedNotesServiceProvider));
});

class SavedNotesNotifier extends StateNotifier<List<SavedNote>> {
  final SavedNotesService _service;

  SavedNotesNotifier(this._service) : super(const []) {
    _load();
  }

  Future<void> _load() async {
    state = await _service.loadAll();
  }

  Future<bool> save({
    required String content,
    String subject = 'General',
    String source = 'App',
    String? title,
  }) async {
    try {
      final note = await _service.save(
        content: content,
        subject: subject,
        source: source,
        title: title,
      );
      state = [note, ...state];
      return true;
    } catch (_) {
      return false;
    }
  }

  Future<void> delete(String id) async {
    await _service.delete(id);
    state = state.where((n) => n.id != id).toList();
  }

  Future<void> refresh() => _load();
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(ref.read(apiServiceProvider));
});

class AuthState {
  final bool isLoading;
  final bool isAuthenticated;
  final bool isCheckingAuth;
  final bool needsStudySetup;
  final UserModel? user;
  final String? error;

  const AuthState({
    this.isLoading = false,
    this.isAuthenticated = false,
    this.isCheckingAuth = true,
    this.needsStudySetup = false,
    this.user,
    this.error,
  });

  AuthState copyWith({
    bool? isLoading,
    bool? isAuthenticated,
    bool? isCheckingAuth,
    bool? needsStudySetup,
    UserModel? user,
    String? error,
  }) {
    return AuthState(
      isLoading: isLoading ?? this.isLoading,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      isCheckingAuth: isCheckingAuth ?? this.isCheckingAuth,
      needsStudySetup: needsStudySetup ?? this.needsStudySetup,
      user: user ?? this.user,
      error: error,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final ApiService _api;

  AuthNotifier(this._api) : super(const AuthState()) {
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    final token = await _api.getToken();
    if (token == null) {
      state = state.copyWith(isCheckingAuth: false);
      return;
    }
    try {
      final data = await _api.getUser();
      final userData = data['user'] ?? data;
      final user = UserModel.fromJson(userData as Map<String, dynamic>);
      await _syncStudyPrefs(user);
      final needsSetup = await _resolveNeedsStudySetup(user);
      state = AuthState(
        isAuthenticated: true,
        isCheckingAuth: false,
        needsStudySetup: needsSetup,
        user: user,
      );
    } catch (_) {
      await _api.clearToken();
      state = state.copyWith(isCheckingAuth: false);
    }
  }

  Future<bool> _resolveNeedsStudySetup(UserModel user) async {
    if (user.targetExam != null &&
        user.targetExam!.trim().isNotEmpty &&
        user.studentClass != null &&
        user.studentClass!.trim().isNotEmpty) {
      return false;
    }
    try {
      final prefs = await SharedPreferences.getInstance();
      final exam = prefs.getString('study_target_exam');
      final cls = prefs.getString('study_class');
      if (exam != null &&
          exam.isNotEmpty &&
          cls != null &&
          cls.isNotEmpty) {
        return false;
      }
    } catch (_) {}
    return StudyProfileUtils.needsStudySetup(
      targetExam: user.targetExam,
      studentClass: user.studentClass,
      isProfileComplete: user.isProfileComplete,
    );
  }

  Future<void> _syncStudyPrefs(UserModel user) async {
    if (user.targetExam == null || user.targetExam!.isEmpty) return;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('study_target_exam', user.targetExam!);
      if (user.studentClass != null) {
        await prefs.setString('study_class', user.studentClass!);
      }
      if (user.subjects != null) {
        await prefs.setString('study_subjects', user.subjects!);
      }
    } catch (_) {}
  }

  Future<bool> sendOtp(String mobile) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final data = await _api.sendOtp(mobile);
      if (data['success'] == false) {
        state = state.copyWith(
          isLoading: false,
          error: data['message']?.toString() ?? 'Failed to send OTP',
        );
        return false;
      }
      state = state.copyWith(isLoading: false);
      return true;
    } on DioException catch (e) {
      state = state.copyWith(isLoading: false, error: _extractMessage(e));
      return false;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  Future<bool> verifyOtp(String mobile, String otp) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final data = await _api.verifyOtp(mobile, otp);
      if (data['success'] == false) {
        state = state.copyWith(
          isLoading: false,
          error: data['message']?.toString() ?? 'Invalid OTP',
        );
        return false;
      }
      return _completeLogin(data);
    } on DioException catch (e) {
      state = state.copyWith(isLoading: false, error: _extractMessage(e));
      return false;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  Future<bool> signInWithGoogle({
    required String webClientId,
    required String redirectUri,
  }) async {
    final resolvedClientId = webClientId.trim().isNotEmpty
        ? webClientId.trim()
        : AppConstants.googleWebClientId;
    final resolvedRedirect = redirectUri.trim().isNotEmpty
        ? redirectUri.trim()
        : AppConstants.googleRedirectUri;

    if (resolvedClientId.isEmpty || resolvedRedirect.isEmpty) {
      state = state.copyWith(error: 'Google login is not configured yet.');
      return false;
    }

    state = state.copyWith(isLoading: true, error: null);
    try {
      final code = await GoogleOAuthService.signIn(
        clientId: resolvedClientId,
        redirectUri: resolvedRedirect,
      );

      final platform = Platform.isIOS ? 'ios' : 'android';
      final data = await _api.loginWithGoogleCode(
        code: code,
        platform: platform,
        redirectUri: resolvedRedirect,
      );

      if (data['success'] == false) {
        state = state.copyWith(
          isLoading: false,
          error: data['message']?.toString() ?? 'Google sign-in failed',
        );
        return false;
      }

      return _completeLogin(data);
    } on GoogleOAuthException catch (e) {
      state = state.copyWith(isLoading: false, error: e.message);
      return false;
    } on DioException catch (e) {
      state = state.copyWith(isLoading: false, error: _extractMessage(e));
      return false;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  Future<bool> _completeLogin(Map<String, dynamic> data) async {
    final payload = (data['data'] as Map<String, dynamic>?) ?? data;
    final token = payload['token'] ?? data['token'] ?? data['access_token'];
    if (token != null) await _api.saveToken(token.toString());

    final userRaw = payload['user'] ?? data['user'];
    if (userRaw is! Map<String, dynamic>) {
      state = state.copyWith(
        isLoading: false,
        error: 'Login response was invalid.',
      );
      return false;
    }

    final user = UserModel.fromJson(userRaw);
    await _syncStudyPrefs(user);
    final needsSetup = payload['needs_profile_completion'] == true ||
        data['needs_profile_completion'] == true ||
        await _resolveNeedsStudySetup(user);
    state = AuthState(
      isAuthenticated: true,
      isCheckingAuth: false,
      needsStudySetup: needsSetup,
      user: user,
    );
    return true;
  }

  String _extractMessage(DioException e) {
    final data = e.response?.data;
    if (data is Map && data['message'] != null) {
      return data['message'].toString();
    }
    final code = e.response?.statusCode;
    if (code == 500) {
      return 'Server error. Please try again in a moment.';
    }
    if (code == 429) {
      return 'Too many attempts. Please wait and try again.';
    }
    if (code == 422) {
      return 'Invalid mobile number. Check and try again.';
    }
    return 'Network error. Check your connection.';
  }

  Future<void> logout() async {
    await _api.logout();
    state = const AuthState(isCheckingAuth: false);
  }

  Future<void> refreshUser() async {
    try {
      final data = await _api.getUser();
      final userData = data['user'] ?? data;
      final user = UserModel.fromJson(userData as Map<String, dynamic>);
      await _syncStudyPrefs(user);
      state = state.copyWith(
        user: user,
        needsStudySetup: await _resolveNeedsStudySetup(user),
      );
    } catch (_) {}
  }

  void markStudySetupComplete() {
    state = state.copyWith(needsStudySetup: false);
  }
}

final navIndexProvider = StateProvider<int>((ref) => 0);

/// Increment to trigger instant capture from bottom nav scan button.
final scanCaptureTriggerProvider = StateProvider<int>((ref) => 0);

final themeModeProvider =
    StateNotifierProvider<ThemeModeNotifier, ThemeMode>((ref) => ThemeModeNotifier());

class ThemeModeNotifier extends StateNotifier<ThemeMode> {
  ThemeModeNotifier() : super(ThemeMode.light) {
    _load();
  }

  static const _key = 'theme_mode';

  Future<void> _load() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final saved = prefs.getString(_key);
      if (saved == 'dark') state = ThemeMode.dark;
      if (saved == 'light') state = ThemeMode.light;
    } catch (_) {}
  }

  Future<void> setMode(ThemeMode mode) async {
    state = mode;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_key, mode == ThemeMode.dark ? 'dark' : 'light');
    } catch (_) {}
  }

  Future<void> toggle() async {
    final next = state == ThemeMode.dark ? ThemeMode.light : ThemeMode.dark;
    await setMode(next);
  }
}
