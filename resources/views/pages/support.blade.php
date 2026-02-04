@extends('layouts.app')

@section('title', 'Support - ' . config('app.name', 'BlinkStudy'))
@section('description', 'Get help and support from ' . config('app.name', 'BlinkStudy') . '. Contact us via email or use our contact form.')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-12">

    <!-- Header -->
    <div class="text-center mb-10">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full dark:bg-primary/10 bg-primary/5 border dark:border-primary/20 border-primary/10 mb-4">
            <span class="material-symbols-outlined text-primary text-sm">support_agent</span>
            <span class="text-primary text-xs font-medium">We're here to help</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-bold dark:text-white text-gray-900 mb-3">Support Center</h1>
        <p class="dark:text-gray-400 text-gray-600 max-w-xl mx-auto">Have a question or need help? Reach out to us and we'll get back to you as soon as possible.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-6 mb-10">
        <!-- Email Card -->
        <div class="dark:bg-white/[0.02] bg-white border dark:border-white/[0.06] border-gray-200 rounded-2xl p-6 text-center">
            <div class="w-12 h-12 rounded-xl dark:bg-primary/10 bg-primary/5 flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-primary text-2xl">mail</span>
            </div>
            <h3 class="font-semibold dark:text-white text-gray-900 mb-1">Email Us</h3>
            <a href="mailto:{{ config('services.support_email') }}" class="text-primary text-sm hover:underline">{{ config('services.support_email') }}</a>
            <p class="dark:text-gray-500 text-gray-400 text-xs mt-2">We reply within 24 hours</p>
        </div>

        <!-- Response Time -->
        <div class="dark:bg-white/[0.02] bg-white border dark:border-white/[0.06] border-gray-200 rounded-2xl p-6 text-center">
            <div class="w-12 h-12 rounded-xl dark:bg-secondary/10 bg-secondary/5 flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-secondary text-2xl">schedule</span>
            </div>
            <h3 class="font-semibold dark:text-white text-gray-900 mb-1">Response Time</h3>
            <p class="text-sm dark:text-gray-400 text-gray-600">Usually within 2-4 hours</p>
            <p class="dark:text-gray-500 text-gray-400 text-xs mt-2">Mon - Sat, 9AM - 7PM IST</p>
        </div>

        <!-- FAQ -->
        <div class="dark:bg-white/[0.02] bg-white border dark:border-white/[0.06] border-gray-200 rounded-2xl p-6 text-center">
            <div class="w-12 h-12 rounded-xl dark:bg-info/10 bg-info/5 flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-info text-2xl">help</span>
            </div>
            <h3 class="font-semibold dark:text-white text-gray-900 mb-1">FAQ</h3>
            <p class="text-sm dark:text-gray-400 text-gray-600">Check common questions</p>
            <a href="{{ route('faq') }}" class="text-primary text-xs hover:underline mt-2 inline-block">View FAQ</a>
        </div>
    </div>

    <!-- Contact Form -->
    <div class="dark:bg-white/[0.02] bg-white border dark:border-white/[0.06] border-gray-200 rounded-2xl p-6 md:p-8 mb-10">
        <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-1">Send us a message</h2>
        <p class="dark:text-gray-500 text-gray-500 text-sm mb-6">Fill out the form and we'll get back to you shortly.</p>

        <!-- Success/Error Messages -->
        <div id="form-success" class="hidden mb-4 p-4 bg-success/10 border border-success/20 rounded-xl">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-success text-lg">check_circle</span>
                <p class="text-success text-sm font-medium">Message sent successfully! We'll get back to you soon.</p>
            </div>
        </div>
        <div id="form-error" class="hidden mb-4 p-4 bg-error/10 border border-error/20 rounded-xl">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-error text-lg">error</span>
                <p class="text-error text-sm font-medium" id="form-error-text">Something went wrong. Please try again.</p>
            </div>
        </div>

        <form id="contact-form" class="space-y-4">
            @csrf
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium dark:text-gray-400 text-gray-600 mb-1.5">Full Name *</label>
                    <input type="text" name="name" required
                           class="w-full px-4 py-2.5 rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 dark:text-white text-gray-900 text-sm placeholder-gray-400 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition"
                           placeholder="Your full name">
                </div>
                <div>
                    <label class="block text-xs font-medium dark:text-gray-400 text-gray-600 mb-1.5">Email Address *</label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-2.5 rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 dark:text-white text-gray-900 text-sm placeholder-gray-400 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition"
                           placeholder="you@example.com">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium dark:text-gray-400 text-gray-600 mb-1.5">Phone (Optional)</label>
                    <input type="tel" name="phone"
                           class="w-full px-4 py-2.5 rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 dark:text-white text-gray-900 text-sm placeholder-gray-400 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition"
                           placeholder="+91 98765 43210">
                </div>
                <div>
                    <label class="block text-xs font-medium dark:text-gray-400 text-gray-600 mb-1.5">Subject *</label>
                    <select name="subject" required
                            class="w-full px-4 py-2.5 rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 dark:text-white text-gray-900 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition">
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

            <div>
                <label class="block text-xs font-medium dark:text-gray-400 text-gray-600 mb-1.5">Message *</label>
                <textarea name="message" rows="5" required
                          class="w-full px-4 py-2.5 rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 dark:text-white text-gray-900 text-sm placeholder-gray-400 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition resize-none"
                          placeholder="Describe your issue or question in detail..."></textarea>
            </div>

            <button type="submit" id="submit-btn"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="material-symbols-outlined text-lg">send</span>
                <span id="submit-text">Send Message</span>
            </button>
        </form>
    </div>

    <!-- FAQ Link -->
    <div id="faq" class="mb-10 text-center">
        <div class="dark:bg-white/[0.02] bg-white border dark:border-white/[0.06] border-gray-200 rounded-2xl p-6">
            <span class="material-symbols-outlined text-primary text-3xl mb-3 block">quiz</span>
            <h3 class="font-semibold dark:text-white text-gray-900 mb-2">Frequently Asked Questions</h3>
            <p class="dark:text-gray-400 text-gray-600 text-sm mb-4">Find answers to 67+ common questions about BlinkStudy.</p>
            <a href="{{ route('faq') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary/90 transition-colors">
                <span class="material-symbols-outlined text-lg">arrow_forward</span>
                View Full FAQ
            </a>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.getElementById('contact-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const successEl = document.getElementById('form-success');
    const errorEl = document.getElementById('form-error');
    const errorText = document.getElementById('form-error-text');

    // Hide previous messages
    successEl.classList.add('hidden');
    errorEl.classList.add('hidden');

    // Disable button
    btn.disabled = true;
    submitText.textContent = 'Sending...';

    try {
        const formData = new FormData(this);
        const response = await fetch('{{ route("contact.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData,
        });

        const data = await response.json();

        if (response.ok && data.success) {
            successEl.classList.remove('hidden');
            this.reset();
            successEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            errorText.textContent = data.message || 'Something went wrong. Please try again.';
            errorEl.classList.remove('hidden');
            errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    } catch (err) {
        errorText.textContent = 'Network error. Please check your connection and try again.';
        errorEl.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        submitText.textContent = 'Send Message';
    }
});
</script>
@endpush
@endsection
