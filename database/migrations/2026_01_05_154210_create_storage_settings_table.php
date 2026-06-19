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
        Schema::create('storage_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Production S3", "Backup R2"
            $table->string('driver'); // local, s3, r2, spaces, gcs
            $table->text('key')->nullable();
            $table->text('secret')->nullable();
            $table->string('region')->nullable();
            $table->string('bucket')->nullable();
            $table->string('endpoint')->nullable(); // For custom S3-compatible services
            $table->string('cdn_url')->nullable(); // Optional CDN URL
            $table->string('url_prefix')->nullable(); // Path prefix for files
            $table->boolean('is_active')->default(false); // Only one should be active at a time
            $table->unsignedBigInteger('max_file_size')->default(10485760); // 10MB default
            $table->json('allowed_extensions')->nullable(); // e.g., ['jpg', 'png', 'pdf']
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_settings');
    }
};
