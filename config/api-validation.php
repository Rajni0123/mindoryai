<?php

return [
    'limits' => [
        'name_min' => 2,
        'name_max' => 50,
        'title_max' => 255,
        'chat_title_max' => 255,
        'topic_max' => 200,
        'subject_max' => 100,
        'search_max' => 200,
        'slug_max' => 100,
        'content_max' => 50000,
        'otp_length' => 4,
        'pagination_max' => 100,
        'stats_days_max' => 365,
        'image_max_kb' => 10240,
        'audio_max_kb' => 25600,
        'icon_max_kb' => 1024,
        'quiz_image_max_kb' => 51200,
    ],

    'features' => [
        'chat',
        'video_quiz',
        'topic_quiz',
        'exam_prep',
        'scan_solve',
        'pdf_upload',
        'whiteboard_video',
        'study_battle',
    ],

    'student_classes' => ['6', '7', '8', '9', '10', '11', '12'],

    'languages' => ['english', 'hindi', 'hinglish'],

    'platforms' => ['android', 'ios'],

    'sort_options' => ['latest', 'popular', 'votes', 'recent'],

    'exam_categories' => ['school', 'competitive', 'board', 'jee', 'neet', 'cbse'],

    'difficulties' => ['easy', 'medium', 'hard', 'mixed'],

    'quiz_periods' => ['week', 'month', 'all'],

    'allowed_image_mimes' => ['jpeg', 'jpg', 'png', 'gif', 'webp'],

    'allowed_image_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ],

    'allowed_audio_mimes' => ['mp3', 'wav', 'm4a', 'ogg', 'webm', 'mp4'],

    'allowed_document_mimes' => ['pdf'],
];
