<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Settings - {{ config('app.name', 'BlinkStudy') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .dark body {
            background: #000000;
        }

        .settings-nav-item.active {
            background: rgba(0, 0, 0, 0.1);
            color: black;
        }

        .dark .settings-nav-item.active {
            background: #2a2a2a;
            color: white;
        }

        .settings-nav-item:not(.active):hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .dark .settings-nav-item:not(.active):hover {
            background: #2a2a2a;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #1a1a1a;
        }

        ::-webkit-scrollbar-thumb {
            background: #404040;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #505050;
        }
    </style>

    <script>
        // Theme initialization - Apply saved theme before page renders
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const htmlElement = document.documentElement;

            if (savedTheme === 'light') {
                htmlElement.classList.remove('dark');
                htmlElement.classList.add('light');
            } else if (savedTheme === 'dark') {
                htmlElement.classList.add('dark');
                htmlElement.classList.remove('light');
            } else if (savedTheme === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (prefersDark) {
                    htmlElement.classList.add('dark');
                    htmlElement.classList.remove('light');
                } else {
                    htmlElement.classList.remove('dark');
                    htmlElement.classList.add('light');
                }
            }
        })();
    </script>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "background-dark": "#000000",
                        "surface-dark": "#171717",
                        "surface-light": "#2f2f2f",
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white dark:bg-black text-gray-900 dark:text-white">

<div class="flex h-screen overflow-hidden">
    <!-- Left Sidebar Navigation -->
    <div class="w-72 bg-gray-50 dark:bg-[#171717] flex flex-col border-r border-gray-200 dark:border-gray-800">
        <!-- Header with Close Button -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Settings</h1>
            <a href="{{ route('chat') }}" class="w-8 h-8 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <span class="material-symbols-outlined text-xl">close</span>
            </a>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <button class="settings-nav-item active w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors" data-section="general">
                <span class="material-symbols-outlined text-xl">settings</span>
                <span>General</span>
            </button>
            <button class="settings-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors" data-section="notifications">
                <span class="material-symbols-outlined text-xl">notifications</span>
                <span>Notifications</span>
            </button>
            <button class="settings-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors" data-section="personalization">
                <span class="material-symbols-outlined text-xl">palette</span>
                <span>Personalization</span>
            </button>
            <button class="settings-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors" data-section="apps">
                <span class="material-symbols-outlined text-xl">apps</span>
                <span>Apps</span>
            </button>
            <button class="settings-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors" data-section="data-controls">
                <span class="material-symbols-outlined text-xl">storage</span>
                <span>Data controls</span>
            </button>
            <button class="settings-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors" data-section="security">
                <span class="material-symbols-outlined text-xl">security</span>
                <span>Security</span>
            </button>
            <button class="settings-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors" data-section="parental-controls">
                <span class="material-symbols-outlined text-xl">family_restroom</span>
                <span>Parental controls</span>
            </button>
            <button class="settings-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors" data-section="account">
                <span class="material-symbols-outlined text-xl">account_circle</span>
                <span>Account</span>
            </button>
        </nav>
    </div>

    <!-- Right Content Area -->
    <div class="flex-1 overflow-y-auto bg-white dark:bg-[#2f2f2f]">
        <!-- General Section -->
        <div id="section-general" class="settings-section">
            <div class="max-w-4xl mx-auto p-8">
                <h2 class="text-3xl font-semibold text-gray-900 dark:text-white mb-10">General</h2>

                <div class="space-y-6">
                    <!-- Appearance -->
                    <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-base text-gray-700 dark:text-gray-200">Appearance</span>
                        <div class="relative">
                            <select id="themeToggle" class="bg-white dark:bg-transparent text-gray-900 dark:text-white text-sm px-4 py-2 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 focus:border-gray-500 dark:focus:border-gray-400 focus:outline-none appearance-none cursor-pointer min-w-[140px]">
                                <option value="dark">Dark</option>
                                <option value="light">Light</option>
                                <option value="system">System</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-lg">expand_more</span>
                        </div>
                    </div>

                    <!-- Accent Color -->
                    <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-base text-gray-700 dark:text-gray-200">Accent color</span>
                        <div class="relative flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full bg-gray-500"></div>
                            <select class="bg-white dark:bg-transparent text-gray-900 dark:text-white text-sm px-4 py-2 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 focus:border-gray-500 dark:focus:border-gray-400 focus:outline-none appearance-none cursor-pointer min-w-[140px]">
                                <option value="default" selected>Default</option>
                                <option value="blue">Blue</option>
                                <option value="green">Green</option>
                                <option value="purple">Purple</option>
                                <option value="red">Red</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-lg">expand_more</span>
                        </div>
                    </div>

                    <!-- Language -->
                    <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-base text-gray-700 dark:text-gray-200">Language</span>
                        <div class="relative">
                            <select class="bg-white dark:bg-transparent text-gray-900 dark:text-white text-sm px-4 py-2 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 focus:border-gray-500 dark:focus:border-gray-400 focus:outline-none appearance-none cursor-pointer min-w-[160px]">
                                <option value="auto" selected>Auto-detect</option>
                                <option value="en">English</option>
                                <option value="es">Spanish</option>
                                <option value="fr">French</option>
                                <option value="de">German</option>
                                <option value="hi">Hindi</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-lg">expand_more</span>
                        </div>
                    </div>

                    <!-- Spoken Language -->
                    <div class="py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-base text-gray-700 dark:text-gray-200">Spoken language</span>
                            <div class="relative">
                                <select class="bg-white dark:bg-transparent text-gray-900 dark:text-white text-sm px-4 py-2 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 focus:border-gray-500 dark:focus:border-gray-400 focus:outline-none appearance-none cursor-pointer min-w-[160px]">
                                    <option value="auto" selected>Auto-detect</option>
                                    <option value="en">English</option>
                                    <option value="es">Spanish</option>
                                    <option value="fr">French</option>
                                    <option value="de">German</option>
                                    <option value="hi">Hindi</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-lg">expand_more</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">For best results, select the language you mainly speak. If it's not listed, it may still be supported via auto-detection.</p>
                    </div>

                    <!-- Voice -->
                    <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-base text-gray-700 dark:text-gray-200">Voice</span>
                        <div class="flex items-center gap-3">
                            <button class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg text-gray-900 dark:text-white text-sm transition-colors">
                                <span class="material-symbols-outlined text-lg">play_arrow</span>
                                <span>Play</span>
                            </button>
                            <div class="relative">
                                <select class="bg-white dark:bg-transparent text-gray-900 dark:text-white text-sm px-4 py-2 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 focus:border-gray-500 dark:focus:border-gray-400 focus:outline-none appearance-none cursor-pointer min-w-[120px]">
                                    <option value="sol" selected>Sol</option>
                                    <option value="luna">Luna</option>
                                    <option value="nova">Nova</option>
                                    <option value="sky">Sky</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-lg">expand_more</span>
                            </div>
                        </div>
                    </div>

                    <!-- Separate Voice Mode -->
                    <div class="py-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-base text-gray-700 dark:text-gray-200">Separate voice mode</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" id="voiceModeToggle">
                                <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-600 peer-focus:ring-offset-2 peer-focus:ring-offset-white dark:peer-focus:ring-offset-[#2f2f2f] rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">Keep ChatGPT Voice in a separate full screen, without real time transcripts and visuals.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Other Sections (Placeholder) -->
        <div id="section-notifications" class="settings-section hidden">
            <div class="max-w-4xl mx-auto p-8">
                <h2 class="text-3xl font-semibold text-gray-900 dark:text-white mb-10">Notifications</h2>
                <div class="bg-gray-100 dark:bg-gray-800/30 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">Notification settings coming soon...</p>
                </div>
            </div>
        </div>

        <div id="section-personalization" class="settings-section hidden">
            <div class="max-w-4xl mx-auto p-8">
                <h2 class="text-3xl font-semibold text-gray-900 dark:text-white mb-10">Personalization</h2>
                <div class="bg-gray-100 dark:bg-gray-800/30 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">Personalization settings coming soon...</p>
                </div>
            </div>
        </div>

        <div id="section-apps" class="settings-section hidden">
            <div class="max-w-4xl mx-auto p-8">
                <h2 class="text-3xl font-semibold text-gray-900 dark:text-white mb-10">Apps</h2>
                <div class="bg-gray-100 dark:bg-gray-800/30 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">Apps settings coming soon...</p>
                </div>
            </div>
        </div>

        <div id="section-data-controls" class="settings-section hidden">
            <div class="max-w-4xl mx-auto p-8">
                <h2 class="text-3xl font-semibold text-gray-900 dark:text-white mb-10">Data controls</h2>
                <div class="bg-gray-100 dark:bg-gray-800/30 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">Data controls settings coming soon...</p>
                </div>
            </div>
        </div>

        <div id="section-security" class="settings-section hidden">
            <div class="max-w-4xl mx-auto p-8">
                <h2 class="text-3xl font-semibold text-gray-900 dark:text-white mb-10">Security</h2>
                <div class="bg-gray-100 dark:bg-gray-800/30 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">Security settings coming soon...</p>
                </div>
            </div>
        </div>

        <div id="section-parental-controls" class="settings-section hidden">
            <div class="max-w-4xl mx-auto p-8">
                <h2 class="text-3xl font-semibold text-gray-900 dark:text-white mb-10">Parental controls</h2>
                <div class="bg-gray-100 dark:bg-gray-800/30 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">Parental controls settings coming soon...</p>
                </div>
            </div>
        </div>

        <div id="section-account" class="settings-section hidden">
            <div class="max-w-4xl mx-auto p-8">
                <h2 class="text-3xl font-semibold text-gray-900 dark:text-white mb-10">Account</h2>
                <div class="space-y-6">
                    <!-- User Profile Info -->
                    <div class="bg-gray-100 dark:bg-gray-800/30 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center text-white font-semibold text-2xl">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="displayName">{{ auth()->user()->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400" id="displayEmail">{{ auth()->user()->email }}</p>
                                @if(auth()->user()->class_level)
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" id="displayClass">{{ auth()->user()->class_level }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Edit Profile Form -->
                        <form id="profileForm" class="space-y-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Full Name</label>
                                <input type="text" name="name" value="{{ auth()->user()->name }}" required
                                       class="w-full px-4 py-2.5 bg-white dark:bg-[#1a1a1a] border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email Address</label>
                                <input type="email" name="email" value="{{ auth()->user()->email }}" required
                                       class="w-full px-4 py-2.5 bg-white dark:bg-[#1a1a1a] border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Class Level</label>
                                <select name="class_level"
                                        class="w-full px-4 py-2.5 bg-white dark:bg-[#1a1a1a] border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors appearance-none cursor-pointer">
                                    <option value="">Select your class</option>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="Class {{ $i }}" {{ auth()->user()->class_level == "Class $i" ? 'selected' : '' }}>Class {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <button type="submit" id="profileSaveBtn"
                                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                                Save Changes
                            </button>
                        </form>

                        <!-- Change Password -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Change Password</h4>
                            <form id="passwordForm" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Current Password</label>
                                    <input type="password" name="current_password" required placeholder="Enter current password"
                                           class="w-full px-4 py-2.5 bg-white dark:bg-[#1a1a1a] border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">New Password</label>
                                    <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters"
                                           class="w-full px-4 py-2.5 bg-white dark:bg-[#1a1a1a] border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Confirm New Password</label>
                                    <input type="password" name="password_confirmation" required minlength="8" placeholder="Re-enter new password"
                                           class="w-full px-4 py-2.5 bg-white dark:bg-[#1a1a1a] border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                                </div>
                                <button type="submit" id="passwordSaveBtn"
                                        class="px-6 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg text-sm font-medium transition-colors">
                                    Update Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Plan Information -->
                    <div class="bg-gray-100 dark:bg-gray-800/30 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Subscription Plan</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">
                            Current Plan:
                            <span class="text-gray-900 dark:text-white font-semibold">
                                @if(auth()->user()->is_active && auth()->user()->plan_id)
                                    {{ Session::get('access_plan', 'Premium') }}
                                @else
                                    Free
                                @endif
                            </span>
                        </p>
                        <a href="{{ route('plans') }}" class="inline-block px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-white text-sm transition-colors">
                            Upgrade Plan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Settings navigation switching
    const settingsNavItems = document.querySelectorAll('.settings-nav-item');
    const settingsSections = document.querySelectorAll('.settings-section');

    settingsNavItems.forEach(item => {
        item.addEventListener('click', function() {
            const sectionId = this.dataset.section;

            // Remove active class from all nav items
            settingsNavItems.forEach(nav => {
                nav.classList.remove('active');
                nav.classList.remove('text-gray-900', 'dark:text-white');
                nav.classList.add('text-gray-600', 'dark:text-gray-400');
            });

            // Add active class to clicked nav item
            this.classList.add('active');
            this.classList.remove('text-gray-600', 'dark:text-gray-400');
            this.classList.add('text-gray-900', 'dark:text-white');

            // Hide all sections
            settingsSections.forEach(section => {
                section.classList.add('hidden');
            });

            // Show selected section
            const targetSection = document.getElementById(`section-${sectionId}`);
            if (targetSection) {
                targetSection.classList.remove('hidden');
            }
        });
    });

    // Theme Toggle Functionality
    const themeToggle = document.getElementById('themeToggle');
    const htmlElement = document.documentElement;

    // Load saved theme from localStorage and set dropdown value
    const savedTheme = localStorage.getItem('theme') || 'dark';
    themeToggle.value = savedTheme;

    // Theme toggle event handler
    themeToggle.addEventListener('change', function() {
        const selectedTheme = this.value;
        localStorage.setItem('theme', selectedTheme);

        if (selectedTheme === 'light') {
            htmlElement.classList.remove('dark');
            htmlElement.classList.add('light');
        } else if (selectedTheme === 'dark') {
            htmlElement.classList.add('dark');
            htmlElement.classList.remove('light');
        } else if (selectedTheme === 'system') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (prefersDark) {
                htmlElement.classList.add('dark');
                htmlElement.classList.remove('light');
            } else {
                htmlElement.classList.remove('dark');
                htmlElement.classList.add('light');
            }
        }
    });

    // Listen for system theme changes when 'system' is selected
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (themeToggle.value === 'system') {
            if (e.matches) {
                htmlElement.classList.add('dark');
                htmlElement.classList.remove('light');
            } else {
                htmlElement.classList.remove('dark');
                htmlElement.classList.add('light');
            }
        }
    });

    // ── Profile Update ──
    document.getElementById('profileForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('profileSaveBtn');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch('{{ route("profile.update") }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                document.getElementById('displayName').textContent = result.user.name;
                document.getElementById('displayEmail').textContent = result.user.email;
                btn.textContent = 'Saved!';
                btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                btn.classList.add('bg-green-600');
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('bg-green-600');
                    btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }, 2000);
            } else {
                alert(result.message || 'Failed to update profile');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while updating profile');
        } finally {
            btn.disabled = false;
            if (btn.textContent === 'Saving...') btn.textContent = originalText;
        }
    });

    // ── Password Update ──
    document.getElementById('passwordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('passwordSaveBtn');
        const originalText = btn.textContent;
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        if (data.password !== data.password_confirmation) {
            alert('New passwords do not match!');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Updating...';

        try {
            const response = await fetch('{{ route("profile.password") }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.reset();
                btn.textContent = 'Updated!';
                btn.classList.remove('bg-gray-200', 'dark:bg-gray-700');
                btn.classList.add('bg-green-600', 'text-white');
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('bg-green-600', 'text-white');
                    btn.classList.add('bg-gray-200', 'dark:bg-gray-700');
                }, 2000);
            } else {
                alert(result.message || 'Failed to update password');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while updating password');
        } finally {
            btn.disabled = false;
            if (btn.textContent === 'Updating...') btn.textContent = originalText;
        }
    });

    console.log('Settings page initialized');
</script>

</body>
</html>
