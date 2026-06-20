<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quick Setup - BlinkStudy</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>

    <style>
        :root {
            --primary: #705CF6;
            --primary-light: #7B61FF;
            --secondary: #5B8CFF;
            --background: #F7F8FC;
            --card: #FFFFFF;
            --card-border: #E8EBF4;
            --text-primary: #0F1222;
            --text-muted: #9CA3AF;
            --error: #EF4444;
            --success: #22C55E;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--background);
            color: var(--text-primary);
        }

        .page {
            min-height: 100vh;
            max-width: 520px;
            margin: 0 auto;
            padding: 12px 20px 16px;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .header-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary-light), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .header-icon svg { width: 22px; height: 22px; fill: white; }

        .header-title {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.2;
        }

        .header-subtitle {
            margin: 2px 0 0;
            font-size: 12px;
            color: var(--text-muted);
        }

        .card {
            flex: 1;
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 16px;
            overflow: auto;
        }

        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.3px;
            margin-bottom: 6px;
        }

        .field-group { margin-bottom: 16px; }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            background: var(--background);
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.15s;
        }

        .input:focus {
            border-color: var(--primary);
            border-width: 1.5px;
            padding: 11.5px 13.5px 11.5px 41.5px;
        }

        .input.has-suffix { padding-right: 42px; }

        .input-suffix {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            display: none;
        }

        .input-suffix.visible { display: block; }

        .suggestions {
            margin-top: 8px;
            background: var(--background);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            max-height: 260px;
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
            font-weight: 600;
            color: var(--text-primary);
        }

        .suggestion-item + .suggestion-item {
            border-top: 1px solid var(--card-border);
        }

        .suggestion-item:hover,
        .suggestion-item.selected {
            color: var(--primary);
        }

        .suggestion-item.selected { font-weight: 700; }

        .exam-chip {
            display: none;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 10px;
            background: rgba(112, 92, 246, 0.08);
            border: 1px solid rgba(112, 92, 246, 0.25);
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
            color: var(--text-muted);
            padding: 0;
            display: flex;
        }

        .board-row {
            display: none;
            gap: 10px;
            margin-top: 16px;
        }

        .board-row.visible {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            background: var(--background);
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            outline: none;
        }

        .board-hint {
            display: none;
            margin-top: 8px;
            font-size: 11px;
            color: var(--text-muted);
        }

        .board-hint.visible { display: block; }

        .alert {
            display: none;
            margin-bottom: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert.error {
            display: block;
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert.success {
            display: block;
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .actions {
            margin-top: 14px;
        }

        .btn-primary {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 16px;
            background: var(--primary);
            color: white;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.15s, transform 0.15s;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
        }

        .btn-primary:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .skip-link {
            display: block;
            width: 100%;
            margin-top: 12px;
            border: none;
            background: none;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 13px;
            cursor: pointer;
            text-align: center;
        }

        .skip-link:hover { color: var(--text-primary); }

        @media (max-width: 480px) {
            .board-row.visible { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="page" id="page">
        <div class="header">
            <div class="header-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 11.08L5.16 11 12 7.25 18.84 11 12 14.08zM3 13.5V18c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-4.5l-8 4.36-8-4.36z"/>
                </svg>
            </div>
            <div>
                <h1 class="header-title">Quick Setup</h1>
                <p class="header-subtitle">Personalize BlinkStudy in 30 seconds</p>
            </div>
        </div>

        <div id="alert" class="alert"></div>

        <div class="card">
            <div class="field-group">
                <label class="field-label" for="name">Your name</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input id="name" class="input" type="text" placeholder="Enter your name" value="{{ $defaultName }}" autocomplete="name">
                </div>
            </div>

            <div class="field-group">
                <label class="field-label" for="exam-search">What are you preparing for?</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input id="exam-search" class="input has-suffix" type="text" placeholder="Search — JEE, UPSC, SSC, RRB, IBPS..." autocomplete="off">
                    <span id="exam-check" class="input-suffix">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </span>
                </div>

                <div id="suggestions" class="suggestions"></div>

                <div id="exam-chip" class="exam-chip">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color: var(--primary); flex-shrink: 0;">
                        <path d="M12 1 3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>
                    </svg>
                    <span id="exam-chip-text" class="exam-chip-text"></span>
                    <button type="button" id="exam-clear" class="exam-chip-clear" aria-label="Clear exam">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
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
        </div>

        <div class="actions">
            <button type="button" id="submit-btn" class="btn-primary">Start Learning</button>
            <button type="button" id="skip-btn" class="skip-link">Skip for now</button>
        </div>
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
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: ${selectedExam === exam ? 'var(--primary)' : 'var(--text-muted)'}">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
                    </svg>
                    <span>${exam}</span>
                    ${selectedExam === exam ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color: var(--primary); margin-left: auto;"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>' : ''}
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
