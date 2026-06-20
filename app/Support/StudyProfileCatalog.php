<?php

namespace App\Support;

class StudyProfileCatalog
{
    public const POPULAR_EXAMS = [
        'JEE Main', 'JEE Advanced', 'NEET', 'CBSE Board', 'ICSE Board',
        'UPSC Civil Services (IAS)', 'SSC CGL', 'SSC CHSL', 'RRB NTPC',
        'IBPS PO', 'GATE', 'NDA', 'CUET UG', 'CTET', 'CLAT',
    ];

    /** @var array<int, array{name: string, keywords: array<int, string>}> */
    public const EXAM_CATALOG = [
        ['name' => 'JEE Main', 'keywords' => ['jee', 'main', 'jeemain', 'joint entrance', 'iit', 'engineering']],
        ['name' => 'JEE Advanced', 'keywords' => ['jee', 'advanced', 'jeeadvanced', 'iit jee', 'iit']],
        ['name' => 'NEET', 'keywords' => ['neet', 'neit', 'medical', 'mbbs', 'doctor']],
        ['name' => 'NEET PG', 'keywords' => ['neet pg', 'neetpg', 'medical pg']],
        ['name' => 'GATE', 'keywords' => ['gate', 'engineering', 'postgraduate']],
        ['name' => 'CUET UG', 'keywords' => ['cuet', 'cuet ug', 'central university']],
        ['name' => 'CUET PG', 'keywords' => ['cuet pg', 'cuetpg', 'central university pg']],
        ['name' => 'CBSE Board', 'keywords' => ['cbse', 'board', 'ncert', 'class 10', 'class 12', 'cbsc']],
        ['name' => 'ICSE Board', 'keywords' => ['icse', 'board', 'cisce', 'icsc']],
        ['name' => 'State Board', 'keywords' => ['state', 'board', 'bihar board', 'up board', 'maharashtra board']],
        ['name' => 'UPSC Civil Services (IAS)', 'keywords' => ['upsc', 'ias', 'ips', 'ifs', 'civil services', 'cse']],
        ['name' => 'UPSC CAPF AC', 'keywords' => ['upsc capf', 'capf', 'assistant commandant']],
        ['name' => 'UPSC CDS', 'keywords' => ['upsc cds', 'cds', 'combined defence']],
        ['name' => 'UPSC NDA', 'keywords' => ['upsc nda', 'nda', 'national defence academy']],
        ['name' => 'UPSC IES/ESE', 'keywords' => ['upsc ies', 'upsc ese', 'engineering services', 'ese']],
        ['name' => 'SSC CGL', 'keywords' => ['ssc cgl', 'cgl', 'graduate level', 'ssc']],
        ['name' => 'SSC CHSL', 'keywords' => ['ssc chsl', 'chsl', '10+2', 'ldc', 'udc']],
        ['name' => 'SSC MTS', 'keywords' => ['ssc mts', 'mts', 'multi tasking']],
        ['name' => 'SSC GD Constable', 'keywords' => ['ssc gd', 'gd constable', 'constable']],
        ['name' => 'SSC CPO', 'keywords' => ['ssc cpo', 'cpo', 'sub inspector', 'delhi police si']],
        ['name' => 'SSC JE', 'keywords' => ['ssc je', 'junior engineer', 'ssc engineer']],
        ['name' => 'IBPS PO', 'keywords' => ['ibps po', 'probationary officer', 'bank po', 'ibps']],
        ['name' => 'IBPS Clerk', 'keywords' => ['ibps clerk', 'bank clerk', 'clerk']],
        ['name' => 'IBPS SO', 'keywords' => ['ibps so', 'specialist officer', 'so']],
        ['name' => 'IBPS RRB', 'keywords' => ['ibps rrb', 'rrb po', 'rrb clerk', 'regional rural bank']],
        ['name' => 'SBI PO', 'keywords' => ['sbi po', 'state bank po']],
        ['name' => 'SBI Clerk', 'keywords' => ['sbi clerk', 'state bank clerk']],
        ['name' => 'RBI Grade B', 'keywords' => ['rbi grade b', 'rbi', 'reserve bank']],
        ['name' => 'RRB NTPC', 'keywords' => ['rrb ntpc', 'ntpc', 'railway ntpc', 'railway']],
        ['name' => 'RRB Group D', 'keywords' => ['rrb group d', 'group d', 'railway group d']],
        ['name' => 'RRB ALP', 'keywords' => ['rrb alp', 'alp', 'assistant loco pilot', 'technician']],
        ['name' => 'NDA', 'keywords' => ['nda', 'national defence academy', 'defence']],
        ['name' => 'CDS', 'keywords' => ['cds', 'combined defence services']],
        ['name' => 'AFCAT', 'keywords' => ['afcat', 'air force', 'flying branch']],
        ['name' => 'CTET', 'keywords' => ['ctet', 'central teacher eligibility', 'teacher']],
        ['name' => 'State TET', 'keywords' => ['tet', 'uptet', 'reet', 'teacher eligibility']],
        ['name' => 'CLAT', 'keywords' => ['clat', 'law', 'nlu']],
        ['name' => 'CAT', 'keywords' => ['cat', 'mba', 'iim']],
        ['name' => 'Other', 'keywords' => ['other', 'general', 'competitive']],
    ];

    public const SETUP_CLASSES = ['9', '10', '11', '12'];

    public static function isKnownExam(string $exam): bool
    {
        $exam = strtolower(trim($exam));
        foreach (self::EXAM_CATALOG as $entry) {
            if (strtolower($entry['name']) === $exam) {
                return true;
            }
        }

        return false;
    }

    public static function normalizeExam(string $exam): string
    {
        $exam = trim($exam);
        foreach (self::EXAM_CATALOG as $entry) {
            if (strcasecmp($entry['name'], $exam) === 0) {
                return $entry['name'];
            }
        }

        return $exam;
    }

    public static function requiresBoardSetup(string $exam): bool
    {
        $e = strtolower($exam);

        return str_contains($e, 'cbse') || str_contains($e, 'icse');
    }

    public static function defaultClassForExam(string $exam): string
    {
        return '12';
    }

    public static function defaultSubjectsForExam(string $exam): string
    {
        $e = strtolower($exam);
        if (str_contains($e, 'neet')) {
            return 'PCB';
        }
        if (str_contains($e, 'jee')) {
            return 'PCM';
        }
        if (self::requiresBoardSetup($exam)) {
            return 'PCM';
        }

        return 'General';
    }

    /** @return array<int, string> */
    public static function subjectsForExam(string $exam): array
    {
        $e = strtolower($exam);
        if (str_contains($e, 'cbse') || str_contains($e, 'icse')) {
            return ['PCM', 'PCB', 'PCMB', 'Commerce', 'Arts'];
        }
        if (str_contains($e, 'neet')) {
            return ['PCB', 'PCMB'];
        }
        if (str_contains($e, 'jee')) {
            return ['PCM', 'PCMB'];
        }

        return ['PCM', 'PCB', 'PCMB', 'Commerce', 'Arts'];
    }

    public static function needsStudySetup(?string $targetExam, ?string $studentClass, ?bool $isProfileComplete = null): bool
    {
        if ($targetExam === null || trim($targetExam) === '') {
            return true;
        }
        if ($studentClass === null || trim($studentClass) === '') {
            return true;
        }
        if ($isProfileComplete === false) {
            return true;
        }

        return false;
    }
}
