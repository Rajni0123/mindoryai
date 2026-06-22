<style>
    #appBattleView.hidden { display: none !important; }

    .battle-shell {
        flex: 1;
        width: 100%;
        display: flex;
        flex-direction: column;
        min-width: 0;
        overflow: hidden;
    }

    .battle-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 20px 28px 28px;
        width: 100%;
    }

    .battle-page {
        max-width: 1400px;
        width: 100%;
        margin: 0 auto;
    }

    .battle-friends-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        align-items: start;
    }

    .battle-full-span { grid-column: 1 / -1; }

    @media (max-width: 900px) {
        .battle-friends-grid { grid-template-columns: 1fr; }
    }

    .battle-play-grid {
        display: grid;
        grid-template-columns: 1.25fr 0.75fr;
        gap: 18px;
        align-items: start;
    }

    @media (max-width: 900px) {
        .battle-play-grid { grid-template-columns: 1fr; }
    }

    .battle-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding-bottom: 4px;
    }

    .battle-tab {
        flex: 1;
        padding: 10px 8px;
        background: transparent;
        border: none;
        color: var(--text-secondary);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
    }

    .battle-tab.active {
        color: #afc6ff;
        border-bottom-color: #afc6ff;
    }

    .battle-card {
        background: rgba(29, 31, 39, 0.72);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 20px;
        margin-bottom: 14px;
    }

    .battle-card h3 {
        font-size: 15px;
        font-weight: 700;
        margin: 0 0 6px;
        color: var(--text-primary);
    }

    .battle-card p.sub {
        font-size: 12px;
        color: var(--text-secondary);
        margin: 0 0 14px;
    }

    .battle-input {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        background: rgba(11, 14, 21, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: var(--text-primary);
        font-size: 14px;
        margin-bottom: 10px;
    }

    .battle-row {
        display: flex;
        gap: 10px;
        align-items: stretch;
    }

    .battle-row .battle-input { margin-bottom: 0; flex: 1; }

    .battle-btn {
        padding: 12px 18px;
        border-radius: 12px;
        border: none;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }

    .battle-btn-primary {
        background: linear-gradient(135deg, #7b61ff, #528dff);
        color: #fff;
    }

    .battle-btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }

    .battle-btn-outline {
        background: transparent;
        border: 1px solid rgba(175, 198, 255, 0.35);
        color: #afc6ff;
    }

    .battle-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    .battle-live-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        border-radius: 16px;
        background: rgba(29, 31, 39, 0.72);
        border: 1px solid rgba(255, 255, 255, 0.08);
        margin-bottom: 10px;
        cursor: pointer;
        transition: border-color 0.2s;
    }

    .battle-live-row:hover { border-color: rgba(175, 198, 255, 0.3); }

    .battle-avatars {
        display: flex;
        width: 52px;
        flex-shrink: 0;
    }

    .battle-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        border: 2px solid;
    }

    .battle-avatar.a { background: rgba(91, 140, 255, 0.2); border-color: #5b8cff; color: #5b8cff; margin-right: -10px; z-index: 1; }
    .battle-avatar.b { background: rgba(255, 159, 90, 0.2); border-color: #ff9f5a; color: #ff9f5a; }

    .battle-live-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 700;
        color: #ff3b30;
    }

    .battle-live-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #ff3b30;
    }

    .battle-stats-card {
        background: linear-gradient(135deg, #7b61ff, #705cf6);
        border-radius: 20px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 8px;
    }

    .battle-stat { flex: 1; text-align: center; color: #fff; }
    .battle-stat strong { display: block; font-size: 20px; font-weight: 800; }
    .battle-stat span { font-size: 10px; opacity: 0.85; }

    .battle-code {
        font-size: 34px;
        font-weight: 900;
        letter-spacing: 6px;
        text-align: center;
        color: var(--text-primary);
        margin: 8px 0;
    }

    .battle-player {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        border-radius: 14px;
        background: rgba(11, 14, 21, 0.5);
        margin-bottom: 8px;
    }

    .battle-timer {
        font-size: 28px;
        font-weight: 800;
        color: #afc6ff;
        text-align: center;
        margin: 12px 0;
    }

    .battle-option {
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
        transition: all 0.15s;
    }

    .battle-option:hover:not(:disabled) {
        border-color: rgba(175, 198, 255, 0.4);
        background: rgba(175, 198, 255, 0.08);
    }

    .battle-option:disabled { cursor: default; opacity: 0.7; }
    .battle-option.selected { border-color: #528dff; background: rgba(82, 141, 255, 0.15); }

    .battle-lb-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        font-size: 13px;
    }

    .battle-toast {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(29, 31, 39, 0.95);
        border: 1px solid rgba(175, 198, 255, 0.3);
        color: var(--text-primary);
        padding: 12px 20px;
        border-radius: 12px;
        font-size: 13px;
        z-index: 9999;
        display: none;
    }

    .battle-toast.show { display: block; }

    body.app-view-battle .sidebar-chat-tools { display: none; }
    body.app-view-mock_test .sidebar-chat-tools { display: none; }
</style>

<div id="appBattleView" class="battle-shell hidden">
    <div class="dash-topbar">
        <div>
            <p class="dash-greeting">Compete with friends</p>
            <h2>Study Battle</h2>
        </div>
        <div class="dash-topbar-actions">
            <div class="dash-search">
                <span class="material-symbols-outlined text-[18px] text-[var(--text-secondary)]">search</span>
                <input type="text" placeholder="Search battles, topics..." onkeydown="if(event.key==='Enter'){window.switchAppView('chat');}">
            </div>
            <button type="button" class="dash-icon-btn" title="Back to Dashboard" onclick="window.switchAppView('dashboard')">
                <span class="material-symbols-outlined text-[20px]">dashboard</span>
            </button>
        </div>
    </div>

    <div class="battle-scroll">
        <div class="battle-page">
        <div id="battleHomeView">
            <div class="battle-tabs">
                <button type="button" class="battle-tab active" data-btab="friends">Friends</button>
                <button type="button" class="battle-tab" data-btab="topper">Topper</button>
                <button type="button" class="battle-tab" data-btab="live">Live</button>
            </div>

            <div id="battlePanelFriends" class="battle-friends-grid">
                <div class="battle-card">
                    <h3>Start a Battle</h3>
                    <p class="sub">Create a room and share the code with your friend</p>
                    <input type="text" id="battleTopic" class="battle-input" placeholder="Topic (e.g. Laws of Motion)" value="">
                    <input type="text" id="battleSubject" class="battle-input" placeholder="Subject (e.g. Physics)">
                    <select id="battleDifficulty" class="battle-input">
                        <option value="easy">Easy</option>
                        <option value="medium" selected>Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                    <button type="button" class="battle-btn battle-btn-primary" style="width:100%" id="battleCreateBtn">Start Battle</button>
                </div>
                <div class="battle-card">
                    <h3>Join with Code</h3>
                    <p class="sub">Enter room code shared by your friend</p>
                    <div class="battle-row">
                        <input type="text" id="battleJoinCode" class="battle-input" placeholder="ROOM CODE" maxlength="8" style="text-transform:uppercase">
                        <button type="button" class="battle-btn battle-btn-primary" id="battleJoinBtn">Join</button>
                    </div>
                </div>
                <div id="battleLivePreview" class="battle-full-span"></div>
                <div id="battleStatsBlock" class="battle-full-span"></div>
            </div>

            <div id="battlePanelTopper" style="display:none">
                <div class="battle-card" style="text-align:center">
                    <span class="material-symbols-outlined" style="font-size:40px;color:#ffd700">emoji_events</span>
                    <h3 style="margin-top:12px">Battle Leaderboard</h3>
                    <p class="sub">See where you rank among friends & toppers</p>
                    <div id="battleLeaderboardList"></div>
                </div>
            </div>

            <div id="battlePanelLive" style="display:none">
                <div id="battleLiveFull"></div>
            </div>
        </div>

        <div id="battleLobbyView" style="display:none">
            <button type="button" class="battle-btn battle-btn-outline" id="battleLeaveLobby" style="margin-bottom:14px">← Back</button>
            <div class="battle-card" style="text-align:center">
                <p class="sub" style="margin:0">Room Code</p>
                <div class="battle-code" id="battleLobbyCode">----</div>
                <button type="button" class="battle-btn battle-btn-outline" id="battleCopyInvite">Copy Invite</button>
            </div>
            <p id="battleLobbyStatus" style="font-size:14px;color:var(--text-secondary);margin:14px 0">Waiting for friend to join...</p>
            <div id="battleLobbyPlayers"></div>
            <div style="display:flex;gap:10px;margin-top:16px">
                <button type="button" class="battle-btn battle-btn-outline" style="flex:1" id="battleReadyBtn">I'm Ready</button>
                <button type="button" class="battle-btn battle-btn-primary" style="flex:1;display:none" id="battleStartBtn">Start Battle</button>
            </div>
        </div>

        <div id="battlePlayView" style="display:none" class="battle-play-grid">
            <div class="battle-card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <span id="battleQProgress" style="font-size:12px;color:var(--text-secondary)">Q 1/10</span>
                    <span id="battleMyScore" style="font-size:12px;color:#afc6ff">Score: 0</span>
                </div>
                <div class="battle-timer" id="battleTimer">15</div>
                <p id="battleQuestionText" style="font-size:16px;font-weight:600;color:var(--text-primary);margin:0 0 16px"></p>
                <div id="battleOptions"></div>
            </div>
            <div class="battle-card">
                <h3>Live Scores</h3>
                <div id="battleLiveScores"></div>
            </div>
        </div>

        <div id="battleResultsView" style="display:none">
            <div class="battle-card" style="text-align:center">
                <span class="material-symbols-outlined" style="font-size:48px;color:#ffd700">military_tech</span>
                <h3 id="battleResultTitle" style="margin-top:10px">Battle Complete!</h3>
                <p id="battleResultSummary" class="sub"></p>
            </div>
            <div class="battle-card">
                <h3>Final Standings</h3>
                <div id="battleFinalLb"></div>
            </div>
            <button type="button" class="battle-btn battle-btn-primary" style="width:100%" id="battleBackHome">Back to Battles</button>
        </div>
        </div>
    </div>
</div>

<div id="battleToast" class="battle-toast"></div>

<script>
(function () {
    const state = {
        tab: 'friends',
        roomId: null,
        roomCode: '',
        isHost: false,
        pollTimer: null,
        currentQuestionIndex: null,
    };

    function toast(msg) {
        const el = document.getElementById('battleToast');
        if (!el) return;
        el.textContent = msg;
        el.classList.add('show');
        setTimeout(() => el.classList.remove('show'), 3000);
    }

    async function battleApi(path, options = {}) {
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

        return fetcher('/api/study-battle' + path, options);
    }

    function firstName(name) {
        if (!name) return 'Player';
        return String(name).trim().split(' ')[0];
    }

    function showBattleView(name) {
        const displays = {
            battleHomeView: 'block',
            battleLobbyView: 'block',
            battlePlayView: 'grid',
            battleResultsView: 'block',
        };
        ['battleHomeView', 'battleLobbyView', 'battlePlayView', 'battleResultsView'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = id === name ? (displays[id] || 'block') : 'none';
        });
    }

    function stopPolling() {
        if (state.pollTimer) {
            clearInterval(state.pollTimer);
            state.pollTimer = null;
        }
    }

    function renderLiveRooms(rooms, targetId, compact) {
        const el = document.getElementById(targetId);
        if (!el) return;

        if (!rooms.length) {
            el.innerHTML = compact
                ? ''
                : '<div class="battle-card"><p class="sub" style="margin:0;text-align:center">No live battles right now. Start one!</p></div>';
            return;
        }

        const limit = compact ? 3 : rooms.length;
        const html = (compact ? '<h3 style="font-size:16px;font-weight:700;margin:18px 0 12px;color:var(--text-primary)">Live Battles</h3>' : '')
            + rooms.slice(0, limit).map(r => {
                const host = firstName(r.host_name);
                const players = r.current_players || 1;
                const opponent = players > 1 ? 'Friend' : 'Waiting';
                const topic = r.topic || r.title || 'Quiz';
                return `
                <div class="battle-live-row" data-code="${r.room_code}">
                    <div class="battle-avatars">
                        <div class="battle-avatar a">${host.charAt(0)}</div>
                        <div class="battle-avatar b">${opponent.charAt(0)}</div>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;font-size:14px">${host} VS ${opponent}</div>
                        <div style="font-size:12px;color:var(--text-secondary)">${topic} · ${r.question_count || 10} Qs</div>
                    </div>
                    <div style="text-align:right">
                        <div class="battle-live-badge"><span class="battle-live-dot"></span> Live</div>
                    </div>
                </div>`;
            }).join('');

        el.innerHTML = html;
        el.querySelectorAll('.battle-live-row').forEach(row => {
            row.addEventListener('click', () => joinBattle(row.dataset.code));
        });
    }

    async function loadBattleHome() {
        try {
            const [roomsRes, historyRes] = await Promise.all([
                battleApi('/rooms'),
                battleApi('/history?limit=20'),
            ]);
            const rooms = roomsRes.data || [];
            renderLiveRooms(rooms, 'battleLivePreview', true);
            renderLiveRooms(rooms, 'battleLiveFull', false);

            const history = historyRes.data || [];
            let won = 0, xp = 0;
            history.forEach(h => {
                if (h.result === 'won') won++;
                xp += (h.xp_earned || h.xp || 0);
            });
            const total = history.length;
            const winRate = total > 0 ? Math.round((won / total) * 100) : 0;

            document.getElementById('battleStatsBlock').innerHTML = total > 0 ? `
                <h3 style="font-size:16px;font-weight:700;margin:18px 0 12px;color:var(--text-primary)">Your Stats</h3>
                <div class="battle-stats-card">
                    <div class="battle-stat"><strong>${won}</strong><span>Battles Won</span></div>
                    <div class="battle-stat"><strong>${winRate}%</strong><span>Win Rate</span></div>
                    <div class="battle-stat"><strong>${xp}</strong><span>XP Earned</span></div>
                </div>` : '';
        } catch (e) {
            console.warn('Battle home load failed', e);
        }
    }

    async function loadLeaderboard() {
        const el = document.getElementById('battleLeaderboardList');
        if (!el) return;
        try {
            const res = await battleApi('/leaderboard?period=weekly&limit=20');
            const list = res.data || [];
            if (!list.length) {
                el.innerHTML = '<p class="sub">Play battles to appear on the leaderboard.</p>';
                return;
            }
            el.innerHTML = list.map((e, i) => `
                <div class="battle-lb-item">
                    <span style="width:24px;font-weight:700;color:${i < 3 ? '#ffd700' : 'var(--text-secondary)'}">${e.rank || i + 1}</span>
                    <span style="flex:1;text-align:left">${e.name || 'Student'}</span>
                    <span style="font-weight:700;color:#afc6ff">${e.total_score ?? e.wins ?? 0}</span>
                </div>
            `).join('');
        } catch (e) {
            el.innerHTML = '<p class="sub">Could not load leaderboard.</p>';
        }
    }

    async function createBattle() {
        const topic = document.getElementById('battleTopic').value.trim();
        const subject = document.getElementById('battleSubject').value.trim();
        const difficulty = document.getElementById('battleDifficulty').value;
        if (!topic) {
            toast('Enter a topic');
            return;
        }
        const btn = document.getElementById('battleCreateBtn');
        btn.disabled = true;
        try {
            const res = await battleApi('/create', {
                method: 'POST',
                body: JSON.stringify({
                    topic,
                    subject: subject || null,
                    difficulty,
                    max_players: 2,
                    question_count: 10,
                }),
            });
            const d = res.data || {};
            enterLobby(d.room_id, d.room_code, true);
        } catch (e) {
            toast(e.message || 'Could not create battle');
        } finally {
            btn.disabled = false;
        }
    }

    async function joinBattle(code) {
        code = String(code || '').trim().toUpperCase();
        if (code.length < 4) {
            toast('Enter a valid room code');
            return;
        }
        try {
            const res = await battleApi('/join/' + encodeURIComponent(code), { method: 'POST' });
            const d = res.data || {};
            enterLobby(d.room_id, d.room_code || code, !!d.is_host);
        } catch (e) {
            toast(e.message || 'Could not join battle');
        }
    }

    function enterLobby(roomId, code, isHost) {
        state.roomId = roomId;
        state.roomCode = code;
        state.isHost = isHost;
        document.getElementById('battleLobbyCode').textContent = code;
        document.getElementById('battleStartBtn').style.display = isHost ? 'block' : 'none';
        showBattleView('battleLobbyView');
        startPolling();
    }

    function startPolling() {
        stopPolling();
        pollOnce();
        state.pollTimer = setInterval(pollOnce, 2000);
    }

    async function pollOnce() {
        if (!state.roomId) return;
        try {
            const res = await battleApi('/poll/' + state.roomId);
            const data = res.data || res;
            handlePoll(data);
        } catch (e) {
            console.warn('Poll failed', e);
        }
    }

    function handlePoll(data) {
        const status = data.room?.status || 'waiting';

        if (status === 'waiting') {
            const players = data.participants || [];
            document.getElementById('battleLobbyPlayers').innerHTML = players.map(p => `
                <div class="battle-player">
                    <div class="battle-avatar a">${firstName(p.name).charAt(0)}</div>
                    <div style="flex:1">
                        <div style="font-weight:600">${p.name || 'Player'}${p.is_host ? ' (Host)' : ''}</div>
                        <div style="font-size:12px;color:var(--text-secondary)">${p.status || 'joined'}</div>
                    </div>
                </div>
            `).join('');
            document.getElementById('battleLobbyStatus').textContent =
                players.length > 1 ? 'Friend joined — ready up!' : 'Waiting for friend to join...';
            document.getElementById('battleStartBtn').disabled = !data.can_start;
            return;
        }

        if (status === 'starting') {
            showBattleView('battlePlayView');
            document.getElementById('battleQuestionText').textContent = 'Battle starting in ' + (data.countdown_seconds ?? 3) + '...';
            document.getElementById('battleOptions').innerHTML = '';
            return;
        }

        if (status === 'in_progress') {
            showBattleView('battlePlayView');
            renderQuestion(data);
            return;
        }

        if (status === 'completed') {
            stopPolling();
            showBattleView('battleResultsView');
            renderResults(data);
        }
    }

    function renderQuestion(data) {
        const q = data.question;
        if (!q) return;

        const total = q.total || 10;
        const idx = (q.index ?? 0) + 1;
        document.getElementById('battleQProgress').textContent = `Q ${idx}/${total}`;
        document.getElementById('battleMyScore').textContent = `Score: ${data.my_score ?? 0}`;
        document.getElementById('battleQuestionText').textContent = q.question || '';

        const secs = Math.ceil((data.time_remaining_ms || 0) / 1000);
        document.getElementById('battleTimer').textContent = secs;

        const opts = q.options || {};
        const letters = ['A', 'B', 'C', 'D'];
        const answered = data.already_answered;

        document.getElementById('battleOptions').innerHTML = letters.map(letter => {
            const text = opts[letter] || opts[letter.toLowerCase()] || '';
            if (!text) return '';
            const sel = data.my_answer === letter ? 'selected' : '';
            return `<button type="button" class="battle-option ${sel}" data-letter="${letter}" ${answered ? 'disabled' : ''}>${letter}. ${text}</button>`;
        }).join('');

        if (!answered) {
            document.getElementById('battleOptions').querySelectorAll('.battle-option').forEach(btn => {
                btn.addEventListener('click', () => submitAnswer(q.index, btn.dataset.letter));
            });
        }

        const lb = data.leaderboard || [];
        document.getElementById('battleLiveScores').innerHTML = lb.map(e => `
            <div class="battle-lb-item">
                <span style="width:24px">#${e.rank}</span>
                <span style="flex:1">${e.name}</span>
                <span style="font-weight:700">${e.score}</span>
            </div>
        `).join('');
    }

    async function submitAnswer(questionIndex, letter) {
        try {
            await battleApi('/answer', {
                method: 'POST',
                body: JSON.stringify({
                    room_id: state.roomId,
                    question_index: questionIndex,
                    answer: letter,
                }),
            });
            pollOnce();
        } catch (e) {
            toast(e.message || 'Could not submit answer');
        }
    }

    function renderResults(data) {
        const rank = data.my_rank ?? '?';
        document.getElementById('battleResultTitle').textContent =
            rank === 1 ? 'You Won! 🏆' : 'Battle Complete!';
        document.getElementById('battleResultSummary').textContent =
            `Rank #${rank} · Score ${data.my_score ?? 0} · ${data.my_correct ?? 0} correct`;

        const lb = data.leaderboard || [];
        document.getElementById('battleFinalLb').innerHTML = lb.map(e => `
            <div class="battle-lb-item">
                <span style="width:24px;font-weight:700;color:${e.rank === 1 ? '#ffd700' : 'inherit'}">#${e.rank}</span>
                <span style="flex:1">${e.name}${e.is_winner ? ' 👑' : ''}</span>
                <span style="font-weight:700">${e.score}</span>
            </div>
        `).join('');
    }

    async function markReady() {
        try {
            await battleApi('/ready', { method: 'POST', body: JSON.stringify({ room_id: state.roomId }) });
            toast('Marked ready');
            pollOnce();
        } catch (e) {
            toast(e.message || 'Could not mark ready');
        }
    }

    async function startBattle() {
        try {
            await battleApi('/start', { method: 'POST', body: JSON.stringify({ room_id: state.roomId }) });
            toast('Battle starting!');
            pollOnce();
        } catch (e) {
            toast(e.message || 'Wait for your friend to join');
        }
    }

    async function leaveBattle() {
        stopPolling();
        if (state.roomId) {
            try {
                await battleApi('/leave', { method: 'POST', body: JSON.stringify({ room_id: state.roomId }) });
            } catch (_) {}
        }
        state.roomId = null;
        state.roomCode = '';
        showBattleView('battleHomeView');
        loadBattleHome();
    }

    function copyInvite() {
        const msg = `Join my BlinkStudy battle! 🎯\nRoom code: ${state.roomCode}\nOpen BlinkStudy → Battles → Join with code`;
        navigator.clipboard?.writeText(msg).then(() => toast('Invite copied!'));
    }

    function switchBattleTab(tab) {
        state.tab = tab;
        document.querySelectorAll('.battle-tab').forEach(t => {
            t.classList.toggle('active', t.dataset.btab === tab);
        });
        document.getElementById('battlePanelFriends').style.display = tab === 'friends' ? 'grid' : 'none';
        document.getElementById('battlePanelTopper').style.display = tab === 'topper' ? 'block' : 'none';
        document.getElementById('battlePanelLive').style.display = tab === 'live' ? 'block' : 'none';
        if (tab === 'topper') loadLeaderboard();
        if (tab === 'live') loadBattleHome();
    }

    document.querySelectorAll('.battle-tab').forEach(btn => {
        btn.addEventListener('click', () => switchBattleTab(btn.dataset.btab));
    });

    document.getElementById('battleCreateBtn')?.addEventListener('click', createBattle);
    document.getElementById('battleJoinBtn')?.addEventListener('click', () => joinBattle(document.getElementById('battleJoinCode').value));
    document.getElementById('battleReadyBtn')?.addEventListener('click', markReady);
    document.getElementById('battleStartBtn')?.addEventListener('click', startBattle);
    document.getElementById('battleLeaveLobby')?.addEventListener('click', leaveBattle);
    document.getElementById('battleBackHome')?.addEventListener('click', leaveBattle);
    document.getElementById('battleCopyInvite')?.addEventListener('click', copyInvite);

    window.loadWebBattle = function () {
        showBattleView('battleHomeView');
        loadBattleHome();
    };

    window.openBattleLobby = function (roomId, code, isHost) {
        if (roomId && code) enterLobby(roomId, code, !!isHost);
        else window.switchAppView('battle');
    };
})();
</script>
