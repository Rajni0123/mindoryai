<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert whiteboard video settings
        DB::table('settings')->insert([
            [
                'key' => 'whiteboard_video_enabled',
                'value' => '1',
                'group' => 'features',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'whiteboard_video_model_id',
                'value' => '15', // Gemini 1.5 Flash
                'group' => 'features',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'whiteboard_video_credit_cost',
                'value' => '50',
                'group' => 'features',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'whiteboard_video_enabled',
            'whiteboard_video_model_id',
            'whiteboard_video_credit_cost',
        ])->delete();
    }
};
