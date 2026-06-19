import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../core/constants/app_constants.dart';
import '../models/models.dart';

class ApiService {
  late final Dio _dio;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  ApiService() {
    _dio = Dio(BaseOptions(
      baseUrl: AppConstants.apiBaseUrl,
      connectTimeout: const Duration(seconds: 30),
      receiveTimeout: const Duration(seconds: 120),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read(key: AppConstants.tokenKey);
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
    ));
  }

  Dio get dio => _dio;

  Future<void> saveToken(String token) async =>
      _storage.write(key: AppConstants.tokenKey, value: token);

  Future<void> clearToken() async =>
      _storage.delete(key: AppConstants.tokenKey);

  Future<String?> getToken() => _storage.read(key: AppConstants.tokenKey);

  // ─── Auth ─────────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> sendOtp(String mobile) async {
    final res = await _dio.post('/login/send-otp', data: {'mobile': mobile});
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> verifyOtp(String mobile, String otp) async {
    final res = await _dio.post('/login/verify-otp', data: {
      'mobile': mobile,
      'otp': otp,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> loginWithGoogle({
    required String idToken,
    required String platform,
  }) async {
    final res = await _dio.post('/login/google', data: {
      'id_token': idToken,
      'platform': platform,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> getUser() async {
    final res = await _dio.get('/user');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> updateProfile({
    String? name,
    String? mobile,
    String? targetExam,
    String? studentClass,
    String? subjects,
  }) async {
    final res = await _dio.put('/user/profile', data: {
      if (name != null) 'name': name,
      if (mobile != null) 'mobile': mobile,
      if (targetExam != null) 'target_exam': targetExam,
      if (studentClass != null) 'student_class': studentClass,
      if (subjects != null) ...{
        'subjects': subjects,
        'favorite_subject': subjects,
      },
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> completeStudyProfile({
    required String name,
    required String targetExam,
    required String studentClass,
    required String subjects,
    String? examDate,
  }) async {
    final res = await _dio.post('/user/complete-profile', data: {
      'name': name,
      'target_exam': targetExam,
      'student_class': studentClass,
      'subjects': subjects,
      'favorite_subject': subjects,
      if (examDate != null) 'exam_date': examDate,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> getAppConfig() async {
    final res = await _dio.get('/app-config');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<void> logout() async {
    try {
      await _dio.post('/logout');
    } catch (_) {}
    await clearToken();
  }

  // ─── Chat & AI ────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> createChat({String? title}) async {
    final res = await _dio.post('/chats', data: {'title': title ?? 'New Chat'});
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<String> sendMessage(
    String chatId, {
    required String content,
    String? language,
  }) async {
    final result = await sendChatMessage(
      chatId,
      content: content,
      language: language,
    );
    return result.content;
  }

  Future<ChatSendResult> sendChatMessage(
    String chatId, {
    required String content,
    String? language,
  }) async {
    final res = await _dio.post('/chats/$chatId/messages', data: {
      'content': content,
      if (language != null) 'language': language,
    });
    return _parseChatSendResult(res.data, fallbackQuestion: content);
  }

  Future<Map<String, dynamic>> submitAiFeedback({
    required String chatId,
    required String messageId,
    required String feedbackType,
    required String messageContent,
    String? question,
    String? feedbackReason,
    String? userComment,
  }) async {
    final res = await _dio.post('/feedback', data: {
      'chat_id': chatId,
      'message_id': messageId,
      'feedback_type': feedbackType,
      'message_content': messageContent,
      if (question != null) 'question': question,
      if (feedbackReason != null) 'feedback_reason': feedbackReason,
      if (userComment != null) 'user_comment': userComment,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  ChatSendResult _parseChatSendResult(
    dynamic data, {
    required String fallbackQuestion,
  }) {
    String content = '';
    String? messageId;
    String question = fallbackQuestion;

    if (data is List) {
      Map? userMsg;
      Map? aiMsg;
      for (final item in data) {
        if (item is! Map) continue;
        final sender = item['sender']?.toString();
        if (sender == 'user') userMsg = item;
        if (sender == 'assistant' || sender == 'ai') aiMsg = item;
      }
      if (userMsg != null) {
        question = userMsg['content']?.toString() ?? fallbackQuestion;
      }
      if (aiMsg != null) {
        content = aiMsg['content']?.toString() ?? '';
        messageId = aiMsg['id']?.toString();
      }
    }

    if (content.isEmpty) {
      content = _extractAiReply(data);
    }

    return ChatSendResult(
      content: content,
      messageId: messageId,
      question: question,
    );
  }

  Future<String> solveImageFast(
    String filePath, {
    String action = 'solve',
  }) async {
    try {
      final reply = await _postSolveImage(filePath, action: action);
      if (_isAiUnavailableText(reply)) {
        throw Exception('AI unavailable');
      }
      return reply;
    } catch (_) {
      // Fallback: blinkstudy chat with image + solve prompt
      final reply = await _postSolveImageViaChat(filePath);
      if (reply.trim().isEmpty || _isAiUnavailableText(reply)) {
        throw DioException(
          requestOptions: RequestOptions(path: '/blinkstudy/solve-image'),
          message: 'AI scan service is busy. Please try again in a moment.',
        );
      }
      return reply;
    }
  }

  Future<String> _postSolveImage(String filePath, {String action = 'solve'}) async {
    final formData = FormData.fromMap({
      'file': await MultipartFile.fromFile(
        filePath,
        filename: 'scan.jpg',
      ),
      'action': action,
    });
    final res = await _dio.post(
      '/blinkstudy/solve-image',
      data: formData,
      options: Options(
        receiveTimeout: const Duration(seconds: 90),
        sendTimeout: const Duration(seconds: 30),
      ),
    );
    final data = res.data;
    if (data is Map && data['success'] == false) {
      throw DioException(
        requestOptions: res.requestOptions,
        message: data['message']?.toString() ?? 'Scan failed',
      );
    }
    if (data is Map) {
      final reply = data['response']?.toString() ??
          data['content']?.toString() ??
          data['solution']?.toString() ??
          '';
      if (_isAiUnavailableText(reply)) {
        throw Exception('AI unavailable response');
      }
      return reply;
    }
    return '';
  }

  Future<String> _postSolveImageViaChat(String filePath) async {
    final formData = FormData.fromMap({
      'file': await MultipartFile.fromFile(
        filePath,
        filename: 'scan.jpg',
      ),
      'message':
          'Solve ALL questions in this image. Give full step-by-step solutions and final answers.',
      'mode': 'detail',
    });
    final res = await _dio.post(
      '/blinkstudy/chat',
      data: formData,
      options: Options(
        receiveTimeout: const Duration(seconds: 90),
        sendTimeout: const Duration(seconds: 30),
      ),
    );
    final data = res.data;
    if (data is Map && data['success'] == false) {
      throw DioException(
        requestOptions: res.requestOptions,
        message: data['message']?.toString() ?? 'Scan failed',
      );
    }
    if (data is Map) {
      return data['response']?.toString() ?? data['content']?.toString() ?? '';
    }
    return '';
  }

  bool _isAiUnavailableText(String text) {
    final lower = text.toLowerCase();
    return lower.contains('all ai services are currently unavailable') ||
        lower.contains('ai service unavailable') ||
        lower.contains('ai service temporarily unavailable') ||
        lower.contains('gemini api key not configured');
  }

  Future<String> sendImageMessage(String chatId, {required String filePath}) async {
    final formData = FormData.fromMap({
      'file': await MultipartFile.fromFile(filePath),
    });
    final res = await _dio.post('/chats/$chatId/messages', data: formData);
    return _extractAiReply(res.data);
  }

  Future<String> transcribeVoice(String audioPath) async {
    final formData = FormData.fromMap({
      'audio': await MultipartFile.fromFile(audioPath),
    });
    final res = await _dio.post('/voice/transcribe', data: formData);
    final data = res.data;
    if (data is Map) {
      return data['text']?.toString() ?? data['transcription']?.toString() ?? '';
    }
    return '';
  }

  Future<Map<String, dynamic>> saveNotesFromChat({
    required String content,
    String? subject,
  }) async {
    final res = await _dio.post('/blinkstudy/notes', data: {
      'content': content,
      if (subject != null) 'subject': subject,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> generateMcqsFromChat({
    required String topic,
    String subject = 'Physics',
    int count = 5,
  }) async {
    final res = await _dio.post('/blinkstudy/mcqs', data: {
      'topic': topic,
      'subject': subject,
      'count': count,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  String _extractAiReply(dynamic data) {
    if (data is List) {
      for (final item in data.reversed) {
        if (item is Map) {
          final sender = item['sender']?.toString();
          if (sender == 'assistant' || sender == 'ai') {
            return item['content']?.toString() ?? '';
          }
        }
      }
      if (data.isNotEmpty && data.last is Map) {
        return (data.last as Map)['content']?.toString() ?? '';
      }
    }
    if (data is Map) {
      return data['reply']?.toString() ??
          data['message']?['content']?.toString() ??
          data['ai_response']?.toString() ??
          data['content']?.toString() ??
          '';
    }
    return '';
  }

  // ─── Quiz ─────────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> generateQuizByTopic({
    required String topic,
    String subject = 'Physics',
    String? examType,
    int count = 10,
    String difficulty = 'medium',
    String language = 'english',
  }) async {
    final res = await _dio.get('/quiz/generate-by-topic', queryParameters: {
      'topic': topic,
      'subject': subject,
      'question_count': count,
      'difficulty': difficulty,
      'language': language,
      if (examType != null && examType.isNotEmpty) 'exam_type': examType,
    });
    final data = Map<String, dynamic>.from(res.data as Map);
    if (data['success'] == false) {
      throw DioException(
        requestOptions: res.requestOptions,
        message: data['message']?.toString() ?? 'Quiz generation failed',
      );
    }
    if (data['quiz'] is Map) {
      return Map<String, dynamic>.from(data['quiz'] as Map);
    }
    return data;
  }

  Future<Map<String, dynamic>> submitQuizAttempt(Map<String, dynamic> data) async {
    final res = await _dio.post('/quiz/attempts', data: data);
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> getQuizStats({String period = 'weekly'}) async {
    final res = await _dio.get('/quiz/stats', queryParameters: {'period': period});
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<List<dynamic>> getQuizHistory({int limit = 20}) async {
    final res = await _dio.get('/quiz/history', queryParameters: {'limit': limit});
    return res.data['history'] as List? ?? [];
  }

  // ─── Personalized Revision ────────────────────────────────────────────────

  Future<Map<String, dynamic>> getRevisionProfile() async {
    final res = await _dio.get('/revision/profile');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> getUserBadges() async {
    final res = await _dio.get('/user/badges');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<RevisionPlan> getRevisionPlan() async {
    final res = await _dio.get('/revision/plan');
    final data = Map<String, dynamic>.from(res.data as Map);
    final plan = data['plan'] as Map? ?? data;
    return RevisionPlan.fromJson(Map<String, dynamic>.from(plan));
  }

  Future<List<FlashcardItem>> getRevisionFlashcards({String? subject}) async {
    final params = <String, dynamic>{};
    if (subject != null &&
        subject.isNotEmpty &&
        subject.toLowerCase() != 'all') {
      params['subject'] = subject;
    }
    final res = await _dio.get('/revision/flashcards', queryParameters: params);
    final cards = res.data['cards'] as List? ?? [];
    return cards
        .map((e) => FlashcardItem.fromJson(Map<String, dynamic>.from(e as Map)))
        .where((c) => c.front.isNotEmpty)
        .toList();
  }

  // ─── Exams & PYQ ──────────────────────────────────────────────────────────

  Future<List<dynamic>> getExams({String? category}) async {
    final res = await _dio.get('/exams', queryParameters: {
      if (category != null) 'category': category,
    });
    return res.data['data'] ?? res.data['exams'] ?? [];
  }

  Future<Map<String, dynamic>> getExamDetail(int examId) async {
    final res = await _dio.get('/exams/$examId');
    return Map<String, dynamic>.from((res.data['data'] ?? res.data) as Map);
  }

  Future<Map<String, dynamic>> getSubjectAnalysis(int examId) async {
    final res = await _dio.get('/exams/$examId/subject-analysis');
    return Map<String, dynamic>.from((res.data['data'] ?? res.data) as Map);
  }

  Future<List<dynamic>> getPyqYears(int examId) async {
    final res = await _dio.get('/exams/$examId/pyq-years');
    return res.data['data'] as List? ?? [];
  }

  Future<Map<String, dynamic>> getPyqPaper(int examId, int year, {String? subject}) async {
    final res = await _dio.get('/exams/$examId/pyq/$year', queryParameters: {
      if (subject != null) 'subject': subject,
    });
    return Map<String, dynamic>.from((res.data['data'] ?? res.data) as Map);
  }

  Future<Map<String, dynamic>> generateMockTest({
    required int examId,
    String? subject,
    int questionCount = 30,
    int durationMinutes = 45,
  }) async {
    final res = await _dio.post('/exams/mock-test/generate', data: {
      'exam_id': examId,
      if (subject != null) 'subject': subject,
      'question_count': questionCount,
      'duration_minutes': durationMinutes,
    });
    return Map<String, dynamic>.from((res.data['data'] ?? res.data) as Map);
  }

  Future<Map<String, dynamic>> startMockTest(int mockTestId) async {
    final res = await _dio.post('/exams/mock-test/$mockTestId/start');
    return Map<String, dynamic>.from((res.data['data'] ?? res.data) as Map);
  }

  Future<Map<String, dynamic>> submitMockTest(
    int mockTestId,
    Map<String, dynamic> answers,
  ) async {
    final res = await _dio.post('/exams/mock-test/$mockTestId/submit', data: {
      'answers': answers,
    });
    return Map<String, dynamic>.from((res.data['data'] ?? res.data) as Map);
  }

  // ─── Daily Challenge ──────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getDailyChallenge() async {
    final res = await _dio.get('/daily-challenge/today');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> startDailyChallenge() async {
    final res = await _dio.post('/daily-challenge/start');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> submitDailyChallenge({
    required List<int> answers,
    required int timeTakenSeconds,
  }) async {
    final res = await _dio.post('/daily-challenge/submit', data: {
      'answers': answers,
      'time_taken_seconds': timeTakenSeconds,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<List<dynamic>> getDailyChallengeLeaderboard() async {
    final res = await _dio.get('/daily-challenge/leaderboard');
    return res.data['leaderboard'] as List? ?? res.data['data'] as List? ?? [];
  }

  // ─── Study Battles ────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> createBattle({
    required String topic,
    String? subject,
    String difficulty = 'medium',
    int maxPlayers = 4,
  }) async {
    final res = await _dio.post('/study-battle/create', data: {
      'topic': topic,
      if (subject != null) 'subject': subject,
      'difficulty': difficulty,
      'max_players': maxPlayers,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> joinBattle(String code) async {
    final res = await _dio.post('/study-battle/join/$code');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> pollBattle(int roomId) async {
    final res = await _dio.get('/study-battle/poll/$roomId');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> submitBattleAnswer({
    required int roomId,
    required int questionIndex,
    required int answerIndex,
    required int timeTakenMs,
  }) async {
    final res = await _dio.post('/study-battle/answer', data: {
      'room_id': roomId,
      'question_index': questionIndex,
      'answer_index': answerIndex,
      'time_taken_ms': timeTakenMs,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<List<dynamic>> getBattleLeaderboard({String period = 'weekly'}) async {
    final res = await _dio.get('/study-battle/leaderboard', queryParameters: {
      'period': period,
      'limit': 50,
    });
    return res.data['data'] as List? ?? [];
  }

  Future<List<dynamic>> getBattleRooms() async {
    final res = await _dio.get('/study-battle/rooms');
    return res.data['data'] as List? ?? res.data['rooms'] as List? ?? [];
  }

  Future<List<dynamic>> getBattleHistory() async {
    final res = await _dio.get('/study-battle/history');
    return res.data['data'] as List? ?? [];
  }

  // ─── Topper Connect / Discussions ─────────────────────────────────────────

  Future<List<dynamic>> getToppers() async {
    final res = await _dio.get('/topper-connect/toppers');
    return res.data['toppers'] as List? ?? res.data['data'] as List? ?? [];
  }

  Future<List<dynamic>> getPublicDoubts({String? subject, String sort = 'recent'}) async {
    final res = await _dio.get('/topper-connect/doubts', queryParameters: {
      if (subject != null) 'subject': subject,
      'sort': sort,
    });
    return res.data['doubts'] as List? ?? [];
  }

  Future<Map<String, dynamic>> upvoteDoubt(int doubtId) async {
    final res = await _dio.post('/topper-connect/doubts/$doubtId/upvote');
    return Map<String, dynamic>.from(res.data as Map);
  }

  // ─── Notifications & Usage ────────────────────────────────────────────────

  Future<List<dynamic>> getNotifications() async {
    final res = await _dio.get('/notifications');
    final data = res.data;
    if (data is Map && data['notifications'] != null) {
      final notifs = data['notifications'];
      if (notifs is Map && notifs['data'] is List) return notifs['data'] as List;
      if (notifs is List) return notifs;
    }
    return [];
  }

  Future<void> markAllNotificationsRead() async {
    await _dio.post('/notifications/mark-all-read');
  }

  Future<Map<String, dynamic>> getUsageSummary() async {
    final res = await _dio.get('/usage/summary');
    return Map<String, dynamic>.from(res.data as Map);
  }

  // ─── Plans & Payments ─────────────────────────────────────────────────────

  Future<List<dynamic>> getPlans() async {
    final res = await _dio.get('/plans');
    return res.data['plans'] as List? ?? [];
  }

  Future<Map<String, dynamic>> getSubscription() async {
    final res = await _dio.get('/subscription');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> createPaymentOrder({
    required int planId,
    String gateway = 'razorpay',
    String billingCycle = 'monthly',
  }) async {
    final res = await _dio.post('/subscription/create-order', data: {
      'plan_id': planId,
      'gateway': gateway,
      'billing_cycle': billingCycle,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> verifyPayment({
    required String orderId,
    required String paymentId,
    String? signature,
  }) async {
    final res = await _dio.post('/subscription/verify-payment', data: {
      'order_id': orderId,
      'payment_id': paymentId,
      if (signature != null) 'signature': signature,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  /// ₹1 trial + UPI autopay offer (paywall copy + eligibility)
  Future<Map<String, dynamic>> getTrialOffer() async {
    final res = await _dio.get('/trial/offer');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> getTrialStatus() async {
    final res = await _dio.get('/trial/status');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> startTrialAutopay() async {
    final res = await _dio.post('/trial/start');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> verifyTrialAutopay({
    required String subscriptionId,
    required String paymentId,
    required String signature,
  }) async {
    final res = await _dio.post('/trial/verify', data: {
      'razorpay_subscription_id': subscriptionId,
      'razorpay_payment_id': paymentId,
      'razorpay_signature': signature,
    });
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> cancelTrialAutopay() async {
    final res = await _dio.post('/trial/cancel');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<List<dynamic>> getPaymentGateways() async {
    final res = await _dio.get('/payment/gateways');
    return res.data['gateways'] as List? ?? [];
  }
}
