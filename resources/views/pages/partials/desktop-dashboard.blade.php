<style>
    .app-nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid transparent;
        color: var(--text-secondary);
        background: transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: left;
    }

    .app-nav-item:hover {
        background: var(--bg-hover);
        color: var(--text-primary);
    }

    .app-nav-item.active {
        background: linear-gradient(135deg, rgba(175, 198, 255, 0.16), rgba(221, 184, 255, 0.1));
        border-color: rgba(175, 198, 255, 0.22);
        color: var(--text-primary);
    }

    .app-nav-item .material-symbols-outlined {
        font-size: 20px;
        flex-shrink: 0;
    }

    .sidebar-chat-tools { display: none; }
    body.app-view-chat .sidebar-chat-tools { display: block; }
    body.app-view-chat .sidebar-dash-tools { display: none; }

    .dash-shell {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        overflow: hidden;
    }

    .dash-topbar {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 28px 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .dash-topbar h2 {
        font-family: Manrope, sans-serif;
        font-size: 1.75rem;
        font-weight: 800;
        margin: 0;
        color: var(--text-primary);
    }

    .dash-topbar-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .dash-search {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        background: rgba(29, 31, 39, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.08);
        min-width: 220px;
    }

    .dash-search input {
        background: transparent;
        border: none;
        outline: none;
        color: var(--text-primary);
        font-size: 13px;
        width: 100%;
    }

    .dash-icon-btn {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(29, 31, 39, 0.8);
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: relative;
    }

    .dash-icon-btn .dot {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #f87171;
    }

    .dash-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 20px 28px 28px;
    }

    .dash-grid {
        display: grid;
        grid-template-columns: 1.4fr 1fr 0.9fr;
        grid-template-rows: auto auto;
        gap: 18px;
        max-width: 1400px;
    }

    @media (max-width: 1200px) {
        .dash-grid { grid-template-columns: 1fr 1fr; }
        .dash-leaderboard { grid-column: span 2; }
    }

    @media (max-width: 768px) {
        .dash-grid { grid-template-columns: 1fr; }
        .dash-leaderboard { grid-column: span 1; }
        .dash-search { display: none; }
    }

    .dash-card {
        background: rgba(29, 31, 39, 0.72);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 22px;
        position: relative;
        overflow: hidden;
    }

    .dash-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(175, 198, 255, 0.06), transparent 55%);
        pointer-events: none;
    }

    .dash-card-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 16px;
    }

    .dash-accuracy {
        grid-row: span 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 16px 18px;
    }

    .dash-accuracy .dash-card-title {
        margin-bottom: 8px;
        align-self: flex-start;
        width: 100%;
    }

    .accuracy-rings {
        position: relative;
        width: 148px;
        height: 148px;
        margin: 0 auto 10px;
    }

    .accuracy-rings svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }

    .accuracy-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .accuracy-center .pct {
        font-family: Manrope, sans-serif;
        font-size: 1.75rem;
        font-weight: 800;
        background: linear-gradient(135deg, #afc6ff, #ddb8ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1;
    }

    .accuracy-center .lbl {
        font-size: 10px;
        color: var(--text-secondary);
        margin-top: 2px;
    }

    .accuracy-legend {
        display: flex;
        gap: 14px;
        font-size: 11px;
        color: var(--text-secondary);
    }

    .accuracy-legend span {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .accuracy-legend i {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }

    .dash-streak-card {
        text-align: center;
        padding: 28px 22px;
    }

    .dash-streak-fire {
        font-size: 2.5rem;
        line-height: 1;
        margin-bottom: 8px;
    }

    .dash-streak-num {
        font-family: Manrope, sans-serif;
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        color: var(--text-primary);
    }

    .dash-streak-sub {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 6px;
    }

    .dash-level-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .dash-level-row strong {
        font-family: Manrope, sans-serif;
        font-size: 1.1rem;
    }

    .dash-xp-bar {
        height: 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.06);
        overflow: hidden;
    }

    .dash-xp-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #528dff, #ddb8ff);
        transition: width 0.6s ease;
    }

    .dash-radar-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 130px;
        padding: 4px 0;
    }

    .dash-radar-wrap canvas {
        max-width: 100%;
        height: auto;
    }

    .dash-weakness {
        padding: 16px 18px;
    }

    .dash-weakness .dash-card-title {
        margin-bottom: 6px;
    }

    .dash-revision-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .dash-revision-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dash-revision-item:hover {
        border-color: rgba(175, 198, 255, 0.25);
        background: rgba(175, 198, 255, 0.06);
    }

    .dash-revision-item.done {
        opacity: 0.65;
    }

    .dash-revision-day {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: rgba(175, 198, 255, 0.12);
        color: #afc6ff;
        font-size: 12px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .dash-revision-item.done .dash-revision-day {
        background: rgba(74, 222, 128, 0.15);
        color: #4ade80;
    }

    .dash-revision-meta {
        flex: 1;
        min-width: 0;
    }

    .dash-revision-meta .topic {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dash-revision-meta .subject {
        font-size: 12px;
        color: var(--text-secondary);
    }

    .dash-leaderboard {
        grid-row: span 2;
    }

    .dash-lb-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .dash-lb-item:last-child { border-bottom: none; }

    .dash-lb-rank {
        width: 24px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-secondary);
        text-align: center;
    }

    .dash-lb-rank.top { color: #afc6ff; }

    .dash-lb-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #528dff, #ddb8ff);
        color: #002d6c;
        font-size: 13px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .dash-lb-name {
        flex: 1;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dash-lb-score {
        font-family: Manrope, sans-serif;
        font-size: 14px;
        font-weight: 700;
        color: #afc6ff;
    }

    .dash-quick-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 18px;
        max-width: 1400px;
    }

    @media (max-width: 900px) {
        .dash-quick-row { grid-template-columns: repeat(2, 1fr); }
    }

    .dash-quick-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 16px 12px;
        border-radius: 16px;
        background: rgba(29, 31, 39, 0.72);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: var(--text-primary);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dash-quick-btn:hover {
        border-color: rgba(175, 198, 255, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(175, 198, 255, 0.1);
    }

    .dash-quick-btn .material-symbols-outlined {
        font-size: 24px;
        color: #afc6ff;
    }

    .dash-quick-btn span:last-child {
        font-size: 12px;
        font-weight: 600;
    }

    .dash-greeting {
        font-size: 14px;
        color: var(--text-secondary);
        margin: 0 0 4px;
    }

    .dash-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 200px;
        color: var(--text-secondary);
        gap: 10px;
    }

    #appChatView.hidden { display: none !important; }
    #appDashboardView.hidden { display: none !important; }
</style>

<div id="appDashboardView" class="dash-shell">
    <div class="dash-topbar">
        <div>
            <p class="dash-greeting" id="dashGreeting">Welcome back</p>
            <h2 id="dashTitle">Dashboard</h2>
        </div>
        <div class="dash-topbar-actions">
            <div class="dash-search">
                <span class="material-symbols-outlined text-[18px]" style="color:#8b92a8">search</span>
                <input type="text" placeholder="Search topics, chats..." id="dashSearchInput" onkeydown="if(event.key==='Enter'){window.switchAppView('chat');}">
            </div>
            <button type="button" class="dash-icon-btn" title="Notifications">
                <span class="material-symbols-outlined text-[20px]">notifications</span>
                <span class="dot"></span>
            </button>
            <button type="button" class="dash-icon-btn" onclick="toggleUserMenu()" title="Profile">
                <div class="avatar avatar-user" style="width:28px;height:28px;font-size:12px;">
                    {{ auth()->user()->name ? substr(auth()->user()->name, 0, 1) : 'U' }}
                </div>
            </button>
        </div>
    </div>

    <div class="dash-scroll scrollbar-thin">
        <div class="dash-quick-row">
            <button type="button" class="dash-quick-btn" onclick="window.switchAppView('chat'); createNewChat();">
                <span class="material-symbols-outlined">smart_toy</span>
                <span>AI Tutor</span>
            </button>
            <button type="button" class="dash-quick-btn" onclick="document.getElementById('dashRevisionSection')?.scrollIntoView({behavior:'smooth'})">
                <span class="material-symbols-outlined">menu_book</span>
                <span>Revision</span>
            </button>
            <button type="button" class="dash-quick-btn" onclick="window.open('https://blinkstudy.in/pricing','_blank')">
                <span class="material-symbols-outlined">workspace_premium</span>
                <span>Upgrade</span>
            </button>
        </div>

        <div id="dashContent">
            <div class="dash-loading">
                <span class="material-symbols-outlined animate-spin">progress_activity</span>
                Loading your dashboard...
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const ringColors = ['#528dff', '#afc6ff', '#ddb8ff'];

    function renderDashboard(data) {
        const container = document.getElementById('dashContent');
        if (!container || !data.success) return;

        document.getElementById('dashGreeting').textContent = 'Hi, ' + (data.user.first_name || 'Student') + '!';
        const accuracy = data.quiz_accuracy || data.accuracy || 0;
        const xpPct = data.level_progress?.progress_percent ?? 0;
        const xpNeeded = data.level_progress?.xp_needed ?? 0;

        const weakTopics = data.weak_topics || [];
        const planDays = (data.revision_plan?.days || []).slice(0, 5);
        const leaderboard = data.leaderboard || [];

        const revisionHtml = planDays.map(day => `
            <div class="dash-revision-item ${day.completed ? 'done' : ''}" onclick="window.switchAppView('chat')">
                <div class="dash-revision-day">${day.completed ? '✓' : 'D' + day.day}</div>
                <div class="dash-revision-meta">
                    <div class="topic">${escapeHtml(day.topic)}</div>
                    <div class="subject">${escapeHtml(day.subject)} · ${day.action.replace('_', ' ')}</div>
                </div>
            </div>
        `).join('') || '<p style="color:var(--text-secondary);font-size:13px;">Take a quiz to unlock your revision plan.</p>';

        const lbHtml = leaderboard.length ? leaderboard.map(entry => `
            <div class="dash-lb-item">
                <div class="dash-lb-rank ${entry.rank <= 3 ? 'top' : ''}">${entry.rank}</div>
                <div class="dash-lb-avatar">${(entry.name || '?').charAt(0).toUpperCase()}</div>
                <div class="dash-lb-name">${escapeHtml(entry.name || 'Student')}</div>
                <div class="dash-lb-score">${entry.total_score ?? entry.wins ?? 0}</div>
            </div>
        `).join('') : '<p style="color:var(--text-secondary);font-size:13px;padding:12px 0;">Play battles to appear on the leaderboard.</p>';

        container.innerHTML = `
            <div class="dash-grid">
                <div class="dash-card dash-accuracy">
                    <div class="dash-card-title">Accuracy</div>
                    <div class="accuracy-rings">
                        <svg viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="8"/>
                            <circle cx="60" cy="60" r="52" fill="none" stroke="${ringColors[0]}" stroke-width="8"
                                stroke-dasharray="${(accuracy/100)*326.7} 326.7" stroke-linecap="round"
                                style="filter:drop-shadow(0 0 8px rgba(82,141,255,0.5))"/>
                            <circle cx="60" cy="60" r="40" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="6"/>
                            <circle cx="60" cy="60" r="40" fill="none" stroke="${ringColors[1]}" stroke-width="6"
                                stroke-dasharray="${(data.strength_score/100)*251.2} 251.2" stroke-linecap="round"
                                style="filter:drop-shadow(0 0 6px rgba(175,198,255,0.4))"/>
                        </svg>
                        <div class="accuracy-center">
                            <div class="pct">${accuracy}%</div>
                            <div class="lbl">Quiz accuracy</div>
                        </div>
                    </div>
                    <div class="accuracy-legend">
                        <span><i style="background:${ringColors[0]}"></i> Quizzes</span>
                        <span><i style="background:${ringColors[1]}"></i> Strength</span>
                    </div>
                </div>

                <div class="dash-card dash-streak-card">
                    <div class="dash-card-title">Daily Streak</div>
                    <div class="dash-streak-fire">🔥</div>
                    <div class="dash-streak-num">${data.streak} DAY STREAK</div>
                    <div class="dash-streak-sub">${data.daily_progress}% of today's plan done</div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-title">Level ${data.level}</div>
                    <div class="dash-level-row">
                        <strong>${data.xp.toLocaleString()} XP</strong>
                        <span style="font-size:12px;color:var(--text-secondary)">${xpNeeded > 0 ? xpNeeded + ' to next' : 'Max level'}</span>
                    </div>
                    <div class="dash-xp-bar"><div class="dash-xp-fill" style="width:${Math.min(100, xpPct)}%"></div></div>
                    <p style="font-size:12px;color:var(--text-secondary);margin:12px 0 0;">Target: ${escapeHtml(data.user.target_exam || 'Your exam')}</p>
                </div>

                <div class="dash-card dash-weakness">
                    <div class="dash-card-title">Neural Weakness Map</div>
                    <div class="dash-radar-wrap"><canvas id="weakRadar" width="200" height="160"></canvas></div>
                </div>

                <div class="dash-card" id="dashRevisionSection">
                    <div class="dash-card-title">Today's Revision Plan</div>
                    <div class="dash-revision-list">${revisionHtml}</div>
                </div>

                <div class="dash-card dash-leaderboard">
                    <div class="dash-card-title">Leaderboard</div>
                    ${lbHtml}
                </div>
            </div>
        `;

        drawRadar(weakTopics);
    }

    function drawRadar(topics) {
        const canvas = document.getElementById('weakRadar');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const w = canvas.width, h = canvas.height;
        const cx = w / 2, cy = h / 2 + 4;
        const radius = Math.min(w, h) * 0.28;

        ctx.clearRect(0, 0, w, h);

        const labels = topics.length ? topics.slice(0, 5).map(t => ({
            label: (t.topic || t.subject || 'Topic').split(' ').slice(0, 2).join(' '),
            value: Math.max(0.2, ((t.success_rate ?? (t.accuracy != null ? t.accuracy * 100 : 50)) / 100)),
        })) : [
            { label: 'Physics', value: 0.7 },
            { label: 'Chemistry', value: 0.5 },
            { label: 'Maths', value: 0.65 },
            { label: 'Biology', value: 0.55 },
            { label: 'Mixed', value: 0.6 },
        ];

        const n = labels.length;
        const angleStep = (Math.PI * 2) / n;

        for (let ring = 1; ring <= 4; ring++) {
            ctx.beginPath();
            for (let i = 0; i <= n; i++) {
                const a = i * angleStep - Math.PI / 2;
                const r = radius * (ring / 4);
                const x = cx + Math.cos(a) * r;
                const y = cy + Math.sin(a) * r;
                i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
            }
            ctx.strokeStyle = 'rgba(255,255,255,0.08)';
            ctx.stroke();
        }

        ctx.beginPath();
        labels.forEach((item, i) => {
            const a = i * angleStep - Math.PI / 2;
            const r = radius * item.value;
            const x = cx + Math.cos(a) * r;
            const y = cy + Math.sin(a) * r;
            i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        });
        ctx.closePath();
        const grad = ctx.createLinearGradient(cx - radius, cy - radius, cx + radius, cy + radius);
        grad.addColorStop(0, 'rgba(82, 141, 255, 0.45)');
        grad.addColorStop(1, 'rgba(221, 184, 255, 0.35)');
        ctx.fillStyle = grad;
        ctx.fill();
        ctx.strokeStyle = '#afc6ff';
        ctx.lineWidth = 2;
        ctx.stroke();

        ctx.fillStyle = '#c2c6d6';
        ctx.font = '10px Inter, sans-serif';
        ctx.textAlign = 'center';
        labels.forEach((item, i) => {
            const a = i * angleStep - Math.PI / 2;
            const x = cx + Math.cos(a) * (radius + 16);
            const y = cy + Math.sin(a) * (radius + 16);
            ctx.fillText(item.label, x, y + 4);
        });
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    window.loadWebDashboard = async function () {
        try {
            const res = await fetch('/api/web/dashboard', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            renderDashboard(data);
        } catch (e) {
            const container = document.getElementById('dashContent');
            if (container) {
                container.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:40px;">Could not load dashboard. Refresh to try again.</p>';
            }
        }
    };

    window.switchAppView = function (view) {
        const dash = document.getElementById('appDashboardView');
        const chat = document.getElementById('appChatView');
        document.querySelectorAll('.app-nav-item').forEach(el => {
            el.classList.toggle('active', el.dataset.view === view);
        });

        document.body.classList.remove('app-view-dashboard', 'app-view-chat');
        document.body.classList.add(view === 'chat' ? 'app-view-chat' : 'app-view-dashboard');

        if (view === 'chat') {
            dash?.classList.add('hidden');
            chat?.classList.remove('hidden');
        } else {
            chat?.classList.add('hidden');
            dash?.classList.remove('hidden');
            if (view === 'dashboard') {
                window.loadWebDashboard();
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.body.classList.add('app-view-dashboard');
        window.loadWebDashboard();
    });
})();
</script>
