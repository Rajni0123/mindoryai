<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Http\Request;

class ExamQuestionController extends Controller
{
    public function index(Request $request, Exam $exam)
    {
        $query = $exam->questions();

        if ($request->filled('subject')) {
            $query->where('subject', $request->subject);
        }
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $questions = $query->orderBy('year', 'desc')
            ->orderBy('subject')
            ->paginate(30);

        $subjects = $exam->getAvailableSubjects();
        $years = $exam->getAvailableYears();

        return view('admin.exam-questions.index', compact('exam', 'questions', 'subjects', 'years'));
    }

    public function create(Exam $exam)
    {
        return view('admin.exam-questions.create', compact('exam'));
    }

    public function store(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'topic' => 'nullable|string|max:255',
            'subtopic' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1990|max:2030',
            'type' => 'required|in:mcq,numerical,assertion_reason,true_false',
            'question_text' => 'required|string',
            'options' => 'nullable|array',
            'options.*.label' => 'required_with:options|string',
            'options.*.text' => 'required_with:options|string',
            'correct_answer' => 'required|string|max:255',
            'explanation' => 'nullable|string',
            'solution_steps' => 'nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'language' => 'nullable|string|max:50',
            'tags' => 'nullable|string', // Comma-separated
            'is_active' => 'nullable|boolean',
        ]);

        $validated['exam_id'] = $exam->id;
        $validated['is_active'] = $request->has('is_active');
        $validated['language'] = $validated['language'] ?? 'english';

        // Parse tags from comma-separated string
        if (!empty($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        } else {
            $validated['tags'] = [];
        }

        ExamQuestion::create($validated);

        return redirect()->route('admin.exam-questions.index', $exam)
            ->with('success', 'Question added successfully!');
    }

    public function edit(ExamQuestion $question)
    {
        $exam = $question->exam;
        return view('admin.exam-questions.edit', compact('question', 'exam'));
    }

    public function update(Request $request, ExamQuestion $question)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'topic' => 'nullable|string|max:255',
            'subtopic' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1990|max:2030',
            'type' => 'required|in:mcq,numerical,assertion_reason,true_false',
            'question_text' => 'required|string',
            'options' => 'nullable|array',
            'options.*.label' => 'required_with:options|string',
            'options.*.text' => 'required_with:options|string',
            'correct_answer' => 'required|string|max:255',
            'explanation' => 'nullable|string',
            'solution_steps' => 'nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'language' => 'nullable|string|max:50',
            'tags' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        if (!empty($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        } else {
            $validated['tags'] = [];
        }

        $question->update($validated);

        return redirect()->route('admin.exam-questions.index', $question->exam)
            ->with('success', 'Question updated successfully!');
    }

    public function destroy(ExamQuestion $question)
    {
        $exam = $question->exam;
        $question->delete();

        return redirect()->route('admin.exam-questions.index', $exam)
            ->with('success', 'Question deleted successfully!');
    }

    public function import(Request $request, Exam $exam)
    {
        $request->validate([
            'file' => 'required|file|mimes:json,csv|max:10240',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        $imported = 0;
        $errors = [];

        if ($extension === 'json') {
            $data = json_decode(file_get_contents($file->getRealPath()), true);

            if (!is_array($data)) {
                return back()->with('error', 'Invalid JSON format.');
            }

            foreach ($data as $index => $item) {
                try {
                    ExamQuestion::create([
                        'exam_id' => $exam->id,
                        'subject' => $item['subject'] ?? 'General',
                        'topic' => $item['topic'] ?? null,
                        'subtopic' => $item['subtopic'] ?? null,
                        'year' => $item['year'] ?? null,
                        'type' => $item['type'] ?? 'mcq',
                        'question_text' => $item['question_text'] ?? $item['question'] ?? '',
                        'options' => $item['options'] ?? null,
                        'correct_answer' => $item['correct_answer'] ?? '',
                        'explanation' => $item['explanation'] ?? null,
                        'solution_steps' => $item['solution_steps'] ?? null,
                        'difficulty' => $item['difficulty'] ?? 'medium',
                        'language' => $item['language'] ?? 'english',
                        'tags' => $item['tags'] ?? [],
                        'is_active' => true,
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$index}: {$e->getMessage()}";
                }
            }
        }

        $message = "Successfully imported {$imported} questions.";
        if (!empty($errors)) {
            $message .= ' Errors: ' . count($errors);
        }

        return redirect()->route('admin.exam-questions.index', $exam)
            ->with($errors ? 'warning' : 'success', $message);
    }
}
