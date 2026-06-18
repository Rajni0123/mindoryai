<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class QuizPdfGenerator
{
    /**
     * Generate a PDF document with quiz questions
     *
     * @param array $quizData Quiz data including questions, subject, topics
     * @param array $options PDF generation options
     * @return \Illuminate\Http\Response
     */
    public function generateQuizPdf(array $quizData, array $options = [])
    {
        $questions = $quizData['questions'] ?? [];
        $subject = $quizData['subject'] ?? 'Quiz';
        $topics = $quizData['topics'] ?? [];
        $quizType = $options['quiz_type'] ?? 'mcq';
        $difficulty = $options['difficulty'] ?? 2;
        $showAnswers = $options['show_answers'] ?? true;
        $showExplanations = $options['show_explanations'] ?? true;

        $difficultyText = match($difficulty) {
            1 => 'Very Easy',
            2 => 'Easy',
            3 => 'Medium',
            4 => 'Hard',
            5 => 'Very Challenging',
            default => 'Medium'
        };

        // Prepare data for the view
        $data = [
            'subject' => $subject,
            'topics' => $topics,
            'questions' => $questions,
            'quiz_type' => $quizType,
            'difficulty' => $difficultyText,
            'show_answers' => $showAnswers,
            'show_explanations' => $showExplanations,
            'generated_at' => now()->format('F j, Y \a\t g:i A'),
            'total_questions' => count($questions),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.quiz', $data);

        // Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');

        // Set options
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ]);

        return $pdf;
    }

    /**
     * Generate and download quiz PDF
     *
     * @param array $quizData Quiz data
     * @param array $options PDF options
     * @param string $filename Filename for download
     * @return \Illuminate\Http\Response
     */
    public function downloadQuizPdf(array $quizData, array $options = [], string $filename = null)
    {
        $pdf = $this->generateQuizPdf($quizData, $options);

        if (!$filename) {
            $subject = $quizData['subject'] ?? 'Quiz';
            $filename = str_replace(' ', '_', $subject) . '_' . now()->format('Y-m-d') . '.pdf';
        }

        return $pdf->download($filename);
    }

    /**
     * Generate and stream quiz PDF (for preview)
     *
     * @param array $quizData Quiz data
     * @param array $options PDF options
     * @return \Illuminate\Http\Response
     */
    public function streamQuizPdf(array $quizData, array $options = [])
    {
        $pdf = $this->generateQuizPdf($quizData, $options);
        return $pdf->stream();
    }

    /**
     * Generate answer key PDF
     *
     * @param array $quizData Quiz data
     * @param array $options PDF options
     * @return \Illuminate\Http\Response
     */
    public function generateAnswerKeyPdf(array $quizData, array $options = [])
    {
        $questions = $quizData['questions'] ?? [];
        $subject = $quizData['subject'] ?? 'Quiz';

        $data = [
            'subject' => $subject,
            'questions' => $questions,
            'generated_at' => now()->format('F j, Y \a\t g:i A'),
            'total_questions' => count($questions),
        ];

        $pdf = Pdf::loadView('pdf.answer-key', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}
