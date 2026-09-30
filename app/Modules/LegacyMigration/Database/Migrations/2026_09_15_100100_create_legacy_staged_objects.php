<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_staged_objects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs')->cascadeOnDelete();
            $table->foreignId('chunk_id')->nullable()->constrained('legacy_migration_chunks')->nullOnDelete();
            $table->string('object_key', 255);
            $table->string('object_type', 80);
            $table->string('legacy_id', 190)->nullable();
            $table->string('legacy_key', 255)->nullable();
            $table->boolean('is_partial')->default(false);
            $table->char('checksum', 64);
            $table->json('payload_json');
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('source_updated_at')->nullable();
            $table->string('status', 40)->default('pending');
            $table->text('error_text')->nullable();
            $table->timestamps();

            $table->unique(['run_id', 'object_key'], 'legacy_objects_run_key_unique');
            $table->index(['run_id', 'status']);
            $table->index(['object_type', 'legacy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_staged_objects');
    }
};
