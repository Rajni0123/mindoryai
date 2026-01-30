<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the protected AI chat interface
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $settings = [
            'site_name' => \App\Models\Setting::get('site_name', 'Mindory'),
            'chat_sidebar_logo' => \App\Models\Setting::get('chat_sidebar_logo', ''),
            'chat_sidebar_logo_height' => \App\Models\Setting::get('chat_sidebar_logo_height', '32'),
            'chat_header_logo' => \App\Models\Setting::get('chat_header_logo', ''),
        ];

        // Get enabled providers from admin panel
        $enabledProviders = [];

        if (\App\Models\FrontendConfig::getValue('ai.openai_enabled', '0') === '1') {
            $enabledProviders[] = 'openai';
        }
        if (\App\Models\FrontendConfig::getValue('ai.anthropic_enabled', '0') === '1' ||
            \App\Models\FrontendConfig::getValue('ai.claude_enabled', '0') === '1') {
            $enabledProviders[] = 'anthropic';
        }
        if (\App\Models\FrontendConfig::getValue('ai.deepseek_enabled', '0') === '1') {
            $enabledProviders[] = 'deepseek';
        }
        if (\App\Models\FrontendConfig::getValue('ai.google_enabled', '0') === '1' ||
            \App\Models\FrontendConfig::getValue('ai.gemini_enabled', '0') === '1') {
            $enabledProviders[] = 'google';
        }
        if (\App\Models\FrontendConfig::getValue('ai.xai_enabled', '0') === '1' ||
            \App\Models\FrontendConfig::getValue('ai.grok_enabled', '0') === '1') {
            $enabledProviders[] = 'xai';
        }

        // Get ONLY the first active model from enabled providers (single model mode)
        $selectedAiModel = AiModel::where('is_active', true)
            ->whereIn('provider', $enabledProviders)
            ->orderBy('order')
            ->orderBy('name')
            ->first();

        // Note: Chat history is now loaded from database via AJAX (conversation system)
        // No need to pass chatHistory from session anymore

        return view('pages.chat', compact('settings', 'selectedAiModel'));
    }
}
