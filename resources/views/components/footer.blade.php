{{-- Shared Footer — clean light footer --}}
@php
    $footerLogo = \App\Models\HomepageSetting::getValue('site_logo');
    $footerSiteName = \App\Models\HomepageSetting::getValue('site_name', 'BlinkStudy');
    $footerLogoIcon = \App\Models\HomepageSetting::getValue('logo_icon', 'auto_stories');
@endphp
<footer class="relative z-10 border-t border-slate-200/80 bg-white py-14 px-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row gap-10 mb-10">
            <div class="md:w-2/5 shrink-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 mb-4">
                    @if($footerLogo)
                        <img src="{{ $footerLogo }}" alt="{{ $footerSiteName }}" class="h-8 w-auto object-contain">
                    @else
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand to-secondary flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-base">{{ $footerLogoIcon }}</span>
                        </div>
                    @endif
                    <span class="text-lg font-extrabold text-slate-900">{{ $footerSiteName }}</span>
                </a>
                <p class="text-sm text-slate-500 leading-relaxed max-w-sm">AI-powered study companion for Indian students. Scan doubts, chat with AI tutor, practice quizzes, and ace your exams.</p>
                <p class="text-sm text-slate-400 mt-3">
                    <a href="mailto:{{ config('services.support_email') }}" class="hover:text-brand transition-colors">{{ config('services.support_email') }}</a>
                </p>
            </div>

            <div class="flex-1 grid grid-cols-2 sm:grid-cols-3 gap-8">
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-3">Product</h4>
                    <ul class="space-y-2.5 text-sm text-slate-500">
                        <li><a href="{{ route('home') }}#features" class="hover:text-brand transition-colors">Features</a></li>
                        <li><a href="{{ route('plans') }}" class="hover:text-brand transition-colors">Pricing</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-brand transition-colors">Get Started</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-3">Company</h4>
                    <ul class="space-y-2.5 text-sm text-slate-500">
                        <li><a href="{{ route('support') }}" class="hover:text-brand transition-colors">Support</a></li>
                        <li><a href="{{ route('faq') }}" class="hover:text-brand transition-colors">FAQ</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-brand transition-colors">Privacy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-3">Legal</h4>
                    <ul class="space-y-2.5 text-sm text-slate-500">
                        @foreach(\App\Models\Page::where('is_active', true)->where('show_in_footer', true)->get() as $fp)
                        <li><a href="{{ route('page.show', $fp->slug) }}" class="hover:text-brand transition-colors">{{ $fp->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-slate-400 text-sm">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p class="text-slate-400 text-xs">Made with ❤️ in India for Indian students</p>
        </div>
    </div>
</footer>
