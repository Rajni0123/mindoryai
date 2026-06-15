class StudyProfileEntry {
  const StudyProfileEntry(this.name, this.keywords);

  final String name;
  final List<String> keywords;
}

class StudyProfileUtils {
  static const defaultExams = [
    'JEE Main',
    'NEET',
    'CBSE Board',
    'UPSC Civil Services (IAS)',
    'SSC CGL',
    'RRB NTPC',
    'IBPS PO',
    'GATE',
  ];

  /// Shown when search box is empty.
  static const popularExams = [
    'JEE Main',
    'JEE Advanced',
    'NEET',
    'CBSE Board',
    'ICSE Board',
    'UPSC Civil Services (IAS)',
    'SSC CGL',
    'SSC CHSL',
    'RRB NTPC',
    'IBPS PO',
    'GATE',
    'NDA',
    'CUET UG',
    'CTET',
    'CLAT',
  ];

  static const examCatalog = [
    // Engineering & Medical
    StudyProfileEntry('JEE Main', [
      'jee',
      'main',
      'jeemain',
      'joint entrance',
      'iit',
      'engineering',
    ]),
    StudyProfileEntry('JEE Advanced', [
      'jee',
      'advanced',
      'jeeadvanced',
      'iit jee',
      'iit',
    ]),
    StudyProfileEntry('NEET', ['neet', 'neit', 'medical', 'mbbs', 'doctor']),
    StudyProfileEntry('NEET PG', ['neet pg', 'neetpg', 'medical pg']),
    StudyProfileEntry('GATE', ['gate', 'engineering', 'postgraduate']),
    StudyProfileEntry('CUET UG', ['cuet', 'cuet ug', 'central university']),
    StudyProfileEntry('CUET PG', ['cuet pg', 'cuetpg', 'central university pg']),

    // School Boards
    StudyProfileEntry('CBSE Board', [
      'cbse',
      'board',
      'ncert',
      'class 10',
      'class 12',
      'cbsc',
    ]),
    StudyProfileEntry('ICSE Board', ['icse', 'board', 'cisce', 'icsc']),
    StudyProfileEntry('State Board', [
      'state',
      'board',
      'bihar board',
      'up board',
      'maharashtra board',
    ]),

    // UPSC
    StudyProfileEntry('UPSC Civil Services (IAS)', [
      'upsc',
      'ias',
      'ips',
      'ifs',
      'civil services',
      'cse',
    ]),
    StudyProfileEntry('UPSC CAPF AC', ['upsc capf', 'capf', 'assistant commandant']),
    StudyProfileEntry('UPSC CDS', ['upsc cds', 'cds', 'combined defence']),
    StudyProfileEntry('UPSC NDA', ['upsc nda', 'nda', 'national defence academy']),
    StudyProfileEntry('UPSC IES/ESE', [
      'upsc ies',
      'upsc ese',
      'engineering services',
      'ese',
    ]),
    StudyProfileEntry('UPSC ISS/IES', [
      'iss',
      'ies economics',
      'economic service',
      'statistical service',
    ]),
    StudyProfileEntry('UPSC CMS', ['cms', 'combined medical services', 'upsc medical']),
    StudyProfileEntry('UPSC IFS (Forest)', ['ifs forest', 'forest service', 'indian forest']),

    // SSC
    StudyProfileEntry('SSC CGL', ['ssc cgl', 'cgl', 'graduate level', 'ssc']),
    StudyProfileEntry('SSC CHSL', ['ssc chsl', 'chsl', '10+2', 'ldc', 'udc']),
    StudyProfileEntry('SSC MTS', ['ssc mts', 'mts', 'multi tasking']),
    StudyProfileEntry('SSC GD Constable', ['ssc gd', 'gd constable', 'constable']),
    StudyProfileEntry('SSC CPO', ['ssc cpo', 'cpo', 'sub inspector', 'delhi police si']),
    StudyProfileEntry('SSC JE', ['ssc je', 'junior engineer', 'ssc engineer']),
    StudyProfileEntry('SSC Stenographer', ['stenographer', 'ssc steno', 'steno']),
    StudyProfileEntry('SSC Selection Post', ['selection post', 'ssc phase']),

    // Banking & Insurance
    StudyProfileEntry('IBPS PO', ['ibps po', 'probationary officer', 'bank po', 'ibps']),
    StudyProfileEntry('IBPS Clerk', ['ibps clerk', 'bank clerk', 'clerk']),
    StudyProfileEntry('IBPS SO', ['ibps so', 'specialist officer', 'so']),
    StudyProfileEntry('IBPS RRB', ['ibps rrb', 'rrb po', 'rrb clerk', 'regional rural bank']),
    StudyProfileEntry('SBI PO', ['sbi po', 'state bank po']),
    StudyProfileEntry('SBI Clerk', ['sbi clerk', 'state bank clerk']),
    StudyProfileEntry('RBI Grade B', ['rbi grade b', 'rbi', 'reserve bank']),
    StudyProfileEntry('RBI Assistant', ['rbi assistant', 'rbi clerk']),
    StudyProfileEntry('NABARD Grade A/B', ['nabard', 'agriculture bank']),
    StudyProfileEntry('LIC AAO', ['lic aao', 'lic', 'life insurance']),
    StudyProfileEntry('LIC ADO', ['lic ado', 'development officer']),
    StudyProfileEntry('NIACL AO', ['niacl', 'new india assurance']),
    StudyProfileEntry('UIIC AO', ['uiic', 'united india insurance']),
    StudyProfileEntry('GIC Assistant Manager', ['gic', 'general insurance']),

    // Railways
    StudyProfileEntry('RRB NTPC', ['rrb ntpc', 'ntpc', 'railway ntpc', 'railway']),
    StudyProfileEntry('RRB Group D', ['rrb group d', 'group d', 'railway group d']),
    StudyProfileEntry('RRB ALP', ['rrb alp', 'alp', 'assistant loco pilot', 'technician']),
    StudyProfileEntry('RRB JE', ['rrb je', 'railway je', 'railway engineer']),

    // Defence (non-UPSC)
    StudyProfileEntry('NDA', ['nda', 'national defence academy', 'defence']),
    StudyProfileEntry('CDS', ['cds', 'combined defence services']),
    StudyProfileEntry('AFCAT', ['afcat', 'air force', 'flying branch']),
    StudyProfileEntry('Indian Navy AA/SSR', ['navy', 'navy aa', 'navy ssr', 'sailor']),
    StudyProfileEntry('Indian Army Agniveer', ['army', 'agniveer', 'agnipath']),

    // Teaching
    StudyProfileEntry('CTET', ['ctet', 'central teacher eligibility', 'teacher']),
    StudyProfileEntry('State TET', ['tet', 'uptet', 'reet', 'teacher eligibility']),

    // PSU & Technical
    StudyProfileEntry('DRDO CEPTAM', ['drdo', 'ceptam', 'defence research']),
    StudyProfileEntry('ISRO Recruitment', ['isro', 'space', 'scientist']),
    StudyProfileEntry('BARC Recruitment', ['barc', 'atomic research']),
    StudyProfileEntry('ONGC GT', ['ongc', 'oil and natural gas']),

    // Other Government
    StudyProfileEntry('EPFO SSA', ['epfo', 'social security', 'provident fund']),
    StudyProfileEntry('ESIC Recruitment', ['esic', 'employees state insurance']),
    StudyProfileEntry('FCI Recruitment', ['fci', 'food corporation']),
    StudyProfileEntry('FSSAI Recruitment', ['fssai', 'food safety']),
    StudyProfileEntry('DSSSB', ['dsssb', 'delhi subordinate', 'delhi govt']),
    StudyProfileEntry('AWES PGT/TGT', ['awes', 'army school', 'pgt', 'tgt']),
    StudyProfileEntry('State PSC', [
      'psc',
      'uppsc',
      'mppsc',
      'bpsc',
      'state civil services',
    ]),
    StudyProfileEntry('Delhi Police', ['delhi police', 'police constable', 'police']),
    StudyProfileEntry('CAPF Constable', ['capf constable', 'crpf', 'bsf', 'cisf', 'itbp']),

    // Law & Management
    StudyProfileEntry('CLAT', ['clat', 'law', 'nlu']),
    StudyProfileEntry('AILET', ['ailet', 'nlu delhi', 'law']),
    StudyProfileEntry('CAT', ['cat', 'mba', 'iim']),
    StudyProfileEntry('XAT', ['xat', 'xlri', 'mba']),
    StudyProfileEntry('Other', ['other', 'general', 'competitive']),
  ];

  static const setupClasses = ['9', '10', '11', '12'];
  static const classes = setupClasses;

  static List<String> get allExamNames =>
      examCatalog.map((e) => e.name).toList();

  static bool isKnownExam(String exam) =>
      allExamNames.any((e) => e.toLowerCase() == exam.toLowerCase());

  static String normalizeExam(String exam) {
    final match = examCatalog
        .where((e) => e.name.toLowerCase() == exam.toLowerCase())
        .map((e) => e.name)
        .firstOrNull;
    return match ?? exam;
  }

  static List<String> searchExams(String query) {
    final q = query.trim().toLowerCase().replaceAll(RegExp(r'\s+'), '');
    if (q.isEmpty) return popularExams;

    final scored = <({String name, int score})>[];
    for (final entry in examCatalog) {
      final score = _matchScore(q, entry);
      if (score > 0) scored.add((name: entry.name, score: score));
    }

    scored.sort((a, b) => b.score.compareTo(a.score));
    return scored.map((e) => e.name).toList();
  }

  static int _matchScore(String query, StudyProfileEntry entry) {
    final name = entry.name.toLowerCase().replaceAll(RegExp(r'[^a-z0-9]'), '');
    if (name.startsWith(query)) return 100;
    if (name.contains(query)) return 80;

    for (final keyword in entry.keywords) {
      final k = keyword.replaceAll(RegExp(r'[^a-z0-9]'), '');
      if (k.startsWith(query)) return 70;
      if (k.contains(query)) return 60;
      if (query.length >= 3 && _isCloseSpelling(query, k)) return 50;
    }

    if (query.length >= 3 && _isCloseSpelling(query, name)) return 40;
    return 0;
  }

  static bool _isCloseSpelling(String a, String b) {
    if (a.isEmpty || b.isEmpty) return false;
    final dist = _levenshtein(a, b);
    final maxLen = a.length > b.length ? a.length : b.length;
    return dist <= (maxLen <= 4 ? 1 : 2);
  }

  static int _levenshtein(String a, String b) {
    if (a == b) return 0;
    if (a.isEmpty) return b.length;
    if (b.isEmpty) return a.length;

    final rows = a.length + 1;
    final cols = b.length + 1;
    final matrix = List.generate(rows, (_) => List<int>.filled(cols, 0));

    for (var i = 0; i < rows; i++) {
      matrix[i][0] = i;
    }
    for (var j = 0; j < cols; j++) {
      matrix[0][j] = j;
    }

    for (var i = 1; i < rows; i++) {
      for (var j = 1; j < cols; j++) {
        final cost = a[i - 1] == b[j - 1] ? 0 : 1;
        matrix[i][j] = [
          matrix[i - 1][j] + 1,
          matrix[i][j - 1] + 1,
          matrix[i - 1][j - 1] + cost,
        ].reduce((x, y) => x < y ? x : y);
      }
    }
    return matrix[a.length][b.length];
  }

  static bool requiresBoardSetup(String exam) {
    final e = exam.toLowerCase();
    return e.contains('cbse') || e.contains('icse');
  }

  static bool requiresCbseSetup(String exam) => requiresBoardSetup(exam);

  static bool requiresSubjects(String exam) => requiresBoardSetup(exam);

  static String defaultClassForExam(String exam) => '12';

  static String defaultSubjectsForExam(String exam) {
    final e = exam.toLowerCase();
    if (e.contains('neet')) return 'PCB';
    if (e.contains('jee')) return 'PCM';
    if (requiresBoardSetup(exam)) return 'PCM';
    return 'General';
  }

  static List<String> subjectsForExam(String exam) {
    final e = exam.toLowerCase();
    if (e.contains('cbse') || e.contains('icse')) {
      return ['PCM', 'PCB', 'PCMB', 'Commerce', 'Arts'];
    }
    if (e.contains('neet')) return ['PCB', 'PCMB'];
    if (e.contains('jee')) return ['PCM', 'PCMB'];
    return ['PCM', 'PCB', 'PCMB', 'Commerce', 'Arts'];
  }

  static String aspirantLabel(String? targetExam) {
    final exam = (targetExam ?? '').trim();
    if (exam.isEmpty) return 'Student';
    if (exam.toLowerCase().contains('neet')) return 'NEET Aspirant';
    if (exam.toLowerCase().contains('jee advanced')) {
      return 'JEE Advanced Aspirant';
    }
    if (exam.toLowerCase().contains('jee')) return 'JEE Aspirant';
    if (exam.toLowerCase().contains('cbse')) return 'CBSE Aspirant';
    if (exam.toLowerCase().contains('icse')) return 'ICSE Aspirant';
    if (exam.toLowerCase().contains('upsc')) return 'UPSC Aspirant';
    if (exam.toLowerCase().contains('ssc')) return 'SSC Aspirant';
    if (exam.toLowerCase().contains('rrb')) return 'Railway Aspirant';
    if (exam.toLowerCase().contains('ibps') || exam.toLowerCase().contains('sbi')) {
      return 'Banking Aspirant';
    }
    if (exam.toLowerCase().contains('cat')) return 'CAT Aspirant';
    return '$exam Aspirant';
  }

  static String profileSubtitle({
    required String? studentClass,
    required String? subjects,
    required String? targetExam,
  }) {
    final parts = <String>[];
    if (studentClass != null && studentClass.isNotEmpty) {
      parts.add('Class $studentClass');
    }
    if (subjects != null && subjects.isNotEmpty) {
      parts.add(subjects);
    }
    if (parts.isEmpty && targetExam != null && targetExam.isNotEmpty) {
      return 'Preparing for $targetExam';
    }
    if (parts.isEmpty) return 'Complete your study profile';
    final exam = targetExam?.trim();
    if (exam != null && exam.isNotEmpty) {
      return '${parts.join(' • ')} • $exam';
    }
    return parts.join(' • ');
  }

  static bool needsStudySetup({
    String? targetExam,
    String? studentClass,
    bool? isProfileComplete,
  }) {
    if (targetExam == null || targetExam.trim().isEmpty) return true;
    if (studentClass == null || studentClass.trim().isEmpty) return true;
    if (isProfileComplete == false) return true;
    return false;
  }

  static String examTypeForApi(String exam) {
    final e = exam.toLowerCase();
    if (e.contains('neet')) return 'NEET';
    if (e.contains('jee')) return 'JEE';
    if (e.contains('cbse')) return 'CBSE';
    if (e.contains('icse')) return 'ICSE';
    if (e.contains('upsc')) return 'UPSC';
    return 'Other';
  }

  /// OTP/mobile logins often get a placeholder like 9999999999_123@mobile.in
  static bool isAutoGeneratedEmail(String? email) {
    if (email == null || email.trim().isEmpty) return true;
    final e = email.trim().toLowerCase();
    if (e.contains('@mobile.')) return true;
    if (RegExp(r'^\d{10}_\d+@').hasMatch(e)) return true;
    if (RegExp(r'^\d{10}@mobile').hasMatch(e)) return true;
    return false;
  }

  static String? displayEmail(String? email) {
    if (isAutoGeneratedEmail(email)) return null;
    return email?.trim();
  }
}

extension _FirstOrNull<E> on Iterable<E> {
  E? get firstOrNull {
    final iterator = this.iterator;
    if (!iterator.moveNext()) return null;
    return iterator.current;
  }
}
