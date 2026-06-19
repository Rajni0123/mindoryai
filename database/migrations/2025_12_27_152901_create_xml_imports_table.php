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
        Schema::create('xml_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('filename');
            $table->string('file_hash')->nullable(); // SHA256 hash to prevent duplicate imports
            $table->string('version')->nullable(); // XML version tag if present
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->integer('records_processed')->default(0);
            $table->integer('records_success')->default(0);
            $table->integer('records_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->longText('import_log')->nullable(); // Detailed log of what was imported
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index('admin_id');
            $table->index('status');
            $table->index('file_hash');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xml_imports');
    }
};
