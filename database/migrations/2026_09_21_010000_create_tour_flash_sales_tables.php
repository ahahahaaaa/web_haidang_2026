<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_flash_sales', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->string('icon_class')->default('fa-solid fa-bolt');
            $table->string('cta_label', 120)->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at'], 'tour_flash_sales_active_window_index');
        });

        Schema::create('tour_flash_sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_flash_sale_id')->constrained('tour_flash_sales')->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('tour_departure_id')->constrained('tour_departures')->cascadeOnDelete();
            $table->unsignedBigInteger('flash_price');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tour_flash_sale_id', 'tour_departure_id'], 'tour_flash_sale_departure_unique');
            $table->index(['tour_flash_sale_id', 'sort_order'], 'tour_flash_sale_items_order_index');
            $table->index(['tour_id', 'tour_departure_id'], 'tour_flash_sale_items_tour_departure_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_flash_sale_items');
        Schema::dropIfExists('tour_flash_sales');
    }
};
