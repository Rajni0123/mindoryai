<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BlinkStudy') }} AI - Chat</title>

    <!-- Vite React Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <!-- KaTeX for Math Rendering -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* BlinkStudy Brand Theme - Matching Mobile App */
        :root {
            --primary: #0D9488;
            --primary-light: #99F6E4;
            --primary-dark: #0F766E;
            --secondary: #F59E0B;
            --secondary-light: #FDE68A;
            --bg-primary: #F0FDFA;
            --bg-secondary: #FFFFFF;
            --bg-hover: #CCFBF1;
            --text-primary: #0F172A;
            --text-secondary: #6B7280;
            --border-color: #E5E7EB;
            --accent-color: #0D9488;
            --accent-light: #99F6E4;
            --message-user: #CCFBF1;
            --message-ai: #FFFFFF;
            --input-bg: #FFFFFF;
            --shadow-sm: 0 1px 2px rgba(13,148,136,0.05);
            --shadow-md: 0 4px 6px rgba(13,148,136,0.1);
            --success: #10B981;
            --error: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
        }

        .dark {
            --primary: #0D9488;
            --primary-light: #14B8A6;
            --primary-dark: #0F766E;
            --secondary: #F59E0B;
            --secondary-light: #FBBF24;
            --bg-primary: #111827;
            --bg-secondary: #1F2937;
            --bg-hover: #374151;
            --text-primary: #F9FAFB;
            --text-secondary: #9CA3AF;
            --border-color: #374151;
            --accent-color: #0D9488;
            --accent-light: #0F766E;
            --message-user: #1F2937;
            --message-ai: #111827;
            --input-bg: #1F2937;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.3);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.4);
            --success: #10B981;
            --error: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
        }

        /* ====== CLAUDE-STYLE SIDEBAR ====== */
        .sidebar {
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 30;
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            width: 60px !important;
            min-width: 60px !important;
        }

        .sidebar.collapsed .sidebar-logo-text,
        .sidebar.collapsed .sidebar-item-text,
        .sidebar.collapsed .chat-item,
        .sidebar.collapsed .user-info,
        .sidebar.collapsed #chatHistory {
            display: none;
        }

        .sidebar.collapsed .sidebar-header {
            justify-content: center;
            padding: 12px 0;
        }

        .sidebar.collapsed .sidebar-collapse-btn {
            margin: 0 auto;
        }

        .sidebar.collapsed .sidebar-nav-item {
            justify-content: center;
            padding: 12px 0;
            width: 100%;
        }

        .sidebar.collapsed .sidebar-nav-item svg {
            margin: 0;
        }

        .sidebar.collapsed .sidebar-user {
            justify-content: center;
            padding: 12px 0;
        }

        .sidebar.collapsed .sidebar-user .sidebar-nav-item {
            justify-content: center;
        }

        .sidebar-collapse-btn {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: transparent;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            color: var(--text-secondary);
        }

        .sidebar-collapse-btn:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }


        /* User Popup Menu */
        .user-popup-menu {
            position: absolute;
            bottom: 100%;
            left: 8px;
            right: 8px;
            margin-bottom: 8px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 100;
            overflow: visible;
        }

        .user-popup-menu.hidden {
            display: none;
        }

        .popup-header {
            padding: 12px 16px;
        }

        .popup-divider {
            height: 1px;
            background: var(--border-color);
            margin: 4px 0;
        }

        .popup-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            cursor: pointer;
            transition: background 0.15s ease;
            color: var(--text-primary);
            text-decoration: none;
        }

        .popup-item:hover {
            background: var(--bg-hover);
        }

        .popup-item span {
            font-size: 14px;
        }

        .popup-item .popup-shortcut {
            margin-left: auto;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .popup-item.text-red-500 {
            color: #EF4444;
        }

        .popup-item.text-red-500:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        /* Popup Submenu - Side Flyout */
        .popup-item-with-submenu {
            position: relative;
        }

        .popup-submenu {
            position: fixed;
            min-width: 200px;
            max-height: 300px;
            overflow-y: auto;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            z-index: 9999;
            opacity: 1;
            transform: translateX(0) scale(1);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .popup-submenu.hidden {
            opacity: 0;
            transform: translateX(-10px) scale(0.95);
            pointer-events: none;
            visibility: hidden;
        }

        /* Web Search & Thinking Mode Indicators */
        .search-mode-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 20px;
            font-size: 12px;
            color: #3B82F6;
            margin-right: 8px;
        }

        .thinking-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 20px;
            font-size: 12px;
            color: #A855F7;
        }

        .thinking-indicator .dots {
            display: inline-flex;
            gap: 2px;
        }

        .thinking-indicator .dots span {
            width: 4px;
            height: 4px;
            background: #A855F7;
            border-radius: 50%;
            animation: thinkingDot 1.4s infinite ease-in-out;
        }

        .thinking-indicator .dots span:nth-child(1) { animation-delay: 0s; }
        .thinking-indicator .dots span:nth-child(2) { animation-delay: 0.2s; }
        .thinking-indicator .dots span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes thinkingDot {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }

        /* Message Mode Badges */
        .message-mode-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            margin-bottom: 6px;
        }

        .message-mode-badge.web-search {
            background: rgba(59, 130, 246, 0.1);
            color: #3B82F6;
        }

        .message-mode-badge.thinking {
            background: rgba(168, 85, 247, 0.1);
            color: #A855F7;
        }

        .popup-submenu .popup-item {
            padding: 10px 16px;
            font-size: 14px;
        }

        .popup-submenu .popup-item.active {
            background: var(--bg-hover);
            color: var(--primary);
        }

        .popup-submenu .popup-item.active::before {
            content: '✓';
            margin-right: 8px;
            color: var(--primary);
        }

        .submenu-arrow {
            transition: transform 0.2s ease;
        }

        /* Chat History Items - Claude Style */
        .chat-item {
            background: transparent;
            border-radius: 10px;
            padding: 10px 12px;
            margin: 2px 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
            font-size: 14px;
        }

        .chat-item:hover {
            background: var(--bg-hover);
        }

        .chat-item.active {
            background: var(--primary);
            color: white;
        }

        .chat-item .chat-title {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 400;
        }

        .chat-item .chat-icon {
            width: 18px;
            height: 18px;
            opacity: 0.7;
            flex-shrink: 0;
        }

        .chat-item:hover .chat-icon,
        .chat-item.active .chat-icon {
            opacity: 1;
        }

        /* Date Group Headers */
        .chat-date-group {
            padding: 8px 20px 4px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ====== CLAUDE-STYLE INPUT ====== */
        .input-wrapper {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: all 0.2s ease;
        }

        .input-wrapper:focus-within {
            border-color: var(--primary);
            box-shadow: 0 2px 20px rgba(13, 148, 136, 0.15);
        }

        .input-wrapper textarea {
            background: transparent;
            border: none;
            outline: none;
            resize: none;
            font-family: inherit;
            font-size: 15px;
            line-height: 1.6;
            color: var(--text-primary);
        }

        .input-wrapper textarea::placeholder {
            color: var(--text-secondary);
        }

        .send-button {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .send-button:hover:not(:disabled) {
            background: var(--primary-dark, #0F766E);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }

        .send-button:disabled {
            background: var(--gray-400);
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ====== CLAUDE-STYLE MESSAGES ====== */
        .message-user {
            display: flex;
            justify-content: flex-end;
            padding: 8px 0;
            margin: 16px 0;
        }

        .message-user .user-bubble {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 10px 16px;
            border-radius: 20px;
            max-width: 70%;
            font-size: 15px;
            line-height: 1.5;
        }

        .message-ai {
            background: transparent;
            padding: 16px 0;
            margin: 8px 0;
            color: var(--text-primary);
        }

        .message-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 12px;
            padding: 0;
        }

        .action-btn {
            padding: 6px;
            border-radius: 6px;
            cursor: pointer;
            color: var(--text-secondary);
            background: transparent;
            border: none;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .action-btn.active {
            color: var(--primary);
        }

        .message-content {
            font-size: 15px;
            line-height: 1.75;
            color: var(--text-primary);
        }

        .message-content p {
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .message-content p:last-child {
            margin-bottom: 0;
        }

        .message-content strong {
            font-weight: 600;
            color: var(--text-primary);
        }

        .message-content code {
            background: var(--bg-hover);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 14px;
        }

        .message-content pre {
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            overflow-x: auto;
            margin: 12px 0;
        }

        .message-content pre code {
            background: none;
            padding: 0;
        }

        /* Markdown Styles - Claude Professional */
        .message-content h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            line-height: 1.3;
        }

        .message-content h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            line-height: 1.4;
        }

        .message-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            line-height: 1.4;
        }

        .message-content h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-top: 0.75rem;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .message-content ul, .message-content ol {
            margin: 12px 0;
            padding-left: 24px;
        }

        .message-content li {
            margin-bottom: 6px;
            line-height: 1.6;
        }

        .message-content ul li {
            list-style-type: disc;
        }

        .message-content ol li {
            list-style-type: decimal;
        }

        .message-content blockquote {
            border-left: 3px solid var(--accent-color);
            padding-left: 12px;
            margin: 12px 0;
            color: var(--text-secondary);
            font-style: italic;
        }

        .message-content hr {
            border: none;
            border-top: 1px solid var(--border-color);
            margin: 16px 0;
        }

        .message-content table {
            border-collapse: collapse;
            width: 100%;
            margin: 12px 0;
            font-size: 14px;
        }

        .message-content th, .message-content td {
            border: 1px solid var(--border-color);
            padding: 8px 12px;
            text-align: left;
        }

        .message-content th {
            background: var(--bg-hover);
            font-weight: 600;
        }

        .message-content a {
            color: var(--primary);
            text-decoration: underline;
        }

        .message-content a:hover {
            opacity: 0.8;
        }

        .message-content em {
            font-style: italic;
        }

        /* KaTeX Math Styles */
        .message-content .katex {
            font-size: 1.05em;
        }

        .message-content .katex-display {
            margin: 16px 0;
            overflow-x: auto;
            overflow-y: hidden;
        }

        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            gap: 6px;
            padding: 16px;
            align-items: center;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--text-secondary);
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                opacity: 0.3;
                transform: scale(1);
            }
            30% {
                opacity: 1;
                transform: scale(1.2);
            }
        }

        /* ====== CLAUDE-STYLE WELCOME SECTION ====== */
        .welcome-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 60px 20px;
            text-align: center;
        }

        .welcome-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            box-shadow: 0 12px 32px rgba(13, 148, 136, 0.25);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        .welcome-subtitle-dots {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 12px;
        }

        .welcome-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse-dot 1.5s ease-in-out infinite;
        }

        .welcome-dot:nth-child(1) {
            background: var(--primary);
            animation-delay: 0s;
        }

        .welcome-dot:nth-child(2) {
            background: var(--secondary);
            animation-delay: 0.2s;
        }

        .welcome-dot:nth-child(3) {
            background: var(--success);
            animation-delay: 0.4s;
        }

        .welcome-dot:nth-child(4) {
            background: var(--info);
            animation-delay: 0.6s;
        }

        @keyframes pulse-dot {
            0%, 100% {
                transform: scale(1);
                opacity: 0.5;
            }
            50% {
                transform: scale(1.3);
                opacity: 1;
            }
        }

        .suggestions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            width: 100%;
            max-width: 600px;
            margin-top: 32px;
        }

        @media (max-width: 640px) {
            .suggestions-grid {
                grid-template-columns: 1fr;
            }
        }

        .suggestion-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            text-align: left;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            min-height: 130px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .suggestion-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--card-color, var(--primary)), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .suggestion-card:hover {
            border-color: var(--card-color, var(--primary));
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(13, 148, 136, 0.12);
        }

        .suggestion-card:hover::before {
            opacity: 1;
        }

        .suggestion-card .card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .suggestion-card .icon-wrapper {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--icon-bg, rgba(13, 148, 136, 0.1));
            transition: all 0.3s ease;
        }

        .suggestion-card:hover .icon-wrapper {
            transform: scale(1.1) rotate(5deg);
        }

        .suggestion-card .icon-wrapper .material-icons {
            font-size: 32px;
            color: var(--card-color, var(--accent-color));
        }

        .suggestion-card .number-badge {
            min-width: 32px;
            height: 24px;
            padding: 0 8px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--card-color, var(--accent-color));
            font-size: 11px;
            font-weight: 600;
            color: white;
        }

        .suggestion-card .title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .suggestion-card .card-footer {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: auto;
        }

        .suggestion-card .card-line {
            flex: 1;
            height: 2px;
            background: var(--card-color-light, rgba(204, 120, 92, 0.2));
            border-radius: 1px;
        }

        .suggestion-card .card-footer .material-icons {
            font-size: 16px;
            color: var(--card-color, var(--accent-color));
        }

        /* Scrollbar */
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        /* ====== CLAUDE-STYLE AVATARS ====== */
        .avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 12px;
            flex-shrink: 0;
        }

        .avatar-user {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }

        .avatar-ai {
            background: linear-gradient(135deg, var(--secondary) 0%, #FBBF24 100%);
            color: white;
        }

        /* Loading animation */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .loading {
            animation: pulse 2s ease-in-out infinite;
        }

        /* Settings Modal Animation */
        #settingsModal {
            transition: opacity 0.3s ease;
        }

        #settingsModal .absolute.right-0 {
            transition: transform 0.3s ease;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 44px;
            height: 24px;
            background: var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .toggle-switch.active {
            background: var(--accent-color);
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            top: 3px;
            left: 3px;
            transition: transform 0.3s;
        }

        .toggle-switch.active::after {
            transform: translateX(20px);
        }

        /* Quiz Modal Styles */
        #quizModal, #quizTakingModal, #quizScoreModal {
            transition: opacity 0.3s ease;
        }

        .quiz-modal-content, .quiz-taking-content, .quiz-score-content {
            background: var(--bg-primary);
            border-radius: 20px;
            max-width: 900px;
            width: 95%;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .quiz-taking-content {
            max-width: 700px;
        }

        .quiz-score-content {
            max-width: 600px;
        }

        /* Quiz Taking Styles */
        .quiz-progress-bar {
            height: 4px;
            background: var(--bg-hover);
            position: relative;
            overflow: hidden;
        }

        .quiz-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-color), #50c878);
            transition: width 0.3s ease;
        }

        .quiz-question-number {
            font-size: 14px;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quiz-question-text {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .quiz-options-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }

        .quiz-option {
            padding: 16px 20px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .quiz-option:hover {
            border-color: var(--accent-color);
            transform: translateX(4px);
        }

        .quiz-option.selected {
            border-color: var(--accent-color);
            background: var(--accent-light);
        }

        .quiz-option-letter {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--text-primary);
            flex-shrink: 0;
        }

        .quiz-option.selected .quiz-option-letter {
            background: var(--accent-color);
            color: white;
        }

        .quiz-option-text {
            flex: 1;
            font-size: 15px;
            color: var(--text-primary);
        }

        .quiz-navigation {
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .quiz-nav-btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quiz-nav-btn-back {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .quiz-nav-btn-back:hover:not(:disabled) {
            background: var(--border-color);
        }

        .quiz-nav-btn-next {
            background: var(--accent-color);
            color: white;
            flex: 1;
        }

        .quiz-nav-btn-next:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(204, 120, 92, 0.3);
        }

        .quiz-nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Score Screen Styles */
        .quiz-score-header {
            text-align: center;
            padding: 32px 24px;
            background: linear-gradient(135deg, var(--accent-color), #50c878);
            color: white;
        }

        .quiz-score-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quiz-score-percentage {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .quiz-score-label {
            font-size: 18px;
            opacity: 0.9;
        }

        .quiz-score-stats {
            padding: 24px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .quiz-stat-card {
            text-align: center;
            padding: 16px;
            border-radius: 12px;
            background: var(--bg-secondary);
        }

        .quiz-stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .quiz-stat-value.correct {
            color: #50c878;
        }

        .quiz-stat-value.incorrect {
            color: #ff6b6b;
        }

        .quiz-stat-value.total {
            color: var(--accent-color);
        }

        .quiz-stat-label {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .quiz-score-actions {
            padding: 0 24px 24px;
            display: flex;
            gap: 12px;
        }

        .quiz-score-btn {
            flex: 1;
            padding: 14px 20px;
            border-radius: 12px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .quiz-score-btn-primary {
            background: var(--accent-color);
            color: white;
        }

        .quiz-score-btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .quiz-score-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Whiteboard Modal Styles */
        .whiteboard-textarea {
            min-height: 200px;
            resize: vertical;
        }

        .whiteboard-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--bg-secondary);
            margin-bottom: 16px;
        }

        .whiteboard-upload-area:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .whiteboard-upload-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 12px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .whiteboard-file-preview {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .whiteboard-file-icon {
            width: 40px;
            height: 40px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .whiteboard-divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 20px 0;
        }

        .whiteboard-divider-line {
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .whiteboard-divider-text {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .whiteboard-info-card {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
        }

        .whiteboard-info-card h4 {
            font-size: 14px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .whiteboard-info-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .whiteboard-info-card li {
            font-size: 12px;
            color: var(--text-secondary);
            padding: 4px 0;
            padding-left: 20px;
            position: relative;
        }

        .whiteboard-info-card li:before {
            content: "•";
            position: absolute;
            left: 8px;
            color: #667eea;
        }

        .quiz-modal-header {
            padding: 24px 28px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .quiz-tabs {
            display: flex;
            gap: 8px;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .quiz-tab {
            flex: 1;
            padding: 12px 20px;
            border-radius: 12px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .quiz-tab:hover {
            background: var(--bg-hover);
        }

        .quiz-tab.active {
            background: var(--bg-primary);
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        .quiz-modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
        }

        .selection-step {
            margin-bottom: 28px;
        }

        .selection-step-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }

        @media (max-width: 640px) {
            .selection-grid {
                grid-template-columns: 1fr;
            }
        }

        .selection-card {
            padding: 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .selection-card:hover {
            border-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .selection-card.selected {
            border-color: var(--accent-color);
            background: var(--accent-light);
        }

        .selection-card-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .selection-card-content {
            flex: 1;
            min-width: 0;
        }

        .selection-card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .selection-card-subtitle {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .quiz-generate-btn {
            width: 100%;
            padding: 16px;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }

        .quiz-generate-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(204, 120, 92, 0.3);
        }

        .quiz-generate-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .file-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 16px;
            padding: 48px 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--bg-secondary);
        }

        .file-upload-area:hover {
            border-color: var(--accent-color);
            background: var(--accent-light);
        }

        .file-upload-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            background: var(--accent-light);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-preview {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 16px;
        }

        .file-preview-icon {
            width: 48px;
            height: 48px;
            background: var(--accent-light);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="flex h-screen relative">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar w-[280px] min-w-[280px] flex-shrink-0 flex flex-col">
            <!-- Logo & Collapse Button -->
            <div class="sidebar-header p-4 flex items-center justify-between">
                <h1 class="sidebar-logo-text text-xl font-bold text-[var(--text-primary)] flex items-center gap-2">
                    <svg class="w-6 h-6 text-[var(--accent-color)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    {{ config('app.name', 'BlinkStudy') }}
                </h1>
                <button onclick="toggleSidebar()" class="sidebar-collapse-btn" title="Toggle sidebar">
                    <svg class="w-5 h-5 text-[var(--text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v18"/>
                    </svg>
                </button>
            </div>

            <!-- Navigation Items -->
            <div class="px-3 pb-3 space-y-1">
                <button onclick="createNewChat()" class="sidebar-nav-item w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[var(--bg-hover)] transition-all text-[var(--text-primary)]">
                    <svg class="w-5 h-5 text-[var(--text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span class="sidebar-item-text text-sm">New chat</span>
                </button>
                <button onclick="openSearch()" class="sidebar-nav-item w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[var(--bg-hover)] transition-all text-[var(--text-primary)]">
                    <svg class="w-5 h-5 text-[var(--text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span class="sidebar-item-text text-sm">Search</span>
                </button>
            </div>

            <!-- Chat History -->
            <div class="flex-1 overflow-y-auto scrollbar-thin px-2 pt-2">
                <div id="chatHistory" class="space-y-1">
                    <!-- Chat items will be loaded here -->
                </div>
            </div>

            <!-- User Section -->
            <div class="sidebar-user p-3 border-t border-[var(--border-color)] relative">
                <div onclick="toggleUserMenu()" class="sidebar-nav-item flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[var(--bg-hover)] cursor-pointer transition-colors group">
                    <div class="avatar avatar-user" style="width: 32px; height: 32px; font-size: 14px;">
                        {{ auth()->user()->name ? substr(auth()->user()->name, 0, 1) : 'U' }}
                    </div>
                    <div class="user-info flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ auth()->user()->name ?? 'User' }}</div>
                        <div class="text-xs text-[var(--text-secondary)] flex items-center gap-1">
                            @php
                                $userPlan = auth()->user()->activeSubscription ? auth()->user()->activeSubscription->plan : null;
                                $planName = $userPlan ? $userPlan->name : 'Free';
                                $isFree = !$userPlan || $userPlan->price_monthly == 0;
                            @endphp
                            <span>{{ $planName }} plan</span>
                        </div>
                    </div>
                </div>

                <!-- User Popup Menu -->
                <div id="userPopupMenu" class="user-popup-menu hidden">
                    <div class="popup-header">
                        <span class="text-sm text-[var(--text-secondary)]">{{ auth()->user()->email ?? 'user@email.com' }}</span>
                    </div>
                    <div class="popup-divider"></div>
                    <div class="popup-item" onclick="openSettings(); closeUserMenu();">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Settings</span>
                        <span class="popup-shortcut">⇧+Ctrl+,</span>
                    </div>

                    <!-- Language Submenu -->
                    @php
                        $currentLang = session('locale', app()->getLocale());
                    @endphp
                    <div class="popup-item-with-submenu" onmouseenter="showSubmenu('languageSubmenu')" onmouseleave="hideSubmenuDelayed('languageSubmenu')">
                        <div class="popup-item" onclick="toggleSubmenu('languageSubmenu', event)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                            </svg>
                            <span>Language</span>
                            <svg class="w-4 h-4 ml-auto submenu-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                        <div id="languageSubmenu" class="popup-submenu hidden" onmouseenter="keepSubmenuOpen('languageSubmenu')" onmouseleave="hideSubmenu('languageSubmenu')">
                            <div class="popup-item {{ $currentLang == 'en' ? 'active' : '' }}" onclick="setLanguage('en');">
                                <span style="margin-right: 8px;">🇺🇸</span><span>English</span>
                            </div>
                            <div class="popup-item {{ $currentLang == 'hi' ? 'active' : '' }}" onclick="setLanguage('hi');">
                                <span style="margin-right: 8px;">🇮🇳</span><span>हिंदी (Hindi)</span>
                            </div>
                            <div class="popup-item {{ $currentLang == 'es' ? 'active' : '' }}" onclick="setLanguage('es');">
                                <span style="margin-right: 8px;">🇪🇸</span><span>Español</span>
                            </div>
                            <div class="popup-item {{ $currentLang == 'fr' ? 'active' : '' }}" onclick="setLanguage('fr');">
                                <span style="margin-right: 8px;">🇫🇷</span><span>Français</span>
                            </div>
                            <div class="popup-item {{ $currentLang == 'de' ? 'active' : '' }}" onclick="setLanguage('de');">
                                <span style="margin-right: 8px;">🇩🇪</span><span>Deutsch</span>
                            </div>
                            <div class="popup-item {{ $currentLang == 'zh' ? 'active' : '' }}" onclick="setLanguage('zh');">
                                <span style="margin-right: 8px;">🇨🇳</span><span>中文</span>
                            </div>
                            <div class="popup-item {{ $currentLang == 'ja' ? 'active' : '' }}" onclick="setLanguage('ja');">
                                <span style="margin-right: 8px;">🇯🇵</span><span>日本語</span>
                            </div>
                            <div class="popup-item {{ $currentLang == 'ar' ? 'active' : '' }}" onclick="setLanguage('ar');">
                                <span style="margin-right: 8px;">🇸🇦</span><span>العربية</span>
                            </div>
                        </div>
                    </div>

                    <!-- Get Help - Links to support page -->
                    <a href="/support" class="popup-item" onclick="closeUserMenu();">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Get help</span>
                    </a>

                    <div class="popup-divider"></div>

                    @if($isFree)
                    <a href="/pricing" class="popup-item" onclick="closeUserMenu();">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Upgrade plan</span>
                    </a>
                    @endif

                    <!-- Download BlinkStudy - Dynamic link from admin -->
                    @php
                        $downloadLink = \App\Models\SystemSetting::where('key', 'app_download_link')->first();
                        $downloadUrl = $downloadLink ? $downloadLink->value : '#';
                    @endphp
                    @if($downloadUrl && $downloadUrl !== '#')
                    <a href="{{ $downloadUrl }}" target="_blank" class="popup-item" onclick="closeUserMenu();">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Download {{ config('app.name', 'BlinkStudy') }}</span>
                    </a>
                    @endif

                    <!-- Learn More - Mandatory pages submenu -->
                    <div class="popup-item-with-submenu" onmouseenter="showSubmenu('learnMoreSubmenu')" onmouseleave="hideSubmenuDelayed('learnMoreSubmenu')">
                        <div class="popup-item" onclick="toggleSubmenu('learnMoreSubmenu', event)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Learn more</span>
                            <svg class="w-4 h-4 ml-auto submenu-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                        <div id="learnMoreSubmenu" class="popup-submenu hidden" onmouseenter="keepSubmenuOpen('learnMoreSubmenu')" onmouseleave="hideSubmenu('learnMoreSubmenu')">
                            <a href="/page/about-us" class="popup-item">
                                <span>About Us</span>
                            </a>
                            <a href="/page/terms-and-conditions" class="popup-item">
                                <span>Terms & Conditions</span>
                            </a>
                            <a href="/page/privacy-policy" class="popup-item">
                                <span>Privacy Policy</span>
                            </a>
                            <a href="/page/refund-policy" class="popup-item">
                                <span>Refund Policy</span>
                            </a>
                            <a href="/page/cancellation-policy" class="popup-item">
                                <span>Cancellation Policy</span>
                            </a>
                            <a href="/page/contact-us" class="popup-item">
                                <span>Contact Us</span>
                            </a>
                        </div>
                    </div>

                    <div class="popup-divider"></div>
                    <a href="/logout" class="popup-item text-red-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Log out</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Settings Modal -->
        <div id="settingsModal" class="fixed inset-0 z-50 hidden">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSettings()"></div>

            <!-- Modal Content -->
            <div class="absolute right-0 top-0 bottom-0 w-full max-w-md bg-[var(--bg-primary)] shadow-2xl overflow-y-auto">
                <!-- Header -->
                <div class="sticky top-0 bg-[var(--bg-primary)] border-b border-[var(--border-color)] px-6 py-4 flex items-center justify-between z-10">
                    <h2 class="text-lg font-semibold">Settings</h2>
                    <button onclick="closeSettings()" class="p-2 hover:bg-[var(--bg-hover)] rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6">
                    <!-- Profile Section -->
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--text-secondary)] uppercase tracking-wide mb-3">Profile</h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-4 p-4 bg-[var(--bg-secondary)] rounded-lg">
                                <div class="avatar avatar-user w-12 h-12 text-lg">
                                    {{ auth()->user()->name ? substr(auth()->user()->name, 0, 1) : 'U' }}
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium">{{ auth()->user()->name ?? 'User' }}</div>
                                    <div class="text-sm text-[var(--text-secondary)]">{{ auth()->user()->email ?? '' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plan & Usage Section -->
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--text-secondary)] uppercase tracking-wide mb-3">Plan</h3>
                        <div class="space-y-3">
                            @php
                                $userPlan = auth()->user()->plan;
                                $planName = $userPlan ? $userPlan->name : 'Free';
                                $isFree = !$userPlan || $userPlan->price == 0;
                                $usageService = app(\App\Services\UsageLimitService::class);
                                $usageSummary = $usageService->getUsageSummary(auth()->user());
                            @endphp

                            <div class="flex items-center justify-between p-4 bg-[var(--bg-secondary)] rounded-lg">
                                <div>
                                    <div class="font-medium">{{ $planName }} Plan</div>
                                    <div class="text-sm text-[var(--text-secondary)]">{{ $userPlan ? $userPlan->billing_description : 'Free forever' }}</div>
                                </div>
                                @if($isFree)
                                <a href="{{ route('plans') }}" class="px-4 py-2 bg-[var(--accent-color)] hover:opacity-90 text-white text-sm font-medium rounded-lg transition-colors">
                                    Upgrade
                                </a>
                                @else
                                <span class="px-3 py-1 bg-green-500/20 text-green-500 text-sm font-medium rounded-full">Active</span>
                                @endif
                            </div>

                            @if($isFree)
                            <div class="p-4 bg-[var(--accent-light)] border border-[var(--accent-color)] rounded-lg">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-[var(--accent-color)] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    <div>
                                        <div class="font-medium text-sm">Upgrade your plan</div>
                                        <div class="text-xs text-[var(--text-secondary)] mt-1">Get more quizzes, videos, and priority support.</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Today's Usage -->
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--text-secondary)] uppercase tracking-wide mb-3">Today's Usage</h3>
                        <div class="space-y-2">
                            @php
                                $usageItems = [
                                    'topic_quiz' => ['label' => 'Topic Quiz', 'icon' => 'quiz'],
                                    'video_quiz' => ['label' => 'Video Quiz', 'icon' => 'play_circle'],
                                    'whiteboard_video' => ['label' => 'Whiteboard Video', 'icon' => 'video_library'],
                                    'exam_prep' => ['label' => 'Exam Prep', 'icon' => 'school'],
                                    'scan_solve' => ['label' => 'Scan & Solve', 'icon' => 'document_scanner'],
                                ];
                            @endphp
                            @foreach($usageItems as $key => $item)
                                @if(isset($usageSummary[$key]))
                                    @php
                                        $usage = $usageSummary[$key];
                                        $isUnlimited = $usage['limit'] === 'Unlimited';
                                        $used = $usage['used'];
                                        $limit = $isUnlimited ? null : (int) $usage['limit'];
                                        $pct = (!$isUnlimited && $limit > 0) ? min(100, ($used / $limit) * 100) : 0;
                                        $remaining = $isUnlimited ? 'Unlimited' : $usage['remaining'];
                                    @endphp
                                    <div class="p-3 bg-[var(--bg-secondary)] rounded-lg">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="material-icons text-sm text-[var(--accent-color)]">{{ $item['icon'] }}</span>
                                                <span class="text-sm">{{ $item['label'] }}</span>
                                            </div>
                                            <span class="text-xs font-medium {{ !$isUnlimited && $remaining == 0 ? 'text-red-400' : 'text-[var(--text-secondary)]' }}">
                                                @if($isUnlimited)
                                                    {{ $used }} used
                                                @else
                                                    {{ $used }}/{{ $limit }}
                                                @endif
                                            </span>
                                        </div>
                                        @if(!$isUnlimited && $limit > 0)
                                        <div class="w-full bg-[var(--bg-hover)] rounded-full h-1.5 overflow-hidden">
                                            <div class="h-full rounded-full transition-all {{ $pct >= 100 ? 'bg-red-400' : ($pct >= 75 ? 'bg-yellow-400' : 'bg-[var(--accent-color)]') }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- Preferences -->
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--text-secondary)] uppercase tracking-wide mb-3">Preferences</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-4 bg-[var(--bg-secondary)] rounded-lg">
                                <div>
                                    <div class="font-medium text-sm">Dark Mode</div>
                                    <div class="text-xs text-[var(--text-secondary)]">Toggle dark/light theme</div>
                                </div>
                                <button id="themeToggleBtn" onclick="toggleTheme(); updateThemeToggle()" class="toggle-switch {{ (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'active' : '' }}"></button>
                            </div>
                        </div>
                    </div>

                    <!-- Account Actions -->
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--text-secondary)] uppercase tracking-wide mb-3">Account</h3>
                        <div class="space-y-2">
                            <a href="{{ route('user.settings') }}" class="flex items-center justify-between p-4 bg-[var(--bg-secondary)] rounded-lg hover:bg-[var(--bg-hover)] transition-colors">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-[var(--text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="text-sm font-medium">Edit Profile</span>
                                </div>
                                <svg class="w-5 h-5 text-[var(--text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>

                            <a href="{{ route('logout') }}" class="flex items-center justify-between p-4 bg-[var(--bg-secondary)] rounded-lg hover:bg-red-500/10 transition-colors group">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    <span class="text-sm font-medium text-red-500">Sign Out</span>
                                </div>
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="pt-6 border-t border-[var(--border-color)]">
                        <div class="text-xs text-[var(--text-secondary)] text-center">
                            {{ config('app.name', 'BlinkStudy') }} AI v1.0
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Chat Area -->
        <main class="flex-1 flex flex-col bg-[var(--bg-primary)]">
            <!-- Messages Area -->
            <div id="chatMessages" class="flex-1 overflow-y-auto scrollbar-thin">
                <div id="messagesContainer" class="max-w-3xl mx-auto px-4 py-6">
                    <!-- Welcome Section removed -->

                    <!-- Messages will be loaded here -->
                </div>
            </div>

            <!-- Input Area -->
            <div class="flex-shrink-0 px-4 pb-4 pt-2">
                <div class="max-w-3xl mx-auto">
                    <!-- Hidden inputs for state management -->
                    <input type="hidden" id="selectedAiModel" name="model_id" value="{{ $selectedAiModel->id ?? '' }}">

                    <!-- React Prompt Input Component Mount Point -->
                    <div
                        id="react-prompt-input"
                        data-csrf="{{ csrf_token() }}"
                        data-endpoint="/api/chat/send"
                        data-placeholder="Message {{ config('app.name', 'BlinkStudy') }}..."
                        style="min-height: 60px; border-radius: 24px; background: var(--bg-secondary); border: 1px solid var(--border-color);"
                    >
                        <div style="padding: 16px; color: var(--text-secondary); text-align: center;">Loading...</div>
                    </div>

                    <p class="text-xs text-center mt-2 text-[var(--text-secondary)]">
                        {{ config('app.name', 'BlinkStudy') }} can make mistakes. Consider checking important information.
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // State
        let currentChatId = null;
        let isGenerating = false;
        let selectedImage = null;
        let fileName = '';

        // Global chat mode (set by React component)
        window.currentChatMode = 'default'; // 'default', 'search', 'think', 'canvas'

        // Function to update chat mode (called from React)
        window.setChatMode = function(mode) {
            window.currentChatMode = mode;
            console.log('Chat mode set to:', mode);
        };

        // DOM Elements
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const chatForm = document.getElementById('chatForm');
        const chatMessages = document.getElementById('chatMessages');
        const messagesContainer = document.getElementById('messagesContainer');
        const welcomeSection = document.getElementById('welcomeSection');
        const sidebar = document.getElementById('sidebar');

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadChatHistory();
            loadCurrentChat();

            // Event delegation for action buttons
            document.getElementById('messagesContainer').addEventListener('click', function(e) {
                const copyBtn = e.target.closest('[data-action="copy"]');
                const likeBtn = e.target.closest('[data-action="like"]');
                const dislikeBtn = e.target.closest('[data-action="dislike"]');
                const retryBtn = e.target.closest('[data-action="retry"]');

                if (copyBtn) {
                    e.preventDefault();
                    handleCopyMessage(copyBtn);
                } else if (likeBtn) {
                    e.preventDefault();
                    handleLikeMessage(likeBtn, 'up');
                } else if (dislikeBtn) {
                    e.preventDefault();
                    handleLikeMessage(dislikeBtn, 'down');
                } else if (retryBtn) {
                    e.preventDefault();
                    handleRetryMessage(retryBtn);
                }
            });

            // Setup React PromptInputBox integration
            window.handleChatSend = async (message, files) => {
                if (isGenerating) return;

                // Hide welcome section
                const welcomeEl = document.getElementById('welcomeSection');
                if (welcomeEl) welcomeEl.classList.add('hidden');

                // Check if this is a Canvas (image generation) request
                const isCanvasRequest = message.startsWith('[Canvas: ') && message.endsWith(']');
                let actualMessage = message;

                if (isCanvasRequest) {
                    actualMessage = message.slice(9, -1); // Extract the actual prompt
                    console.log('Canvas mode detected, generating image for:', actualMessage);
                }

                // Use the file from React component
                const imageFile = files && files.length > 0 ? files[0] : null;

                // Add user message to UI
                const userMessage = {
                    role: 'user',
                    content: message || (imageFile ? '[Image uploaded]' : '')
                };

                // If image exists, show it in the message
                if (imageFile) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        addMessage({
                            ...userMessage,
                            image: e.target.result
                        });
                    };
                    reader.readAsDataURL(imageFile);
                } else {
                    addMessage(userMessage);
                }

                // Show loading state
                isGenerating = true;
                if (window.setPromptLoading) {
                    window.setPromptLoading(true);
                }
                showTypingIndicator();

                try {
                    // Handle Canvas mode - Image Generation
                    if (isCanvasRequest) {
                        console.log('Sending image generation request');

                        // First create a chat if needed
                        if (!currentChatId) {
                            const chatResponse = await fetch('/api/chat/send', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    message: 'Starting image generation session',
                                    model_id: document.getElementById('selectedAiModel').value
                                })
                            });
                            const chatData = await chatResponse.json();
                            if (chatData.chat_id) {
                                currentChatId = chatData.chat_id;
                            }
                        }

                        const response = await fetch('/api/generate-image', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                prompt: actualMessage,
                                chat_id: currentChatId || 0
                            })
                        });

                        const data = await response.json();
                        console.log('Image generation response:', data);

                        removeTypingIndicator();

                        if (data.success && data.image_url) {
                            // Show the generated image
                            addMessage({
                                role: 'assistant',
                                content: `🎨 **Generated Image**\n\nBased on your request: *"${actualMessage}"*\n\n![Generated Image](${data.image_url})`,
                                image_url: data.image_url
                            });
                        } else {
                            // Show error with helpful message
                            const errorMsg = data.error || 'Failed to generate image';
                            const allowedTopics = data.allowed_topics ? `\n\n**Allowed topics:** ${data.allowed_topics.join(', ')}` : '';
                            addMessage({
                                role: 'assistant',
                                content: `⚠️ **Image Generation Failed**\n\n${errorMsg}${allowedTopics}\n\n${data.message || ''}`
                            });
                        }

                        // Reset canvas mode
                        window.currentChatMode = 'default';
                        loadChatHistory();
                        return;
                    }

                    // Regular chat message
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content);
                    formData.append('message', actualMessage || (imageFile ? 'Analyze this image' : ''));
                    formData.append('chat_id', currentChatId || '');
                    formData.append('model_id', document.getElementById('selectedAiModel').value);

                    // Add image if provided
                    if (imageFile) {
                        console.log('Appending file to FormData:', {
                            name: imageFile.name,
                            size: imageFile.size,
                            type: imageFile.type
                        });
                        formData.append('file', imageFile);
                    }

                    console.log('Sending request to /api/chat/send');
                    const response = await fetch('/api/chat/send', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    console.log('Response status:', response.status);
                    const data = await response.json();
                    console.log('Response data:', data);

                    if (data.success) {
                        removeTypingIndicator();
                        addMessage({
                            role: 'assistant',
                            content: data.response
                        });

                        // Check if this is a quiz response
                        checkAndStartQuiz(data.response);

                        if (data.chat_id) {
                            currentChatId = data.chat_id;
                            window.history.replaceState({}, '', `?chat=${data.chat_id}`);
                        }

                        // Reload chat history
                        loadChatHistory();
                    } else {
                        console.error('Server returned error:', data);
                        removeTypingIndicator();
                        showError(data.message || 'Failed to send message');
                    }
                } catch (error) {
                    console.error('Request failed:', error);
                    removeTypingIndicator();
                    showError('Failed to send message. Please try again.');
                } finally {
                    isGenerating = false;
                    if (window.setPromptLoading) {
                        window.setPromptLoading(false);
                    }
                    // Reset mode after sending
                    window.currentChatMode = 'default';
                }
            };
        });

        // Toggle Sidebar
        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
        }

        // User Menu Functions
        window.toggleUserMenu = function() {
            const menu = document.getElementById('userPopupMenu');
            menu.classList.toggle('hidden');
        }

        window.closeUserMenu = function() {
            const menu = document.getElementById('userPopupMenu');
            menu.classList.add('hidden');
            // Also close submenus
            document.querySelectorAll('.popup-submenu').forEach(el => {
                el.classList.add('hidden');
            });
        }

        // Close user menu when clicking outside
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('userPopupMenu');
            const userSection = document.querySelector('.sidebar-user');
            if (menu && userSection && !userSection.contains(e.target)) {
                menu.classList.add('hidden');
                // Also close submenus
                document.getElementById('languageSubmenu')?.classList.add('hidden');
                document.getElementById('learnMoreSubmenu')?.classList.add('hidden');
            }
        });

        // Submenu hover functions
        let submenuTimeout = null;

        function showSubmenu(id) {
            clearTimeout(submenuTimeout);
            // Hide other submenus
            document.querySelectorAll('.popup-submenu').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            document.getElementById(id)?.classList.remove('hidden');
        }

        function hideSubmenuDelayed(id) {
            submenuTimeout = setTimeout(() => {
                document.getElementById(id)?.classList.add('hidden');
            }, 150);
        }

        function keepSubmenuOpen(id) {
            clearTimeout(submenuTimeout);
        }

        function hideSubmenu(id) {
            submenuTimeout = setTimeout(() => {
                document.getElementById(id)?.classList.add('hidden');
            }, 100);
        }

        // Position and show submenu
        function positionSubmenu(submenu, trigger) {
            const popup = document.getElementById('userPopupMenu');
            const popupRect = popup.getBoundingClientRect();
            const triggerRect = trigger.getBoundingClientRect();

            // Position to the right of the popup menu
            const left = popupRect.right + 8;
            const top = triggerRect.top;

            // Check if it would go off-screen on the right
            const submenuWidth = 200;
            if (left + submenuWidth > window.innerWidth) {
                // Position to the left of the popup instead
                submenu.style.left = (popupRect.left - submenuWidth - 8) + 'px';
            } else {
                submenu.style.left = left + 'px';
            }

            // Adjust vertical position if needed
            const submenuHeight = submenu.offsetHeight || 300;
            if (top + submenuHeight > window.innerHeight) {
                submenu.style.top = Math.max(10, window.innerHeight - submenuHeight - 10) + 'px';
            } else {
                submenu.style.top = top + 'px';
            }
        }

        // Toggle submenu on click (for mobile/touch)
        window.toggleSubmenu = function(id, event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            console.log('toggleSubmenu called:', id);
            const submenu = document.getElementById(id);
            if (!submenu) {
                console.error('Submenu not found:', id);
                return;
            }
            const isHidden = submenu.classList.contains('hidden');
            console.log('Is hidden:', isHidden);

            // Hide all submenus first
            document.querySelectorAll('.popup-submenu').forEach(el => {
                el.classList.add('hidden');
            });

            // Toggle the clicked one
            if (isHidden) {
                submenu.classList.remove('hidden');
                // Position the submenu
                const trigger = event.currentTarget || event.target.closest('.popup-item');
                positionSubmenu(submenu, trigger);
                console.log('Showing submenu');
            }
        };

        // Make other functions global too
        window.showSubmenu = function(id, event) {
            clearTimeout(submenuTimeout);
            document.querySelectorAll('.popup-submenu').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            const submenu = document.getElementById(id);
            if (submenu) {
                submenu.classList.remove('hidden');
                // Find the trigger element
                const triggerItem = document.querySelector(`[onmouseenter*="${id}"]`);
                if (triggerItem) {
                    const trigger = triggerItem.querySelector('.popup-item');
                    positionSubmenu(submenu, trigger || triggerItem);
                }
            }
        };

        window.hideSubmenuDelayed = function(id) {
            submenuTimeout = setTimeout(() => {
                document.getElementById(id)?.classList.add('hidden');
            }, 150);
        };

        window.keepSubmenuOpen = function(id) {
            clearTimeout(submenuTimeout);
        };

        window.hideSubmenu = function(id) {
            submenuTimeout = setTimeout(() => {
                document.getElementById(id)?.classList.add('hidden');
            }, 100);
        };

        // Set Language
        window.setLanguage = function(lang) {
            // Save preference
            localStorage.setItem('language', lang);
            // Send to server
            fetch('/api/user/language', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ language: lang })
            }).then(() => {
                // Reload page to apply language
                window.location.reload();
            }).catch(err => {
                console.log('Language preference saved locally');
                window.location.reload();
            });
            closeUserMenu();
        }

        // Image Upload Functions
        function handleImageSelect(event) {
            event.preventDefault();
            event.stopPropagation();

            const file = event.target.files[0];
            if (!file) return;

            console.log('File selected:', {
                name: file.name,
                type: file.type,
                size: file.size
            });

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                console.error('Invalid file type:', file.type);
                showError('Please select a valid image file (JPG, PNG, GIF, WebP)');
                return;
            }

            // Validate file size (max 25MB to match php.ini)
            const maxSize = 25 * 1024 * 1024; // 25MB
            if (file.size > maxSize) {
                console.error('File too large:', file.size);
                showError('Image size must be less than 25MB (your file is ' + (file.size / 1024 / 1024).toFixed(2) + 'MB)');
                return;
            }

            // Store the file and filename
            selectedImage = file;
            fileName = file.name;

            // Show preview
            const reader = new FileReader();
            reader.onload = (e) => {
                console.log('File preview loaded');
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('imagePreviewContainer').classList.remove('hidden');

                // Update filename display
                const fileNameEl = document.getElementById('imageFileName');
                if (fileNameEl) {
                    fileNameEl.textContent = fileName;
                }
            };
            reader.onerror = (error) => {
                console.error('FileReader error:', error);
                showError('Failed to read image file');
            };
            reader.readAsDataURL(file);

            // Enable send button if there's an image even without text
            checkInput();
        }

        function removeImage() {
            selectedImage = null;
            fileName = '';
            document.getElementById('imageInput').value = '';
            document.getElementById('imagePreviewContainer').classList.add('hidden');
            checkInput();
        }

        // Toggle Theme
        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }

        // Settings Modal
        function openSettings() {
            document.getElementById('settingsModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeSettings() {
            document.getElementById('settingsModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Update theme toggle button appearance
        function updateThemeToggle() {
            const toggleBtn = document.getElementById('themeToggleBtn');
            if (document.documentElement.classList.contains('dark')) {
                toggleBtn.classList.add('active');
            } else {
                toggleBtn.classList.remove('active');
            }
        }

        // Initialize theme toggle on page load
        document.addEventListener('DOMContentLoaded', () => {
            updateThemeToggle();
        });

        // Close settings on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeSettings();
            }
        });

        // Auto Resize Textarea
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
        }

        // Check Input
        function checkInput() {
            sendButton.disabled = !messageInput.value.trim() && !selectedImage;
        }

        // Handle Key Down
        function handleKeyDown(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                if (!sendButton.disabled) {
                    chatForm.dispatchEvent(new Event('submit'));
                }
            }
        }

        // Send Suggestion
        function sendSuggestion(text) {
            messageInput.value = text;
            checkInput();
            chatForm.dispatchEvent(new Event('submit'));
        }

        // Load Chat History
        async function loadChatHistory() {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const response = await fetch('/api/chat/history', {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success && data.chats.length > 0) {
                    const chatHistory = document.getElementById('chatHistory');
                    chatHistory.innerHTML = data.chats.map(chat => `
                        <div class="chat-item ${chat.id === currentChatId ? 'active' : ''}" onclick="loadChat(${chat.id})">
                            <div class="text-sm font-medium truncate">${escapeHTML(chat.title)}</div>
                            <div class="text-xs text-[var(--text-secondary)] mt-1">${formatTime(chat.updated_at)}</div>
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Failed to load chat history:', error);
            }
        }

        // Load Current Chat
        async function loadCurrentChat() {
            const urlParams = new URLSearchParams(window.location.search);
            const chatId = urlParams.get('chat');

            if (chatId) {
                await loadChat(chatId);
            }
        }

        // Load Chat
        async function loadChat(chatId) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const response = await fetch(`/api/chat/${chatId}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    currentChatId = chatId;
                    welcomeSection.classList.add('hidden');
                    messagesContainer.innerHTML = data.messages.map(msg => createMessageHTML(msg)).join('');
                    scrollToBottom();
                    updateActiveChatItem(chatId);

                    // Update URL
                    window.history.replaceState({}, '', `?chat=${chatId}`);
                }
            } catch (error) {
                console.error('Failed to load chat:', error);
            }
        }

        // Create New Chat
        function createNewChat() {
            currentChatId = null;
            welcomeSection.classList.remove('hidden');
            messagesContainer.innerHTML = welcomeSection.outerHTML;
            updateActiveChatItem(null);
            messageInput.value = '';
            messageInput.focus();
            window.history.replaceState({}, '', window.location.pathname);
        }

        // Search chats
        function openSearch() {
            const searchQuery = prompt('Search chats:');
            if (searchQuery && searchQuery.trim()) {
                const chatItems = document.querySelectorAll('.chat-item');
                chatItems.forEach(item => {
                    const title = item.querySelector('.chat-title')?.textContent?.toLowerCase() || '';
                    if (title.includes(searchQuery.toLowerCase())) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            } else {
                // Show all if empty search
                const chatItems = document.querySelectorAll('.chat-item');
                chatItems.forEach(item => item.style.display = 'flex');
            }
        }

        // Submit Form
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const message = messageInput.value.trim();
            if ((!message && !selectedImage) || isGenerating) return;

            // Hide welcome section
            const welcomeEl = document.getElementById('welcomeSection');
            if (welcomeEl) welcomeEl.classList.add('hidden');

            // Prepare form data
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content);
            formData.append('message', message || 'Analyze this image');
            formData.append('chat_id', currentChatId || '');
            formData.append('model_id', document.getElementById('selectedAiModel').value);

            // Add image if selected
            if (selectedImage) {
                console.log('Appending file to FormData:', {
                    name: selectedImage.name,
                    size: selectedImage.size,
                    type: selectedImage.type
                });
                formData.append('file', selectedImage);
            }

            // Add user message to UI
            const userMessage = {
                role: 'user',
                content: message || (selectedImage ? '[Image uploaded]' : '')
            };

            // If image exists, show it in the message
            if (selectedImage) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    addMessage({
                        ...userMessage,
                        image: e.target.result
                    });
                };
                reader.readAsDataURL(selectedImage);
            } else {
                addMessage(userMessage);
            }

            // Clear input and image
            messageInput.value = '';
            messageInput.style.height = 'auto';
            const tempImage = selectedImage;
            selectedImage = null;
            document.getElementById('imageInput').value = '';
            document.getElementById('imagePreviewContainer').classList.add('hidden');
            sendButton.disabled = true;

            // Show loading
            isGenerating = true;
            showTypingIndicator();

            try {
                console.log('Sending request to /api/chat/send');
                const response = await fetch('/api/chat/send', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                console.log('Response status:', response.status);
                const data = await response.json();
                console.log('Response data:', data);

                if (data.success) {
                    removeTypingIndicator();

                    // CRITICAL: Validate response is not empty
                    const responseContent = data.response || '';
                    if (!responseContent || responseContent.trim().length < 10) {
                        console.error('Empty or invalid response received:', data);
                        showError('Response was empty. Please try again.');
                        // Restore image if failed
                        if (tempImage) {
                            selectedImage = tempImage;
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                document.getElementById('imagePreview').src = e.target.result;
                                document.getElementById('imagePreviewContainer').classList.remove('hidden');
                            };
                            reader.readAsDataURL(tempImage);
                        }
                        return;
                    }

                    addMessage({
                        role: 'assistant',
                        content: responseContent
                    });

                    // Check if this is a quiz response
                    checkAndStartQuiz(responseContent);

                    if (data.chat_id) {
                        currentChatId = data.chat_id;
                        window.history.replaceState({}, '', `?chat=${data.chat_id}`);
                    }

                    // Reload chat history
                    loadChatHistory();
                } else {
                    console.error('Server returned error:', data);
                    removeTypingIndicator();
                    showError(data.message || data.error || 'Failed to send message');

                    // Restore image if failed
                    if (tempImage) {
                        selectedImage = tempImage;
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            document.getElementById('imagePreview').src = e.target.result;
                            document.getElementById('imagePreviewContainer').classList.remove('hidden');
                        };
                        reader.readAsDataURL(tempImage);
                    }
                }
            } catch (error) {
                console.error('Request failed:', error);
                removeTypingIndicator();
                showError('Failed to send message. Please try again.');

                // Restore image if failed
                if (tempImage) {
                    selectedImage = tempImage;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        document.getElementById('imagePreview').src = e.target.result;
                        document.getElementById('imagePreviewContainer').classList.remove('hidden');
                    };
                    reader.readAsDataURL(tempImage);
                }
            } finally {
                isGenerating = false;
            }
        });

        // Add Message
        function addMessage(message) {
            const messageHTML = createMessageHTML(message);
            messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
            scrollToBottom();
        }

        // Create Message HTML
        function createMessageHTML(message) {
            if (message.role === 'user') {
                let imageHTML = '';
                if (message.image) {
                    imageHTML = `<img src="${message.image}" class="max-w-full max-h-64 rounded-lg mt-2 border border-[var(--border-color)]" alt="Uploaded image">`;
                }

                // Check for mode prefix in message
                let displayContent = message.content || '';
                let modeBadge = '';

                if (displayContent.startsWith('[Search: ') && displayContent.endsWith(']')) {
                    displayContent = displayContent.slice(9, -1);
                    modeBadge = `
                        <div class="message-mode-badge web-search">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            <span>Web Search</span>
                        </div>
                    `;
                } else if (displayContent.startsWith('[Think: ') && displayContent.endsWith(']')) {
                    displayContent = displayContent.slice(8, -1);
                    modeBadge = `
                        <div class="message-mode-badge thinking">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            <span>Deep Think</span>
                        </div>
                    `;
                } else if (displayContent.startsWith('[Canvas: ') && displayContent.endsWith(']')) {
                    displayContent = displayContent.slice(9, -1);
                    modeBadge = `
                        <div class="message-mode-badge" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                            <span>Canvas</span>
                        </div>
                    `;
                }

                return `
                    <div class="message-user">
                        <div class="user-bubble">
                            ${modeBadge}
                            ${displayContent ? `<div class="whitespace-pre-wrap">${escapeHTML(displayContent)}</div>` : ''}
                            ${imageHTML}
                        </div>
                    </div>
                `;
            } else {
                // Check for generated image
                let aiImageHTML = '';
                if (message.image_url) {
                    aiImageHTML = `
                        <div class="generated-image-container" style="margin: 16px 0;">
                            <img src="${message.image_url}"
                                 alt="Generated Image"
                                 class="generated-image"
                                 style="max-width: 100%; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer;"
                                 onclick="window.open('${message.image_url}', '_blank')">
                            <div style="margin-top: 8px; display: flex; gap: 8px;">
                                <a href="${message.image_url}"
                                   download="blinkstudy-generated-image.png"
                                   class="action-btn"
                                   style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: var(--bg-hover); border-radius: 8px; font-size: 12px; text-decoration: none; color: var(--text-primary);">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Download
                                </a>
                                <button onclick="window.open('${message.image_url}', '_blank')"
                                        class="action-btn"
                                        style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: var(--bg-hover); border-radius: 8px; font-size: 12px;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    Open Full Size
                                </button>
                            </div>
                        </div>
                    `;
                }

                return `
                    <div class="message-ai">
                        <div class="message-content">
                            ${formatMessage(message.content)}
                            ${aiImageHTML}
                        </div>
                        <div class="message-actions">
                            <button class="action-btn" data-action="copy" title="Copy">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                            <button class="action-btn" data-action="like" title="Good response">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                </svg>
                            </button>
                            <button class="action-btn" data-action="dislike" title="Bad response">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                                </svg>
                            </button>
                            <button class="action-btn" data-action="retry" title="Retry">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }
        }

        // Copy Message Handler
        function handleCopyMessage(button) {
            const messageAi = button.closest('.message-ai');
            if (!messageAi) {
                console.error('Message AI container not found');
                return;
            }

            const messageContent = messageAi.querySelector('.message-content');
            if (!messageContent) {
                console.error('Message content not found');
                return;
            }

            const text = messageContent.innerText;
            const originalHTML = button.innerHTML;

            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopySuccess(button, originalHTML);
                }).catch(() => {
                    fallbackCopy(text, button, originalHTML);
                });
            } else {
                fallbackCopy(text, button, originalHTML);
            }
        }

        function showCopySuccess(button, originalHTML) {
            button.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            button.style.color = '#10B981';
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.style.color = '';
            }, 2000);
        }

        function fallbackCopy(text, button, originalHTML) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.cssText = 'position:fixed;opacity:0;left:-9999px';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            try {
                document.execCommand('copy');
                showCopySuccess(button, originalHTML);
            } catch (e) {
                console.error('Copy failed:', e);
                alert('Copy failed. Please select and copy manually.');
            }
            document.body.removeChild(textarea);
        }

        // Like/Dislike Message Handler
        function handleLikeMessage(button, type) {
            const actions = button.closest('.message-actions');
            if (actions) {
                actions.querySelectorAll('.action-btn').forEach(btn => btn.classList.remove('active'));
            }
            button.classList.add('active');
            console.log('Feedback:', type);
        }

        // Retry Message Handler
        function handleRetryMessage(button) {
            const messages = document.querySelectorAll('.message-user');
            if (messages.length > 0) {
                const lastUserMessage = messages[messages.length - 1];
                const text = lastUserMessage.querySelector('.user-bubble')?.innerText || '';
                if (text) {
                    const aiMessage = button.closest('.message-ai');
                    if (aiMessage) aiMessage.remove();
                    console.log('Retry with:', text);
                }
            }
        }

        // Format Message (Markdown support - Claude Professional with Math)
        function formatMessage(content) {
            // Configure marked for professional rendering
            marked.setOptions({
                breaks: true,      // Convert \n to <br>
                gfm: true,         // GitHub Flavored Markdown
                headerIds: false,  // Don't generate IDs
                mangle: false      // Don't mangle email addresses
            });

            try {
                // Parse markdown using marked.js
                let html = marked.parse(content);

                // Sanitize HTML to prevent XSS (basic sanitization)
                html = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
                           .replace(/on\w+="[^"]*"/gi, '')
                           .replace(/javascript:/gi, '');

                // Create a temporary container to render math
                const container = document.createElement('div');
                container.innerHTML = html;

                // Render math using KaTeX if available
                if (typeof renderMathInElement !== 'undefined') {
                    renderMathInElement(container, {
                        delimiters: [
                            {left: '$$', right: '$$', display: true},
                            {left: '$', right: '$', display: false},
                            {left: '\\(', right: '\\)', display: false},
                            {left: '\\[', right: '\\]', display: true}
                        ],
                        throwOnError: false,
                        trust: true
                    });
                }

                return container.innerHTML;
            } catch (error) {
                console.error('Markdown parsing error:', error);
                // Fallback to escaped text if parsing fails
                return escapeHTML(content).replace(/\n/g, '<br>');
            }
        }

        // Escape HTML
        function escapeHTML(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Show Typing Indicator with mode support
        function showTypingIndicator() {
            const currentMode = window.currentChatMode || 'default';
            let modeIndicator = '';
            let typingContent = '';

            if (currentMode === 'search') {
                modeIndicator = `
                    <div class="search-mode-indicator" style="margin-bottom: 8px;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Searching the web...</span>
                    </div>
                `;
                typingContent = `
                    <div class="flex items-center gap-2" style="color: #3B82F6;">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span style="font-size: 14px;">Finding relevant information...</span>
                    </div>
                `;
            } else if (currentMode === 'think') {
                modeIndicator = `
                    <div class="thinking-indicator" style="margin-bottom: 8px;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <span>Thinking</span>
                        <div class="dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                `;
                typingContent = `
                    <div class="flex items-center gap-2" style="color: #A855F7;">
                        <span style="font-size: 14px; font-style: italic;">Analyzing deeply...</span>
                    </div>
                `;
            } else if (currentMode === 'canvas') {
                modeIndicator = `
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 20px; font-size: 12px; color: #10B981; margin-bottom: 8px;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <span>Creating on canvas...</span>
                    </div>
                `;
                typingContent = `
                    <div class="flex items-center gap-2" style="color: #10B981;">
                        <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        <span style="font-size: 14px;">Generating code...</span>
                    </div>
                `;
            } else {
                typingContent = `
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                `;
            }

            const typingHTML = `
                <div id="typingIndicator" class="message-ai">
                    ${modeIndicator}
                    ${typingContent}
                </div>
            `;
            messagesContainer.insertAdjacentHTML('beforeend', typingHTML);
            scrollToBottom();
        }

        // Remove Typing Indicator
        function removeTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) {
                indicator.remove();
            }
        }

        // Scroll To Bottom
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Update Active Chat Item
        function updateActiveChatItem(chatId) {
            document.querySelectorAll('.chat-item').forEach(item => {
                item.classList.remove('active');
            });
        }

        // Format Time
        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMins / 60);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        // Show Error
        function showError(message) {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-red-500 text-white px-4 py-3 rounded-lg shadow-lg z-50 animate-pulse';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // Load theme from localStorage
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.remove('dark');
        }

        // Quiz Data
        const quizExams = [
            { id: 1, name: 'JEE (Joint Entrance Examination)', icon: 'engineering', color: '#cc785c', shortName: 'JEE' },
            { id: 2, name: 'NEET (Medical Entrance)', icon: 'local_hospital', color: '#4a90e2', shortName: 'NEET' },
            { id: 3, name: 'UPSC (Civil Services)', icon: 'account_balance', color: '#95E1D3', shortName: 'UPSC' },
            { id: 4, name: 'SSC (Staff Selection Commission)', icon: 'work', color: '#F38181', shortName: 'SSC' },
            { id: 5, name: 'Banking (IBPS, SBI)', icon: 'account_balance_wallet', color: '#AA96DA', shortName: 'Banking' },
            { id: 6, name: 'Railway Recruitment', icon: 'train', color: '#FCBAD3', shortName: 'Railway' },
            { id: 7, name: 'CAT (MBA Entrance)', icon: 'business_center', color: '#FFD93D', shortName: 'CAT' },
            { id: 8, name: 'GATE (Engineering)', icon: 'school', color: '#6BCB77', shortName: 'GATE' },
        ];

        const quizSubjectsByExam = {
            1: [ // JEE
                { id: 1, name: 'Physics', icon: 'bolt', color: '#cc785c' },
                { id: 2, name: 'Chemistry', icon: 'science', color: '#4a90e2' },
                { id: 3, name: 'Mathematics', icon: 'calculate', color: '#50c878' },
            ],
            2: [ // NEET
                { id: 4, name: 'Physics', icon: 'bolt', color: '#cc785c' },
                { id: 5, name: 'Chemistry', icon: 'science', color: '#4a90e2' },
                { id: 6, name: 'Biology', icon: 'biotech', color: '#AA96DA' },
            ],
            3: [ // UPSC
                { id: 9, name: 'History', icon: 'history_edu', color: '#95E1D3' },
                { id: 10, name: 'Geography', icon: 'public', color: '#F38181' },
                { id: 11, name: 'Polity', icon: 'account_balance', color: '#AA96DA' },
                { id: 12, name: 'Economics', icon: 'trending_up', color: '#FFD93D' },
            ],
            4: [ // SSC
                { id: 15, name: 'General Intelligence', icon: 'psychology', color: '#FF6B6B' },
                { id: 16, name: 'General Awareness', icon: 'lightbulb', color: '#4ECDC4' },
                { id: 17, name: 'Quantitative Aptitude', icon: 'calculate', color: '#95E1D3' },
                { id: 18, name: 'English', icon: 'translate', color: '#F38181' },
            ],
            5: [ // Banking
                { id: 19, name: 'Reasoning Ability', icon: 'psychology', color: '#AA96DA' },
                { id: 20, name: 'Quantitative Aptitude', icon: 'calculate', color: '#FCBAD3' },
                { id: 21, name: 'English Language', icon: 'translate', color: '#FFD93D' },
                { id: 22, name: 'General Awareness', icon: 'lightbulb', color: '#6BCB77' },
            ],
            6: [ // Railway
                { id: 24, name: 'General Science', icon: 'science', color: '#FF9EAA' },
                { id: 25, name: 'Mathematics', icon: 'calculate', color: '#B983FF' },
                { id: 26, name: 'General Intelligence', icon: 'psychology', color: '#94B49F' },
            ],
            7: [ // CAT
                { id: 28, name: 'Quantitative Ability', icon: 'calculate', color: '#4ECDC4' },
                { id: 29, name: 'Verbal Ability', icon: 'chat', color: '#95E1D3' },
                { id: 30, name: 'Data Interpretation', icon: 'bar_chart', color: '#F38181' },
                { id: 31, name: 'Logical Reasoning', icon: 'psychology', color: '#AA96DA' },
            ],
            8: [ // GATE
                { id: 32, name: 'Engineering Mathematics', icon: 'calculate', color: '#FCBAD3' },
                { id: 33, name: 'General Aptitude', icon: 'lightbulb', color: '#FFD93D' },
                { id: 34, name: 'Technical Subject', icon: 'engineering', color: '#6BCB77' },
            ],
        };

        const quizTopicsBySubject = {
            1: [{id: 1, name: 'Mechanics'}, {id: 2, name: 'Thermodynamics'}, {id: 3, name: 'Electromagnetism'}, {id: 4, name: 'Optics'}, {id: 5, name: 'Modern Physics'}],
            2: [{id: 6, name: 'Organic Chemistry'}, {id: 7, name: 'Inorganic Chemistry'}, {id: 8, name: 'Physical Chemistry'}, {id: 9, name: 'Chemical Bonding'}],
            3: [{id: 11, name: 'Algebra'}, {id: 12, name: 'Calculus'}, {id: 13, name: 'Trigonometry'}, {id: 14, name: 'Coordinate Geometry'}, {id: 15, name: 'Probability'}],
            4: [{id: 1, name: 'Mechanics'}, {id: 2, name: 'Thermodynamics'}, {id: 3, name: 'Electromagnetism'}, {id: 4, name: 'Optics'}, {id: 5, name: 'Modern Physics'}],
            5: [{id: 6, name: 'Organic Chemistry'}, {id: 7, name: 'Inorganic Chemistry'}, {id: 8, name: 'Physical Chemistry'}, {id: 9, name: 'Chemical Bonding'}],
            6: [{id: 17, name: 'Cell Biology'}, {id: 18, name: 'Genetics'}, {id: 19, name: 'Evolution'}, {id: 20, name: 'Ecology'}, {id: 21, name: 'Human Physiology'}],
            9: [{id: 30, name: 'Ancient India'}, {id: 31, name: 'Medieval India'}, {id: 32, name: 'Modern India'}, {id: 33, name: 'World History'}, {id: 34, name: 'Art & Culture'}],
            10: [{id: 35, name: 'Physical Geography'}, {id: 36, name: 'Indian Geography'}, {id: 37, name: 'World Geography'}, {id: 38, name: 'Economic Geography'}],
            11: [{id: 40, name: 'Constitution'}, {id: 41, name: 'Parliament'}, {id: 42, name: 'Judiciary'}, {id: 43, name: 'Fundamental Rights'}, {id: 44, name: 'Governance'}],
            12: [{id: 45, name: 'Microeconomics'}, {id: 46, name: 'Macroeconomics'}, {id: 47, name: 'Indian Economy'}, {id: 48, name: 'Banking'}],
            15: [{id: 60, name: 'Logical Reasoning'}, {id: 61, name: 'Analytical Reasoning'}, {id: 62, name: 'Verbal Reasoning'}, {id: 63, name: 'Non-Verbal Reasoning'}],
            16: [{id: 64, name: 'Indian History'}, {id: 65, name: 'Geography'}, {id: 66, name: 'Polity'}, {id: 67, name: 'Economy'}, {id: 68, name: 'General Science'}],
            17: [{id: 69, name: 'Number System'}, {id: 70, name: 'Percentage'}, {id: 71, name: 'Profit & Loss'}, {id: 72, name: 'Time & Work'}, {id: 73, name: 'Data Interpretation'}],
            18: [{id: 74, name: 'Grammar'}, {id: 75, name: 'Vocabulary'}, {id: 76, name: 'Comprehension'}, {id: 77, name: 'Error Detection'}],
            19: [{id: 60, name: 'Logical Reasoning'}, {id: 61, name: 'Analytical Reasoning'}, {id: 62, name: 'Verbal Reasoning'}],
            20: [{id: 69, name: 'Number System'}, {id: 70, name: 'Percentage'}, {id: 71, name: 'Profit & Loss'}, {id: 72, name: 'Time & Work'}, {id: 73, name: 'Data Interpretation'}],
            21: [{id: 74, name: 'Grammar'}, {id: 75, name: 'Vocabulary'}, {id: 76, name: 'Comprehension'}, {id: 77, name: 'Error Correction'}],
            22: [{id: 64, name: 'Banking Awareness'}, {id: 65, name: 'Current Affairs'}, {id: 66, name: 'General Knowledge'}],
            24: [{id: 83, name: 'Physics'}, {id: 84, name: 'Chemistry'}, {id: 85, name: 'Biology'}, {id: 86, name: 'Environmental Science'}],
            25: [{id: 11, name: 'Algebra'}, {id: 12, name: 'Calculus'}, {id: 13, name: 'Trigonometry'}, {id: 14, name: 'Coordinate Geometry'}, {id: 15, name: 'Probability'}],
            26: [{id: 60, name: 'Logical Reasoning'}, {id: 61, name: 'Analytical Reasoning'}],
            28: [{id: 69, name: 'Number System'}, {id: 70, name: 'Percentage'}, {id: 71, name: 'Profit & Loss'}, {id: 73, name: 'Geometry'}],
            29: [{id: 87, name: 'Reading Comprehension'}, {id: 88, name: 'Para Jumbles'}, {id: 89, name: 'Grammar'}, {id: 90, name: 'Vocabulary'}],
            30: [{id: 91, name: 'Tables'}, {id: 92, name: 'Bar Graphs'}, {id: 93, name: 'Pie Charts'}, {id: 94, name: 'Line Graphs'}, {id: 95, name: 'Caselets'}],
            31: [{id: 60, name: 'Logical Reasoning'}, {id: 61, name: 'Analytical Reasoning'}, {id: 62, name: 'Critical Reasoning'}],
            32: [{id: 96, name: 'Linear Algebra'}, {id: 97, name: 'Calculus'}, {id: 98, name: 'Differential Equations'}, {id: 99, name: 'Probability'}],
            33: [{id: 100, name: 'Verbal Ability'}, {id: 101, name: 'Numerical Ability'}, {id: 102, name: 'Logical Reasoning'}],
            34: [{id: 103, name: 'Core Engineering'}, {id: 104, name: 'Specialized Topics'}, {id: 105, name: 'Practical Applications'}],
        };

        // Quiz Modal Functions
        let quizState = {
            activeTab: 'topic', // 'topic' or 'scan'
            selectedExam: null,
            selectedSubject: null,
            selectedTopic: null,
            selectedLanguage: null,
            selectedDuration: null,
            selectedFile: null
        };

        // Quiz Taking State
        let quizTakingState = {
            questions: [],
            currentQuestionIndex: 0,
            userAnswers: [],
            isGeneratingQuiz: false
        };

        function openQuizModal() {
            console.log('Opening quiz modal...');
            const modal = document.getElementById('quizModal');
            if (!modal) {
                console.error('Quiz modal element not found!');
                return;
            }
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            console.log('Quiz modal opened');
        }

        function closeQuizModal() {
            const modal = document.getElementById('quizModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
            // Reset quiz state
            quizState = {
                activeTab: 'topic',
                selectedExam: null,
                selectedSubject: null,
                selectedTopic: null,
                selectedLanguage: null,
                selectedDuration: null,
                selectedFile: null
            };
            renderQuizContent();
        }

        function switchQuizTab(tab) {
            quizState.activeTab = tab;
            renderQuizContent();
        }

        function selectExam(examId) {
            quizState.selectedExam = examId;
            quizState.selectedSubject = null;
            quizState.selectedTopic = null;
            renderQuizContent();
        }

        function selectSubject(subjectId) {
            quizState.selectedSubject = subjectId;
            quizState.selectedTopic = null;
            renderQuizContent();
        }

        function selectTopic(topicId) {
            quizState.selectedTopic = topicId;
            renderQuizContent();
        }

        function selectLanguage(language) {
            quizState.selectedLanguage = language;
            renderQuizContent();
        }

        function selectDuration(duration) {
            quizState.selectedDuration = duration;
            renderQuizContent();
        }

        function renderQuizContent() {
            const container = document.getElementById('quizModalBody');
            if (!container) {
                console.error('Quiz modal body not found!');
                return;
            }

            const tabs = document.querySelectorAll('.quiz-tab');

            // Update tab states
            tabs.forEach(tab => {
                if (tab.dataset.tab === quizState.activeTab) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });

            if (quizState.activeTab === 'scan') {
                container.innerHTML = `
                    <div class="file-upload-area" onclick="document.getElementById('quizFileInput').click()">
                        <div class="file-upload-icon">
                            <span class="material-icons" style="font-size: 32px; color: var(--accent-color);">upload_file</span>
                        </div>
                        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Upload your notes</h3>
                        <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 12px;">Upload images or PDF documents to generate quiz</p>
                        <p style="font-size: 12px; color: var(--text-secondary);">Supports: JPG, PNG, PDF (Max 10MB)</p>
                    </div>
                    <input type="file" id="quizFileInput" accept="image/*,.pdf" style="display: none;" onchange="handleQuizFileSelect(event)">
                    <button class="quiz-generate-btn" disabled>
                        <span class="material-icons">quiz</span>
                        Generate Quiz from Notes
                    </button>
                `;
            } else {
                // Topic-based quiz
                let html = '';

                // Step 1: Exam Selection
                html += `<div class="selection-step">
                    <div class="selection-step-title">
                        <span class="material-icons" style="font-size: 20px; color: var(--accent-color);">school</span>
                        Select Exam
                    </div>
                    <div class="selection-grid">`;

                quizExams.forEach(exam => {
                    const selected = quizState.selectedExam === exam.id ? 'selected' : '';
                    html += `
                        <div class="selection-card ${selected}" onclick="selectExam(${exam.id})">
                            <div class="selection-card-icon" style="background: ${exam.color}20;">
                                <span class="material-icons" style="color: ${exam.color};">${exam.icon}</span>
                            </div>
                            <div class="selection-card-content">
                                <div class="selection-card-title">${exam.shortName}</div>
                                <div class="selection-card-subtitle">${exam.name.split('(')[0]}</div>
                            </div>
                        </div>
                    `;
                });

                html += `</div></div>`;

                // Step 2: Subject Selection (if exam selected)
                if (quizState.selectedExam) {
                    const subjects = quizSubjectsByExam[quizState.selectedExam] || [];
                    html += `<div class="selection-step">
                        <div class="selection-step-title">
                            <span class="material-icons" style="font-size: 20px; color: var(--accent-color);">menu_book</span>
                            Select Subject
                        </div>
                        <div class="selection-grid">`;

                    subjects.forEach(subject => {
                        const selected = quizState.selectedSubject === subject.id ? 'selected' : '';
                        html += `
                            <div class="selection-card ${selected}" onclick="selectSubject(${subject.id})">
                                <div class="selection-card-icon" style="background: ${subject.color}20;">
                                    <span class="material-icons" style="color: ${subject.color};">${subject.icon}</span>
                                </div>
                                <div class="selection-card-content">
                                    <div class="selection-card-title">${subject.name}</div>
                                </div>
                            </div>
                        `;
                    });

                    html += `</div></div>`;
                }

                // Step 3: Topic Selection (if subject selected)
                if (quizState.selectedSubject) {
                    const topics = quizTopicsBySubject[quizState.selectedSubject] || [];
                    html += `<div class="selection-step">
                        <div class="selection-step-title">
                            <span class="material-icons" style="font-size: 20px; color: var(--accent-color);">topic</span>
                            Select Topic
                        </div>
                        <div class="selection-grid">`;

                    topics.forEach(topic => {
                        const selected = quizState.selectedTopic === topic.id ? 'selected' : '';
                        html += `
                            <div class="selection-card ${selected}" onclick="selectTopic(${topic.id})">
                                <div class="selection-card-content" style="padding: 4px 0;">
                                    <div class="selection-card-title">${topic.name}</div>
                                </div>
                            </div>
                        `;
                    });

                    html += `</div></div>`;
                }

                // Step 4: Language & Duration (if topic selected)
                if (quizState.selectedTopic) {
                    html += `<div class="selection-step">
                        <div class="selection-step-title">
                            <span class="material-icons" style="font-size: 20px; color: var(--accent-color);">language</span>
                            Select Language
                        </div>
                        <div class="selection-grid">`;

                    ['English', 'Hindi', 'Hinglish'].forEach(lang => {
                        const selected = quizState.selectedLanguage === lang ? 'selected' : '';
                        html += `
                            <div class="selection-card ${selected}" onclick="selectLanguage('${lang}')">
                                <div class="selection-card-icon" style="background: rgba(74, 144, 226, 0.1);">
                                    <span class="material-icons" style="color: #4a90e2;">translate</span>
                                </div>
                                <div class="selection-card-content">
                                    <div class="selection-card-title">${lang}</div>
                                </div>
                            </div>
                        `;
                    });

                    html += `</div></div>`;

                    html += `<div class="selection-step">
                        <div class="selection-step-title">
                            <span class="material-icons" style="font-size: 20px; color: var(--accent-color);">timer</span>
                            Select Duration
                        </div>
                        <div class="selection-grid">`;

                    [{value: 5, name: '5 Minutes'}, {value: 10, name: '10 Minutes'}, {value: 15, name: '15 Minutes'}, {value: 20, name: '20 Minutes'}].forEach(duration => {
                        const selected = quizState.selectedDuration === duration.value ? 'selected' : '';
                        html += `
                            <div class="selection-card ${selected}" onclick="selectDuration(${duration.value})">
                                <div class="selection-card-icon" style="background: rgba(80, 200, 120, 0.1);">
                                    <span class="material-icons" style="color: #50c878;">schedule</span>
                                </div>
                                <div class="selection-card-content">
                                    <div class="selection-card-title">${duration.name}</div>
                                </div>
                            </div>
                        `;
                    });

                    html += `</div></div>`;
                }

                // Generate Button
                const canGenerate = quizState.selectedTopic && quizState.selectedLanguage && quizState.selectedDuration;
                console.log('Can generate quiz?', canGenerate, quizState);

                html += `
                    <button class="quiz-generate-btn" ${!canGenerate ? 'disabled' : ''} onclick="generateQuiz()">
                        <span class="material-icons">auto_awesome</span>
                        Generate Quiz
                    </button>
                `;

                container.innerHTML = html;
            }
        }

        function handleQuizFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                quizState.selectedFile = file;
                renderQuizContent();
            }
        }

        function generateQuiz() {
            console.log('Generate Quiz clicked!', quizState);

            try {
                const exam = quizExams.find(e => e.id === quizState.selectedExam);
                const subjects = quizSubjectsByExam[quizState.selectedExam];
                const subject = subjects ? subjects.find(s => s.id === quizState.selectedSubject) : null;
                const topics = quizTopicsBySubject[quizState.selectedSubject];
                const topic = topics ? topics.find(t => t.id === quizState.selectedTopic) : null;

                if (!exam || !subject || !topic) {
                    console.error('Missing data:', { exam, subject, topic });
                    showError('Please select all options before generating quiz');
                    return;
                }

                // Calculate number of questions based on duration
                const questionCount = Math.max(3, Math.floor(quizState.selectedDuration / 3));

                const quizPrompt = `Generate exactly ${questionCount} multiple choice questions on ${topic.name} for ${exam.shortName} ${subject.name} in ${quizState.selectedLanguage} language.

Format each question EXACTLY like this:
Q1. [Question text here]
A) [Option A]
B) [Option B]
C) [Option C]
D) [Option D]
Correct Answer: [A/B/C/D]

Q2. [Next question...]

Make sure to follow this format precisely for all ${questionCount} questions.`;

                console.log('Quiz prompt:', quizPrompt);

                // Mark that we're generating a quiz
                quizTakingState.isGeneratingQuiz = true;

                // Close modal first
                closeQuizModal();

                // Hide welcome section if visible
                const welcomeEl = document.getElementById('welcomeSection');
                if (welcomeEl) {
                    welcomeEl.classList.add('hidden');
                }

                // Set the message and submit
                messageInput.value = quizPrompt;
                messageInput.focus();
                checkInput();

                // Small delay to ensure input is set
                setTimeout(() => {
                    chatForm.dispatchEvent(new Event('submit'));
                }, 100);
            } catch (error) {
                console.error('Error generating quiz:', error);
                showError('Failed to generate quiz. Please try again.');
            }
        }

        // Parse quiz questions from AI response
        function parseQuizQuestions(text) {
            const questions = [];
            const questionRegex = /Q\d+\.\s*(.+?)\n\s*A\)\s*(.+?)\n\s*B\)\s*(.+?)\n\s*C\)\s*(.+?)\n\s*D\)\s*(.+?)\n\s*Correct Answer:\s*([ABCD])/gi;

            let match;
            while ((match = questionRegex.exec(text)) !== null) {
                questions.push({
                    question: match[1].trim(),
                    options: [
                        { letter: 'A', text: match[2].trim() },
                        { letter: 'B', text: match[3].trim() },
                        { letter: 'C', text: match[4].trim() },
                        { letter: 'D', text: match[5].trim() }
                    ],
                    correctAnswer: match[6].trim().toUpperCase()
                });
            }

            return questions;
        }

        // Check if message contains quiz and start quiz taking
        function checkAndStartQuiz(messageContent) {
            if (!quizTakingState.isGeneratingQuiz) return;

            const questions = parseQuizQuestions(messageContent);
            console.log('Parsed questions:', questions);

            if (questions.length > 0) {
                quizTakingState.isGeneratingQuiz = false;
                quizTakingState.questions = questions;
                quizTakingState.currentQuestionIndex = 0;
                quizTakingState.userAnswers = new Array(questions.length).fill(null);
                openQuizTakingModal();
            }
        }

        // Open quiz taking modal
        function openQuizTakingModal() {
            const modal = document.getElementById('quizTakingModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            renderQuizQuestion();
        }

        // Close quiz taking modal
        function closeQuizTakingModal() {
            const modal = document.getElementById('quizTakingModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Render current quiz question
        function renderQuizQuestion() {
            const { questions, currentQuestionIndex, userAnswers } = quizTakingState;
            const question = questions[currentQuestionIndex];
            const container = document.getElementById('quizTakingBody');

            const progress = ((currentQuestionIndex + 1) / questions.length) * 100;

            container.innerHTML = `
                <div class="quiz-progress-bar">
                    <div class="quiz-progress-fill" style="width: ${progress}%"></div>
                </div>
                <div style="padding: 24px;">
                    <div class="quiz-question-number">
                        <span class="material-icons">help_outline</span>
                        Question ${currentQuestionIndex + 1} of ${questions.length}
                    </div>
                    <div class="quiz-question-text">${question.question}</div>
                    <div class="quiz-options-container">
                        ${question.options.map(option => `
                            <div class="quiz-option ${userAnswers[currentQuestionIndex] === option.letter ? 'selected' : ''}"
                                 onclick="selectQuizAnswer('${option.letter}')">
                                <div class="quiz-option-letter">${option.letter}</div>
                                <div class="quiz-option-text">${option.text}</div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="quiz-navigation">
                        <button class="quiz-nav-btn quiz-nav-btn-back"
                                onclick="previousQuestion()"
                                ${currentQuestionIndex === 0 ? 'disabled' : ''}>
                            <span class="material-icons">arrow_back</span>
                            Previous
                        </button>
                        <button class="quiz-nav-btn quiz-nav-btn-next"
                                onclick="${currentQuestionIndex === questions.length - 1 ? 'finishQuiz()' : 'nextQuestion()'}">
                            ${currentQuestionIndex === questions.length - 1 ? 'Finish' : 'Next'}
                            <span class="material-icons">${currentQuestionIndex === questions.length - 1 ? 'check_circle' : 'arrow_forward'}</span>
                        </button>
                    </div>
                </div>
            `;
        }

        // Select answer
        function selectQuizAnswer(letter) {
            quizTakingState.userAnswers[quizTakingState.currentQuestionIndex] = letter;
            renderQuizQuestion();
        }

        // Next question
        function nextQuestion() {
            if (quizTakingState.currentQuestionIndex < quizTakingState.questions.length - 1) {
                quizTakingState.currentQuestionIndex++;
                renderQuizQuestion();
            }
        }

        // Previous question
        function previousQuestion() {
            if (quizTakingState.currentQuestionIndex > 0) {
                quizTakingState.currentQuestionIndex--;
                renderQuizQuestion();
            }
        }

        // Finish quiz and show score
        function finishQuiz() {
            const { questions, userAnswers } = quizTakingState;

            let correct = 0;
            let incorrect = 0;

            questions.forEach((q, i) => {
                if (userAnswers[i] === q.correctAnswer) {
                    correct++;
                } else if (userAnswers[i] !== null) {
                    incorrect++;
                }
            });

            const unanswered = questions.length - correct - incorrect;
            const percentage = Math.round((correct / questions.length) * 100);

            closeQuizTakingModal();
            showScoreModal(correct, incorrect, questions.length, percentage);
        }

        // Show score modal
        function showScoreModal(correct, incorrect, total, percentage) {
            const modal = document.getElementById('quizScoreModal');
            const container = document.getElementById('quizScoreBody');

            const emoji = percentage >= 80 ? '🎉' : percentage >= 60 ? '👍' : percentage >= 40 ? '😊' : '💪';
            const message = percentage >= 80 ? 'Excellent!' : percentage >= 60 ? 'Good Job!' : percentage >= 40 ? 'Keep Practicing!' : 'Try Again!';

            container.innerHTML = `
                <div class="quiz-score-header">
                    <div class="quiz-score-icon">
                        <span style="font-size: 40px;">${emoji}</span>
                    </div>
                    <div class="quiz-score-percentage">${percentage}%</div>
                    <div class="quiz-score-label">${message}</div>
                </div>
                <div class="quiz-score-stats">
                    <div class="quiz-stat-card">
                        <div class="quiz-stat-value correct">${correct}</div>
                        <div class="quiz-stat-label">Correct</div>
                    </div>
                    <div class="quiz-stat-card">
                        <div class="quiz-stat-value incorrect">${incorrect}</div>
                        <div class="quiz-stat-label">Incorrect</div>
                    </div>
                    <div class="quiz-stat-card">
                        <div class="quiz-stat-value total">${total}</div>
                        <div class="quiz-stat-label">Total</div>
                    </div>
                </div>
                <div class="quiz-score-actions">
                    <button class="quiz-score-btn quiz-score-btn-secondary" onclick="closeScoreModal()">
                        <span class="material-icons">close</span>
                        Close
                    </button>
                    <button class="quiz-score-btn quiz-score-btn-primary" onclick="retakeQuiz()">
                        <span class="material-icons">refresh</span>
                        New Quiz
                    </button>
                </div>
            `;

            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Close score modal
        function closeScoreModal() {
            const modal = document.getElementById('quizScoreModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Retake quiz (open quiz modal again)
        function retakeQuiz() {
            closeScoreModal();
            openQuizModal();
        }

        // Whiteboard Modal Functions
        let whiteboardState = {
            content: '',
            isGenerating: false,
            jobId: null,
            selectedFile: null,
            fileContent: null
        };

        function openWhiteboardModal() {
            console.log('Opening whiteboard modal...');
            const modal = document.getElementById('whiteboardModal');
            if (!modal) {
                console.error('Whiteboard modal element not found!');
                return;
            }
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            console.log('Whiteboard modal opened');
        }

        function closeWhiteboardModal() {
            const modal = document.getElementById('whiteboardModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
            // Reset state
            whiteboardState.content = '';
            whiteboardState.selectedFile = null;
            whiteboardState.fileContent = null;
            document.getElementById('whiteboardContent').value = '';
            document.getElementById('whiteboardFilePreview').style.display = 'none';
            document.getElementById('whiteboardUploadArea').style.display = 'block';
        }

        function selectWhiteboardFile() {
            document.getElementById('whiteboardFileInput').click();
        }

        async function handleWhiteboardFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                showError('Please upload JPG, PNG, or PDF files only');
                return;
            }

            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                showError('File size must be less than 10MB');
                return;
            }

            whiteboardState.selectedFile = file;

            // Show file preview
            const previewDiv = document.getElementById('whiteboardFilePreview');
            const uploadArea = document.getElementById('whiteboardUploadArea');
            const fileName = document.getElementById('whiteboardFileName');
            const fileSize = document.getElementById('whiteboardFileSize');

            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';

            uploadArea.style.display = 'none';
            previewDiv.style.display = 'flex';

            // Extract text from file using OCR/Vision API
            await extractWhiteboardFileContent(file);
        }

        function removeWhiteboardFile() {
            whiteboardState.selectedFile = null;
            whiteboardState.fileContent = null;
            document.getElementById('whiteboardFileInput').value = '';
            document.getElementById('whiteboardFilePreview').style.display = 'none';
            document.getElementById('whiteboardUploadArea').style.display = 'block';
            updateWhiteboardGenerateButton();
        }

        async function extractWhiteboardFileContent(file) {
            try {
                // Show loading state
                const btn = document.getElementById('whiteboardGenerateBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="material-icons animate-spin">sync</span> Extracting content...';

                // Convert to base64
                const base64 = await fileToBase64(file);

                // Use AI vision to extract text
                const response = await fetch('/api/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer {{ auth()->user()->api_token ?? '' }}`,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        message: 'Extract all text and educational content from this image/document. Provide complete detailed text suitable for creating an educational video.',
                        image: {
                            uri: base64,
                            type: file.type,
                            fileName: file.name
                        }
                    })
                });

                const data = await response.json();

                if (data.success && data.response) {
                    whiteboardState.fileContent = data.response;
                    showSuccess('Content extracted successfully!');
                    updateWhiteboardGenerateButton();
                } else {
                    showError('Failed to extract content from file');
                    removeWhiteboardFile();
                }
            } catch (error) {
                console.error('Error extracting file content:', error);
                showError('Failed to process file. Please try again.');
                removeWhiteboardFile();
            }
        }

        function fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        function updateWhiteboardContent() {
            whiteboardState.content = document.getElementById('whiteboardContent').value;
            updateWhiteboardGenerateButton();
        }

        function updateWhiteboardGenerateButton() {
            const generateBtn = document.getElementById('whiteboardGenerateBtn');
            const hasText = whiteboardState.content.trim().length >= 100;
            const hasFile = whiteboardState.fileContent !== null;

            generateBtn.disabled = !(hasText || hasFile);
        }

        async function generateWhiteboardVideo() {
            // Use file content if available, otherwise use text input
            let content = whiteboardState.fileContent || whiteboardState.content.trim();

            if (!content || content.length < 100) {
                showError('Please upload a file or enter at least 100 characters of educational content');
                return;
            }

            console.log('Generating whiteboard video...');
            whiteboardState.isGenerating = true;

            // Update button state
            const btn = document.getElementById('whiteboardGenerateBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-icons animate-spin">sync</span> Generating...';

            try {
                const response = await fetch('/api/whiteboard-video/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer {{ auth()->user()->api_token ?? '' }}`,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        title: whiteboardState.selectedFile
                            ? 'Whiteboard Video - ' + whiteboardState.selectedFile.name
                            : 'Whiteboard Video',
                        document_content: content
                    })
                });

                const data = await response.json();
                console.log('Whiteboard API response:', data);

                if (data.success) {
                    whiteboardState.jobId = data.data.job_id;
                    closeWhiteboardModal();

                    // Show success message
                    showSuccess('Whiteboard video generation started! This may take a few minutes.');

                    // Start polling for status
                    pollWhiteboardStatus(data.data.job_id);
                } else {
                    showError(data.message || 'Failed to start video generation');
                    btn.disabled = false;
                    btn.innerHTML = '<span class="material-icons">video_library</span> Generate Video';
                }
            } catch (error) {
                console.error('Whiteboard generation error:', error);
                showError('Failed to generate whiteboard video. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '<span class="material-icons">video_library</span> Generate Video';
            }

            whiteboardState.isGenerating = false;
        }

        async function pollWhiteboardStatus(jobId) {
            const pollInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/api/whiteboard-video/status/${jobId}`, {
                        headers: {
                            'Authorization': `Bearer {{ auth()->user()->api_token ?? '' }}`,
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        const video = data.data;

                        if (video.status === 'completed') {
                            clearInterval(pollInterval);
                            showWhiteboardResult(video);
                        } else if (video.status === 'failed') {
                            clearInterval(pollInterval);
                            showError(`Video generation failed: ${video.error_message || 'Unknown error'}`);
                        }
                        // Continue polling if still processing
                    }
                } catch (error) {
                    console.error('Status polling error:', error);
                }
            }, 5000); // Poll every 5 seconds
        }

        function showWhiteboardResult(video) {
            // Add video result to chat
            const videoHTML = `
                <div class="message-ai">
                    <div class="flex items-start gap-3">
                        <div class="avatar avatar-ai">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="message-header">
                                <span class="name">{{ config('app.name', 'BlinkStudy') }}</span>
                                <span class="badge">AI</span>
                            </div>
                            <div class="message-content">
                                <h4 style="font-weight: 600; margin-bottom: 12px; color: #667eea;">🎬 Whiteboard Video Generated!</h4>
                                <p style="margin-bottom: 12px;">Your whiteboard animation video is ready:</p>
                                <div style="background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 12px; padding: 16px; margin-bottom: 12px;">
                                    <video controls style="width: 100%; max-width: 600px; border-radius: 8px;">
                                        <source src="${video.final_video_url}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                    <div style="background: rgba(255,255,255,0.05); padding: 8px 12px; border-radius: 8px; font-size: 12px;">
                                        <span style="opacity: 0.7;">Duration:</span> <strong>${video.duration_seconds}s</strong>
                                    </div>
                                    <div style="background: rgba(255,255,255,0.05); padding: 8px 12px; border-radius: 8px; font-size: 12px;">
                                        <span style="opacity: 0.7;">Scenes:</span> <strong>${video.total_scenes}</strong>
                                    </div>
                                    <a href="${video.final_video_url}" download style="background: #667eea; color: white; padding: 8px 12px; border-radius: 8px; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="material-icons" style="font-size: 16px;">download</span>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            messagesContainer.insertAdjacentHTML('beforeend', videoHTML);
            scrollToBottom();
        }

        function showSuccess(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
            toast.innerHTML = `<span class="material-icons">check_circle</span>${message}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }

        // Initialize modal content when page loads
        document.addEventListener('DOMContentLoaded', () => {
            // Wait a bit to ensure modal is in DOM
            setTimeout(() => {
                const modalBody = document.getElementById('quizModalBody');
                if (modalBody) {
                    console.log('Quiz modal initialized');
                    renderQuizContent();
                } else {
                    console.error('Quiz modal body not found on page load');
                }
            }, 100);
        });
    </script>

    <!-- Quiz Modal -->
    <div id="quizModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background: rgba(0,0,0,0.7); display: none;" onclick="closeQuizModal()">
        <div class="quiz-modal-content" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="quiz-modal-header">
                <h2 style="font-size: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                    <span class="material-icons" style="color: var(--accent-color);">quiz</span>
                    Quiz Generator
                </h2>
                <button onclick="closeQuizModal()" style="padding: 8px; border-radius: 8px; border: none; background: var(--bg-hover); cursor: pointer; transition: all 0.2s ease;">
                    <span class="material-icons" style="font-size: 20px; color: var(--text-primary);">close</span>
                </button>
            </div>

            <!-- Tabs -->
            <div class="quiz-tabs">
                <button class="quiz-tab active" data-tab="topic" onclick="switchQuizTab('topic')">
                    <span class="material-icons">school</span>
                    Quiz by Topic
                </button>
                <button class="quiz-tab" data-tab="scan" onclick="switchQuizTab('scan')">
                    <span class="material-icons">document_scanner</span>
                    Scan Notes
                </button>
            </div>

            <!-- Body -->
            <div class="quiz-modal-body" id="quizModalBody">
                <!-- Content will be dynamically rendered here -->
            </div>
        </div>
    </div>

    <!-- Quiz Taking Modal -->
    <div id="quizTakingModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background: rgba(0,0,0,0.7); display: none;">
        <div class="quiz-taking-content" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="quiz-modal-header">
                <h2 style="font-size: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                    <span class="material-icons" style="color: var(--accent-color);">quiz</span>
                    Quiz
                </h2>
                <button onclick="if(confirm('Are you sure you want to exit? Your progress will be lost.')) closeQuizTakingModal()" style="padding: 8px; border-radius: 8px; border: none; background: var(--bg-hover); cursor: pointer; transition: all 0.2s ease;">
                    <span class="material-icons" style="font-size: 20px; color: var(--text-primary);">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="quiz-modal-body" id="quizTakingBody" style="overflow-y: auto;">
                <!-- Question will be dynamically rendered here -->
            </div>
        </div>
    </div>

    <!-- Quiz Score Modal -->
    <div id="quizScoreModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background: rgba(0,0,0,0.7); display: none;">
        <div class="quiz-score-content" onclick="event.stopPropagation()">
            <div id="quizScoreBody">
                <!-- Score will be dynamically rendered here -->
            </div>
        </div>
    </div>

    <!-- Whiteboard Video Modal -->
    <div id="whiteboardModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background: rgba(0,0,0,0.7); display: none;" onclick="closeWhiteboardModal()">
        <div class="quiz-modal-content" onclick="event.stopPropagation()" style="max-width: 700px;">
            <!-- Header -->
            <div class="quiz-modal-header">
                <h2 style="font-size: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                    <span class="material-icons" style="color: #667eea;">video_library</span>
                    Whiteboard Video Generator
                </h2>
                <button onclick="closeWhiteboardModal()" style="padding: 8px; border-radius: 8px; border: none; background: var(--bg-hover); cursor: pointer; transition: all 0.2s ease;">
                    <span class="material-icons" style="font-size: 20px; color: var(--text-primary);">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="quiz-modal-body" style="padding: 24px;">
                <!-- File Upload Area -->
                <div class="selection-step">
                    <div class="selection-step-title">
                        <span class="material-icons" style="font-size: 20px; color: #667eea;">upload_file</span>
                        Upload Document/Image
                    </div>

                    <!-- Upload Area -->
                    <div id="whiteboardUploadArea" class="whiteboard-upload-area" onclick="selectWhiteboardFile()">
                        <div class="whiteboard-upload-icon">
                            <span class="material-icons" style="font-size: 32px; color: #667eea;">cloud_upload</span>
                        </div>
                        <h3 style="font-size: 15px; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Upload your notes or images</h3>
                        <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">Click to browse files or drag and drop</p>
                        <p style="font-size: 11px; color: var(--text-secondary);">Supports: JPG, PNG, PDF (Max 10MB)</p>
                    </div>

                    <!-- File Preview -->
                    <div id="whiteboardFilePreview" class="whiteboard-file-preview" style="display: none;">
                        <div class="whiteboard-file-icon">
                            <span class="material-icons" style="font-size: 24px; color: #667eea;">description</span>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div id="whiteboardFileName" style="font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></div>
                            <div id="whiteboardFileSize" style="font-size: 12px; color: var(--text-secondary);"></div>
                        </div>
                        <button onclick="removeWhiteboardFile()" style="padding: 8px; border-radius: 8px; border: none; background: rgba(255,0,0,0.1); cursor: pointer; transition: all 0.2s ease;">
                            <span class="material-icons" style="font-size: 18px; color: #ff4444;">close</span>
                        </button>
                    </div>

                    <input type="file" id="whiteboardFileInput" accept="image/*,.pdf" style="display: none;" onchange="handleWhiteboardFileSelect(event)">
                </div>

                <!-- OR Divider -->
                <div class="whiteboard-divider">
                    <div class="whiteboard-divider-line"></div>
                    <div class="whiteboard-divider-text">OR</div>
                    <div class="whiteboard-divider-line"></div>
                </div>

                <!-- Text Input Area -->
                <div class="selection-step">
                    <div class="selection-step-title">
                        <span class="material-icons" style="font-size: 20px; color: #667eea;">edit_note</span>
                        Enter Educational Content
                    </div>
                    <textarea
                        id="whiteboardContent"
                        class="whiteboard-textarea"
                        placeholder="Enter your educational content here... (minimum 100 characters)

Example topics:
• Explain photosynthesis
• Newton's laws of motion
• History of Indian independence
• Mathematics formulas
• Geography concepts"
                        style="width: 100%; padding: 16px; background: var(--bg-secondary); border: 2px solid var(--border-color); border-radius: 12px; color: var(--text-primary); font-size: 14px; line-height: 1.6; resize: vertical;"
                        oninput="updateWhiteboardContent()"></textarea>
                    <p style="font-size: 12px; color: var(--text-secondary); margin-top: 8px;">
                        <span class="material-icons" style="font-size: 14px; vertical-align: middle;">info</span>
                        Minimum 100 characters required
                    </p>
                </div>

                <div class="whiteboard-info-card">
                    <h4>
                        <span class="material-icons" style="font-size: 18px;">smart_toy</span>
                        Powered by Dedicated AI Model
                    </h4>
                    <ul>
                        <li>Upload images/PDFs or enter text to create videos</li>
                        <li>AI automatically extracts educational content from files</li>
                        <li>Uses specialized AI model configured in admin settings</li>
                        <li>This model works ONLY for whiteboard animations</li>
                        <li>Separate from chat and quiz AI models</li>
                        <li>Generates scenes with narration and visuals</li>
                        <li>Creates professional educational videos</li>
                    </ul>
                </div>

                <button
                    id="whiteboardGenerateBtn"
                    class="quiz-generate-btn"
                    onclick="generateWhiteboardVideo()"
                    disabled>
                    <span class="material-icons">video_library</span>
                    Generate Whiteboard Video
                </button>
            </div>
        </div>
    </div>
<!-- Profile Completion Popup (first-time login) -->
@if(auth()->check() && !auth()->user()->profile_completed)
<style>
#pp-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:99999;display:flex;align-items:flex-end;justify-content:center;opacity:0;transition:opacity 0.3s ease}
#pp-box{width:100%;max-width:420px;background:#1a1f2e;border-radius:16px 16px 0 0;box-shadow:0 -10px 40px rgba(0,0,0,0.4);transform:translateY(100%);transition:transform 0.5s cubic-bezier(0.16,1,0.3,1)}
#pp-box .pp-handle{width:40px;height:4px;border-radius:4px;background:#4b5563;margin:12px auto 4px}
#pp-box .pp-body{padding:16px 24px 32px}
#pp-box h2{font-size:20px;font-weight:700;color:#fff;text-align:center;margin:0}
#pp-box .pp-sub{font-size:13px;color:#9ca3af;text-align:center;margin:4px 0 20px}
#pp-box label{display:block;font-size:13px;font-weight:600;color:#d1d5db;margin-bottom:6px}
#pp-box label span{font-weight:400;color:#6b7280}
#pp-box input[type=text],#pp-box input[type=email]{width:100%;padding:12px 16px;border-radius:12px;border:1px solid #374151;background:#1f2937;color:#fff;font-size:14px;outline:none;box-sizing:border-box;transition:border-color 0.2s}
#pp-box input:focus{border-color:#0d9488}
#pp-box .pp-phone{display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:12px;border:1px solid #374151;background:#111827}
#pp-box .pp-phone span{font-size:13px;color:#9ca3af}
#pp-box .pp-phone .pp-num{font-size:13px;color:#e5e7eb}
#pp-box .pp-phone .pp-check{margin-left:auto;color:#22c55e;font-size:18px}
#pp-box .pp-btn{width:100%;padding:14px;border:none;border-radius:999px;background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;font-size:14px;font-weight:700;cursor:pointer;margin-top:8px;transition:transform 0.2s,box-shadow 0.2s;box-shadow:0 4px 15px rgba(13,148,136,0.3)}
#pp-box .pp-btn:hover{transform:scale(1.02);box-shadow:0 4px 20px rgba(13,148,136,0.5)}
#pp-box .pp-btn:disabled{opacity:0.7;cursor:not-allowed;transform:none}
#pp-box .pp-field{margin-bottom:16px}
</style>
<div id="pp-overlay">
    <div id="pp-box">
        <div class="pp-handle"></div>
        <div class="pp-body">
            <h2>Profile Information</h2>
            <p class="pp-sub">Manage your basic profile details</p>
            <form id="pp-form">
                <div class="pp-field">
                    <label>Full Name</label>
                    <input type="text" id="pp-name" value="{{ auth()->user()->name }}" placeholder="Enter your name" required minlength="2" maxlength="50">
                </div>
                <div class="pp-field">
                    <label>Email <span>(optional)</span></label>
                    <input type="email" id="pp-email" placeholder="your@email.com">
                </div>
                <div class="pp-field">
                    <label>Phone</label>
                    <div class="pp-phone">
                        <span>+91</span>
                        <span class="pp-num">{{ auth()->user()->mobile }}</span>
                        <span class="pp-check material-icons">verified</span>
                    </div>
                </div>
                <button type="submit" id="pp-btn" class="pp-btn">Update Profile</button>
            </form>
        </div>
    </div>
</div>
<script>
(function(){
    var ov = document.getElementById('pp-overlay');
    var box = document.getElementById('pp-box');
    if(!ov||!box) return;
    setTimeout(function(){
        ov.style.opacity='1';
        setTimeout(function(){ box.style.transform='translateY(0)'; },100);
    },800);
    document.getElementById('pp-form').addEventListener('submit',function(e){
        e.preventDefault();
        var btn=document.getElementById('pp-btn');
        var name=document.getElementById('pp-name').value.trim();
        var email=document.getElementById('pp-email').value.trim();
        if(name.length<2) return;
        btn.disabled=true; btn.textContent='Updating...';
        fetch('/profile/complete',{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},
            body:JSON.stringify({name:name,email:email||null})
        })
        .then(function(r){return r.json();})
        .then(function(d){
            if(d.success){
                btn.textContent='Done!';
                btn.style.background='#22c55e';
                setTimeout(function(){
                    box.style.transform='translateY(100%)';
                    ov.style.opacity='0';
                    setTimeout(function(){ov.remove();},400);
                },500);
            } else {btn.disabled=false;btn.textContent='Update Profile';}
        })
        .catch(function(){btn.disabled=false;btn.textContent='Update Profile';});
    });
})();
</script>
@endif

<!-- React Prompt Input Component -->
<link rel="stylesheet" href="/build/assets/app-CGKmMOMK.css">
<script type="module" src="/build/assets/app-Dffyk5Qw.js"></script>
</body>
</html>
