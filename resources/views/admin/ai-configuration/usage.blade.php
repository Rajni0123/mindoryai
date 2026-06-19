<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Usage & Cost Analytics</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4 sticky top-0 z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-semibold text-white">AI Usage & Cost Analytics</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Monitor token usage and cost breakdown</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Date Filter -->
                        <form method="GET" action="{{ route('admin.ai-config.usage') }}" class="flex items-center gap-2">
                            <select name="days" onchange="this.form.submit()" class="px-3 py-1.5 bg-white/5 border border-white/10 rounded-lg text-white text-xs focus:outline-none focus:border-blue-500">
                                <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
                                <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
                                <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 days</option>
                            </select>
                        </form>
                        <!-- Export Button -->
                        <a href="{{ route('admin.ai-config.usage.export', ['days' => $days]) }}"
                           class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5">
                            <span class="material-icons-outlined" style="font-size: 14px;">download</span>
                            Export CSV
                        </a>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6 space-y-6">
                <!-- Overall Statistics Cards -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="card rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                <span class="material-icons-outlined text-blue-400" style="font-size: 20px;">psychology</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] text-gray-500 uppercase">Total Requests</p>
                                <p class="text-lg font-semibold text-white">{{ number_format($overallStats->total_requests ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                <span class="material-icons-outlined text-purple-400" style="font-size: 20px;">token</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] text-gray-500 uppercase">Total Tokens</p>
                                <p class="text-lg font-semibold text-white">{{ number_format($overallStats->total_tokens ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                                <span class="material-icons-outlined text-green-400" style="font-size: 20px;">attach_money</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] text-gray-500 uppercase">Total Cost</p>
                                <p class="text-lg font-semibold text-white">${{ number_format($overallStats->total_cost ?? 0, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center">
                                <span class="material-icons-outlined text-orange-400" style="font-size: 20px;">trending_up</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] text-gray-500 uppercase">Avg Cost/Request</p>
                                <p class="text-lg font-semibold text-white">
                                    ${{ $overallStats->total_requests > 0 ? number_format($overallStats->total_cost / $overallStats->total_requests, 4) : '0.0000' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature Breakdown -->
                <div class="grid grid-cols-4 gap-4">
                    @php
                        $featureColors = [
                            'chat' => ['bg' => '#3b82f6', 'icon' => 'chat'],
                            'quiz' => ['bg' => '#f59e0b', 'icon' => 'quiz'],
                            'whiteboard' => ['bg' => '#8b5cf6', 'icon' => 'video_library'],
                            'image_generation' => ['bg' => '#10b981', 'icon' => 'image'],
                        ];
                    @endphp

                    @foreach(['chat', 'quiz', 'whiteboard', 'image_generation'] as $feature)
                        @php
                            $stats = $featureStats[$feature] ?? [];
                            $color = $featureColors[$feature];
                        @endphp
                        <div class="card rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: {{ $color['bg'] }}20;">
                                    <span class="material-icons-outlined" style="color: {{ $color['bg'] }}; font-size: 16px;">{{ $color['icon'] }}</span>
                                </div>
                                <h3 class="text-xs font-semibold text-white">{{ ucfirst(str_replace('_', ' ', $feature)) }}</h3>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] text-gray-500">Requests</span>
                                    <span class="text-xs font-semibold text-white">{{ number_format($stats['total_requests'] ?? 0) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] text-gray-500">Tokens</span>
                                    <span class="text-xs font-semibold text-white">{{ number_format($stats['total_tokens'] ?? 0) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] text-gray-500">Cost</span>
                                    <span class="text-xs font-semibold" style="color: {{ $color['bg'] }}">${{ number_format($stats['total_cost'] ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Charts Row 1 -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Daily Tokens Chart -->
                    <div class="card rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-white mb-4">Daily Token Usage</h3>
                        <div style="height: 250px; position: relative;">
                            <canvas id="tokensChart"></canvas>
                        </div>
                    </div>

                    <!-- Daily Cost Chart -->
                    <div class="card rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-white mb-4">Daily Cost Trend</h3>
                        <div style="height: 250px; position: relative;">
                            <canvas id="costChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Model Distribution -->
                    <div class="card rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-white mb-4">Model Distribution</h3>
                        <div style="height: 250px; position: relative;">
                            <canvas id="modelChart"></canvas>
                        </div>
                    </div>

                    <!-- Feature Distribution -->
                    <div class="card rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-white mb-4">Feature Usage (Requests)</h3>
                        <div style="height: 250px; position: relative;">
                            <canvas id="featureChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Model Stats Table -->
                <div class="card rounded-lg p-5">
                    <h3 class="text-sm font-semibold text-white mb-4">Model-wise Breakdown</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-gray-800/50">
                                    <th class="text-left py-2 px-3 text-gray-400 font-medium">Model</th>
                                    <th class="text-right py-2 px-3 text-gray-400 font-medium">Requests</th>
                                    <th class="text-right py-2 px-3 text-gray-400 font-medium">Input Tokens</th>
                                    <th class="text-right py-2 px-3 text-gray-400 font-medium">Output Tokens</th>
                                    <th class="text-right py-2 px-3 text-gray-400 font-medium">Total Tokens</th>
                                    <th class="text-right py-2 px-3 text-gray-400 font-medium">Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($modelStats as $model)
                                <tr class="border-b border-gray-800/30 hover:bg-white/5">
                                    <td class="py-2 px-3 text-white font-medium">{{ $model->model_name }}</td>
                                    <td class="py-2 px-3 text-right text-gray-300">{{ number_format($model->total_requests) }}</td>
                                    <td class="py-2 px-3 text-right text-gray-300">{{ number_format($model->total_input_tokens) }}</td>
                                    <td class="py-2 px-3 text-right text-gray-300">{{ number_format($model->total_output_tokens) }}</td>
                                    <td class="py-2 px-3 text-right text-gray-300">{{ number_format($model->total_tokens) }}</td>
                                    <td class="py-2 px-3 text-right text-green-400 font-semibold">${{ number_format($model->total_cost, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-gray-500">No model data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Users -->
                <div class="card rounded-lg p-5">
                    <h3 class="text-sm font-semibold text-white mb-4">Top Users by Cost</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-gray-800/50">
                                    <th class="text-left py-2 px-3 text-gray-400 font-medium">User</th>
                                    <th class="text-right py-2 px-3 text-gray-400 font-medium">Requests</th>
                                    <th class="text-right py-2 px-3 text-gray-400 font-medium">Total Tokens</th>
                                    <th class="text-right py-2 px-3 text-gray-400 font-medium">Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topUsers as $userStat)
                                <tr class="border-b border-gray-800/30 hover:bg-white/5">
                                    <td class="py-2 px-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-blue-500/20 flex items-center justify-center">
                                                <span class="material-icons-outlined text-blue-400" style="font-size: 12px;">person</span>
                                            </div>
                                            <div>
                                                <p class="text-white font-medium">{{ $userStat->user->name ?? 'Unknown' }}</p>
                                                <p class="text-[9px] text-gray-500">{{ $userStat->user->email ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 px-3 text-right text-gray-300">{{ number_format($userStat->request_count) }}</td>
                                    <td class="py-2 px-3 text-right text-gray-300">{{ number_format($userStat->total_tokens) }}</td>
                                    <td class="py-2 px-3 text-right text-green-400 font-semibold">${{ number_format($userStat->total_cost, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-gray-500">No user data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Chart.js default config
        Chart.defaults.color = '#9ca3af';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.08)';
        Chart.defaults.backgroundColor = 'rgba(59, 130, 246, 0.5)';

        // Get selected days from dropdown
        const selectedDays = {{ $days }};

        // Get canvas contexts
        const tokensCtx = document.getElementById('tokensChart').getContext('2d');
        const costCtx = document.getElementById('costChart').getContext('2d');
        const modelCtx = document.getElementById('modelChart').getContext('2d');
        new Chart(modelCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($modelStats->pluck('model_name')->toArray()) !!},
                datasets: [{
                    data: {!! json_encode($modelStats->pluck('total_requests')->toArray()) !!},
                    backgroundColor: [
                        '#3b82f6',
                        '#8b5cf6',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#06b6d4',
                    ],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: { size: 10 }
                        }
                    }
                }
            }
        });

        // Feature Distribution Chart
        const featureCtx = document.getElementById('featureChart').getContext('2d');
        new Chart(featureCtx, {
            type: 'bar',
            data: {
                labels: ['Chat', 'Quiz', 'Whiteboard', 'Image Gen'],
                datasets: [{
                    label: 'Requests',
                    data: [
                        {{ $featureStats['chat']['total_requests'] ?? 0 }},
                        {{ $featureStats['quiz']['total_requests'] ?? 0 }},
                        {{ $featureStats['whiteboard']['total_requests'] ?? 0 }},
                        {{ $featureStats['image_generation']['total_requests'] ?? 0 }}
                    ],
                    backgroundColor: ['#3b82f6', '#f59e0b', '#8b5cf6', '#10b981'],
                    borderWidth: 0,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Store chart instances for updating
        let tokensChart = new Chart(tokensCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_column($dailyUsage, 'date')) !!},
                datasets: [{
                    label: 'Tokens',
                    data: {!! json_encode(array_column($dailyUsage, 'tokens')) !!},
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        let costChart = new Chart(costCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_column($dailyUsage, 'date')) !!},
                datasets: [{
                    label: 'Cost (USD)',
                    data: {!! json_encode(array_column($dailyUsage, 'cost')) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Real-time data refresh function
        async function refreshUsageData() {
            try {
                const response = await fetch(`/admin/ai-config/usage-data?days=${selectedDays}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) return;

                const data = await response.json();

                // Update overall statistics cards
                document.querySelector('.grid.grid-cols-4:first-of-type .card:nth-child(1) p.text-lg').textContent =
                    Number(data.overallStats.total_requests || 0).toLocaleString();

                document.querySelector('.grid.grid-cols-4:first-of-type .card:nth-child(2) p.text-lg').textContent =
                    Number(data.overallStats.total_tokens || 0).toLocaleString();

                document.querySelector('.grid.grid-cols-4:first-of-type .card:nth-child(3) p.text-lg').textContent =
                    '$' + Number(data.overallStats.total_cost || 0).toFixed(2);

                const avgCost = data.overallStats.total_requests > 0
                    ? (data.overallStats.total_cost / data.overallStats.total_requests).toFixed(4)
                    : '0.0000';
                document.querySelector('.grid.grid-cols-4:first-of-type .card:nth-child(4) p.text-lg').textContent =
                    '$' + avgCost;

                // Update feature cards
                const features = ['chat', 'quiz', 'whiteboard', 'image_generation'];
                features.forEach((feature, index) => {
                    const stats = data.featureStats[feature] || {};
                    const card = document.querySelectorAll('.grid.grid-cols-4')[1].children[index];

                    const spans = card.querySelectorAll('.space-y-2 .flex span:last-child');
                    spans[0].textContent = Number(stats.total_requests || 0).toLocaleString();
                    spans[1].textContent = Number(stats.total_tokens || 0).toLocaleString();
                    spans[2].textContent = '$' + Number(stats.total_cost || 0).toFixed(2);
                });

                // Update charts
                tokensChart.data.labels = data.dailyUsage.map(d => d.date);
                tokensChart.data.datasets[0].data = data.dailyUsage.map(d => d.tokens);
                tokensChart.update('none'); // Update without animation for smoother refresh

                costChart.data.labels = data.dailyUsage.map(d => d.date);
                costChart.data.datasets[0].data = data.dailyUsage.map(d => d.cost);
                costChart.update('none');

                // Show last updated timestamp
                const now = new Date().toLocaleTimeString();
                console.log(`✅ Usage data refreshed at ${now}`);

            } catch (error) {
                console.error('Failed to refresh usage data:', error);
            }
        }

        // Refresh data every 10 seconds
        setInterval(refreshUsageData, 10000);

        // Show refresh indicator
        const header = document.querySelector('header > div');
        const refreshIndicator = document.createElement('div');
        refreshIndicator.className = 'flex items-center gap-2 text-[10px] text-gray-500';
        refreshIndicator.innerHTML = `
            <span class="material-icons-outlined animate-spin" style="font-size: 12px;" id="refreshIcon">sync</span>
            <span>Live updates every 10s</span>
        `;
        header.appendChild(refreshIndicator);

        // Stop animation after initial load
        setTimeout(() => {
            document.getElementById('refreshIcon').classList.remove('animate-spin');
        }, 2000);

        // Animate refresh icon on each update
        const originalRefresh = refreshUsageData;
        refreshUsageData = async function() {
            document.getElementById('refreshIcon').classList.add('animate-spin');
            await originalRefresh();
            setTimeout(() => {
                document.getElementById('refreshIcon').classList.remove('animate-spin');
            }, 1000);
        };
    </script>
</body>
</html>
