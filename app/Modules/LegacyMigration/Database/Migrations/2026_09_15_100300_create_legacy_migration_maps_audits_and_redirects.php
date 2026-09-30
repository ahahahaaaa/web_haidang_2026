<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_object_maps', function (Blueprint $table): void {
            $table->id();
            $table->char('source_identity', 64)->unique();
            $table->string('source_system', 80);
            $table->string('object_type', 80);
            $table->string('legacy_id', 190)->nullable();
            $table->string('legacy_key', 255)->nullable();
            $table->string('target_type', 80);
            $table->string('target_id', 190);
            $table->char('last_checksum', 64);
            $table->boolean('is_partial')->default(false);
            $table->foreignId('first_run_id')->nullable()->constrained('legacy_migration_runs')->nullOnDelete();
            $table->foreignId('last_run_id')->nullable()->constrained('legacy_migration_runs')->nullOnDelete();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index(['source_system', 'object_type', 'legacy_id'], 'legacy_maps_source_lookup');
        });

        Schema::create('legacy_cast_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->nullable()->constrained('legacy_migration_runs')->nullOnDelete();
            $table->foreignId('staged_url_id')->nullable()->constrained('legacy_staged_urls')->nullOnDelete();
            $table->foreignId('staged_object_id')->nullable()->constrained('legacy_staged_objects')->nullOnDelete();
            $table->string('target_type', 80)->nullable();
            $table->string('target_id', 190)->nullable();
            $table->string('action', 40);
            $table->string('merge_policy', 40)->nullable();
            $table->string('timestamp_policy', 40)->nullable();
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('source_updated_at')->nullable();
            $table->dateTime('previous_target_created_at')->nullable();
            $table->dateTime('previous_target_updated_at')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('completed');
            $table->text('error_text')->nullable();
            $table->timestamps();

            $table->index(['run_id', 'created_at']);
        });

        Schema::create('legacy_migration_redirects', function (Blueprint $table): void {
            $table->id();
            $table->char('source_hash', 64)->unique();
            $table->string('source_path', 700);
            $table->string('target_path', 700);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('run_id')->nullable()->constrained('legacy_migration_runs')->nullOnDelete();
            $table->foreignId('staged_url_id')->nullable()->constrained('legacy_staged_urls')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_redirects');
        Schema::dropIfExists('legacy_cast_audits');
        Schema::dropIfExists('legacy_object_maps');
    }
};
