<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('Haidang Travel');
            $table->string('site_tagline')->nullable();
            $table->text('site_description')->nullable();
            $table->string('active_theme')->default('haidangtravel');
            $table->string('company_name')->nullable();
            $table->text('about_summary')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('hotline')->nullable();
            $table->string('primary_email')->nullable();
            $table->string('support_email')->nullable();
            $table->string('sales_email')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->string('mail_from_address')->nullable();
            $table->string('mail_contact_recipient')->nullable();
            $table->string('map_embed_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('zalo_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->unsignedInteger('experience_years')->nullable();
            $table->unsignedInteger('completed_projects_count')->nullable();
            $table->unsignedInteger('team_size')->nullable();
            $table->string('quality_badge_label')->nullable();
            $table->string('copyright_text')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('seo_robots')->default('index,follow');
            $table->json('structured_data')->nullable();
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('url');
            $table->string('target')->default('_self');
            $table->string('icon')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('content_categories', function (Blueprint $table) {
            $table->id();
            $table->string('taxonomy');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['taxonomy', 'slug']);
            $table->index(['taxonomy', 'sort_order']);
        });

        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('content_category_id')->nullable()->constrained('content_categories')->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('cover_alt')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots_directive')->default('index,follow');
            $table->json('schema')->nullable();
            $table->unsignedInteger('reading_time_minutes')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('content_category_id')->nullable()->constrained('content_categories')->nullOnDelete();
            $table->foreignId('project_type_id')->nullable()->constrained('project_types')->nullOnDelete();
            $table->string('location')->nullable()->index();
            $table->decimal('area_value', 10, 2)->nullable()->index();
            $table->string('area_unit')->default('m2');
            $table->string('timeline')->nullable();
            $table->date('completion_date')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('cover_alt')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots_directive')->default('index,follow');
            $table->json('schema')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('content_category_id')->nullable()->constrained('content_categories')->nullOnDelete();
            $table->string('icon_class')->nullable();
            $table->string('price_note')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('cover_alt')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots_directive')->default('index,follow');
            $table->json('schema')->nullable();
            $table->timestamps();
        });

        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('hero_badge')->nullable();
            $table->string('hero_title')->nullable();
            $table->text('hero_excerpt')->nullable();
            $table->string('intro_title')->nullable();
            $table->text('intro_excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('cta_title')->nullable();
            $table->text('cta_excerpt')->nullable();
            $table->string('cta_primary_label')->nullable();
            $table->string('cta_primary_url')->nullable();
            $table->string('cta_secondary_label')->nullable();
            $table->string('cta_secondary_url')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots_directive')->default('index,follow');
            $table->json('schema')->nullable();
            $table->timestamps();
        });

        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('autoplay_delay')->nullable();
            $table->timestamps();
        });

        Schema::create('slider_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slider_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('image_link')->nullable();
            $table->string('effect')->default('animate__fadeInUp');
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slider_items');
        Schema::dropIfExists('sliders');
        Schema::dropIfExists('landing_pages');
        Schema::dropIfExists('services');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('project_types');
        Schema::dropIfExists('content_categories');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('site_settings');
    }
};
