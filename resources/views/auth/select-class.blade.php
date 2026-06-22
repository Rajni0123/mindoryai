<!DOCTYPE html>
<html class="dark scroll-smooth" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quick Setup - BlinkStudy</title>
    <meta name="theme-color" content="#11131a"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"/>

    <style>
        :root {
            --bg: #0b0e15;
            --surface: #11131a;
            --surface-low: #191b22;
            --surface-card: #1d1f27;
            --surface-high: #272a31;
            --border: rgba(255, 255, 255, 0.08);
            --border-strong: rgba(255, 255, 255, 0.12);
            --text: #e1e2ec;
            --text-muted: #c2c6d6;
            --text-dim: #8b92a8;
            --primary: #afc6ff;
            --primary-dark: #002d6c;
            --secondary: #ddb8ff;
            --outline: #424753;
            --success: #4ade80;
            --error: #f87171;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .ambient {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }

        .ambient::before,
        .ambient::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
        }

        .ambient::before {
            width: 420px;
            height: 420px;
            top: 10%;
            left: 15%;
            background: rgba(175, 198, 255, 0.12);
        }

        .ambient::after {
            width: 360px;
            height: 360px;
            bottom: 10%;
            right: 12%;
            background: rgba(221, 184, 255, 0.1);
        }

        .shell {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            border-bottom: 1px solid var(--border);
            background: rgba(17, 19, 26, 0.85);
            backdrop-filter: blur(16px);
        }

        .topbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--primary);
            font-family: Manrope, sans-serif;
            font-weight: 700;
            font-size: 1.125rem;
        }

        .brand .material-symbols-outlined { font-size: 24px; }

        .main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 24px 48px;
        }

        .layout {
            width: 100%;
            max-width: 1080px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
            align-items: center;
        }

        @media (min-width: 900px) {
            .layout {
                grid-template-columns: 1fr 480px;
                gap: 64px;
            }
        }

        .hero-copy {
            display: none;
        }

        @media (min-width: 900px) {
            .hero-copy { display: block; }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            border: 1px solid rgba(175, 198, 255, 0.2);
            background: rgba(175, 198, 255, 0.08);
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .hero-title {
            font-family: Manrope, sans-serif;
            font-size: clamp(2rem, 4vw, 2.75rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.02em;
            margin: 0 0 16px;
        }

        .hero-title span {
            background: linear-gradient(135deg, #afc6ff, #ddb8ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc {
            font-size: 16px;
            line-height: 1.65;
            color: var(--text-muted);
            max-width: 420px;
            margin: 0 0 28px;
        }

        .hero-points {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .hero-points li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .hero-points .material-symbols-outlined {
            font-size: 20px;
            color: var(--primary);
        }

        .mobile-header {
            margin-bottom: 8px;
        }

        @media (min-width: 900px) {
            .mobile-header { display: none; }
        }

        .panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-top-color: rgba(255, 255, 255, 0.12);
            border-left-color: rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
        }

        .panel-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .panel-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(175, 198, 255, 0.25), rgba(221, 184, 255, 0.2));
            border: 1px solid rgba(175, 198, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .panel-icon .material-symbols-outlined {
            color: var(--primary);
            font-size: 22px;
        }

        .panel-title {
            margin: 0;
            font-family: Manrope, sans-serif;
            font-size: 20px;
            font-weight: 700;
        }

        .panel-sub {
            margin: 2px 0 0;
            font-size: 13px;
            color: var(--text-dim);
        }

        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.02em;
            margin-bottom: 8px;
        }

        .field-group { margin-bottom: 18px; }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 20px;
            pointer-events: none;
        }

        .input {
            width: 100%;
            padding: 13px 14px 13px 44px;
            border-radius: 12px;
            border: 1px solid var(--outline);
            background: var(--surface-low);
            color: var(--text);
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .input::placeholder { color: var(--text-dim); }

        .input:focus {
            border-color: rgba(175, 198, 255, 0.5);
            box-shadow: 0 0 0 3px rgba(175, 198, 255, 0.12);
        }

        .input.has-suffix { padding-right: 44px; }

        .input-suffix {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            display: none;
        }

        .input-suffix.visible { display: block; }

        .input-suffix .material-symbols-outlined { font-size: 20px; }

        .suggestions {
            margin-top: 8px;
            background: var(--surface-low);
            border: 1px solid var(--outline);
            border-radius: 12px;
            max-height: 240px;
            overflow-y: auto;
            display: none;
        }

        .suggestions.visible { display: block; }

        .suggestion-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 14px;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
        }

        .suggestion-item + .suggestion-item {
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .suggestion-item:hover,
        .suggestion-item.selected {
            background: rgba(175, 198, 255, 0.08);
            color: var(--primary);
        }

        .exam-chip {
            display: none;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 10px;
            background: rgba(175, 198, 255, 0.08);
            border: 1px solid rgba(175, 198, 255, 0.2);
        }

        .exam-chip.visible { display: flex; }

        .exam-chip-text {
            flex: 1;
            font-size: 12px;
            font-weight: 700;
            color: var(--primary);
        }

        .exam-chip-clear {
            border: none;
            background: none;
            cursor: pointer;
            color: var(--text-dim);
            padding: 0;
            display: flex;
        }

        .board-row {
            display: none;
            gap: 12px;
            margin-top: 4px;
        }

        .board-row.visible {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .select {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--outline);
            background: var(--surface-low);
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            outline: none;
        }

        .select option {
            background: var(--surface-card);
            color: var(--text);
        }

        .board-hint {
            display: none;
            margin-top: 8px;
            font-size: 11px;
            color: var(--text-dim);
        }

        .board-hint.visible { display: block; }

        .alert {
            display: none;
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert.error {
            display: block;
            background: rgba(248, 113, 113, 0.1);
            color: var(--error);
            border: 1px solid rgba(248, 113, 113, 0.25);
        }

        .alert.success {
            display: block;
            background: rgba(74, 222, 128, 0.1);
            color: var(--success);
            border: 1px solid rgba(74, 222, 128, 0.25);
        }

        .btn-primary {
            width: 100%;
            height: 52px;
            margin-top: 8px;
            border: none;
            border-radius: 12px;
            background: var(--primary);
            color: var(--primary-dark);
            font-family: Manrope, sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 0 20px rgba(175, 198, 255, 0.2);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 0 28px rgba(175, 198, 255, 0.35);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .skip-link {
            display: block;
            width: 100%;
            margin-top: 14px;
            border: none;
            background: none;
            color: var(--text-dim);
            font-family: inherit;
            font-size: 13px;
            cursor: pointer;
            text-align: center;
        }

        .skip-link:hover { color: var(--text-muted); }

        @media (max-width: 480px) {
            .board-row.visible { grid-template-columns: 1fr; }
            .panel { padding: 22px 18px; }
            .main { padding: 24px 16px 32px; }
        }
    </style>
</head>
<body>
    <div class="ambient"></div>

    <div class="shell">
        <header class="topbar">
            <div class="topbar-inner">
                <a href="{{ route('home') }}" class="brand">
                    <span class="material-symbols-outlined">auto_stories</span>
                    BlinkStudy
                </a>
                <a href="{{ route('home') }}" class="skip-link" style="width:auto;margin:0;font-size:14px;">Back to home</a>
            </div>
        </header>

        <main class="main">
            <div class="layout">
                <div class="hero-copy">
                    <div class="hero-badge">
                        <span class="material-symbols-outlined" style="font-size:16px">bolt</span>
                        30-second setup
                    </div>
                    <h1 class="hero-title">
                        Personalize your<br/>
                        <span>AI study journey</span>
                    </h1>
                    <p class="hero-desc">
                        Tell us your exam goal once — BlinkStudy will tailor quizzes, doubt solving, and revision to your syllabus.
                    </p>
                    <ul class="hero-points">
                        <li><span class="material-symbols-outlined">check_circle</span> JEE, NEET, UPSC, SSC &amp; 50+ exams</li>
                        <li><span class="material-symbols-outlined">check_circle</span> Smart AI tutor matched to your level</li>
                        <li><span class="material-symbols-outlined">check_circle</span> One profile — syncs with mobile app</li>
                    </ul>
                </div>

                <div class="panel" id="page">
                    <div class="mobile-header panel-head">
                        <div class="panel-icon">
                            <span class="material-symbols-outlined">school</span>
                        </div>
                        <div>
                            <h2 class="panel-title">Quick Setup</h2>
                            <p class="panel-sub">Personalize BlinkStudy in 30 seconds</p>
                        </div>
                    </div>

                    <div id="alert" class="alert"></div>

                    <div class="field-group">
                        <label class="field-label" for="name">Your name</label>
                        <div class="input-wrap">
                            <span class="material-symbols-outlined input-icon">person</span>
                            <input id="name" class="input" type="text" placeholder="Enter your name" value="{{ $defaultName }}" autocomplete="name">
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="exam-search">What are you preparing for?</label>
                        <div class="input-wrap">
                            <span class="material-symbols-outlined input-icon">search</span>
                            <input id="exam-search" class="input has-suffix" type="text" placeholder="Search — JEE, UPSC, SSC, RRB, IBPS..." autocomplete="off">
                            <span id="exam-check" class="input-suffix">
                                <span class="material-symbols-outlined">check_circle</span>
                            </span>
                        </div>

                        <div id="suggestions" class="suggestions"></div>

                        <div id="exam-chip" class="exam-chip">
                            <span class="material-symbols-outlined" style="font-size:16px;color:var(--primary)">verified</span>
                            <span id="exam-chip-text" class="exam-chip-text"></span>
                            <button type="button" id="exam-clear" class="exam-chip-clear" aria-label="Clear exam">
                                <span class="material-symbols-outlined" style="font-size:16px">close</span>
                            </button>
                        </div>
                    </div>

                    <div id="board-row" class="board-row">
                        <div>
                            <label class="field-label" for="student-class">Class</label>
                            <select id="student-class" class="select">
                                @foreach($setupClasses as $class)
                                    <option value="{{ $class }}" @selected($class === '12')>Class {{ $class }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label" for="subjects">Subjects</label>
                            <select id="subjects" class="select"></select>
                        </div>
                    </div>
                    <p id="board-hint" class="board-hint">Select your class and subject stream</p>

                    <button type="button" id="submit-btn" class="btn-primary">Start Learning</button>
                    <button type="button" id="skip-btn" class="skip-link">Skip for now</button>
                </div>
            </div>
        </main>
    </div>

    <script>
        const POPULAR_EXAMS = @json($popularExams);
        const EXAM_CATALOG = @json($examCatalog);
        const SETUP_CLASSES = @json($setupClasses);
        const CHAT_SKIP_URL = @json($chatSkipUrl);
        const UPDATE_URL = @json(route('class.update'));
        const WELCOME_MESSAGE = @json(session('success'));

        let selectedExam = null;
        let showSuggestions = false;
        let saving = false;

        const nameInput = document.getElementById('name');
        const examSearch = document.getElementById('exam-search');
        const suggestionsEl = document.getElementById('suggestions');
        const examChip = document.getElementById('exam-chip');
        const examChipText = document.getElementById('exam-chip-text');
        const examCheck = document.getElementById('exam-check');
        const boardRow = document.getElementById('board-row');
        const boardHint = document.getElementById('board-hint');
        const subjectsSelect = document.getElementById('subjects');
        const studentClassSelect = document.getElementById('student-class');
        const alertEl = document.getElementById('alert');
        const submitBtn = document.getElementById('submit-btn');
        const skipBtn = document.getElementById('skip-btn');

        function showAlert(message, type = 'error') {
            alertEl.textContent = message;
            alertEl.className = 'alert ' + type;
        }

        function hideAlert() {
            alertEl.className = 'alert';
            alertEl.textContent = '';
        }

        function normalizeQuery(q) {
            return q.trim().toLowerCase().replace(/\s+/g, '');
        }

        function levenshtein(a, b) {
            if (a === b) return 0;
            if (!a.length) return b.length;
            if (!b.length) return a.length;
            const matrix = Array.from({ length: a.length + 1 }, () => Array(b.length + 1).fill(0));
            for (let i = 0; i <= a.length; i++) matrix[i][0] = i;
            for (let j = 0; j <= b.length; j++) matrix[0][j] = j;
            for (let i = 1; i <= a.length; i++) {
                for (let j = 1; j <= b.length; j++) {
                    const cost = a[i - 1] === b[j - 1] ? 0 : 1;
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j - 1] + cost
                    );
                }
            }
            return matrix[a.length][b.length];
        }

        function isCloseSpelling(a, b) {
            if (!a || !b) return false;
            const dist = levenshtein(a, b);
            const maxLen = Math.max(a.length, b.length);
            return dist <= (maxLen <= 4 ? 1 : 2);
        }

        function matchScore(query, entry) {
            const name = entry.name.toLowerCase().replace(/[^a-z0-9]/g, '');
            if (name.startsWith(query)) return 100;
            if (name.includes(query)) return 80;
            for (const keyword of entry.keywords) {
                const k = keyword.replace(/[^a-z0-9]/g, '');
                if (k.startsWith(query)) return 70;
                if (k.includes(query)) return 60;
                if (query.length >= 3 && isCloseSpelling(query, k)) return 50;
            }
            if (query.length >= 3 && isCloseSpelling(query, name)) return 40;
            return 0;
        }

        function searchExams(query) {
            const q = normalizeQuery(query);
            if (!q) return POPULAR_EXAMS;
            const scored = [];
            for (const entry of EXAM_CATALOG) {
                const score = matchScore(q, entry);
                if (score > 0) scored.push({ name: entry.name, score });
            }
            scored.sort((a, b) => b.score - a.score);
            return scored.map(item => item.name);
        }

        function isKnownExam(exam) {
            return EXAM_CATALOG.some(e => e.name.toLowerCase() === exam.toLowerCase());
        }

        function requiresBoardSetup(exam) {
            const e = exam.toLowerCase();
            return e.includes('cbse') || e.includes('icse');
        }

        function subjectsForExam(exam) {
            const e = exam.toLowerCase();
            if (e.includes('cbse') || e.includes('icse')) return ['PCM', 'PCB', 'PCMB', 'Commerce', 'Arts'];
            if (e.includes('neet')) return ['PCB', 'PCMB'];
            if (e.includes('jee')) return ['PCM', 'PCMB'];
            return ['PCM', 'PCB', 'PCMB', 'Commerce', 'Arts'];
        }

        function defaultSubjectsForExam(exam) {
            const e = exam.toLowerCase();
            if (e.includes('neet')) return 'PCB';
            if (e.includes('jee')) return 'PCM';
            if (requiresBoardSetup(exam)) return 'PCM';
            return 'General';
        }

        function defaultClassForExam() {
            return '12';
        }

        function syncSubjectsDropdown(exam, keepValue) {
            const options = subjectsForExam(exam);
            subjectsSelect.innerHTML = options.map(opt =>
                `<option value="${opt}">${opt}</option>`
            ).join('');
            if (keepValue && options.includes(keepValue)) {
                subjectsSelect.value = keepValue;
            } else {
                subjectsSelect.value = defaultSubjectsForExam(exam);
            }
        }

        function renderSuggestions() {
            const exams = searchExams(examSearch.value).slice(0, 12);
            if (!showSuggestions || exams.length === 0) {
                suggestionsEl.classList.remove('visible');
                suggestionsEl.innerHTML = '';
                return;
            }

            suggestionsEl.innerHTML = exams.map(exam => `
                <button type="button" class="suggestion-item${selectedExam === exam ? ' selected' : ''}" data-exam="${exam.replace(/"/g, '&quot;')}">
                    <span class="material-symbols-outlined" style="font-size:18px;color:${selectedExam === exam ? 'var(--primary)' : 'var(--text-dim)'}">school</span>
                    <span>${exam}</span>
                    ${selectedExam === exam ? '<span class="material-symbols-outlined" style="font-size:18px;color:var(--primary);margin-left:auto">check</span>' : ''}
                </button>
            `).join('');

            suggestionsEl.querySelectorAll('.suggestion-item').forEach(btn => {
                btn.addEventListener('click', () => selectExam(btn.dataset.exam));
            });

            suggestionsEl.classList.add('visible');
        }

        function updateBoardUI() {
            const needsBoard = selectedExam && requiresBoardSetup(selectedExam);
            boardRow.classList.toggle('visible', !!needsBoard);
            boardHint.classList.toggle('visible', !!needsBoard);
            if (needsBoard) {
                syncSubjectsDropdown(selectedExam, subjectsSelect.value);
            }
        }

        function updateExamUI() {
            const hasExam = !!selectedExam;
            examCheck.classList.toggle('visible', hasExam && !showSuggestions);
            examChip.classList.toggle('visible', hasExam && !showSuggestions);
            if (hasExam) {
                examChipText.textContent = selectedExam;
            }
            updateBoardUI();
            renderSuggestions();
        }

        function selectExam(exam) {
            selectedExam = exam;
            examSearch.value = exam;
            showSuggestions = false;
            syncSubjectsDropdown(exam);
            updateExamUI();
            examSearch.blur();
        }

        function clearExam() {
            selectedExam = null;
            examSearch.value = '';
            showSuggestions = true;
            updateExamUI();
            examSearch.focus();
        }

        examSearch.addEventListener('input', () => {
            if (selectedExam && examSearch.value !== selectedExam) {
                selectedExam = null;
            }
            showSuggestions = true;
            updateExamUI();
        });

        examSearch.addEventListener('focus', () => {
            showSuggestions = true;
            renderSuggestions();
        });

        document.getElementById('exam-clear').addEventListener('click', clearExam);

        document.getElementById('page').addEventListener('click', (e) => {
            if (!e.target.closest('#exam-search') && !e.target.closest('#suggestions')) {
                showSuggestions = false;
                renderSuggestions();
                updateExamUI();
            }
        });

        async function submitProfile() {
            if (saving) return;
            hideAlert();

            const name = nameInput.value.trim();
            if (name.length < 2) {
                showAlert('Enter your name to continue');
                nameInput.focus();
                return;
            }

            let exam = selectedExam;
            if (!exam) {
                const matches = searchExams(examSearch.value);
                exam = matches[0] || null;
            }

            if (!exam || !isKnownExam(exam)) {
                showAlert('Search and select your exam from the list');
                showSuggestions = true;
                renderSuggestions();
                examSearch.focus();
                return;
            }

            const needsBoard = requiresBoardSetup(exam);
            const studentClass = needsBoard ? studentClassSelect.value : defaultClassForExam(exam);
            const subjects = needsBoard ? subjectsSelect.value : defaultSubjectsForExam(exam);

            saving = true;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Personalizing...';

            try {
                const response = await fetch(UPDATE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        name,
                        target_exam: exam,
                        student_class: studentClass,
                        subjects,
                    }),
                });

                const data = await response.json();

                if (data.success && data.redirect) {
                    showAlert(data.message || 'Profile saved!', 'success');
                    window.location.href = data.redirect;
                    return;
                }

                showAlert(data.message || 'Could not save profile. Try again.');
            } catch (err) {
                showAlert('Could not save profile. Try again.');
            } finally {
                saving = false;
                submitBtn.disabled = false;
                submitBtn.textContent = 'Start Learning';
            }
        }

        submitBtn.addEventListener('click', submitProfile);
        skipBtn.addEventListener('click', () => {
            window.location.href = CHAT_SKIP_URL;
        });

        if (WELCOME_MESSAGE) {
            showAlert(WELCOME_MESSAGE, 'success');
        }

        updateExamUI();
    </script>
</body>
</html>
