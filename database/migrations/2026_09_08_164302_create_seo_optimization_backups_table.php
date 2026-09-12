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
        Schema::create('seo_optimization_backups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('page_id')->constrained('seo_optimization_pages')->restrictOnDelete();
            $table->foreignUlid('proposal_id')->unique()->constrained('seo_optimization_proposals')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_version', 100);
            $table->string('owner_type', 80);
            $table->string('owner_id', 100);
            $table->json('content_snapshot');
            $table->json('media_snapshot')->nullable();
            $table->json('publish_snapshot')->nullable();
            $table->string('checksum', 64);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['page_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_optimization_backups');
    }
};
