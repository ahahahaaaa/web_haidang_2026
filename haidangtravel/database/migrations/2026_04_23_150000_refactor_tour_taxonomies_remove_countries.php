<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_category_tour', function (Blueprint $table): void {
            $table->foreignId('tour_category_id')->constrained('tour_categories')->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();

            $table->unique(['tour_category_id', 'tour_id']);
        });

        Schema::create('destination_tour', function (Blueprint $table): void {
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();

            $table->unique(['destination_id', 'tour_id']);
        });

        Schema::create('region_tour', function (Blueprint $table): void {
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();

            $table->unique(['region_id', 'tour_id']);
        });

        DB::table('tours')
            ->select(['id', 'tour_category_id', 'destination_id', 'region_id'])
            ->orderBy('id')
            ->chunkById(200, function ($tours): void {
                $categoryRows = [];
                $destinationRows = [];
                $regionRows = [];

                foreach ($tours as $tour) {
                    if ($tour->tour_category_id) {
                        $categoryRows[] = [
                            'tour_category_id' => (int) $tour->tour_category_id,
                            'tour_id' => (int) $tour->id,
                        ];
                    }

                    if ($tour->destination_id) {
                        $destinationRows[] = [
                            'destination_id' => (int) $tour->destination_id,
                            'tour_id' => (int) $tour->id,
                        ];
                    }

                    if ($tour->region_id) {
                        $regionRows[] = [
                            'region_id' => (int) $tour->region_id,
                            'tour_id' => (int) $tour->id,
                        ];
                    }
                }

                if ($categoryRows !== []) {
                    DB::table('tour_category_tour')->insertOrIgnore($categoryRows);
                }

                if ($destinationRows !== []) {
                    DB::table('destination_tour')->insertOrIgnore($destinationRows);
                }

                if ($regionRows !== []) {
                    DB::table('region_tour')->insertOrIgnore($regionRows);
                }
            });

        Schema::table('tours', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('country_id');
        });

        Schema::table('destinations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('country_id');
        });

        Schema::table('regions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('country_id');
        });

        Schema::dropIfExists('countries');
    }

    public function down(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('iso_code', 10)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('published')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->string('cover_alt')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots_directive')->default('index,follow');
            $table->json('schema')->nullable();
            $table->json('gallery')->nullable();
            $table->timestamps();
        });

        Schema::table('regions', function (Blueprint $table): void {
            $table->foreignId('country_id')->nullable()->after('id')->constrained('countries')->nullOnDelete();
        });

        Schema::table('destinations', function (Blueprint $table): void {
            $table->foreignId('country_id')->nullable()->after('id')->constrained('countries')->nullOnDelete();
        });

        Schema::table('tours', function (Blueprint $table): void {
            $table->foreignId('country_id')->nullable()->after('region_id')->constrained('countries')->nullOnDelete();
        });

        Schema::dropIfExists('region_tour');
        Schema::dropIfExists('destination_tour');
        Schema::dropIfExists('tour_category_tour');
    }
};
