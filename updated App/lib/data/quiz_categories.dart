import 'package:flutter/material.dart';

class QuizExamCategory {
  final String id;
  final String name;
  final String subtitle;
  final IconData icon;
  final Color color;
  final List<QuizSubjectGroup> subjects;

  const QuizExamCategory({
    required this.id,
    required this.name,
    required this.subtitle,
    required this.icon,
    required this.color,
    required this.subjects,
  });
}

class QuizSubjectGroup {
  final String name;
  final IconData icon;
  final List<String> topics;

  const QuizSubjectGroup({
    required this.name,
    required this.icon,
    required this.topics,
  });
}

// ─── Shared subject templates ─────────────────────────────────────────────────

const _physicsPcm = QuizSubjectGroup(
  name: 'Physics',
  icon: Icons.bolt_rounded,
  topics: [
    'Mechanics',
    'Thermodynamics',
    'Electrostatics',
    'Current Electricity',
    'Magnetism',
    'Optics',
    'Modern Physics',
  ],
);

const _chemistryPcm = QuizSubjectGroup(
  name: 'Chemistry',
  icon: Icons.science_rounded,
  topics: [
    'Physical Chemistry',
    'Organic Chemistry',
    'Inorganic Chemistry',
    'Chemical Equilibrium',
    'Electrochemistry',
  ],
);

const _mathsPcm = QuizSubjectGroup(
  name: 'Mathematics',
  icon: Icons.calculate_rounded,
  topics: [
    'Algebra',
    'Calculus',
    'Trigonometry',
    'Coordinate Geometry',
    'Vectors & 3D',
    'Probability',
  ],
);

const _biologyPcb = QuizSubjectGroup(
  name: 'Biology',
  icon: Icons.biotech_rounded,
  topics: [
    'Human Physiology',
    'Genetics',
    'Cell Biology',
    'Plant Physiology',
    'Ecology',
    'Reproduction',
  ],
);

const _upscPolity = QuizSubjectGroup(
  name: 'Polity',
  icon: Icons.gavel_rounded,
  topics: [
    'Indian Constitution',
    'Fundamental Rights',
    'Parliament & Legislature',
    'Judiciary',
    'Local Government',
  ],
);

const _upscHistory = QuizSubjectGroup(
  name: 'History',
  icon: Icons.menu_book_rounded,
  topics: ['Modern India', 'Ancient India', 'Medieval India', 'World History'],
);

const _upscGeography = QuizSubjectGroup(
  name: 'Geography',
  icon: Icons.public_rounded,
  topics: [
    'Indian Geography',
    'Physical Geography',
    'Climate & Environment',
    'World Geography',
  ],
);

const _upscEconomy = QuizSubjectGroup(
  name: 'Economy',
  icon: Icons.trending_up_rounded,
  topics: [
    'Indian Economy',
    'Budget & Fiscal Policy',
    'Banking & Finance',
    'Agriculture & Industry',
  ],
);

const _upscCurrentAffairs = QuizSubjectGroup(
  name: 'Current Affairs',
  icon: Icons.newspaper_rounded,
  topics: [
    'National News',
    'International Affairs',
    'Government Schemes',
    'Reports & Indices',
  ],
);

const _sscQuant = QuizSubjectGroup(
  name: 'Quantitative Aptitude',
  icon: Icons.calculate_rounded,
  topics: [
    'Percentage & Ratio',
    'Profit & Loss',
    'Time & Work',
    'Speed & Distance',
    'Data Interpretation',
  ],
);

const _sscReasoning = QuizSubjectGroup(
  name: 'Reasoning',
  icon: Icons.psychology_rounded,
  topics: [
    'Logical Reasoning',
    'Puzzles',
    'Syllogism',
    'Blood Relations',
    'Coding-Decoding',
  ],
);

const _sscEnglish = QuizSubjectGroup(
  name: 'English',
  icon: Icons.translate_rounded,
  topics: [
    'Grammar',
    'Vocabulary',
    'Reading Comprehension',
    'Error Spotting',
  ],
);

const _sscGa = QuizSubjectGroup(
  name: 'General Awareness',
  icon: Icons.newspaper_rounded,
  topics: [
    'Current Affairs',
    'Indian History',
    'Geography',
    'Science & Tech',
  ],
);

const _bankingAwareness = QuizSubjectGroup(
  name: 'Banking Awareness',
  icon: Icons.account_balance_rounded,
  topics: [
    'Banking Terms',
    'RBI & Monetary Policy',
    'Financial Awareness',
    'Static GK',
  ],
);

const _railwayScience = QuizSubjectGroup(
  name: 'General Science',
  icon: Icons.science_outlined,
  topics: ['Physics Basics', 'Chemistry Basics', 'Biology Basics', 'Environment'],
);

const _defenceMaths = QuizSubjectGroup(
  name: 'Mathematics',
  icon: Icons.calculate_rounded,
  topics: ['Arithmetic', 'Algebra', 'Trigonometry', 'Geometry', 'Statistics'],
);

const _defenceGat = QuizSubjectGroup(
  name: 'General Ability',
  icon: Icons.psychology_rounded,
  topics: ['English', 'General Knowledge', 'Current Affairs', 'Science'],
);

const _teachingPedagogy = QuizSubjectGroup(
  name: 'Pedagogy',
  icon: Icons.school_rounded,
  topics: [
    'Child Development',
    'Learning Theories',
    'Teaching Methods',
    'Assessment & Evaluation',
  ],
);

const _lawLegal = QuizSubjectGroup(
  name: 'Legal Aptitude',
  icon: Icons.balance_rounded,
  topics: [
    'Constitutional Law',
    'Contract Law',
    'Torts',
    'Legal Reasoning',
  ],
);

const _catQuant = QuizSubjectGroup(
  name: 'Quantitative Ability',
  icon: Icons.calculate_rounded,
  topics: ['Arithmetic', 'Algebra', 'Geometry', 'Number System', 'Data Interpretation'],
);

const _catVerbal = QuizSubjectGroup(
  name: 'Verbal Ability',
  icon: Icons.translate_rounded,
  topics: ['Reading Comprehension', 'Para Jumbles', 'Critical Reasoning', 'Vocabulary'],
);

const _catDlr = QuizSubjectGroup(
  name: 'Data Interpretation & LR',
  icon: Icons.analytics_rounded,
  topics: ['Data Interpretation', 'Logical Reasoning', 'Puzzles', 'Arrangements'],
);

const _boardScience = QuizSubjectGroup(
  name: 'Science',
  icon: Icons.science_outlined,
  topics: ['Physics', 'Chemistry', 'Biology', 'Environment'],
);

const _boardMaths = QuizSubjectGroup(
  name: 'Mathematics',
  icon: Icons.functions_rounded,
  topics: ['Algebra', 'Geometry', 'Trigonometry', 'Statistics', 'Calculus'],
);

const _boardSocial = QuizSubjectGroup(
  name: 'Social Science',
  icon: Icons.history_edu_rounded,
  topics: ['History', 'Geography', 'Civics', 'Economics'],
);

const _stateGk = QuizSubjectGroup(
  name: 'State GK',
  icon: Icons.map_rounded,
  topics: [
    'State History',
    'State Geography',
    'Culture & Heritage',
    'State Current Affairs',
  ],
);

const _pcmSubjects = [_physicsPcm, _chemistryPcm, _mathsPcm];
const _pcbSubjects = [_physicsPcm, _chemistryPcm, _biologyPcb];
const _upscSubjects = [
  _upscPolity,
  _upscHistory,
  _upscGeography,
  _upscEconomy,
  _upscCurrentAffairs,
];
const _sscSubjects = [_sscQuant, _sscReasoning, _sscEnglish, _sscGa];
const _bankingSubjects = [_sscQuant, _sscReasoning, _sscEnglish, _bankingAwareness];
const _railwaySubjects = [_sscQuant, _sscReasoning, _sscGa, _railwayScience];
const _defenceSubjects = [_defenceMaths, _defenceGat, _sscEnglish];
const _teachingSubjects = [_teachingPedagogy, _sscGa, _sscEnglish, _boardScience];
const _lawSubjects = [_lawLegal, _sscReasoning, _sscEnglish, _sscGa];
const _catSubjects = [_catQuant, _catVerbal, _catDlr];
const _statePscSubjects = [..._upscSubjects, _stateGk];
const _boardSubjects = [_boardScience, _boardMaths, _boardSocial, _sscEnglish];
const _gateSubjects = [
  QuizSubjectGroup(
    name: 'Engineering Mathematics',
    icon: Icons.functions_rounded,
    topics: ['Linear Algebra', 'Calculus', 'Probability', 'Differential Equations'],
  ),
  QuizSubjectGroup(
    name: 'General Aptitude',
    icon: Icons.psychology_rounded,
    topics: ['Verbal Ability', 'Numerical Ability', 'Reasoning'],
  ),
  QuizSubjectGroup(
    name: 'Core Subject',
    icon: Icons.engineering_rounded,
    topics: ['Technical MCQs', 'Conceptual Questions', 'Applied Problems'],
  ),
];

/// All central + state competitive exams for Mock Test setup.
const quizExamCategories = <QuizExamCategory>[
  // ── Engineering & Medical (Central) ──
  QuizExamCategory(
    id: 'jee_main',
    name: 'JEE Main',
    subtitle: 'National engineering entrance',
    icon: Icons.rocket_launch_rounded,
    color: Color(0xFF6366F1),
    subjects: _pcmSubjects,
  ),
  QuizExamCategory(
    id: 'jee_advanced',
    name: 'JEE Advanced',
    subtitle: 'IIT admission exam',
    icon: Icons.rocket_launch_rounded,
    color: Color(0xFF4F46E5),
    subjects: _pcmSubjects,
  ),
  QuizExamCategory(
    id: 'neet',
    name: 'NEET UG',
    subtitle: 'Medical entrance exam',
    icon: Icons.medical_services_rounded,
    color: Color(0xFF10B981),
    subjects: _pcbSubjects,
  ),
  QuizExamCategory(
    id: 'neet_pg',
    name: 'NEET PG',
    subtitle: 'Postgraduate medical entrance',
    icon: Icons.local_hospital_rounded,
    color: Color(0xFF059669),
    subjects: _pcbSubjects,
  ),
  QuizExamCategory(
    id: 'gate',
    name: 'GATE',
    subtitle: 'Graduate aptitude test in engineering',
    icon: Icons.engineering_rounded,
    color: Color(0xFF6BCB77),
    subjects: _gateSubjects,
  ),
  QuizExamCategory(
    id: 'cuet_ug',
    name: 'CUET UG',
    subtitle: 'Central university entrance (UG)',
    icon: Icons.school_rounded,
    color: Color(0xFF8B5CF6),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'cuet_pg',
    name: 'CUET PG',
    subtitle: 'Central university entrance (PG)',
    icon: Icons.school_rounded,
    color: Color(0xFF7C3AED),
    subjects: _boardSubjects,
  ),

  // ── School Boards ──
  QuizExamCategory(
    id: 'cbse',
    name: 'CBSE Board',
    subtitle: 'Class 10 & 12 — NCERT based',
    icon: Icons.school_rounded,
    color: Color(0xFF8B5CF6),
    subjects: [
      QuizSubjectGroup(
        name: 'Class 10 — Science',
        icon: Icons.science_outlined,
        topics: ['Light', 'Electricity', 'Chemical Reactions', 'Life Processes'],
      ),
      QuizSubjectGroup(
        name: 'Class 10 — Maths',
        icon: Icons.functions_rounded,
        topics: ['Polynomials', 'Quadratic Equations', 'Triangles', 'Trigonometry'],
      ),
      QuizSubjectGroup(
        name: 'Class 12 — Physics',
        icon: Icons.bolt_rounded,
        topics: ['Electrostatics', 'Magnetism', 'Optics', 'Semiconductor Devices'],
      ),
      QuizSubjectGroup(
        name: 'Class 12 — Chemistry',
        icon: Icons.science_rounded,
        topics: ['Solutions', 'Electrochemistry', 'Organic Chemistry', 'Biomolecules'],
      ),
      QuizSubjectGroup(
        name: 'Class 12 — Biology',
        icon: Icons.biotech_rounded,
        topics: ['Reproduction', 'Genetics', 'Evolution', 'Human Health'],
      ),
      QuizSubjectGroup(
        name: 'Class 12 — Maths',
        icon: Icons.calculate_rounded,
        topics: ['Calculus', 'Matrices', 'Probability', 'Vectors & 3D'],
      ),
    ],
  ),
  QuizExamCategory(
    id: 'icse',
    name: 'ICSE Board',
    subtitle: 'CISCE board exam prep',
    icon: Icons.menu_book_rounded,
    color: Color(0xFFEC4899),
    subjects: _boardSubjects,
  ),

  // ── UPSC (Central) ──
  QuizExamCategory(
    id: 'upsc_cse',
    name: 'UPSC Civil Services (IAS)',
    subtitle: 'Civil services examination',
    icon: Icons.account_balance_rounded,
    color: Color(0xFFF59E0B),
    subjects: _upscSubjects,
  ),
  QuizExamCategory(
    id: 'upsc_capf',
    name: 'UPSC CAPF AC',
    subtitle: 'Assistant commandant exam',
    icon: Icons.shield_rounded,
    color: Color(0xFFD97706),
    subjects: _upscSubjects,
  ),
  QuizExamCategory(
    id: 'upsc_cds',
    name: 'UPSC CDS',
    subtitle: 'Combined defence services',
    icon: Icons.military_tech_rounded,
    color: Color(0xFFCA8A04),
    subjects: _defenceSubjects,
  ),
  QuizExamCategory(
    id: 'upsc_nda',
    name: 'UPSC NDA',
    subtitle: 'National defence academy',
    icon: Icons.military_tech_rounded,
    color: Color(0xFF854D0E),
    subjects: _defenceSubjects,
  ),
  QuizExamCategory(
    id: 'upsc_ies',
    name: 'UPSC IES/ESE',
    subtitle: 'Engineering services exam',
    icon: Icons.engineering_rounded,
    color: Color(0xFF0EA5E9),
    subjects: _gateSubjects,
  ),
  QuizExamCategory(
    id: 'upsc_iss',
    name: 'UPSC ISS/IES',
    subtitle: 'Indian economic & statistical service',
    icon: Icons.analytics_rounded,
    color: Color(0xFF0284C7),
    subjects: [_upscEconomy, _mathsPcm, _upscCurrentAffairs, _upscGeography],
  ),
  QuizExamCategory(
    id: 'upsc_cms',
    name: 'UPSC CMS',
    subtitle: 'Combined medical services',
    icon: Icons.medical_services_outlined,
    color: Color(0xFF14B8A6),
    subjects: _pcbSubjects,
  ),
  QuizExamCategory(
    id: 'upsc_ifs',
    name: 'UPSC IFS (Forest)',
    subtitle: 'Indian forest service',
    icon: Icons.park_rounded,
    color: Color(0xFF16A34A),
    subjects: [_upscGeography, _biologyPcb, _upscCurrentAffairs, _upscPolity],
  ),

  // ── SSC (Central) ──
  QuizExamCategory(
    id: 'ssc_cgl',
    name: 'SSC CGL',
    subtitle: 'Combined graduate level',
    icon: Icons.work_rounded,
    color: Color(0xFF3B82F6),
    subjects: _sscSubjects,
  ),
  QuizExamCategory(
    id: 'ssc_chsl',
    name: 'SSC CHSL',
    subtitle: '10+2 level recruitment',
    icon: Icons.work_outline_rounded,
    color: Color(0xFF2563EB),
    subjects: _sscSubjects,
  ),
  QuizExamCategory(
    id: 'ssc_mts',
    name: 'SSC MTS',
    subtitle: 'Multi-tasking staff',
    icon: Icons.badge_rounded,
    color: Color(0xFF1D4ED8),
    subjects: _sscSubjects,
  ),
  QuizExamCategory(
    id: 'ssc_gd',
    name: 'SSC GD Constable',
    subtitle: 'General duty constable',
    icon: Icons.local_police_rounded,
    color: Color(0xFF1E40AF),
    subjects: _sscSubjects,
  ),
  QuizExamCategory(
    id: 'ssc_cpo',
    name: 'SSC CPO',
    subtitle: 'Sub-inspector recruitment',
    icon: Icons.security_rounded,
    color: Color(0xFF3730A3),
    subjects: _sscSubjects,
  ),
  QuizExamCategory(
    id: 'ssc_je',
    name: 'SSC JE',
    subtitle: 'Junior engineer exam',
    icon: Icons.engineering_outlined,
    color: Color(0xFF4338CA),
    subjects: _gateSubjects,
  ),
  QuizExamCategory(
    id: 'ssc_steno',
    name: 'SSC Stenographer',
    subtitle: 'Stenographer grade C & D',
    icon: Icons.keyboard_rounded,
    color: Color(0xFF4F46E5),
    subjects: _sscSubjects,
  ),
  QuizExamCategory(
    id: 'ssc_selection',
    name: 'SSC Selection Post',
    subtitle: 'Phase-wise recruitment',
    icon: Icons.fact_check_rounded,
    color: Color(0xFF6366F1),
    subjects: _sscSubjects,
  ),

  // ── Banking & Insurance (Central) ──
  QuizExamCategory(
    id: 'ibps_po',
    name: 'IBPS PO',
    subtitle: 'Probationary officer exam',
    icon: Icons.account_balance_wallet_rounded,
    color: Color(0xFF0D9488),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'ibps_clerk',
    name: 'IBPS Clerk',
    subtitle: 'Clerical cadre exam',
    icon: Icons.receipt_long_rounded,
    color: Color(0xFF0F766E),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'ibps_so',
    name: 'IBPS SO',
    subtitle: 'Specialist officer exam',
    icon: Icons.badge_outlined,
    color: Color(0xFF115E59),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'ibps_rrb',
    name: 'IBPS RRB',
    subtitle: 'Regional rural bank exam',
    icon: Icons.agriculture_rounded,
    color: Color(0xFF134E4A),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'sbi_po',
    name: 'SBI PO',
    subtitle: 'State Bank PO exam',
    icon: Icons.account_balance_rounded,
    color: Color(0xFF0369A1),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'sbi_clerk',
    name: 'SBI Clerk',
    subtitle: 'State Bank clerk exam',
    icon: Icons.account_balance_rounded,
    color: Color(0xFF075985),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'rbi_grade_b',
    name: 'RBI Grade B',
    subtitle: 'Reserve Bank of India officer',
    icon: Icons.currency_rupee_rounded,
    color: Color(0xFF7C2D12),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'rbi_assistant',
    name: 'RBI Assistant',
    subtitle: 'Reserve Bank assistant exam',
    icon: Icons.payments_rounded,
    color: Color(0xFF92400E),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'nabard',
    name: 'NABARD Grade A/B',
    subtitle: 'Agriculture development bank',
    icon: Icons.grass_rounded,
    color: Color(0xFF166534),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'lic_aao',
    name: 'LIC AAO',
    subtitle: 'Life Insurance Corporation',
    icon: Icons.health_and_safety_rounded,
    color: Color(0xFFBE123C),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'lic_ado',
    name: 'LIC ADO',
    subtitle: 'Development officer exam',
    icon: Icons.health_and_safety_outlined,
    color: Color(0xFFE11D48),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'niacl',
    name: 'NIACL AO',
    subtitle: 'New India Assurance',
    icon: Icons.shield_outlined,
    color: Color(0xFF9333EA),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'uiic',
    name: 'UIIC AO',
    subtitle: 'United India Insurance',
    icon: Icons.shield_outlined,
    color: Color(0xFF7E22CE),
    subjects: _bankingSubjects,
  ),
  QuizExamCategory(
    id: 'gic',
    name: 'GIC Assistant Manager',
    subtitle: 'General Insurance Corporation',
    icon: Icons.shield_outlined,
    color: Color(0xFF6B21A8),
    subjects: _bankingSubjects,
  ),

  // ── Railways (Central) ──
  QuizExamCategory(
    id: 'rrb_ntpc',
    name: 'RRB NTPC',
    subtitle: 'Non-technical popular categories',
    icon: Icons.train_rounded,
    color: Color(0xFFDC2626),
    subjects: _railwaySubjects,
  ),
  QuizExamCategory(
    id: 'rrb_group_d',
    name: 'RRB Group D',
    subtitle: 'Level 1 posts recruitment',
    icon: Icons.train_outlined,
    color: Color(0xFFB91C1C),
    subjects: _railwaySubjects,
  ),
  QuizExamCategory(
    id: 'rrb_alp',
    name: 'RRB ALP',
    subtitle: 'Assistant loco pilot & technician',
    icon: Icons.directions_railway_rounded,
    color: Color(0xFF991B1B),
    subjects: _railwaySubjects,
  ),
  QuizExamCategory(
    id: 'rrb_je',
    name: 'RRB JE',
    subtitle: 'Junior engineer recruitment',
    icon: Icons.build_circle_outlined,
    color: Color(0xFF7F1D1D),
    subjects: _railwaySubjects,
  ),

  // ── Defence ──
  QuizExamCategory(
    id: 'nda',
    name: 'NDA',
    subtitle: 'National defence academy',
    icon: Icons.military_tech_rounded,
    color: Color(0xFF475569),
    subjects: _defenceSubjects,
  ),
  QuizExamCategory(
    id: 'cds',
    name: 'CDS',
    subtitle: 'Combined defence services',
    icon: Icons.military_tech_outlined,
    color: Color(0xFF334155),
    subjects: _defenceSubjects,
  ),
  QuizExamCategory(
    id: 'afcat',
    name: 'AFCAT',
    subtitle: 'Air force common admission test',
    icon: Icons.flight_rounded,
    color: Color(0xFF0EA5E9),
    subjects: _defenceSubjects,
  ),
  QuizExamCategory(
    id: 'navy',
    name: 'Indian Navy AA/SSR',
    subtitle: 'Sailor entry exam',
    icon: Icons.sailing_rounded,
    color: Color(0xFF0284C7),
    subjects: _defenceSubjects,
  ),
  QuizExamCategory(
    id: 'army_agniveer',
    name: 'Indian Army Agniveer',
    subtitle: 'Agnipath recruitment',
    icon: Icons.security_rounded,
    color: Color(0xFF166534),
    subjects: _defenceSubjects,
  ),
  QuizExamCategory(
    id: 'capf_constable',
    name: 'CAPF Constable',
    subtitle: 'CRPF, BSF, CISF, ITBP, SSB',
    icon: Icons.local_police_outlined,
    color: Color(0xFF14532D),
    subjects: _sscSubjects,
  ),

  // ── Teaching ──
  QuizExamCategory(
    id: 'ctet',
    name: 'CTET',
    subtitle: 'Central teacher eligibility test',
    icon: Icons.cast_for_education_rounded,
    color: Color(0xFFEA580C),
    subjects: _teachingSubjects,
  ),
  QuizExamCategory(
    id: 'state_tet',
    name: 'State TET',
    subtitle: 'UPTET, REET, HTET & more',
    icon: Icons.co_present_rounded,
    color: Color(0xFFC2410C),
    subjects: _teachingSubjects,
  ),

  // ── PSU & Technical (Central) ──
  QuizExamCategory(
    id: 'drdo',
    name: 'DRDO CEPTAM',
    subtitle: 'Defence research recruitment',
    icon: Icons.biotech_outlined,
    color: Color(0xFF0891B2),
    subjects: _gateSubjects,
  ),
  QuizExamCategory(
    id: 'isro',
    name: 'ISRO Recruitment',
    subtitle: 'Space research scientist exam',
    icon: Icons.rocket_outlined,
    color: Color(0xFF0E7490),
    subjects: _gateSubjects,
  ),
  QuizExamCategory(
    id: 'barc',
    name: 'BARC Recruitment',
    subtitle: 'Atomic research recruitment',
    icon: Icons.science_rounded,
    color: Color(0xFF155E75),
    subjects: _gateSubjects,
  ),
  QuizExamCategory(
    id: 'ongc',
    name: 'ONGC GT',
    subtitle: 'Oil & natural gas recruitment',
    icon: Icons.oil_barrel_outlined,
    color: Color(0xFF164E63),
    subjects: _gateSubjects,
  ),

  // ── Other Central Government ──
  QuizExamCategory(
    id: 'epfo',
    name: 'EPFO SSA',
    subtitle: 'Employees Provident Fund',
    icon: Icons.savings_rounded,
    color: Color(0xFF65A30D),
    subjects: _sscSubjects,
  ),
  QuizExamCategory(
    id: 'esic',
    name: 'ESIC Recruitment',
    subtitle: 'Employees state insurance',
    icon: Icons.medical_information_outlined,
    color: Color(0xFF4D7C0F),
    subjects: _sscSubjects,
  ),
  QuizExamCategory(
    id: 'fci',
    name: 'FCI Recruitment',
    subtitle: 'Food Corporation of India',
    icon: Icons.rice_bowl_rounded,
    color: Color(0xFF3F6212),
    subjects: _sscSubjects,
  ),
  QuizExamCategory(
    id: 'fssai',
    name: 'FSSAI Recruitment',
    subtitle: 'Food safety & standards',
    icon: Icons.restaurant_rounded,
    color: Color(0xFF365314),
    subjects: _sscSubjects,
  ),
  QuizExamCategory(
    id: 'dsssb',
    name: 'DSSSB',
    subtitle: 'Delhi subordinate services board',
    icon: Icons.location_city_rounded,
    color: Color(0xFF713F12),
    subjects: _sscSubjects,
  ),
  QuizExamCategory(
    id: 'awes',
    name: 'AWES PGT/TGT',
    subtitle: 'Army public schools teacher',
    icon: Icons.menu_book_outlined,
    color: Color(0xFF854D0E),
    subjects: _teachingSubjects,
  ),
  QuizExamCategory(
    id: 'delhi_police',
    name: 'Delhi Police',
    subtitle: 'Constable & SI recruitment',
    icon: Icons.local_police_rounded,
    color: Color(0xFF1E3A8A),
    subjects: _sscSubjects,
  ),

  // ── Law & Management ──
  QuizExamCategory(
    id: 'clat',
    name: 'CLAT',
    subtitle: 'Common law admission test',
    icon: Icons.balance_rounded,
    color: Color(0xFF7C3AED),
    subjects: _lawSubjects,
  ),
  QuizExamCategory(
    id: 'ailet',
    name: 'AILET',
    subtitle: 'NLU Delhi law entrance',
    icon: Icons.balance_outlined,
    color: Color(0xFF6D28D9),
    subjects: _lawSubjects,
  ),
  QuizExamCategory(
    id: 'cat',
    name: 'CAT',
    subtitle: 'MBA entrance — IIMs',
    icon: Icons.business_center_rounded,
    color: Color(0xFFFFD93D),
    subjects: _catSubjects,
  ),
  QuizExamCategory(
    id: 'xat',
    name: 'XAT',
    subtitle: 'XLRI management aptitude test',
    icon: Icons.business_rounded,
    color: Color(0xFFFBBF24),
    subjects: _catSubjects,
  ),

  // ── State PSC (All major states) ──
  QuizExamCategory(
    id: 'uppsc',
    name: 'UPPSC',
    subtitle: 'Uttar Pradesh PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFF97316),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'bpsc',
    name: 'BPSC',
    subtitle: 'Bihar PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFEA580C),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'mppsc',
    name: 'MPPSC',
    subtitle: 'Madhya Pradesh PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFDC2626),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'rpsc',
    name: 'RPSC',
    subtitle: 'Rajasthan PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFE11D48),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'mpsc',
    name: 'MPSC',
    subtitle: 'Maharashtra PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFDB2777),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'gpsc',
    name: 'GPSC',
    subtitle: 'Gujarat PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF9333EA),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'kpsc_karnataka',
    name: 'KPSC Karnataka',
    subtitle: 'Karnataka PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF7C3AED),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'tnpsc',
    name: 'TNPSC',
    subtitle: 'Tamil Nadu PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF6366F1),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'tspsc',
    name: 'TSPSC',
    subtitle: 'Telangana PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF4F46E5),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'appsc',
    name: 'APPSC',
    subtitle: 'Andhra Pradesh PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF4338CA),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'wbpsc',
    name: 'WBPSC',
    subtitle: 'West Bengal PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF2563EB),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'kerala_psc',
    name: 'Kerala PSC',
    subtitle: 'Kerala public service commission',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF0D9488),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'ppsc',
    name: 'PPSC Punjab',
    subtitle: 'Punjab PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF059669),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'hpsc',
    name: 'HPSC Haryana',
    subtitle: 'Haryana PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF16A34A),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'opsc',
    name: 'OPSC Odisha',
    subtitle: 'Odisha PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF65A30D),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'jpsc',
    name: 'JPSC Jharkhand',
    subtitle: 'Jharkhand PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF84CC16),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'cgpsc',
    name: 'CGPSC',
    subtitle: 'Chhattisgarh PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFCA8A04),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'ukpsc',
    name: 'UKPSC',
    subtitle: 'Uttarakhand PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFD97706),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'hppsc',
    name: 'HPPSC',
    subtitle: 'Himachal Pradesh PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFF59E0B),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'apsc',
    name: 'APSC Assam',
    subtitle: 'Assam PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFFB923C),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'goa_psc',
    name: 'Goa PSC',
    subtitle: 'Goa public service commission',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFF472B6),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'mpsc_mizoram',
    name: 'MPSC Mizoram',
    subtitle: 'Mizoram PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFEC4899),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'npsc',
    name: 'NPSC Nagaland',
    subtitle: 'Nagaland PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFDB2777),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'mpsc_manipur',
    name: 'MPSC Manipur',
    subtitle: 'Manipur PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFFBE185D),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'spsc',
    name: 'SPSC Sikkim',
    subtitle: 'Sikkim PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF9D174D),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'trpsc',
    name: 'TPSC Tripura',
    subtitle: 'Tripura PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF831843),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'meghalaya_psc',
    name: 'Meghalaya PSC',
    subtitle: 'Meghalaya PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF701A75),
    subjects: _statePscSubjects,
  ),
  QuizExamCategory(
    id: 'arunachal_psc',
    name: 'APSC Arunachal',
    subtitle: 'Arunachal Pradesh PCS',
    icon: Icons.account_balance_outlined,
    color: Color(0xFF581C87),
    subjects: _statePscSubjects,
  ),

  // ── State Boards ──
  QuizExamCategory(
    id: 'up_board',
    name: 'UP Board',
    subtitle: 'Uttar Pradesh state board',
    icon: Icons.school_outlined,
    color: Color(0xFFF97316),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'bihar_board',
    name: 'Bihar Board',
    subtitle: 'BSEB board exam',
    icon: Icons.school_outlined,
    color: Color(0xFFEA580C),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'maharashtra_board',
    name: 'Maharashtra Board',
    subtitle: 'MSBSHSE HSC/SSC',
    icon: Icons.school_outlined,
    color: Color(0xFF2563EB),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'rajasthan_board',
    name: 'Rajasthan Board',
    subtitle: 'RBSE board exam',
    icon: Icons.school_outlined,
    color: Color(0xFFDC2626),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'tamil_nadu_board',
    name: 'Tamil Nadu Board',
    subtitle: 'TN state board exam',
    icon: Icons.school_outlined,
    color: Color(0xFF7C3AED),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'telangana_board',
    name: 'Telangana Board',
    subtitle: 'TSBIE board exam',
    icon: Icons.school_outlined,
    color: Color(0xFF6366F1),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'andhra_board',
    name: 'Andhra Pradesh Board',
    subtitle: 'BIEAP board exam',
    icon: Icons.school_outlined,
    color: Color(0xFF4F46E5),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'west_bengal_board',
    name: 'West Bengal Board',
    subtitle: 'WBBSE/WBCHSE board',
    icon: Icons.school_outlined,
    color: Color(0xFF0D9488),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'karnataka_board',
    name: 'Karnataka Board',
    subtitle: 'KSEEB board exam',
    icon: Icons.school_outlined,
    color: Color(0xFF059669),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'gujarat_board',
    name: 'Gujarat Board',
    subtitle: 'GSEB board exam',
    icon: Icons.school_outlined,
    color: Color(0xFF16A34A),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'punjab_board',
    name: 'Punjab Board',
    subtitle: 'PSEB board exam',
    icon: Icons.school_outlined,
    color: Color(0xFF65A30D),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'haryana_board',
    name: 'Haryana Board',
    subtitle: 'HBSE board exam',
    icon: Icons.school_outlined,
    color: Color(0xFF84CC16),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'kerala_board',
    name: 'Kerala Board',
    subtitle: 'KBPE/KHSE board exam',
    icon: Icons.school_outlined,
    color: Color(0xFF14B8A6),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'odisha_board',
    name: 'Odisha Board',
    subtitle: 'CHSE/BSE Odisha',
    icon: Icons.school_outlined,
    color: Color(0xFF0EA5E9),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'jharkhand_board',
    name: 'Jharkhand Board',
    subtitle: 'JAC board exam',
    icon: Icons.school_outlined,
    color: Color(0xFF0284C7),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'mp_board',
    name: 'MP Board',
    subtitle: 'MPBSE board exam',
    icon: Icons.school_outlined,
    color: Color(0xFF0369A1),
    subjects: _boardSubjects,
  ),
  QuizExamCategory(
    id: 'assam_board',
    name: 'Assam Board',
    subtitle: 'SEBA/AHSEC board exam',
    icon: Icons.school_outlined,
    color: Color(0xFF075985),
    subjects: _boardSubjects,
  ),
];
