<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }} - Quiz</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #4F46E5;
        }

        .header h1 {
            font-size: 24pt;
            color: #4F46E5;
            margin-bottom: 8px;
        }

        .header .subtitle {
            font-size: 12pt;
            color: #666;
            margin-bottom: 5px;
        }

        .header .meta {
            font-size: 10pt;
            color: #888;
            margin-top: 10px;
        }

        .topics {
            background: #F3F4F6;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #4F46E5;
        }

        .topics strong {
            color: #4F46E5;
        }

        .question-block {
            margin-bottom: 25px;
            page-break-inside: avoid;
            padding: 15px;
            border: 1px solid #E5E7EB;
            border-radius: 5px;
            background: #FAFAFA;
        }

        .question-header {
            display: flex;
            margin-bottom: 10px;
        }

        .question-number {
            font-weight: bold;
            color: #4F46E5;
            margin-right: 8px;
            min-width: 35px;
        }

        .question-text {
            font-weight: 600;
            color: #1F2937;
            flex: 1;
        }

        .options {
            margin-left: 43px;
            margin-top: 10px;
        }

        .option {
            margin-bottom: 8px;
            padding: 8px;
            background: white;
            border-radius: 3px;
            border: 1px solid #E5E7EB;
        }

        .option-label {
            font-weight: 600;
            color: #4F46E5;
            margin-right: 8px;
        }

        .answer-section {
            margin-left: 43px;
            margin-top: 15px;
            padding: 10px;
            background: #ECFDF5;
            border-left: 3px solid #10B981;
            border-radius: 3px;
        }

        .answer-label {
            font-weight: 600;
            color: #10B981;
            margin-right: 5px;
        }

        .answer-value {
            color: #065F46;
            font-weight: 600;
        }

        .explanation {
            margin-left: 43px;
            margin-top: 10px;
            padding: 10px;
            background: #FEF3C7;
            border-left: 3px solid #F59E0B;
            border-radius: 3px;
            font-size: 10pt;
            color: #92400E;
        }

        .explanation-label {
            font-weight: 600;
            color: #F59E0B;
            margin-right: 5px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #E5E7EB;
            text-align: center;
            font-size: 9pt;
            color: #6B7280;
        }

        .page-break {
            page-break-after: always;
        }

        .true-false-answer {
            margin-left: 43px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $subject }}</h1>
        <div class="subtitle">Quiz - {{ $difficulty }} Level</div>
        <div class="subtitle">Total Questions: {{ $total_questions }}</div>
        <div class="meta">Generated: {{ $generated_at }}</div>
    </div>

    @if(!empty($topics))
    <div class="topics">
        <strong>Topics Covered:</strong> {{ implode(', ', $topics) }}
    </div>
    @endif

    @foreach($questions as $index => $question)
        <div class="question-block">
            <div class="question-header">
                <span class="question-number">Q{{ $index + 1 }}.</span>
                <span class="question-text">{{ $question['question'] }}</span>
            </div>

            @if($quiz_type === 'mcq' && isset($question['options']) && is_array($question['options']))
                <div class="options">
                    @foreach(['A', 'B', 'C', 'D'] as $optionKey)
                        @if(isset($question['options'][$optionKey]))
                            <div class="option">
                                <span class="option-label">{{ $optionKey }})</span>
                                <span>{{ $question['options'][$optionKey] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            @if($show_answers && isset($question['correct_answer']))
                <div class="answer-section">
                    <span class="answer-label">Correct Answer:</span>
                    <span class="answer-value">{{ $question['correct_answer'] }}</span>
                </div>
            @endif

            @if($show_explanations && isset($question['explanation']) && !empty($question['explanation']))
                <div class="explanation">
                    <span class="explanation-label">Explanation:</span>
                    <span>{{ $question['explanation'] }}</span>
                </div>
            @endif
        </div>

        @if(($index + 1) % 5 === 0 && $index + 1 < $total_questions)
            <div class="page-break"></div>
        @endif
    @endforeach

    <div class="footer">
        <p>Generated by Mindoory - AI-Powered Quiz Generator</p>
        <p>{{ $total_questions }} questions | {{ $difficulty }} difficulty</p>
    </div>
</body>
</html>
