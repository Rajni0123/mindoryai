<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Page;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supportEmail = config('services.support_email', 'support@' . env('MAIN_DOMAIN', 'example.com'));
        $siteHost = parse_url(config('app.url'), PHP_URL_HOST) ?: env('MAIN_DOMAIN', 'example.com');
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about',
                'content' => $this->getAboutContent(),
                'is_active' => true,
                'order' => 1,
                'meta_title' => 'About Us - BlinkStudy AI',
                'meta_description' => 'Learn about BlinkStudy AI - Your AI-powered doubt solver for students. Get instant, step-by-step explanations for Math, Science, Literature, and more.',
                'meta_keywords' => 'BlinkStudy AI, about, AI education, student learning, doubt solver',
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'content' => $this->getContactContent(),
                'is_active' => true,
                'order' => 2,
                'meta_title' => 'Contact Us - BlinkStudy AI',
                'meta_description' => 'Get in touch with the BlinkStudy AI team. We\'re here to help with any questions or support you need.',
                'meta_keywords' => 'contact, support, help, BlinkStudy AI, customer service',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => $this->getPrivacyContent(),
                'is_active' => true,
                'order' => 3,
                'meta_title' => 'Privacy Policy - BlinkStudy AI',
                'meta_description' => 'Read our privacy policy to understand how we protect your data and privacy when using BlinkStudy AI services.',
                'meta_keywords' => 'privacy policy, data protection, user privacy, BlinkStudy AI',
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => $this->getTermsContent(),
                'is_active' => true,
                'order' => 4,
                'meta_title' => 'Terms of Service - BlinkStudy AI',
                'meta_description' => 'Read our terms of service to understand the rules and guidelines for using BlinkStudy AI platform.',
                'meta_keywords' => 'terms of service, terms and conditions, user agreement, BlinkStudy AI',
            ],
            [
                'title' => 'Cancellation & Refund Policy',
                'slug' => 'cancellation-refund-policy',
                'content' => $this->getRefundContent(),
                'is_active' => true,
                'order' => 5,
                'meta_title' => 'Cancellation & Refund Policy - BlinkStudy AI',
                'meta_description' => 'Learn about our cancellation and refund policy for subscriptions and purchases on BlinkStudy AI.',
                'meta_keywords' => 'refund policy, cancellation policy, subscription refund, BlinkStudy AI',
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }

    private function getAboutContent()
    {
        return '<h2>Welcome to BlinkStudy AI</h2>
        <p>At BlinkStudy AI, we believe every student deserves instant access to quality educational support. Our AI-powered platform is designed to be your personal study companion, available 24/7 to help you understand complex concepts, solve problems, and excel in your academic journey.</p>

        <h2>Our Mission</h2>
        <p>We\'re on a mission to make quality education accessible to every student through the power of artificial intelligence. By combining cutting-edge AI technology with educational expertise, we provide step-by-step explanations that help students learn, understand, and retain knowledge more effectively.</p>

        <h2>What We Offer</h2>
        <ul>
            <li><strong>Instant Doubt Solving:</strong> Upload images or type questions to get immediate, detailed explanations</li>
            <li><strong>Step-by-Step Solutions:</strong> Understand the problem-solving process with detailed breakdowns</li>
            <li><strong>Multiple Subjects:</strong> Get help with Math, Science, Literature, and more</li>
            <li><strong>24/7 Availability:</strong> Learn anytime, anywhere - our AI never sleeps</li>
            <li><strong>Personalized Learning:</strong> Adapt the explanations to your learning style</li>
        </ul>

        <h2>Why Choose BlinkStudy AI?</h2>
        <p>Unlike traditional tutoring services, BlinkStudy AI provides instant answers without scheduling conflicts or waiting times. Our AI models are trained on vast educational content, ensuring accurate and comprehensive responses to your questions.</p>

        <h2>Contact Us</h2>
        <p>Have questions or feedback? We\'d love to hear from you! Reach out to us at <strong>' . $supportEmail . '</strong></p>';
    }

    private function getContactContent()
    {
        return '<h2>Get in Touch</h2>
        <p>Have a question, feedback, or need support? We\'re here to help! Fill out the form below or reach us through any of the channels listed.</p>

        <h2>Contact Information</h2>
        <ul>
            <li><strong>Email:</strong> ' . $supportEmail . '</li>
            <li><strong>Website:</strong> ' . $siteHost . '</li>
            <li><strong>Response Time:</strong> Within 24 hours</li>
        </ul>

        <h2>Office Hours</h2>
        <ul>
            <li><strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM IST</li>
            <li><strong>Saturday:</strong> 10:00 AM - 4:00 PM IST</li>
            <li><strong>Sunday:</strong> Closed</li>
        </ul>

        <h2>Frequently Asked Questions</h2>
        <p>Before reaching out, check our FAQ section where we address common questions about:</p>
        <ul>
            <li>Account creation and management</li>
            <li>Credits, payments, and subscriptions</li>
            <li>Voice messages and file uploads</li>
            <li>Data security and privacy</li>
            <li>Technical troubleshooting</li>
        </ul>

        <h2>We Value Your Feedback</h2>
        <p>Your feedback helps us improve BlinkStudy AI. Whether it\'s a feature request, bug report, or suggestion, we appreciate you taking the time to share your thoughts with us.</p>';
    }

    private function getPrivacyContent()
    {
        return '<h2>Privacy Policy for BlinkStudy AI</h2>
        <p><strong>Effective Date:</strong> ' . date('F d, Y') . '</p>

        <h2>1. Introduction</h2>
        <p>BlinkStudy AI ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our mobile application and related services.</p>

        <h2>2. Information We Collect</h2>
        <h3>Account Information</h3>
        <ul>
            <li>Name, email address, phone number</li>
            <li>Profile information and preferences</li>
            <li>Payment information (processed securely)</li>
        </ul>

        <h3>Usage Information</h3>
        <ul>
            <li>Questions and queries you submit</li>
            <li>Images and files you upload for analysis</li>
            <li>Chat history and conversation data</li>
            <li>Voice messages and transcriptions</li>
        </ul>

        <h3>Device Information</h3>
        <ul>
            <li>IP address and device identifiers</li>
            <li>Mobile device type and operating system</li>
            <li>App usage statistics and crash reports</li>
        </ul>

        <h2>3. How We Use Your Information</h2>
        <p>We use your information to:</p>
        <ul>
            <li>Provide and improve our AI services</li>
            <li>Process transactions and manage subscriptions</li>
            <li>Generate AI responses and recommendations</li>
            <li>Send technical notices and updates</li>
            <li>Respond to your inquiries and support requests</li>
            <li>Prevent fraud and abuse of our platform</li>
        </ul>

        <h2>4. Data Security</h2>
        <p>We implement industry-standard security measures to protect your data:</p>
        <ul>
            <li>End-to-end encryption for sensitive data</li>
            <li>Secure payment processing</li>
            <li>Regular security audits and updates</li>
            <li>Restricted access to user data</li>
        </ul>

        <h2>5. Data Retention</h2>
        <ul>
            <li><strong>Uploaded Images:</strong> Automatically deleted after 60 seconds</li>
            <li><strong>Chat History:</strong> Retained until you delete it</li>
            <li><strong>Account Data:</strong> Retained until account deletion</li>
        </ul>

        <h2>6. Your Rights</h2>
        <p>You have the right to:</p>
        <ul>
            <li>Access your personal data</li>
            <li>Correct inaccurate data</li>
            <li>Delete your account and data</li>
            <li>Opt-out of marketing communications</li>
            <li>Export your data</li>
        </ul>

        <h2>7. Third-Party Services</h2>
        <p>We use third-party services to power our AI features and process payments. These services have their own privacy policies:</p>
        <ul>
            <li><strong>AI Services:</strong> OpenAI, Google Cloud, Anthropic</li>
            <li><strong>Payment Processing:</strong> Razorpay, Stripe</li>
        </ul>

        <h2>8. Contact Us</h2>
        <p>If you have questions about this Privacy Policy, please contact us at <strong>' . $supportEmail . '</strong></p>';
    }

    private function getTermsContent()
    {
        return '<h2>Terms of Service</h2>
        <p><strong>Last Updated:</strong> ' . date('F d, Y') . '</p>

        <h2>1. Agreement to Terms</h2>
        <p>By accessing or using BlinkStudy AI ("Service"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, please do not use our Service.</p>

        <h2>2. Description of Service</h2>
        <p>BlinkStudy AI provides an AI-powered educational platform that includes:</p>
        <ul>
            <li>AI-powered doubt solving and explanations</li>
            <li>Image and document analysis</li>
            <li>Voice-to-text transcription</li>
            <li>Chat history management</li>
            <li>Premium subscription services</li>
        </ul>

        <h2>3. User Accounts</h2>
        <h3>Account Creation</h3>
        <ul>
            <li>You must provide accurate and complete information</li>
            <li>You are responsible for maintaining account security</li>
            <li>You must be at least 13 years old to use our Service</li>
            <li>One account per person is allowed</li>
        </ul>

        <h3>Account Responsibilities</h3>
        <ul>
            <li>Keep your password confidential</li>
            <li>Notify us immediately of unauthorized access</li>
            <li>You are liable for activities under your account</li>
        </ul>

        <h2>4. Acceptable Use Policy</h2>
        <h3>You Agree NOT To:</h3>
        <ul>
            <li>Use the Service for any illegal purpose</li>
            <li>Upload harmful, offensive, or inappropriate content</li>
            <li>Attempt to circumvent usage limits or restrictions</li>
            <li>Reverse engineer or attempt to extract our AI models</li>
            <li>Use automated tools to abuse the Service</li>
            <li>Share your account with others</li>
        </ul>

        <h2>5. Intellectual Property</h2>
        <h3>Our Rights</h3>
        <ul>
            <li>The Service and its content are owned by BlinkStudy AI</li>
            <li>Our AI models and algorithms are proprietary</li>
            <li>You may not copy, modify, or distribute our content</li>
        </ul>

        <h3>Your Rights</h3>
        <ul>
            <li>You retain ownership of content you submit</li>
            <li>You grant us a license to process your content</li>
            <li>AI-generated responses belong to you</li>
        </ul>

        <h2>6. Payment Terms</h2>
        <h3>Subscriptions</h3>
        <ul>
            <li>Subscriptions auto-renew unless cancelled</li>
            <li>Prices are subject to change with notice</li>
            <li>No refunds for partial subscription periods</li>
        </ul>

        <h3>Credit Packages</h3>
        <ul>
            <li>Credits are non-refundable once purchased</li>
            <li>Credits expire as specified at purchase</li>
            <li>We reserve the right to modify credit pricing</li>
        </ul>

        <h2>7. Service Availability</h2>
        <ul>
            <li>We strive for 99% uptime but don\'t guarantee it</li>
            <li>We may suspend service for maintenance</li>
            <li>We\'re not liable for service interruptions</li>
        </ul>

        <h2>8. Disclaimers</h2>
        <ul>
            <li>AI responses may not always be accurate</li>
            <li>The Service is provided "as is" without warranties</li>
            <li>We\'re not responsible for academic outcomes</li>
        </ul>

        <h2>9. Limitation of Liability</h2>
        <p>Our liability is limited to the amount you paid in the past 12 months. We\'re not liable for indirect, incidental, or consequential damages.</p>

        <h2>10. Termination</h2>
        <ul>
            <li>We may suspend or terminate your account for violations</li>
            <li>You may delete your account at any time</li>
            <li>Termination doesn\'t relieve you of payment obligations</li>
        </ul>

        <h2>11. Changes to Terms</h2>
        <p>We may modify these Terms at any time. Continued use of the Service constitutes acceptance of changes.</p>

        <h2>12. Contact Us</h2>
        <p>For questions about these Terms, contact us at <strong>' . $supportEmail . '</strong></p>';
    }

    private function getRefundContent()
    {
        return '<h2>Cancellation & Refund Policy</h2>
        <p><strong>Effective Date:</strong> ' . date('F d, Y') . '</p>

        <h2>1. Overview</h2>
        <p>Please read this policy carefully before making a purchase. All sales of credit packages and subscription plans are final and non-refundable except as specifically stated in this policy.</p>

        <h2>2. Refund Policy</h2>
        <h3>No Refunds For:</h3>
        <ul>
            <li>Used credits or partially consumed credit packages</li>
            <li>Subscription fees for the current billing period</li>
            <li>Voluntary subscription cancellations</li>
            <li>Change of mind after purchase</li>
        </ul>

        <h3>Refunds May Be Issued For:</h3>
        <ul>
            <li><strong>Technical Issues:</strong> If the Service fails to work due to our technical issues</li>
            <li><strong>Double Billing:</strong> If you were accidentally charged multiple times</li>
            <li><strong>Unauthorized Transactions:</strong> If you can prove the transaction was unauthorized</li>
            <li><strong>Service Not Delivered:</strong> If credits were not added to your account</li>
        </ul>

        <h2>3. Cancellation Policy</h2>
        <h3>How to Cancel:</h3>
        <ul>
            <li><strong>In-App:</strong> Go to Settings → Subscription → Cancel Subscription</li>
            <li><strong>Email:</strong> Send a cancellation request to ' . $supportEmail . '</li>
            <li><strong>Timing:</strong> Cancel at least 24 hours before renewal</li>
        </ul>

        <h3>Effect of Cancellation:</h3>
        <ul>
            <li>Access continues until the end of the current billing period</li>
            <li>No further charges will be made after cancellation</li>
            <li>Unused credits remain available for 30 days</li>
            <li>You can reactivate anytime</li>
        </ul>

        <h2>4. Subscription Renewals</h2>
        <ul>
            <li>Subscriptions auto-renew unless cancelled</li>
            <li>You will be charged on the renewal date</li>
            <li>No refunds for auto-renewals if cancellation wasn\'t processed</li>
            <li>We send renewal reminders 3 days before billing</li>
        </ul>

        <h2>5. Credit Expiration</h2>
        <ul>
            <li><strong>Daily Credits:</strong> Reset daily, don\'t accumulate</li>
            <li><strong>Purchased Credits:</strong> Valid for 30 days from purchase</li>
            <li><strong>Premium Credits:</strong> Valid for the subscription period</li>
            <li>Expired credits cannot be refunded or extended</li>
        </ul>

        <h2>6. Refund Request Process</h2>
        <h3>To Request a Refund:</h3>
        <ol>
            <li>Send an email to <strong>' . $supportEmail . '</strong></li>
            <li>Include your transaction ID</li>
            <li>Provide purchase date and amount</li>
            <li>Explain the reason for refund</li>
            <li>Attach relevant screenshots (if applicable)</li>
        </ol>

        <h3>Processing Time:</h3>
        <ul>
            <li>Refund requests are reviewed within 3-5 business days</li>
            <li>Approved refunds are processed within 7-10 business days</li>
            <li>Refunds are credited to the original payment method</li>
        </ul>

        <h2>7. Special Cases</h2>
        <h3>Technical Problems</h3>
        <ul>
            <li>We\'ll try to resolve technical issues first</li>
            <li>Refunds considered if issues can\'t be resolved</li>
            <li>Partial refunds may be offered for partial disruptions</li>
        </ul>

        <h3>Billing Errors</h3>
        <ul>
            <li>Report billing errors within 30 days</li>
            <li>We\'ll investigate and correct genuine errors</li>
            <li>Full refunds for confirmed billing mistakes</li>
        </ul>

        <h2>8. Dispute Resolution</h2>
        <ul>
            <li>Contact us first for any refund-related issues</li>
            <li>We aim for amicable resolution</li>
            <li>Legal recourse available as a last resort</li>
        </ul>

        <h2>9. Policy Changes</h2>
        <p>We reserve the right to modify this policy. Changes will be posted on this page with the updated effective date.</p>

        <h2>10. Contact Information</h2>
        <p>For refund requests or questions about this policy:</p>
        <ul>
            <li><strong>Email:</strong> ' . $supportEmail . '</li>
            <li><strong>Website:</strong> ' . $siteHost . '</li>
            <li><strong>Response Time:</strong> Within 24 hours</li>
        </ul>

        <h2>11. Consumer Protection</h2>
        <p>If you\'re a consumer in India, you have additional rights under the Consumer Protection Act 2019. This policy doesn\'t limit your statutory rights.</p>';
    }
}
