<style>
    #appMockTestView.hidden { display: none !important; }

    .mock-shell {
        flex: 1;
        width: 100%;
        display: flex;
        flex-direction: column;
        min-width: 0;
        overflow: hidden;
    }

    .mock-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 20px 28px 28px;
        width: 100%;
    }

    .mock-page {
        max-width: 1400px;
        width: 100%;
        margin: 0 auto;
    }

    .mock-home-grid {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 18px;
        align-items: start;
    }

    .mock-full-span { grid-column: 1 / -1; }

    @media (max-width: 1024px) {
        .mock-home-grid { grid-template-columns: 1fr; }
    }

    .mock-settings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    @media (max-width: 640px) {
        .mock-settings-grid { grid-template-columns: 1fr; }
    }

    .mock-field-label {
        display: block;
        font-size: 12px;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }

    .mock-card {
        background: rgba(29, 31, 39, 0.72);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 20px;
        margin-bottom: 14px;
    }

    .mock-info-row {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px;
        border-radius: 14px;
        background: rgba(11, 14, 21, 0.45);
        margin-bottom: 10px;
    }

    .mock-info-row .material-symbols-outlined { color: #afc6ff; }

    .mock-field {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        background: rgba(11, 14, 21, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: var(--text-primary);
        font-size: 14px;
        margin-bottom: 10px;
    }

    .mock-btn {
        width: 100%;
        padding: 14px;
        border-radius: 14px;
        border: none;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        background: linear-gradient(135deg, #7b61ff, #528dff);
        color: #fff;
        transition: opacity 0.2s;
    }

    .mock-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .mock-btn-outline {
        background: transparent;
        border: 1px solid rgba(175, 198, 255, 0.35);
        color: #afc6ff;
        width: auto;
        padding: 10px 16px;
    }

    .mock-timer {
        font-size: 26px;
        font-weight: 800;
        color: #afc6ff;
        padding: 8px 14px;
        border-radius: 12px;
        background: rgba(29, 31, 39, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .mock-timer.danger { color: #f87171; border-color: rgba(248, 113, 113, 0.4); }

    .mock-q-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 14px;
    }

    .mock-q-dot {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: rgba(11, 14, 21, 0.6);
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }

    .mock-q-dot.current { border-color: #528dff; color: #afc6ff; background: rgba(82, 141, 255, 0.15); }
    .mock-q-dot.answered { background: rgba(34, 197, 94, 0.2); border-color: rgba(34, 197, 94, 0.4); color: #86efac; }

    .mock-option {
        display: block;
        width: 100%;
        text-align: left;
        padding: 14px 16px;
        margin-bottom: 8px;
        border-radius: 12px;
        background: rgba(11, 14, 21, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: var(--text-primary);
        cursor: pointer;
        font-size: 14px;
    }

    .mock-option.selected { border-color: #528dff; background: rgba(82, 141, 255, 0.12); }

    .mock-exam-layout {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 18px;
        align-items: start;
    }

    .mock-exam-side {
        position: sticky;
        top: 0;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .mock-exam-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .mock-results-grid {
        display: grid;
        grid-template-columns: minmax(260px, 0.85fr) 1.15fr;
        gap: 18px;
        align-items: start;
    }

    @media (max-width: 900px) {
        .mock-exam-layout,
        .mock-results-grid { grid-template-columns: 1fr; }
        .mock-exam-side { position: static; }
    }

    .mock-history-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        font-size: 13px;
    }

    .mock-loading {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-secondary);
    }

    .mock-score-ring {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(123, 97, 255, 0.3), rgba(82, 141, 255, 0.2));
        border: 3px solid #528dff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 28px;
        font-weight: 800;
        color: #afc6ff;
    }

    body.app-view-mock_test .sidebar-chat-tools { display: none; }
</style>

@php
    $webMockExams = \App\Models\Exam::active()
        ->orderBy('order')
        ->get(['id', 'name', 'slug', 'subjects', 'category'])
        ->map(fn ($exam) => [
            'id' => $exam->id,
            'name' => $exam->name,
            'slug' => $exam->slug,
            'subjects' => $exam->subjects ?? [],
            'category' => $exam->category,
        ])
        ->values();
    $webUserTargetExam = auth()->user()?->target_exam;
@endphp

<div id="appMockTestView" class="mock-shell hidden">
    <div class="dash-topbar">
        <div>
            <p class="dash-greeting">Full-length practice tests</p>
            <h2>Mock Test</h2>
        </div>
        <div class="dash-topbar-actions">
            <div class="dash-search">
                <span class="material-symbols-outlined text-[18px] text-[var(--text-secondary)]">search</span>
                <input type="text" placeholder="Search exams, subjects..." id="mockSearchInput" onkeydown="if(event.key==='Enter'){window.switchAppView('chat');}">
            </div>
            <button type="button" class="dash-icon-btn" title="Back to Dashboard" onclick="window.switchAppView('dashboard')">
                <span class="material-symbols-outlined text-[20px]">dashboard</span>
            </button>
        </div>
    </div>

    <div class="mock-scroll">
        <div class="mock-page">
            <div id="mockHomeView">
                <div class="mock-home-grid">
                    <div class="mock-column">
                        <div class="mock-card">
                            <h3 style="font-size:15px;font-weight:700;margin:0 0 14px;color:var(--text-primary)">Test Configuration</h3>

                            <label class="mock-field-label" for="mockExamSelect">Select Exam</label>
                            <select id="mockExamSelect" class="mock-field">
                                <option value="">Loading exams...</option>
                            </select>

                            <div class="mock-settings-grid">
                                <div>
                                    <label class="mock-field-label" for="mockSubjectSelect">Subject</label>
                                    <select id="mockSubjectSelect" class="mock-field" style="margin-bottom:0">
                                        <option value="">All Subjects</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mock-field-label" for="mockLanguage">Language</label>
                                    <select id="mockLanguage" class="mock-field" style="margin-bottom:0">
                                        <option value="English" selected>English</option>
                                        <option value="Hindi">Hindi</option>
                                        <option value="Hinglish">Hinglish</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mock-settings-grid" style="margin-top:12px">
                                <div>
                                    <label class="mock-field-label" for="mockQuestionCount">Questions</label>
                                    <select id="mockQuestionCount" class="mock-field" style="margin-bottom:0">
                                        <option value="10">10 Questions</option>
                                        <option value="20">20 Questions</option>
                                        <option value="30" selected>30 Questions</option>
                                        <option value="50">50 Questions</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mock-field-label" for="mockDuration">Duration (minutes)</label>
                                    <select id="mockDuration" class="mock-field" style="margin-bottom:0">
                                        <option value="15">15 min</option>
                                        <option value="30">30 min</option>
                                        <option value="45" selected>45 min</option>
                                        <option value="60">60 min</option>
                                        <option value="90">90 min</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="mock-btn" id="mockStartBtn">Start Mock Test</button>
                    </div>

                    <div class="mock-column">
                        <div class="mock-card">
                            <div class="mock-info-row">
                                <span class="material-symbols-outlined">quiz</span>
                                <div><strong>Real PYQ + AI mix</strong><br><span style="font-size:12px;color:var(--text-secondary)">Previous year questions when available</span></div>
                            </div>
                            <div class="mock-info-row">
                                <span class="material-symbols-outlined">timer</span>
                                <div><strong>Timed exam</strong><br><span style="font-size:12px;color:var(--text-secondary)">Auto-submit when time runs out</span></div>
                            </div>
                            <div class="mock-info-row" style="margin-bottom:0">
                                <span class="material-symbols-outlined">analytics</span>
                                <div><strong>Instant analysis</strong><br><span style="font-size:12px;color:var(--text-secondary)">Score, accuracy & review</span></div>
                            </div>
                        </div>

                        <div class="mock-card">
                            <h3 style="font-size:15px;font-weight:700;margin:0 0 12px;color:var(--text-primary)">Recent Mock Tests</h3>
                            <div id="mockHistoryList"><p style="font-size:13px;color:var(--text-secondary)">Loading history...</p></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="mockLoadingView" style="display:none">
                <div class="mock-loading">
                    <span class="material-symbols-outlined" style="font-size:40px;animation:spin 1s linear infinite">progress_activity</span>
                    <p style="margin-top:14px;font-weight:600;color:var(--text-primary)">Generating your mock test...</p>
                    <p style="font-size:13px">This may take up to a minute</p>
                </div>
            </div>

            <div id="mockExamView" style="display:none" class="mock-exam-layout">
                <div class="mock-exam-main">
                    <div class="mock-exam-header">
                        <button type="button" class="mock-btn mock-btn-outline" id="mockExitExam">← Exit</button>
                    </div>
                    <div class="mock-card">
                        <p id="mockQProgress" style="font-size:12px;color:var(--text-secondary);margin:0 0 8px">Question 1 of 30</p>
                        <p id="mockQuestionText" style="font-size:16px;font-weight:600;line-height:1.5;color:var(--text-primary);margin:0 0 16px"></p>
                        <div id="mockOptions"></div>
                    </div>
                    <div style="display:flex;gap:10px;margin-top:12px">
                        <button type="button" class="mock-btn mock-btn-outline" id="mockPrevBtn" style="flex:1">Previous</button>
                        <button type="button" class="mock-btn" id="mockNextBtn" style="flex:2">Next</button>
                    </div>
                </div>
                <aside class="mock-exam-side">
                    <div class="mock-card" style="text-align:center;margin-bottom:0">
                        <p style="font-size:12px;color:var(--text-secondary);margin:0 0 6px">Time remaining</p>
                        <div class="mock-timer" id="mockExamTimer" style="display:inline-block">45:00</div>
                    </div>
                    <div class="mock-card" style="margin-bottom:0">
                        <p style="font-size:12px;font-weight:600;color:var(--text-secondary);margin:0 0 10px;text-transform:uppercase;letter-spacing:0.05em">Questions</p>
                        <div class="mock-q-nav" id="mockQNav"></div>
                    </div>
                </aside>
            </div>

            <div id="mockResultsView" style="display:none" class="mock-results-grid">
                <div class="mock-card" style="text-align:center">
                    <div class="mock-score-ring" id="mockScoreRing">0%</div>
                    <h3 style="margin:0 0 6px;color:var(--text-primary)">Test Completed!</h3>
                    <p id="mockResultSummary" style="font-size:14px;color:var(--text-secondary);margin:0 0 16px"></p>
                    <button type="button" class="mock-btn" id="mockBackHomeBtn">Take Another Mock Test</button>
                </div>
                <div class="mock-card" id="mockResultDetails"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const MOCK_BOOTSTRAP = {
        exams: @json($webMockExams),
        targetExam: @json($webUserTargetExam),
    };

    const state = {
        exams: [],
        selectedExamId: null,
        mockTestId: null,
        questions: [],
        answers: {},
        currentIndex: 0,
        timerInterval: null,
        timeLeftSec: 0,
        startedAt: null,
    };

    async function mockApi(path, options = {}) {
        const fetcher = window.blinkApiFetch || (async (url, opts) => {
            const res = await fetch(url, {
                credentials: 'same-origin',
                ...opts,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    ...(opts.headers || {}),
                },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Request failed');
            return data;
        });

        return fetcher('/api/exams' + path, options);
    }

    function showView(name) {
        const displays = {
            mockHomeView: 'block',
            mockLoadingView: 'block',
            mockExamView: 'grid',
            mockResultsView: 'grid',
        };
        ['mockHomeView', 'mockLoadingView', 'mockExamView', 'mockResultsView'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = id === name ? (displays[id] || 'block') : 'none';
        });
    }

    function stopTimer() {
        if (state.timerInterval) {
            clearInterval(state.timerInterval);
            state.timerInterval = null;
        }
    }

    function formatTime(sec) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    function startTimer(seconds) {
        stopTimer();
        state.timeLeftSec = seconds;
        const el = document.getElementById('mockExamTimer');
        const tick = () => {
            if (el) {
                el.textContent = formatTime(state.timeLeftSec);
                el.classList.toggle('danger', state.timeLeftSec < 300);
            }
            if (state.timeLeftSec <= 0) {
                stopTimer();
                submitMockTest(true);
                return;
            }
            state.timeLeftSec--;
        };
        tick();
        state.timerInterval = setInterval(tick, 1000);
    }

    function normalizeExamName(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
    }

    function findDefaultExamId(exams) {
        if (!exams.length) return null;
        const target = normalizeExamName(MOCK_BOOTSTRAP.targetExam);
        if (target) {
            const matched = exams.find(exam => {
                const name = normalizeExamName(exam.name);
                const slug = normalizeExamName(exam.slug);
                return name.includes(target) || target.includes(name) || slug.includes(target.replace(/\s+/g, '-'));
            });
            if (matched) return matched.id;
        }
        return exams[0].id;
    }

    function renderSubjectOptions() {
        const select = document.getElementById('mockSubjectSelect');
        if (!select) return;

        const exam = state.exams.find(item => item.id === state.selectedExamId);
        const subjects = Array.isArray(exam?.subjects) ? exam.subjects.filter(Boolean) : [];
        const previous = select.value;

        select.innerHTML = '<option value="">All Subjects</option>' + subjects.map(subject =>
            `<option value="${subject}">${subject}</option>`
        ).join('');

        if (previous && subjects.includes(previous)) {
            select.value = previous;
        }
    }

    function renderExamSelect() {
        const select = document.getElementById('mockExamSelect');
        if (!select) return;

        if (!state.exams.length) {
            select.innerHTML = '<option value="">No exams available</option>';
            select.disabled = true;
            document.getElementById('mockStartBtn').disabled = true;
            return;
        }

        select.disabled = false;
        document.getElementById('mockStartBtn').disabled = false;
        select.innerHTML = '<option value="">Select exam</option>' + state.exams.map(exam =>
            `<option value="${exam.id}" ${state.selectedExamId === exam.id ? 'selected' : ''}>${exam.name}</option>`
        ).join('');

        if (state.selectedExamId) {
            select.value = String(state.selectedExamId);
        }

        renderSubjectOptions();
    }

    async function loadExams() {
        try {
            const res = await mockApi('/');
            const apiExams = res.data || [];
            if (apiExams.length) {
                state.exams = apiExams;
            }
        } catch (_) {
            // Fall back to server-rendered exam list below.
        }

        if (!state.exams.length && MOCK_BOOTSTRAP.exams?.length) {
            state.exams = MOCK_BOOTSTRAP.exams;
        }

        if (!state.selectedExamId) {
            state.selectedExamId = findDefaultExamId(state.exams);
        }

        renderExamSelect();
    }

    async function loadHistory() {
        const el = document.getElementById('mockHistoryList');
        if (!el) return;
        try {
            const res = await mockApi('/mock-test/history?per_page=5');
            const items = res.data?.data || res.data || [];
            if (!items.length) {
                el.innerHTML = '<p style="font-size:13px;color:var(--text-secondary)">No mock tests yet. Start your first one!</p>';
                return;
            }
            el.innerHTML = items.map(item => `
                <div class="mock-history-item">
                    <div>
                        <div style="font-weight:600;color:var(--text-primary)">${item.title || item.exam?.name || 'Mock Test'}</div>
                        <div style="font-size:12px;color:var(--text-secondary)">${item.total_questions || 0} Q · ${item.status || ''}</div>
                    </div>
                    <div style="font-weight:700;color:#afc6ff">${item.score != null ? item.score + ' pts' : '—'}</div>
                </div>
            `).join('');
        } catch (_) {
            el.innerHTML = '<p style="font-size:13px;color:var(--text-secondary)">No history available.</p>';
        }
    }

    async function startMockTest() {
        if (!state.selectedExamId) {
            alert('Please select an exam');
            return;
        }

        const btn = document.getElementById('mockStartBtn');
        btn.disabled = true;
        showView('mockLoadingView');

        try {
            const qCount = parseInt(document.getElementById('mockQuestionCount').value, 10);
            const duration = parseInt(document.getElementById('mockDuration').value, 10);
            const subject = document.getElementById('mockSubjectSelect').value.trim();
            const language = document.getElementById('mockLanguage').value;

            const gen = await mockApi('/mock-test/generate', {
                method: 'POST',
                body: JSON.stringify({
                    exam_id: state.selectedExamId,
                    question_count: qCount,
                    duration_minutes: duration,
                    subject: subject || null,
                    language: language,
                }),
            });

            const mockTest = gen.data;
            state.mockTestId = mockTest.id;

            const start = await mockApi('/mock-test/' + mockTest.id + '/start', { method: 'POST' });
            const payload = start.data || start;

            state.questions = payload.questions || [];
            state.answers = {};
            state.currentIndex = 0;
            state.startedAt = Date.now();

            const durationMin = payload.mock_test?.duration_minutes || duration;
            showView('mockExamView');
            startTimer(durationMin * 60);
            renderQuestion();
        } catch (e) {
            alert(e.message || 'Failed to start mock test');
            showView('mockHomeView');
        } finally {
            btn.disabled = false;
        }
    }

    function renderQuestionNav() {
        const nav = document.getElementById('mockQNav');
        if (!nav) return;
        nav.innerHTML = state.questions.map((q, i) => {
            const answered = state.answers[q.id] != null;
            const current = i === state.currentIndex;
            return `<button type="button" class="mock-q-dot ${current ? 'current' : ''} ${answered ? 'answered' : ''}" data-idx="${i}">${i + 1}</button>`;
        }).join('');
        nav.querySelectorAll('.mock-q-dot').forEach(btn => {
            btn.addEventListener('click', () => {
                state.currentIndex = parseInt(btn.dataset.idx, 10);
                renderQuestion();
            });
        });
    }

    function renderQuestion() {
        const q = state.questions[state.currentIndex];
        if (!q) return;

        document.getElementById('mockQProgress').textContent =
            `Question ${state.currentIndex + 1} of ${state.questions.length}`;
        document.getElementById('mockQuestionText').textContent = q.question_text || '';

        const selected = state.answers[q.id];
        const opts = q.options || [];
        document.getElementById('mockOptions').innerHTML = opts.map(opt => {
            const label = opt.label || 'A';
            const text = opt.text || '';
            const sel = selected === label ? 'selected' : '';
            return `<button type="button" class="mock-option ${sel}" data-label="${label}">${label}. ${text}</button>`;
        }).join('');

        document.getElementById('mockOptions').querySelectorAll('.mock-option').forEach(btn => {
            btn.addEventListener('click', () => {
                state.answers[q.id] = btn.dataset.label;
                renderQuestion();
            });
        });

        document.getElementById('mockPrevBtn').style.visibility = state.currentIndex > 0 ? 'visible' : 'hidden';
        document.getElementById('mockNextBtn').textContent =
            state.currentIndex < state.questions.length - 1 ? 'Next' : 'Submit Test';

        renderQuestionNav();
    }

    async function submitMockTest(auto = false) {
        if (!auto && !confirm('Submit your mock test?')) return;

        stopTimer();
        const btn = document.getElementById('mockNextBtn');
        if (btn) btn.disabled = true;

        try {
            const res = await mockApi('/mock-test/' + state.mockTestId + '/submit', {
                method: 'POST',
                body: JSON.stringify({ answers: state.answers }),
            });

            const d = res.data || {};
            const total = state.questions.length;
            const accuracy = d.accuracy ?? (total ? Math.round((d.correct_answers / total) * 100) : 0);

            document.getElementById('mockScoreRing').textContent = accuracy + '%';
            document.getElementById('mockResultSummary').textContent =
                `Score: ${d.score ?? 0} · ${d.correct_answers ?? 0} correct · ${d.wrong_answers ?? 0} wrong · ${d.unanswered ?? 0} skipped`;

            document.getElementById('mockResultDetails').innerHTML = `
                <div class="mock-info-row"><span class="material-symbols-outlined">check_circle</span><div>Correct: <strong>${d.correct_answers ?? 0}</strong></div></div>
                <div class="mock-info-row"><span class="material-symbols-outlined">cancel</span><div>Wrong: <strong>${d.wrong_answers ?? 0}</strong></div></div>
                <div class="mock-info-row"><span class="material-symbols-outlined">schedule</span><div>Time: <strong>${d.time_taken || '—'}</strong></div></div>
            `;

            showView('mockResultsView');
            loadHistory();
        } catch (e) {
            alert(e.message || 'Submit failed');
            showView('mockExamView');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function resetToHome() {
        stopTimer();
        state.mockTestId = null;
        state.questions = [];
        state.answers = {};
        state.currentIndex = 0;
        showView('mockHomeView');
        loadHistory();
    }

    document.getElementById('mockStartBtn')?.addEventListener('click', startMockTest);
    document.getElementById('mockExamSelect')?.addEventListener('change', (event) => {
        const value = parseInt(event.target.value, 10);
        state.selectedExamId = Number.isNaN(value) ? null : value;
        renderSubjectOptions();
    });
    document.getElementById('mockPrevBtn')?.addEventListener('click', () => {
        if (state.currentIndex > 0) {
            state.currentIndex--;
            renderQuestion();
        }
    });
    document.getElementById('mockNextBtn')?.addEventListener('click', () => {
        if (state.currentIndex < state.questions.length - 1) {
            state.currentIndex++;
            renderQuestion();
        } else {
            submitMockTest(false);
        }
    });
    document.getElementById('mockExitExam')?.addEventListener('click', () => {
        if (confirm('Exit mock test? Progress will be lost.')) resetToHome();
    });
    document.getElementById('mockBackHomeBtn')?.addEventListener('click', resetToHome);

    window.loadWebMockTest = function () {
        showView('mockHomeView');
        loadExams();
        loadHistory();
    };

    window.openMockTest = function () {
        if (typeof window.switchAppView === 'function') {
            window.switchAppView('mock_test');
        }
    };
})();
</script>
