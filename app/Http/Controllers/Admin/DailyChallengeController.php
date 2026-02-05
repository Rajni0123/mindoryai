<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyChallenge;
use App\Models\DynamicAppConfig;
use Illuminate\Http\Request;

class DailyChallengeController extends Controller
{
    public function index()
    {
        $challenges = DailyChallenge::orderBy('challenge_date', 'desc')->paginate(20);

        $settings = [
            'enabled' => DynamicAppConfig::getValue('features.daily_challenge_enabled', true),
            'questions_count' => DynamicAppConfig::getValue('daily_challenge.questions_count', 5),
            'time_limit' => DynamicAppConfig::getValue('daily_challenge.time_limit_seconds', 300),
            'reward_credits' => DynamicAppConfig::getValue('daily_challenge.reward_credits', 2),
            'streak_bonus' => DynamicAppConfig::getValue('daily_challenge.streak_bonus_credits', 5),
            'difficulty' => DynamicAppConfig::getValue('daily_challenge.difficulty', 'mixed'),
            'subjects' => DynamicAppConfig::getValue('daily_challenge.subjects', ['Mathematics', 'Science', 'English', 'Social Studies', 'General Knowledge']),
        ];

        $todayChallenge = DailyChallenge::getToday();

        return view('admin.daily-challenges.index', compact('challenges', 'settings', 'todayChallenge'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'questions_count' => 'required|integer|min:3|max:20',
            'time_limit' => 'required|integer|min:60|max:1800',
            'reward_credits' => 'required|integer|min:0|max:50',
            'streak_bonus' => 'required|integer|min:0|max:100',
            'difficulty' => 'required|in:easy,medium,hard,mixed',
            'subjects' => 'required|string',
        ]);

        try {
            DynamicAppConfig::setValue('features.daily_challenge_enabled', $request->boolean('enabled'), 'boolean');
            DynamicAppConfig::setValue('daily_challenge.questions_count', (int) $request->questions_count, 'integer');
            DynamicAppConfig::setValue('daily_challenge.time_limit_seconds', (int) $request->time_limit, 'integer');
            DynamicAppConfig::setValue('daily_challenge.reward_credits', (int) $request->reward_credits, 'integer');
            DynamicAppConfig::setValue('daily_challenge.streak_bonus_credits', (int) $request->streak_bonus, 'integer');
            DynamicAppConfig::setValue('daily_challenge.difficulty', $request->difficulty);

            $subjects = array_map('trim', explode(',', $request->subjects));
            DynamicAppConfig::setValue('daily_challenge.subjects', $subjects, 'json');

            return redirect()->back()->with('success', 'Daily challenge settings updated!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $subjects = DynamicAppConfig::getValue('daily_challenge.subjects', ['Mathematics', 'Science', 'English', 'Social Studies', 'General Knowledge']);
        return view('admin.daily-challenges.create', compact('subjects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'challenge_date' => 'required|date|unique:daily_challenges,challenge_date',
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:100',
            'difficulty' => 'required|in:easy,medium,hard,mixed',
            'time_limit_seconds' => 'required|integer|min:60|max:1800',
            'reward_credits' => 'required|integer|min:0|max:50',
            'questions' => 'required|array|min:3',
            'questions.*.question' => 'required|string',
            'questions.*.options' => 'required|array|min:2|max:4',
            'questions.*.correct_answer' => 'required|integer|min:0|max:3',
            'questions.*.explanation' => 'nullable|string',
        ]);

        try {
            DailyChallenge::create([
                'challenge_date' => $request->challenge_date,
                'title' => $request->title,
                'subject' => $request->subject,
                'difficulty' => $request->difficulty,
                'time_limit_seconds' => $request->time_limit_seconds,
                'reward_credits' => $request->reward_credits,
                'questions' => $request->questions,
                'is_active' => true,
            ]);

            return redirect()->route('admin.daily-challenges.index')->with('success', 'Daily challenge created!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create challenge: ' . $e->getMessage());
        }
    }

    public function edit(DailyChallenge $dailyChallenge)
    {
        $subjects = DynamicAppConfig::getValue('daily_challenge.subjects', ['Mathematics', 'Science', 'English', 'Social Studies', 'General Knowledge']);
        return view('admin.daily-challenges.edit', compact('dailyChallenge', 'subjects'));
    }

    public function update(Request $request, DailyChallenge $dailyChallenge)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:100',
            'difficulty' => 'required|in:easy,medium,hard,mixed',
            'time_limit_seconds' => 'required|integer|min:60|max:1800',
            'reward_credits' => 'required|integer|min:0|max:50',
            'questions' => 'required|array|min:3',
            'questions.*.question' => 'required|string',
            'questions.*.options' => 'required|array|min:2|max:4',
            'questions.*.correct_answer' => 'required|integer|min:0|max:3',
            'questions.*.explanation' => 'nullable|string',
        ]);

        try {
            $dailyChallenge->update([
                'title' => $request->title,
                'subject' => $request->subject,
                'difficulty' => $request->difficulty,
                'time_limit_seconds' => $request->time_limit_seconds,
                'reward_credits' => $request->reward_credits,
                'questions' => $request->questions,
            ]);

            return redirect()->route('admin.daily-challenges.index')->with('success', 'Challenge updated!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update challenge: ' . $e->getMessage());
        }
    }

    public function destroy(DailyChallenge $dailyChallenge)
    {
        $dailyChallenge->delete();
        return redirect()->route('admin.daily-challenges.index')->with('success', 'Challenge deleted!');
    }

    public function toggleActive(DailyChallenge $dailyChallenge)
    {
        $dailyChallenge->update(['is_active' => !$dailyChallenge->is_active]);
        return redirect()->back()->with('success', 'Challenge status updated!');
    }
}
