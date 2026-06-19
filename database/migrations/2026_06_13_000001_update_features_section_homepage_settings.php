<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_settings')) {
            return;
        }

        DB::table('homepage_settings')
            ->where('key', 'features_title')
            ->whereIn('value', ['Academic Capabilities', ''])
            ->update(['value' => 'Precision Tools for Top Percentiles']);

        DB::table('homepage_settings')
            ->where('key', 'features_description')
            ->whereIn('value', [
                'Everything you need to ace your exams and understand concepts deeply.',
                '',
            ])
            ->update([
                'value' => 'Eliminate guesswork from your preparation with our suite of AI-driven cognitive analysis tools.',
            ]);

        if (class_exists(\App\Models\HomepageSetting::class)) {
            \App\Models\HomepageSetting::clearCache();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_settings')) {
            return;
        }

        DB::table('homepage_settings')
            ->where('key', 'features_title')
            ->where('value', 'Precision Tools for Top Percentiles')
            ->update(['value' => 'Academic Capabilities']);

        DB::table('homepage_settings')
            ->where('key', 'features_description')
            ->where('value', 'Eliminate guesswork from your preparation with our suite of AI-driven cognitive analysis tools.')
            ->update(['value' => 'Everything you need to ace your exams and understand concepts deeply.']);
    }
};
