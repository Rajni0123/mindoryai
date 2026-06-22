<style>
    #appMockTestView.hidden { display: none !important; }

    .mock-shell {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        overflow: hidden;
    }

    .mock-topbar {
        flex-shrink: 0;
        padding: 20px 28px 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .mock-topbar h2 {
        font-family: Manrope, sans-serif;
        font-size: 1.75rem;
        font-weight: 800;
        margin: 0;
        color: var(--text-primary);
    }

    .mock-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 20px 28px 28px;
        max-width: 820px;
    }

    .mock-card {
        background: rgba(29, 31, 39, 0.72);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 20px;
        margin-bottom: 14px;
    }

    .mock-hero {
        background: linear-gradient(135deg, #7b61ff, #528dff);
        border-radius: 20px;
        padding: 28px 24px;
        text-align: center;
        color: #fff;
        margin-bottom: 18px;
    }

    .mock-hero h3 {
        margin: 12px 0 6px;
        font-size: 20px;
        font-weight: 800;
    }

    .mock-hero p { margin: 0; opacity: 0.85; font-size: 14px; }

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

    .mock-exam-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
        margin: 12px 0;
    }

    .mock-exam-chip {
        padding: 14px 12px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(11, 14, 21, 0.5);
        color: var(--text-primary);
        cursor: pointer;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s;
    }

    .mock-exam-chip:hover, .mock-exam-chip.selected {
        border-color: rgba(175, 198, 255, 0.45);
        background: rgba(175, 198, 255, 0.1);
    }

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

    .mock-exam-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
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

<div id="appMockTestView" class="mock-shell hidden">
    <div class="mock-topbar">
        <p class="dash-greeting">Full-length practice tests</p>
        <h2>Mock Test</h2>
    </div>

    <div class="mock-scroll">
        <div id="mockHomeView">
            <div class="mock-hero">
                <span class="material-symbols-outlined" style="font-size:48px">assignment</span>
                <h3 id="mockHeroTitle">AI Mock Test</h3>
                <p id="mockHeroSub">Select your exam and start a timed full mock</p>
            </div>

            <div class="mock-card">
                <h3 style="font-size:15px;font-weight:700;margin:0 0 10px;color:var(--text-primary)">Select Exam</h3>
                <div class="mock-exam-grid" id="mockExamGrid"></div>
            </div>

            <div class="mock-card">
                <h3 style="font-size:15px;font-weight:700;margin:0 0 10px;color:var(--text-primary)">Test Settings</h3>
                <label style="font-size:12px;color:var(--text-secondary)">Questions</label>
                <select id="mockQuestionCount" class="mock-field">
                    <option value="10">10 Questions</option>
                    <option value="20">20 Questions</option>
                    <option value="30" selected>30 Questions</option>
                    <option value="50">50 Questions</option>
                </select>
                <label style="font-size:12px;color:var(--text-secondary)">Duration (minutes)</label>
                <select id="mockDuration" class="mock-field">
                    <option value="15">15 min</option>
                    <option value="30">30 min</option>
                    <option value="45" selected>45 min</option>
                    <option value="60">60 min</option>
                    <option value="90">90 min</option>
                </select>
                <label style="font-size:12px;color:var(--text-secondary)">Subject (optional)</label>
                <input type="text" id="mockSubject" class="mock-field" placeholder="e.g. Physics, All subjects">
            </div>

            <div class="mock-card">
                <div class="mock-info-row">
                    <span class="material-symbols-outlined">quiz</span>
                    <div><strong>Real PYQ + AI mix</strong><br><span style="font-size:12px;color:var(--text-secondary)">Previous year questions when available</span></div>
                </div>
                <div class="mock-info-row">
                    <span class="material-symbols-outlined">timer</span>
                    <div><strong>Timed exam</strong><br><span style="font-size:12px;color:var(--text-secondary)">Auto-submit when time runs out</span></div>
                </div>
                <div class="mock-info-row">
                    <span class="material-symbols-outlined">analytics</span>
                    <div><strong>Instant analysis</strong><br><span style="font-size:12px;color:var(--text-secondary)">Score, accuracy & review</span></div>
                </div>
            </div>

            <button type="button" class="mock-btn" id="mockStartBtn">Start Mock Test</button>

            <div class="mock-card" style="margin-top:18px">
                <h3 style="font-size:15px;font-weight:700;margin:0 0 12px;color:var(--text-primary)">Recent Mock Tests</h3>
                <div id="mockHistoryList"><p style="font-size:13px;color:var(--text-secondary)">Loading history...</p></div>
            </div>
        </div>

        <div id="mockLoadingView" style="display:none">
            <div class="mock-loading">
                <span class="material-symbols-outlined" style="font-size:40px;animation:spin 1s linear infinite">progress_activity</span>
                <p style="margin-top:14px;font-weight:600;color:var(--text-primary)">Generating your mock test...</p>
                <p style="font-size:13px">This may take up to a minute</p>
            </div>
        </div>

        <div id="mockExamView" style="display:none">
            <div class="mock-exam-header">
                <button type="button" class="mock-btn mock-btn-outline" id="mockExitExam">← Exit</button>
                <div class="mock-timer" id="mockExamTimer">45:00</div>
            </div>
            <div class="mock-q-nav" id="mockQNav"></div>
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

        <div id="mockResultsView" style="display:none">
            <div class="mock-card" style="text-align:center">
                <div class="mock-score-ring" id="mockScoreRing">0%</div>
                <h3 style="margin:0 0 6px;color:var(--text-primary)">Test Completed!</h3>
                <p id="mockResultSummary" style="font-size:14px;color:var(--text-secondary);margin:0"></p>
            </div>
            <div class="mock-card" id="mockResultDetails"></div>
            <button type="button" class="mock-btn" id="mockBackHomeBtn">Take Another Mock Test</button>
        </div>
    </div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

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
        const res = await fetch('/api/exams' + path, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            ...options,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Request failed');
        return data;
    }

    function showView(name) {
        ['mockHomeView', 'mockLoadingView', 'mockExamView', 'mockResultsView'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = id === name ? 'block' : 'none';
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

    async function loadExams() {
        try {
            const res = await mockApi('/');
            state.exams = res.data || [];
            const grid = document.getElementById('mockExamGrid');
            if (!grid) return;

            if (!state.exams.length) {
                grid.innerHTML = '<p style="grid-column:1/-1;font-size:13px;color:var(--text-secondary)">No exams configured yet.</p>';
                return;
            }

            grid.innerHTML = state.exams.map(exam => `
                <button type="button" class="mock-exam-chip ${state.selectedExamId === exam.id ? 'selected' : ''}" data-id="${exam.id}">
                    ${exam.name || exam.slug}
                </button>
            `).join('');

            grid.querySelectorAll('.mock-exam-chip').forEach(btn => {
                btn.addEventListener('click', () => {
                    state.selectedExamId = parseInt(btn.dataset.id, 10);
                    const exam = state.exams.find(e => e.id === state.selectedExamId);
                    if (exam) {
                        document.getElementById('mockHeroTitle').textContent = exam.name + ' — Mock Test';
                        document.getElementById('mockHeroSub').textContent =
                            (state.selectedExamId ? '' : '') + 'Timed full-length practice';
                    }
                    loadExams();
                });
            });

            if (!state.selectedExamId && state.exams[0]) {
                state.selectedExamId = state.exams[0].id;
                document.getElementById('mockHeroTitle').textContent = state.exams[0].name + ' — Mock Test';
                loadExams();
            }
        } catch (e) {
            document.getElementById('mockExamGrid').innerHTML =
                '<p style="color:var(--text-secondary);font-size:13px">Could not load exams.</p>';
        }
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
            const subject = document.getElementById('mockSubject').value.trim();

            const gen = await mockApi('/mock-test/generate', {
                method: 'POST',
                body: JSON.stringify({
                    exam_id: state.selectedExamId,
                    question_count: qCount,
                    duration_minutes: duration,
                    subject: subject || null,
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
