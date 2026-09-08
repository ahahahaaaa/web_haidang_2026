<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_agency_push_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tour_title')->nullable();
            $table->unsignedBigInteger('source_tour_id')->nullable()->index();
            $table->string('tour_code')->nullable()->index();
            $table->string('trigger')->default('tour_saved')->index();
            $table->string('status')->default('pending')->index();
            $table->string('queue_name')->default('default')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->json('deleted_departures')->nullable();
            $table->json('summary')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'queued_at']);
            $table->index(['tour_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_agency_push_sync_runs');
    }
};
