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

/// PRD competitive exam categories only (no Class 1–9).
const quizExamCategories = <QuizExamCategory>[
  QuizExamCategory(
    id: 'jee',
    name: 'JEE',
    subtitle: 'JEE Main & Advanced',
    icon: Icons.rocket_launch_rounded,
    color: Color(0xFF6366F1),
    subjects: [
      QuizSubjectGroup(
        name: 'Physics',
        icon: Icons.bolt_rounded,
        topics: [
          'Laws of Motion',
          'Rotational Motion',
          'Gravitation',
          'Thermodynamics',
          'Electrostatics',
          'Current Electricity',
          'Magnetism',
          'Optics',
          'Modern Physics',
        ],
      ),
      QuizSubjectGroup(
        name: 'Chemistry',
        icon: Icons.science_rounded,
        topics: [
          'Physical Chemistry',
          'Organic Chemistry',
          'Inorganic Chemistry',
          'Chemical Equilibrium',
          'Electrochemistry',
          'Coordination Compounds',
        ],
      ),
      QuizSubjectGroup(
        name: 'Mathematics',
        icon: Icons.calculate_rounded,
        topics: [
          'Calculus',
          'Algebra',
          'Trigonometry',
          'Coordinate Geometry',
          'Vectors & 3D',
          'Probability',
        ],
      ),
    ],
  ),
  QuizExamCategory(
    id: 'neet',
    name: 'NEET',
    subtitle: 'Medical entrance',
    icon: Icons.medical_services_rounded,
    color: Color(0xFF10B981),
    subjects: [
      QuizSubjectGroup(
        name: 'Physics',
        icon: Icons.bolt_rounded,
        topics: [
          'Mechanics',
          'Thermodynamics',
          'Optics',
          'Electrostatics',
          'Modern Physics',
        ],
      ),
      QuizSubjectGroup(
        name: 'Chemistry',
        icon: Icons.science_rounded,
        topics: [
          'Organic Chemistry',
          'Inorganic Chemistry',
          'Physical Chemistry',
          'Biomolecules',
        ],
      ),
      QuizSubjectGroup(
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
      ),
    ],
  ),
  QuizExamCategory(
    id: 'upsc',
    name: 'UPSC',
    subtitle: 'Civil services prep',
    icon: Icons.account_balance_rounded,
    color: Color(0xFFF59E0B),
    subjects: [
      QuizSubjectGroup(
        name: 'Polity',
        icon: Icons.gavel_rounded,
        topics: [
          'Indian Constitution',
          'Fundamental Rights',
          'Parliament & Legislature',
          'Judiciary',
          'Local Government',
        ],
      ),
      QuizSubjectGroup(
        name: 'History',
        icon: Icons.menu_book_rounded,
        topics: [
          'Modern India',
          'Ancient India',
          'Medieval India',
          'World History',
        ],
      ),
      QuizSubjectGroup(
        name: 'Geography',
        icon: Icons.public_rounded,
        topics: [
          'Indian Geography',
          'Physical Geography',
          'Climate & Environment',
          'World Geography',
        ],
      ),
      QuizSubjectGroup(
        name: 'Economy',
        icon: Icons.trending_up_rounded,
        topics: [
          'Indian Economy',
          'Budget & Fiscal Policy',
          'Banking & Finance',
          'Agriculture & Industry',
        ],
      ),
    ],
  ),
  QuizExamCategory(
    id: 'ssc_banking',
    name: 'SSC & Banking',
    subtitle: 'Government job exams',
    icon: Icons.work_rounded,
    color: Color(0xFF3B82F6),
    subjects: [
      QuizSubjectGroup(
        name: 'Quantitative Aptitude',
        icon: Icons.calculate_rounded,
        topics: [
          'Percentage & Ratio',
          'Profit & Loss',
          'Time & Work',
          'Speed & Distance',
          'Data Interpretation',
        ],
      ),
      QuizSubjectGroup(
        name: 'Reasoning',
        icon: Icons.psychology_rounded,
        topics: [
          'Logical Reasoning',
          'Puzzles',
          'Syllogism',
          'Blood Relations',
          'Coding-Decoding',
        ],
      ),
      QuizSubjectGroup(
        name: 'English',
        icon: Icons.translate_rounded,
        topics: [
          'Grammar',
          'Vocabulary',
          'Reading Comprehension',
          'Error Spotting',
        ],
      ),
      QuizSubjectGroup(
        name: 'General Awareness',
        icon: Icons.newspaper_rounded,
        topics: [
          'Current Affairs',
          'Indian History',
          'Geography',
          'Science & Tech',
        ],
      ),
    ],
  ),
  QuizExamCategory(
    id: 'cbse',
    name: 'CBSE Board',
    subtitle: 'Class 10 & 12 only',
    icon: Icons.school_rounded,
    color: Color(0xFF8B5CF6),
    subjects: [
      QuizSubjectGroup(
        name: 'Class 10 — Science',
        icon: Icons.science_outlined,
        topics: [
          'Light & Reflection',
          'Electricity',
          'Chemical Reactions',
          'Life Processes',
          'Heredity',
        ],
      ),
      QuizSubjectGroup(
        name: 'Class 10 — Maths',
        icon: Icons.functions_rounded,
        topics: [
          'Polynomials',
          'Quadratic Equations',
          'Triangles',
          'Statistics',
          'Trigonometry',
        ],
      ),
      QuizSubjectGroup(
        name: 'Class 12 — Physics',
        icon: Icons.bolt_rounded,
        topics: [
          'Electrostatics',
          'Current Electricity',
          'Magnetism',
          'Optics',
          'Semiconductor Devices',
        ],
      ),
      QuizSubjectGroup(
        name: 'Class 12 — Chemistry',
        icon: Icons.science_rounded,
        topics: [
          'Solutions',
          'Electrochemistry',
          'Organic Chemistry',
          'Biomolecules',
        ],
      ),
      QuizSubjectGroup(
        name: 'Class 12 — Biology',
        icon: Icons.biotech_rounded,
        topics: [
          'Reproduction',
          'Genetics',
          'Evolution',
          'Human Health & Disease',
        ],
      ),
      QuizSubjectGroup(
        name: 'Class 12 — Maths',
        icon: Icons.calculate_rounded,
        topics: [
          'Calculus',
          'Matrices',
          'Probability',
          'Vectors & 3D',
        ],
      ),
    ],
  ),
];
