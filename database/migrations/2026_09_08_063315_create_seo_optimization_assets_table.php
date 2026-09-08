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
        Schema::create('seo_optimization_assets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('task_id')->unique()->constrained('seo_optimization_tasks')->restrictOnDelete();
            $table->foreignUlid('page_id')->constrained('seo_optimization_pages')->restrictOnDelete();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('sheet_revision', 100);
            $table->string('source_type', 30);
            $table->text('source_url')->nullable();
            $table->text('prompt')->nullable();
            $table->string('alt', 255);
            $table->string('sha256', 64);
            $table->string('request_hash', 64);
            $table->string('manifest_hash', 64);
            $table->json('manifest');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_optimization_assets');
    }
};
