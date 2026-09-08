<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tours') && ! Schema::hasColumn('tours', 'reviews')) {
            $afterColumn = Schema::hasColumn('tours', 'faq_items') ? 'faq_items' : 'schema';

            Schema::table('tours', function (Blueprint $table) use ($afterColumn): void {
                $table->json('reviews')->nullable()->after($afterColumn);
            });
        }

        if (Schema::hasTable('tour_categories') && ! Schema::hasColumn('tour_categories', 'reviews')) {
            $afterColumn = Schema::hasColumn('tour_categories', 'faq_items')
                ? 'faq_items'
                : (Schema::hasColumn('tour_categories', 'gallery') ? 'gallery' : 'schema');

            Schema::table('tour_categories', function (Blueprint $table) use ($afterColumn): void {
                $table->json('reviews')->nullable()->after($afterColumn);
            });
        }

        if (Schema::hasTable('destinations') && ! Schema::hasColumn('destinations', 'reviews')) {
            $afterColumn = Schema::hasColumn('destinations', 'faq_items')
                ? 'faq_items'
                : (Schema::hasColumn('destinations', 'gallery') ? 'gallery' : 'schema');

            Schema::table('destinations', function (Blueprint $table) use ($afterColumn): void {
                $table->json('reviews')->nullable()->after($afterColumn);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('destinations') && Schema::hasColumn('destinations', 'reviews')) {
            Schema::table('destinations', function (Blueprint $table): void {
                $table->dropColumn('reviews');
            });
        }

        if (Schema::hasTable('tour_categories') && Schema::hasColumn('tour_categories', 'reviews')) {
            Schema::table('tour_categories', function (Blueprint $table): void {
                $table->dropColumn('reviews');
            });
        }

        if (Schema::hasTable('tours') && Schema::hasColumn('tours', 'reviews')) {
            Schema::table('tours', function (Blueprint $table): void {
                $table->dropColumn('reviews');
            });
        }
    }
};
