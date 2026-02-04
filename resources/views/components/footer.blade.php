{{-- Shared Footer — used by all pages --}}
@php
    $footerLogo = \App\Models\HomepageSetting::getValue('site_logo');
    $footerSiteName = \App\Models\HomepageSetting::getValue('site_name', 'BlinkStudy');
    $footerLogoIcon = \App\Models\HomepageSetting::getValue('logo_icon', 'school');
@endphp
<footer class="relative z-10 border-t dark:border-white/5 border-gray-200 dark:bg-[#05080a] bg-white py-12 px-6">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row gap-8 mb-8">
            {{-- Brand --}}
            <div class="md:w-1/4 shrink-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2 mb-3 group">
                    @if($footerLogo)
                        <img src="{{ $footerLogo }}" alt="{{ $footerSiteName }}" class="h-8 w-auto object-contain">
                    @else
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg dark:bg-[#1a1f2e] bg-primary/10 border dark:border-white/10 border-primary/10">
                            <span class="material-symbols-outlined text-primary text-lg">{{ $footerLogoIcon }}</span>
                        </div>
                    @endif
                    <span class="text-lg font-bold dark:text-white text-slate-800 font-display group-hover:text-primary transition-colors">{{ $footerSiteName }}</span>
                </a>
                <p class="text-xs dark:text-gray-500 text-gray-400 leading-relaxed">AI-powered learning companion for students. Solve doubts, generate quizzes, and learn smarter.</p>
                <p class="text-xs dark:text-gray-600 text-gray-400 mt-2">
                    <a href="mailto:{{ config('services.support_email') }}" class="hover:text-primary transition-colors">{{ config('services.support_email') }}</a>
                </p>
            </div>

            {{-- Link columns --}}
            <div class="flex-1 grid grid-cols-2 sm:grid-cols-3 gap-8">
                <div>
                    <h4 class="font-semibold dark:text-white text-slate-700 text-sm mb-3">Product</h4>
                    <ul class="space-y-2 text-xs dark:text-gray-500 text-gray-400">
                        <li><a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a></li>
                        <li><a href="{{ route('plans') }}" class="hover:text-primary transition-colors">Pricing</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-primary transition-colors">Get Started</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold dark:text-white text-slate-700 text-sm mb-3">Company</h4>
                    <ul class="space-y-2 text-xs dark:text-gray-500 text-gray-400">
                        <li><a href="{{ route('support') }}" class="hover:text-primary transition-colors">Support</a></li>
                        <li><a href="{{ route('faq') }}" class="hover:text-primary transition-colors">FAQ</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-primary transition-colors">Privacy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold dark:text-white text-slate-700 text-sm mb-3">Legal</h4>
                    <ul class="space-y-2 text-xs dark:text-gray-500 text-gray-400">
                        @foreach(\App\Models\Page::where('is_active', true)->where('show_in_footer', true)->get() as $fp)
                        <li><a href="{{ route('page.show', $fp->slug) }}" class="hover:text-primary transition-colors">{{ $fp->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="border-t dark:border-white/5 border-gray-200 pt-6 text-center flex flex-col items-center gap-2">
            <p class="dark:text-gray-600 text-gray-400 text-xs">&copy; {{ date('Y') }} {{ config('app.name') }} | Operated by Rajnish Kumar</p>
        </div>
    </div>
</footer>
