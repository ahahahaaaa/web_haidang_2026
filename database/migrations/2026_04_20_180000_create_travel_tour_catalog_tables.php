<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('scope')->nullable()->index();
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

        Schema::create('regions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('scope')->nullable()->index();
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

        Schema::create('destinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('scope')->nullable()->index();
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

        Schema::table('tours', function (Blueprint $table): void {
            $table->foreignId('tour_category_id')->nullable()->after('scope')->constrained('tour_categories')->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->after('tour_category_id')->constrained('destinations')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->after('destination_id')->constrained('regions')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->after('region_id')->constrained('countries')->nullOnDelete();
        });

        Schema::create('tour_departures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->date('departure_date')->nullable()->index();
            $table->date('return_date')->nullable();
            $table->string('departure_location')->nullable();
            $table->string('transport_label')->nullable();
            $table->string('standard_label')->nullable();
            $table->unsignedBigInteger('base_price')->nullable();
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->unsignedSmallInteger('available_slots')->nullable();
            $table->string('pricing_note')->nullable();
            $table->string('status')->default('scheduled')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tour_id', 'departure_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_departures');

        Schema::table('tours', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('country_id');
            $table->dropConstrainedForeignId('region_id');
            $table->dropConstrainedForeignId('destination_id');
            $table->dropConstrainedForeignId('tour_category_id');
        });

        Schema::dropIfExists('destinations');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('tour_categories');
    }
};
