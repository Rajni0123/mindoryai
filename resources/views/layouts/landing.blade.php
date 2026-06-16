<!DOCTYPE html>
<html class="dark scroll-smooth" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'BlinkStudy') . ' — AI Powered Exam Preparation')</title>
    <meta name="description" content="@yield('description', 'AI-powered daily tests, weakness analysis, study battles and revision — built for Indian students.')"/>
    <meta name="theme-color" content="#11131a"/>
    <link rel="icon" href="{{ asset('favicon.ico') }}"/>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"/>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        tertiary: "#ffb77b",
                        "on-tertiary": "#4d2700",
                        secondary: "#ddb8ff",
                        "on-secondary": "#490081",
                        "secondary-container": "#62259b",
                        "on-secondary-container": "#d1a1ff",
                        primary: "#afc6ff",
                        "on-primary": "#002d6c",
                        "primary-container": "#528dff",
                        "on-primary-container": "#00275f",
                        "on-surface": "#e1e2ec",
                        "on-surface-variant": "#c2c6d6",
                        "surface-container-lowest": "#0b0e15",
                        "surface-container-low": "#191b22",
                        "surface-container": "#1d1f27",
                        "surface-container-high": "#272a31",
                        "outline-variant": "#424753",
                        background: "#11131a",
                        surface: "#11131a",
                    },
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        full: "9999px",
                    },
                    spacing: {
                        gutter: "24px",
                        xl: "40px",
                        "margin-desktop": "48px",
                        "margin-mobile": "16px",
                        md: "16px",
                        sm: "8px",
                        lg: "24px",
                        "2xl": "64px",
                    },
                    fontFamily: {
                        "display-lg": ["Manrope", "sans-serif"],
                        "headline-lg": ["Manrope", "sans-serif"],
                        "headline-md": ["Manrope", "sans-serif"],
                        "body-lg": ["Inter", "sans-serif"],
                        "body-md": ["Inter", "sans-serif"],
                        "label-md": ["Inter", "sans-serif"],
                        "label-sm": ["Inter", "sans-serif"],
                    },
                    fontSize: {
                        "display-lg": ["48px", { lineHeight: "1.1", letterSpacing: "-0.02em", fontWeight: "800" }],
                        "headline-lg": ["32px", { lineHeight: "1.2", letterSpacing: "-0.01em", fontWeight: "700" }],
                        "headline-md": ["24px", { lineHeight: "1.3", fontWeight: "600" }],
                        "body-lg": ["18px", { lineHeight: "1.6", fontWeight: "400" }],
                        "body-md": ["16px", { lineHeight: "1.6", fontWeight: "400" }],
                        "label-md": ["14px", { lineHeight: "1.4", letterSpacing: "0.01em", fontWeight: "600" }],
                        "label-sm": ["12px", { lineHeight: "1.4", letterSpacing: "0.02em", fontWeight: "500" }],
                    },
                },
            },
        };
    </script>

    <style>
        html { scroll-behavior: smooth; }
        body { background-color: #0b0e15; font-family: Inter, sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-top-color: rgba(255, 255, 255, 0.15);
            border-left-color: rgba(255, 255, 255, 0.15);
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .text-gradient {
            background: linear-gradient(135deg, #afc6ff, #ddb8ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            padding-bottom: 0.08em;
            line-height: 1.15;
        }
        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 0 15px 0 rgba(175, 198, 255, 0.2);
        }
        .step-connector::after {
            content: '';
            position: absolute;
            top: 2rem;
            left: calc(50% + 2rem);
            width: calc(100% - 4rem);
            height: 2px;
            background: linear-gradient(90deg, #afc6ff44, #ddb8ff44);
            z-index: 0;
        }
        @media (max-width: 768px) {
            .step-connector::after { display: none; }
        }
        #mobile-nav:not(.hidden) { display: block; }
        .hero-visual-shell {
            width: 100%;
            max-width: 380px;
            margin-left: auto;
            margin-right: auto;
            aspect-ratio: 4 / 5;
            max-height: min(480px, 62vh);
            border-radius: 1.25rem;
            overflow: hidden;
        }
        .hero-visual-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 12%;
            display: block;
        }
        .hero-dashboard-shell {
            width: 100%;
            max-width: 640px;
            margin-left: auto;
            margin-right: auto;
            aspect-ratio: 16 / 10;
            border-radius: 1.25rem;
            overflow: hidden;
        }
        .hero-dashboard-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .hero-glow-ring {
            position: absolute;
            inset: -4px;
            border-radius: 1.35rem;
            background: linear-gradient(135deg, rgba(175, 198, 255, 0.45), rgba(221, 184, 255, 0.35));
            opacity: 0.35;
            filter: blur(8px);
            z-index: 0;
        }
        .preview-visual-shell {
            width: 100%;
            max-height: 420px;
            border-radius: 1.25rem;
            overflow: hidden;
        }
        .preview-visual-img {
            width: 100%;
            height: 100%;
            max-height: 420px;
            object-fit: cover;
            object-position: center top;
            display: block;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }
        @media (min-width: 640px) {
            .feature-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .feature-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        .feature-card {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            padding: 1.5rem 1.5rem 1.75rem;
            min-height: 200px;
            border-radius: 1rem;
            background: #161b22;
            border: 1px solid rgba(255, 255, 255, 0.06);
            overflow: hidden;
        }
        .feature-card-glow {
            position: absolute;
            top: -3.5rem;
            right: -3.5rem;
            width: 9rem;
            height: 9rem;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.06) 0%, transparent 68%);
            border: 1px solid rgba(255, 255, 255, 0.04);
            pointer-events: none;
        }
        .feature-icon-purple {
            background: rgba(221, 184, 255, 0.12);
            color: #ddb8ff;
        }
        .feature-icon-orange {
            background: rgba(255, 183, 123, 0.12);
            color: #ffb77b;
        }
        .feature-icon-blue {
            background: rgba(175, 198, 255, 0.12);
            color: #afc6ff;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-surface-container-lowest text-on-surface antialiased overflow-x-hidden selection:bg-primary-container selection:text-on-primary-container">
@yield('content')
@stack('scripts')
</body>
</html>
