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
    body:not(.app-view-chat) .sidebar-dash-tools { display: flex; }

    .dash-shell {
        flex: 1;
        width: 100%;
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

    .dash-streak-badge {
        font-size: 11px;
        color: #afc6ff;
        margin-top: 8px;
        font-weight: 600;
    }

    .dash-meta-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 18px;
        max-width: 1400px;
    }

    @media (max-width: 900px) {
        .dash-meta-row { grid-template-columns: 1fr; }
    }

    .dash-meta-card {
        padding: 18px 20px;
    }

    .dash-meta-card .dash-meta-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 6px;
        font-family: Manrope, sans-serif;
    }

    .dash-meta-card .dash-meta-desc {
        font-size: 12px;
        color: var(--text-secondary);
        margin: 0 0 12px;
        line-height: 1.45;
    }

    .dash-meta-card .dash-meta-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        font-size: 11px;
        color: var(--text-secondary);
    }

    .dash-meta-progress {
        height: 6px;
        border-radius: 999px;
        background: rgba(255,255,255,0.06);
        overflow: hidden;
        margin: 10px 0 8px;
    }

    .dash-meta-progress-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #528dff, #ddb8ff);
    }

    .dash-meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 999px;
        background: rgba(175,198,255,0.12);
        color: #afc6ff;
        font-size: 11px;
        font-weight: 600;
    }

    .dash-meta-chip.done {
        background: rgba(74,222,128,0.12);
        color: #4ade80;
    }

    .dash-empty-chart {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 120px;
        text-align: center;
        font-size: 12px;
        color: var(--text-secondary);
        padding: 12px;
    }

    .dash-lb-item.is-you {
        background: rgba(175,198,255,0.08);
        border-radius: 10px;
        margin: 0 -8px;
        padding: 10px 8px;
    }

    .dash-peer-line {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 10px;
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
        grid-template-columns: repeat(4, 1fr);
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
    #appBattleView.hidden { display: none !important; }
    #appMockTestView.hidden { display: none !important; }
</style>

<div id="appDashboardView" class="dash-shell">
    <div class="dash-topbar">
        <div>
            <p class="dash-greeting" id="dashGreeting">Welcome back</p>
            <h2 id="dashTitle">Dashboard</h2>
            <p class="dash-greeting" id="dashCoachNote" style="margin-top:6px;font-size:13px;"></p>
        </div>
        <div class="dash-topbar-actions">
            <div class="dash-search">
                <span class="material-symbols-outlined text-[18px]" style="color:#8b92a8">search</span>
                <input type="text" placeholder="Search topics, chats..." id="dashSearchInput" onkeydown="if(event.key==='Enter'){window.switchAppView('chat');}">
            </div>
            <button type="button" class="dash-icon-btn" id="dashNotifBtn" title="Notifications" style="display:none;">
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
        <div class="dash-quick-row" id="dashQuickRow"></div>

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
    let dashData = null;

    function renderQuickActions(actions) {
        const row = document.getElementById('dashQuickRow');
        if (!row) return;
        const items = actions || [];
        row.style.gridTemplateColumns = `repeat(${Math.min(4, Math.max(1, items.length))}, 1fr)`;
        row.innerHTML = items.map(a => `
            <button type="button" class="dash-quick-btn" onclick="window.handleDashAction('${escapeAttr(a.action)}', '${escapeAttr(a.url || '')}')">
                <span class="material-symbols-outlined">${escapeHtml(a.icon)}</span>
                <span>${escapeHtml(a.label)}</span>
                ${a.subtitle ? `<span style="font-size:10px;color:var(--text-secondary);font-weight:500;">${escapeHtml(a.subtitle)}</span>` : ''}
            </button>
        `).join('');
    }

    function renderMetaRow(data) {
        const dc = data.daily_challenge || {};
        const cl = data.continue_learning || {};
        const ue = data.upcoming_exam || {};

        return `
            <div class="dash-meta-row">
                <div class="dash-card dash-meta-card">
                    <div class="dash-card-title">Daily Challenge</div>
                    <p class="dash-meta-title">${escapeHtml(dc.title || 'Daily Challenge')}</p>
                    <p class="dash-meta-desc">${escapeHtml(dc.description || '')}</p>
                    <div class="dash-meta-foot">
                        <span>${dc.participants ? dc.participants + ' participants' : ''}</span>
                        <span class="dash-meta-chip ${dc.completed ? 'done' : ''}">${dc.completed ? 'Done today ✓' : (dc.available ? 'Available' : 'Unavailable')}</span>
                    </div>
                </div>
                <div class="dash-card dash-meta-card" style="cursor:pointer" onclick="window.handleDashAction('continue')">
                    <div class="dash-card-title">Continue Learning</div>
                    <p class="dash-meta-title">${escapeHtml(cl.topic || 'Your next topic')}</p>
                    <p class="dash-meta-desc">${escapeHtml(cl.subject || '')} · ${escapeHtml(cl.exam_name || '')}</p>
                    <div class="dash-meta-progress"><div class="dash-meta-progress-fill" style="width:${cl.progress_percent || 0}%"></div></div>
                    <div class="dash-meta-foot">
                        <span>${cl.topics_done ?? 0} / ${cl.topics_total ?? 0} topics</span>
                        <span class="dash-meta-chip">Continue →</span>
                    </div>
                </div>
                <div class="dash-card dash-meta-card">
                    <div class="dash-card-title">Upcoming Exam</div>
                    <p class="dash-meta-title">${escapeHtml(ue.exam_name || data.user.target_exam || 'Your exam')}</p>
                    <p class="dash-meta-desc">${ue.has_date ? (ue.days_left + ' days left · ' + (ue.exam_date || '')) : 'Set your exam date in profile settings'}</p>
                    <div class="dash-meta-foot">
                        <span>${data.plan_name || 'Free'} plan</span>
                        ${ue.has_date ? `<span class="dash-meta-chip">${ue.days_left}d to go</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    function renderDashboard(data) {
        const container = document.getElementById('dashContent');
        if (!container || !data.success) return;

        dashData = data;

        document.getElementById('dashGreeting').textContent = data.greeting || ('Hi, ' + (data.user.first_name || 'Student') + '!');
        const coachEl = document.getElementById('dashCoachNote');
        if (coachEl) coachEl.textContent = data.coach_note || '';

        renderQuickActions(data.quick_actions);

        const rings = data.accuracy_rings || [];
        const outer = rings[0] || { value: data.quiz_accuracy || 0, label: 'Quizzes', color: ringColors[0] };
        const inner = rings[1] || { value: data.strength_score || 0, label: 'Strength', color: ringColors[1] };

        const planTasks = data.today_plan || [];
        const leaderboard = data.leaderboard || [];
        const userId = data.user.id;

        const revisionHtml = planTasks.map(task => `
            <div class="dash-revision-item ${task.completed ? 'done' : ''}" onclick="window.handlePlanTask('${escapeAttr(task.action)}')">
                <div class="dash-revision-day">${task.completed ? '✓' : 'D' + task.day}</div>
                <div class="dash-revision-meta">
                    <div class="topic">${escapeHtml(task.title)}</div>
                    <div class="subject">${escapeHtml(task.subject)} · ${escapeHtml(task.subtitle || '')}</div>
                </div>
            </div>
        `).join('') || `<p style="color:var(--text-secondary);font-size:13px;">${escapeHtml(data.messages?.revision_empty || '')}</p>`;

        const lbHtml = leaderboard.length ? leaderboard.map(entry => {
            const isYou = entry.user_id === userId;
            return `
            <div class="dash-lb-item ${isYou ? 'is-you' : ''}">
                <div class="dash-lb-rank ${entry.rank <= 3 ? 'top' : ''}">${entry.rank}</div>
                <div class="dash-lb-avatar">${(entry.name || '?').charAt(0).toUpperCase()}</div>
                <div class="dash-lb-name">${escapeHtml(entry.name || 'Student')}${isYou ? ' (You)' : ''}</div>
                <div class="dash-lb-score">${entry.total_score ?? entry.wins ?? 0}</div>
            </div>`;
        }).join('') : `<p style="color:var(--text-secondary);font-size:13px;padding:12px 0;">${escapeHtml(data.messages?.leaderboard_empty || '')}</p>`;

        const peer = data.peer_comparison || {};
        const peerLine = peer.total_users ? `Top ${peer.accuracy_percentile ?? '—'}% accuracy · ${peer.total_users} students` : '';

        const streakBadge = data.streak_badge?.name ? `<div class="dash-streak-badge">${escapeHtml(data.streak_badge.name)} · ${escapeHtml(data.streak_badge.tagline || '')}</div>` : '';

        const xpPct = data.level_progress?.progress_percent ?? 0;
        const xpNeeded = data.level_progress?.xp_needed ?? 0;

        const weaknessBlock = data.weakness_has_data
            ? '<div class="dash-radar-wrap"><canvas id="weakRadar" width="200" height="160"></canvas></div>'
            : `<div class="dash-empty-chart">${escapeHtml(data.weakness_message || data.messages?.weakness_empty || '')}</div>`;

        container.innerHTML = renderMetaRow(data) + `
            <div class="dash-grid">
                <div class="dash-card dash-accuracy">
                    <div class="dash-card-title">Accuracy</div>
                    <div class="accuracy-rings">
                        <svg viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="8"/>
                            <circle cx="60" cy="60" r="52" fill="none" stroke="${outer.color}" stroke-width="8"
                                stroke-dasharray="${(outer.value/100)*326.7} 326.7" stroke-linecap="round"
                                style="filter:drop-shadow(0 0 8px rgba(82,141,255,0.5))"/>
                            <circle cx="60" cy="60" r="40" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="6"/>
                            <circle cx="60" cy="60" r="40" fill="none" stroke="${inner.color}" stroke-width="6"
                                stroke-dasharray="${(inner.value/100)*251.2} 251.2" stroke-linecap="round"
                                style="filter:drop-shadow(0 0 6px rgba(175,198,255,0.4))"/>
                        </svg>
                        <div class="accuracy-center">
                            <div class="pct">${outer.value}%</div>
                            <div class="lbl">${escapeHtml(outer.label)}</div>
                        </div>
                    </div>
                    <div class="accuracy-legend">
                        <span><i style="background:${outer.color}"></i> ${escapeHtml(outer.label)}</span>
                        <span><i style="background:${inner.color}"></i> ${escapeHtml(inner.label)}</span>
                    </div>
                    ${data.quiz_stats?.studyHoursWeek ? `<p style="font-size:11px;color:var(--text-secondary);margin-top:10px;">${data.quiz_stats.studyHoursWeek}h studied this week</p>` : ''}
                </div>

                <div class="dash-card dash-streak-card">
                    <div class="dash-card-title">Daily Streak</div>
                    <div class="dash-streak-fire">🔥</div>
                    <div class="dash-streak-num">${escapeHtml(data.streak_label || (data.streak + ' DAY STREAK'))}</div>
                    <div class="dash-streak-sub">${data.daily_progress}% of today's plan · ${data.plan_completed}/${data.plan_total} tasks</div>
                    ${streakBadge}
                </div>

                <div class="dash-card">
                    <div class="dash-card-title">Level ${data.level}</div>
                    <div class="dash-level-row">
                        <strong>${(data.xp || 0).toLocaleString()} XP</strong>
                        <span style="font-size:12px;color:var(--text-secondary)">${xpNeeded > 0 ? xpNeeded + ' to next' : 'Max level'}</span>
                    </div>
                    <div class="dash-xp-bar"><div class="dash-xp-fill" style="width:${Math.min(100, xpPct)}%"></div></div>
                    <p style="font-size:12px;color:var(--text-secondary);margin:12px 0 0;">Target: ${escapeHtml(data.user.target_exam || 'Set your exam')}</p>
                    ${data.quiz_stats?.totalQuizzes != null ? `<p style="font-size:11px;color:var(--text-secondary);margin-top:6px;">${data.quiz_stats.totalQuizzes} quizzes · best ${data.quiz_stats.bestScore || 0}%</p>` : ''}
                </div>

                <div class="dash-card dash-weakness">
                    <div class="dash-card-title">Neural Weakness Map</div>
                    ${weaknessBlock}
                </div>

                <div class="dash-card" id="dashRevisionSection">
                    <div class="dash-card-title">Today's Revision Plan</div>
                    <div class="dash-revision-list">${revisionHtml}</div>
                </div>

                <div class="dash-card dash-leaderboard">
                    <div class="dash-card-title">Leaderboard</div>
                    ${lbHtml}
                    ${data.user_leaderboard_rank ? `<p class="dash-peer-line">Your rank: #${data.user_leaderboard_rank}</p>` : peerLine ? `<p class="dash-peer-line">${peerLine}</p>` : ''}
                </div>
            </div>
        `;

        if (data.weakness_has_data) {
            drawRadar(data.weakness_map || []);
        }
    }

    function drawRadar(points) {
        const canvas = document.getElementById('weakRadar');
        if (!canvas || !points.length) return;
        const ctx = canvas.getContext('2d');
        const w = canvas.width, h = canvas.height;
        const cx = w / 2, cy = h / 2 + 4;
        const radius = Math.min(w, h) * 0.28;

        ctx.clearRect(0, 0, w, h);

        const labels = points.slice(0, 5).map(t => ({
            label: t.label || 'Topic',
            value: Math.max(0.15, Math.min(1, t.value ?? 0.5)),
        }));

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
        d.textContent = str ?? '';
        return d.innerHTML;
    }

    function escapeAttr(str) {
        return String(str ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    }

    window.handleDashAction = function (action, url) {
        switch (action) {
            case 'chat':
                window.switchAppView('chat');
                if (typeof createNewChat === 'function') createNewChat();
                break;
            case 'battle':
                window.switchAppView('battle');
                break;
            case 'mock_test':
                window.switchAppView('mock_test');
                break;
            case 'revision':
                window.openRevision();
                break;
            case 'upgrade':
                window.open(url || dashData?.upgrade_url || '/pricing', '_blank');
                break;
            case 'continue':
                window.switchAppView('mock_test');
                break;
            default:
                break;
        }
    };

    window.handlePlanTask = function (action) {
        if (action === 'quiz') {
            if (typeof openQuizModal === 'function') openQuizModal();
        } else if (action === 'mock_test') {
            window.switchAppView('mock_test');
        } else {
            window.switchAppView('chat');
        }
    };

    window.openRevision = function () {
        window.switchAppView('dashboard');
        document.querySelectorAll('.app-nav-item').forEach(el => {
            el.classList.toggle('active', el.dataset.view === 'revision');
        });
        setTimeout(() => {
            document.getElementById('dashRevisionSection')?.scrollIntoView({ behavior: 'smooth' });
        }, 250);
    };

    window.openMockTest = function () {
        if (typeof window.switchAppView === 'function') {
            window.switchAppView('mock_test');
        }
    };

    window.loadWebDashboard = async function () {
        try {
            const res = await fetch('/api/web/dashboard', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!data.success) throw new Error('Failed');
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
        const battle = document.getElementById('appBattleView');
        const mockTest = document.getElementById('appMockTestView');

        document.querySelectorAll('.app-nav-item').forEach(el => {
            el.classList.toggle('active', el.dataset.view === view);
        });

        document.body.classList.remove('app-view-dashboard', 'app-view-chat', 'app-view-battle', 'app-view-mock_test');
        const bodyClass = view === 'chat' ? 'app-view-chat'
            : (view === 'battle' ? 'app-view-battle'
            : (view === 'mock_test' ? 'app-view-mock_test' : 'app-view-dashboard'));
        document.body.classList.add(bodyClass);

        dash?.classList.add('hidden');
        chat?.classList.add('hidden');
        battle?.classList.add('hidden');
        mockTest?.classList.add('hidden');

        if (view === 'chat') {
            chat?.classList.remove('hidden');
        } else if (view === 'battle') {
            battle?.classList.remove('hidden');
            if (typeof window.loadWebBattle === 'function') window.loadWebBattle();
        } else if (view === 'mock_test') {
            mockTest?.classList.remove('hidden');
            if (typeof window.loadWebMockTest === 'function') window.loadWebMockTest();
        } else {
            dash?.classList.remove('hidden');
            if (view === 'dashboard' && typeof window.loadWebDashboard === 'function') {
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
