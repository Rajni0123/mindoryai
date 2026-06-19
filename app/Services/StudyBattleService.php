<?php

namespace App\Services;

use App\Models\StudyBattleRoom;
use App\Models\StudyBattleParticipant;
use App\Models\StudyBattleAnswer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudyBattleService
{
    protected StudentDoubtSolverService $quizService;

    public function __construct(StudentDoubtSolverService $quizService)
    {
        $this->quizService = $quizService;
    }

    /**
     * Generate unique 6-character room code
     * Format: BL + 4 alphanumeric (e.g., "BL7K9X")
     */
    public function generateRoomCode(): string
    {
        $alphanumeric = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // Exclude 0, 1, I, L, O

        do {
            $code = 'BL';
            for ($i = 0; $i < 4; $i++) {
                $code .= $alphanumeric[random_int(0, strlen($alphanumeric) - 1)];
            }
        } while (StudyBattleRoom::where('room_code', $code)
            ->whereNotIn('status', ['completed', 'cancelled', 'abandoned'])
            ->exists());

        return $code;
    }

    /**
     * Create a new battle room
     */
    public function createRoom(User $user, array $data): StudyBattleRoom
    {
        // Check if user is already in an active room
        $existingParticipation = StudyBattleParticipant::whereHas('room', function ($q) {
                $q->whereIn('status', ['waiting', 'starting', 'in_progress']);
            })
            ->where('user_id', $user->id)
            ->whereIn('status', ['joined', 'ready', 'playing'])
            ->first();

        if ($existingParticipation) {
            throw new \Exception('You are already in an active battle room: ' . $existingParticipation->room->room_code);
        }

        // Get max questions limit from user's plan
        $maxQuestions = $this->getMaxQuestionsForUser($user);
        $requestedQuestions = $data['question_count'] ?? 10;
        $questionCount = $maxQuestions > 0 ? min($requestedQuestions, $maxQuestions) : $requestedQuestions;

        $room = StudyBattleRoom::create([
            'room_code' => $this->generateRoomCode(),
            'host_user_id' => $user->id,
            'title' => 'Battle: ' . ($data['topic'] ?? 'Quick Quiz'),
            'topic' => $data['topic'] ?? null,
            'subject' => $data['subject'] ?? null,
            'exam_type' => $data['exam_type'] ?? null,
            'difficulty' => $data['difficulty'] ?? 'medium',
            'question_count' => $questionCount,
            'time_per_question' => $data['time_per_question'] ?? 15,
            'max_players' => $data['max_players'] ?? 4,
            'min_players' => 2,
            'status' => 'waiting',
            'expires_at' => now()->addMinutes(15),
        ]);

        // Add host as first participant
        StudyBattleParticipant::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'is_host' => true,
            'status' => 'joined',
            'joined_at' => now(),
            'last_poll_at' => now(),
        ]);

        Log::info('Study Battle room created', [
            'room_id' => $room->id,
            'room_code' => $room->room_code,
            'host_id' => $user->id,
            'topic' => $room->topic,
        ]);

        return $room;
    }

    /**
     * Join a room by code
     */
    public function joinRoom(User $user, string $code): array
    {
        $code = strtoupper(trim($code));

        $room = StudyBattleRoom::where('room_code', $code)
            ->where('status', 'waiting')
            ->first();

        if (!$room) {
            return ['success' => false, 'message' => 'Room not found or battle already started.'];
        }

        if ($room->isFull()) {
            return ['success' => false, 'message' => 'Room is full.'];
        }

        // Check if already in this room
        $existing = StudyBattleParticipant::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            if (in_array($existing->status, ['disconnected', 'left'])) {
                // Rejoin
                $existing->update([
                    'status' => 'joined',
                    'last_poll_at' => now(),
                ]);
                return ['success' => true, 'room' => $room->fresh(), 'message' => 'Rejoined the battle room.'];
            }
            return ['success' => true, 'room' => $room, 'message' => 'Already in this room.'];
        }

        // Check if user is in another active room
        $otherRoom = StudyBattleParticipant::whereHas('room', function ($q) {
                $q->whereIn('status', ['waiting', 'starting', 'in_progress']);
            })
            ->where('user_id', $user->id)
            ->whereIn('status', ['joined', 'ready', 'playing'])
            ->first();

        if ($otherRoom) {
            return ['success' => false, 'message' => 'You are already in another battle room.'];
        }

        // Join room
        StudyBattleParticipant::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'is_host' => false,
            'status' => 'joined',
            'joined_at' => now(),
            'last_poll_at' => now(),
        ]);

        // Touch room to notify pollers
        $room->touch();

        Log::info('User joined Study Battle room', [
            'room_id' => $room->id,
            'user_id' => $user->id,
        ]);

        return ['success' => true, 'room' => $room->fresh(), 'message' => 'Joined the battle room.'];
    }

    /**
     * Leave a room
     */
    public function leaveRoom(User $user): array
    {
        $participant = StudyBattleParticipant::with('room')
            ->where('user_id', $user->id)
            ->whereHas('room', function ($q) {
                $q->whereIn('status', ['waiting', 'starting', 'in_progress']);
            })
            ->whereIn('status', ['joined', 'ready', 'playing'])
            ->first();

        if (!$participant) {
            return ['success' => false, 'message' => 'You are not in any active room.'];
        }

        $room = $participant->room;
        $wasHost = $participant->is_host;

        $participant->update(['status' => 'left']);

        // If host left during waiting, transfer host or cancel
        if ($wasHost && $room->status === 'waiting') {
            $nextHost = $room->participants()
                ->where('status', 'joined')
                ->where('user_id', '!=', $user->id)
                ->first();

            if ($nextHost) {
                $nextHost->update(['is_host' => true]);
                $room->update(['host_user_id' => $nextHost->user_id]);
            } else {
                $room->update(['status' => 'cancelled']);
            }
        }

        // During battle, check if only 1 player left
        if ($room->status === 'in_progress') {
            $activePlayers = $room->participants()->playing()->count();
            if ($activePlayers <= 1) {
                $this->endBattle($room);
            }
        }

        $room->touch();

        return ['success' => true, 'message' => 'Left the room.'];
    }

    /**
     * Start the battle (host only)
     */
    public function startBattle(User $user): array
    {
        $participant = StudyBattleParticipant::with('room')
            ->where('user_id', $user->id)
            ->where('is_host', true)
            ->whereHas('room', function ($q) {
                $q->where('status', 'waiting');
            })
            ->first();

        if (!$participant) {
            return ['success' => false, 'message' => 'You are not a host of any waiting room.'];
        }

        $room = $participant->room;

        if (!$room->canStart()) {
            $activeCount = $room->active_participants_count;
            return [
                'success' => false,
                'message' => "Need at least {$room->min_players} players to start. Currently: {$activeCount}",
            ];
        }

        // Generate questions using AI
        try {
            $topicContext = $room->topic ?? 'General Knowledge';
            if ($room->exam_type) {
                $topicContext = "[{$room->exam_type}] {$topicContext}";
            }

            $quizResponse = $this->quizService->generateQuiz(
                $topicContext,
                $room->subject,
                $room->question_count,
                $room->difficulty === 'mixed' ? 'medium' : $room->difficulty
            );

            $questionsData = $this->parseQuizResponse($quizResponse);

            if (empty($questionsData['questions'])) {
                return ['success' => false, 'message' => 'Failed to generate questions. Please try again.'];
            }

            // Ensure we have enough questions
            if (count($questionsData['questions']) < $room->question_count) {
                Log::warning('Generated fewer questions than requested', [
                    'requested' => $room->question_count,
                    'generated' => count($questionsData['questions']),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to generate battle questions', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Failed to generate questions. Please try again.'];
        }

        // Start countdown (3 seconds)
        $room->update([
            'status' => 'starting',
            'questions_data' => $questionsData,
            'battle_started_at' => now()->addSeconds(3),
        ]);

        // Update all participants to playing
        $room->participants()
            ->whereIn('status', ['joined', 'ready'])
            ->update(['status' => 'playing']);

        Log::info('Study Battle started', [
            'room_id' => $room->id,
            'questions_count' => count($questionsData['questions']),
        ]);

        return [
            'success' => true,
            'message' => 'Battle starting in 3 seconds!',
            'countdown' => 3,
        ];
    }

    /**
     * Poll for current room state
     */
    public function poll(User $user, int $roomId): array
    {
        $participant = StudyBattleParticipant::with('room')
            ->where('room_id', $roomId)
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            return ['success' => false, 'message' => 'You are not in this room.'];
        }

        $room = $participant->room;

        // Update last poll time
        $participant->update(['last_poll_at' => now()]);

        // Handle state transitions
        if ($room->status === 'starting' && now()->gte($room->battle_started_at)) {
            $room->update([
                'status' => 'in_progress',
                'current_question_index' => 0,
                'question_started_at' => now(),
            ]);
            $room = $room->fresh();
        }

        // Check for question timeout during in_progress
        if ($room->status === 'in_progress' && $room->question_started_at) {
            $elapsedMs = now()->diffInMilliseconds($room->question_started_at);
            $timeoutMs = $room->time_per_question * 1000;

            if ($elapsedMs >= $timeoutMs) {
                $this->advanceToNextQuestion($room);
                $room = $room->fresh();
            }
        }

        return $this->buildPollResponse($room, $participant->fresh(), $user);
    }

    /**
     * Submit an answer
     */
    public function submitAnswer(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $participant = StudyBattleParticipant::with('room')
                ->where('room_id', $data['room_id'])
                ->where('user_id', $user->id)
                ->where('status', 'playing')
                ->lockForUpdate()
                ->first();

            if (!$participant) {
                return ['success' => false, 'message' => 'Invalid participation state.'];
            }

            $room = $participant->room;

            // Verify question index matches current
            if ($data['question_index'] !== $room->current_question_index) {
                return ['success' => false, 'message' => 'Question has already ended.'];
            }

            // Check for duplicate answer
            $existingAnswer = StudyBattleAnswer::where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->where('question_index', $data['question_index'])
                ->first();

            if ($existingAnswer) {
                return ['success' => false, 'message' => 'Already answered this question.'];
            }

            // Calculate response time
            $responseTimeMs = now()->diffInMilliseconds($room->question_started_at);
            $maxTimeMs = $room->time_per_question * 1000;

            // Get correct answer
            $currentQuestion = $room->getCurrentQuestion();
            if (!$currentQuestion) {
                return ['success' => false, 'message' => 'Question not found.'];
            }

            $correctAnswer = strtoupper($currentQuestion['correct_answer']);
            $selectedAnswer = strtoupper($data['selected_answer']);
            $isCorrect = $selectedAnswer === $correctAnswer;

            // Calculate points
            $points = $this->calculatePoints($isCorrect, $responseTimeMs, $maxTimeMs, $participant);

            // Save answer
            StudyBattleAnswer::create([
                'room_id' => $room->id,
                'participant_id' => $participant->id,
                'user_id' => $user->id,
                'question_index' => $data['question_index'],
                'selected_answer' => $data['selected_answer'],
                'is_correct' => $isCorrect,
                'response_time_ms' => $responseTimeMs,
                'points_earned' => $points['base'],
                'bonus_points' => $points['bonus'],
                'answered_at' => now(),
            ]);

            // Update participant stats
            $participant->increment('questions_answered');
            $participant->increment('total_score', $points['base'] + $points['bonus']);
            $participant->increment('total_time_ms', $responseTimeMs);

            if ($isCorrect) {
                $participant->increment('correct_answers');
                $participant->increment('current_streak');
                if ($participant->current_streak > $participant->best_streak) {
                    $participant->update(['best_streak' => $participant->current_streak]);
                }
            } else {
                $participant->increment('wrong_answers');
                $participant->update(['current_streak' => 0]);
            }

            // Touch room to signal update
            $room->touch();

            return [
                'success' => true,
                'correct' => $isCorrect,
                'correct_answer' => $correctAnswer,
                'points_earned' => $points['base'] + $points['bonus'],
                'explanation' => $currentQuestion['explanation'] ?? null,
                'current_score' => $participant->fresh()->total_score,
                'current_rank' => $this->calculateRank($room, $participant->fresh()),
            ];
        });
    }

    /**
     * Calculate points for an answer
     */
    public function calculatePoints(bool $isCorrect, int $responseTimeMs, int $maxTimeMs, StudyBattleParticipant $participant): array
    {
        if (!$isCorrect) {
            return ['base' => 0, 'bonus' => 0];
        }

        // Base points
        $basePoints = 100;

        // Speed bonus: 50 points max, linear decrease
        $speedFactor = max(0, 1 - ($responseTimeMs / $maxTimeMs));
        $speedBonus = (int) round($speedFactor * 50);

        // Streak bonus: +10 per consecutive correct, max 50
        $streakBonus = min(50, $participant->current_streak * 10);

        return [
            'base' => $basePoints,
            'bonus' => $speedBonus + $streakBonus,
        ];
    }

    /**
     * Calculate participant's current rank
     */
    public function calculateRank(StudyBattleRoom $room, StudyBattleParticipant $participant): int
    {
        $higherRanked = $room->participants()
            ->whereIn('status', ['playing', 'finished'])
            ->where(function ($query) use ($participant) {
                $query->where('total_score', '>', $participant->total_score)
                    ->orWhere(function ($q) use ($participant) {
                        $q->where('total_score', $participant->total_score)
                            ->where('total_time_ms', '<', $participant->total_time_ms);
                    });
            })
            ->count();

        return $higherRanked + 1;
    }

    /**
     * Advance to next question or end battle
     */
    public function advanceToNextQuestion(StudyBattleRoom $room): void
    {
        $currentIndex = $room->current_question_index;

        // Record timeout answers for players who didn't answer
        $answeredUserIds = StudyBattleAnswer::where('room_id', $room->id)
            ->where('question_index', $currentIndex)
            ->pluck('user_id');

        $room->participants()
            ->where('status', 'playing')
            ->whereNotIn('user_id', $answeredUserIds)
            ->each(function ($participant) use ($room, $currentIndex) {
                StudyBattleAnswer::create([
                    'room_id' => $room->id,
                    'participant_id' => $participant->id,
                    'user_id' => $participant->user_id,
                    'question_index' => $currentIndex,
                    'selected_answer' => null, // Timeout
                    'is_correct' => false,
                    'response_time_ms' => $room->time_per_question * 1000,
                    'points_earned' => 0,
                    'bonus_points' => 0,
                    'answered_at' => now(),
                ]);

                $participant->increment('questions_answered');
                $participant->increment('wrong_answers');
                $participant->update(['current_streak' => 0]);
            });

        // Move to next question or end battle
        if ($room->hasMoreQuestions()) {
            $room->update([
                'current_question_index' => $currentIndex + 1,
                'question_started_at' => now(),
            ]);
        } else {
            $this->endBattle($room);
        }
    }

    /**
     * End the battle and calculate final rankings
     */
    public function endBattle(StudyBattleRoom $room): void
    {
        // Calculate final ranks
        $participants = $room->participants()
            ->whereIn('status', ['playing', 'finished'])
            ->orderByDesc('total_score')
            ->orderBy('total_time_ms')
            ->get();

        foreach ($participants as $index => $participant) {
            $participant->update([
                'rank' => $index + 1,
                'status' => 'finished',
                'finished_at' => now(),
            ]);
        }

        $room->update([
            'status' => 'completed',
            'battle_ended_at' => now(),
        ]);

        Log::info('Study Battle completed', [
            'room_id' => $room->id,
            'winner_id' => $participants->first()?->user_id,
            'participants' => $participants->count(),
        ]);
    }

    /**
     * Build poll response based on room state
     */
    protected function buildPollResponse(StudyBattleRoom $room, StudyBattleParticipant $participant, User $user): array
    {
        $baseResponse = [
            'success' => true,
            'room' => [
                'id' => $room->id,
                'code' => $room->room_code,
                'status' => $room->status,
                'topic' => $room->topic,
                'subject' => $room->subject,
                'difficulty' => $room->difficulty,
                'host_id' => $room->host_user_id,
                'question_count' => $room->question_count,
                'time_per_question' => $room->time_per_question,
            ],
            'poll_interval_ms' => $room->status === 'starting' ? 500 : 2000,
            'server_time' => now()->toIso8601String(),
        ];

        switch ($room->status) {
            case 'waiting':
                return array_merge($baseResponse, $this->buildWaitingResponse($room));
            case 'starting':
                return array_merge($baseResponse, $this->buildStartingResponse($room));
            case 'in_progress':
                return array_merge($baseResponse, $this->buildInProgressResponse($room, $participant));
            case 'completed':
                return array_merge($baseResponse, $this->buildCompletedResponse($room, $participant));
            default:
                return array_merge($baseResponse, ['message' => 'Room ended.']);
        }
    }

    protected function buildWaitingResponse(StudyBattleRoom $room): array
    {
        return [
            'participants' => $room->participants()
                ->with('user:id,name,plan_id')
                ->whereIn('status', ['joined', 'ready'])
                ->get()
                ->map(fn($p) => [
                    'user_id' => $p->user_id,
                    'name' => $p->user->name,
                    'is_host' => $p->is_host,
                    'status' => $p->status,
                ]),
            'can_start' => $room->canStart(),
            'max_players' => $room->max_players,
            'min_players' => $room->min_players,
            'current_players' => $room->active_participants_count,
        ];
    }

    protected function buildStartingResponse(StudyBattleRoom $room): array
    {
        $countdown = max(0, now()->diffInSeconds($room->battle_started_at, false));

        return [
            'countdown_seconds' => $countdown,
            'total_questions' => $room->question_count,
            'time_per_question' => $room->time_per_question,
            'participants' => $room->participants()
                ->with('user:id,name')
                ->whereIn('status', ['joined', 'ready', 'playing'])
                ->get()
                ->map(fn($p) => [
                    'user_id' => $p->user_id,
                    'name' => $p->user->name,
                    'is_host' => $p->is_host,
                ]),
        ];
    }

    protected function buildInProgressResponse(StudyBattleRoom $room, StudyBattleParticipant $participant): array
    {
        $currentQuestion = $room->getCurrentQuestion();
        $currentIndex = $room->current_question_index;

        // Time remaining for current question
        $elapsedMs = now()->diffInMilliseconds($room->question_started_at);
        $remainingMs = max(0, ($room->time_per_question * 1000) - $elapsedMs);

        // Check if user already answered
        $userAnswer = StudyBattleAnswer::where('room_id', $room->id)
            ->where('user_id', $participant->user_id)
            ->where('question_index', $currentIndex)
            ->first();

        // Live leaderboard
        $leaderboard = $room->participants()
            ->whereIn('status', ['playing', 'finished'])
            ->orderByDesc('total_score')
            ->orderBy('total_time_ms')
            ->with('user:id,name')
            ->get()
            ->map(fn($p, $rank) => [
                'rank' => $rank + 1,
                'user_id' => $p->user_id,
                'name' => $p->user->name,
                'score' => $p->total_score,
                'correct' => $p->correct_answers,
                'streak' => $p->current_streak,
            ]);

        return [
            'question' => $currentQuestion ? [
                'index' => $currentIndex,
                'total' => $room->total_questions,
                'question' => $currentQuestion['question'],
                'options' => $currentQuestion['options'],
                // Do NOT include correct_answer here
            ] : null,
            'time_remaining_ms' => $remainingMs,
            'already_answered' => $userAnswer !== null,
            'my_answer' => $userAnswer?->selected_answer,
            'my_score' => $participant->total_score,
            'my_rank' => $this->calculateRank($room, $participant),
            'leaderboard' => $leaderboard,
        ];
    }

    protected function buildCompletedResponse(StudyBattleRoom $room, StudyBattleParticipant $participant): array
    {
        $finalLeaderboard = $room->participants()
            ->whereIn('status', ['playing', 'finished'])
            ->orderByDesc('total_score')
            ->orderBy('total_time_ms')
            ->with('user:id,name,plan_id')
            ->get()
            ->map(fn($p, $rank) => [
                'rank' => $rank + 1,
                'user_id' => $p->user_id,
                'name' => $p->user->name,
                'score' => $p->total_score,
                'correct' => $p->correct_answers,
                'wrong' => $p->wrong_answers,
                'best_streak' => $p->best_streak,
                'accuracy' => $p->accuracy,
                'avg_time_ms' => $p->average_response_time,
                'is_winner' => $rank === 0,
            ]);

        return [
            'final_results' => true,
            'my_rank' => $participant->rank ?? $this->calculateRank($room, $participant),
            'my_score' => $participant->total_score,
            'my_correct' => $participant->correct_answers,
            'my_wrong' => $participant->wrong_answers,
            'my_accuracy' => $participant->accuracy,
            'my_best_streak' => $participant->best_streak,
            'leaderboard' => $finalLeaderboard,
            'questions_with_answers' => $room->questions_data['questions'] ?? [],
        ];
    }

    /**
     * Get user's battle history
     */
    public function getHistory(User $user, int $limit = 20): array
    {
        return StudyBattleParticipant::with(['room:id,room_code,topic,subject,status,battle_ended_at'])
            ->where('user_id', $user->id)
            ->whereHas('room', function ($q) {
                $q->where('status', 'completed');
            })
            ->orderByDesc('finished_at')
            ->limit($limit)
            ->get()
            ->map(fn($p) => [
                'room_code' => $p->room->room_code,
                'topic' => $p->room->topic,
                'subject' => $p->room->subject,
                'rank' => $p->rank,
                'score' => $p->total_score,
                'correct' => $p->correct_answers,
                'wrong' => $p->wrong_answers,
                'accuracy' => $p->accuracy,
                'played_at' => $p->finished_at?->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * Get global leaderboard
     */
    public function getLeaderboard(int $limit = 50): array
    {
        return DB::table('study_battle_participants as p')
            ->join('users as u', 'p.user_id', '=', 'u.id')
            ->join('study_battle_rooms as r', 'p.room_id', '=', 'r.id')
            ->where('r.status', 'completed')
            ->select(
                'p.user_id',
                'u.name',
                DB::raw('COUNT(*) as battles_played'),
                DB::raw('SUM(CASE WHEN p.rank = 1 THEN 1 ELSE 0 END) as wins'),
                DB::raw('SUM(p.total_score) as total_score'),
                DB::raw('AVG(p.total_score) as avg_score'),
                DB::raw('SUM(p.correct_answers) as total_correct'),
                DB::raw('MAX(p.best_streak) as best_streak')
            )
            ->groupBy('p.user_id', 'u.name')
            ->orderByDesc('wins')
            ->orderByDesc('total_score')
            ->limit($limit)
            ->get()
            ->map(fn($row, $index) => [
                'rank' => $index + 1,
                'user_id' => $row->user_id,
                'name' => $row->name,
                'battles_played' => $row->battles_played,
                'wins' => $row->wins,
                'total_score' => $row->total_score,
                'avg_score' => round($row->avg_score),
                'total_correct' => $row->total_correct,
                'best_streak' => $row->best_streak,
            ])
            ->toArray();
    }

    /**
     * Parse quiz response from AI
     */
    protected function parseQuizResponse(string $response): array
    {
        try {
            // Try direct JSON decode first
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['questions'])) {
                return $data;
            }

            // Try to extract JSON from response
            if (preg_match('/\{[\s\S]*"questions"[\s\S]*\}/', $response, $matches)) {
                $data = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE && isset($data['questions'])) {
                    return $data;
                }
            }

            // Try to extract array
            if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
                $questions = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($questions)) {
                    return ['questions' => $questions];
                }
            }

            Log::error('Failed to parse quiz response', ['response' => substr($response, 0, 500)]);
            return ['questions' => []];

        } catch (\Exception $e) {
            Log::error('Quiz parsing exception', ['error' => $e->getMessage()]);
            return ['questions' => []];
        }
    }

    /**
     * Cleanup expired and abandoned rooms
     */
    public function cleanup(): array
    {
        $stats = ['disconnected' => 0, 'expired' => 0, 'deleted' => 0];

        // Detect disconnected players
        $disconnected = StudyBattleParticipant::disconnected(10)->get();
        foreach ($disconnected as $participant) {
            $participant->update(['status' => 'disconnected']);
            $stats['disconnected']++;

            $room = $participant->room;

            // Host disconnect handling
            if ($participant->is_host && $room->status === 'waiting') {
                $nextHost = $room->participants()
                    ->where('status', 'joined')
                    ->where('user_id', '!=', $participant->user_id)
                    ->first();

                if ($nextHost) {
                    $participant->update(['is_host' => false]);
                    $nextHost->update(['is_host' => true]);
                    $room->update(['host_user_id' => $nextHost->user_id]);
                } else {
                    $room->update(['status' => 'abandoned']);
                }
            }

            // During battle, check if only 1 player left
            if ($room->status === 'in_progress') {
                $activePlayers = $room->participants()->playing()->count();
                if ($activePlayers <= 1) {
                    $this->endBattle($room);
                }
            }
        }

        // Cleanup expired waiting rooms
        $expired = StudyBattleRoom::expired()->get();
        foreach ($expired as $room) {
            $room->update(['status' => 'cancelled']);
            $stats['expired']++;
        }

        // Delete old completed rooms (older than 7 days)
        $deleted = StudyBattleRoom::whereIn('status', ['completed', 'cancelled', 'abandoned'])
            ->where('updated_at', '<', now()->subDays(7))
            ->delete();
        $stats['deleted'] = $deleted;

        return $stats;
    }

    /**
     * Get max questions per battle for user based on their plan
     * Returns 0 for unlimited, or the max number
     */
    protected function getMaxQuestionsForUser(User $user): int
    {
        // Admins have no limit
        if ($user->role === 'admin') {
            return 0;
        }

        // Get user's plan
        $plan = null;
        if ($user->plan_id) {
            $plan = DB::table('user_plans')->find($user->plan_id);
        }

        if (!$plan) {
            // Free plan - default max 10
            return 10;
        }

        // Decode features
        $features = $plan->features;
        if (is_string($features)) {
            $features = json_decode($features, true);
        }
        if (is_string($features)) {
            $features = json_decode($features, true);
        }

        // Return 0 for unlimited (-1 in config)
        $maxQuestions = $features['study_battle_max_questions'] ?? 0;
        return $maxQuestions == -1 ? 0 : (int) $maxQuestions;
    }
}
