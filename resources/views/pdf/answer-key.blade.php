<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }} - Answer Key</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #10B981;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #10B981;
            font-size: 24pt;
        }
        .answer-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .answer-row {
            display: table-row;
        }
        .answer-cell {
            display: table-cell;
            padding: 10px;
            border: 1px solid #E5E7EB;
            width: 33.33%;
        }
        .answer-number {
            font-weight: bold;
            color: #4F46E5;
        }
        .answer-value {
            color: #10B981;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $subject }}</h1>
        <p>Answer Key</p>
        <p style="font-size: 10pt; color: #666;">Generated: {{ $generated_at }}</p>
    </div>

    <div class="answer-grid">
        @foreach($questions as $index => $question)
            @if($index % 3 === 0)
                <div class="answer-row">
            @endif

            <div class="answer-cell">
                <span class="answer-number">Q{{ $index + 1 }}:</span>
                <span class="answer-value">{{ $question['correct_answer'] ?? 'N/A' }}</span>
            </div>

            @if(($index + 1) % 3 === 0 || $index + 1 === count($questions))
                </div>
            @endif
        @endforeach
    </div>
</body>
</html>
