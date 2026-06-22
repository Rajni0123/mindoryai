<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('knowledge_sources')) {
            Schema::create('knowledge_sources', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('provider_key')->unique();
                $table->string('type'); // pdf, docx, txt, markdown, url, zip, question_bank
                $table->string('source_path')->nullable();
                $table->json('metadata')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('chunk_count')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('document_chunks')) {
            Schema::create('document_chunks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('knowledge_source_id')->nullable()->constrained('knowledge_sources')->nullOnDelete();
                $table->string('provider_key')->default('ncert')->index();
                $table->longText('content');
                $table->json('embedding')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['provider_key', 'knowledge_source_id']);
            });
        }

        if (! Schema::hasTable('temporary_pdf_retrievals')) {
            Schema::create('temporary_pdf_retrievals', function (Blueprint $table) {
                $table->id();
                $table->string('cache_key')->unique();
                $table->string('source_url');
                $table->longText('extracted_text')->nullable();
                $table->json('chunks')->nullable();
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->index('expires_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_pdf_retrievals');
        Schema::dropIfExists('document_chunks');
        Schema::dropIfExists('knowledge_sources');
    }
};
