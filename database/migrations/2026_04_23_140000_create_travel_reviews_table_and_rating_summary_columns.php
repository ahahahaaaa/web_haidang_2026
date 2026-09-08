<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tour_categories') && ! Schema::hasColumn('tour_categories', 'rating_average')) {
            Schema::table('tour_categories', function (Blueprint $table): void {
                $table->decimal('rating_average', 3, 1)->nullable()->after('published_at');
                $table->unsignedInteger('rating_count')->nullable()->after('rating_average');
            });
        }

        if (Schema::hasTable('destinations') && ! Schema::hasColumn('destinations', 'rating_average')) {
            Schema::table('destinations', function (Blueprint $table): void {
                $table->decimal('rating_average', 3, 1)->nullable()->after('published_at');
                $table->unsignedInteger('rating_count')->nullable()->after('rating_average');
            });
        }

        if (! Schema::hasTable('travel_reviews')) {
            Schema::create('travel_reviews', function (Blueprint $table): void {
                $table->id();
                $table->morphs('reviewable');
                $table->string('title')->nullable();
                $table->string('author_name');
                $table->string('author_title')->nullable();
                $table->text('content');
                $table->decimal('rating_value', 3, 1);
                $table->string('status')->default('draft')->index();
                $table->boolean('is_featured')->default(false)->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->date('published_at')->nullable()->index();
                $table->timestamps();

                $table->index(['reviewable_type', 'reviewable_id', 'status'], 'travel_reviews_reviewable_status_idx');
            });
        }

        $this->backfillLegacyReviews('tours', \Src\Domains\Cms\Models\Tour::class);
        $this->backfillLegacyReviews('tour_categories', \Src\Domains\Cms\Models\TourCategory::class);
        $this->backfillLegacyReviews('destinations', \Src\Domains\Cms\Models\Destination::class);
    }

    public function down(): void
    {
        if (Schema::hasTable('travel_reviews')) {
            Schema::dropIfExists('travel_reviews');
        }

        if (Schema::hasTable('destinations') && Schema::hasColumn('destinations', 'rating_count')) {
            Schema::table('destinations', function (Blueprint $table): void {
                $table->dropColumn(['rating_average', 'rating_count']);
            });
        }

        if (Schema::hasTable('tour_categories') && Schema::hasColumn('tour_categories', 'rating_count')) {
            Schema::table('tour_categories', function (Blueprint $table): void {
                $table->dropColumn(['rating_average', 'rating_count']);
            });
        }
    }

    protected function backfillLegacyReviews(string $table, string $reviewableType): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'reviews') || ! Schema::hasTable('travel_reviews')) {
            return;
        }

        DB::table($table)
            ->select(['id', 'reviews'])
            ->whereNotNull('reviews')
            ->orderBy('id')
            ->lazy()
            ->each(function (object $row) use ($reviewableType): void {
                $items = $this->decodeReviews($row->reviews);

                if ($items === []) {
                    return;
                }

                $exists = DB::table('travel_reviews')
                    ->where('reviewable_type', $reviewableType)
                    ->where('reviewable_id', $row->id)
                    ->exists();

                if ($exists) {
                    return;
                }

                DB::table('travel_reviews')->insert(
                    collect($items)
                        ->values()
                        ->map(function (array $item, int $index) use ($reviewableType, $row): array {
                            return [
                                'author_name' => $this->plainText($item['author_name'] ?? ''),
                                'author_title' => $this->plainText($item['author_title'] ?? ''),
                                'content' => $this->plainText($item['content'] ?? ''),
                                'created_at' => now(),
                                'is_featured' => $index === 0,
                                'published_at' => $this->normalizeDate($item['published_at'] ?? null),
                                'rating_value' => $this->normalizeRating($item['rating_value'] ?? null) ?? 5.0,
                                'reviewable_id' => $row->id,
                                'reviewable_type' => $reviewableType,
                                'sort_order' => $index,
                                'status' => 'published',
                                'title' => $this->plainText($item['title'] ?? ''),
                                'updated_at' => now(),
                            ];
                        })
                        ->filter(fn (array $item) => $item['author_name'] !== '' && $item['content'] !== '')
                        ->values()
                        ->all()
                );
            });
    }

    protected function decodeReviews(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeRating(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $rating = round((float) $value, 1);

        return $rating >= 1 && $rating <= 5 ? $rating : null;
    }

    protected function plainText(mixed $value): string
    {
        $value = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', (string) $value) ?? (string) $value;
        $value = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $value) ?? $value;
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
};
