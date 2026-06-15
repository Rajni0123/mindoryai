<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - BlinkStudy</title>
    <meta name="description" content="Get help and support from BlinkStudy. Contact us via email or use our contact form.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #fafafa; }

        /* Navigation */
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

        .logo-blink { animation: bulbGlow 3s ease-in-out infinite; }
        @keyframes bulbGlow {
            0%, 100% { filter: drop-shadow(0 0 0px rgba(255, 200, 50, 0)); }
            50% { filter: drop-shadow(0 0 8px rgba(255, 200, 50, 0.6)); }
        }

        .nav-menu { display: none; list-style: none; margin: 0; padding: 0; gap: 0.5rem; font-weight: 500; font-size: 0.875rem; }
        @media (min-width: 768px) { .nav-menu { display: flex; } }
        .nav-menu li a { color: #475569; text-decoration: none; padding: 8px 14px; border-radius: 50px; display: inline-block; }
        .nav-menu li a:hover { color: #0f172a; background: rgba(15, 23, 42, 0.05); }
        .nav-menu li a.active { color: #1DB9A0; background: rgba(29, 185, 160, 0.1); }

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

        /* Form Styles */
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #fff;
            font-size: 14px;
            color: #0f172a;
            outline: none;
            transition: all 0.2s;
        }
        .form-input:focus {
            border-color: #1DB9A0;
            box-shadow: 0 0 0 3px rgba(29, 185, 160, 0.1);
        }
        .form-input::placeholder { color: #94a3b8; }

        /* Footer Grid */
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 48px; margin-bottom: 48px; }

        @media (max-width: 768px) {
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
    <!-- Navigation -->
    <div class="navbar-sticky">
        <div class="navbar-wrapper">
            <nav class="navbar">
                <a href="/" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                    <img src="/logo.png" alt="BlinkStudy" class="logo-blink" style="width: 40px; height: 40px; object-fit: contain;">
                    <span style="font-weight: 700; font-size: 1.2rem; background: linear-gradient(135deg, #0f172a 0%, #334155 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">BlinkStudy</span>
                </a>

                <ul class="nav-menu">
                    <li><a href="/#features">Features</a></li>
                    <li><a href="/plans">Plans</a></li>
                    <li><a href="/#testimonials">Reviews</a></li>
                    <li><a href="/support" class="active">Support</a></li>
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
        <div style="max-width: 900px; margin: 0 auto; padding: 40px 24px 80px;">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 48px;">
                <span style="display: inline-block; background: linear-gradient(135deg, #1DB9A0, #14b8a6); color: white; padding: 6px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; margin-bottom: 16px;">SUPPORT</span>
                <h1 style="font-size: 2.25rem; font-weight: 700; color: #0f172a; margin-bottom: 12px;">How Can We Help?</h1>
                <p style="font-size: 15px; color: #64748b; max-width: 500px; margin: 0 auto;">Have a question or need help? Reach out to us and we'll get back to you as soon as possible.</p>
            </div>

            <!-- Contact Cards -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
                <!-- Email Card -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="width: 50px; height: 50px; background: rgba(29, 185, 160, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <span class="material-symbols-outlined" style="color: #1DB9A0; font-size: 24px;">mail</span>
                    </div>
                    <h3 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">Email Us</h3>
                    <a href="mailto:support@blinkstudy.in" style="color: #1DB9A0; font-size: 14px; text-decoration: none;">support@blinkstudy.in</a>
                    <p style="color: #94a3b8; font-size: 12px; margin-top: 8px;">We reply within 24 hours</p>
                </div>

                <!-- Response Time -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="width: 50px; height: 50px; background: rgba(245, 158, 11, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <span class="material-symbols-outlined" style="color: #f59e0b; font-size: 24px;">schedule</span>
                    </div>
                    <h3 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">Response Time</h3>
                    <p style="color: #64748b; font-size: 14px;">Usually within 2-4 hours</p>
                    <p style="color: #94a3b8; font-size: 12px; margin-top: 8px;">Mon - Sat, 9AM - 7PM IST</p>
                </div>

                <!-- FAQ -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="width: 50px; height: 50px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <span class="material-symbols-outlined" style="color: #3b82f6; font-size: 24px;">help</span>
                    </div>
                    <h3 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">FAQ</h3>
                    <p style="color: #64748b; font-size: 14px;">Check common questions</p>
                    <a href="/#faqs" style="color: #1DB9A0; font-size: 12px; text-decoration: none; margin-top: 8px; display: inline-block;">View FAQ →</a>
                </div>
            </div>

            <!-- Contact Form -->
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Send us a message</h2>
                <p style="color: #64748b; font-size: 14px; margin-bottom: 24px;">Fill out the form and we'll get back to you shortly.</p>

                <!-- Success/Error Messages -->
                <div id="form-success" style="display: none; margin-bottom: 20px; padding: 16px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="material-symbols-outlined" style="color: #22c55e;">check_circle</span>
                        <p style="color: #22c55e; font-size: 14px; font-weight: 500;">Message sent successfully! We'll get back to you soon.</p>
                    </div>
                </div>
                <div id="form-error" style="display: none; margin-bottom: 20px; padding: 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="material-symbols-outlined" style="color: #ef4444;">error</span>
                        <p style="color: #ef4444; font-size: 14px; font-weight: 500;" id="form-error-text">Something went wrong. Please try again.</p>
                    </div>
                </div>

                <form id="contact-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #64748b; margin-bottom: 6px;">Full Name *</label>
                            <input type="text" name="name" required class="form-input" placeholder="Your full name">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #64748b; margin-bottom: 6px;">Email Address *</label>
                            <input type="email" name="email" required class="form-input" placeholder="you@example.com">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #64748b; margin-bottom: 6px;">Phone (Optional)</label>
                            <input type="tel" name="phone" class="form-input" placeholder="+91 98765 43210">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #64748b; margin-bottom: 6px;">Subject *</label>
                            <select name="subject" required class="form-input">
                                <option value="">Select a topic</option>
                                <option value="General Inquiry">General Inquiry</option>
                                <option value="Technical Issue">Technical Issue</option>
                                <option value="Billing & Payment">Billing & Payment</option>
                                <option value="Account Issue">Account Issue</option>
                                <option value="Feature Request">Feature Request</option>
                                <option value="Refund Request">Refund Request</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #64748b; margin-bottom: 6px;">Message *</label>
                        <textarea name="message" rows="5" required class="form-input" style="resize: none;" placeholder="Describe your issue or question in detail..."></textarea>
                    </div>

                    <button type="submit" id="submit-btn" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: linear-gradient(135deg, #1DB9A0, #14b8a6); color: white; font-weight: 600; font-size: 14px; border: none; border-radius: 12px; cursor: pointer;">
                        <span class="material-symbols-outlined" style="font-size: 18px;">send</span>
                        <span id="submit-text">Send Message</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
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

    <script>
    document.getElementById('contact-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = document.getElementById('submit-btn');
        const submitText = document.getElementById('submit-text');
        const successEl = document.getElementById('form-success');
        const errorEl = document.getElementById('form-error');
        const errorText = document.getElementById('form-error-text');

        // Hide previous messages
        successEl.style.display = 'none';
        errorEl.style.display = 'none';

        // Disable button
        btn.disabled = true;
        btn.style.opacity = '0.7';
        submitText.textContent = 'Sending...';

        try {
            const formData = new FormData(this);
            const response = await fetch('/contact', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok && data.success) {
                successEl.style.display = 'block';
                this.reset();
                successEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                errorText.textContent = data.message || 'Something went wrong. Please try again.';
                errorEl.style.display = 'block';
                errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } catch (err) {
            errorText.textContent = 'Network error. Please check your connection and try again.';
            errorEl.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.style.opacity = '1';
            submitText.textContent = 'Send Message';
        }
    });
    </script>
</body>
</html>
