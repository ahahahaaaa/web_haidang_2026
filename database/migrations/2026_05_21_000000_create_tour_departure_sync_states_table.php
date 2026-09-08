<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_departure_sync_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('tour_departure_id')->nullable()->constrained('tour_departures')->nullOnDelete();
            $table->unsignedBigInteger('source_tour_id')->nullable()->index();
            $table->string('tour_code')->nullable()->index();
            $table->unsignedBigInteger('source_startdate_id')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['tour_id', 'source_startdate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_departure_sync_states');
    }
};
