<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_migration_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('source_system', 80);
            $table->uuid('source_run_id');
            $table->string('schema_version', 80);
            $table->string('source_filename')->nullable();
            $table->string('status', 40)->default('receiving')->index();
            $table->unsignedInteger('expected_chunks');
            $table->unsignedInteger('received_chunks')->default(0);
            $table->unsignedInteger('expected_urls');
            $table->unsignedInteger('staged_urls')->default(0);
            $table->unsignedInteger('mapped_urls')->default(0);
            $table->unsignedInteger('casted_urls')->default(0);
            $table->unsignedInteger('blocked_urls')->default(0);
            $table->unsignedInteger('failed_urls')->default(0);
            $table->json('summary_json')->nullable();
            $table->text('error_text')->nullable();
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('finalized_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['source_system', 'source_run_id'], 'legacy_runs_source_unique');
        });

        Schema::create('legacy_migration_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->uuid('source_chunk_uuid');
            $table->string('idempotency_key', 190)->unique();
            $table->char('payload_hash', 64);
            $table->string('payload_disk', 80);
            $table->string('payload_path', 700);
            $table->unsignedBigInteger('payload_bytes');
            $table->unsignedInteger('url_count')->default(0);
            $table->unsignedInteger('object_count')->default(0);
            $table->string('status', 40)->default('accepted')->index();
            $table->unsignedInteger('attempts')->default(1);
            $table->text('error_text')->nullable();
            $table->dateTime('received_at');
            $table->timestamps();

            $table->unique(['run_id', 'sequence'], 'legacy_chunks_run_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_chunks');
        Schema::dropIfExists('legacy_migration_runs');
    }
};
