<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_clusters')) {
            Schema::table('content_clusters', function (Blueprint $table) {
                if (! Schema::hasColumn('content_clusters', 'business_value')) {
                    $table->text('business_value')->nullable()->after('target_page_type');
                }

                if (! Schema::hasColumn('content_clusters', 'priority_score')) {
                    $table->unsignedInteger('priority_score')->default(50)->after('business_value');
                }

                if (! Schema::hasColumn('content_clusters', 'context')) {
                    $table->json('context')->nullable()->after('status');
                }
            });
        }

        if (Schema::hasTable('seo_pages')) {
            Schema::table('seo_pages', function (Blueprint $table) {
                if (! Schema::hasColumn('seo_pages', 'page_type')) {
                    $table->string('page_type')->nullable()->after('content_cluster_id');
                }

                if (! Schema::hasColumn('seo_pages', 'canonical_url')) {
                    $table->string('canonical_url')->nullable()->after('slug');
                }

                if (! Schema::hasColumn('seo_pages', 'primary_keyword')) {
                    $table->string('primary_keyword')->nullable()->after('canonical_url');
                }

                if (! Schema::hasColumn('seo_pages', 'secondary_keywords')) {
                    $table->json('secondary_keywords')->nullable()->after('primary_keyword');
                }

                if (! Schema::hasColumn('seo_pages', 'h1')) {
                    $table->string('h1')->nullable()->after('secondary_keywords');
                }

                if (! Schema::hasColumn('seo_pages', 'excerpt')) {
                    $table->text('excerpt')->nullable()->after('h1');
                }

                if (! Schema::hasColumn('seo_pages', 'og_title')) {
                    $table->string('og_title')->nullable()->after('meta_description');
                }

                if (! Schema::hasColumn('seo_pages', 'og_description')) {
                    $table->text('og_description')->nullable()->after('og_title');
                }

                if (! Schema::hasColumn('seo_pages', 'og_image')) {
                    $table->string('og_image')->nullable()->after('og_description');
                }

                if (! Schema::hasColumn('seo_pages', 'faq_items')) {
                    $table->json('faq_items')->nullable()->after('schema');
                }

                if (! Schema::hasColumn('seo_pages', 'qa_report')) {
                    $table->json('qa_report')->nullable()->after('faq_items');
                }

                if (! Schema::hasColumn('seo_pages', 'generation_payload')) {
                    $table->json('generation_payload')->nullable()->after('qa_report');
                }

                if (! Schema::hasColumn('seo_pages', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('status');
                }
            });
        }

        if (Schema::hasTable('seo_links')) {
            Schema::table('seo_links', function (Blueprint $table) {
                if (! Schema::hasColumn('seo_links', 'link_type')) {
                    $table->string('link_type')->default('related')->after('anchor_text');
                }

                if (! Schema::hasColumn('seo_links', 'priority')) {
                    $table->unsignedInteger('priority')->default(50)->after('link_type');
                }

                if (! Schema::hasColumn('seo_links', 'status')) {
                    $table->string('status')->default('suggested')->after('priority');
                }
            });
        }
    }

    public function down(): void
    {
        //
    }
};
