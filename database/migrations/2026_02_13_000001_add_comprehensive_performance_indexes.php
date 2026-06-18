<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Comprehensive performance optimization indexes.
     * Targets the most frequently queried tables to reduce response times.
     */
    public function up(): void
    {
        // ========================================
        // MOBILE CHAT PERFORMANCE INDEXES
        // ========================================

        // mobile_chats: Optimize user's chat list queries
        if (Schema::hasTable('mobile_chats')) {
            Schema::table('mobile_chats', function (Blueprint $table) {
                // Index for user's chat list: WHERE user_id = ? ORDER BY last_message_at DESC
                if (!$this->hasIndex('mobile_chats', 'mobile_chats_user_last_msg_idx')) {
                    $table->index(['user_id', 'last_message_at'], 'mobile_chats_user_last_msg_idx');
                }
            });
        }

        // mobile_chat_messages: Optimize message history queries
        if (Schema::hasTable('mobile_chat_messages')) {
            Schema::table('mobile_chat_messages', function (Blueprint $table) {
                // Index for chat message history: WHERE mobile_chat_id = ? ORDER BY id
                if (!$this->hasIndex('mobile_chat_messages', 'mobile_chat_msgs_chat_id_idx')) {
                    $table->index(['mobile_chat_id', 'id'], 'mobile_chat_msgs_chat_id_idx');
                }
            });
        }

        // ========================================
        // DAILY USAGE LIMITS PERFORMANCE INDEXES
        // ========================================

        if (Schema::hasTable('daily_usage_limits')) {
            Schema::table('daily_usage_limits', function (Blueprint $table) {
                // Composite index for daily checks: WHERE user_id = ? AND usage_date = ?
                if (!$this->hasIndex('daily_usage_limits', 'daily_usage_user_date_idx')) {
                    $table->index(['user_id', 'usage_date'], 'daily_usage_user_date_idx');
                }
            });
        }

        // ========================================
        // USER PLANS PERFORMANCE INDEXES
        // ========================================

        if (Schema::hasTable('user_plans')) {
            Schema::table('user_plans', function (Blueprint $table) {
                // Index for free plan lookup: WHERE slug = 'free' AND is_active = 1
                if (!$this->hasIndex('user_plans', 'user_plans_slug_active_idx')) {
                    $table->index(['slug', 'is_active'], 'user_plans_slug_active_idx');
                }
            });
        }

        // ========================================
        // QUIZ & EXAM PERFORMANCE INDEXES
        // ========================================

        if (Schema::hasTable('exam_questions')) {
            Schema::table('exam_questions', function (Blueprint $table) {
                // Index for exam questions: WHERE exam_id = ? AND is_active = 1
                if (!$this->hasIndex('exam_questions', 'exam_questions_exam_active_idx')) {
                    $table->index(['exam_id', 'is_active'], 'exam_questions_exam_active_idx');
                }
                // Index for PYQ queries: WHERE exam_id = ? AND year = ?
                if (!$this->hasIndex('exam_questions', 'exam_questions_exam_year_idx')) {
                    $table->index(['exam_id', 'year'], 'exam_questions_exam_year_idx');
                }
            });
        }

        if (Schema::hasTable('mock_tests')) {
            Schema::table('mock_tests', function (Blueprint $table) {
                // Index for user's mock tests: WHERE user_id = ? ORDER BY created_at DESC
                if (!$this->hasIndex('mock_tests', 'mock_tests_user_created_idx')) {
                    $table->index(['user_id', 'created_at'], 'mock_tests_user_created_idx');
                }
            });
        }

        if (Schema::hasTable('quiz_cache')) {
            Schema::table('quiz_cache', function (Blueprint $table) {
                // Index for quiz cache lookups
                if (!$this->hasIndex('quiz_cache', 'quiz_cache_hash_idx')) {
                    $table->index('image_hash', 'quiz_cache_hash_idx');
                }
            });
        }

        // ========================================
        // AI MODELS & TRACKING INDEXES
        // ========================================

        if (Schema::hasTable('ai_models')) {
            Schema::table('ai_models', function (Blueprint $table) {
                // Index for active model lookups: WHERE is_active = 1 ORDER BY order
                if (!$this->hasIndex('ai_models', 'ai_models_active_order_idx')) {
                    $table->index(['is_active', 'order'], 'ai_models_active_order_idx');
                }
                // Index for provider lookups: WHERE provider = ? AND is_active = 1
                if (!$this->hasIndex('ai_models', 'ai_models_provider_active_idx')) {
                    $table->index(['provider', 'is_active'], 'ai_models_provider_active_idx');
                }
            });
        }

        // ========================================
        // FRONTEND CONFIG PERFORMANCE INDEX
        // ========================================

        if (Schema::hasTable('frontend_configs')) {
            Schema::table('frontend_configs', function (Blueprint $table) {
                // Index for config lookups: WHERE is_active = 1
                if (!$this->hasIndex('frontend_configs', 'frontend_configs_active_idx')) {
                    $table->index('is_active', 'frontend_configs_active_idx');
                }
            });
        }

        // ========================================
        // DAILY CHALLENGES PERFORMANCE INDEXES
        // ========================================

        if (Schema::hasTable('daily_challenges')) {
            Schema::table('daily_challenges', function (Blueprint $table) {
                // Index for today's challenge: WHERE challenge_date = ? AND is_active = 1
                if (!$this->hasIndex('daily_challenges', 'daily_challenges_date_active_idx')) {
                    $table->index(['challenge_date', 'is_active'], 'daily_challenges_date_active_idx');
                }
            });
        }

        if (Schema::hasTable('daily_challenge_attempts')) {
            Schema::table('daily_challenge_attempts', function (Blueprint $table) {
                // Index for user attempts - check column exists first
                if (Schema::hasColumn('daily_challenge_attempts', 'daily_challenge_id')) {
                    if (!$this->hasIndex('daily_challenge_attempts', 'daily_attempts_user_challenge_idx')) {
                        $table->index(['user_id', 'daily_challenge_id'], 'daily_attempts_user_challenge_idx');
                    }
                } elseif (Schema::hasColumn('daily_challenge_attempts', 'challenge_id')) {
                    if (!$this->hasIndex('daily_challenge_attempts', 'daily_attempts_user_challenge_idx')) {
                        $table->index(['user_id', 'challenge_id'], 'daily_attempts_user_challenge_idx');
                    }
                }
            });
        }

        // ========================================
        // SUPPORT CHAT PERFORMANCE INDEXES
        // ========================================

        if (Schema::hasTable('support_chats')) {
            Schema::table('support_chats', function (Blueprint $table) {
                // Index for user's support chats
                if (!$this->hasIndex('support_chats', 'support_chats_user_idx')) {
                    $table->index('user_id', 'support_chats_user_idx');
                }
            });
        }

        // ========================================
        // WHITEBOARD VIDEOS PERFORMANCE INDEX
        // ========================================

        if (Schema::hasTable('whiteboard_videos')) {
            Schema::table('whiteboard_videos', function (Blueprint $table) {
                // Index for user's videos: WHERE user_id = ? ORDER BY created_at DESC
                if (!$this->hasIndex('whiteboard_videos', 'whiteboard_videos_user_created_idx')) {
                    $table->index(['user_id', 'created_at'], 'whiteboard_videos_user_created_idx');
                }
                // Index for status checks: WHERE status = ?
                if (!$this->hasIndex('whiteboard_videos', 'whiteboard_videos_status_idx')) {
                    $table->index('status', 'whiteboard_videos_status_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'mobile_chats' => ['mobile_chats_user_last_msg_idx'],
            'mobile_chat_messages' => ['mobile_chat_msgs_chat_id_idx'],
            'daily_usage_limits' => ['daily_usage_user_date_idx'],
            'user_plans' => ['user_plans_slug_active_idx'],
            'exam_questions' => ['exam_questions_exam_active_idx', 'exam_questions_exam_year_idx'],
            'mock_tests' => ['mock_tests_user_created_idx'],
            'quiz_cache' => ['quiz_cache_hash_idx'],
            'ai_models' => ['ai_models_active_order_idx', 'ai_models_provider_active_idx'],
            'frontend_configs' => ['frontend_configs_active_idx'],
            'daily_challenges' => ['daily_challenges_date_active_idx'],
            'daily_challenge_attempts' => ['daily_attempts_user_challenge_idx'],
            'support_chats' => ['support_chats_user_idx'],
            'whiteboard_videos' => ['whiteboard_videos_user_created_idx', 'whiteboard_videos_status_idx'],
        ];

        foreach ($indexes as $table => $indexNames) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexNames) {
                    foreach ($indexNames as $indexName) {
                        if ($this->hasIndex($table->getTable(), $indexName)) {
                            $table->dropIndex($indexName);
                        }
                    }
                });
            }
        }
    }

    /**
     * Check if index exists on table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();

        // For MySQL
        if ($connection->getDriverName() === 'mysql') {
            $indexes = $connection->select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$indexName]
            );
            return count($indexes) > 0;
        }

        // For SQLite
        if ($connection->getDriverName() === 'sqlite') {
            $indexes = $connection->select(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND name = ?",
                [$indexName]
            );
            return count($indexes) > 0;
        }

        return false;
    }
};
