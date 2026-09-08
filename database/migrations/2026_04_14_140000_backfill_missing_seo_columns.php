<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillContentClusters();
        $this->backfillSeoPages();
        $this->backfillSeoLinks();
    }

    public function down(): void
    {
        //
    }

    protected function backfillContentClusters(): void
    {
        if (! Schema::hasTable('content_clusters')) {
            return;
        }

        $missingColumns = array_filter([
            'business_value' => ! Schema::hasColumn('content_clusters', 'business_value'),
            'priority_score' => ! Schema::hasColumn('content_clusters', 'priority_score'),
            'context' => ! Schema::hasColumn('content_clusters', 'context'),
        ]);

        if ($missingColumns === []) {
            return;
        }

        Schema::table('content_clusters', function (Blueprint $table) use ($missingColumns) {
            if ($missingColumns['business_value'] ?? false) {
                $table->text('business_value')->nullable()->after('target_page_type');
            }

            if ($missingColumns['priority_score'] ?? false) {
                $table->unsignedInteger('priority_score')->default(50)->after('business_value');
            }

            if ($missingColumns['context'] ?? false) {
                $table->json('context')->nullable()->after('status');
            }
        });
    }

    protected function backfillSeoPages(): void
    {
        if (! Schema::hasTable('seo_pages')) {
            return;
        }

        $missingColumns = array_filter([
            'page_type' => ! Schema::hasColumn('seo_pages', 'page_type'),
            'canonical_url' => ! Schema::hasColumn('seo_pages', 'canonical_url'),
            'primary_keyword' => ! Schema::hasColumn('seo_pages', 'primary_keyword'),
            'secondary_keywords' => ! Schema::hasColumn('seo_pages', 'secondary_keywords'),
            'h1' => ! Schema::hasColumn('seo_pages', 'h1'),
            'excerpt' => ! Schema::hasColumn('seo_pages', 'excerpt'),
            'og_title' => ! Schema::hasColumn('seo_pages', 'og_title'),
            'og_description' => ! Schema::hasColumn('seo_pages', 'og_description'),
            'og_image' => ! Schema::hasColumn('seo_pages', 'og_image'),
            'faq_items' => ! Schema::hasColumn('seo_pages', 'faq_items'),
            'qa_report' => ! Schema::hasColumn('seo_pages', 'qa_report'),
            'generation_payload' => ! Schema::hasColumn('seo_pages', 'generation_payload'),
            'published_at' => ! Schema::hasColumn('seo_pages', 'published_at'),
        ]);

        if ($missingColumns === []) {
            return;
        }

        Schema::table('seo_pages', function (Blueprint $table) use ($missingColumns) {
            if ($missingColumns['page_type'] ?? false) {
                $table->string('page_type')->nullable()->after('content_cluster_id');
            }

            if ($missingColumns['canonical_url'] ?? false) {
                $table->string('canonical_url')->nullable()->after('slug');
            }

            if ($missingColumns['primary_keyword'] ?? false) {
                $table->string('primary_keyword')->nullable()->after('canonical_url');
            }

            if ($missingColumns['secondary_keywords'] ?? false) {
                $table->json('secondary_keywords')->nullable()->after('primary_keyword');
            }

            if ($missingColumns['h1'] ?? false) {
                $table->string('h1')->nullable()->after('secondary_keywords');
            }

            if ($missingColumns['excerpt'] ?? false) {
                $table->text('excerpt')->nullable()->after('h1');
            }

            if ($missingColumns['og_title'] ?? false) {
                $table->string('og_title')->nullable()->after('meta_description');
            }

            if ($missingColumns['og_description'] ?? false) {
                $table->text('og_description')->nullable()->after('og_title');
            }

            if ($missingColumns['og_image'] ?? false) {
                $table->string('og_image')->nullable()->after('og_description');
            }

            if ($missingColumns['faq_items'] ?? false) {
                $table->json('faq_items')->nullable()->after('schema');
            }

            if ($missingColumns['qa_report'] ?? false) {
                $table->json('qa_report')->nullable()->after('faq_items');
            }

            if ($missingColumns['generation_payload'] ?? false) {
                $table->json('generation_payload')->nullable()->after('qa_report');
            }

            if ($missingColumns['published_at'] ?? false) {
                $table->timestamp('published_at')->nullable()->after('status');
            }
        });
    }

    protected function backfillSeoLinks(): void
    {
        if (! Schema::hasTable('seo_links')) {
            return;
        }

        $missingColumns = array_filter([
            'link_type' => ! Schema::hasColumn('seo_links', 'link_type'),
            'priority' => ! Schema::hasColumn('seo_links', 'priority'),
            'status' => ! Schema::hasColumn('seo_links', 'status'),
        ]);

        if ($missingColumns === []) {
            return;
        }

        Schema::table('seo_links', function (Blueprint $table) use ($missingColumns) {
            if ($missingColumns['link_type'] ?? false) {
                $table->string('link_type')->default('related')->after('anchor_text');
            }

            if ($missingColumns['priority'] ?? false) {
                $table->unsignedInteger('priority')->default(50)->after('link_type');
            }

            if ($missingColumns['status'] ?? false) {
                $table->string('status')->default('suggested')->after('priority');
            }
        });
    }
};
