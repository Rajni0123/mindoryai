<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->meta_title ?: $page->title }} - BlinkStudy</title>
    <meta name="description" content="{{ $page->meta_description ?: Str::limit(strip_tags($page->content), 160) }}">
    @if($page->meta_keywords)
    <meta name="keywords" content="{{ $page->meta_keywords }}">
    @endif
    <link rel="icon" type="image/png" href="/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #fafafa; }

        :root {
            --teal-500: #1DB9A0;
            --teal-600: #189A85;
        }

        /* Navigation - Same as homepage */
        .navbar-sticky { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; width: 100%; padding-top: 12px; }
        .navbar-wrapper { padding: 1rem 1rem; max-width: 1400px; margin: 0 auto; }
        @media (min-width: 768px) { .navbar-wrapper { padding: 1rem 2rem; } }

        .navbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.6rem 1rem;
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.8);
            border-radius: 100px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        }

        .nav-logo { font-size: 1.35rem; font-weight: 800; color: #0f172a; text-decoration: none; }

        .logo-blink { animation: bulbGlow 3s ease-in-out infinite; }
        @keyframes bulbGlow {
            0%, 100% { filter: drop-shadow(0 0 0px rgba(255, 200, 50, 0)); }
            50% { filter: drop-shadow(0 0 8px rgba(255, 200, 50, 0.6)); }
        }

        .nav-menu { display: none; list-style: none; margin: 0; padding: 0; gap: 0.5rem; font-weight: 500; font-size: 0.875rem; }
        @media (min-width: 768px) { .nav-menu { display: flex; } }
        .nav-menu li a { color: #475569; text-decoration: none; padding: 8px 14px; border-radius: 50px; display: inline-block; }
        .nav-menu li a:hover { color: #0f172a; background: rgba(15, 23, 42, 0.05); }

        .nav-cta {
            display: none; padding: 10px 22px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 0.875rem;
        }
        @media (min-width: 768px) { .nav-cta { display: inline-flex; } }

        .mobile-nav-login {
            display: inline-flex; padding: 8px 16px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 0.8rem;
        }
        @media (min-width: 768px) { .mobile-nav-login { display: none; } }

        /* Page Background */
        .page-bg {
            background: radial-gradient(ellipse 60% 80% at 0% 50%, rgba(200, 180, 240, 0.3) 0%, transparent 60%),
                        radial-gradient(ellipse 50% 70% at 100% 60%, rgba(255, 200, 150, 0.3) 0%, transparent 60%);
            min-height: 100vh;
            padding-top: 100px;
        }

        /* Content Card */
        .content-card {
            background: rgba(255,255,255,0.95);
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.8);
        }

        /* Content Styles */
        .content-card h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
            text-align: center;
        }
        .content-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 32px 0 16px;
        }
        .content-card h2:first-of-type { margin-top: 0; }
        .content-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
            margin: 24px 0 12px;
        }
        .content-card p {
            color: #475569;
            line-height: 1.8;
            font-size: 15px;
            margin-bottom: 16px;
        }
        .content-card ul, .content-card ol {
            padding-left: 24px;
            margin: 16px 0;
        }
        .content-card li {
            color: #475569;
            line-height: 1.8;
            font-size: 15px;
            margin-bottom: 8px;
        }
        .content-card a {
            color: #1DB9A0;
            text-decoration: none;
            font-weight: 500;
        }
        .content-card a:hover {
            text-decoration: underline;
        }
        .content-card strong {
            color: #0f172a;
            font-weight: 600;
        }
        .content-card blockquote {
            border-left: 4px solid #1DB9A0;
            padding-left: 20px;
            margin: 20px 0;
            color: #64748b;
            font-style: italic;
        }
        .content-card table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .content-card th, .content-card td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .content-card th {
            background: #f8fafc;
            font-weight: 600;
            color: #0f172a;
        }
        .content-card td {
            color: #475569;
        }

        .last-updated {
            color: #64748b;
            font-size: 14px;
            text-align: center;
            margin-bottom: 40px;
        }

        /* Footer Grid */
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 48px; margin-bottom: 48px; }

        @media (max-width: 768px) {
            .content-card {
                padding: 24px;
                border-radius: 16px;
            }
            .content-card h1 {
                font-size: 1.75rem;
            }
            .content-card h2 {
                font-size: 1.25rem;
            }
            .footer-grid { grid-template-columns: 1fr 1fr !important; gap: 24px !important; }
            .footer-grid > div:first-child { grid-column: 1 / -1; }
            .footer-bottom { flex-direction: column !important; text-align: center !important; }
        }
        @media (max-width: 480px) {
            .footer-grid { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>
    <!-- Navigation - Same as homepage -->
    <div class="navbar-sticky">
        <div class="navbar-wrapper">
            <nav class="navbar">
                <a href="/" class="nav-logo" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                    <img src="/logo.png" alt="BlinkStudy" class="logo-blink" style="width: 40px; height: 40px; object-fit: contain;">
                    <span style="font-weight: 700; font-size: 1.2rem; background: linear-gradient(135deg, #0f172a 0%, #334155 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">BlinkStudy</span>
                </a>

                <ul class="nav-menu">
                    <li><a href="/#features">Features</a></li>
                    <li><a href="/plans">Plans</a></li>
                    <li><a href="/#testimonials">Reviews</a></li>
                    <li><a href="/support">Support</a></li>
                </ul>

                <a href="/login" class="nav-cta">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        Get Started
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </span>
                </a>

                <a href="/login" class="mobile-nav-login">Login</a>
            </nav>
        </div>
    </div>

    <div class="page-bg">
        <!-- Main Content -->
        <div style="max-width: 800px; margin: 0 auto; padding: 40px 24px 80px;">
            <div class="content-card">
                <!-- Header -->
                <h1>{{ $page->title }}</h1>
                @if($page->updated_at)
                <p class="last-updated">Last updated: {{ $page->updated_at->format('F d, Y') }}</p>
                @endif

                <!-- Page Content -->
                <div class="page-content">
                    @php
                        $bodyContent = $page->content;

                        // Strip full HTML document wrappers if present
                        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $bodyContent, $m)) {
                            $bodyContent = trim($m[1]);
                        } else {
                            $bodyContent = preg_replace('/<(!DOCTYPE|html|\/html|head|\/head|body|\/body)[^>]*>/i', '', $bodyContent);
                            $bodyContent = preg_replace('/<title[^>]*>.*?<\/title>/is', '', $bodyContent);
                            $bodyContent = trim($bodyContent);
                        }

                        // If content is plain text (no block-level HTML tags), format it
                        $hasHtmlBlocks = preg_match('/<(p|h[1-6]|ul|ol|div|table|blockquote|section)\b/i', $bodyContent);
                        if (!$hasHtmlBlocks && strlen($bodyContent) > 0) {
                            // Split by numbered sections like "1. TITLE" or "1.1 TITLE"
                            $bodyContent = preg_replace('/(\d+\.(?:\d+)?)\s+([A-Z][A-Z &]+)/m', '</p><h2>$1 $2</h2><p>', $bodyContent);
                            // Convert dash/hyphen lists to <li>
                            $bodyContent = preg_replace('/[\r\n]\s*[-–]\s+(.+)/m', '</p><ul><li>$1</li></ul><p>', $bodyContent);
                            // Merge consecutive <ul> blocks
                            $bodyContent = str_replace('</ul><p></p><ul>', '', $bodyContent);
                            $bodyContent = str_replace("</ul><p>\n</p><ul>", '', $bodyContent);
                            // Wrap in paragraphs by double newlines
                            $bodyContent = preg_replace('/\n{2,}/', '</p><p>', $bodyContent);
                            $bodyContent = '<p>' . $bodyContent . '</p>';
                            // Clean empty paragraphs
                            $bodyContent = preg_replace('/<p>\s*<\/p>/', '', $bodyContent);
                        }
                    @endphp
                    {!! $bodyContent !!}
                </div>
            </div>
        </div>
    </div>

    <!-- Footer - Same as homepage -->
    <footer style="padding: 60px 24px 30px; background: radial-gradient(ellipse 60% 80% at 0% 100%, rgba(100, 80, 140, 0.3) 0%, transparent 50%), radial-gradient(ellipse 50% 70% at 100% 0%, rgba(150, 100, 80, 0.2) 0%, transparent 50%), #0f172a; color: #fff;">
        <div style="max-width: 1100px; margin: 0 auto;">
            <div class="footer-grid">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                        <img src="/logo.png" alt="BlinkStudy" class="logo-blink" style="width: 40px; height: 40px;">
                        <span style="font-size: 1.5rem; font-weight: 700;">BlinkStudy</span>
                    </div>
                    <p style="color: #94a3b8; font-size: 14px; line-height: 1.7; max-width: 280px; margin-bottom: 20px;">
                        Your AI-powered study companion. Get instant solutions, AI tutoring, and personalized quizzes. Made with &#10084; in India.
                    </p>
                    <div style="display: flex; gap: 12px;">
                        <a href="https://instagram.com/blinkstudy" style="width: 38px; height: 38px; background: #1e293b; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                        </a>
                        <a href="https://twitter.com/blinkstudy" style="width: 38px; height: 38px; background: #1e293b; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="https://youtube.com/@blinkstudy" style="width: 38px; height: 38px; background: #1e293b; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 20px; text-transform: uppercase;">Product</h4>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <a href="/#features" style="color: #94a3b8; text-decoration: none; font-size: 14px;">Features</a>
                        <a href="/plans" style="color: #94a3b8; text-decoration: none; font-size: 14px;">Pricing</a>
                        <a href="https://play.google.com/store/apps/details?id=com.blinkstudy.app" style="color: #94a3b8; text-decoration: none; font-size: 14px;">Download App</a>
                        <a href="/login" style="color: #94a3b8; text-decoration: none; font-size: 14px;">Login</a>
                    </div>
                </div>

                <div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 20px; text-transform: uppercase;">Resources</h4>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <a href="/about" style="color: #94a3b8; text-decoration: none; font-size: 14px;">About Us</a>
                        <a href="/#faqs" style="color: #94a3b8; text-decoration: none; font-size: 14px;">FAQ</a>
                        <a href="/support" style="color: #94a3b8; text-decoration: none; font-size: 14px;">Support</a>
                        <a href="/page/contact-us" style="color: #94a3b8; text-decoration: none; font-size: 14px;">Contact Us</a>
                    </div>
                </div>

                <div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 20px; text-transform: uppercase;">Legal</h4>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <a href="/page/privacy-policy" style="color: #94a3b8; text-decoration: none; font-size: 14px;">Privacy Policy</a>
                        <a href="/page/terms-of-service" style="color: #94a3b8; text-decoration: none; font-size: 14px;">Terms of Service</a>
                        <a href="/page/cancellation-refund-policy" style="color: #94a3b8; text-decoration: none; font-size: 14px;">Refund Policy</a>
                        <a href="/cookie-policy" style="color: #94a3b8; text-decoration: none; font-size: 14px;">Cookie Policy</a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom" style="border-top: 1px solid #1e293b; padding-top: 24px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
                <p style="color: #64748b; font-size: 13px;">&copy; {{ date('Y') }} BlinkStudy. All rights reserved.</p>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #64748b; font-size: 13px;">Made with</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#ef4444" stroke="#ef4444" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    <span style="color: #64748b; font-size: 13px;">in India</span>
                    <img src="https://flagcdn.com/20x15/in.png" alt="India" style="margin-left: 4px; border-radius: 2px;">
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
