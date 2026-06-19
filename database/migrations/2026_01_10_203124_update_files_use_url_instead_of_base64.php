<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add file_url column to mobile_chat_messages table (if not exists)
        if (!Schema::hasColumn('mobile_chat_messages', 'file_url')) {
            Schema::table('mobile_chat_messages', function (Blueprint $table) {
                $table->string('file_url')->nullable()->after('content');
                $table->string('file_name')->nullable()->after('file_url');
                $table->string('file_type')->nullable()->after('file_name');
                $table->unsignedBigInteger('file_size')->nullable()->after('file_type');
            });
        }

        // Note: storage_settings table already exists with correct structure
        // Just insert default R2 storage configuration if not exists
        DB::table('storage_settings')->updateOrInsert(
            ['name' => 'Cloudflare R2 Default'],
            [
                'name' => 'Cloudflare R2 Default',
                'driver' => 'r2',
                'key' => null,
                'secret' => null,
                'region' => 'auto',
                'bucket' => null,
                'endpoint' => null,
                'cdn_url' => null,
                'url_prefix' => null,
                'is_active' => false,
                'max_file_size' => 10485760, // 10MB
                'allowed_extensions' => json_encode(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']),
                'notes' => 'Default Cloudflare R2 configuration - add your credentials to activate',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobile_chat_messages', function (Blueprint $table) {
            $table->dropColumn(['file_url', 'file_name', 'file_type', 'file_size']);
        });
    }
};
