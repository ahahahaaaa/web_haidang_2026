<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_url_mappings', function (Blueprint $table): void {
            $table->id();
            $table->char('source_hash', 64)->unique();
            $table->string('source_path', 700);
            $table->string('mode', 40)->index();
            $table->string('target_type', 80)->nullable();
            $table->string('target_id', 190)->nullable();
            $table->string('target_path', 700);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true)->index();
            $table->string('origin', 80)->nullable();
            $table->json('context_json')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_url_mappings');
    }
};
