@extends('layouts.app')

@section('title', $policy->title . ' - ' . config('app.name'))
@section('description', Str::limit(strip_tags($policy->content['sections'][0]['content'] ?? ''), 160))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="{{ route('home') }}" class="dark:text-gray-500 text-gray-500 hover:text-primary transition-colors">Home</a>
            </li>
            <li><span class="dark:text-gray-600 text-gray-400">/</span></li>
            <li>
                <a href="{{ route('policy.index') }}" class="dark:text-gray-500 text-gray-500 hover:text-primary transition-colors">Policies</a>
            </li>
            <li><span class="dark:text-gray-600 text-gray-400">/</span></li>
            <li>
                <span class="dark:text-white text-gray-900 font-medium">{{ $policy->title }}</span>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="text-center mb-12">
        @php
        $icons = [
            'privacy_policy' => ['shield', 'primary'],
            'terms_of_service' => ['gavel', 'primary'],
            'refund_policy' => ['assignment_return', 'success'],
            'cancellation_policy' => ['event_busy', 'error'],
            'cookie_policy' => ['storage', 'secondary'],
            'about' => ['business', 'info'],
            'support' => ['contact_support', 'secondary'],
        ];
        $icon = $icons[$policy->key] ?? ['article', 'primary'];
        @endphp
        <div class="w-14 h-14 dark:bg-{{ $icon[1] }}/10 bg-{{ $icon[1] }}/5 rounded-2xl flex items-center justify-center mx-auto mb-4 border dark:border-{{ $icon[1] }}/20 border-{{ $icon[1] }}/10">
            <span class="material-icons-outlined text-{{ $icon[1] }} text-2xl">{{ $icon[0] }}</span>
        </div>

        <h1 class="text-3xl md:text-4xl font-bold dark:text-white text-gray-900 mb-3">
            {{ $policy->title }}
        </h1>
        <div class="w-20 h-1 bg-primary mx-auto mb-4"></div>
        <p class="dark:text-gray-500 text-gray-600 text-sm">Last updated: {{ date('F d, Y') }}</p>
    </div>

    <!-- Content -->
    <div class="dark:bg-white/[0.02] bg-white rounded-2xl shadow-sm border dark:border-white/[0.06] border-gray-200 p-6 md:p-10">
        <div class="space-y-8" id="policy-content">
            @foreach($policy->content['sections'] ?? [] as $index => $section)
            <section class="scroll-mt-32 policy-section" id="section-{{ $index + 1 }}" data-section="{{ $index + 1 }}">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="w-2 h-2 bg-primary rounded-full mt-2.5 flex-shrink-0"></div>
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold dark:text-white text-gray-900 mb-2">
                            {{ $section['title'] }}
                        </h2>
                        <div class="dark:text-gray-400 text-gray-600 text-sm leading-relaxed">
                            @if(str_contains($section['content'], '<') && str_contains($section['content'], '>'))
                                {!! $section['content'] !!}
                            @else
                                {!! nl2br(e($section['content'])) !!}
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            @if(!$loop->last)
            <hr class="dark:border-white/[0.06] border-gray-200">
            @endif
            @endforeach
        </div>
    </div>

</div>

@endsection
