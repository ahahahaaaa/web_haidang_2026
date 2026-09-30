<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_staged_urls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs')->cascadeOnDelete();
            $table->foreignId('chunk_id')->nullable()->constrained('legacy_migration_chunks')->nullOnDelete();
            $table->string('raw_url', 1000)->nullable();
            $table->string('raw_path', 700);
            $table->string('normalized_path', 700);
            $table->char('path_hash', 64);
            $table->string('resolved_path', 700)->nullable();
            $table->string('route_kind', 80);
            $table->string('root_object_key', 255)->nullable();
            $table->json('payload_json');
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->decimal('ctr', 12, 8)->nullable();
            $table->decimal('position', 12, 4)->nullable();
            $table->string('mapping_mode', 40)->nullable();
            $table->string('merge_policy', 40)->default('fill_blanks');
            $table->string('timestamp_policy', 40)->default('source');
            $table->string('target_type', 80)->nullable();
            $table->string('target_id', 190)->nullable();
            $table->string('target_route', 190)->nullable();
            $table->json('target_parameters_json')->nullable();
            $table->string('target_path', 700)->nullable();
            $table->unsignedSmallInteger('redirect_code')->default(301);
            $table->string('status', 40)->default('pending')->index();
            $table->foreignId('mapped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('mapped_at')->nullable();
            $table->dateTime('casted_at')->nullable();
            $table->text('error_text')->nullable();
            $table->timestamps();

            $table->unique(['run_id', 'path_hash'], 'legacy_urls_run_path_unique');
            $table->index(['run_id', 'status']);
            $table->index(['target_type', 'target_id']);
            $table->index(['route_kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_staged_urls');
    }
};
