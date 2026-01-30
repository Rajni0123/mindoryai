@extends('layouts.app')

@section('title', $page->meta_title ? $page->meta_title . ' - ' . $settings['site_name'] : $page->title . ' - ' . $settings['site_name'])
@section('description', $page->meta_description ?: Str::limit(strip_tags($page->content), 160))

@if($page->meta_keywords)
@section('meta_keywords', $page->meta_keywords)
@endif

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">

    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-bold dark:text-white text-gray-900 mb-3">
            {{ $page->title }}
        </h1>
        <div class="w-20 h-1 bg-primary mx-auto mb-4"></div>
        @if($page->updated_at)
        <p class="dark:text-gray-500 text-gray-600 text-sm">Last updated: {{ $page->updated_at->format('F d, Y') }}</p>
        @endif
    </div>

    <!-- Content Card -->
    <div class="dark:bg-white/[0.02] bg-white rounded-xl shadow-sm border dark:border-white/[0.06] border-gray-200 p-6 md:p-10">

        <!-- Page Content -->
        <div class="prose prose-lg max-w-none dark:prose-invert">
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
                    $bodyContent = preg_replace('/(\d+\.(?:\d+)?)\s+([A-Z][A-Z &]+)/m', '</p><h3>$1 $2</h3><p>', $bodyContent);
                    // Convert dash/hyphen lists to <li>
                    $bodyContent = preg_replace('/[\r\n]\s*[-–]\s+(.+)/m', '</p><ul><li>$1</li></ul><p>', $bodyContent);
                    // Merge consecutive <ul> blocks
                    $bodyContent = str_replace('</ul><p></p><ul>', '', $bodyContent);
                    $bodyContent = str_replace('</ul><p>
</p><ul>', '', $bodyContent);
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

<style>
    .prose { color: #1f2937; line-height: 1.75; font-size: 0.95rem; }
    .dark .prose { color: #e5e7eb; }

    .prose h1, .prose h2, .prose h3 { color: #111827; font-weight: 700; }
    .dark .prose h1, .dark .prose h2, .dark .prose h3 { color: #f9fafb; }

    .prose h1 { font-size: 2em; margin-top: 0; margin-bottom: 0.75em; line-height: 1.2; font-weight: 800; }
    .prose h2 { font-size: 1.4em; margin-top: 2em; margin-bottom: 0.75em; line-height: 1.3; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.3em; }
    .dark .prose h2 { border-bottom-color: rgba(255,255,255,0.08); }
    .prose h3 { font-size: 1.15em; margin-top: 1.6em; margin-bottom: 0.5em; line-height: 1.5; }

    .prose p { margin-top: 1em; margin-bottom: 1em; color: #374151; }
    .dark .prose p { color: #d1d5db; }

    .prose ul { list-style-type: disc; margin-top: 0.75em; margin-bottom: 0.75em; padding-left: 1.625em; }
    .prose ol { list-style-type: decimal; margin-top: 0.75em; margin-bottom: 0.75em; padding-left: 1.625em; }
    .prose li { margin-top: 0.35em; margin-bottom: 0.35em; color: #374151; }
    .dark .prose li { color: #d1d5db; }

    .prose a { color: #0d9488; text-decoration: underline; font-weight: 500; }
    .prose a:hover { color: #0f766e; }

    .prose strong { color: #111827; font-weight: 600; }
    .dark .prose strong { color: #f3f4f6; }

    .prose em { font-style: italic; }

    .prose code { color: #111827; font-weight: 600; font-size: 0.875em; background-color: #f3f4f6; padding: 0.2em 0.4em; border-radius: 0.25rem; }
    .dark .prose code { color: #e5e7eb; background-color: rgba(255,255,255,0.05); }

    .prose blockquote { font-weight: 500; font-style: italic; color: #374151; border-left-width: 0.25rem; border-left-color: #0d9488; margin-top: 1.6em; margin-bottom: 1.6em; padding-left: 1em; }
    .dark .prose blockquote { color: #d1d5db; border-left-color: rgba(13,148,136,0.5); }

    .prose table { width: 100%; table-layout: auto; text-align: left; margin-top: 2em; margin-bottom: 2em; font-size: 0.875em; line-height: 1.7; }
    .prose thead { border-bottom: 2px solid #e5e7eb; }
    .dark .prose thead { border-bottom-color: rgba(255,255,255,0.1); }
    .prose th { font-weight: 600; vertical-align: bottom; padding: 0.5em; color: #111827; }
    .dark .prose th { color: #f3f4f6; }
    .prose tbody tr { border-bottom: 1px solid #f3f4f6; }
    .dark .prose tbody tr { border-bottom-color: rgba(255,255,255,0.06); }
    .prose td { vertical-align: top; padding: 0.5em; color: #374151; }
    .dark .prose td { color: #d1d5db; }
</style>
@endsection
