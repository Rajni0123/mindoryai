<?php

namespace App\Http\Controllers;

use App\Services\StudentDoubtSolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class DoubtSolverController extends Controller
{
    protected $doubtSolver;

    public function __construct(StudentDoubtSolverService $doubtSolver)
    {
        $this->doubtSolver = $doubtSolver;
    }

    /**
     * Solve text-based doubt
     */
    public function solveTextDoubt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|min:3',
            'subject' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $question = $request->input('question');
        $subject = $request->input('subject');

        // Get conversation history from session
        $conversationHistory = Session::get('doubt_conversation_history', []);

        // Solve the doubt
        $response = $this->doubtSolver->solveTextDoubt($question, $subject, $conversationHistory);

        // Update conversation history
        $conversationHistory[] = ['role' => 'user', 'content' => $question];
        $conversationHistory[] = ['role' => 'assistant', 'content' => $response];

        // Keep only last 8 messages (4 exchanges)
        if (count($conversationHistory) > 8) {
            $conversationHistory = array_slice($conversationHistory, -8);
        }

        Session::put('doubt_conversation_history', $conversationHistory);

        // Also set current subject in session
        if ($subject) {
            Session::put('current_subject', $subject);
        }

        return response()->json([
            'success' => true,
            'question' => $question,
            'subject' => $subject ?? Session::get('current_subject'),
            'response' => $response,
            'conversation_count' => count($conversationHistory) / 2
        ]);
    }

    /**
     * Solve image-based doubt
     */
    public function solveImageDoubt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:10240', // Max 10MB
            'question' => 'nullable|string',
            'subject' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $subject = $request->input('subject') ?? Session::get('current_subject');
        $question = $request->input('question');

        // Get the uploaded image
        $image = $request->file('image');
        $imagePath = $image->getRealPath();

        // Solve the image doubt
        $response = $this->doubtSolver->solveImageDoubt($imagePath, $question, $subject);

        return response()->json([
            'success' => true,
            'subject' => $subject,
            'additional_question' => $question,
            'response' => $response
        ]);
    }

    /**
     * Solve image doubt from base64
     */
    public function solveImageDoubtBase64(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image_base64' => 'required|string',
            'question' => 'nullable|string',
            'subject' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $subject = $request->input('subject') ?? Session::get('current_subject');
        $question = $request->input('question');
        $base64Image = $request->input('image_base64');

        // Remove data:image/...;base64, prefix if present
        $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);

        // Solve the image doubt
        $response = $this->doubtSolver->solveImageDoubt($base64Image, $question, $subject);

        return response()->json([
            'success' => true,
            'subject' => $subject,
            'additional_question' => $question,
            'response' => $response
        ]);
    }

    /**
     * Solve PDF doubt
     */
    public function solvePdfDoubt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pdf_text' => 'required|string|min:10',
            'question' => 'nullable|string',
            'subject' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $pdfText = $request->input('pdf_text');
        $question = $request->input('question');
        $subject = $request->input('subject') ?? Session::get('current_subject');

        // Solve the PDF doubt
        $response = $this->doubtSolver->solvePdfDoubt($pdfText, $question, $subject);

        return response()->json([
            'success' => true,
            'subject' => $subject,
            'question' => $question,
            'response' => $response
        ]);
    }

    /**
     * Set current subject
     */
    public function setSubject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $subject = $request->input('subject');
        Session::put('current_subject', $subject);

        return response()->json([
            'success' => true,
            'message' => "✓ Subject set to: {$subject}",
            'subject' => $subject
        ]);
    }

    /**
     * Clear conversation history
     */
    public function clearHistory(Request $request)
    {
        Session::forget('doubt_conversation_history');
        Session::forget('current_subject');

        return response()->json([
            'success' => true,
            'message' => '✓ Conversation history cleared'
        ]);
    }

    /**
     * Get conversation history
     */
    public function getHistory(Request $request)
    {
        $conversationHistory = Session::get('doubt_conversation_history', []);
        $currentSubject = Session::get('current_subject');

        return response()->json([
            'success' => true,
            'subject' => $currentSubject,
            'conversation_history' => $conversationHistory,
            'message_count' => count($conversationHistory)
        ]);
    }

    /**
     * Generate quiz for a topic or from uploaded image
     * Two modes:
     * 1. WITHOUT image: Uses AI knowledge about the topic
     * 2. WITH image: Generates questions ONLY from image content
     */
    public function generateQuiz(Request $request)
    {
        $hasImage = $request->hasFile('image');

        if ($hasImage) {
            // Image-based quiz - require image, topic is optional
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:10240', // 10MB
                'num_questions' => 'nullable|integer|min:1|max:20',
                'difficulty' => 'nullable|in:easy,medium,hard',
            ]);
        } else {
            // Topic-based quiz - require topic
            $validator = Validator::make($request->all(), [
                'topic' => 'required|string|min:2',
                'subject' => 'nullable|string|max:100',
                'num_questions' => 'nullable|integer|min:1|max:20',
                'difficulty' => 'nullable|in:easy,medium,hard',
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $numQuestions = $request->input('num_questions', 5);
        $difficulty = $request->input('difficulty', 'medium');

        if ($hasImage) {
            // Image-based quiz generation
            $image = $request->file('image');
            $quiz = $this->doubtSolver->generateQuizFromImage($image, $numQuestions, $difficulty);

            return response()->json([
                'success' => true,
                'mode' => 'image',
                'num_questions' => $numQuestions,
                'difficulty' => $difficulty,
                'quiz' => $quiz
            ]);
        } else {
            // Topic-based quiz generation
            $topic = $request->input('topic');
            $subject = $request->input('subject') ?? Session::get('current_subject');
            $quiz = $this->doubtSolver->generateQuiz($topic, $subject, $numQuestions, $difficulty);

            return response()->json([
                'success' => true,
                'mode' => 'topic',
                'topic' => $topic,
                'subject' => $subject,
                'num_questions' => $numQuestions,
                'difficulty' => $difficulty,
                'quiz' => $quiz
            ]);
        }
    }

    /**
     * Generate practice problems
     */
    public function generatePracticeProblems(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string|min:2',
            'subject' => 'nullable|string|max:100',
            'num_problems' => 'nullable|integer|min:1|max:10',
            'difficulty' => 'nullable|in:easy,medium,hard',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $topic = $request->input('topic');
        $subject = $request->input('subject') ?? Session::get('current_subject');
        $numProblems = $request->input('num_problems', 5);
        $difficulty = $request->input('difficulty', 'medium');

        $problems = $this->doubtSolver->generatePracticeProblems($topic, $subject, $numProblems, $difficulty);

        return response()->json([
            'success' => true,
            'topic' => $topic,
            'subject' => $subject,
            'num_problems' => $numProblems,
            'difficulty' => $difficulty,
            'problems' => $problems
        ]);
    }

    /**
     * Explain a concept
     */
    public function explainConcept(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'concept' => 'required|string|min:2',
            'subject' => 'nullable|string|max:100',
            'level' => 'nullable|in:beginner,intermediate,advanced',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $concept = $request->input('concept');
        $subject = $request->input('subject') ?? Session::get('current_subject');
        $level = $request->input('level', 'intermediate');

        $explanation = $this->doubtSolver->explainConcept($concept, $subject, $level);

        return response()->json([
            'success' => true,
            'concept' => $concept,
            'subject' => $subject,
            'level' => $level,
            'explanation' => $explanation
        ]);
    }

    /**
     * Get hint for a problem
     */
    public function getHint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'problem' => 'required|string|min:5',
            'subject' => 'nullable|string|max:100',
            'step' => 'nullable|integer|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $problem = $request->input('problem');
        $subject = $request->input('subject') ?? Session::get('current_subject');
        $step = $request->input('step', 0);

        $hint = $this->doubtSolver->getHint($problem, $subject, $step);

        return response()->json([
            'success' => true,
            'problem' => $problem,
            'subject' => $subject,
            'step' => $step,
            'hint' => $hint
        ]);
    }

    /**
     * Summarize a topic
     */
    public function summarizeTopic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string|min:2',
            'subject' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $topic = $request->input('topic');
        $subject = $request->input('subject') ?? Session::get('current_subject');

        $summary = $this->doubtSolver->summarizeTopic($topic, $subject);

        return response()->json([
            'success' => true,
            'topic' => $topic,
            'subject' => $subject,
            'summary' => $summary
        ]);
    }
}
