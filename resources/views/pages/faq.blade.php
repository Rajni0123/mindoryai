@extends('layouts.app')

@section('title', 'FAQ - ' . config('app.name', 'BlinkStudy'))
@section('description', 'Find quick answers to common questions about ' . config('app.name') . '. Account, billing, features, refunds, and more.')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">

    <!-- Header -->
    <div class="text-center mb-10">
        <div class="w-14 h-14 dark:bg-primary/10 bg-primary/5 rounded-2xl flex items-center justify-center mx-auto mb-4 border dark:border-primary/20 border-primary/10">
            <span class="material-symbols-outlined text-primary text-2xl">quiz</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-bold dark:text-white text-gray-900 mb-3">Frequently Asked Questions</h1>
        <div class="w-20 h-1 bg-primary mx-auto mb-4"></div>
        <p class="dark:text-gray-400 text-gray-600 max-w-xl mx-auto text-sm">
            Find quick answers to common questions about {{ config('app.name') }}. Can't find what you're looking for? Contact us at
            <a href="mailto:{{ config('services.support_email') }}" class="text-primary hover:underline">{{ config('services.support_email') }}</a>
        </p>
        <p class="dark:text-gray-500 text-gray-500 text-xs mt-2">Last Updated: January 30, 2025</p>
    </div>

    @php
    $supportEmail = config('services.support_email');
    $siteHost = parse_url(config('app.url'), PHP_URL_HOST) ?: config('services.main_domain', 'example.com');
    $siteUrl = config('app.url');
    $sections = [
        [
            'icon' => 'smartphone',
            'title' => 'Account & Login',
            'color' => 'primary',
            'faqs' => [
                ['q' => 'How do I create an account on BlinkStudy?', 'a' => 'Simply download the BlinkStudy app or visit ' . $siteHost . ', enter your mobile number, verify the OTP sent to your phone, and you\'re ready to start learning!'],
                ['q' => 'I\'m not receiving the OTP. What should I do?', 'a' => "Check if you entered the correct mobile number. Wait for 2-3 minutes (sometimes there's a delay). Check your phone's message/SMS app. Ensure you have network connectivity. Try requesting OTP again after 5 minutes. Contact support at {$supportEmail} if issue persists."],
                ['q' => 'How many times can I request OTP?', 'a' => 'You can request OTP maximum 5 times per hour for security reasons. After that, please wait for 1 hour before trying again.'],
                ['q' => 'Can I use one account on multiple devices?', 'a' => 'Yes! You can log in to your BlinkStudy account on multiple devices (phone, tablet, computer) simultaneously. Your progress syncs across all devices.'],
                ['q' => 'How do I reset/change my password?', 'a' => 'BlinkStudy uses OTP-based login, so there\'s no password to remember! Simply enter your mobile number and verify OTP each time you log in.'],
                ['q' => 'Can I change my registered mobile number?', 'a' => 'Yes, contact our support team at ' . $supportEmail . ' with your current mobile number and new mobile number. We\'ll assist you with the change.'],
                ['q' => 'How do I delete my account?', 'a' => 'Go to Settings → Account → Delete Account, or email us at ' . $supportEmail . '. Please note that account deletion is permanent and cannot be undone.'],
            ],
        ],
        [
            'icon' => 'credit_card',
            'title' => 'Subscription & Pricing',
            'color' => 'secondary',
            'faqs' => [
                ['q' => 'What subscription plans does BlinkStudy offer?', 'a' => 'We offer four plans: FREE (₹0 - 10 chats/day, 2 quizzes/day, ad-supported), STARTER (₹149/month - Unlimited chat, 5 quizzes/day, ad-free), PRO (₹399/month - 20 quizzes/day, unlimited topic quizzes & exam prep, HD videos, priority processing), and ULTIMATE (₹899/month - Unlimited everything, 50 whiteboard videos/month, advanced analytics, up to 5 devices).'],
                ['q' => 'Is there a free trial?', 'a' => 'Yes! All new users start with a FREE plan to explore BlinkStudy. You can upgrade anytime to unlock premium features.'],
                ['q' => 'How do I upgrade my plan?', 'a' => 'Go to Settings → Subscription → Choose Plan → Make Payment. Your upgrade takes effect immediately!'],
                ['q' => 'Can I downgrade my plan?', 'a' => 'Yes, you can downgrade from Ultimate → Pro → Starter → Free anytime. The change will take effect at the end of your current billing cycle. No refund for the difference.'],
                ['q' => 'What payment methods are accepted?', 'a' => 'We accept UPI (Google Pay, PhonePe, Paytm, etc.), Credit/Debit Cards (Visa, Mastercard, RuPay), Net Banking, and Mobile Wallets (Paytm, Mobikwik, etc.).'],
                ['q' => 'Are prices inclusive of GST/taxes?', 'a' => 'Yes, all prices displayed are inclusive of applicable taxes.'],
                ['q' => 'Do subscriptions auto-renew?', 'a' => 'Yes, subscriptions automatically renew every month unless you cancel. You\'ll receive a reminder 3 days before renewal.'],
                ['q' => 'How do I cancel my subscription?', 'a' => 'Go to Settings → Subscription → Cancel Plan. You can also email ' . $supportEmail . '. You\'ll continue to have access until the end of your billing period.'],
            ],
        ],
        [
            'icon' => 'payments',
            'title' => 'Payments & Refunds',
            'color' => 'success',
            'faqs' => [
                ['q' => 'Is my payment information secure?', 'a' => 'Absolutely! All payments are processed through secure, PCI-DSS compliant payment gateways. We do NOT store your card/banking details.'],
                ['q' => 'My payment failed but money was deducted. What should I do?', 'a' => 'Don\'t worry! If payment fails, the amount is usually refunded within 5-7 business days automatically. If not, contact ' . $supportEmail . ' with your transaction ID.'],
                ['q' => 'Can I get a refund?', 'a' => 'Yes! We offer a 7-day money-back guarantee for first-time subscribers. Simply email ' . $supportEmail . ' within 7 days of purchase.'],
                ['q' => 'How long does refund processing take?', 'a' => 'Refunds are processed within 7-10 business days after approval. The amount is credited to your original payment method.'],
                ['q' => 'Do I get a receipt/invoice for my payment?', 'a' => 'Yes! An invoice is automatically sent to your registered email after successful payment. You can also download it from Settings → Billing History.'],
            ],
        ],
        [
            'icon' => 'school',
            'title' => 'Features & Usage',
            'color' => 'info',
            'faqs' => [
                ['q' => 'What is "Scan to Solve"?', 'a' => 'Simply take a photo of any math, physics, chemistry, or biology problem, and our AI instantly provides step-by-step solutions with explanations!'],
                ['q' => 'How many problems can I scan per day?', 'a' => 'It depends on your plan: Free: 3 scans/day, Basic: 15 scans/day, Pro: 100 scans/day.'],
                ['q' => 'What are Video Quizzes?', 'a' => 'AI-generated video content with interactive questions designed for JEE and NEET preparation. Learn concepts through engaging videos!'],
                ['q' => 'What are Whiteboard Videos?', 'a' => 'Hand-drawn style explanatory videos that make complex concepts easy to understand - just like having a personal tutor!'],
                ['q' => 'Can I upload PDFs for analysis?', 'a' => 'Yes! Upload your study materials (notes, textbooks) and get AI-powered summaries, key points, and practice questions.'],
                ['q' => 'What\'s the maximum PDF size I can upload?', 'a' => 'Free: 1 PDF/month (5 pages max), Basic: 10 PDFs/month (20 pages max per PDF), Pro: 150 PDFs/month (100 pages max per PDF).'],
                ['q' => 'Does BlinkStudy work offline?', 'a' => 'Currently, BlinkStudy requires internet connection for AI-powered features. Offline mode is coming soon!'],
                ['q' => 'What subjects does BlinkStudy cover?', 'a' => 'We cover all major subjects for JEE and NEET: Mathematics, Physics, Chemistry, Biology (Botany & Zoology).'],
                ['q' => 'Can I use BlinkStudy for board exams (not just JEE/NEET)?', 'a' => 'Absolutely! While optimized for JEE/NEET, BlinkStudy works great for Class 11-12 board exam preparation too.'],
            ],
        ],
        [
            'icon' => 'smart_toy',
            'title' => 'AI Tutor & Accuracy',
            'color' => 'secondary',
            'faqs' => [
                ['q' => 'How accurate is the AI tutor?', 'a' => 'Our AI is trained on millions of problems and maintains high accuracy. However, we always recommend verifying important concepts with your teachers or textbooks.'],
                ['q' => 'What if the AI gives a wrong answer?', 'a' => 'While rare, if you find an error, please report it via the "Report Issue" button. We continuously improve our AI based on feedback.'],
                ['q' => 'Can the AI tutor explain concepts in Hindi?', 'a' => 'Currently, BlinkStudy primarily works in English. Hindi language support is on our roadmap!'],
                ['q' => 'Does the AI tutor remember my previous questions?', 'a' => 'Yes! The AI maintains context within a session and can reference previous conversations to provide better assistance.'],
            ],
        ],
        [
            'icon' => 'bar_chart',
            'title' => 'Performance & History',
            'color' => 'primary',
            'faqs' => [
                ['q' => 'How long is my study history saved?', 'a' => 'Free: 3 days, Basic: 7 days, Pro: Unlimited (lifetime).'],
                ['q' => 'Can I track my progress over time?', 'a' => 'Yes! Go to Dashboard to see detailed analytics including problems solved, topics covered, quiz scores, and strengths & weaknesses.'],
                ['q' => 'Can I export my performance data?', 'a' => 'Currently not available, but this feature is in development for Pro users.'],
            ],
        ],
        [
            'icon' => 'build',
            'title' => 'Technical Issues',
            'color' => 'error',
            'faqs' => [
                ['q' => 'The app is not loading. What should I do?', 'a' => 'Check your internet connection. Close and reopen the app. Clear app cache (Settings → Apps → BlinkStudy → Clear Cache). Update to the latest version. Restart your device. Contact support if issue persists.'],
                ['q' => 'Videos are not playing properly. How to fix?', 'a' => 'Ensure stable internet connection (3G/4G/WiFi). Lower video quality in settings. Clear app cache. Check if you have sufficient storage space. Try restarting the app.'],
                ['q' => 'I\'m getting "daily limit reached" error. Why?', 'a' => 'You\'ve exhausted your daily quota for that feature. Limits reset at midnight (IST) or upgrade your plan for higher limits.'],
                ['q' => 'Which devices are compatible with BlinkStudy?', 'a' => 'Android: 5.0 and above, iOS: 12.0 and above, Web: All modern browsers (Chrome, Firefox, Safari, Edge).'],
                ['q' => 'Why is the app using so much storage?', 'a' => 'Cached videos and images may consume storage. Go to Settings → Clear Cache to free up space.'],
            ],
        ],
        [
            'icon' => 'shield',
            'title' => 'Privacy & Security',
            'color' => 'success',
            'faqs' => [
                ['q' => 'Is my personal data safe with BlinkStudy?', 'a' => 'Yes! We use bank-grade encryption and follow strict data protection policies. Read our Privacy Policy for details.'],
                ['q' => 'Do you sell my data to third parties?', 'a' => 'Absolutely NOT! We never sell your personal information. Your trust is our priority.'],
                ['q' => 'Can others see my study progress?', 'a' => 'No, your account and progress are completely private and visible only to you.'],
                ['q' => 'What happens to my data if I delete my account?', 'a' => 'All your personal data is permanently deleted within 30 days as per our Privacy Policy.'],
            ],
        ],
        [
            'icon' => 'support_agent',
            'title' => 'Support & Help',
            'color' => 'info',
            'faqs' => [
                ['q' => 'How do I contact customer support?', 'a' => 'Email us at ' . $supportEmail . '. We respond within 24 hours (Monday-Saturday, 9 AM - 8 PM IST).'],
                ['q' => 'Can I get a demo before subscribing?', 'a' => 'Yes! The FREE plan is your demo. Explore all features with daily limits before upgrading.'],
                ['q' => 'Do you have phone support?', 'a' => 'Currently, we provide support via email only for better documentation and faster resolution.'],
                ['q' => 'How do I report a bug or technical issue?', 'a' => 'Email ' . $supportEmail . ' with: Device details (Android/iOS, version), App version, Screenshot/screen recording, and a detailed description of the issue.'],
            ],
        ],
        [
            'icon' => 'card_giftcard',
            'title' => 'Referrals & Rewards',
            'color' => 'secondary',
            'faqs' => [
                ['q' => 'Is there a referral program?', 'a' => 'Referral program is coming soon! You\'ll be able to earn rewards by inviting friends to BlinkStudy.'],
                ['q' => 'Do you offer student discounts?', 'a' => 'We keep our pricing affordable for all students. Special discounts are announced during festive seasons - follow us on social media!'],
                ['q' => 'Are there any scholarships available?', 'a' => 'Scholarship programs for deserving students are under planning. Stay tuned for announcements!'],
            ],
        ],
        [
            'icon' => 'menu_book',
            'title' => 'Content & Syllabus',
            'color' => 'primary',
            'faqs' => [
                ['q' => 'Is the content aligned with JEE/NEET syllabus?', 'a' => 'Yes! Our content is specifically designed according to the latest JEE Main, JEE Advanced, and NEET syllabus.'],
                ['q' => 'Do you provide previous year questions?', 'a' => 'Yes! Exam Prep feature includes previous year questions with detailed solutions.'],
                ['q' => 'How often is content updated?', 'a' => 'We regularly update content to match the latest exam patterns and syllabus changes.'],
                ['q' => 'Can I request specific topics to be added?', 'a' => 'Yes! Share your suggestions at ' . $supportEmail . '. We prioritize user-requested content.'],
            ],
        ],
        [
            'icon' => 'gavel',
            'title' => 'Policies & Legal',
            'color' => 'error',
            'faqs' => [
                ['q' => 'Where can I read your Terms & Conditions?', 'a' => 'Visit ' . $siteHost . '/terms or check the app footer for complete Terms & Conditions.'],
                ['q' => 'What is your Privacy Policy?', 'a' => 'Full Privacy Policy is available at ' . $siteHost . '/privacy'],
                ['q' => 'What is your Refund Policy?', 'a' => 'Detailed Refund & Cancellation Policy: ' . $siteHost . '/refund'],
            ],
        ],
        [
            'icon' => 'star',
            'title' => 'Miscellaneous',
            'color' => 'secondary',
            'faqs' => [
                ['q' => 'Can schools/coaching institutes use BlinkStudy?', 'a' => 'Yes! We offer bulk licensing for educational institutions. Contact ' . $supportEmail . ' for details.'],
                ['q' => 'Is BlinkStudy available in languages other than English?', 'a' => 'Currently English only. Hindi and other regional languages are planned for future releases.'],
                ['q' => 'Can I suggest new features?', 'a' => 'Absolutely! We love user feedback. Email your ideas to ' . $supportEmail . ''],
                ['q' => 'How is BlinkStudy different from other learning apps?', 'a' => 'BlinkStudy combines advanced AI technology, personalized learning paths, scan-to-solve convenience, affordable pricing, exam-focused content, and 24/7 availability.'],
                ['q' => 'Who founded BlinkStudy?', 'a' => 'BlinkStudy was founded by Rajnish Kumar, an educator and technology enthusiast from Patna, Bihar.'],
            ],
        ],
        [
            'icon' => 'rocket_launch',
            'title' => 'Future Updates',
            'color' => 'primary',
            'faqs' => [
                ['q' => 'What new features are coming soon?', 'a' => 'Upcoming features include: Live doubt-solving sessions, Peer learning communities, Offline mode, Hindi language support, Advanced analytics, and Scholarship programs.'],
                ['q' => 'How can I stay updated about new features?', 'a' => 'Enable notifications in the app or follow us on social media for the latest updates!'],
            ],
        ],
    ];
    @endphp

    <!-- Quick Navigation -->
    <div class="dark:bg-white/[0.02] bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5 mb-8 shadow-sm">
        <h3 class="text-sm font-semibold dark:text-white text-gray-900 mb-3">Jump to Section</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($sections as $i => $section)
            <a href="#section-{{ $i }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg dark:bg-white/5 bg-gray-100 dark:text-gray-300 text-gray-600 hover:text-primary dark:hover:bg-white/10 hover:bg-primary/5 transition-colors border dark:border-white/5 border-gray-200">
                <span class="material-symbols-outlined text-sm">{{ $section['icon'] }}</span>
                {{ $section['title'] }}
            </a>
            @endforeach
        </div>
    </div>

    <!-- FAQ Sections -->
    <div class="space-y-8">
        @foreach($sections as $i => $section)
        <div id="section-{{ $i }}" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl dark:bg-{{ $section['color'] }}/10 bg-{{ $section['color'] }}/5 flex items-center justify-center border dark:border-{{ $section['color'] }}/20 border-{{ $section['color'] }}/10">
                    <span class="material-symbols-outlined text-{{ $section['color'] }} text-xl">{{ $section['icon'] }}</span>
                </div>
                <h2 class="text-lg font-bold dark:text-white text-gray-900">{{ $section['title'] }}</h2>
            </div>

            <div class="space-y-2">
                @foreach($section['faqs'] as $faq)
                <details class="group dark:bg-white/[0.02] bg-white border dark:border-white/[0.06] border-gray-200 rounded-xl overflow-hidden">
                    <summary class="flex items-center justify-between px-5 py-4 cursor-pointer list-none">
                        <span class="text-sm font-medium dark:text-white text-gray-900 pr-4">{{ $faq['q'] }}</span>
                        <span class="material-symbols-outlined text-gray-400 text-lg transition-transform group-open:rotate-180 flex-shrink-0">expand_more</span>
                    </summary>
                    <div class="px-5 pb-4 border-t dark:border-white/5 border-gray-100 pt-3">
                        <p class="dark:text-gray-400 text-gray-600 text-sm leading-relaxed">{{ $faq['a'] }}</p>
                    </div>
                </details>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <!-- Still Have Questions -->
    <div class="mt-12 dark:bg-white/[0.02] bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-6 md:p-8 text-center shadow-sm">
        <div class="w-14 h-14 dark:bg-primary/10 bg-primary/5 rounded-2xl flex items-center justify-center mx-auto mb-4 border dark:border-primary/20 border-primary/10">
            <span class="material-symbols-outlined text-primary text-2xl">contact_support</span>
        </div>
        <h3 class="text-lg font-bold dark:text-white text-gray-900 mb-2">Still Have Questions?</h3>
        <p class="dark:text-gray-400 text-gray-600 text-sm mb-4">If your question isn't answered here, we're here to help!</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 text-sm">
            <a href="mailto:{{ $supportEmail }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl font-medium hover:bg-primary/90 transition-colors">
                <span class="material-symbols-outlined text-lg">mail</span>
                {{ $supportEmail }}
            </a>
            <a href="{{ route('support') }}" class="inline-flex items-center gap-2 px-5 py-2.5 dark:bg-white/5 bg-gray-100 dark:text-white text-gray-700 rounded-xl font-medium hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                <span class="material-symbols-outlined text-lg">support_agent</span>
                Contact Form
            </a>
        </div>
        <p class="dark:text-gray-500 text-gray-500 text-xs mt-4">Support Hours: Mon-Sat, 9 AM - 8 PM (IST) | Response Time: Within 24 hours</p>
    </div>

</div>
@endsection
