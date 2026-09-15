<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_content_creation_tasks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('credential_id')->constrained('seo_optimization_credentials')->restrictOnDelete();
            $table->string('content_type', 40)->index();
            $table->string('status', 40)->default('drafting')->index();
            $table->json('brief')->nullable();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->string('request_hash', 64);
            $table->string('content_hash', 64)->nullable();
            $table->string('lease_token_hash', 64);
            $table->timestamp('leased_until')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_content_creation_assets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('task_id')->constrained('seo_content_creation_tasks')->restrictOnDelete();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('reference', 100);
            $table->string('sha256', 64);
            $table->json('manifest');
            $table->unique(['task_id', 'reference']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_content_creation_assets');
        Schema::dropIfExists('seo_content_creation_tasks');
    }
};
