<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tour_review_batches')) {
            Schema::create('tour_review_batches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
                $table->foreignId('tour_departure_id')->nullable()->constrained('tour_departures')->nullOnDelete();
                $table->date('departure_date')->nullable()->index();
                $table->string('label')->nullable();
                $table->boolean('enabled')->default(true)->index();
                $table->text('password')->nullable();
                $table->string('token', 64)->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['tour_id', 'departure_date']);
            });
        }

        if (Schema::hasTable('travel_reviews') && ! Schema::hasColumn('travel_reviews', 'tour_review_batch_id')) {
            Schema::table('travel_reviews', function (Blueprint $table): void {
                $table->foreignId('tour_review_batch_id')
                    ->nullable()
                    ->after('reviewable_id')
                    ->constrained('tour_review_batches')
                    ->nullOnDelete();
            });
        }

        $this->backfillLegacyTourReviewLinks();
    }

    public function down(): void
    {
        if (Schema::hasTable('travel_reviews') && Schema::hasColumn('travel_reviews', 'tour_review_batch_id')) {
            Schema::table('travel_reviews', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('tour_review_batch_id');
            });
        }

        Schema::dropIfExists('tour_review_batches');
    }

    protected function backfillLegacyTourReviewLinks(): void
    {
        if (! Schema::hasTable('tours')
            || ! Schema::hasTable('tour_review_batches')
            || ! Schema::hasColumn('tours', 'review_submission_token')) {
            return;
        }

        DB::table('tours')
            ->select(['id', 'review_submission_enabled', 'review_submission_password', 'review_submission_token'])
            ->whereNotNull('review_submission_token')
            ->where('review_submission_token', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($tours): void {
                foreach ($tours as $tour) {
                    $token = trim((string) $tour->review_submission_token);

                    if ($token === '' || DB::table('tour_review_batches')->where('token', $token)->exists()) {
                        continue;
                    }

                    DB::table('tour_review_batches')->insert([
                        'created_at' => now(),
                        'departure_date' => null,
                        'enabled' => (bool) $tour->review_submission_enabled,
                        'label' => 'Lượt đánh giá mặc định',
                        'password' => $tour->review_submission_password,
                        'sort_order' => 0,
                        'token' => $token,
                        'tour_departure_id' => null,
                        'tour_id' => $tour->id,
                        'updated_at' => now(),
                    ]);
                }
            });
    }
};
