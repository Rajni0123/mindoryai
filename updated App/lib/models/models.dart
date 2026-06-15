class UserModel {
  final int id;
  final String name;
  final String? email;
  final String? mobile;
  final String? avatar;
  final String? planName;
  final String? targetExam;
  final String? studentClass;
  final String? subjects;
  final bool? isProfileComplete;
  final int? streak;
  final int? level;
  final int? xp;
  final int? xpToNextLevel;

  UserModel({
    required this.id,
    required this.name,
    this.email,
    this.mobile,
    this.avatar,
    this.planName,
    this.targetExam,
    this.studentClass,
    this.subjects,
    this.isProfileComplete,
    this.streak,
    this.level,
    this.xp,
    this.xpToNextLevel,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    final usage = json['usage'] as Map<String, dynamic>?;
    return UserModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? json['full_name'] ?? 'Student',
      email: json['email'],
      mobile: json['mobile'] ?? json['phone'],
      avatar: json['avatar'] ?? json['profile_image'],
      planName: json['plan_name'] ?? json['plan']?['name'],
      targetExam: json['target_exam']?.toString(),
      studentClass: json['student_class']?.toString(),
      subjects: json['subjects']?.toString() ??
          json['favorite_subject']?.toString(),
      isProfileComplete: json['is_profile_complete'] == true ||
          json['profile_completed'] == true,
      streak: json['streak'] ??
          json['study_streak'] ??
          json['current_streak'] ??
          usage?['streak'] ??
          0,
      level: json['level'] ??
          json['current_level'] ??
          1,
      xp: json['xp'] ?? json['total_xp'] ?? 0,
      xpToNextLevel: json['xp_to_next_level'] ?? 6000,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'mobile': mobile,
        'avatar': avatar,
        'plan_name': planName,
        'target_exam': targetExam,
        'student_class': studentClass,
        'subjects': subjects,
        'is_profile_complete': isProfileComplete,
        'streak': streak,
        'level': level,
        'xp': xp,
        'xp_to_next_level': xpToNextLevel,
      };
}

class ChatMessage {
  final String id;
  final String content;
  final bool isUser;
  final DateTime? createdAt;
  final String? question;
  final String? feedback;

  ChatMessage({
    required this.id,
    required this.content,
    required this.isUser,
    this.createdAt,
    this.question,
    this.feedback,
  });

  ChatMessage copyWith({
    String? id,
    String? content,
    bool? isUser,
    DateTime? createdAt,
    String? question,
    String? feedback,
  }) {
    return ChatMessage(
      id: id ?? this.id,
      content: content ?? this.content,
      isUser: isUser ?? this.isUser,
      createdAt: createdAt ?? this.createdAt,
      question: question ?? this.question,
      feedback: feedback ?? this.feedback,
    );
  }

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    final role = json['role'] ?? json['sender'];
    return ChatMessage(
      id: json['id']?.toString() ?? '',
      content: json['content'] ?? json['message'] ?? '',
      isUser: role == 'user' || json['is_user'] == true,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }
}

class ChatSendResult {
  final String content;
  final String? messageId;
  final String question;

  const ChatSendResult({
    required this.content,
    this.messageId,
    required this.question,
  });
}

class QuizQuestion {
  final String question;
  final List<String> options;
  final int correctIndex;
  final String? explanation;

  QuizQuestion({
    required this.question,
    required this.options,
    required this.correctIndex,
    this.explanation,
  });

  factory QuizQuestion.fromJson(Map<String, dynamic> json) {
    final opts = <String>[];
    final rawOptions = json['options'];
    if (rawOptions is List) {
      opts.addAll(rawOptions.map((e) => e.toString()));
    } else if (rawOptions is Map) {
      for (final key in ['A', 'B', 'C', 'D', 'a', 'b', 'c', 'd']) {
        final v = rawOptions[key];
        if (v != null && v.toString().trim().isNotEmpty) {
          opts.add(v.toString());
        }
      }
      if (opts.isEmpty) {
        rawOptions.forEach((_, v) {
          if (v != null && v.toString().trim().isNotEmpty) {
            opts.add(v.toString());
          }
        });
      }
    }

    var correctIndex = 0;
    final answer = json['correct_index'] ??
        json['correct_answer'] ??
        json['correctAnswer'] ??
        json['answer'];

    if (opts.isNotEmpty) {
      if (answer is int) {
        if (answer >= 1 && answer <= opts.length) {
          correctIndex = answer - 1;
        } else {
          correctIndex = answer.clamp(0, opts.length - 1);
        }
      } else if (answer is String) {
        final trimmed = answer.trim();
        final asInt = int.tryParse(trimmed);
        if (asInt != null) {
          if (asInt >= 1 && asInt <= opts.length) {
            correctIndex = asInt - 1;
          } else {
            correctIndex = asInt.clamp(0, opts.length - 1);
          }
        } else {
          final letterMatch =
              RegExp(r'(?:^|[^A-Za-z])([A-Da-d])(?:[^A-Za-z]|$)')
                  .firstMatch(trimmed);
          if (letterMatch != null) {
            correctIndex =
                letterMatch.group(1)!.toUpperCase().codeUnitAt(0) -
                    'A'.codeUnitAt(0);
          } else {
            final idx = opts.indexWhere(
              (o) => o.trim().toLowerCase() == trimmed.toLowerCase(),
            );
            if (idx >= 0) correctIndex = idx;
          }
        }
        correctIndex = correctIndex.clamp(0, opts.length - 1);
      }
    }

    return QuizQuestion(
      question: json['question']?.toString() ?? '',
      options: opts,
      correctIndex: correctIndex,
      explanation: json['explanation']?.toString(),
    );
  }

  bool get isValid => question.trim().isNotEmpty && options.length >= 2;
}

class DailyTask {
  final String subject;
  final String topic;
  final int minutesDone;
  final int minutesTotal;
  final bool completed;

  DailyTask({
    required this.subject,
    required this.topic,
    required this.minutesDone,
    required this.minutesTotal,
    this.completed = false,
  });
}

class TopicProgress {
  final String name;
  final double progress;
  final bool isWeak;

  TopicProgress({
    required this.name,
    required this.progress,
    this.isWeak = false,
  });
}

class WeakTopicItem {
  final String topic;
  final String subject;
  final double successRate;
  final int attempts;
  final double accuracy;

  const WeakTopicItem({
    required this.topic,
    required this.subject,
    required this.successRate,
    this.attempts = 0,
    this.accuracy = 0,
  });

  factory WeakTopicItem.fromJson(Map<String, dynamic> json) {
    final rate = (json['success_rate'] as num?)?.toDouble() ?? 0;
    return WeakTopicItem(
      topic: json['topic']?.toString() ?? 'Topic',
      subject: json['subject']?.toString() ?? 'General',
      successRate: rate,
      attempts: (json['attempts'] as num?)?.toInt() ?? 0,
      accuracy: (json['accuracy'] as num?)?.toDouble() ?? (rate / 100),
    );
  }
}

class RevisionPlanDay {
  final int day;
  final String topic;
  final String subject;
  final String action;
  final double? successRate;
  final bool completed;

  const RevisionPlanDay({
    required this.day,
    required this.topic,
    required this.subject,
    required this.action,
    this.successRate,
    this.completed = false,
  });

  factory RevisionPlanDay.fromJson(Map<String, dynamic> json) {
    return RevisionPlanDay(
      day: (json['day'] as num?)?.toInt() ?? 1,
      topic: json['topic']?.toString() ?? '',
      subject: json['subject']?.toString() ?? 'General',
      action: json['action']?.toString() ?? 'revise',
      successRate: (json['success_rate'] as num?)?.toDouble(),
      completed: json['completed'] == true,
    );
  }
}

class RevisionPlan {
  final String planId;
  final double userAccuracy;
  final int userStreak;
  final int weakCount;
  final bool personalized;
  final List<RevisionPlanDay> days;

  const RevisionPlan({
    required this.planId,
    required this.userAccuracy,
    required this.userStreak,
    required this.weakCount,
    required this.personalized,
    required this.days,
  });

  factory RevisionPlan.fromJson(Map<String, dynamic> json) {
    final rawDays = json['days'] as List? ?? [];
    return RevisionPlan(
      planId: json['plan_id']?.toString() ?? '',
      userAccuracy: (json['user_accuracy'] as num?)?.toDouble() ?? 0,
      userStreak: (json['user_streak'] as num?)?.toInt() ?? 0,
      weakCount: (json['weak_count'] as num?)?.toInt() ?? 0,
      personalized: json['personalized'] == true,
      days: rawDays
          .map((e) => RevisionPlanDay.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList(),
    );
  }
}

class FlashcardItem {
  final String front;
  final String back;
  final String subject;
  final String type;

  const FlashcardItem({
    required this.front,
    required this.back,
    required this.subject,
    this.type = 'formula',
  });

  factory FlashcardItem.fromJson(Map<String, dynamic> json) {
    return FlashcardItem(
      front: json['front']?.toString() ?? '',
      back: json['back']?.toString() ?? '',
      subject: json['subject']?.toString() ?? 'General',
      type: json['type']?.toString() ?? 'formula',
    );
  }
}

class SavedNote {
  final String id;
  final String title;
  final String content;
  final String subject;
  final String source;
  final DateTime createdAt;

  const SavedNote({
    required this.id,
    required this.title,
    required this.content,
    required this.subject,
    required this.source,
    required this.createdAt,
  });

  factory SavedNote.fromJson(Map<String, dynamic> json) {
    return SavedNote(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? 'Untitled',
      content: json['content']?.toString() ?? '',
      subject: json['subject']?.toString() ?? 'General',
      source: json['source']?.toString() ?? 'App',
      createdAt: DateTime.tryParse(json['created_at']?.toString() ?? '') ??
          DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'content': content,
        'subject': subject,
        'source': source,
        'created_at': createdAt.toIso8601String(),
      };

  static String titleFromContent(String content) {
    final line = content
        .split('\n')
        .map((l) => l.replaceAll(RegExp(r'[#*_`>]'), '').trim())
        .firstWhere((l) => l.isNotEmpty, orElse: () => 'Saved note');
    return line.length > 60 ? '${line.substring(0, 57)}...' : line;
  }

  String get relativeDate {
    final diff = DateTime.now().difference(createdAt);
    if (diff.inDays == 0) return 'Today';
    if (diff.inDays == 1) return 'Yesterday';
    if (diff.inDays < 7) return '${diff.inDays} days ago';
    if (diff.inDays < 30) return '${diff.inDays ~/ 7} week${diff.inDays ~/ 7 == 1 ? '' : 's'} ago';
    return '${diff.inDays ~/ 30} month${diff.inDays ~/ 30 == 1 ? '' : 's'} ago';
  }
}
