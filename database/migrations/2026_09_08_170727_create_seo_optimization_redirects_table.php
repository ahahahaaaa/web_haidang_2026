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
        Schema::create('seo_optimization_redirects', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('page_id')->constrained('seo_optimization_pages')->restrictOnDelete();
            $table->foreignUlid('proposal_id')->constrained('seo_optimization_proposals')->restrictOnDelete();
            $table->string('site_id', 80);
            $table->string('locale', 12);
            $table->string('source_path', 700);
            $table->string('source_hash', 64);
            $table->string('target_path', 700);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['site_id', 'locale', 'source_hash'], 'seo_opt_redirect_source_unique');
            $table->index(['page_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_optimization_redirects');
    }
};
