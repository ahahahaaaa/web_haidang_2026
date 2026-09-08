<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tour_categories') && ! Schema::hasColumn('tour_categories', 'faq_items')) {
            Schema::table('tour_categories', function (Blueprint $table): void {
                $table->json('faq_items')->nullable()->after('gallery');
            });
        }

        if (Schema::hasTable('destinations') && ! Schema::hasColumn('destinations', 'faq_items')) {
            Schema::table('destinations', function (Blueprint $table): void {
                $table->json('faq_items')->nullable()->after('gallery');
            });
        }

        if (Schema::hasTable('content_categories') && ! Schema::hasColumn('content_categories', 'faq_items')) {
            Schema::table('content_categories', function (Blueprint $table): void {
                $table->json('faq_items')->nullable()->after('description');
            });
        }

        if (Schema::hasTable('blog_posts') && ! Schema::hasColumn('blog_posts', 'faq_items')) {
            Schema::table('blog_posts', function (Blueprint $table): void {
                $table->json('faq_items')->nullable()->after('content');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blog_posts') && Schema::hasColumn('blog_posts', 'faq_items')) {
            Schema::table('blog_posts', function (Blueprint $table): void {
                $table->dropColumn('faq_items');
            });
        }

        if (Schema::hasTable('content_categories') && Schema::hasColumn('content_categories', 'faq_items')) {
            Schema::table('content_categories', function (Blueprint $table): void {
                $table->dropColumn('faq_items');
            });
        }

        if (Schema::hasTable('destinations') && Schema::hasColumn('destinations', 'faq_items')) {
            Schema::table('destinations', function (Blueprint $table): void {
                $table->dropColumn('faq_items');
            });
        }

        if (Schema::hasTable('tour_categories') && Schema::hasColumn('tour_categories', 'faq_items')) {
            Schema::table('tour_categories', function (Blueprint $table): void {
                $table->dropColumn('faq_items');
            });
        }
    }
};
