@extends('layouts.app')

@section('title', 'Policies - ' . config('app.name'))
@section('description', 'View all our policies including Privacy Policy, Terms of Service, Refund Policy, and more')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-12">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold dark:text-white text-gray-900 mb-4">
            Legal & Policies
        </h1>
        <p class="text-lg dark:text-gray-400 text-gray-600 max-w-2xl mx-auto">
            Access all our legal documents and policies in one place.
        </p>
    </div>

    <!-- Policies Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($policies as $policy)
        @php
            $iconConfig = [
                'privacy_policy' => ['shield', 'blue'],
                'terms_of_service' => ['gavel', 'purple'],
                'refund_policy' => ['assignment_return', 'green'],
                'cancellation_policy' => ['event_busy', 'red'],
                'cookie_policy' => ['storage', 'amber'],
                'about' => ['business', 'indigo'],
                'support' => ['contact_support', 'yellow'],
            ];
            $cfg = $iconConfig[$policy->key] ?? ['article', 'gray'];
            $icon = $cfg[0];
            $color = $cfg[1];
        @endphp
        <a href="{{ route('policy.show', $policy->key) }}"
           class="dark:bg-white/[0.02] bg-white rounded-xl shadow-sm dark:shadow-none border dark:border-white/[0.06] border-gray-200 p-6 hover:shadow-md dark:hover:border-white/10 hover:border-primary/30 transition-all group">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="w-12 h-12 dark:bg-{{ $color }}-500/10 bg-{{ $color }}-100 rounded-lg flex items-center justify-center mb-3 dark:group-hover:bg-{{ $color }}-500/20 group-hover:bg-{{ $color }}-200 transition-colors">
                        <span class="material-icons-outlined dark:text-{{ $color }}-400 text-{{ $color }}-600">{{ $icon }}</span>
                    </div>

                    <h3 class="text-lg font-semibold dark:text-white text-gray-900 group-hover:text-primary transition-colors">
                        {{ $policy->title }}
                    </h3>
                </div>
                <span class="material-icons-outlined dark:text-gray-600 text-gray-400 group-hover:text-primary transition-colors">
                    arrow_forward
                </span>
            </div>

            <p class="text-sm dark:text-gray-400 text-gray-600 line-clamp-3">
                {{ Str::limit(strip_tags($policy->content['sections'][0]['content'] ?? ''), 150) }}
            </p>

            <div class="mt-4 flex items-center justify-between">
                <span class="text-xs dark:text-gray-500 text-gray-500">
                    {{ count($policy->content['sections'] ?? []) }} sections
                </span>
                <span class="text-xs font-medium text-primary group-hover:text-primary/80">
                    Read more →
                </span>
            </div>
        </a>
        @empty
        <div class="col-span-full text-center py-12">
            <span class="material-icons-outlined dark:text-gray-600 text-gray-400 text-5xl">folder_off</span>
            <p class="dark:text-gray-500 text-gray-500 mt-4">No policies available at the moment.</p>
        </div>
        @endforelse
    </div>

</div>
@endsection
