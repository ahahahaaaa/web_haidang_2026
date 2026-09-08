<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('scope')->index();
            $table->foreignId('destination_category_id')->nullable()->constrained('content_categories')->nullOnDelete();
            $table->foreignId('region_category_id')->nullable()->constrained('content_categories')->nullOnDelete();
            $table->string('transport')->nullable();
            $table->string('departure_location')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('duration_nights')->nullable();
            $table->string('standard_label')->nullable();
            $table->unsignedBigInteger('base_price')->nullable();
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->decimal('rating_average', 3, 1)->nullable();
            $table->unsignedInteger('rating_count')->nullable();
            $table->string('cta_mode')->default('book_now');
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
            $table->json('itinerary')->nullable();
            $table->json('pricing_table')->nullable();
            $table->json('departure_schedules')->nullable();
            $table->json('inclusions')->nullable();
            $table->json('faq_items')->nullable();
            $table->json('gallery')->nullable();
            $table->timestamps();

            $table->index(['scope', 'status', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
