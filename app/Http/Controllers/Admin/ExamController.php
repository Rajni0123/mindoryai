<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::withCount('questions')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return view('admin.exams.index', compact('exams'));
    }

    public function create()
    {
        return view('admin.exams.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:engineering,medical,government,board,other',
            'level' => 'nullable|in:national,state,board',
            'subjects' => 'nullable|array',
            'subjects.*' => 'string|max:255',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            // Config fields
            'duration_minutes' => 'nullable|integer|min:1',
            'total_questions' => 'nullable|integer|min:1',
            'correct_marks' => 'nullable|numeric',
            'wrong_marks' => 'nullable|numeric',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;
        $validated['subjects'] = array_filter($validated['subjects'] ?? []);
        $validated['config'] = [
            'duration_minutes' => (int) ($request->duration_minutes ?? 60),
            'total_questions' => (int) ($request->total_questions ?? 50),
            'marking_scheme' => [
                'correct' => (float) ($request->correct_marks ?? 4),
                'wrong' => (float) ($request->wrong_marks ?? -1),
            ],
            'negative_marking' => ($request->wrong_marks ?? -1) < 0,
        ];

        Exam::create($validated);

        return redirect()->route('admin.exams.index')
            ->with('success', 'Exam created successfully!');
    }

    public function edit(Exam $exam)
    {
        return view('admin.exams.edit', compact('exam'));
    }

    public function update(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:engineering,medical,government,board,other',
            'level' => 'nullable|in:national,state,board',
            'subjects' => 'nullable|array',
            'subjects.*' => 'string|max:255',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'total_questions' => 'nullable|integer|min:1',
            'correct_marks' => 'nullable|numeric',
            'wrong_marks' => 'nullable|numeric',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;
        $validated['subjects'] = array_filter($validated['subjects'] ?? []);
        $validated['config'] = [
            'duration_minutes' => (int) ($request->duration_minutes ?? 60),
            'total_questions' => (int) ($request->total_questions ?? 50),
            'marking_scheme' => [
                'correct' => (float) ($request->correct_marks ?? 4),
                'wrong' => (float) ($request->wrong_marks ?? -1),
            ],
            'negative_marking' => ($request->wrong_marks ?? -1) < 0,
        ];

        $exam->update($validated);

        return redirect()->route('admin.exams.index')
            ->with('success', 'Exam updated successfully!');
    }

    public function destroy(Exam $exam)
    {
        if ($exam->questions()->count() > 0) {
            return redirect()->route('admin.exams.index')
                ->with('error', 'Cannot delete exam with existing questions. Delete questions first.');
        }

        $exam->delete();

        return redirect()->route('admin.exams.index')
            ->with('success', 'Exam deleted successfully!');
    }
}
